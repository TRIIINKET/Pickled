document.addEventListener('DOMContentLoaded', function () {
  var rules = Array.prototype.slice.call(document.querySelectorAll('.home-rule'));
  if (!rules.length) return;

  var section = document.querySelector('.home-rules');
  var list = document.querySelector('.home-rules__list');
  if (!section || !list) return;
  var activeIndex = 0;
  var wheelLocked = false;

  function updateActiveRule() {
    rules.forEach(function (rule, index) {
      rule.classList.toggle('is-active', index === activeIndex);
      rule.classList.toggle('is-prev', index < activeIndex);
      rule.classList.toggle('is-next', index > activeIndex);
    });
  }

  function sectionCanTakeWheel(deltaY) {
    var rect = section.getBoundingClientRect();
    var inFocus = rect.top <= 110 && rect.bottom >= window.innerHeight * 0.72;
    var atFirst = activeIndex <= 0;
    var atLast = activeIndex >= rules.length - 1;

    if (!inFocus) return false;
    if (deltaY < 0 && atFirst) return false;
    if (deltaY > 0 && atLast) return false;
    return true;
  }

  window.addEventListener('wheel', function (event) {
    if (!sectionCanTakeWheel(event.deltaY)) return;
    event.preventDefault();
    if (wheelLocked) return;

    activeIndex += event.deltaY > 0 ? 1 : -1;
    activeIndex = Math.max(0, Math.min(rules.length - 1, activeIndex));
    updateActiveRule();

    wheelLocked = true;
    window.setTimeout(function () {
      wheelLocked = false;
    }, 420);
  }, { passive: false });

  updateActiveRule();
  window.addEventListener('resize', updateActiveRule);
  window.addEventListener('load', updateActiveRule);
});
