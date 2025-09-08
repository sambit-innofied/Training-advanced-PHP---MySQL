<?php
// src/helpers/Mailer.php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/../../vendor/PHPMailer/Exception.php';
require __DIR__ . '/../../vendor/PHPMailer/PHPMailer.php';
require __DIR__ . '/../../vendor/PHPMailer/SMTP.php';

// Use composer autoload (robust)
require_once __DIR__ . '/../../vendor/autoload.php';

class Mailer
{
    public static function sendOrderConfirmation(string $toEmail, string $toName)
    {
        if (empty($toEmail) || !filter_var($toEmail, FILTER_VALIDATE_EMAIL)) {
            error_log('Mailer: invalid or empty recipient email: ' . var_export($toEmail, true));
            return false;
        }

        $mail = new PHPMailer(true);

        try {
            //Server settings
            $mail->SMTPDebug = SMTP::DEBUG_SERVER;                      //Enable verbose debug output
            $mail->isSMTP();                                            //Send using SMTP
            $mail->Host = $_ENV['SMTP_HOST'];                       //Set the SMTP server to send through
            $mail->SMTPAuth = true;                                   //Enable SMTP authentication
            $mail->Username = $_ENV['SMTP_USERNAME'];                       //SMTP username
            $mail->Password = $_ENV['SMTP_PASSWORD'];                                //SMTP password
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;            //Enable implicit TLS encryption
            $mail->Port = 465;                                    //TCP port to connect to; use 587 if you have set `SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS`

            //Recipients
            $mail->setFrom('sambitsarkardbpc@gmail.com', 'Payment');
            $mail->addAddress($toEmail, $toName);     //Add a recipient

            //Content
            $mail->isHTML(true);                                  //Set email format to HTML
            $mail->Subject = 'Here is the subject';
            $mail->Body = 'This is the HTML message body <b>in bold!</b>';

            $mail->send();
            echo 'Message has been sent';
        } catch (Exception $e) {
            echo "Message could not be sent. Mailer Error: {$mail->ErrorInfo}";
        }
    }
}
