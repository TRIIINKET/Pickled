<?php
// Shared footer.
require_once __DIR__ . '/paths.php';
?>
<footer class="footer">
  <div class="footer-inner">
    <div class="footer-main">
      <section class="footer-about">
        <h2>Pickle &amp; Club</h2>
        <p>Pickled is a vibrant social hub that uses pickleball as a medium to connect people through various dynamic experiences. Going beyond just a sports venue, Pickled aims to spread pickleball culture in a casual and stylish way, making the sport accessible and appealing to everyone.</p>
        <p>The club creates an atmosphere where the fastest-growing sport in the world meets contemporary style and social connection. Whether you're a beginner or experienced player, Pickled offers a fresh approach to enjoying pickleball in a trendy environment where sport, community, and good vibes seamlessly blend together.</p>
        <p>Here, members share fun, laughter, and unforgettable moments on the court, creating memories that extend far beyond the game itself.</p>
      </section>

      <section class="footer-address">
        <h2>Address</h2>
        <p class="footer-address__line">Makati, Metro Manila<br>Open daily for bookings and sessions</p>
        <div class="footer-socials" aria-label="Social links">
          <a href="https://www.facebook.com/" target="_blank" rel="noopener noreferrer" aria-label="Facebook">f</a>
          <a href="https://www.instagram.com/" target="_blank" rel="noopener noreferrer" aria-label="Instagram">
            <svg viewBox="0 0 24 24" aria-hidden="true">
              <rect x="3" y="3" width="18" height="18" rx="5"></rect>
              <circle cx="12" cy="12" r="4"></circle>
              <circle cx="17" cy="7" r="1"></circle>
            </svg>
          </a>
        </div>
        <div class="footer-payments" aria-label="Accepted payment methods">
          <span>GCash</span>
        </div>
      </section>
    </div>

    <div class="footer-bottom">
      <span class="footer-copy"><?= date('Y') ?> Pickled. All rights reserved</span>
      <a href="<?= htmlspecialchars(pickled_frontend_url('resident/social-play.php')) ?>" class="footer-shop">Join social play</a>
      <div class="footer-legal">
        <a href="#privacyModal" class="footer-legal__link" id="privacyPolicyBtn" data-modal-target="privacyModal">Privacy policy</a>
        <a href="#termsModal" class="footer-legal__link" id="termsPolicyBtn" data-modal-target="termsModal">Terms of service</a>
        <a href="#cancellationModal" class="footer-legal__link" id="cancellationPolicyBtn" data-modal-target="cancellationModal">Cancellation policy</a>
      </div>
    </div>
  </div>
</footer>

<div class="cookie-consent" data-cookie-consent hidden>
  <div class="cookie-consent__copy">
    <strong>Cookie Preferences</strong>
    <p>We use cookies to improve your browsing experience, remember preferences, and help us understand how our website is used.</p>
  </div>
  <div class="cookie-consent__actions">
    <button type="button" class="cookie-consent__button cookie-consent__button--primary" data-cookie-accept>Accept All Cookies</button>
    <button type="button" class="cookie-consent__button cookie-consent__button--ghost" data-cookie-manage>Manage Preferences</button>
    <button type="button" class="cookie-consent__button cookie-consent__button--text" data-cookie-decline>Decline</button>
  </div>
</div>

<div class="cookie-modal" data-cookie-modal hidden aria-hidden="true">
  <div class="cookie-modal__overlay" data-cookie-close></div>
  <section class="cookie-modal__panel" role="dialog" aria-modal="true" aria-labelledby="cookieModalTitle">
    <button type="button" class="cookie-modal__close" data-cookie-close aria-label="Close cookie preferences">&times;</button>
    <h2 id="cookieModalTitle">Cookie Preferences</h2>
    <p>Choose which cookies PICKLED can use on this browser. Essential cookies are always enabled because the site needs them for core features.</p>
    <div class="cookie-modal__options">
      <div class="cookie-option">
        <div>
          <strong>Essential Cookies</strong>
          <span>Always enabled</span>
        </div>
        <span class="cookie-option__badge">Required</span>
      </div>
      <label class="cookie-option">
        <div>
          <strong>Preference Cookies</strong>
          <span>Remember choices like display and booking preferences.</span>
        </div>
        <input type="checkbox" data-cookie-toggle="preferences" />
      </label>
      <label class="cookie-option">
        <div>
          <strong>Analytics Cookies</strong>
          <span>Help us understand how visitors use the website.</span>
        </div>
        <input type="checkbox" data-cookie-toggle="analytics" />
      </label>
    </div>
    <div class="cookie-modal__actions">
      <button type="button" class="cookie-consent__button cookie-consent__button--ghost" data-cookie-save>Save Preferences</button>
      <button type="button" class="cookie-consent__button cookie-consent__button--primary" data-cookie-accept-modal>Accept All</button>
    </div>
  </section>
