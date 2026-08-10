<?php

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

class MailNotifier
{
    /**
     * @param string $smtpUser     Cuenta Gmail que autentica y envía
     * @param string $smtpPassword Contraseña de aplicación (no la normal)
     * @param array  $recipients   Lista de correos destino (To)
     * @param array  $ccRecipients Lista de correos en copia (Cc), opcional
     */
    public function __construct(
        private string $smtpUser,
        private string $smtpPassword,
        private array $recipients,
        private array $ccRecipients = []
    ) {
    }

    public function send(string $subject, string $htmlBody): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'smtp.gmail.com';
            $mail->SMTPAuth   = true;
            $mail->Username   = $this->smtpUser;
            $mail->Password   = $this->smtpPassword;
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 587;
            $mail->CharSet    = 'UTF-8';

            $mail->setFrom($this->smtpUser, 'Bot Clima Maracaibo');

            foreach ($this->recipients as $to) {
                $mail->addAddress(trim($to));
            }

            foreach ($this->ccRecipients as $cc) {
                $mail->addCC(trim($cc));
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body    = $htmlBody;

            $mail->send();
            return true;

        } catch (PHPMailerException $e) {
            error_log("Gmail notification failed: " . $mail->ErrorInfo);
            return false;
        }
    }
}