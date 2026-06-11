document.addEventListener('DOMContentLoaded', function () {
  var section = document.querySelector('.home-rules');
  var list = document.querySelector('.home-rules__list');
  var rules = Array.prototype.slice.call(document.querySelectorAll('.home-rule'));
  if (!section || !list || !rules.length) return;

  var desktopQuery = window.matchMedia('(min-width: 981px)');
  var activeIndex = 0;
  var step = 360;
  var ticking = false;

  function clamp(value, min, max) {
    return Math.max(min, Math.min(max, value));
  }

  function setActive(index) {
    activeIndex = clamp(index, 0, rules.length - 1);
    rules.forEach(function (rule, ruleIndex) {
      rule.classList.toggle('is-active', ruleIndex === activeIndex);
      rule.classList.toggle('is-prev', ruleIndex < activeIndex);
      rule.classList.toggle('is-next', ruleIndex > activeIndex);
    });
  }

  function configure() {
    if (!desktopQuery.matches) {
      section.classList.remove('is-js-stack');
      section.style.removeProperty('--rules-stack-height');
      rules.forEach(function (rule) {
        rule.classList.remove('is-active', 'is-prev', 'is-next');
      });
      return;
    }

    step = clamp(window.innerHeight * 0.42, 260, 430);
    section.style.setProperty('--rules-stack-height', Math.round(window.innerHeight + (rules.length - 1) * step + 220) + 'px');
    section.classList.add('is-js-stack');
    updateFromScroll();
  }

  function updateFromScroll() {
    if (!desktopQuery.matches) return;
    var topOffset = 96;
    var rect = section.getBoundingClientRect();
    var progress = (-rect.top + topOffset) / step;
    setActive(Math.round(progress));
  }

  function requestUpdate() {
    if (ticking) return;
    ticking = true;
    window.requestAnimationFrame(function () {
      updateFromScroll();
      ticking = false;
    });
  }

  configure();
  window.addEventListener('scroll', requestUpdate, { passive: true });
  window.addEventListener('resize', configure);
  window.addEventListener('load', configure);
  if (desktopQuery.addEventListener) {
    desktopQuery.addEventListener('change', configure);
  }
});