</div>

<div id="privacyModal" class="privacy-modal" style="display: none;">
  <div class="privacy-modal__overlay" id="privacyOverlay"></div>
  <div class="privacy-modal__content">
    <button type="button" class="privacy-modal__close" id="privacyClose">&times;</button>
    <div class="privacy-modal__body">
      <h1>Privacy Policy</h1>
      <p><strong>Last updated: March 10, 2025</strong></p>

      <h2>About This Policy</h2>
      <p>This Privacy Policy describes how PICKLET &amp; Club Limited (the "Site", "we", "us", or "our") collects, uses, and discloses your personal information when you visit, use our services, or make a purchase from us. For purposes of this Policy, the  "you" and "your" refers to any user of the Services, whether you are a customer, website visitor, or another individual whose information we have collected.</p>
      <p>By using and accessing our Services, you agree to the collection, use, and disclosure of your information as described in this Policy. If you do not agree, please do not use or access any of our Services.</p>

      <h2>Changes to This Privacy Policy</h2>
      <p>We may update this Privacy Policy from time to time to reflect changes to our practices or for other operational, legal, or regulatory reasons. We will post any revised policy on the Site, update the "Last updated" date, and take any other steps required by applicable law.</p>

      <h2>How We Collect and Use Your Personal Information</h2>
      <p>We collect personal information about you from a variety of sources to provide our Services. The information we collect and use varies depending on how you interact with us.</p>
      <p>In addition to specific uses outlined below, we may use information we collect to communicate with you, provide or improve our Services, comply with applicable legal obligations, enforce applicable  of service, and protect or defend our Services, our rights, and the rights of our users or others.</p>

      <h2>What Personal Information We Collect</h2>
      <p>The types of personal information we collect depend on how you interact with our Site and Services. Below are the categories of information we collect:</p>

      <h3>Information We Collect Directly From You</h3>
      <p>Information you directly submit to us may include:</p>
      <ul>
        <li><strong>Contact details</strong> — your name, address, phone number, and email</li>
        <li><strong>Order information</strong> — billing address, shipping address, payment confirmation, email, and phone number</li>
        <li><strong>Account information</strong> — username, password, security questions, and other information used for account security</li>
        <li><strong>Shopping information</strong> — items you view, items in your cart, saved items like loyalty points, reviews, referrals or gift cards, or purchases</li>
        <li><strong>Customer support information</strong> — any information you choose to include when communicating with us</li>
      </ul>
      <p>Some features may require you to directly provide certain information. You may elect not to provide this information, but doing so may prevent you from using or accessing these features.</p>

      <h3>Information We Collect About Your Usage</h3>
      <p>We may automatically collect certain information about your interaction with our Services through Usage Data, including device information, browser information, IP address, network connection details, and other information regarding your interaction with us. To collect this, we use cookies, pixels and similar technologies.</p>

      <h2>How We Use Your Personal Information</h2>
      <ul>
        <li><strong>Providing Products and Services</strong> — We use your information to provide our Services, process payments, fulfill orders, send account notifications, arrange shipping, handle returns and exchanges, and manage your account.</li>
        <li><strong>Marketing and Advertising</strong> — We may use your information for marketing and promotional communications by email, text message, or postal mail, and to show you advertisements for our Services on our Site and other websites.</li>
        <li><strong>Security and Fraud Prevention</strong> — We use your information to detect, investigate, and take action regarding possible fraudulent, illegal, or malicious activity.</li>
        <li><strong>Communicating with You and Service Improvement</strong> — We use your information to provide customer support and improve our Services in our legitimate interests to be responsive to you and maintain our business relationship.</li>
      </ul>

      <h2>How We Disclose Personal Information</h2>
      <p>In certain circumstances, we may disclose your personal information to third parties for contract fulfillment, legitimate purposes, and other reasons. Such circumstances may include:</p>
      <ul>
        <li>With vendors and service providers who perform services on our behalf (e.g., IT management, payment processing, data analytics, customer support, cloud storage, fulfillment and shipping)</li>
        <li>With business and marketing partners to provide services and advertise to you</li>
        <li>When you direct, request, or consent to our disclosure of certain information to third parties</li>
        <li>With our affiliates or corporate group for legitimate business interests</li>
        <li>In connection with business transactions such as merger or bankruptcy, to comply with applicable legal obligations</li>
      </ul>

      <h2>Third Party Websites and Links</h2>
      <p>Our Site may provide links to websites or other online platforms operated by third parties. If you follow links to sites not affiliated or controlled by us, you should review their privacy and security policies and other  and conditions. We do not guarantee and are not responsible for the privacy or security of such sites. Information you provide on public or semi-public venues may also be viewable by other users of these third-party platforms without limitation. Our inclusion of such links does not imply endorsement of the content on such platforms or of their owners or operators.</p>

      <h2>Children's Data</h2>
      <p>Our Services are not intended to be used by children, and we do not knowingly collect personal information about children. If you are the parent or guardian of a child who has provided us with personal information, you may contact us to request that it be deleted.</p>
      <p>As of the Effective Date of this Privacy Policy, we do not have actual knowledge that we "share" or "sell" personal information of individuals under 16 years of age.</p>

      <h2>Complaints</h2>
      <p>If you have complaints about how we process your personal information, please contact us using the contact details provided below. If you are not satisfied with our response, depending on where you live, you may have the right to appeal our decision by contacting us or lodge your complaint with your local data protection authority.</p>

      <h2>International Users</h2>
      <p>Please note that we may transfer, store, and process your personal information outside your country of residence. Your personal information is also processed by staff and third party service providers and partners in these countries. If we transfer personal information out of Europe, we will rely on recognized transfer mechanisms like the European Commission's Standard Contractual Clauses or equivalent contracts issued by relevant authorities of the UK, as relevant, unless the transfer is to a country that has been determined to provide an adequate level of protection.</p>

      <h2>Contact Us</h2>
      <p>For questions or inquiries about this Privacy Policy or our data practices, please contact us at the details provided on the Site.</p>
    </div>
  </div>
