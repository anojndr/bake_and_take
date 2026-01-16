<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/config.php';

/**
 * Log email to database
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email body
 * @param bool $isHtml Whether the body is HTML
 * @param string $status Status (pending, sent, failed)
 * @param string|null $errorMessage Error message if failed
 * @param int|null $orderId Related order ID
 * @param int|null $userId Related user ID
 * @return int|null Email log ID
 */
function logEmail($to, $subject, $body, $isHtml, $status, $errorMessage = null, $orderId = null, $userId = null) {
    global $conn;
    
    if (!$conn) {
        return null;
    }
    
    // Check if email_log table exists
    $tableCheck = mysqli_query($conn, "SHOW TABLES LIKE 'email_log'");
    if (!$tableCheck || mysqli_num_rows($tableCheck) === 0) {
        return null;
    }
    
    $stmt = mysqli_prepare($conn, "
        INSERT INTO email_log (recipient_email, subject, body, is_html, status, error_message, order_id, user_id)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        error_log("Email log error: " . mysqli_error($conn));
        return null;
    }
    
    $isHtmlInt = $isHtml ? 1 : 0;
    mysqli_stmt_bind_param($stmt, "sssissii", $to, $subject, $body, $isHtmlInt, $status, $errorMessage, $orderId, $userId);
    
    if (!mysqli_stmt_execute($stmt)) {
        error_log("Email log error: " . mysqli_error($conn));
        mysqli_stmt_close($stmt);
        return null;
    }
    
    $insertId = mysqli_insert_id($conn);
    mysqli_stmt_close($stmt);
    return $insertId;
}

/**
 * Update email log status
 * 
 * @param int $emailLogId Email log ID
 * @param string $status New status
 * @param string|null $errorMessage Error message if failed
 * @return bool Success
 */
function updateEmailLog($emailLogId, $status, $errorMessage = null) {
    global $conn;
    
    if (!$conn || !$emailLogId) {
        return false;
    }
    
    $stmt = mysqli_prepare($conn, "
        UPDATE email_log SET status = ?, error_message = ?, updated_at = NOW()
        WHERE email_id = ?
    ");
    if (!$stmt) {
        error_log("Email log update error: " . mysqli_error($conn));
        return false;
    }
    mysqli_stmt_bind_param($stmt, "ssi", $status, $errorMessage, $emailLogId);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);
    return $success;
}

/**
 * Send email using PHPMailer with Gmail
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email body
 * @param bool $isHtml Whether the body is HTML
 * @param int|null $orderId Related order ID for logging
 * @param int|null $userId Related user ID for logging
 * @return array ['success' => bool, 'message' => string]
 */
function sendMail($to, $subject, $body, $isHtml = true, $orderId = null, $userId = null) {
    $mail = new PHPMailer(true);
    
    // Log the email attempt
    $emailLogId = logEmail($to, $subject, $body, $isHtml, 'pending', null, $orderId, $userId);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;
        
        // Performance optimizations - reduce timeouts significantly
        $mail->Timeout       = 10;  // Connection timeout (seconds) - was 300 default
        $mail->SMTPKeepAlive = true; // Keep connection open for faster subsequent sends
        $mail->SMTPDebug     = SMTP::DEBUG_OFF;  // Disable debug output
        $mail->SMTPOptions   = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];

        // Recipients
        $mail->setFrom(SMTP_USER, SMTP_FROM_NAME);
        $mail->addAddress($to);
        $mail->addReplyTo(SMTP_USER, SMTP_FROM_NAME);

        // Content
        $mail->isHTML($isHtml);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        
        if ($isHtml) {
            $mail->AltBody = strip_tags($body);
        }

        $mail->send();
        
        // Update log on success
        if ($emailLogId) {
            updateEmailLog($emailLogId, 'sent');
        }
        
        return ['success' => true, 'message' => 'Message has been sent'];
    } catch (Exception $e) {
        $errorMessage = "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        
        // Update log on failure
        if ($emailLogId) {
            updateEmailLog($emailLogId, 'failed', $errorMessage);
        }
        
        return ['success' => false, 'message' => $errorMessage];
    }
}
?>
