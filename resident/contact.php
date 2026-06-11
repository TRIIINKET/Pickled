<?php
$basePath   = '../';
$pageTitle  = 'Contact - Pickled';
$activePage = 'contact.php';
$extraHead  = '<link rel="stylesheet" href="../assets/css/contact.css"/>';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../app/services/EmailService.php';

pickled_start_secure_session();
pickled_init_csrf();

$contactSuccess = '';
$contactError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (!pickled_validate_csrf_token($_POST['csrf_token'] ?? null)) {
    $contactError = 'Invalid request. Please refresh and try again.';
  } else {
    $name = trim((string) ($_POST['name'] ?? ''));
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $phone = trim((string) ($_POST['phone'] ?? ''));
    $message = trim((string) ($_POST['message'] ?? ''));

    if ($name === '' || !$email || $phone === '' || $message === '') {
      $contactError = 'Please complete all contact fields.';
    } else {
      $emailService = new EmailService();
      if ($emailService->sendContactMessage([
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'message' => $message,
      ])) {
        $_SESSION['flash'] = [
          'type' => 'success',
          'message' => 'Message sent. We will contact you through email.'
        ];
        header('Location: contact.php');
        exit;
      }

      $contactError = 'Message saved, but the email could not be sent. Please try again later.';
      error_log('Contact email failed for ' . $email);
    }
  }
}
include __DIR__ . '/../includes/header.php';
?>

<main class="contact-page">
  <section class="contact-service">
    <div class="contact-service__inner">
      <h1>We are at your service!</h1>
      <p>If you have any general inquiries, sales-related questions, or press inquiries, please provide us with your contact information below. We will promptly get back to you to assist you further.</p>

      <div class="contact-info-bar">
        <a href="mailto:pickled.shopph@gmail.com">pickled.shopph@gmail.com</a>
        <span>Makati, Metro Manila</span>
        <span>Monday - Sunday, 10am - 10pm</span>
        <a href="https://wa.me/639000000000">0900 000 0000 (WhatsApp)</a>
      </div>

      <?php if ($contactSuccess || $contactError): ?>
        <div class="contact-success" role="status" aria-live="polite" style="display:block; <?= $contactError ? 'background:#FBE4E8;color:#8b1f33;' : '' ?>">
          <?= htmlspecialchars($contactError ?: $contactSuccess) ?>
        </div>
      <?php endif; ?>

      <form class="contact-form" action="contact.php" method="post" id="contactForm">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(pickled_csrf_token()) ?>" />
        <div class="contact-form__row">
          <input type="text" name="name" placeholder="First name" required value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" />
          <input type="email" name="email" placeholder="Enter your email address" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" />
          <input type="tel" name="phone" placeholder="Phone number" required value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>" />
        </div>
        <textarea name="message" placeholder="Comment" required><?= htmlspecialchars($_POST['message'] ?? '') ?></textarea>
        <button type="submit">Send message</button>
      </form>
    </div>
  </section>

  <section class="contact-location">
    <div class="contact-location__inner">
      <div class="contact-location__visual" role="img" aria-label="Pickled Manila venue image">
        <span>Pickled Manila</span>
      </div>
      <div class="contact-location__card">
        <div>
          <h2>Pickled</h2>
          <p>Makati, Metro Manila, Philippines</p>
        </div>
        <p>Mon - Fri, 10am - 10pm<br>Saturday, 10am - 10pm<br>Sunday, 10am - 10pm</p>
        <a href="https://maps.google.com/?q=Makati+Metro+Manila" target="_blank">Get directions</a>
      </div>
    </div>
  </section>
</main>

<?php include __DIR__ . '/../includes/footer.php'; ?>
