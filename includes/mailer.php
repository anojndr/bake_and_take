<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use PHPMailer\PHPMailer\SMTP;

require_once __DIR__ . '/PHPMailer/src/Exception.php';
require_once __DIR__ . '/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/src/SMTP.php';
require_once __DIR__ . '/config.php';

/**
 * Send email using PHPMailer with Gmail
 * 
 * @param string $to Recipient email
 * @param string $subject Email subject
 * @param string $body Email body
 * @param bool $isHtml Whether the body is HTML
 * @return array ['success' => bool, 'message' => string]
 */
function sendMail($to, $subject, $body, $isHtml = true) {
    $mail = new PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = SMTP_HOST;
        $mail->SMTPAuth   = true;
        $mail->Username   = SMTP_USER;
        $mail->Password   = SMTP_PASS;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = SMTP_PORT;

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
        return ['success' => true, 'message' => 'Message has been sent'];
    } catch (Exception $e) {
        return ['success' => false, 'message' => "Message could not be sent. Mailer Error: {$mail->ErrorInfo}"];
    }
}
?>
