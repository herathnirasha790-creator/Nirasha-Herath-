<?php
// config/email_config.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

// ============================================================
// ✅ Path - libs/PHPMailer/src/
// ============================================================
require_once __DIR__ . '/../libs/PHPMailer/src/Exception.php';
require_once __DIR__ . '/../libs/PHPMailer/src/PHPMailer.php';
require_once __DIR__ . '/../libs/PHPMailer/src/SMTP.php';

/**
 * Send booking confirmation email to user
 */
function sendBookingConfirmationEmail($user_email, $user_name, $booking_type, $booking_id, $details) {
    try {
        $mail = new PHPMailer(true);
        
        // Server settings - Gmail SMTP
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'chamikadinisuru2@gmail.com';
        $mail->Password   = 'upjssoacusagxebk';  // App Password (Spaces නැතිව)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        
        // Sender & Recipient
        $mail->setFrom('chamikadinisuru2@gmail.com', 'Royal Estate');
        $mail->addAddress($user_email, $user_name);
        $mail->addReplyTo('info@royalestate.lk', 'Royal Estate Support');
        
        // Email Content
        $mail->isHTML(true);
        $mail->Subject = 'Booking Confirmation #' . $booking_id . ' | Royal Estate';
        $mail->Body = generateBookingEmailHTML($user_name, $booking_type, $booking_id, $details);
        
        $plainText = strip_tags(str_replace(['<br>', '</p>'], ["\n", "\n"], $mail->Body));
        $mail->AltBody = $plainText;
        
        $mail->send();
        return true;
        
    } catch (Exception $e) {
        error_log("Email sending failed: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate HTML Email Body
 */
function generateBookingEmailHTML($user_name, $booking_type, $booking_id, $details) {
    $type_label = ucfirst(str_replace('_', ' ', $booking_type));
    
    // Room vs Event details
    $extra_details = '';
    if ($booking_type === 'room') {
        $extra_details = "
            <tr><td><strong>Check In:</strong></td><td>{$details['check_in']}</td></tr>
            <tr><td><strong>Check Out:</strong></td><td>{$details['check_out']}</td></tr>
            <tr><td><strong>Nights:</strong></td><td>{$details['nights']}</td></tr>
        ";
    } else {
        $extra_details = "
            <tr><td><strong>Event Date:</strong></td><td>{$details['event_date']}</td></tr>
            <tr><td><strong>Hall:</strong></td><td>{$details['hall']}</td></tr>
            <tr><td><strong>Package:</strong></td><td>{$details['package']}</td></tr>
            <tr><td><strong>Guests:</strong></td><td>{$details['guests']}</td></tr>
        ";
    }
    
    $booking_link = ($booking_type === 'room') ? 'room' : ($booking_type === 'event_hall' ? 'event' : 'package');
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <style>
            body { font-family: Arial, sans-serif; background: #f5f0eb; padding: 20px; }
            .container { max-width: 600px; margin: 0 auto; background: #ffffff; border-radius: 12px; padding: 30px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); }
            .header { text-align: center; border-bottom: 2px solid #c5a263; padding-bottom: 15px; }
            .header h1 { color: #2c1810; font-family: 'Playfair Display', serif; }
            .header p { color: #c5a263; font-size: 14px; }
            .content { padding: 20px 0; }
            .content h2 { color: #2c1810; border-left: 4px solid #c5a263; padding-left: 12px; }
            .details-table { width: 100%; border-collapse: collapse; margin: 15px 0; }
            .details-table td { padding: 8px 12px; border-bottom: 1px solid #eee; }
            .details-table td:first-child { font-weight: 600; color: #2c1810; width: 40%; }
            .status-paid { background: #28a745; color: white; padding: 4px 12px; border-radius: 20px; display: inline-block; font-size: 12px; }
            .footer { text-align: center; padding-top: 20px; border-top: 1px solid #eee; color: #999; font-size: 12px; }
            .footer a { color: #c5a263; text-decoration: none; }
            .btn-dashboard { display: inline-block; background: linear-gradient(135deg, #c5a263, #8b691f); color: white; padding: 10px 25px; border-radius: 30px; text-decoration: none; margin-top: 15px; }
        </style>
    </head>
    <body>
        <div class=\"container\">
            <div class=\"header\">
                <h1>🏨 Royal Estate</h1>
                <p>Luxury & Elegance</p>
            </div>
            <div class=\"content\">
                <h2>Dear {$user_name},</h2>
                <p>Thank you for choosing Royal Estate! Your <strong>{$type_label}</strong> booking has been confirmed and payment has been successfully received.</p>
                
                <h3>📋 Booking Details</h3>
                <table class=\"details-table\">
                    <tr><td><strong>Booking ID:</strong></td><td>#{$booking_id}</td></tr>
                    <tr><td><strong>Type:</strong></td><td>{$type_label}</td></tr>
                    {$extra_details}
                    <tr><td><strong>Total Amount:</strong></td><td><strong>LKR {$details['total']}</strong></td></tr>
                    <tr><td><strong>Payment Status:</strong></td><td><span class=\"status-paid\">✅ Paid</span></td></tr>
                    <!-- ✅ Booking Status Row Removed -->
                </table>
                
                <p style=\"text-align:center;\">
                    <a href=\"http://localhost/royaledit/user/my-{$booking_link}-bookings.php\" class=\"btn-dashboard\">📊 View My Bookings</a>
                </p>
                
                <p style=\"color:#666; font-size:14px;\">If you have any questions, please contact us at <a href=\"mailto:info@royalestate.lk\">info@royalestate.lk</a> or call <strong>+94 37 222 1234</strong>.</p>
            </div>
            <div class=\"footer\">
                <p>&copy; 2024 Royal Estate. All rights reserved.</p>
                <p>Kurunegala, Sri Lanka</p>
            </div>
        </div>
    </body>
    </html>
    ";
}
?>