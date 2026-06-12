<?php
require_once __DIR__ . '/../includes/security.php';
pickled_init_csrf();
$pageTitle  = 'Social Play - Pickled';
$activePage = 'social-play.php';
$basePath   = '../';
$extraHead  = '<link rel="stylesheet" href="../assets/css/social-play.css?v=20260610b"/>';
include __DIR__ . '/../includes/header.php';

$galleryImages = [
  '../assets/img/court/social play-1.png',
  '../assets/img/court/social play-2.png',
  '../assets/img/court/social play-3.png',
];

$faqs = [
  ['WHO ARE BEGINNER SESSIONS FOR?', 'Beginner sessions are for first-timers or newer players who want a friendly, low-pressure place to learn scoring, serving, movement, and basic rallies.'],
  ['WHO SHOULD JOIN ALL SKILLS OR ADVANCED BEGINNER SESSIONS?', 'Join these sessions if you understand the basics and want casual games with mixed levels. We rotate players so everyone gets court time and new matchups.'],
  ['WHO SHOULD JOIN INTERMEDIATE OR ADVANCED SESSIONS?', 'These sessions are for players who can sustain rallies, understand positioning, and want faster games with more strategy and consistency.'],
  ['HOW DO SOCIAL PICKLEBALL SESSIONS WORK?', 'Our Open Match-Play sessions use a structured rotation system, so players are paired with different partners and opponents throughout the 2-hour window.'],
  ['WHAT SHOULD I WEAR?', 'Wear comfortable athletic clothes and non-marking court shoes. Bring water and a towel. Paddles and balls can be provided for first-timers.'],
  ['CANCELLATION AND NO-SHOW POLICY', 'Please cancel early if you cannot attend. Late cancellations and no-shows may be charged because spots are limited and sessions fill quickly.'],
  ['WAITLIST INFO', 'If a session is full, join the waitlist. When a spot opens, we will contact players in order so someone can take the court time.'],
  ['FREE PARKING', 'Free parking is available where venue policy allows. Present your booking confirmation when requested.'],
];
?>

