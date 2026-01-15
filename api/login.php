<?php
// Disable display errors for API to return valid JSON
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Output buffering to catch any unwanted output/warnings
ob_start();

// Enable CORS to allow other websites to access this API
header("Access-Control-Allow-Origin: https://bakeandtake.xyz");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");
header("Content-Type: application/json; charset=UTF-8");

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    ob_clean(); // Clean buffer
    http_response_code(200);
    exit();
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    http_response_code(405); // Method Not Allowed
    echo json_encode(["status" => "error", "message" => "Method not allowed. Use POST."]);
    exit();
}

// Include database configuration
require_once __DIR__ . '/../includes/config.php';

// Clean buffer before sending successful/error response
ob_clean();

// Check database connection
if (!isset($pdo) || $pdo === null) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database connection failed."]);
    exit();
}

// Get JSON input
$data = json_decode(file_get_contents("php://input"));

// Check if data is valid
if (!isset($data->email) || !isset($data->password)) {
    http_response_code(400); // Bad Request
    echo json_encode(["status" => "error", "message" => "Incomplete data. Provide email and password."]);
    exit();
}

$email = trim($data->email);
$password = $data->password;

try {
    // Prepare query to fetch user
    $stmt = $pdo->prepare("SELECT user_id, first_name, last_name, email, password, phone, is_admin, created_at FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    
    if ($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Remove sensitive password hash from response
            unset($user['password']);
            
            http_response_code(200);
            echo json_encode([
                "status" => "success",
                "message" => "Login successful.",
                "user" => $user
            ]);
        } else {
            http_response_code(401); // Unauthorized
            echo json_encode(["status" => "error", "message" => "Invalid email or password."]);
        }
    } else {
        http_response_code(401); // Unauthorized
        echo json_encode(["status" => "error", "message" => "Invalid email or password."]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database error: " . $e->getMessage()]);
}
?>
