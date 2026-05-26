<?php
class Mailer {
    public static function send(string $to, string $subject, string $htmlBody): bool {
        $headers = [
            'MIME-Version: 1.0',
            'Content-type: text/html; charset=UTF-8',
            'From: ' . APP_NAME . ' <noreply@deansygrove.co.uk>',
            'Reply-To: noreply@deansygrove.co.uk',
            'X-Mailer: PHP/' . phpversion(),
        ];

        return mail($to, $subject, $htmlBody, implode("\r\n", $headers));
    }

    public static function sendInvite(string $email, string $name, string $schoolName, string $token): bool {
        $url = APP_URL . '/invite/' . $token;
        $subject = "You've been invited to {$schoolName} on " . APP_NAME;

        $body = "
        <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; background: #111; color: #fafafa; padding: 30px; border-radius: 12px;'>
            <h2 style='color: #818cf8;'>Staff Invitation</h2>
            <p>Hi {$name},</p>
            <p>You've been invited to join <strong>{$schoolName}</strong> on the Student Risk Prediction System.</p>
            <p style='margin: 25px 0;'>
                <a href='{$url}' style='background: #6366f1; color: white; padding: 12px 24px; border-radius: 8px; text-decoration: none; font-weight: bold;'>Accept Invitation</a>
            </p>
            <p style='color: #666; font-size: 12px;'>This link expires in 7 days. If you didn't expect this, ignore this email.</p>
        </div>";

        return self::send($email, $subject, $body);
    }
}