<main class="social-page">
  <section class="social-hero">
    <img src="../assets/img/court/social play-1.png" alt="Social Play" />
    <div class="social-hero__overlay">
      <p>Join our Social Play Events</p>
      <h1>SOCIAL PLAY</h1>
      <span>Ready to play? Join the Court Green community today!</span>
    </div>
  </section>

  <section class="social-intro">
    <div class="social-wrap intro-grid">
      <img src="../assets/img/court/social play-2.png" alt="Players at social pickleball session" />
      <article>
        <h2>Join our Community Open Session</h2>
        <p>Ready to mix it up on the court? Our Community Open Session brings together pickleball enthusiasts of all skill levels to share the game we cannot get enough of.</p>
        <p>Dive into organized casual games, meet new partners, and level up your skills in a vibrant, low-pressure atmosphere.</p>
        <strong>Want to know more? Check out the info below!</strong>
      </article>
    </div>
  </section>

  <section class="flow-section">
    <div class="social-wrap">
      <h2>FLOW</h2>
      <div class="flow-grid">
        <article>
          <span class="flow-icon flow-icon--box"></span>
          <h3>BROWSE SESSIONS</h3>
          <p>Explore court times, clinics, and competitive events for every skill level.</p>
        </article>
        <article>
          <span class="flow-icon flow-icon--cart"></span>
          <h3>PICK YOUR GAME</h3>
          <p>View full details including timing, participant lists, and coaching objectives.</p>
        </article>
        <article>
          <span class="flow-icon flow-icon--person"></span>
          <h3>SECURE YOUR SPOT</h3>
          <p>Confirm your reservation instantly and keep your place in player rotations.</p>
        </article>
        <article>
          <span class="flow-icon flow-icon--lock"></span>
          <h3>JOIN US COURTSIDE</h3>
          <p>Check in at the club, meet the community, and elevate your game.</p>
        </article>
      </div>
    </div>
  </section>

  <section class="social-booking" id="social-booking">
    <div class="social-wrap booking-grid">
      <div class="social-gallery">
        <div class="social-thumbs">
          <?php foreach ($galleryImages as $index => $image): ?>
            <button class="social-thumb <?= $index === 0 ? 'is-active' : '' ?>" type="button" data-social-image="<?= htmlspecialchars($image) ?>">
              <img src="<?= htmlspecialchars($image) ?>" alt="Court green thumbnail <?= $index + 1 ?>" />
            </button>
          <?php endforeach; ?>
        </div>
        <div class="social-main-image">
          <img id="socialMainImage" src="<?= htmlspecialchars($galleryImages[0]) ?>" alt="Court Green social play" />
        </div>
      </div>

      <article class="social-product">
        <p>PICKLE &amp;</p>
        <h2><a class="social-court-link" href="courts.php#court-detail">COURT GREEN</a></h2>
        <div class="social-note">Connect, compete, and enjoy 2 hours of high-energy pickleball action</div>
        <div class="social-price">₱350.00 <span>/ session</span></div>
        <button class="social-option is-selected" type="button" data-social-option data-variant="green-open-match-play" data-label="OPEN MATCH-PLAY" data-price="350" data-duration="2 hours" data-mode="open-play" data-note="Open match-play dates are available on Tuesdays, Thursdays, and Saturdays.">
          <strong>OPEN MATCH-PLAY ₱350</strong>
          <small>Meet new partners, rotate games, and level up with peers.</small>
        </button>
        <button class="social-option" type="button" data-social-option data-variant="green-weekly-tournament" data-label="WEEKLY TOURNAMENT" data-price="900" data-duration="This week" data-mode="tournament" data-note="Weekly Court Green tournament brackets are available this Friday and Sunday.">
          <strong>WEEKLY TOURNAMENT ₱900</strong>
          <small>Compete in this week's Court Green bracket.</small>
        </button>
        <div class="social-product-actions">
          <button class="social-book-now" type="button" data-tooltip="Connect and Play">Book now</button>
          <form method="post" action="cart.php" id="socialCartForm">
            <input type="hidden" name="action" value="add_booking" />
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(pickled_csrf_token()) ?>" />
            <input type="hidden" name="variant_id" value="green-open-match-play" />
            <input type="hidden" name="date" value="" />
            <input type="hidden" name="time" value="" />
            <input type="hidden" name="quantity" value="1" />
            <button class="social-cart-button" type="submit">Add to cart</button>
          </form>
        </div>
        <div class="social-benefits">
          <span class="benefit-share">Meet New Partners</span>
          <span class="benefit-heart">Dynamic Rotation Play</span>
          <span class="benefit-person">Level up with Peers</span>
          <button type="button" data-social-share data-share-title="Pickled Social Play" data-share-text="Join Pickled Social Play at Court Green." data-share-url="social-play.php#social-booking">Share</button>
          <a href="#faq">View full details</a>
        </div>
      </article>
    </div>
  </section>

  <section class="faq-section" id="faq">
    <div class="social-wrap faq-layout">
      <aside>
        <p>LEARN MORE</p>
        <h2>FAQ'S</h2>
        <span>Also find us on social media.</span>
      </aside>
      <div class="faq-list">
        <?php foreach ($faqs as $index => [$question, $answer]): ?>
          <article class="faq-item">
            <button class="faq-question" type="button" aria-expanded="false">
              <span><?= htmlspecialchars($question) ?></span>
              <strong>+</strong>
            </button>
            <div class="faq-answer">
              <p><?= htmlspecialchars($answer) ?></p>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

</main>

<?php
$initialCalendar = new DateTimeImmutable('first day of this month');
$initialCalendarTitle = $initialCalendar->format('F Y');
$initialDaysInMonth = (int) $initialCalendar->format('t');
$initialMondayOffset = ((int) $initialCalendar->format('w') + 6) % 7;
$initialCalendarCells = (int) ceil(($initialMondayOffset + $initialDaysInMonth) / 7) * 7;
?>

