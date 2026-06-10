<?php
$basePath = '';
$pageTitle  = 'Pickled - Indoor Pickleball Courts in Manila';
$activePage = 'index.php';
include __DIR__ . '/includes/header.php';
$courtBookingHref = !empty($_SESSION['user']) ? 'resident/courts.php#court-detail' : 'auth/login.php?notice=booking&redirect=resident/courts.php%23court-detail';

$events = [
  ['day' => 'Tue', 'date' => 'May 7',  'title' => 'Beginner Open Play',  'time' => '7:00 PM', 'spots' => '8 spots'],
  ['day' => 'Thu', 'date' => 'May 9',  'title' => 'Ladies Night Rally',  'time' => '6:30 PM', 'spots' => '6 spots'],
  ['day' => 'Sat', 'date' => 'May 11', 'title' => 'Doubles Social',      'time' => '4:00 PM', 'spots' => '10 spots'],
  ['day' => 'Sun', 'date' => 'May 12', 'title' => 'Family Court Hour',   'time' => '10:00 AM', 'spots' => '5 spots'],
];

$steps = [
  ['icon' => 'member', 'title' => 'Join Member',   'copy' => 'Join the pickleball community today to enjoy benefits and discounts!'],
  ['icon' => 'booking', 'title' => 'First Booking', 'copy' => 'Learn how to book here. One minute to secure your court, class, or social play.'],
  ['icon' => 'coaching', 'title' => 'Coaching',      'copy' => 'Learn, grow, and have fun with our coaching programs.'],
  ['icon' => 'kids', 'title' => 'Kids',          'copy' => 'Play, learn, grow, and have fun with family and friends.'],
];

function pickled_step_icon(string $icon): string {
  $icons = [
    'member' => '<svg viewBox="0 0 64 64" aria-hidden="true"><path d="M24 8 10 26c-3 4-2 9 2 12l6 4"/><path d="m28 18 18 18"/><path d="M39 54 22 37c-4-4-4-10 0-14l2-2c4-4 10-4 14 0l8 8c4 4 4 10 0 14L39 54Z"/><path d="m15 43-7 9"/><path d="m21 47-7 9"/><path d="M46 50c3 0 6-3 6-6"/><path d="M54 50c0 3-3 6-6 6"/></svg>',
    'booking' => '<svg viewBox="0 0 64 64" aria-hidden="true"><path d="m12 31 18-18 22 22-18 18Z"/><path d="m22 21 21 21"/><path d="M38 12a5 5 0 1 0 10 0 5 5 0 0 0-10 0Z"/><path d="M12 49h16"/><path d="M20 41v16"/></svg>',
    'coaching' => '<svg viewBox="0 0 64 64" aria-hidden="true"><path d="M20 50c-6-5-9-11-8-18 4 3 8 2 10-2 1-8 7-14 16-18-1 5 0 9 4 12 3-1 6-3 8-6 4 12 0 24-10 31"/><path d="M28 44c-3-3-3-7-1-10 3 2 6 2 8-1 3 4 4 9 1 13"/><path d="M19 51c8 4 17 4 25 0"/></svg>',
    'kids' => '<svg viewBox="0 0 64 64" aria-hidden="true"><path d="M32 8v8"/><path d="M32 48v8"/><path d="M8 32h8"/><path d="M48 32h8"/><path d="m15 15 6 6"/><path d="m43 43 6 6"/><path d="m49 15-6 6"/><path d="m21 43-6 6"/><circle cx="32" cy="32" r="16"/><circle cx="26" cy="29" r="2"/><circle cx="38" cy="29" r="2"/><path d="M25 37c4 4 10 4 14 0"/></svg>',
  ];

  return $icons[$icon] ?? $icons['member'];
}

$rules = [
  ['Basic Rules', 'Play to 11 points and win by 2. Singles and doubles both work, and only the serving side scores.'],
  ['Serve', 'Serve underhand and diagonally. The ball must clear the kitchen and land inside the opposite service box.'],
  ['Two-Bounce Rule', 'After the serve, each side lets the ball bounce once before volleys are allowed.'],
  ['Kitchen Rule', 'No volleys inside the non-volley zone. Step in only when the ball has bounced there.'],
  ['End of a Rally', 'A rally ends when the ball lands out, hits the net on your side, or bounces twice.'],
];
?>

