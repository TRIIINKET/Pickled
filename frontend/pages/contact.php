<?php
$pageTitle  = 'Contact - Pickled';
$activePage = 'contact.php';
$basePath   = '../';
$extraHead  = '<link rel="stylesheet" href="../css/contact.css"/>';
include '../includes/_header.php';
?>

<main class="contact-page">
  <section class="contact-service">
    <div class="contact-service__inner">
      <h1>We are at your service!</h1>
      <p>If you have any general inquiries, sales-related questions, or press inquiries, please provide us with your contact information below. We will promptly get back to you to assist you further.</p>

      <div class="contact-info-bar">
        <a href="mailto:info@pickled.ph">info@pickled.ph</a>
        <span>Makati, Metro Manila</span>
        <span>Monday - Sunday, 10am - 10pm</span>
        <a href="https://wa.me/639000000000">0900 000 0000 (WhatsApp)</a>
      </div>

      <form class="contact-form" action="#" method="post" id="contactForm">
        <div class="contact-success" role="status" aria-live="polite">Message submitted. We will contact you through email.</div>
        <div class="contact-form__row">
          <input type="text" name="name" placeholder="First name" required />
          <input type="email" name="email" placeholder="Enter your email address" required />
          <input type="tel" name="phone" placeholder="Phone number" required />
        </div>
        <textarea name="message" placeholder="Comment" required></textarea>
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

<script>
(function(){
  const form = document.getElementById('contactForm');
  if (!form) return;
  form.addEventListener('submit', event => {
    event.preventDefault();
    if (!form.checkValidity()) {
      form.reportValidity();
      return;
    }
    form.classList.add('is-submitted');
    form.reset();
  });
})();
</script>

<?php include __DIR__ . '/../includes/_footer.php'; ?>
