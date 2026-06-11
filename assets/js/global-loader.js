(function(){
  var root = document.documentElement;
  var loader = null;
  var asyncCount = 0;
  var manualCount = 0;
  var pageLoading = root.classList.contains('global-loader-active');
  var visibleSince = now();
  var hideTimer = 0;
  var showTimer = 0;
  var minVisibleMs = 650;
  var maxInitialMs = 3600;

  function now(){
    return window.performance && performance.now ? performance.now() : Date.now();
  }

  function getLoader(){
    if (!loader) loader = document.getElementById('globalLoader');
    return loader;
  }

  function activeCount(){
    return asyncCount + manualCount + (pageLoading ? 1 : 0);
  }

  function reveal(options){
    options = options || {};
    window.clearTimeout(hideTimer);
    window.clearTimeout(showTimer);

    var node = getLoader();
    if (!node) return;

    node.hidden = false;
    node.setAttribute('aria-hidden', 'false');
    node.classList.remove('is-hiding');
    root.classList.add('global-loader-enabled', 'global-loader-active');
    if (!visibleSince || options.resetTime) visibleSince = now();
  }

  function show(options){
    options = options || {};
    if (options.delay && !root.classList.contains('global-loader-active')) {
      window.clearTimeout(showTimer);
      showTimer = window.setTimeout(function(){ reveal(options); }, options.delay);
      return;
    }
    reveal(options);
  }

  function conceal(options){
    options = options || {};
    if (activeCount() > 0 && !options.force) return;

    window.clearTimeout(showTimer);
    window.clearTimeout(hideTimer);

    var node = getLoader();
    if (!node) {
      root.classList.remove('global-loader-active', 'global-loader-booting');
      return;
    }

    var elapsed = Math.max(0, now() - visibleSince);
    var remaining = options.immediate ? 0 : Math.max(0, minVisibleMs - elapsed);

    hideTimer = window.setTimeout(function(){
      if (activeCount() > 0 && !options.force) return;
      node.classList.add('is-hiding');
      root.classList.remove('global-loader-active', 'global-loader-booting');
      window.setTimeout(function(){
        if (activeCount() > 0 && !options.force) return;
        node.hidden = true;
        node.setAttribute('aria-hidden', 'true');
        visibleSince = 0;
      }, 460);
    }, remaining);
  }

  function begin(options){
    asyncCount += 1;
    show(options);
    var ended = false;
    return function(){
      if (ended) return;
      ended = true;
      asyncCount = Math.max(0, asyncCount - 1);
      conceal();
    };
  }

  window.showLoader = function(options){
    manualCount += 1;
    show(options);
    return function(){ window.hideLoader(); };
  };

  window.hideLoader = function(options){
    manualCount = Math.max(0, manualCount - 1);
    conceal(options);
  };

  window.withLoader = function(callback, options){
    var end = begin(options);
    try {
      var result = callback();
      if (result && typeof result.then === 'function') {
        return result.then(function(value){
          end();
          return value;
        }, function(error){
          end();
          throw error;
        });
      }
      end();
      return result;
    } catch (error) {
      end();
      throw error;
    }
  };

  function isWriteMethod(method){
    method = String(method || 'GET').toUpperCase();
    return method !== 'GET' && method !== 'HEAD' && method !== 'OPTIONS';
  }

  function requestMethod(resource, init){
    if (init && init.method) return init.method;
    if (resource && typeof resource === 'object' && resource.method) return resource.method;
    return 'GET';
  }

  function requestUrl(resource){
    if (typeof resource === 'string') return resource;
    if (resource && typeof resource === 'object' && resource.url) return resource.url;
    return '';
  }

  function normalizedPath(value){
    try {
      return new URL(value || window.location.href, window.location.href).pathname.toLowerCase();
    } catch (error) {
      return String(value || '').toLowerCase();
    }
  }

  function formActionPath(form){
    return normalizedPath(form.getAttribute('action') || window.location.href);
  }

  function formActionValue(form){
    var actionInput = form.querySelector('input[name="action"]');
    return actionInput ? String(actionInput.value || '').toLowerCase() : '';
  }

  function isSignInForm(form){
    return form.dataset.loader === 'auth'
      || (formActionPath(form).endsWith('/auth/login.php') && formActionValue(form) === 'login')
      || (formActionPath(form).endsWith('/login.php') && formActionValue(form) === 'login');
  }

  function isBookingPath(path){
    path = normalizedPath(path);
    return path.endsWith('/resident/cart.php')
      || path.endsWith('/cart.php')
      || path.endsWith('/resident/booking.php')
      || path.endsWith('/app/api/cart.php');
  }

  function isBookingForm(form){
    if (form.dataset.loader === 'booking') return true;
    if (form.id === 'courtCartForm' || form.id === 'socialCartForm') return true;
    return isBookingPath(formActionPath(form));
  }

  function shouldTrackForm(form){
    return isSignInForm(form) || isBookingForm(form);
  }

  function shouldTrackRequest(resource, init, method){
    if (init && init.loader === true) return true;
    return isWriteMethod(method) && isBookingPath(requestUrl(resource));
  }

  function shouldSkipForm(form){
    if (!form || form.closest('[data-no-loader]') || form.dataset.noLoader === 'true') return true;
    var target = (form.getAttribute('target') || '').toLowerCase();
    if (target && target !== '_self') return true;
    return !shouldTrackForm(form);
  }

  document.addEventListener('submit', function(event){
    var form = event.target;
    if (shouldSkipForm(form)) return;
    window.setTimeout(function(){
      if (!event.defaultPrevented) show({ resetTime: true });
    }, 0);
  });

  if (window.HTMLFormElement && HTMLFormElement.prototype.submit) {
    var nativeSubmit = HTMLFormElement.prototype.submit;
    HTMLFormElement.prototype.submit = function(){
      if (!shouldSkipForm(this)) show({ resetTime: true });
      return nativeSubmit.apply(this, arguments);
    };
  }

  if (window.fetch) {
    var nativeFetch = window.fetch;
    window.fetch = function(){
      var method = requestMethod(arguments[0], arguments[1]);
      var shouldTrack = shouldTrackRequest(arguments[0], arguments[1], method);
      var end = shouldTrack ? begin({ delay: 120 }) : function(){};
      var request;
      try {
        request = nativeFetch.apply(this, arguments);
      } catch (error) {
        end();
        throw error;
      }
      return Promise.resolve(request).then(function(response){
        end();
        return response;
      }, function(error){
        end();
        throw error;
      });
    };
  }

  if (window.XMLHttpRequest) {
    var nativeOpen = XMLHttpRequest.prototype.open;
    var nativeSend = XMLHttpRequest.prototype.send;

    XMLHttpRequest.prototype.open = function(method, url){
      this.__pickledLoaderTrack = isWriteMethod(method) && isBookingPath(url);
      return nativeOpen.apply(this, arguments);
    };

    XMLHttpRequest.prototype.send = function(){
      var xhr = this;
      var end = xhr.__pickledLoaderTrack ? begin({ delay: 120 }) : function(){};
      xhr.addEventListener('loadend', end, { once: true });
      try {
        return nativeSend.apply(xhr, arguments);
      } catch (error) {
        end();
        throw error;
      }
    };
  }

  window.addEventListener('pageshow', function(event){
    if (event.persisted) {
      pageLoading = false;
      asyncCount = 0;
      manualCount = 0;
      conceal({ force: true, immediate: true });
    }
  });

  function finishInitialLoad(){
    pageLoading = false;
    conceal();
  }

  if (document.readyState === 'complete') {
    finishInitialLoad();
  } else {
    window.addEventListener('load', finishInitialLoad, { once: true });
    window.setTimeout(finishInitialLoad, maxInitialMs);
  }
})();