<main class="home-page">
  <section class="home-hero" aria-label="Pickled indoor pickleball">
    <div class="home-hero__shade"></div>
    <div class="home-hero__content">
      <!-- <img src="assets/img/WM-DGreen.png" alt="Pickled" class="home-hero__mark" /> -->
      <p class="home-hero__eyebrow">Manila indoor pickleball</p>
      <h1>Premier Indoor<br>Pickleball Courts<br class="home-mobile-break"></h1>
      <div class="home-hero__actions">
        <a href="resident/courts.php#court-detail" class="btn btn-lime btn-md">Book a court</a>
        <a href="resident/private.php" class="btn btn-hero-outline btn-md">View lessons</a>
      </div>
    </div>
  </section>

  <div class="home-strip" aria-hidden="true">
    <div class="home-strip__track">
      <span>Open play</span><span>Private lessons</span><span>Court booking</span><span>Kids programs</span>
      <span>Open play</span><span>Private lessons</span><span>Court booking</span><span>Kids programs</span>
      <span>Open play</span><span>Private lessons</span><span>Court booking</span><span>Kids programs</span>
      <span>Open play</span><span>Private lessons</span><span>Court booking</span><span>Kids programs</span>
    </div>
  </div>

  <section class="home-intro section">
    <div class="section-inner home-intro__inner" data-pickle-rotator>
      <p class="home-kicker">Let's</p>
      <h2>
        <span class="home-intro__fixed">Pickle</span>
        <span class="home-intro__amp">&amp;</span>
        <span class="home-intro__word is-solid" data-pickle-word>Beats</span>
      </h2>
      <p>The hottest sport in the world currently.</p>
      <p>A suitable sport to make people play with their families and friends with smiles that never stop!</p>
    </div>
  </section>

  <section class="home-start section">
    <div class="section-inner">
      <h2>How to start</h2>
      <div class="home-start__grid">
        <?php foreach ($steps as $step): ?>
          <article class="home-start__item">
            <span class="home-start__icon"><?= pickled_step_icon($step['icon']) ?></span>
            <h3><?= htmlspecialchars($step['title']) ?></h3>
            <p><?= htmlspecialchars($step['copy']) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <!-- <section class="home-events section">
    <div class="section-inner">
      <div class="home-section-head">
        <h2>Happening This Week</h2>
        <p>Social games, beginner sessions, and family-friendly court time.</p>
      </div>
      <div class="home-event-grid">
        <?php foreach ($events as $event): ?>
          <article class="home-event">
            <div class="home-event__date">
              <strong><?= htmlspecialchars($event['day']) ?></strong>
              <span><?= htmlspecialchars($event['date']) ?></span>
            </div>
            <h3><?= htmlspecialchars($event['title']) ?></h3>
            <div class="home-event__meta">
              <span><?= htmlspecialchars($event['time']) ?></span>
              <span><?= htmlspecialchars($event['spots']) ?></span>
            </div>
            <a href="resident/social-play.php#social-booking">Join now</a>
          </article>
        <?php endforeach; ?>
      </div>
      <div class="home-center-action">
        <a href="resident/social-play.php" class="btn btn-ghost-dark btn-sm">More sessions</a>
      </div>
    </div>
  </section> -->

  <section class="home-lessons section" id="lessons">
    <div class="section-inner">
      <div class="home-section-head home-section-head--compact">
        <h2>Lessons</h2>
      </div>
      <div class="home-lesson-layout">
        <article class="home-lesson home-lesson--large">
          <img src="assets/img/court/academy.png" alt="Academy pickleball lesson" />
          <div>
            <h3>Academy</h3>
            <p>Build your skills through structured programs designed for beginners to intermediate players.</p>
            <a href="resident/private.php" class="btn btn-lime btn-sm">Know more</a>
          </div>
          <p class="home-lesson__ticker"><span>Internationally certified coach | 4-8 players with internationally certified coach | 4-8 players with internationally certified coach | </span></p>
        </article>
        <div class="home-lesson-stack">
          <article class="home-lesson home-lesson--pink">
            <img src="assets/img/court/private lesson.png" alt="Private pickleball lesson" />
            <div>
              <h3>Private Lesson</h3>
              <p>Get one-on-one coaching tailored to your level and progress faster with personalized guidance.</p>
              <a href="resident/private.php" class="btn btn-lime btn-sm">Know more</a>
            </div>
            <p class="home-lesson__ticker"><span>Certified coach | 1 on 1 with an internationally certified coach | Certified coach | 1 on 1 with an internationally certified coach | </span></p>
          </article>
          <article class="home-lesson home-lesson--orange">
            <img src="assets/img/court/friends private.png" alt="Friends private pickleball lesson" />
            <div>
              <h3>Friends Private</h3>
              <p>Train together with friends in a private group session while improving teamwork and gameplay.</p>
              <a href="resident/private.php" class="btn btn-lime btn-sm">Know more</a>
            </div>
            <p class="home-lesson__ticker"><span>Internationally certified coach | Up to 6 on 1 with internationally certified coach | Internationally certified coach | Up to 6 on 1 with internationally certified coach | </span></p>
          </article>
        </div>
      </div>
    </div>
  </section>

  <section class="home-big-marquee" aria-hidden="true">
    <div class="home-big-marquee__track">
      <span>Everyone's Invited.</span>
      <strong>Game On. Vibe On.</strong>
      <span>Everyone's Invited.</span>
      <strong>Game On. Vibe On.</strong>
    </div>
  </section>

  <section class="home-court home-court--green section" id="court-green">
    <div class="section-inner home-court__grid">
      <div class="home-court__gallery">
        <div class="home-court__thumbs">
          <button type="button" class="is-active" data-court-thumb data-full="assets/img/court/court green-1.png" aria-label="Show Court Green image 1"><img src="assets/img/court/court green-1.png" alt="" /></button>
          <button type="button" data-court-thumb data-full="assets/img/court/court green-2.png" aria-label="Show Court Green image 2"><img src="assets/img/court/court green-2.png" alt="" /></button>
          <button type="button" data-court-thumb data-full="assets/img/court/court green-3.png" aria-label="Show Court Green image 3"><img src="assets/img/court/court green-3.png" alt="" /></button>
        </div>
        <div class="home-court__media">
          <img data-court-main src="assets/img/court/court green-1.png" alt="Green indoor pickleball court" />
        </div>
      </div>
      <div class="home-court__content">
        <p class="home-kicker">Court Green</p>
        <p class="home-price"><span data-court-price>₱600.00</span> <small>/ session</small></p>
        <ul class="home-court__list">
          <li><button type="button" class="is-active" data-court-option data-price="₱600.00" data-package="Court Rentals ₱600"><strong>Court Rentals ₱600</strong><span>Reserve Court Green for casual or private play</span></button></li>
          <li><button type="button" data-court-option data-price="₱500.00" data-package="Lessons ₱500"><strong>Lessons ₱500</strong><span>Beginner-friendly drills and guided class sessions</span></button></li>
          <li><button type="button" data-court-option data-price="₱1,200.00" data-package="Private Coaching ₱1200"><strong>Private Coaching ₱1,200</strong><span>1-on-1 session with a certified coach</span></button></li>
          <li><button type="button" data-court-option data-price="₱800.00" data-package="Training ₱800"><strong>Training ₱800</strong><span>Focused skills training for stronger gameplay</span></button></li>
        </ul>
        <a href="<?= htmlspecialchars($courtBookingHref) ?>" class="btn btn-court-book btn-md" data-court-book data-court="green">Book now</a>
        <p class="home-court__badge">Main standard court</p>
        <a href="resident/courts.php" class="home-court__details">View full details</a>
      </div>
    </div>
  </section>

  <section class="home-court home-court--pink section" id="court-pink">
    <div class="section-inner home-court__grid">
      <div class="home-court__gallery">
        <div class="home-court__thumbs">
          <button type="button" class="is-active" data-court-thumb data-full="assets/img/court/court pink-1.webp" aria-label="Show Court Pink image 1"><img src="assets/img/court/court pink-1.webp" alt="" /></button>
          <button type="button" data-court-thumb data-full="assets/img/court/court pink-2.png" aria-label="Show Court Pink image 2"><img src="assets/img/court/court pink-2.png" alt="" /></button>
          <button type="button" data-court-thumb data-full="assets/img/court/court pink-3.png" aria-label="Show Court Pink image 3"><img src="assets/img/court/court pink-3.png" alt="" /></button>
        </div>
        <div class="home-court__media">
          <img data-court-main src="assets/img/court/court pink-1.webp" alt="Pink indoor pickleball court" />
        </div>
      </div>
      <div class="home-court__content">
        <p class="home-kicker">Court Pink</p>
        <p class="home-price"><span data-court-price>₱400.00</span> <small>/ session</small></p>
        <ul class="home-court__list">
          <li><button type="button" class="is-active" data-court-option data-price="₱400.00" data-package="Court Rental ₱400"><strong>Court Rental ₱400</strong><span>Reserve Court Pink for casual games, family play, and beginner-friendly sessions.</span></button></li>
          <li><button type="button" data-court-option data-price="₱350.00" data-package="Kids Pickleball Class Ages 6-10 ₱350"><strong>Kids Pickleball Class (Ages 6-10) ₱350</strong><span>Fun and engaging introductory session focused on movement, coordination, and basic skills.</span></button></li>
          <li><button type="button" data-court-option data-price="₱350.00" data-package="Youth Development Class Ages 11-17 ₱350"><strong>Youth Development Class (Ages 11-17) ₱350</strong><span>Guided session designed to build confidence, consistency, and match awareness.</span></button></li>
          <li><button type="button" data-court-option data-price="₱500.00" data-package="Parent & Child Session ₱500"><strong>Parent &amp; Child Session ₱500</strong><span>A shared beginner-friendly experience for one parent and one child.</span></button></li>
        </ul>
        <a href="<?= htmlspecialchars($courtBookingHref) ?>" class="btn btn-court-book btn-md" data-court-book data-court="pink">Book now</a>
        <p class="home-court__badge">Slightly smaller but a lot happier</p>
        <a href="resident/courts.php" class="home-court__details">View full details</a>
      </div>
    </div>
  </section>

  <section class="home-testimonial section" data-review-carousel>
    <div class="section-inner">
      <div class="home-testimonial__icon" aria-hidden="true">
        <span></span><span></span><span></span>
      </div>
      <div class="home-testimonial__slides">
        <article class="home-review is-active" data-review>
          <blockquote>Pickleball has completely transformed my weekends. It's easy to learn, fun, and a great way to meet new people. I never thought I’d enjoy a sport this much!</blockquote>
          <p><span>32, DUPR 3.5</span>Jeffrey</p>
        </article>
        <article class="home-review" data-review>
          <blockquote>I love how inclusive pickleball is! Whether you're a beginner or a seasoned player, everyone can join in and have a good time. It's my go-to activity for staying active!</blockquote>
          <p><span>43, Designer</span>Ken</p>
        </article>
        <article class="home-review" data-review>
          <blockquote>Pickleball is simple to learn and fun from the first rally. I joined a social play session and left with new friends and better footwork.</blockquote>
          <p><span>34, Parent</span>Angel</p>
        </article>
        <article class="home-review" data-review>
          <blockquote>Every session feels relaxed but still competitive. The court energy, coaches, and booking flow make it easy to keep coming back.</blockquote>
          <p><span>Architect</span>Olivia</p>
        </article>
      </div>
      <p class="home-review__verified">Verified customer</p>
      <div class="home-testimonial__controls">
        <button type="button" data-review-prev aria-label="Previous review">‹</button>
        <div class="home-testimonial__dots">
          <button type="button" class="is-active" data-review-dot="0" aria-label="Show review 1"></button>
          <button type="button" data-review-dot="1" aria-label="Show review 2"></button>
          <button type="button" data-review-dot="2" aria-label="Show review 3"></button>
          <button type="button" data-review-dot="3" aria-label="Show review 4"></button>
        </div>
        <button type="button" data-review-next aria-label="Next review">›</button>
      </div>
    </div>
  </section>

  <section class="home-rules section" id="pickleball-101">
    <div class="section-inner home-rules__grid">
      <div class="home-rules__intro">
        <p class="home-kicker">To kickstart</p>
        <h2>Pickleball 101</h2>
        <p>Pickle &amp; features an advanced Live Score AI System that tracks and displays your game score in real-time.</p>
        <div class="home-score-card">
          <img src="https://pickleand.club/cdn/shop/files/Screenshot_2025-03-07_at_3.36.29_PM.png?v=1741332999&width=535" alt="Pickleball live score system" />
        </div>
      </div>
      <div class="home-rules__list">
        <?php foreach ($rules as $index => $rule): ?>
          <article class="home-rule <?= $index % 2 ? 'home-rule--green' : 'home-rule--pink' ?>">
            <h3><?= $index + 1 ?>. <?= htmlspecialchars($rule[0]) ?></h3>
            <p><?= htmlspecialchars($rule[1]) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
    </div>
  </section>

  <section class="home-cta section">
    <div class="section-inner">
      <h2>More games.<br>Less coordination.<br><span>Better play.</span></h2>
      <div class="home-cta__actions">
        <a href="resident/courts.php#court-detail" class="btn btn-pink btn-md">Book a court</a>
        <a href="resident/social-play.php" class="btn btn-cta-outline btn-md">Join social play</a>
      </div>
    </div>
  </section>
