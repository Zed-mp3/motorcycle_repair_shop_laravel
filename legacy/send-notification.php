<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/phpmailer/src/PHPMailer.php';
require_once __DIR__ . '/phpmailer/src/SMTP.php';
require_once __DIR__ . '/phpmailer/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

function sendEmailNotification($to, $subject, $message) {
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com'; // Your SMTP server
        $mail->SMTPAuth   = true;
        $mail->Username   = 'zedmacasiray@gmail.com'; // Your email
        $mail->Password   = 'btwt pqzd tpyc lofe'; // Your password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Recipients
        $mail->setFrom('no-reply@motorepair.com', 'MotoRepair Pro');
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $message;
        $mail->AltBody = strip_tags($message);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email could not be sent. Error: {$mail->ErrorInfo}");
        return false;
    }
}

// Function to send booking confirmation
function sendBookingConfirmation($email, $name, $booking_id, $queue_no) {
    $subject = "Booking Confirmation - MotoRepair Pro";
    $message = "
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; }
                .container { padding: 20px; background: #f4f4f4; }
                .content { background: white; padding: 20px; border-radius: 5px; }
                .header { background: #e67e22; color: white; padding: 10px; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='content'>
                    <div class='header'>
                        <h2>Booking Confirmation</h2>
                    </div>
                    <p>Dear $name,</p>
                    <p>Thank you for choosing MotoRepair Pro. Your booking has been confirmed.</p>
                    <p><strong>Booking ID:</strong> $booking_id<br>
                    <strong>Queue Number:</strong> #$queue_no</p>
                    <p>You can track your repair status using your booking ID on our website.</p>
                    <p>Best regards,<br>MotoRepair Pro Team</p>
                </div>
            </div>
        </body>
        </html>
    ";
    
    return sendEmailNotification($email, $subject, $message);
}
function sendPasswordReset($email, $name, $reset_link) {
    $subject = "Password Reset - MotoRepair Pro";
    $message = "
        <html>
        <body style='font-family: Arial, sans-serif;'>
            <div style='padding:20px; background:#f4f4f4;'>
                <div style='background:white; padding:20px; border-radius:5px;'>
                    <h2 style='color:#e67e22;'>Password Reset Request</h2>
                    <p>Dear $name,</p>
                    <p>We received a request to reset your password. Click the button below to set a new one:</p>
                    <p style='text-align:center;'>
                        <a href='$reset_link' style='background:#e67e22; color:white; padding:12px 24px; text-decoration:none; border-radius:6px; display:inline-block;'>
                            Reset Password
                        </a>
                    </p>
                    <p>Or copy this link: <br><a href='$reset_link'>$reset_link</a></p>
                    <p>This link expires in 1 hour.</p>
                    <p>If you didn't request this, ignore this email.</p>
                    <p>Best regards,<br>MotoRepair Pro Team</p>
                </div>
            </div>
        </body>
        </html>
    ";
    return sendEmailNotification($email, $subject, $message);
}
?>
