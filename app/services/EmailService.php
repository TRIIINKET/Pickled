<?php
declare(strict_types=1);

final class EmailService
{
    private string $fromEmail;
    private string $fromName;
    private array $smtp;
  private ?string $lastError = null;

    public function __construct()
    {
        $config = require __DIR__ . '/../../includes/config.php';
        $mail = $config['mail'] ?? [];
    $mailConfigPaths = [
      __DIR__ . '/../../includes/mail.local.php',
      __DIR__ . '/../../backend/config/mail.local.php',
    ];

    foreach ($mailConfigPaths as $mailConfigPath) {
      if (is_file($mailConfigPath)) {
        $mail = array_merge($mail, require $mailConfigPath);
      }
        }

        $this->fromEmail = (string) ($mail['from_email'] ?? 'no-reply@pickled.local');
        $this->fromName = (string) ($mail['from_name'] ?? ($config['app_name'] ?? 'PICKLED'));
        $this->smtp = $mail;
    }

    public function sendLoginNotification(array $user): bool
    {
        $name = $this->displayName($user);
        $subject = 'You are now logged in to PICKLED';
        $text = <<<TEXT
Hi {$name},

You have successfully logged in to your PICKLED account.

If this was not you, please reset your password or contact PICKLED support immediately.

Thank you,
PICKLED
TEXT;
        $html = $this->renderLoginHtml($name);

        return $this->send((string) ($user['email'] ?? ''), $subject, $text, $html);
    }

    public function sendBookingConfirmation(array $user, array $booking): bool
    {
        $name = $this->displayName($user);
        $items = $this->formatBookingItems($booking['items'] ?? []);
        $total = number_format((float) ($booking['total'] ?? 0), 2);
        $reference = (string) ($booking['reference'] ?? 'N/A');
        $status = (string) ($booking['status'] ?? 'Pending');
        $paymentMethod = (string) ($booking['payment_method'] ?? 'N/A');
        $paymentStatus = (string) ($booking['payment_status'] ?? 'N/A');
        $policy = (string) (($booking['cancellation_policy']['label'] ?? $booking['cancellation_label'] ?? 'Standard cancellation policy'));

        $subject = 'PICKLED booking confirmation ' . $reference;
        $text = <<<TEXT
Hi {$name},

Your booking has been submitted.

Reference: {$reference}
Status: {$status}
Payment: {$paymentMethod} - {$paymentStatus}
Total: PHP {$total}
Cancellation: {$policy}

Booking details:
{$items}

Thank you,
PICKLED
TEXT;
        $html = $this->renderBookingHtml($name, $reference, $status, $paymentMethod, $paymentStatus, $total, $policy, $booking['items'] ?? []);

        return $this->send((string) ($user['email'] ?? ''), $subject, $text, $html);
    }

  public function sendContactMessage(array $contact): bool
  {
    $name = trim((string) ($contact['name'] ?? ''));
    $email = trim((string) ($contact['email'] ?? ''));
    $phone = trim((string) ($contact['phone'] ?? ''));
    $message = trim((string) ($contact['message'] ?? ''));

    if ($name === '') {
      $name = 'Guest';
    }

    $subject = 'New contact inquiry from ' . $name;
    $text = <<<TEXT
New contact inquiry received.

Name: {$name}
Email: {$email}
Phone: {$phone}

Message:
{$message}
TEXT;
    $html = $this->renderContactHtml($name, $email, $phone, $message);

    return $this->send('pickled.shopph@gmail.com', $subject, $text, $html, $email ?: null);
  }

      public function getLastError(): ?string
      {
        return $this->lastError;
      }

    private function send(string $to, string $subject, string $textBody, string $htmlBody, ?string $replyTo = null): bool
    {
        $this->lastError = null;
        $to = trim($to);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            $this->lastError = 'invalid recipient email';
            error_log('EmailService::send - invalid recipient email');
            return false;
        }

        if (!empty($this->smtp['smtp_host']) && !empty($this->smtp['smtp_username']) && !empty($this->smtp['smtp_password'])) {
            if ($this->sendViaSmtp($to, $subject, $textBody, $htmlBody)) {
                return true;
            }
        return false;
        }