<div class="social-modal" id="socialModal" aria-hidden="true">
  <div class="social-modal__dialog" role="dialog" aria-modal="true" aria-labelledby="socialBookingTitle">
    <button class="social-modal__close" type="button" aria-label="Close booking">×</button>
    <div class="social-book-step social-book-step--date is-active">
      <div class="social-calendar">
        <img src="<?= htmlspecialchars($galleryImages[0]) ?>" alt="" />
        <button type="button" id="socialBookingType">OPEN MATCH-PLAY ₱350⌄</button>
        <p id="socialBookingHint">Open match-play dates are available on Tuesdays, Thursdays, and Saturdays.</p>
        <div class="calendar-head">
          <button type="button">‹</button>
          <strong><?= htmlspecialchars($initialCalendarTitle) ?></strong>
          <button type="button">›</button>
        </div>
        <div class="calendar-grid">
          <span>MO</span><span>TU</span><span>WE</span><span>TH</span><span>FR</span><span>SA</span><span>SU</span>
          <?php for ($i = 0; $i < $initialCalendarCells; $i++): $day = $i - $initialMondayOffset + 1; ?>
            <?php if ($day < 1 || $day > $initialDaysInMonth): ?>
              <button type="button" disabled></button>
            <?php else: ?>
              <?php $dateLabel = $initialCalendar->setDate((int) $initialCalendar->format('Y'), (int) $initialCalendar->format('n'), $day)->format('l, F j, Y'); ?>
              <button type="button" disabled data-date="<?= htmlspecialchars($dateLabel) ?>"><?= $day ?></button>
            <?php endif; ?>
          <?php endfor; ?>
        </div>
        <div class="calendar-legend" aria-label="Calendar legend">
          <span><i class="legend-dot legend-dot--available"></i>Available</span>
          <span><i class="legend-dot legend-dot--selected"></i>Selected</span>
          <span><i class="legend-dot legend-dot--booked"></i>Booked</span>
          <span><i class="legend-dot legend-dot--unavailable"></i>Not available</span>
        </div>
      </div>
      <div class="social-times">
        <h2 id="socialBookingTitle">Book a 2 hours session</h2>
        <label>
          <span>Person <b id="socialQty">1</b></span>
          <input id="socialQtyInput" type="range" min="1" max="8" value="1" />
          <strong id="socialTotal">₱350.00</strong>
        </label>
        <h3>What time works best?</h3>
        <p>Asia/Manila · <span id="socialPhTime">PH time</span></p>
        <div class="social-time-grid" id="socialTimeGrid">
          <button class="social-time" type="button" data-time="08:00 AM - 10:00 AM" data-modes="open-play">08:00 AM - 10:00 AM</button>
          <button class="social-time" type="button" data-time="07:00 PM - 09:00 PM" data-modes="open-play">07:00 PM - 09:00 PM</button>
          <button class="social-time" type="button" data-time="10:00 AM - 12:00 PM" data-modes="open-play">10:00 AM - 12:00 PM</button>
          <button class="social-time" type="button" data-time="12:00 PM - 02:00 PM" data-modes="open-play">12:00 PM - 02:00 PM</button>
          <button class="social-time" type="button" data-time="02:00 PM - 04:00 PM" data-modes="open-play">02:00 PM - 04:00 PM</button>
          <button class="social-time" type="button" data-time="04:00 PM - 06:00 PM" data-modes="open-play">04:00 PM - 06:00 PM</button>
          <button class="social-time" type="button" data-time="06:00 PM - 08:00 PM" data-modes="open-play">06:00 PM - 08:00 PM</button>
          <button class="social-time" type="button" data-time="08:00 PM - 10:00 PM" data-modes="open-play">08:00 PM - 10:00 PM</button>
          <button class="social-time" type="button" data-time="09:00 AM - 12:00 PM" data-modes="tournament">09:00 AM - 12:00 PM</button>
          <button class="social-time" type="button" data-time="01:00 PM - 04:00 PM" data-modes="tournament">01:00 PM - 04:00 PM</button>
          <button class="social-time" type="button" data-time="06:00 PM - 09:00 PM" data-modes="tournament">06:00 PM - 09:00 PM</button>
          <button class="social-time" type="button" data-time="02:00 PM - 05:00 PM" data-modes="tournament">02:00 PM - 05:00 PM</button>
        </div>
        <button class="social-continue" type="button">Continue</button>
      </div>
    </div>
    <div class="social-book-step social-book-step--form">
      <aside class="social-summary">
        <button class="social-back" type="button">‹ Back to dates</button>
        <img src="<?= htmlspecialchars($galleryImages[0]) ?>" alt="" />
        <h3 id="socialSummaryProduct">OPEN MATCH-PLAY ₱350</h3>
        <dl>
          <dt>Date</dt><dd id="socialSummaryDate">Selected date</dd>
          <dt>Time</dt><dd id="socialSummaryTime">07:00 PM - 09:00 PM</dd>
          <dt>Quantity</dt><dd id="socialSummaryQty">1</dd>
          <dt>Subtotal</dt><dd id="socialSummarySubtotal">₱350.00</dd>
          <dt>Payment fee</dt><dd id="socialSummaryFee">₱0.00</dd>
          <dt>Total</dt><dd id="socialSummaryTotal">₱350.00</dd>
        </dl>
      </aside>
      <form class="social-form" id="socialDetailsForm">
        <h2>Your Information</h2>
        <div class="social-alert">This will add your selected social play session to cart. Payment happens during checkout.</div>
        <label>Name *<input type="text" required placeholder="Enter your name" id="socialName" /></label>
        <label>Email *<input type="email" required placeholder="Enter your email" id="socialEmail" /></label>
        <fieldset>
          <legend>What is your experience level in pickleball? *</legend>
          <label><input type="radio" name="social_level" required /> New or had trial class experience</label>
          <label><input type="radio" name="social_level" /> DUPR 2.0-2.5</label>
          <label><input type="radio" name="social_level" /> DUPR 2.5-3.0</label>
          <label><input type="radio" name="social_level" /> DUPR 3-3.5</label>
        </fieldset>
        <button type="submit" class="social-continue-form">Add to cart</button>
      </form>
    </div>
    <div class="social-book-step social-book-step--payment">
      <aside class="social-summary">
        <button class="social-back-payment" type="button">‹ Back</button>
        <img src="<?= htmlspecialchars($galleryImages[0]) ?>" alt="" />
        <h3 id="socialPaymentProduct">OPEN MATCH-PLAY ₱350</h3>
        <dl>
          <dt>Date</dt><dd id="socialPaymentDate">Selected date</dd>
          <dt>Time</dt><dd id="socialPaymentTime">07:00 PM - 09:00 PM</dd>
          <dt>Quantity</dt><dd id="socialPaymentQty">1</dd>
          <dt>Subtotal</dt><dd id="socialPaymentSubtotal">₱350.00</dd>
          <dt>Payment fee</dt><dd id="socialPaymentFee">₱0.00</dd>
          <dt>Total</dt><dd id="socialPaymentTotal">₱350.00</dd>
        </dl>
      </aside>
      <form class="social-payment-form" id="socialPaymentForm">
        <h2>Payment Method</h2>
        <fieldset class="social-payment-methods">
          <legend>Choose payment method *</legend>
          <label><input type="radio" name="social_payment" value="Pay at Club" data-fee-rate="0" checked /> Pay at Club <span>No fee</span></label>
          <label><input type="radio" name="social_payment" value="Credit / Debit Card" data-fee-rate="0.03" /> Credit / Debit Card <span>+3%</span></label>
          <label><input type="radio" name="social_payment" value="Apple Pay / Google Pay" data-fee-rate="0.03" /> Apple Pay / Google Pay <span>+3%</span></label>
        </fieldset>
        <div class="social-breakdown">
          <span>Subtotal <strong id="socialPaymentDisplaySubtotal">₱350.00</strong></span>
          <span>Payment fee <strong id="socialPaymentDisplayFee">₱0.00</strong></span>
          <span>Total <strong id="socialPaymentDisplayTotal">₱350.00</strong></span>
        </div>
        <button type="submit" class="social-confirm-payment">Confirm Payment</button>
      </form>
    </div>
    <div class="social-book-step social-book-step--confirmation">
      <div class="social-confirmation">
        <p>Booking confirmed</p>
        <h2>Booking Confirmed</h2>
        <div class="confirmation-content">
          <p>Thank you for booking with us.</p>
          <div class="reference-box">
            <span>Your Reference Number</span>
            <strong id="refNumber" class="ref-display">REF-XXXXXXXX</strong>
          </div>
          <dl class="social-confirmed-details">
            <dt>Session</dt><dd id="confirmedSocialProduct">OPEN MATCH-PLAY</dd>
            <dt>Name</dt><dd id="confirmedSocialName">Guest</dd>
            <dt>Date</dt><dd id="confirmedSocialDate">Selected date</dd>
            <dt>Time</dt><dd id="confirmedSocialTime">07:00 PM - 09:00 PM</dd>
            <dt>Total</dt><dd id="confirmedSocialTotal">₱350.00</dd>
          </dl>
          <p>A confirmation email has been sent to <strong id="confirmationEmail">your email</strong></p>
          <p class="small-text">Please present this reference number when you arrive at the club.</p>
          <form class="social-feedback-form">
            <div class="social-success" role="status" aria-live="polite">Thank you for your feedback.</div>
            <h3>Feedback</h3>
            <fieldset>
              <legend>Rate your booking experience</legend>
              <label><input type="radio" name="social_rating" value="5" required /> 5</label>
              <label><input type="radio" name="social_rating" value="4" /> 4</label>
              <label><input type="radio" name="social_rating" value="3" /> 3</label>
              <label><input type="radio" name="social_rating" value="2" /> 2</label>
              <label><input type="radio" name="social_rating" value="1" /> 1</label>
            </fieldset>
            <label>Comments<textarea placeholder="Tell us how we did"></textarea></label>
            <button type="submit">Submit feedback</button>
          </form>
        </div>
        <button type="button" class="social-close-confirmation">Close</button>
      </div>
    </div>
  </div>
