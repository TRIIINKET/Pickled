(function () {
  function initPasswordToggles() {
    document.querySelectorAll('[data-password-toggle]').forEach(function (button) {
      if (button.dataset.passwordToggleReady === 'true') {
        return;
      }

      var field = button.closest('.login-field');
      var input = field ? field.querySelector('input[type="password"], input[type="text"]') : null;

      if (!input) {
        return;
      }

      button.dataset.passwordToggleReady = 'true';
      button.addEventListener('click', function () {
        var shouldShow = input.type === 'password';

        input.type = shouldShow ? 'text' : 'password';
        button.classList.toggle('is-visible', shouldShow);
        button.setAttribute('aria-pressed', shouldShow ? 'true' : 'false');
        button.setAttribute('aria-label', shouldShow ? 'Hide password' : 'Show password');
      });
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initPasswordToggles);
  } else {
    initPasswordToggles();
  }
})();
