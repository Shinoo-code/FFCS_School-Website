<?php
// CRITICAL: Ensure this is the very first line of the file with no spaces above it.

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Assuming composer installed the files and the autoloader is correctly linked
require_once '../../vendor/autoload.php';

function sendOtpEmail($recipientEmail, $otpCode) {
    
    // --- FINAL GMAIL CREDENTIALS ---
    $smtp_username = 'mcrei.dev.gma@gmail.com'; 
    // Ensure the password has no quotes or strange characters
    $smtp_password = 'cbtmweryobqkdnps'; 
    $sender_name   = 'MCREI Admin Portal';
    // -------------------------------------------
    
    // ... (rest of the PHPMailer function body remains the same) ...

    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();                                            
        $mail->Host       = 'smtp.gmail.com';                      
        $mail->SMTPAuth   = true;                                  
        $mail->Username   = $smtp_username;                        
        $mail->Password   = $smtp_password;                        
        // Make SMTP connections fail faster in dev if the server is unreachable
        // and avoid verbose debug output in production.
        $mail->SMTPDebug = 0; // 0 = off (for production), 2 = client+server
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;           
        $mail->Port       = 465;                                   
        // Reduce default timeouts so the login request doesn't hang for minutes
        // when the SMTP server is unreachable. Adjust as needed.
        $mail->Timeout = 10; // seconds for network operations
        $mail->SMTPKeepAlive = false;
        $mail->SMTPAutoTLS = false;
        // Allow local dev environments with self-signed certs to proceed,
        // but in production you should remove/adjust these options.
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];

        //Recipients
        $mail->setFrom($smtp_username, $sender_name);
        $mail->addAddress($recipientEmail);                          

        //Content
        $mail->isHTML(false);                                       
        $mail->Subject = 'MCREI Two-Factor Login Code';
        $mail->Body    = "Your one-time verification code for MCREI Admin login is: {$otpCode}\n\nThis code is valid for 5 minutes.\n\nIf you did not attempt to log in, please secure your account.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("PHPMailer Error: Failed to send email to {$recipientEmail}. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

// NOTE: No closing ?> tag.