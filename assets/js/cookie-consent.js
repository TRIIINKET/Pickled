(function () {
  'use strict';

  const consentKey = 'cookieConsent';
  const timestampKey = 'cookieConsentTimestamp';
  const preferencesKey = 'cookiePreferences';
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
      const status = window.localStorage.getItem(consentKey);
      const savedAt = Number(window.localStorage.getItem(timestampKey));
      if (!status || !savedAt) return null;
      if (now() - savedAt > maxAgeMs) {
        window.localStorage.removeItem(consentKey);
        window.localStorage.removeItem(timestampKey);
        window.localStorage.removeItem(preferencesKey);
        return null;
      }

      const preferences = JSON.parse(window.localStorage.getItem(preferencesKey) || '{}');
      return {
        status,
        essential: true,
        preferences: Boolean(preferences.preferences),
        analytics: Boolean(preferences.analytics),
        savedAt
      };
    } catch (error) {
      return null;
    }
  }

  function writeConsent(consent) {
    const savedAt = now();
    const payload = {
      status: consent.status,
      essential: true,
      preferences: Boolean(consent.preferences),
      analytics: Boolean(consent.analytics),
      savedAt
    };

    try {
      window.localStorage.setItem(consentKey, payload.status);
      window.localStorage.setItem(timestampKey, String(savedAt));
      window.localStorage.setItem(preferencesKey, JSON.stringify({
        essential: true,
        preferences: payload.preferences,
        analytics: payload.analytics
      }));
    } catch (error) {
      return null;
    }

    return payload;
  }

  function applyToggles(consent) {
    if (preferenceToggle) preferenceToggle.checked = Boolean(consent && consent.preferences);
    if (analyticsToggle) analyticsToggle.checked = Boolean(consent && consent.analytics);
  }

  function hideBanner() {
    banner.hidden = true;
    banner.removeAttribute('data-cookie-visible');
  }

  function showBanner() {
    banner.hidden = false;
    banner.setAttribute('data-cookie-visible', 'true');
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

  document.addEventListener('keydown', function (event) {
    if (event.key === 'Escape' && !modal.hidden) closeModal();
  });

  if (readConsent()) {
    hideBanner();
  } else {
    window.requestAnimationFrame(showBanner);
  }
})();
