<?php
session_start();
if (empty($_SESSION['user'])) {
  header('Location: login.php?notice=booking&redirect=booking.php');
  exit;
}

$pageTitle  = 'Book Now - Pickled';
$activePage = 'courts.php';
$extraHead  = '<style>
  .booking-landing{min-height:70vh;padding:calc(var(--nav-h) + 90px) 20px 90px;background:#f6f0e4;color:#245f49}
  .booking-landing__inner{max-width:960px;margin:0 auto;text-align:center}
  .booking-landing h1{font-size:clamp(42px,7vw,86px);line-height:1;text-transform:uppercase;margin-bottom:22px}
  .booking-landing p{font-size:18px;line-height:1.6;font-weight:700;max-width:680px;margin:0 auto 34px}
  .booking-landing__actions{display:flex;justify-content:center;gap:14px;flex-wrap:wrap}
</style>';
include '_header.php';
?>

<main class="booking-landing">
  <div class="booking-landing__inner">
    <h1>Book Your Game</h1>
    <p>Choose court booking, coaching, or social play. The live booking widgets are available inside the court and social play pages.</p>
    <div class="booking-landing__actions">
      <a href="courts.php#court-detail" class="btn btn-green btn-md">Court booking</a>
      <a href="social-play.php#social-booking" class="btn btn-lime btn-md">Social play</a>
      <a href="private.php" class="btn btn-ghost-dark btn-md">Private group</a>
    </div>
  </div>
</main>

<?php include '_footer.php'; ?>
