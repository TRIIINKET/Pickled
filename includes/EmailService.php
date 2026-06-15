<?php
declare(strict_types=1);

use PHPMailer\PHPMailer\PHPMailer;

require_once __DIR__ . '/../vendor/autoload.php';

final class EmailService
{
    private array $config;
    private ?string $lastError = null;

    public function __construct(?array $config = null)
    {
        $this->config = $config ?? $this->loadConfig();
    }

    public static function sendEmail(string $to, string $subject, string $htmlBody, ?string $replyTo = null): bool
    {
        return (new self())->send($to, $subject, $htmlBody, null, $replyTo);
    }

    public function send(string $to, string $subject, string $htmlBody, ?string $textBody = null, ?string $replyTo = null): bool
    {
        $this->lastError = null;
        $to = trim($to);

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->lastError = 'Invalid recipient email.';
            error_log('EmailService::send - invalid recipient email');
            return false;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            $mail->Host = (string) ($this->config['host'] ?? 'smtp.gmail.com');
            $mail->SMTPAuth = true;
            $mail->Username = (string) ($this->config['username'] ?? '');
            $mail->Password = (string) ($this->config['password'] ?? '');
            $mail->Port = (int) ($this->config['port'] ?? 587);
            $mail->SMTPSecure = (string) ($this->config['encryption'] ?? PHPMailer::ENCRYPTION_STARTTLS);
            $mail->CharSet = PHPMailer::CHARSET_UTF8;

            if ($mail->Username === '' || $mail->Password === '' || $mail->Password === 'REPLACE_WITH_APP_PASSWORD') {
                throw new RuntimeException('SMTP credentials are not configured.');
            }

            $fromEmail = (string) ($this->config['from_email'] ?? $mail->Username);
            $fromName = (string) ($this->config['from_name'] ?? 'PICKLED');
            $mail->setFrom($fromEmail, $fromName);
            $mail->addAddress($to);

            if ($replyTo && filter_var($replyTo, FILTER_VALIDATE_EMAIL)) {
                $mail->addReplyTo($replyTo);
            }

            $mail->isHTML(true);
            $mail->Subject = $subject;
            $mail->Body = $htmlBody;
            $mail->AltBody = $textBody ?? $this->htmlToText($htmlBody);

            return $mail->send();
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            error_log('EmailService::send - ' . $e->getMessage());
            return false;
        }
    }

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    public function sendOtp(string $to, string $name, string $otp): bool
    {
        $safeOtp = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');
        $html = $this->emailFrame(
            'Email Verification',
            'Verify your PICKLED account',
            '<p>Hi ' . htmlspecialchars($this->displayName(['name' => $name]), ENT_QUOTES, 'UTF-8') . ',</p>
             <p>Use this one-time password to verify your PICKLED account. It expires in 10 minutes.</p>
             <div style="font-size:34px;letter-spacing:8px;font-weight:900;color:#264414;background:#F6EFE1;border:1px solid rgba(38,68,20,.14);border-radius:12px;padding:18px 20px;text-align:center;">' . $safeOtp . '</div>
             <p>If you did not create this account, you may ignore this email.</p>'
        );

        return $this->send($to, 'Verify Your Email - PICKLED', $html);
    }

    public function sendPasswordResetOtp(string $to, string $name, string $otp): bool
    {
        $safeOtp = htmlspecialchars($otp, ENT_QUOTES, 'UTF-8');
        $html = $this->emailFrame(
            'Password Reset',
            'Reset Your PICKLED Password',
            '<p>Hi ' . htmlspecialchars($this->displayName(['name' => $name]), ENT_QUOTES, 'UTF-8') . ',</p>
             <p>Use this one-time password to reset your PICKLED account password.</p>
             <div style="font-size:34px;letter-spacing:8px;font-weight:900;color:#264414;background:#F6EFE1;border:1px solid rgba(38,68,20,.14);border-radius:12px;padding:18px 20px;text-align:center;">' . $safeOtp . '</div>
             <p><strong>This code will expire in 10 minutes.</strong></p>
             <p>If you did not request this, you may ignore this email.</p>'
        );

        return $this->send($to, 'Reset Your PICKLED Password', $html);
    }

    public function sendLoginNotification(array $user): bool
    {
        $timezone = $this->timezone();
        $date = (new DateTimeImmutable('now', $timezone))->format('F j, Y g:i A');
        $name = $this->displayName($user);
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeDate = htmlspecialchars($date, ENT_QUOTES, 'UTF-8');
        $html = $this->emailFrame(
            'Login Alert',
            'Login Alert - PICKLED',
            '<p>Hi ' . $safeName . ',</p>
             <p>Your PICKLED account was just signed in.</p>
             <table role="presentation" style="width:100%;border-collapse:collapse;margin:18px 0;">
               <tr><td style="padding:10px 0;color:#667085;font-weight:800;">User name</td><td style="padding:10px 0;font-weight:900;">' . $safeName . '</td></tr>
               <tr><td style="padding:10px 0;color:#667085;font-weight:800;">Login date and time</td><td style="padding:10px 0;font-weight:900;">' . $safeDate . '</td></tr>
             </table>
             <p>If this was not you, reset your password immediately.</p>'
        );

        return $this->send((string) ($user['email'] ?? ''), 'Login Alert - PICKLED', $html);
    }

    public function sendBookingConfirmation(array $user, array $booking): bool
    {
        $name = $this->displayName($user);
        $reference = (string) ($booking['reference'] ?? 'N/A');
        $bookingStatus = (string) ($booking['status'] ?? 'pending');
        $paymentStatus = (string) ($booking['payment_status'] ?? 'pending');
        $total = number_format((float) ($booking['total'] ?? 0), 2);
        $itemsHtml = $this->renderBookingItemsHtml($booking['items'] ?? []);
        $html = $this->emailFrame(
            'Booking Confirmation',
            'Booking Confirmation - PICKLED',
            '<p>Hi ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>
             <p>Your booking request has been received.</p>
             <div style="background:#F6EFE1;border:1px solid rgba(38,68,20,.12);border-radius:12px;padding:16px;margin:18px 0;">
               <p style="margin:0 0 8px;"><strong>Customer name:</strong> ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</p>
               <p style="margin:0 0 8px;"><strong>Booking reference:</strong> ' . htmlspecialchars($reference, ENT_QUOTES, 'UTF-8') . '</p>
               <p style="margin:0 0 8px;"><strong>Booking status:</strong> ' . htmlspecialchars(ucfirst($bookingStatus), ENT_QUOTES, 'UTF-8') . '</p>
               <p style="margin:0 0 8px;"><strong>Payment status:</strong> ' . htmlspecialchars(ucfirst($paymentStatus), ENT_QUOTES, 'UTF-8') . '</p>
               <p style="margin:0;"><strong>Total amount:</strong> PHP ' . htmlspecialchars($total, ENT_QUOTES, 'UTF-8') . '</p>
             </div>
             <h3 style="color:#264414;margin:22px 0 10px;">Session details</h3>' . $itemsHtml
        );

        return $this->send((string) ($user['email'] ?? ''), 'Booking Confirmation - PICKLED', $html);
    }

    public function sendBookingIssue(array $user, string $message = ''): bool
    {
        $name = $this->displayName($user);
        $safeMessage = htmlspecialchars($message !== '' ? $message : 'Your booking or payment could not be completed.', ENT_QUOTES, 'UTF-8');
        $html = $this->emailFrame(
            'Booking Issue',
            'Booking Issue - PICKLED',
            '<p>Hi ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>
             <p>Your booking or payment could not be completed.</p>
             <div style="margin:18px 0;padding:16px;background:#fff1f1;border-left:4px solid #b42318;border-radius:10px;">' . $safeMessage . '</div>
             <p>Please try again or contact PICKLED if you need help completing your booking.</p>'
        );

        return $this->send((string) ($user['email'] ?? ''), 'Booking Issue - PICKLED', $html);
    }

    public function sendPaymentApproved(array $user, array $booking): bool
    {
        $html = $this->paymentEmailHtml('Payment Approved', $user, $booking, 'Your payment has been approved. Your booking is now confirmed.');
        return $this->send((string) ($user['email'] ?? ''), 'Payment Approved - PICKLED', $html);
    }

    public function sendPaymentRejected(array $user, array $booking, string $reason): bool
    {
        $safeReason = htmlspecialchars($reason !== '' ? $reason : 'No reason provided.', ENT_QUOTES, 'UTF-8');
        $html = $this->paymentEmailHtml(
            'Payment Rejected',
            $user,
            $booking,
            'Your payment was rejected. Please review the reason below and contact PICKLED if you need help.',
            '<div style="margin-top:16px;padding:14px 16px;background:#fff1f1;border-left:4px solid #b42318;border-radius:10px;"><strong>Reason:</strong><br>' . $safeReason . '</div>'
        );
        return $this->send((string) ($user['email'] ?? ''), 'Payment Rejected - PICKLED', $html);
    }

    public function sendContactMessage(array $contact): bool
    {
        $name = trim((string) ($contact['name'] ?? ''));
        $email = trim((string) ($contact['email'] ?? ''));
        $subject = trim((string) ($contact['subject'] ?? 'Contact Inquiry'));
        $message = trim((string) ($contact['message'] ?? ''));
        $adminEmail = (string) ($this->config['admin_email'] ?? $this->config['username'] ?? 'pickled.shopph@gmail.com');
        $submittedAt = (new DateTimeImmutable('now', $this->timezone()))->format('F j, Y g:i A');

        $adminHtml = $this->emailFrame(
            'Contact Inquiry',
            'New Contact Inquiry',
            '<p><strong>Full Name:</strong> ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . '</p>
             <p><strong>Email Address:</strong> ' . htmlspecialchars($email, ENT_QUOTES, 'UTF-8') . '</p>
             <p><strong>Subject:</strong> ' . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . '</p>
             <p><strong>Date/time submitted:</strong> ' . htmlspecialchars($submittedAt, ENT_QUOTES, 'UTF-8') . '</p>
             <div style="margin-top:18px;padding:16px;background:#F6EFE1;border-radius:12px;">' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</div>'
        );
        $adminSent = $this->send($adminEmail, '[Contact Inquiry] ' . $subject, $adminHtml, null, $email);

        $confirmationHtml = $this->emailFrame(
            'Message Received',
            'We Received Your Inquiry - PICKLED',
            '<p>Hi ' . htmlspecialchars($name !== '' ? $name : 'there', ENT_QUOTES, 'UTF-8') . ',</p>
             <p>Thank you for contacting PICKLED. We received your inquiry and will get back to you soon.</p>
             <p><strong>Subject:</strong> ' . htmlspecialchars($subject, ENT_QUOTES, 'UTF-8') . '</p>
             <div style="margin-top:18px;padding:16px;background:#F6EFE1;border-radius:12px;">' . nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8')) . '</div>'
        );
        $senderSent = filter_var($email, FILTER_VALIDATE_EMAIL)
            ? $this->send($email, 'We Received Your Inquiry - PICKLED', $confirmationHtml)
            : false;

        return $adminSent && $senderSent;
    }

    private function paymentEmailHtml(string $heading, array $user, array $booking, string $message, string $extraHtml = ''): string
    {
        $name = $this->displayName($user);
        $reference = (string) ($booking['reference'] ?? 'N/A');
        $total = number_format((float) ($booking['total'] ?? 0), 2);
        $paymentStatus = (string) ($booking['payment_status'] ?? '');

        return $this->emailFrame(
            $heading,
            $heading . ' - PICKLED',
            '<p>Hi ' . htmlspecialchars($name, ENT_QUOTES, 'UTF-8') . ',</p>
             <p>' . htmlspecialchars($message, ENT_QUOTES, 'UTF-8') . '</p>
             <div style="background:#F6EFE1;border:1px solid rgba(38,68,20,.12);border-radius:12px;padding:16px;margin:18px 0;">
               <p style="margin:0 0 8px;"><strong>Booking reference:</strong> ' . htmlspecialchars($reference, ENT_QUOTES, 'UTF-8') . '</p>
               <p style="margin:0 0 8px;"><strong>Payment status:</strong> ' . htmlspecialchars(ucfirst($paymentStatus ?: $heading), ENT_QUOTES, 'UTF-8') . '</p>
               <p style="margin:0;"><strong>Total amount:</strong> PHP ' . htmlspecialchars($total, ENT_QUOTES, 'UTF-8') . '</p>
             </div>' . $extraHtml
        );
    }

    private function renderBookingItemsHtml(array $items): string
    {
        if (!$items) {
            return '<p>No session details available.</p>';
        }

        $html = '';
        foreach ($items as $item) {
            $court = htmlspecialchars((string) ($item['court'] ?? 'Court'), ENT_QUOTES, 'UTF-8');
            $name = htmlspecialchars((string) ($item['name'] ?? 'Session'), ENT_QUOTES, 'UTF-8');
            $date = htmlspecialchars((string) ($item['booking_date'] ?? $item['date'] ?? ''), ENT_QUOTES, 'UTF-8');
            $time = htmlspecialchars((string) ($item['booking_time'] ?? $item['time'] ?? ''), ENT_QUOTES, 'UTF-8');
            $start = htmlspecialchars((string) ($item['start_time'] ?? ''), ENT_QUOTES, 'UTF-8');
            $end = htmlspecialchars((string) ($item['end_time'] ?? ''), ENT_QUOTES, 'UTF-8');
            $quantity = (int) ($item['quantity'] ?? 1);
            $price = number_format((float) ($item['unit_price'] ?? $item['price'] ?? 0), 2);
            $timeLine = $time !== '' ? $time : trim($start . ($end !== '' ? ' - ' . $end : ''));
            $html .= '<div style="border:1px solid rgba(38,68,20,.12);border-radius:12px;padding:14px 16px;margin-bottom:10px;">'
                . '<strong style="color:#264414;">' . $court . ' - ' . $name . '</strong>'
                . '<p style="margin:8px 0 0;"><strong>Date:</strong> ' . $date . '</p>'
                . '<p style="margin:6px 0 0;"><strong>Time:</strong> ' . htmlspecialchars($timeLine, ENT_QUOTES, 'UTF-8') . '</p>'
                . '<p style="margin:6px 0 0;color:#667085;"><strong>Quantity:</strong> ' . $quantity . ' - PHP ' . htmlspecialchars($price, ENT_QUOTES, 'UTF-8') . '</p>'
                . '</div>';
        }

        return $html;
    }

    private function emailFrame(string $eyebrow, string $heading, string $body): string
    {
        return '<!doctype html><html lang="en"><body style="margin:0;background:#F6EFE1;font-family:Arial,Helvetica,sans-serif;color:#172033;">'
            . '<div style="padding:32px 16px;background:#F6EFE1;">'
            . '<div style="max-width:680px;margin:0 auto;background:#fff;border:1px solid rgba(38,68,20,.12);border-radius:16px;overflow:hidden;">'
            . '<div style="background:#264414;color:#fff;padding:26px 30px;">'
            . '<div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;font-weight:800;color:#F5BAD9;">' . htmlspecialchars($eyebrow, ENT_QUOTES, 'UTF-8') . '</div>'
            . '<h1 style="margin:8px 0 0;font-size:28px;line-height:1.15;">' . htmlspecialchars($heading, ENT_QUOTES, 'UTF-8') . '</h1>'
            . '</div><div style="padding:30px;font-size:15px;line-height:1.7;">' . $body . '</div>'
            . '<div style="padding:16px 30px 24px;border-top:1px solid rgba(38,68,20,.08);font-size:12px;color:#667085;">PICKLED Court and Events Booking Management System</div>'
            . '</div></div></body></html>';
    }

    private function htmlToText(string $html): string
    {
        return trim(html_entity_decode(strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html)), ENT_QUOTES, 'UTF-8'));
    }

    private function displayName(array $user): string
    {
        $name = trim((string) ($user['name'] ?? ''));
        return $name !== '' ? $name : 'Member';
    }

    private function timezone(): DateTimeZone
    {
        $appConfig = is_file(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];
        return new DateTimeZone((string) ($appConfig['timezone'] ?? 'Asia/Manila'));
    }

    private function loadConfig(): array
    {
        $emailConfig = is_file(__DIR__ . '/../config/email.php') ? require __DIR__ . '/../config/email.php' : [];
        $legacyConfig = is_file(__DIR__ . '/config.php') ? require __DIR__ . '/config.php' : [];
        $legacyMail = $legacyConfig['mail'] ?? [];
        $localConfig = is_file(__DIR__ . '/mail.local.php') ? require __DIR__ . '/mail.local.php' : [];

        $mappedLocal = [];
        if ($localConfig) {
            $mappedLocal = [
                'host' => $localConfig['smtp_host'] ?? $localConfig['host'] ?? null,
                'port' => $localConfig['smtp_port'] ?? $localConfig['port'] ?? null,
                'username' => $localConfig['smtp_username'] ?? $localConfig['username'] ?? null,
                'password' => $localConfig['smtp_password'] ?? $localConfig['password'] ?? null,
                'encryption' => $localConfig['smtp_secure'] ?? $localConfig['encryption'] ?? null,
                'from_email' => $localConfig['from_email'] ?? null,
                'from_name' => $localConfig['from_name'] ?? null,
            ];
            $mappedLocal = array_filter($mappedLocal, static fn($value): bool => $value !== null && $value !== '');
        }

        return array_merge($legacyMail, $emailConfig, $mappedLocal);
    }
}
