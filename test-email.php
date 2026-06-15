<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/EmailService.php';

$message = '';
$messageType = 'success';
$to = trim((string) ($_POST['to'] ?? ''));

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        $message = 'Please enter a valid recipient email.';
        $messageType = 'error';
    } else {
        $html = '<h1 style="color:#264414;">PICKLED Test Email</h1>'
            . '<p>This is a test email from the PICKLED PHPMailer SMTP setup.</p>'
            . '<p>If you received this, Gmail SMTP is working.</p>';

        $emailService = new EmailService();
        if ($emailService->send($to, 'PICKLED Test Email', $html)) {
            $message = 'Test email sent successfully to ' . htmlspecialchars($to, ENT_QUOTES, 'UTF-8') . '.';
        } else {
            $message = 'Email failed: ' . htmlspecialchars($emailService->getLastError() ?? 'Unknown error', ENT_QUOTES, 'UTF-8');
            $messageType = 'error';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>PICKLED Test Email</title>
  <style>
    body { margin: 0; min-height: 100vh; display: grid; place-items: center; background: #F6EFE1; font-family: Arial, sans-serif; color: #172033; }
    main { width: min(520px, calc(100% - 32px)); background: #fff; border: 1px solid rgba(38,68,20,.14); border-radius: 12px; padding: 28px; }
    h1 { margin: 0 0 18px; color: #264414; }
    label { display: grid; gap: 8px; font-weight: 800; color: #667085; }
    input { min-height: 44px; border: 1px solid rgba(38,68,20,.18); border-radius: 8px; padding: 0 12px; font: inherit; }
    button { margin-top: 16px; width: 100%; min-height: 46px; border: 0; border-radius: 8px; background: #264414; color: #fff; font-weight: 900; cursor: pointer; }
    .message { margin-bottom: 16px; padding: 12px 14px; border-radius: 8px; background: #eef8e8; color: #264414; font-weight: 800; }
    .message.error { background: #fff1f1; color: #b42318; }
  </style>
</head>
<body>
  <main>
    <h1>PICKLED Test Email</h1>
    <?php if ($message): ?>
      <div class="message <?= $messageType === 'error' ? 'error' : '' ?>"><?= $message ?></div>
    <?php endif; ?>
    <form method="post">
      <label>
        Recipient Email
        <input type="email" name="to" value="<?= htmlspecialchars($to, ENT_QUOTES, 'UTF-8') ?>" placeholder="recipient@example.com" required>
      </label>
      <button type="submit">Send Test Email</button>
    </form>
  </main>
</body>
</html>
