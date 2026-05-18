<?php
$pageTitle  = 'Private Events - Pickled';
$activePage = 'private.php';
$basePath   = '../';
$extraHead  = '<link rel="stylesheet" href="../css/private.css"/>';
include __DIR__ . '/../includes/_header.php';
?>

<main class="private-page">
  <section class="private-hero">
    <div class="private-hero__inner">
      <p>Planning an event?</p>
      <h1>Pickle <span>&amp;</span> <em>Launch</em></h1>
      <p>A 5,000 sq ft private space best for ultimate corporate or brand event experience where your team can dine, drink, and play in a dynamic and engaging environment. Our dedicated event spaces, pickleball courts, and food and drink options create the perfect setting for fostering connections, collaboration, and unforgettable moments.</p>
    </div>
  </section>

  <section class="private-gallery">
    <div class="private-gallery__grid">
      <article class="private-gallery__large">
        <img src="https://pickleand.club/cdn/shop/files/rachel_class1.jpg?v=1745813319&width=1500" alt="Private pickleball event group" />
      </article>
      <article>
        <img src="https://pickleand.club/cdn/shop/files/250411_-_Pickle__205.jpg?v=1744700285&width=900" alt="Group event at Pickle and Club" />
      </article>
      <article>
        <img src="https://pickleand.club/cdn/shop/files/250411_-_Pickle__215.jpg?v=1744701445&width=900" alt="Players at private pickleball event" />
      </article>
      <!-- <article class="private-gallery__wide">
        <img src="https://pickleand.club/cdn/shop/files/250411_-_Pickle__208_cc44460d-89be-4e85-a4cb-835987f57ee6.jpg?v=1744816152&width=1500" alt="Large private event group photo" />
      </article> -->
    </div>
  </section>

  <section class="private-service">
    <div class="private-service__inner">
      <h2>We are at your service!</h2>
      <p>If you have any general inquiries, sales-related questions, or press inquiries, please provide us with your contact information below. We will promptly get back to you to assist you further.</p>
      <a href="contact.php" class="private-service__button">Plan with us</a>
    </div>
  </section>
</main>

<?php include __DIR__ . '/../includes/_footer.php'; ?>
