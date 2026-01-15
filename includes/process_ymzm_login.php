<?php
/**
 * YMZM Login Processor
 * Authenticates users against the YMZM API and creates local sessions
 */
session_start();
require_once 'config.php';
require_once 'functions.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('../index.php?page=login');
}

if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    redirect('../index.php?page=login', 'Invalid request. Please try again.', 'error');
}

$email = sanitize($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {
    redirect('../index.php?page=login', 'Please enter both email and password.', 'error');
}

if (!isValidEmail($email)) {
    redirect('../index.php?page=login', 'Please enter a valid email address.', 'error');
}

// Check if YMZM API URL is configured
if (!defined('YMZM_API_URL') || empty(YMZM_API_URL)) {
    redirect('../index.php?page=login', 'YMZM login is not configured. Please contact the administrator.', 'error');
}

// Authenticate against YMZM API
try {
    $ymzmUser = authenticateWithYMZM($email, $password);
    
    if (!$ymzmUser) {
        redirect('../index.php?page=login', 'Invalid email or password.', 'error');
    }
    
    // Check if this YMZM user already exists locally (by email or ymzm_user_id)
    $localUser = findOrCreateYMZMUser($ymzmUser);
    
    if (!$localUser) {
        redirect('../index.php?page=login', 'Failed to create local account. Please try again.', 'error');
    }
    
    // Check if the local user is an admin (admins can't login via customer portal)
    if ($localUser['is_admin']) {
        redirect('../index.php?page=login', 'Admin accounts cannot login here. Please use the admin portal.', 'error');
    }
    
    // Set session variables for logged in user
    $_SESSION['user_id'] = $localUser['user_id'];
    $_SESSION['user_email'] = $localUser['email'];
    $_SESSION['user_name'] = $localUser['first_name'] . ' ' . $localUser['last_name'];
    $_SESSION['ymzm_login'] = true; // Flag to indicate YMZM login
    
    redirect('../index.php', 'Welcome, ' . $localUser['first_name'] . '! You\'re now logged in via YMZM.', 'success');
    
} catch (Exception $e) {
    error_log('YMZM Login Error: ' . $e->getMessage());
    redirect('../index.php?page=login', 'Login failed. Please try again later.', 'error');
}

/**
 * Authenticate user against YMZM API
 * 
 * @param string $email User's email
 * @param string $password User's password
 * @return array|null User data on success, null on failure
 */
function authenticateWithYMZM($email, $password) {
    $apiUrl = YMZM_API_URL;
    
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Accept: application/json'
        ],
        CURLOPT_POSTFIELDS => json_encode([
            'email' => $email,
            'password' => $password
        ]),
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 10,
        // For development on localhost, you may need to disable SSL verification
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($curlError) {
        error_log('YMZM API cURL Error: ' . $curlError);
        throw new Exception('Could not connect to YMZM authentication server.');
    }
    
    $data = json_decode($response, true);
    
    if ($httpCode === 200 && isset($data['status']) && $data['status'] === 'success') {
        return $data['user'];
    }
    
    // Log the error for debugging
    if (isset($data['message'])) {
        error_log('YMZM API Error: ' . $data['message']);
    }
    
    return null;
}

/**
 * Find existing user by YMZM ID or email, or create a new one
 * 
 * @param array $ymzmUser User data from YMZM API
 * @return array|null Local user data on success, null on failure
 */
function findOrCreateYMZMUser($ymzmUser) {
    global $conn;
    
    if (!$conn) {
        throw new Exception('Database connection not available.');
    }
    
    $ymzmUserId = $ymzmUser['id'];
    $email = $ymzmUser['email'];
    $name = $ymzmUser['name'];
    
    // Split name into first and last name
    $nameParts = explode(' ', $name, 2);
    $firstName = $nameParts[0] ?? 'User';
    $lastName = $nameParts[1] ?? '';
    
    // First, try to find user by ymzm_user_id
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE ymzm_user_id = ?");
    mysqli_stmt_bind_param($stmt, "s", $ymzmUserId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $existingUser = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($existingUser) {
        // Update email and name if changed in YMZM
        $stmt = mysqli_prepare($conn, "UPDATE users SET email = ?, first_name = ?, last_name = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "sssi", $email, $firstName, $lastName, $existingUser['user_id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        // Return updated user data
        $existingUser['email'] = $email;
        $existingUser['first_name'] = $firstName;
        $existingUser['last_name'] = $lastName;
        return $existingUser;
    }
    
    // Next, try to find user by email (might have registered locally first)
    $stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE email = ?");
    mysqli_stmt_bind_param($stmt, "s", $email);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $existingUser = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);
    
    if ($existingUser) {
        // Link this existing account to YMZM
        $stmt = mysqli_prepare($conn, "UPDATE users SET ymzm_user_id = ? WHERE user_id = ?");
        mysqli_stmt_bind_param($stmt, "si", $ymzmUserId, $existingUser['user_id']);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        
        $existingUser['ymzm_user_id'] = $ymzmUserId;
        return $existingUser;
    }
    
    // Create new user linked to YMZM account
    // Generate a random password since the user authenticates via YMZM
    $randomPassword = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
    $isVerified = 1;
    
    $stmt = mysqli_prepare($conn, "
        INSERT INTO users (first_name, last_name, email, password, is_verified, ymzm_user_id, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    mysqli_stmt_bind_param($stmt, "ssssss", $firstName, $lastName, $email, $randomPassword, $isVerified, $ymzmUserId);
    
    if (!mysqli_stmt_execute($stmt)) {
        error_log('YMZM User Creation Error: ' . mysqli_stmt_error($stmt));
        mysqli_stmt_close($stmt);
        throw new Exception('Failed to create or update user account.');
    }
    
    $newUserId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    
    return [
        'user_id' => $newUserId,
        'first_name' => $firstName,
        'last_name' => $lastName,
        'email' => $email,
        'is_admin' => 0,
        'is_verified' => 1,
        'ymzm_user_id' => $ymzmUserId
    ];
}
?>
