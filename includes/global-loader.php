<?php
require_once __DIR__ . '/paths.php';

$globalLoaderLogo = pickled_asset_url('img/WM-LPink.png');
?>
<div class="global-loader" id="globalLoader" role="status" aria-live="polite" aria-label="Loading Pickled">
  <div class="global-loader__content">
    <img src="<?= htmlspecialchars($globalLoaderLogo) ?>" alt="Pickled" class="global-loader__logo" decoding="async" fetchpriority="high" />
    <div class="global-loader__spinner" aria-hidden="true">
      <span></span>
    </div>
  </div>
</div>
