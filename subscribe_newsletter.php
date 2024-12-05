<?php
session_start();
require_once 'smtp_config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    if ($email) {
        // Save the email to a file (or database)
        $newsletterFile = 'newsletter_subscribers.txt';
        file_put_contents($newsletterFile, $email . PHP_EOL, FILE_APPEND);

        // Load SMTP configuration
        $smtpConfig = include 'smtp_config.php';

        // Send confirmation email
        $subject = 'تایید اشتراک';
        $message = 'از اینکه به خبرنامه ما پیوستید متشکریم!';
        $headers = 'From: no-reply@example.com' . "\r\n" .
                   'Reply-To: no-reply@example.com' . "\r\n" .
                   'X-Mailer: PHP/' . phpversion();

        if ($smtpConfig['smtp_enabled']) {
            // Use SMTP to send the email
            require_once 'PHPMailer/PHPMailer.php';
            require_once 'PHPMailer/SMTP.php';
            require_once 'PHPMailer/Exception.php';

            $mail = new PHPMailer\PHPMailer\PHPMailer();
            $mail->isSMTP();
            $mail->Host = $smtpConfig['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtpConfig['smtp_username'];
            $mail->Password = $smtpConfig['smtp_password'];
            $mail->SMTPSecure = $smtpConfig['smtp_encryption'];
            $mail->Port = $smtpConfig['smtp_port'];

            $mail->setFrom('no-reply@example.com', 'خبرنامه');
            $mail->addAddress($email);
            $mail->Subject = $subject;
            $mail->Body = $message;

            if ($mail->send()) {
                $_SESSION['success_message'] = 'اشتراک شما با موفقیت انجام شد! ایمیل تایید برای شما ارسال شد.';
            } else {
                $_SESSION['error_message'] = 'ارسال ایمیل تایید با مشکل مواجه شد. لطفا دوباره تلاش کنید.';
            }
        } else {
            // Use default mail function
            if (mail($email, $subject, $message, $headers)) {
                $_SESSION['success_message'] = 'اشتراک شما با موفقیت انجام شد! ایمیل تایید برای شما ارسال شد.';
            } else {
                $_SESSION['error_message'] = 'ارسال ایمیل تایید با مشکل مواجه شد. لطفا دوباره تلاش کنید.';
            }
        }
    } else {
        $_SESSION['error_message'] = 'آدرس ایمیل نامعتبر است.';
    }
    header('Location: index.php');
    exit;
}

// If someone tries to access this file directly, redirect them to the homepage
header('Location: index.php');
exit;
?>
