<?php
/** @var array $paymentMethods */
/** @var string $selectedPayment */
?>
<div class="payment-methods" role="radiogroup" aria-label="Payment method">
  <?php foreach ($paymentMethods as $methodKey => $method): ?>
    <label class="payment-option <?= $selectedPayment === $methodKey ? 'is-selected' : '' ?>" data-fee-rate="<?= htmlspecialchars((string) $method['fee_rate']) ?>">
      <input type="radio" name="payment_method" value="<?= htmlspecialchars($methodKey) ?>" <?= $selectedPayment === $methodKey ? 'checked' : '' ?> />
      <span><?= htmlspecialchars($method['label']) ?></span>
      <small><?= ((float) $method['fee_rate']) > 0 ? '+' . ((float) $method['fee_rate'] * 100) . '% fee' : 'No fee' ?></small>
    </label>
  <?php endforeach; ?>
</div>
