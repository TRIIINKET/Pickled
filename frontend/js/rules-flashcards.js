document.addEventListener('DOMContentLoaded', function () {
  var rules = Array.prototype.slice.call(document.querySelectorAll('.home-rule'));
  if (!rules.length) return;

  var section = document.querySelector('.home-rules');
  var list = document.querySelector('.home-rules__list');
  if (!section || !list) return;
  var activeIndex = 0;
  var wheelLocked = false;
  var currentLabel = document.querySelector('[data-rule-current]');
  var prevButton = document.querySelector('[data-rule-prev]');
  var nextButton = document.querySelector('[data-rule-next]');
  var startButton = document.querySelector('[data-tutorial-start]');

  function updateActiveRule() {
    rules.forEach(function (rule, index) {
      rule.classList.toggle('is-active', index === activeIndex);
      rule.classList.toggle('is-prev', index < activeIndex);
      rule.classList.toggle('is-next', index > activeIndex);
    });
    if (currentLabel) currentLabel.textContent = String(activeIndex + 1);
  }

  function moveRule(direction) {
    activeIndex += direction;
    activeIndex = Math.max(0, Math.min(rules.length - 1, activeIndex));
    updateActiveRule();
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

    moveRule(event.deltaY > 0 ? 1 : -1);

    wheelLocked = true;
    window.setTimeout(function () {
      wheelLocked = false;
    }, 420);
  }, { passive: false });

  if (prevButton) prevButton.addEventListener('click', function () { moveRule(-1); });
  if (nextButton) nextButton.addEventListener('click', function () { moveRule(1); });
  if (startButton) {
    startButton.addEventListener('click', function () {
      activeIndex = 0;
      updateActiveRule();
    });
  }

  updateActiveRule();
  window.addEventListener('resize', updateActiveRule);
  window.addEventListener('load', updateActiveRule);
});