</div>

<div id="termsModal" class="terms-modal" style="display: none;">
  <div class="terms-modal__overlay" id="termsOverlay"></div>
  <div class="terms-modal__content">
    <button type="button" class="terms-modal__close" id="termsClose">&times;</button>
    <div class="terms-modal__body">
      <h1>Terms of Service</h1>
      <p>Welcome to Pickle &amp; Club! By using our facilities and services or confirming bookings, you agree to the following terms and conditions. We keep them straightforward so everyone can enjoy a safe and fun visit.</p>

      <h2>General</h2>
      <ul>
        <li>“Pickle &amp; Club” or “The Club” refers to our venue where pickleball and community events happen.</li>
        <li>We may update these rules from time to time. Any changes will be posted on our website and at The Club.</li>
        <li>Be respectful, play fair, and follow the house rules so everyone can enjoy the space.</li>
        <li>We are open daily from 10:00 AM to 10:00 PM.</li>
      </ul>

      <h2>Booking, Payments &amp; Refunds (Plan Ahead, Play More!)</h2>
      <ul>
        <li>Book your court through our website. Payments are required before a booking is confirmed.</li>
        <li>Peak hours are weekdays after 6:00 PM, plus all day Saturday, Sunday, public holidays, and special occasions. Other times are off-peak.</li>
        <li>If plans change, you may reschedule your booking using the reschedule link in your confirmation email. Please do so at least 48 hours before your original booking, and pick a new date within 14 days. Manual rescheduling requests cannot be accommodated.</li>
        <li>No-shows forfeit the booking fee, and repeat no-shows may result in booking restrictions.</li>
        <li>Late arrivals do not extend your reserved time. Your session ends at the originally scheduled finish time.</li>
      </ul>

      <h2>Checking In (Hello &amp; Welcome!)</h2>
      <ul>
        <li>Clock in at the reception desk when you arrive.</li>
        <li>Our staff may verify your booking using your phone number to make your check-in quick and easy.</li>
        <li>Lockers are available: free for members and ₱20 for non-members.</li>
      </ul>

      <h2>Court Rules (Let’s Play Fair!)</h2>
      <ul>
        <li>Only six players are allowed per court for a one-hour booking. Overcrowding spoils the fun.</li>
        <li>A two-hour private class may include up to eight players. Adding more hours does not increase the number of participants.</li>
        <li>Non-member guest fee is charged per guest per hour, payable before entry. It is more convenient to pay online at checkout.</li>
        <li>Wear athletic, non-marking shoes. Keep clothing court-friendly—no denim or loose, dangling gear.</li>
        <li>Coaching is allowed only with our registered coaches. If this rule is not followed, you may be asked to leave immediately and refunds will not be provided.</li>
        <li>Treat courts and equipment with care. If damages occur, we may charge for repairs.</li>
        <li>Finish your session on time so the next group can start as scheduled.</li>
        <li>Water is fine, but outside food and drinks are not allowed. Check out our snack menu onsite.</li>
        <li>Rental slots must not be resold or used for revenue without prior approval. Violations may result in immediate removal without refund.</li>
      </ul>

      <h2>Severe Weather Policy</h2>
      <ul>
        <li>If Typhoon Signal No. 8 or above is hoisted, the venue will temporarily close and all bookings will be suspended and subject to rescheduling.</li>
        <li>We will contact affected customers within 48 hours to arrange the best reschedule option.</li>
        <li>The venue will reopen two hours after the signal is officially lowered, and bookings will resume as scheduled.</li>
        <li>Customers are expected to honor their original booking once the venue reopens. No refunds or rescheduling will be provided for missed appointments.</li>
        <li>Customers from outlying islands with valid address proof may be considered for discretionary arrangements.</li>
        <li>If Signal No. 8 is forecast after your booking slot, the session is not reschedulable and you must honor the original booking.</li>
        <li>If the signal is lowered after 6:00 PM, the venue will remain closed for the rest of the day.</li>
        <li>During a Black Rainstorm Warning, the venue may remain open, but classes may be suspended and bookings rescheduled accordingly.</li>
        <li>Reschedule policy for severe weather:
          <ul>
            <li>Rescheduling depends on the original booking conditions and availability.</li>
            <li>Extension or reduction of the original booking hours is not available.</li>
            <li>Pink court bookings cannot be switched to Green court while Green court bookings can be switched to Pink if available.</li>
            <li>Non-peak bookings cannot switch to peak hours, but the reverse is allowed. Price differences will not be refunded.</li>
            <li>Cancellation is unavailable unless special reasons are provided and approved by Pickle &amp; Club Limited.</li>
          </ul>
        </li>
      </ul>

      <h2>Facility Fun &amp; Safety</h2>
      <ul>
        <li>Children under 12 must be accompanied by an adult. Infants under 2 are not allowed unless pre-approved.</li>
        <li>If you bring kids under 6, it is at your own risk.</li>
        <li>We are not responsible for lost or damaged belongings, so please watch your items.</li>
        <li>Use the facility safely and understand that participation is at your own risk.</li>
        <li>In case of emergency, alert our staff immediately.</li>
      </ul>

      <h2>Membership Perks</h2>
      <ul>
        <li>Membership is non-refundable, non-transferable, and specific to you.</li>
        <li>Staff may check your phone number or ID at arrival to confirm your membership.</li>
        <li>Membership gives you access to special perks, but those benefits are not transferable.</li>
      </ul>

      <h2>Events &amp; Cool Stuff</h2>
      <ul>
        <li>Register early for tournaments or events to save your spot.</li>
        <li>If we need to reschedule or cancel an event, we will give you advance notice.</li>
        <li>Each event has its own rules, and you must follow them.</li>
        <li>Standard coaching sessions and private classes are conducted in Cantonese. English-speaking coaches are available on request for an additional charge. Please ask our staff for help.</li>
      </ul>

      <h2>Community Vibes &amp; Conduct</h2>
      <ul>
        <li>Safety first—follow staff instructions so everyone has a great time.</li>
        <li>Be kind and respectful. Harassment or vandalism will not be tolerated.</li>
      </ul>
    </div>
  </div>