</div>

<div class="share-toast" id="socialShareToast" role="status" aria-live="polite">Share link copied.</div>

<script>
(function(){
  const isLoggedIn = <?= !empty($_SESSION['user']) ? 'true' : 'false' ?>;
  const loginUrl = '../auth/login.php?notice=booking&redirect=resident/social-play.php%23social-booking';
  const modal = document.getElementById('socialModal');
  const dateStep = modal.querySelector('.social-book-step--date');
  const formStep = modal.querySelector('.social-book-step--form');
  const paymentStep = modal.querySelector('.social-book-step--payment');
  const confirmationStep = modal.querySelector('.social-book-step--confirmation');
  const calendarGrid = modal.querySelector('.calendar-grid');
  const calendarTitle = modal.querySelector('.calendar-head strong');
  const calendarNavButtons = modal.querySelectorAll('.calendar-head button');
  const csrfToken = '<?= htmlspecialchars(pickled_csrf_token()) ?>';
  const availabilityEndpoint = '../app/api/availability.php';
  let availability = { dates: {} };
  const state = {
    variant: 'green-open-match-play',
    label: 'OPEN MATCH-PLAY',
    note: 'Open match-play dates are available on Tuesdays, Thursdays, and Saturdays.',
    mode: 'open-play',
    duration: '2 hours',
    date: '',
    time: '',
    qty: 1,
    price: 350,
    feeRate: 0,
    paymentMethod: 'Pay at Club',
    name: '',
    email: ''
  };
  const money = value => '₱' + Number(value).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
  const months = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  const weekdays = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  let visibleMonth = new Date(<?= (int) date('Y') ?>, <?= (int) date('n') - 1 ?>, 1);

  function absoluteUrl(path){
    return new URL(path, window.location.href).href;
  }

  function showShareToast(message){
    const toast = document.getElementById('socialShareToast');
    toast.textContent = message;
    toast.classList.add('is-visible');
    window.clearTimeout(showShareToast.timer);
    showShareToast.timer = window.setTimeout(() => toast.classList.remove('is-visible'), 2200);
  }

  function sharePage(button){
    const shareData = {
      title: button.dataset.shareTitle || document.title,
      text: button.dataset.shareText || '',
      url: absoluteUrl(button.dataset.shareUrl || window.location.href)
    };

    if (navigator.share) {
      navigator.share(shareData).catch(() => {});
      return;
    }

    if (navigator.clipboard && navigator.clipboard.writeText) {
      navigator.clipboard.writeText(shareData.url).then(() => showShareToast('Share link copied.'));
      return;
    }

    showShareToast(shareData.url);
  }

  // Generate random reference number
  function generateReferenceNumber(){
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    let ref = 'REF-';
    for (let i = 0; i < 8; i++) {
      ref += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return ref;
  }

  function formatDate(year, month, day){
    const date = new Date(year, month, day);
    return weekdays[date.getDay()] + ', ' + months[month] + ' ' + day + ', ' + year;
  }

  function dateAllowed(date){
    return true;
  }

  function selectedDayAvailability(){
    return availability.dates[state.date] || null;
  }

  async function loadAvailability(){
    const year = visibleMonth.getFullYear();
    const month = visibleMonth.getMonth() + 1;
    const response = await fetch(availabilityEndpoint + '?variant=' + encodeURIComponent(state.variant) + '&year=' + year + '&month=' + month);
    availability = await response.json();
    renderCalendar();
    updateTimes();
    updateSummary();
  }

  function renderCalendar(){
    const year = visibleMonth.getFullYear();
    const month = visibleMonth.getMonth();
    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const mondayOffset = (new Date(year, month, 1).getDay() + 6) % 7;
    let html = '<span>MO</span><span>TU</span><span>WE</span><span>TH</span><span>FR</span><span>SA</span><span>SU</span>';

    for (let i = 0; i < mondayOffset; i++) html += '<button type="button" disabled></button>';

    for (let day = 1; day <= daysInMonth; day++) {
      const date = new Date(year, month, day);
      const label = formatDate(year, month, day);
      const availableDate = availability.dates[label];
      const allowed = dateAllowed(date) && !!availableDate;
      const booked = allowed && !availableDate.available;
      const active = allowed && !booked && label === state.date ? ' is-selected' : '';
      const status = booked ? ' is-booked' : allowed ? ' is-available' : ' is-unavailable';
      const disabled = allowed && !booked ? '' : ' disabled title="' + (booked ? 'Booked' : 'Not available for this session') + '"';
      const labelText = booked ? day + '<small>Booked</small>' : day;
      html += '<button type="button" class="' + (status + active).trim() + '"' + disabled + ' data-date="' + label + '">' + labelText + '</button>';
    }

    calendarTitle.textContent = months[month] + ' ' + year;
    calendarGrid.innerHTML = html;
    if (!calendarGrid.querySelector('.is-selected')) {
      const first = calendarGrid.querySelector('[data-date]:not(:disabled)');
      if (first) {
        first.classList.add('is-selected');
        state.date = first.dataset.date;
      }
    }
  }

  function updateTimes(){
    const usable = [];
    modal.querySelectorAll('.social-time').forEach(button => {
      const modes = (button.dataset.modes || '').split(',');
      const show = modes.includes(state.mode);
      const dayAvailability = selectedDayAvailability();
      const slot = dayAvailability && dayAvailability.slots ? dayAvailability.slots[button.dataset.time] : null;
      const booked = !slot || slot.full;
      button.hidden = !show;
      button.disabled = !show || booked;
      if (!show || booked) button.classList.remove('is-selected');
      if (show && !booked) usable.push(button);
    });
    if (!modal.querySelector('.social-time.is-selected:not([hidden]):not(:disabled)') && usable.length) {
      usable[0].classList.add('is-selected');
      state.time = usable[0].dataset.time;
    }
  }

  function updateBookingCopy(){
    document.getElementById('socialBookingType').textContent = state.label + ' ₱' + state.price.toLocaleString('en-PH') + '⌄';
    document.getElementById('socialBookingHint').textContent = state.note;
    document.getElementById('socialBookingTitle').textContent = 'Book a ' + state.duration + ' session';
    document.getElementById('socialSummaryProduct').textContent = state.label + ' ₱' + state.price.toLocaleString('en-PH');
    document.getElementById('socialPaymentProduct').textContent = state.label + ' ₱' + state.price.toLocaleString('en-PH');
    loadAvailability();
    updateSummary();
  }

  function updateSummary(){
    const subtotal = state.qty * state.price;
    const fee = subtotal * state.feeRate;
    const total = subtotal + fee;
    document.getElementById('socialQty').textContent = state.qty;
    document.getElementById('socialTotal').textContent = money(total);
    document.getElementById('socialSummaryDate').textContent = state.date;
    document.getElementById('socialSummaryTime').textContent = state.time;
    document.getElementById('socialSummaryQty').textContent = state.qty;
    document.getElementById('socialSummarySubtotal').textContent = money(subtotal);
    document.getElementById('socialSummaryFee').textContent = money(fee);
    document.getElementById('socialSummaryTotal').textContent = money(total);
    document.getElementById('socialPaymentDate').textContent = state.date;
    document.getElementById('socialPaymentTime').textContent = state.time;
    document.getElementById('socialPaymentQty').textContent = state.qty;
    document.getElementById('socialPaymentSubtotal').textContent = money(subtotal);
    document.getElementById('socialPaymentFee').textContent = money(fee);
    document.getElementById('socialPaymentTotal').textContent = money(total);
    document.getElementById('socialPaymentDisplaySubtotal').textContent = money(subtotal);
    document.getElementById('socialPaymentDisplayFee').textContent = money(fee);
    document.getElementById('socialPaymentDisplayTotal').textContent = money(total);
    document.getElementById('confirmedSocialProduct').textContent = state.label;
    document.getElementById('confirmedSocialName').textContent = state.name || 'Guest';
    document.getElementById('confirmedSocialDate').textContent = state.date;
    document.getElementById('confirmedSocialTime').textContent = state.time;
    document.getElementById('confirmedSocialTotal').textContent = money(total);
  }

  document.querySelectorAll('[data-social-image]').forEach(button => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-social-image]').forEach(item => item.classList.remove('is-active'));
      button.classList.add('is-active');
      document.getElementById('socialMainImage').src = button.dataset.socialImage;
    });
  });

  function openSocialModal(){
    if (!isLoggedIn) {
      window.location.href = loginUrl;
      return;
    }
    updateBookingCopy();
    modal.classList.add('is-open');
    modal.setAttribute('aria-hidden', 'false');
    dateStep.classList.add('is-active');
    formStep.classList.remove('is-active');
    paymentStep.classList.remove('is-active');
    confirmationStep.classList.remove('is-active');
  }

  document.querySelector('.social-book-now').addEventListener('click', openSocialModal);
  const socialCartForm = document.getElementById('socialCartForm');
  if (socialCartForm) {
    socialCartForm.addEventListener('submit', () => {
      socialCartForm.elements.variant_id.value = state.variant;
      socialCartForm.elements.date.value = state.date;
      socialCartForm.elements.time.value = state.time;
      socialCartForm.elements.quantity.value = state.qty;
    });
  }

  document.querySelectorAll('[data-social-share]').forEach(button => {
    button.addEventListener('click', () => sharePage(button));
  });

  document.querySelector('.social-modal__close').addEventListener('click', closeModal);
  modal.addEventListener('click', event => { if (event.target === modal) closeModal(); });
  function closeModal(){
    modal.classList.remove('is-open');
    modal.setAttribute('aria-hidden', 'true');
  }

  document.getElementById('socialQtyInput').addEventListener('input', event => {
    state.qty = Number(event.target.value);
    updateSummary();
  });

  document.querySelectorAll('[data-social-option]').forEach(button => {
    button.addEventListener('click', () => {
      document.querySelectorAll('[data-social-option]').forEach(item => item.classList.remove('is-selected'));
      button.classList.add('is-selected');
      state.label = button.dataset.label;
      state.variant = button.dataset.variant;
      state.price = Number(button.dataset.price);
      state.duration = button.dataset.duration;
      state.mode = button.dataset.mode;
      state.note = button.dataset.note;
      updateBookingCopy();
      openSocialModal();
    });
  });

  document.querySelectorAll('.social-payment-form input[name="social_payment"]').forEach(input => {
    input.addEventListener('change', () => {
      state.paymentMethod = input.value;
      state.feeRate = Number(input.dataset.feeRate);
      updateSummary();
    });
  });

  calendarNavButtons[0].addEventListener('click', () => {
    visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() - 1, 1);
    loadAvailability();
  });

  calendarNavButtons[1].addEventListener('click', () => {
    visibleMonth = new Date(visibleMonth.getFullYear(), visibleMonth.getMonth() + 1, 1);
    loadAvailability();
  });

  calendarGrid.addEventListener('click', event => {
    const button = event.target.closest('[data-date]');
    if (!button || button.disabled) return;
    calendarGrid.querySelectorAll('[data-date]').forEach(item => item.classList.remove('is-selected'));
    button.classList.add('is-selected');
    state.date = button.dataset.date;
    updateSummary();
  });

  document.getElementById('socialTimeGrid').addEventListener('click', event => {
    const button = event.target.closest('.social-time');
    if (!button || button.disabled) return;
    modal.querySelectorAll('.social-time').forEach(item => item.classList.remove('is-selected'));
    button.classList.add('is-selected');
    state.time = button.dataset.time;
    updateSummary();
  });

  document.querySelector('.social-continue').addEventListener('click', () => {
    updateSummary();
    dateStep.classList.remove('is-active');
    formStep.classList.add('is-active');
  });

  document.querySelector('.social-back').addEventListener('click', () => {
    formStep.classList.remove('is-active');
    dateStep.classList.add('is-active');
  });

  document.querySelector('.social-form').addEventListener('submit', event => {
    event.preventDefault();
    state.name = document.getElementById('socialName').value.trim();
    state.email = document.getElementById('socialEmail').value.trim();
    updateSummary();
    const form = document.createElement('form');
    const fields = {
      action: 'add_booking',
      csrf_token: csrfToken,
      variant_id: state.variant,
      date: state.date,
      time: state.time,
      quantity: String(state.qty)
    };
    form.method = 'post';
    form.action = 'cart.php';
    Object.keys(fields).forEach(name => {
      const input = document.createElement('input');
      input.type = 'hidden';
      input.name = name;
      input.value = fields[name];
      form.appendChild(input);
    });
    document.body.appendChild(form);
    form.submit();
  });

  document.querySelector('.social-back-payment').addEventListener('click', () => {
    paymentStep.classList.remove('is-active');
    formStep.classList.add('is-active');
  });

  document.querySelector('.social-payment-form').addEventListener('submit', event => {
    event.preventDefault();
    document.getElementById('refNumber').textContent = generateReferenceNumber();
    document.getElementById('confirmationEmail').textContent = state.email || 'your email';
    paymentStep.classList.remove('is-active');
    confirmationStep.classList.add('is-active');
  });

  document.querySelector('.social-close-confirmation').addEventListener('click', closeModal);

  document.querySelector('.social-feedback-form').addEventListener('submit', event => {
    event.preventDefault();
    event.currentTarget.classList.add('is-confirmed');
    event.currentTarget.querySelector('button[type="submit"]').textContent = 'Feedback submitted';
  });

  document.querySelectorAll('.faq-question').forEach(button => {
    button.addEventListener('click', () => {
      const item = button.closest('.faq-item');
      const open = item.classList.toggle('is-open');
      button.setAttribute('aria-expanded', open ? 'true' : 'false');
      button.querySelector('strong').textContent = open ? '×' : '+';
    });
  });

  function tick(){
    document.getElementById('socialPhTime').textContent = new Intl.DateTimeFormat('en-PH', {
      timeZone: 'Asia/Manila',
      hour: '2-digit',
      minute: '2-digit'
    }).format(new Date());
  }
  tick();
  setInterval(tick, 30000);
  loadAvailability();
})();
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