</main>

<script>
(function(){
  var courtBookingHref = <?= json_encode($courtBookingHref) ?>;
  var rotator = document.querySelector('[data-pickle-rotator]');
  if (rotator) {
    var word = rotator.querySelector('[data-pickle-word]');
    var words = [
      { text: 'Beats', cls: 'is-solid' },
      { text: 'Chill', cls: 'is-outline' },
      { text: 'Events', cls: 'is-block' },
      { text: 'Friends', cls: 'is-underlined' },
      { text: 'Date', cls: 'is-outline is-underlined' }
    ];
    var wordIndex = 0;
    setInterval(function(){
      wordIndex = (wordIndex + 1) % words.length;
      word.className = 'home-intro__word ' + words[wordIndex].cls;
      word.textContent = words[wordIndex].text;
    }, 2000);
  }

  document.querySelectorAll('.home-court').forEach(function(court){
    var main = court.querySelector('[data-court-main]');
    var book = court.querySelector('[data-court-book]');
    var price = court.querySelector('[data-court-price]');
    var courtName = book ? book.getAttribute('data-court') : '';

    court.querySelectorAll('[data-court-thumb]').forEach(function(button){
      button.addEventListener('click', function(){
        court.querySelectorAll('[data-court-thumb]').forEach(function(item){ item.classList.remove('is-active'); });
        button.classList.add('is-active');
        if (main) main.src = button.getAttribute('data-full');
      });
    });

    court.querySelectorAll('[data-court-option]').forEach(function(button){
      button.addEventListener('click', function(){
        court.querySelectorAll('[data-court-option]').forEach(function(item){ item.classList.remove('is-active'); });
        button.classList.add('is-active');
        if (price) price.textContent = button.getAttribute('data-price');
        if (book) {
          book.href = courtBookingHref;
        }
      });
    });
  });

  document.querySelectorAll('[data-review-carousel]').forEach(function(carousel){
    var slides = Array.prototype.slice.call(carousel.querySelectorAll('[data-review]'));
    var dots = Array.prototype.slice.call(carousel.querySelectorAll('[data-review-dot]'));
    var prev = carousel.querySelector('[data-review-prev]');
    var next = carousel.querySelector('[data-review-next]');
    var active = 0;
    var timer;

    function show(index) {
      active = (index + slides.length) % slides.length;
      slides.forEach(function(slide, i){ slide.classList.toggle('is-active', i === active); });
      dots.forEach(function(dot, i){ dot.classList.toggle('is-active', i === active); });
    }

    function restart() {
      window.clearInterval(timer);
      timer = window.setInterval(function(){ show(active + 1); }, 5000);
    }

    dots.forEach(function(dot, i){
      dot.addEventListener('click', function(){ show(i); restart(); });
    });
    if (prev) prev.addEventListener('click', function(){ show(active - 1); restart(); });
    if (next) next.addEventListener('click', function(){ show(active + 1); restart(); });
    restart();
  });
})();
</script>

<script src="assets/js/rules-flashcards.js"></script>
<?php include __DIR__ . '/includes/footer.php'; ?>