</div>

<div id="cancellationModal" class="cancellation-modal" style="display: none;">
  <div class="cancellation-modal__overlay" id="cancellationOverlay"></div>
  <div class="cancellation-modal__content">
    <button type="button" class="cancellation-modal__close" id="cancellationClose">&times;</button>
    <div class="cancellation-modal__body">
      <h1>Cancellation Policy</h1>
      <p>All bookings are guaranteed only after full payment is received. Booking fees are non-refundable unless a valid severe weather event occurs while your slot is active.</p>
      <p>Rescheduling due to typhoon signal no. 8 or Black Rainstorm Warning is at the sole discretion of Pickle &amp; Club. Please refer to our Severe Weather Policy for details.</p>
      <h2>Key Cancellation Details</h2>
      <ul>
        <li>Full payment must be completed before a booking is confirmed.</li>
        <li>Bookings are non-refundable in normal circumstances.</li>
        <li>If signal no. 8 or Black Rainstorm Warning is active during your slot, rescheduling may be allowed.</li>
        <li>Rescheduling decisions are made by Pickle &amp; Club staff and may depend on availability.</li>
        <li>For reschedule arrangements, please follow the instructions provided on our website and in your confirmation email.</li>
      </ul>
      <h2>Weather-Related Rescheduling</h2>
      <p>If the weather forces a cancellation, we will attempt to reschedule your booking based on available courts and future slot availability. The final decision remains with Pickle &amp; Club management.</p>
      <p>Please note: if your booking slot is already underway when the severe weather warning starts, the policy will still apply only to instances where the warning is active during your reserved time.</p>
      <h2>Questions?</h2>
      <p>If you need help or want to confirm your booking terms, contact our support team or check the website for the latest cancellation guidance.</p>
    </div>
  </div>
</div>

<script src="<?= htmlspecialchars(pickled_asset_url('js/privacy-modal.js')) ?>"></script>
<script src="<?= htmlspecialchars(pickled_asset_url('js/cookie-consent.js?v=20260617c')) ?>"></script>