      $this->lastError = 'SMTP not configured';
      error_log('EmailService::send - SMTP not configured for ' . $to);
      return false;
    }

    private function sendViaSmtp(string $to, string $subject, string $textBody, string $htmlBody): bool
    {
        $host = (string) $this->smtp['smtp_host'];
        $port = (int) ($this->smtp['smtp_port'] ?? 587);
        $username = (string) $this->smtp['smtp_username'];
        $password = (string) $this->smtp['smtp_password'];
        $secure = strtolower((string) ($this->smtp['smtp_secure'] ?? 'tls'));
        $remote = ($secure === 'ssl' ? 'ssl://' : '') . $host . ':' . $port;

        $socket = @stream_socket_client($remote, $errno, $errstr, 20, STREAM_CLIENT_CONNECT);
        if (!$socket) {
          $this->lastError = "connection failed: {$errno} {$errstr}";
            error_log("EmailService::sendViaSmtp - connection failed: {$errno} {$errstr}");
            return false;
        }

        stream_set_timeout($socket, 20);

        try {
            $this->smtpExpect($socket, [220]);
            $this->smtpCommand($socket, 'EHLO localhost', [250]);

            if ($secure === 'tls') {
                $this->smtpCommand($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                    throw new RuntimeException('SMTP STARTTLS failed.');
                }
                $this->smtpCommand($socket, 'EHLO localhost', [250]);
            }

            $this->smtpCommand($socket, 'AUTH LOGIN', [334]);
            $this->smtpCommand($socket, base64_encode($username), [334]);
            $this->smtpCommand($socket, base64_encode($password), [235]);
            $this->smtpCommand($socket, 'MAIL FROM:<' . $this->fromEmail . '>', [250]);
            $this->smtpCommand($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
            $this->smtpCommand($socket, 'DATA', [354]);

            $message = $this->smtpMessage($to, $subject, $textBody, $htmlBody);
            fwrite($socket, $message . "\r\n.\r\n");
            $this->smtpExpect($socket, [250]);
            $this->smtpCommand($socket, 'QUIT', [221]);
            fclose($socket);
            return true;
        } catch (Throwable $e) {
          $this->lastError = $e->getMessage();
            error_log('EmailService::sendViaSmtp - ' . $e->getMessage());
            fclose($socket);
            return false;
        }
    }

    private function smtpMessage(string $to, string $subject, string $textBody, string $htmlBody): string
    {
        $boundary = '=_Pickled_' . bin2hex(random_bytes(12));
        $headers = [
            'From: ' . $this->formatAddress($this->fromEmail, $this->fromName),
            'To: ' . $to,
            'Subject: ' . $subject,
            'Date: ' . date(DATE_RFC2822),
            'MIME-Version: 1.0',
            'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        ];

        $text = str_replace(["\r\n", "\r"], "\n", wordwrap($textBody, 78));
        $html = $this->normalizeHtml($htmlBody);

        $parts = [
            '--' . $boundary,
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 7bit',
            '',
            $text,
            '--' . $boundary,
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: 7bit',
            '',
            $html,
            '--' . $boundary . '--',
            '',
        ];

        return implode("\r\n", $headers) . "\r\n\r\n" . implode("\r\n", $parts);
    }

    private function smtpCommand($socket, string $command, array $expectedCodes): string
    {
        fwrite($socket, $command . "\r\n");
        return $this->smtpExpect($socket, $expectedCodes);
    }

    private function smtpExpect($socket, array $expectedCodes): string
    {
        $response = '';
        do {
            $line = fgets($socket, 515);
            if ($line === false) {
                throw new RuntimeException('SMTP server closed the connection.');
            }
            $response .= $line;
        } while (isset($line[3]) && $line[3] === '-');

        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expectedCodes, true)) {
            throw new RuntimeException('Unexpected SMTP response: ' . trim($response));
        }

        return $response;
    }

    private function formatAddress(string $email, string $name): string
    {
        return '"' . addcslashes($name, '"\\') . '" <' . $email . '>';
    }

    private function renderLoginHtml(string $name): string
    {
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');

        return <<<HTML
<!doctype html>
<html lang="en">
<body style="margin:0;background:#F6EFE1;padding:0;font-family:Arial,Helvetica,sans-serif;color:#264414;">
  <div style="background:#F6EFE1;padding:32px 16px;">
    <div style="max-width:640px;margin:0 auto;background:#fff;border:1px solid rgba(38,68,20,.12);border-radius:18px;overflow:hidden;box-shadow:0 16px 48px rgba(0,0,0,.08);">
      <div style="background:#264414;color:#fff;padding:28px 32px;">
        <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;font-weight:700;color:#DDE255;">PICKLED</div>
        <div style="font-size:30px;line-height:1.1;font-weight:900;margin-top:8px;">Logged in successfully</div>
      </div>
      <div style="padding:32px;">
        <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hi {$safeName},</p>
        <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">You have successfully logged in to your PICKLED account.</p>
        <div style="margin:24px 0;padding:16px 18px;border-left:4px solid #F85696;background:#fff9fb;border-radius:12px;">
          <div style="font-size:12px;letter-spacing:1.8px;text-transform:uppercase;font-weight:800;color:#F85696;margin-bottom:6px;">Security notice</div>
          <div style="font-size:15px;line-height:1.6;color:#264414;">If this was not you, reset your password immediately or contact PICKLED support.</div>
        </div>
        <p style="margin:0;font-size:15px;line-height:1.7;color:#7a7a6e;">Thank you,<br>PICKLED</p>
      </div>
      <div style="padding:16px 32px 28px;color:#7a7a6e;font-size:12px;line-height:1.6;border-top:1px solid rgba(38,68,20,.08);">
        This email is part of your account activity notifications.
      </div>
    </div>
  </div>
</body>
</html>
HTML;
    }

    private function renderBookingHtml(string $name, string $reference, string $status, string $paymentMethod, string $paymentStatus, string $total, string $policy, array $items): string
    {
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeReference = htmlspecialchars($reference, ENT_QUOTES, 'UTF-8');
        $safeStatus = htmlspecialchars($status, ENT_QUOTES, 'UTF-8');
        $safePaymentMethod = htmlspecialchars($paymentMethod, ENT_QUOTES, 'UTF-8');
        $safePaymentStatus = htmlspecialchars($paymentStatus, ENT_QUOTES, 'UTF-8');
        $safeTotal = htmlspecialchars($total, ENT_QUOTES, 'UTF-8');
        $safePolicy = htmlspecialchars($policy, ENT_QUOTES, 'UTF-8');
        $statusColor = $this->statusColor($status);
        $itemsHtml = $this->renderBookingItemsHtml($items);

        return <<<HTML
<!doctype html>
<html lang="en">
<body style="margin:0;background:#F6EFE1;padding:0;font-family:Arial,Helvetica,sans-serif;color:#264414;">
  <div style="background:#F6EFE1;padding:32px 16px;">
    <div style="max-width:720px;margin:0 auto;background:#fff;border:1px solid rgba(38,68,20,.12);border-radius:18px;overflow:hidden;box-shadow:0 16px 48px rgba(0,0,0,.08);">
      <div style="background:#264414;color:#fff;padding:28px 32px;">
        <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;font-weight:700;color:#DDE255;">PICKLED</div>
        <div style="font-size:30px;line-height:1.1;font-weight:900;margin-top:8px;">Booking confirmed</div>
      </div>
      <div style="padding:32px;">
        <p style="margin:0 0 16px;font-size:16px;line-height:1.7;">Hi {$safeName},</p>
        <p style="margin:0 0 24px;font-size:16px;line-height:1.7;">Your booking has been submitted. Here is your confirmation summary.</p>
        <table role="presentation" cellpadding="0" cellspacing="0" style="width:100%;border-collapse:separate;border-spacing:0 12px;">
          <tr>
            <td style="width:50%;padding-right:8px;vertical-align:top;">
              <div style="background:#F6EFE1;border:1px solid rgba(38,68,20,.12);border-radius:14px;padding:16px 18px;">
                <div style="font-size:11px;letter-spacing:1.8px;text-transform:uppercase;font-weight:800;color:#7a7a6e;margin-bottom:6px;">Reference</div>
                <div style="font-size:18px;font-weight:800;color:#264414;">{$safeReference}</div>
              </div>
            </td>
            <td style="width:50%;padding-left:8px;vertical-align:top;">
              <div style="background:#F6EFE1;border:1px solid rgba(38,68,20,.12);border-radius:14px;padding:16px 18px;">
                <div style="font-size:11px;letter-spacing:1.8px;text-transform:uppercase;font-weight:800;color:#7a7a6e;margin-bottom:6px;">Status</div>
                <div style="display:inline-block;background:{$statusColor};color:#fff;border-radius:999px;padding:6px 12px;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:1px;">{$safeStatus}</div>
              </div>
            </td>
          </tr>
          <tr>
            <td style="width:50%;padding-right:8px;vertical-align:top;">
              <div style="background:#fff;border:1px solid rgba(38,68,20,.12);border-radius:14px;padding:16px 18px;">
                <div style="font-size:11px;letter-spacing:1.8px;text-transform:uppercase;font-weight:800;color:#7a7a6e;margin-bottom:6px;">Payment</div>
                <div style="font-size:16px;font-weight:700;color:#264414;">{$safePaymentMethod}</div>
                <div style="font-size:13px;color:#7a7a6e;margin-top:4px;">{$safePaymentStatus}</div>
              </div>
            </td>
            <td style="width:50%;padding-left:8px;vertical-align:top;">
              <div style="background:#fff;border:1px solid rgba(38,68,20,.12);border-radius:14px;padding:16px 18px;">
                <div style="font-size:11px;letter-spacing:1.8px;text-transform:uppercase;font-weight:800;color:#7a7a6e;margin-bottom:6px;">Total</div>
                <div style="font-size:24px;font-weight:900;color:#264414;">PHP {$safeTotal}</div>
              </div>
            </td>
          </tr>
        </table>
        <div style="margin-top:16px;padding:16px 18px;border-left:4px solid #DDE255;background:#fcfde7;border-radius:12px;">
          <div style="font-size:11px;letter-spacing:1.8px;text-transform:uppercase;font-weight:800;color:#264414;margin-bottom:6px;">Cancellation policy</div>
          <div style="font-size:14px;line-height:1.6;color:#264414;">{$safePolicy}</div>
        </div>
        <div style="margin-top:24px;">
          <div style="font-size:11px;letter-spacing:1.8px;text-transform:uppercase;font-weight:800;color:#7a7a6e;margin-bottom:10px;">Booking items</div>
          {$itemsHtml}
        </div>
      </div>
      <div style="padding:16px 32px 28px;color:#7a7a6e;font-size:12px;line-height:1.6;border-top:1px solid rgba(38,68,20,.08);">
        Thank you for booking with PICKLED.
      </div>
    </div>
  </div>
</body>
</html>
HTML;
    }

    private function renderContactHtml(string $name, string $email, string $phone, string $message): string
    {
        $safeName = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
        $safeEmail = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
        $safePhone = htmlspecialchars($phone, ENT_QUOTES, 'UTF-8');
        $safeMessage = nl2br(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'));

        return <<<HTML
<!doctype html>
<html lang="en">
<body style="margin:0;background:#F6EFE1;padding:0;font-family:Arial,Helvetica,sans-serif;color:#264414;">
  <div style="background:#F6EFE1;padding:32px 16px;">
    <div style="max-width:720px;margin:0 auto;background:#fff;border:1px solid rgba(38,68,20,.12);border-radius:18px;overflow:hidden;box-shadow:0 16px 48px rgba(0,0,0,.08);">
      <div style="background:#264414;color:#fff;padding:28px 32px;">
        <div style="font-size:12px;letter-spacing:2px;text-transform:uppercase;font-weight:700;color:#DDE255;">PICKLED</div>
        <div style="font-size:30px;line-height:1.1;font-weight:900;margin-top:8px;">New contact inquiry</div>
      </div>
      <div style="padding:32px;">
        <p style="margin:0 0 12px;font-size:16px;line-height:1.7;"><strong>Name:</strong> {$safeName}</p>
        <p style="margin:0 0 12px;font-size:16px;line-height:1.7;"><strong>Email:</strong> {$safeEmail}</p>
        <p style="margin:0 0 18px;font-size:16px;line-height:1.7;"><strong>Phone:</strong> {$safePhone}</p>
        <div style="margin:0 0 24px;padding:16px 18px;border-left:4px solid #F85696;background:#fff9fb;border-radius:12px;">
          <div style="font-size:12px;letter-spacing:1.8px;text-transform:uppercase;font-weight:800;color:#F85696;margin-bottom:6px;">Message</div>
          <div style="font-size:15px;line-height:1.7;color:#264414;">{$safeMessage}</div>
        </div>
      </div>
      <div style="padding:16px 32px 28px;color:#7a7a6e;font-size:12px;line-height:1.6;border-top:1px solid rgba(38,68,20,.08);">
        This inquiry was submitted from the Pickled contact page.
      </div>
    </div>
  </div>
</body>
</html>
HTML;
    }

    private function renderBookingItemsHtml(array $items): string
    {
        if (empty($items)) {
            return '<div style="font-size:14px;color:#7a7a6e;">No booking items found.</div>';
        }

        $rows = [];
        foreach ($items as $item) {
            $court = htmlspecialchars((string) ($item['court'] ?? 'Court'), ENT_QUOTES, 'UTF-8');
            $session = htmlspecialchars((string) ($item['name'] ?? 'Session'), ENT_QUOTES, 'UTF-8');
            $date = htmlspecialchars((string) ($item['date'] ?? ''), ENT_QUOTES, 'UTF-8');
            $time = htmlspecialchars((string) ($item['time'] ?? ''), ENT_QUOTES, 'UTF-8');
            $quantity = (int) ($item['quantity'] ?? 1);
            $price = number_format((float) ($item['price'] ?? 0), 2);

            $rows[] = <<<HTML
<div style="border:1px solid rgba(38,68,20,.12);border-radius:14px;padding:16px 18px;background:#fff;margin-bottom:10px;">
  <div style="font-size:15px;font-weight:800;color:#264414;margin-bottom:6px;">{$court}</div>
  <div style="font-size:14px;line-height:1.6;color:#264414;">{$session}</div>
  <div style="font-size:13px;line-height:1.6;color:#7a7a6e;margin-top:4px;">{$date} {$time}</div>
  <div style="font-size:13px;line-height:1.6;color:#7a7a6e;margin-top:4px;">{$quantity} participant(s) · PHP {$price}</div>
</div>
HTML;
        }

        return implode('', $rows);
    }

    private function statusColor(string $status): string
    {
        $status = strtolower($status);

        return match (true) {
            str_contains($status, 'cancel') => '#8f1d2f',
            str_contains($status, 'complete') => '#6b6b6b',
            str_contains($status, 'ongoing') => '#0b4a7a',
            str_contains($status, 'confirm') => '#12401f',
            default => '#6d4b00',
        };
    }

    private function normalizeHtml(string $html): string
    {
        return str_replace(["\r\n", "\r"], "\n", $html);
    }

    private function displayName(array $user): string
    {
        $name = trim((string) ($user['name'] ?? ''));
        return $name !== '' ? $name : 'Member';
    }

    private function formatBookingItems(array $items): string
    {
        if (empty($items)) {
            return '- No booking items found.';
        }

        $lines = [];
        foreach ($items as $item) {
            $court = (string) ($item['court'] ?? 'Court');
            $session = (string) ($item['name'] ?? 'Session');
            $date = (string) ($item['date'] ?? '');
            $time = (string) ($item['time'] ?? '');
            $quantity = (int) ($item['quantity'] ?? 1);
            $price = number_format((float) ($item['price'] ?? 0), 2);

            $lines[] = "- {$court} / {$session} / {$date} {$time} / {$quantity} participant(s) / PHP {$price}";
        }

        return implode("\n", $lines);
    }
}
