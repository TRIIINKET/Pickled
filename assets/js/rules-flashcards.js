document.addEventListener('DOMContentLoaded', function () {
  var section = document.querySelector('.home-rules');
  var list = document.querySelector('.home-rules__list');
  var rules = Array.prototype.slice.call(document.querySelectorAll('.home-rule'));
  if (!section || !list || !rules.length) return;

  var desktopQuery = window.matchMedia('(min-width: 981px)');
  var ticking = false;
  var scrollDistance = 1;
  var cardHeight = 148;
  var spreadGap = 164;

  function clamp(value, min, max) {
    return Math.max(min, Math.min(max, value));
  }

  function easeOutCubic(value) {
    return 1 - Math.pow(1 - value, 3);
  }

  function getCardState(index) {
    var stackFromTop = rules.length - 1 - index;

    return {
      spreadX: 0,
      spreadY: index * spreadGap,
      spreadRotate: 0,
      spreadScale: 1,
      stackX: stackFromTop * -8,
      stackY: stackFromTop * -9,
      stackRotate: stackFromTop * -1.2,
      stackScale: 1 - stackFromTop * 0.018
    };
  }

  function configure() {
    if (!desktopQuery.matches) {
      section.classList.remove('is-js-stack');
      section.style.removeProperty('--rules-stack-height');
      list.style.removeProperty('--rules-list-height');
      list.style.removeProperty('--rules-card-height');
      rules.forEach(function (rule) {
        rule.classList.remove('is-front');
        rule.style.removeProperty('opacity');
        rule.style.removeProperty('transform');
        rule.style.removeProperty('z-index');
      });
      return;
    }

    scrollDistance = clamp(window.innerHeight * 1.55, 900, 1380);
    cardHeight = clamp(window.innerHeight * 0.17, 132, 164);
    spreadGap = cardHeight + 16;
    list.style.setProperty('--rules-card-height', Math.round(cardHeight) + 'px');
    list.style.setProperty('--rules-list-height', Math.round(cardHeight + spreadGap * (rules.length - 1) + 48) + 'px');
    section.style.setProperty('--rules-stack-height', Math.round(window.innerHeight + scrollDistance + 260) + 'px');
    section.classList.add('is-js-stack');
    updateFromScroll();
  }

  function updateFromScroll() {
    if (!desktopQuery.matches) return;
    var topOffset = 96;
    var rect = section.getBoundingClientRect();
    var progress = clamp((-rect.top + topOffset) / scrollDistance, 0, 1);
    var stackProgress = easeOutCubic(clamp((progress - 0.08) / 0.92, 0, 1));
    var frontIndex = Math.round(stackProgress * (rules.length - 1));

    rules.forEach(function (rule, index) {
      var state = getCardState(index);
      var x = state.spreadX + (state.stackX - state.spreadX) * stackProgress;
      var y = state.spreadY + (state.stackY - state.spreadY) * stackProgress;
      var rotate = state.spreadRotate + (state.stackRotate - state.spreadRotate) * stackProgress;
      var scale = state.spreadScale + (state.stackScale - state.spreadScale) * stackProgress;
      var distanceFromFront = Math.max(0, frontIndex - index);

      rule.classList.toggle('is-front', index === frontIndex);
      rule.style.zIndex = String(100 - Math.abs(frontIndex - index) * 8 + index);
      rule.style.opacity = String(clamp(1 - distanceFromFront * 0.08, 0.68, 1));
      rule.style.transform = 'translate3d(' + x.toFixed(2) + 'px, ' + y.toFixed(2) + 'px, 0) rotate(' + rotate.toFixed(2) + 'deg) scale(' + scale.toFixed(3) + ')';
    });
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
