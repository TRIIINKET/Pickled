(function () {
  'use strict';

  const storageKey = 'pickledCookieConsent';
  const maxAgeDays = 183;
  const maxAgeMs = maxAgeDays * 24 * 60 * 60 * 1000;

  const banner = document.querySelector('[data-cookie-consent]');
  const modal = document.querySelector('[data-cookie-modal]');
  if (!banner || !modal) return;

  const preferenceToggle = modal.querySelector('[data-cookie-toggle="preferences"]');
  const analyticsToggle = modal.querySelector('[data-cookie-toggle="analytics"]');

  function now() {
    return Date.now();
  }

  function readConsent() {
    try {
      const raw = window.localStorage.getItem(storageKey);
      if (!raw) return null;
      const value = JSON.parse(raw);
      if (!value || !value.savedAt || now() - Number(value.savedAt) > maxAgeMs) {
        window.localStorage.removeItem(storageKey);
        return null;
      }
      return value;
    } catch (error) {
      return null;
    }
  }

  function writeConsent(consent) {
    const payload = {
      status: consent.status,
      essential: true,
      preferences: Boolean(consent.preferences),
      analytics: Boolean(consent.analytics),
      savedAt: now()
    };

    try {
      window.localStorage.setItem(storageKey, JSON.stringify(payload));
    } catch (error) {
      document.cookie = storageKey + '=' + encodeURIComponent(JSON.stringify(payload)) + '; Max-Age=' + (maxAgeDays * 24 * 60 * 60) + '; Path=/; SameSite=Lax';
    }

    return payload;
  }

  function applyToggles(consent) {
    if (preferenceToggle) preferenceToggle.checked = Boolean(consent && consent.preferences);
    if (analyticsToggle) analyticsToggle.checked = Boolean(consent && consent.analytics);
  }

  function hideBanner() {
    banner.hidden = true;
  }

  function showBanner() {
    banner.hidden = false;
  }

  function openModal() {
    applyToggles(readConsent());
    modal.hidden = false;
    modal.setAttribute('aria-hidden', 'false');
    const firstControl = modal.querySelector('button, input');
    if (firstControl) firstControl.focus({ preventScroll: true });
  }

  function closeModal() {
    modal.hidden = true;
    modal.setAttribute('aria-hidden', 'true');
  }

  function acceptAll() {
    writeConsent({ status: 'accepted', preferences: true, analytics: true });
    hideBanner();
    closeModal();
  }

  function decline() {
    writeConsent({ status: 'declined', preferences: false, analytics: false });
    hideBanner();
    closeModal();
  }

  function savePreferences() {
    writeConsent({
      status: 'custom',
      preferences: preferenceToggle ? preferenceToggle.checked : false,
      analytics: analyticsToggle ? analyticsToggle.checked : false
    });
    hideBanner();
    closeModal();
  }

  banner.querySelector('[data-cookie-accept]')?.addEventListener('click', acceptAll);
  banner.querySelector('[data-cookie-decline]')?.addEventListener('click', decline);
  banner.querySelector('[data-cookie-manage]')?.addEventListener('click', openModal);
  modal.querySelector('[data-cookie-save]')?.addEventListener('click', savePreferences);
  modal.querySelector('[data-cookie-accept-modal]')?.addEventListener('click', acceptAll);
  modal.querySelectorAll('[data-cookie-close]').forEach((button) => button.addEventListener('click', closeModal));
  document.querySelectorAll('[data-cookie-preferences]').forEach((button) => button.addEventListener('click', openModal));

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !modal.hidden) closeModal();
  });

  if (readConsent()) {
    hideBanner();
  } else {
    showBanner();
  }
})();
