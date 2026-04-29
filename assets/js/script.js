// === Inline script extracted from index.php ===
document.addEventListener('DOMContentLoaded', function () {
  function initializeGotoLabels() {
    document.querySelectorAll('label[data-goto-url]').forEach(function (label) {
      label.addEventListener('click', function (event) {
        var url = label.dataset.gotoUrl;
        if (!url) return;
        event.preventDefault();
        window.location.href = url.replace(/^\'|\'$/g, '');
      });
    });
  }
  function initializeSelectOnClick() {
    document.querySelectorAll('[data-select-on-click="true"]').forEach(function (element) {
      element.addEventListener('click', function () {
        element.select();
      });
    });
  }
  function initializeOpenFlowIo() {
    var anchor = document.querySelector('#openflowio');
    if (!anchor) return;
    anchor.addEventListener('click', function (event) {
      event.preventDefault();
      if (typeof window.openflowio === 'function') {
        window.openflowio();
      }
    });
  }
  function attachPortableWalletsError() {
    var portableScript = document.querySelector('script[src*="portable-wallets.en.js"]');
    if (!portableScript || typeof window.portableWalletsCleanup !== 'function') return;
    portableScript.addEventListener('error', function (event) {
      window.portableWalletsCleanup(event.target);
    });
  }
  initializeGotoLabels();
  initializeSelectOnClick();
  initializeOpenFlowIo();
  attachPortableWalletsError();
});

window.performance && window.performance.mark && window.performance.mark('shopify.content_for_header.start');

var Shopify = Shopify || {};
Shopify.shop = "h6pafc-61.myshopify.com";
Shopify.locale = "en";
Shopify.currency = {"active":"HKD","rate":"1.0"};
Shopify.country = "HK";
Shopify.theme = {"name":"Energy","id":175452520734,"schema_name":"Energy","schema_version":"2.0.1","theme_store_id":2717,"role":"main"};
Shopify.theme.handle = "null";
Shopify.theme.style = {"id":null,"handle":null};
Shopify.cdnHost = "pickleand.club/cdn";
Shopify.routes = Shopify.routes || {};
Shopify.routes.root = "/";
Shopify.shopJsCdnBaseUrl = "https://cdn.shopify.com/shopifycloud/shop-js";

!function(o){(o.Shopify=o.Shopify||{}).modules=!0}(window);

!function(o){function n(){var o=[];function n(){o.push(Array.prototype.slice.apply(arguments))}return n.q=o,n}var t=o.Shopify=o.Shopify||{};t.loadFeatures=n(),t.autoloadFeatures=n()}(window);

window.ShopifyPay = window.ShopifyPay || {};
  window.ShopifyPay.apiHost = "shop.app\/pay";
  window.ShopifyPay.redirectState = "pending";

(async function() {
await import("//pickleand.club/cdn/shopifycloud/shop-js/modules/v2/loader.init-shop-cart-sync.en.esm.js");

  window.Shopify.SignInWithShop?.initShopCartSync?.({"fedCMEnabled":true,"windoidEnabled":true});
})();


window.Shopify = window.Shopify || {};
  if (!window.Shopify.featureAssets) window.Shopify.featureAssets = {};
  window.Shopify.featureAssets['shop-js'] = {"shop-toast-manager":["modules/v2/loader.shop-toast-manager.en.esm.js"],"shop-button":["modules/v2/loader.shop-button.en.esm.js"],"init-shop-user-recognition":["modules/v2/loader.init-shop-user-recognition.en.esm.js"],"shop-cash-offers":["modules/v2/loader.shop-cash-offers.en.esm.js"],"avatar":["modules/v2/loader.avatar.en.esm.js"],"init-fed-cm":["modules/v2/loader.init-fed-cm.en.esm.js"],"init-windoid":["modules/v2/loader.init-windoid.en.esm.js"],"init-shop-email-lookup-coordinator":["modules/v2/loader.init-shop-email-lookup-coordinator.en.esm.js"],"init-shop-cart-sync":["modules/v2/loader.init-shop-cart-sync.en.esm.js"],"init-customer-accounts-sign-up":["modules/v2/loader.init-customer-accounts-sign-up.en.esm.js"],"shop-cart-sync":["modules/v2/loader.shop-cart-sync.en.esm.js"],"shop-login-button":["modules/v2/loader.shop-login-button.en.esm.js"],"checkout-modal":["modules/v2/loader.checkout-modal.en.esm.js"],"pay-button":["modules/v2/loader.pay-button.en.esm.js"],"init-shop-for-new-customer-accounts":["modules/v2/loader.init-shop-for-new-customer-accounts.en.esm.js"],"shop-user-recognition":["modules/v2/loader.shop-user-recognition.en.esm.js"],"init-customer-accounts":["modules/v2/loader.init-customer-accounts.en.esm.js"],"shop-login":["modules/v2/loader.shop-login.en.esm.js"],"shop-follow-button":["modules/v2/loader.shop-follow-button.en.esm.js"],"lead-capture":["modules/v2/loader.lead-capture.en.esm.js"],"payment-terms":["modules/v2/loader.payment-terms.en.esm.js"]};

(function() {
  var isLoaded = false;
  function asyncLoad() {
    if (isLoaded) return;
    isLoaded = true;
    var urls = ["https:\/\/cdn-app.sealsubscriptions.com\/shopify\/public\/js\/sealsubscriptions.js?shop=h6pafc-61.myshopify.com"];
    for (var i = 0; i < urls.length; i++) {
      var s = document.createElement('script');
      s.type = 'text/javascript';
      s.async = true;
      s.src = urls[i];
      var x = document.getElementsByTagName('script')[0];
      x.parentNode.insertBefore(s, x);
    }
  };
  if(window.attachEvent) {
    window.attachEvent('onload', asyncLoad);
  } else {
    window.addEventListener('load', asyncLoad, false);
  }
})();

var __st={"a":91571978526,"offset":28800,"reqid":"c7c202f1-53bb-45fb-8654-4720a7fe2fa2-1777451021","pageurl":"pickleand.club\/","t":"prospect","u":"9bb7822bdc03","p":"home"};

window.ShopifyPaypalV4VisibilityTracking = true;

!function(){'use strict';const t='contact',e='account',n='new_comment',o=[[t,t],['blogs',n],['comments',n],[t,'customer']],c=[[e,'customer_login'],[e,'guest_login'],[e,'recover_customer_password'],[e,'create_customer']],r=t=>t.map((([t,e])=>`form[action*='/${t}']:not([data-nocaptcha='true']) input[name='form_type'][value='${e}']`)).join(','),a=t=>()=>t?[...document.querySelectorAll(t)].map((t=>t.form)):[];function s(){const t=[...o],e=r(t);return a(e)}const i='password',u='form_key',d=['recaptcha-v3-token','g-recaptcha-response','h-captcha-response',i],f=()=>{try{return window.sessionStorage}catch{return}},m='__shopify_v',_=t=>t.elements[u];function p(t,e,n=!1){try{const o=window.sessionStorage,c=JSON.parse(o.getItem(e)),{data:r}=function(t){const{data:e,action:n}=t;return t[m]||n?{data:e,action:n}:{data:t,action:n}}(c);for(const[e,n]of Object.entries(r))t.elements[e]&&(t.elements[e].value=n);n&&o.removeItem(e)}catch(o){console.error('form repopulation failed',{error:o})}}const l='form_type',E='cptcha';function T(t){t.dataset[E]=!0}const w=window,h=w.document,L='Shopify',v='ce_forms',y='captcha';let A=!1;((t,e)=>{const n=(g='f06e6c50-85a8-45c8-87d0-21a2b65856fe',I='https://cdn.shopify.com/shopifycloud/storefront-forms-hcaptcha/ce_storefront_forms_captcha_hcaptcha.v1.5.2.iife.js',D={infoText:'Protected by hCaptcha',privacyText:'Privacy',termsText:'Terms'},(t,e,n)=>{const o=w[L][v],c=o.bindForm;if(c)return c(t,g,e,D).then(n);var r;o.q.push([[t,g,e,D],n]),r=I,A||(h.body.append(Object.assign(h.createElement('script'),{id:'captcha-provider',async:!0,src:r})),A=!0)});var g,I,D;w[L]=w[L]||{},w[L][v]=w[L][v]||{},w[L][v].q=[],w[L][y]=w[L][y]||{},w[L][y].protect=function(t,e){n(t,void 0,e),T(t)},Object.freeze(w[L][y]),function(t,e,n,w,h,L){const[v,y,A,g]=function(t,e,n){const i=e?o:[],u=t?c:[],d=[...i,...u],f=r(d),m=r(i),_=r(d.filter((([t,e])=>n.includes(e))));return[a(f),a(m),a(_),s()]}(w,h,L),I=t=>{const e=t.target;return e instanceof HTMLFormElement?e:e&&e.form},D=t=>v().includes(t);t.addEventListener('submit',(t=>{const e=I(t);if(!e)return;const n=D(e)&&!e.dataset.hcaptchaBound&&!e.dataset.recaptchaBound,o=_(e),c=g().includes(e)&&(!o||!o.value);(n||c)&&t.preventDefault(),c&&!n&&(function(t){try{if(!f())return;!function(t){const e=f();if(!e)return;const n=_(t);if(!n)return;const o=n.value;o&&e.removeItem(o)}(t);const e=Array.from(Array(32),(()=>Math.random().toString(36)[2])).join('');!function(t,e){_(t)||t.append(Object.assign(document.createElement('input'),{type:'hidden',name:u})),t.elements[u].value=e}(t,e),function(t,e){const n=f();if(!n)return;const o=[...t.querySelectorAll(`input[type='${i}']`)].map((({name:t})=>t)),c=[...d,...o],r={};for(const[a,s]of new FormData(t).entries())c.includes(a)||(r[a]=s);n.setItem(e,JSON.stringify({[m]:1,action:t.action,data:r}))}(t,e)}catch(e){console.error('failed to persist form',e)}}(e),e.submit())}));const S=(t,e)=>{t&&!t.dataset[E]&&(n(t,e.some((e=>e===t))),T(t))};for(const o of['focusin','change'])t.addEventListener(o,(t=>{const e=I(t);D(e)&&S(e,y())}));const B=e.get('form_key'),M=e.get(l),P=B&&M;t.addEventListener('DOMContentLoaded',(()=>{const t=y();if(P)for(const e of t)e.elements[l].value===M&&p(e,B);[...new Set([...A(),...v().filter((t=>'true'===t.dataset.shopifyCaptcha))])].forEach((e=>S(e,t)))}))}(h,new URLSearchParams(w.location.search),n,t,e,['guest_login'])})(!1,!0)}();

var Shopify=Shopify||{};Shopify.PaymentButton=Shopify.PaymentButton||{isStorefrontPortableWallets:!0,init:function(){window.Shopify.PaymentButton.init=function(){};var t=document.createElement("script");t.src="https://pickleand.club/cdn/shopifycloud/portable-wallets/latest/portable-wallets.en.js",t.type="module",document.head.appendChild(t)}};

function portableWalletsHideBuyerConsent(e){var t=document.getElementById("shopify-buyer-consent"),n=document.getElementById("shopify-subscription-policy-button");t&&n&&(t.classList.add("hidden"),t.setAttribute("aria-hidden","true"),n.removeEventListener("click",e))}function portableWalletsShowBuyerConsent(e){var t=document.getElementById("shopify-buyer-consent"),n=document.getElementById("shopify-subscription-policy-button");t&&n&&(t.classList.remove("hidden"),t.removeAttribute("aria-hidden"),n.addEventListener("click",e))}window.Shopify?.PaymentButton&&(window.Shopify.PaymentButton.hideBuyerConsent=portableWalletsHideBuyerConsent,window.Shopify.PaymentButton.showBuyerConsent=portableWalletsShowBuyerConsent);

function portableWalletsCleanup(e){e&&e.src&&console.error("Failed to load portable wallets script "+e.src);var t=document.querySelectorAll("shopify-accelerated-checkout .shopify-payment-button__skeleton, shopify-accelerated-checkout-cart .wallet-cart-button__skeleton"),e=document.getElementById("shopify-buyer-consent");for(let e=0;e<t.length;e++)t[e].remove();e&&e.remove()}function portableWalletsNotLoadedAsModule(e){e instanceof ErrorEvent&&"string"==typeof e.message&&e.message.includes("import.meta")&&"string"==typeof e.filename&&e.filename.includes("portable-wallets")&&(window.removeEventListener("error",portableWalletsNotLoadedAsModule),window.Shopify.PaymentButton.failedToLoad=e,"loading"===document.readyState?document.addEventListener("DOMContentLoaded",window.Shopify.PaymentButton.init):window.Shopify.PaymentButton.init())}window.addEventListener("error",portableWalletsNotLoadedAsModule);

document.addEventListener("DOMContentLoaded", portableWalletsCleanup);

window.performance && window.performance.mark && window.performance.mark('shopify.content_for_header.end');

document.documentElement.className = document.documentElement.className.replace('no-js', 'js');
        if (Shopify.designMode) {
          document.documentElement.classList.add('shopify-design-mode');
        }

!function(f,b,e,v,n,t,s)
{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
n.callMethod.apply(n,arguments):n.queue.push(arguments)};
if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
n.queue=[];t=b.createElement(e);t.async=!0;
t.src=v;s=b.getElementsByTagName(e)[0];
s.parentNode.insertBefore(t,s)}(window, document,'script',
'https://connect.facebook.net/en_US/fbevents.js');
fbq('init', '638188665652068');
fbq('track', 'PageView');

/**	SealSubs loader,version number: 2.0 */
	(function(){
		var loadScript=function(a,b){var c=document.createElement("script");c.setAttribute("defer", "defer");c.type="text/javascript",c.readyState?c.onreadystatechange=function(){("loaded"==c.readyState||"complete"==c.readyState)&&(c.onreadystatechange=null,b())}:c.onload=function(){b()},c.src=a,document.getElementsByTagName("head")[0].appendChild(c)};
		// Set variable to prevent the other loader from requesting the same resources
		window.seal_subs_app_block_loader = true;
		appendScriptUrl('h6pafc-61.myshopify.com');

		// get script url and append timestamp of last change
		function appendScriptUrl(shop) {
			var timeStamp = Math.floor(Date.now() / (1000*1*1));
			var timestampUrl = 'https://app.sealsubscriptions.com/shopify/public/status/shop/'+shop+'.js?'+timeStamp;
			loadScript(timestampUrl, function() {
				// append app script
				if (typeof sealsubscriptions_settings_updated == 'undefined') {
					sealsubscriptions_settings_updated = 'default-by-script';
				}
				
				var locale = '';
				if (typeof window !== 'undefined' && 
					typeof window.Shopify !== 'undefined' && 
					typeof window.Shopify.locale !== 'undefined'
				) {
					locale = window.Shopify.locale;
				}

				var scriptUrl = "https://cdn-app.sealsubscriptions.com/shopify/public/js/sealsubscriptions-main.js?shop="+shop+"&"+sealsubscriptions_settings_updated+`&locale=${locale}`;
				loadScript(scriptUrl, function(){});
			});
		}
	})();

	var SealSubsScriptAppended = true;

if ('CF' in window) {
    window.CF.appEmbedEnabled = true;
  } else {
    window.CF = {
      appEmbedEnabled: true,
    };
  }

  window.CF.editAccountFormId = "";
  window.CF.registrationFormId = "";

function patchRegistrationLinks() {
    const PATCHABLE_LINKS_SELECTOR = 'a[href*="/account/register"]';

    const search = new URLSearchParams(window.location.search);
    const checkoutUrl = search.get('checkout_url');
    const returnUrl = search.get('return_url');

    const redirectUrl = checkoutUrl || returnUrl;
    if (!redirectUrl) return;

    const registrationLinks = Array.from(document.querySelectorAll(PATCHABLE_LINKS_SELECTOR));
    registrationLinks.forEach(link => {
      const url = new URL(link.href);

      url.searchParams.set('return_url', redirectUrl);

      link.href = url.href;
    });
  }

  if (['complete', 'interactive', 'loaded'].includes(document.readyState)) {
    patchRegistrationLinks();
  } else {
    document.addEventListener('DOMContentLoaded', () => patchRegistrationLinks());
  }

// Fixes a problem where both grecaptcha and hcaptcha response fields are included in the /account/login form submission
  // resulting in a 404 on the /challenge page.
  // This is caused by our triggerShopifyRecaptchaLoad function in initialize-forms.liquid.ejs
  // The fix itself just removes the unnecessary g-recaptcha-response input

  function patchLoginGrecaptchaConflict() {
    Array.from(document.querySelectorAll('form')).forEach(form => {
      form.addEventListener('submit', e => {
        const grecaptchaResponse = form.querySelector('[name="g-recaptcha-response"]');
        const hcaptchaResponse = form.querySelector('[name="h-captcha-response"]');

        if (grecaptchaResponse && hcaptchaResponse) {
          // Can't use both. Only keep hcaptcha response field.
          grecaptchaResponse.parentElement.removeChild(grecaptchaResponse);
        }
      })
    })
  }

  if (['complete', 'interactive', 'loaded'].includes(document.readyState)) {
    patchLoginGrecaptchaConflict();
  } else {
    document.addEventListener('DOMContentLoaded', () => patchLoginGrecaptchaConflict());
  }

window.CF.version = "5.2.6";
  window.CF.environment = 
  {
  
  "domain": "h6pafc-61.myshopify.com",
  "baseApiUrl": "https:\/\/app.customerfields.com",
  "captchaSiteKey": "6LfmSIMrAAAAAGuvKd-YCK_-jn2jJ_uP_YOJmZzu",
  "captchaEnabled": true,
  "proxyPath": "\/tools\/customr",
  "countries": [{"name":"Afghanistan","code":"AF"},{"name":"Åland Islands","code":"AX"},{"name":"Albania","code":"AL"},{"name":"Algeria","code":"DZ"},{"name":"Andorra","code":"AD"},{"name":"Angola","code":"AO"},{"name":"Anguilla","code":"AI"},{"name":"Antigua \u0026 Barbuda","code":"AG"},{"name":"Argentina","code":"AR","provinces":[{"name":"Buenos Aires Province","code":"B"},{"name":"Catamarca","code":"K"},{"name":"Chaco","code":"H"},{"name":"Chubut","code":"U"},{"name":"Buenos Aires (Autonomous City)","code":"C"},{"name":"Córdoba","code":"X"},{"name":"Corrientes","code":"W"},{"name":"Entre Ríos","code":"E"},{"name":"Formosa","code":"P"},{"name":"Jujuy","code":"Y"},{"name":"La Pampa","code":"L"},{"name":"La Rioja","code":"F"},{"name":"Mendoza","code":"M"},{"name":"Misiones","code":"N"},{"name":"Neuquén","code":"Q"},{"name":"Río Negro","code":"R"},{"name":"Salta","code":"A"},{"name":"San Juan","code":"J"},{"name":"San Luis","code":"D"},{"name":"Santa Cruz","code":"Z"},{"name":"Santa Fe","code":"S"},{"name":"Santiago del Estero","code":"G"},{"name":"Tierra del Fuego","code":"V"},{"name":"Tucumán","code":"T"}]},{"name":"Armenia","code":"AM"},{"name":"Aruba","code":"AW"},{"name":"Ascension Island","code":"AC"},{"name":"Australia","code":"AU","provinces":[{"name":"Australian Capital Territory","code":"ACT"},{"name":"New South Wales","code":"NSW"},{"name":"Northern Territory","code":"NT"},{"name":"Queensland","code":"QLD"},{"name":"South Australia","code":"SA"},{"name":"Tasmania","code":"TAS"},{"name":"Victoria","code":"VIC"},{"name":"Western Australia","code":"WA"}]},{"name":"Austria","code":"AT"},{"name":"Azerbaijan","code":"AZ"},{"name":"Bahamas","code":"BS"},{"name":"Bahrain","code":"BH"},{"name":"Bangladesh","code":"BD"},{"name":"Barbados","code":"BB"},{"name":"Belarus","code":"BY"},{"name":"Belgium","code":"BE"},{"name":"Belize","code":"BZ"},{"name":"Benin","code":"BJ"},{"name":"Bermuda","code":"BM"},{"name":"Bhutan","code":"BT"},{"name":"Bolivia","code":"BO"},{"name":"Bosnia \u0026 Herzegovina","code":"BA"},{"name":"Botswana","code":"BW"},{"name":"Brazil","code":"BR","provinces":[{"name":"Acre","code":"AC"},{"name":"Alagoas","code":"AL"},{"name":"Amapá","code":"AP"},{"name":"Amazonas","code":"AM"},{"name":"Bahia","code":"BA"},{"name":"Ceará","code":"CE"},{"name":"Federal District","code":"DF"},{"name":"Espírito Santo","code":"ES"},{"name":"Goiás","code":"GO"},{"name":"Maranhão","code":"MA"},{"name":"Mato Grosso","code":"MT"},{"name":"Mato Grosso do Sul","code":"MS"},{"name":"Minas Gerais","code":"MG"},{"name":"Pará","code":"PA"},{"name":"Paraíba","code":"PB"},{"name":"Paraná","code":"PR"},{"name":"Pernambuco","code":"PE"},{"name":"Piauí","code":"PI"},{"name":"Rio Grande do Norte","code":"RN"},{"name":"Rio Grande do Sul","code":"RS"},{"name":"Rio de Janeiro","code":"RJ"},{"name":"Rondônia","code":"RO"},{"name":"Roraima","code":"RR"},{"name":"Santa Catarina","code":"SC"},{"name":"São Paulo","code":"SP"},{"name":"Sergipe","code":"SE"},{"name":"Tocantins","code":"TO"}]},{"name":"British Indian Ocean Territory","code":"IO"},{"name":"British Virgin Islands","code":"VG"},{"name":"Brunei","code":"BN"},{"name":"Bulgaria","code":"BG"},{"name":"Burkina Faso","code":"BF"},{"name":"Burundi","code":"BI"},{"name":"Cambodia","code":"KH"},{"name":"Cameroon","code":"CM"},{"name":"Canada","code":"CA","provinces":[{"name":"Alberta","code":"AB"},{"name":"British Columbia","code":"BC"},{"name":"Manitoba","code":"MB"},{"name":"New Brunswick","code":"NB"},{"name":"Newfoundland and Labrador","code":"NL"},{"name":"Northwest Territories","code":"NT"},{"name":"Nova Scotia","code":"NS"},{"name":"Nunavut","code":"NU"},{"name":"Ontario","code":"ON"},{"name":"Prince Edward Island","code":"PE"},{"name":"Quebec","code":"QC"},{"name":"Saskatchewan","code":"SK"},{"name":"Yukon","code":"YT"}]},{"name":"Cape Verde","code":"CV"},{"name":"Caribbean Netherlands","code":"BQ"},{"name":"Cayman Islands","code":"KY"},{"name":"Central African Republic","code":"CF"},{"name":"Chad","code":"TD"},{"name":"Chile","code":"CL","provinces":[{"name":"Arica y Parinacota","code":"AP"},{"name":"Tarapacá","code":"TA"},{"name":"Antofagasta","code":"AN"},{"name":"Atacama","code":"AT"},{"name":"Coquimbo","code":"CO"},{"name":"Valparaíso","code":"VS"},{"name":"Santiago Metropolitan","code":"RM"},{"name":"Libertador General Bernardo O’Higgins","code":"LI"},{"name":"Maule","code":"ML"},{"name":"Ñuble","code":"NB"},{"name":"Bío Bío","code":"BI"},{"name":"Araucanía","code":"AR"},{"name":"Los Ríos","code":"LR"},{"name":"Los Lagos","code":"LL"},{"name":"Aysén","code":"AI"},{"name":"Magallanes Region","code":"MA"}]},{"name":"China","code":"CN","provinces":[{"name":"Anhui","code":"AH"},{"name":"Beijing","code":"BJ"},{"name":"Chongqing","code":"CQ"},{"name":"Fujian","code":"FJ"},{"name":"Gansu","code":"GS"},{"name":"Guangdong","code":"GD"},{"name":"Guangxi","code":"GX"},{"name":"Guizhou","code":"GZ"},{"name":"Hainan","code":"HI"},{"name":"Hebei","code":"HE"},{"name":"Heilongjiang","code":"HL"},{"name":"Henan","code":"HA"},{"name":"Hubei","code":"HB"},{"name":"Hunan","code":"HN"},{"name":"Inner Mongolia","code":"NM"},{"name":"Jiangsu","code":"JS"},{"name":"Jiangxi","code":"JX"},{"name":"Jilin","code":"JL"},{"name":"Liaoning","code":"LN"},{"name":"Ningxia","code":"NX"},{"name":"Qinghai","code":"QH"},{"name":"Shaanxi","code":"SN"},{"name":"Shandong","code":"SD"},{"name":"Shanghai","code":"SH"},{"name":"Shanxi","code":"SX"},{"name":"Sichuan","code":"SC"},{"name":"Tianjin","code":"TJ"},{"name":"Xinjiang","code":"XJ"},{"name":"Tibet","code":"YZ"},{"name":"Yunnan","code":"YN"},{"name":"Zhejiang","code":"ZJ"}]},{"name":"Christmas Island","code":"CX"},{"name":"Cocos (Keeling) Islands","code":"CC"},{"name":"Colombia","code":"CO","provinces":[{"name":"Capital District","code":"DC"},{"name":"Amazonas","code":"AMA"},{"name":"Antioquia","code":"ANT"},{"name":"Arauca","code":"ARA"},{"name":"Atlántico","code":"ATL"},{"name":"Bolívar","code":"BOL"},{"name":"Boyacá","code":"BOY"},{"name":"Caldas","code":"CAL"},{"name":"Caquetá","code":"CAQ"},{"name":"Casanare","code":"CAS"},{"name":"Cauca","code":"CAU"},{"name":"Cesar","code":"CES"},{"name":"Chocó","code":"CHO"},{"name":"Córdoba","code":"COR"},{"name":"Cundinamarca","code":"CUN"},{"name":"Guainía","code":"GUA"},{"name":"Guaviare","code":"GUV"},{"name":"Huila","code":"HUI"},{"name":"La Guajira","code":"LAG"},{"name":"Magdalena","code":"MAG"},{"name":"Meta","code":"MET"},{"name":"Nariño","code":"NAR"},{"name":"Norte de Santander","code":"NSA"},{"name":"Putumayo","code":"PUT"},{"name":"Quindío","code":"QUI"},{"name":"Risaralda","code":"RIS"},{"name":"San Andrés \u0026 Providencia","code":"SAP"},{"name":"Santander","code":"SAN"},{"name":"Sucre","code":"SUC"},{"name":"Tolima","code":"TOL"},{"name":"Valle del Cauca","code":"VAC"},{"name":"Vaupés","code":"VAU"},{"name":"Vichada","code":"VID"}]},{"name":"Comoros","code":"KM"},{"name":"Congo - Brazzaville","code":"CG"},{"name":"Congo - Kinshasa","code":"CD"},{"name":"Cook Islands","code":"CK"},{"name":"Costa Rica","code":"CR","provinces":[{"name":"Alajuela","code":"CR-A"},{"name":"Cartago","code":"CR-C"},{"name":"Guanacaste","code":"CR-G"},{"name":"Heredia","code":"CR-H"},{"name":"Limón","code":"CR-L"},{"name":"Puntarenas","code":"CR-P"},{"name":"San José","code":"CR-SJ"}]},{"name":"Croatia","code":"HR"},{"name":"Curaçao","code":"CW"},{"name":"Cyprus","code":"CY"},{"name":"Czechia","code":"CZ"},{"name":"Côte d’Ivoire","code":"CI"},{"name":"Denmark","code":"DK"},{"name":"Djibouti","code":"DJ"},{"name":"Dominica","code":"DM"},{"name":"Dominican Republic","code":"DO"},{"name":"Ecuador","code":"EC"},{"name":"Egypt","code":"EG","provinces":[{"name":"6th of October","code":"SU"},{"name":"Al Sharqia","code":"SHR"},{"name":"Alexandria","code":"ALX"},{"name":"Aswan","code":"ASN"},{"name":"Asyut","code":"AST"},{"name":"Beheira","code":"BH"},{"name":"Beni Suef","code":"BNS"},{"name":"Cairo","code":"C"},{"name":"Dakahlia","code":"DK"},{"name":"Damietta","code":"DT"},{"name":"Faiyum","code":"FYM"},{"name":"Gharbia","code":"GH"},{"name":"Giza","code":"GZ"},{"name":"Helwan","code":"HU"},{"name":"Ismailia","code":"IS"},{"name":"Kafr el-Sheikh","code":"KFS"},{"name":"Luxor","code":"LX"},{"name":"Matrouh","code":"MT"},{"name":"Minya","code":"MN"},{"name":"Monufia","code":"MNF"},{"name":"New Valley","code":"WAD"},{"name":"North Sinai","code":"SIN"},{"name":"Port Said","code":"PTS"},{"name":"Qalyubia","code":"KB"},{"name":"Qena","code":"KN"},{"name":"Red Sea","code":"BA"},{"name":"Sohag","code":"SHG"},{"name":"South Sinai","code":"JS"},{"name":"Suez","code":"SUZ"}]},{"name":"El Salvador","code":"SV","provinces":[{"name":"Ahuachapán","code":"SV-AH"},{"name":"Cabañas","code":"SV-CA"},{"name":"Chalatenango","code":"SV-CH"},{"name":"Cuscatlán","code":"SV-CU"},{"name":"La Libertad","code":"SV-LI"},{"name":"La Paz","code":"SV-PA"},{"name":"La Unión","code":"SV-UN"},{"name":"Morazán","code":"SV-MO"},{"name":"San Miguel","code":"SV-SM"},{"name":"San Salvador","code":"SV-SS"},{"name":"San Vicente","code":"SV-SV"},{"name":"Santa Ana","code":"SV-SA"},{"name":"Sonsonate","code":"SV-SO"},{"name":"Usulután","code":"SV-US"}]},{"name":"Equatorial Guinea","code":"GQ"},{"name":"Eritrea","code":"ER"},{"name":"Estonia","code":"EE"},{"name":"Eswatini","code":"SZ"},{"name":"Ethiopia","code":"ET"},{"name":"Falkland Islands","code":"FK"},{"name":"Faroe Islands","code":"FO"},{"name":"Fiji","code":"FJ"},{"name":"Finland","code":"FI"},{"name":"France","code":"FR"},{"name":"French Guiana","code":"GF"},{"name":"French Polynesia","code":"PF"},{"name":"French Southern Territories","code":"TF"},{"name":"Gabon","code":"GA"},{"name":"Gambia","code":"GM"},{"name":"Georgia","code":"GE"},{"name":"Germany","code":"DE"},{"name":"Ghana","code":"GH"},{"name":"Gibraltar","code":"GI"},{"name":"Greece","code":"GR"},{"name":"Greenland","code":"GL"},{"name":"Grenada","code":"GD"},{"name":"Guadeloupe","code":"GP"},{"name":"Guatemala","code":"GT","provinces":[{"name":"Alta Verapaz","code":"AVE"},{"name":"Baja Verapaz","code":"BVE"},{"name":"Chimaltenango","code":"CMT"},{"name":"Chiquimula","code":"CQM"},{"name":"El Progreso","code":"EPR"},{"name":"Escuintla","code":"ESC"},{"name":"Guatemala","code":"GUA"},{"name":"Huehuetenango","code":"HUE"},{"name":"Izabal","code":"IZA"},{"name":"Jalapa","code":"JAL"},{"name":"Jutiapa","code":"JUT"},{"name":"Petén","code":"PET"},{"name":"Quetzaltenango","code":"QUE"},{"name":"Quiché","code":"QUI"},{"name":"Retalhuleu","code":"RET"},{"name":"Sacatepéquez","code":"SAC"},{"name":"San Marcos","code":"SMA"},{"name":"Santa Rosa","code":"SRO"},{"name":"Sololá","code":"SOL"},{"name":"Suchitepéquez","code":"SUC"},{"name":"Totonicapán","code":"TOT"},{"name":"Zacapa","code":"ZAC"}]},{"name":"Guernsey","code":"GG"},{"name":"Guinea","code":"GN"},{"name":"Guinea-Bissau","code":"GW"},{"name":"Guyana","code":"GY"},{"name":"Haiti","code":"HT"},{"name":"Honduras","code":"HN"},{"name":"Hong Kong SAR","code":"HK","provinces":[{"name":"Hong Kong Island","code":"HK"},{"name":"Kowloon","code":"KL"},{"name":"New Territories","code":"NT"}]},{"name":"Hungary","code":"HU"},{"name":"Iceland","code":"IS"},{"name":"India","code":"IN","provinces":[{"name":"Andaman and Nicobar Islands","code":"AN"},{"name":"Andhra Pradesh","code":"AP"},{"name":"Arunachal Pradesh","code":"AR"},{"name":"Assam","code":"AS"},{"name":"Bihar","code":"BR"},{"name":"Chandigarh","code":"CH"},{"name":"Chhattisgarh","code":"CG"},{"name":"Dadra and Nagar Haveli","code":"DN"},{"name":"Daman and Diu","code":"DD"},{"name":"Delhi","code":"DL"},{"name":"Goa","code":"GA"},{"name":"Gujarat","code":"GJ"},{"name":"Haryana","code":"HR"},{"name":"Himachal Pradesh","code":"HP"},{"name":"Jammu and Kashmir","code":"JK"},{"name":"Jharkhand","code":"JH"},{"name":"Karnataka","code":"KA"},{"name":"Kerala","code":"KL"},{"name":"Ladakh","code":"LA"},{"name":"Lakshadweep","code":"LD"},{"name":"Madhya Pradesh","code":"MP"},{"name":"Maharashtra","code":"MH"},{"name":"Manipur","code":"MN"},{"name":"Meghalaya","code":"ML"},{"name":"Mizoram","code":"MZ"},{"name":"Nagaland","code":"NL"},{"name":"Odisha","code":"OR"},{"name":"Puducherry","code":"PY"},{"name":"Punjab","code":"PB"},{"name":"Rajasthan","code":"RJ"},{"name":"Sikkim","code":"SK"},{"name":"Tamil Nadu","code":"TN"},{"name":"Telangana","code":"TS"},{"name":"Tripura","code":"TR"},{"name":"Uttar Pradesh","code":"UP"},{"name":"Uttarakhand","code":"UK"},{"name":"West Bengal","code":"WB"}]},{"name":"Indonesia","code":"ID","provinces":[{"name":"Aceh","code":"AC"},{"name":"Bali","code":"BA"},{"name":"Bangka–Belitung Islands","code":"BB"},{"name":"Banten","code":"BT"},{"name":"Bengkulu","code":"BE"},{"name":"Gorontalo","code":"GO"},{"name":"Jakarta","code":"JK"},{"name":"Jambi","code":"JA"},{"name":"West Java","code":"JB"},{"name":"Central Java","code":"JT"},{"name":"East Java","code":"JI"},{"name":"West Kalimantan","code":"KB"},{"name":"South Kalimantan","code":"KS"},{"name":"Central Kalimantan","code":"KT"},{"name":"East Kalimantan","code":"KI"},{"name":"North Kalimantan","code":"KU"},{"name":"Riau Islands","code":"KR"},{"name":"Lampung","code":"LA"},{"name":"Maluku","code":"MA"},{"name":"North Maluku","code":"MU"},{"name":"North Sumatra","code":"SU"},{"name":"West Nusa Tenggara","code":"NB"},{"name":"East Nusa Tenggara","code":"NT"},{"name":"Papua","code":"PA"},{"name":"West Papua","code":"PB"},{"name":"Riau","code":"RI"},{"name":"South Sumatra","code":"SS"},{"name":"West Sulawesi","code":"SR"},{"name":"South Sulawesi","code":"SN"},{"name":"Central Sulawesi","code":"ST"},{"name":"Southeast Sulawesi","code":"SG"},{"name":"North Sulawesi","code":"SA"},{"name":"West Sumatra","code":"SB"},{"name":"Yogyakarta","code":"YO"}]},{"name":"Iraq","code":"IQ"},{"name":"Ireland","code":"IE","provinces":[{"name":"Carlow","code":"CW"},{"name":"Cavan","code":"CN"},{"name":"Clare","code":"CE"},{"name":"Cork","code":"CO"},{"name":"Donegal","code":"DL"},{"name":"Dublin","code":"D"},{"name":"Galway","code":"G"},{"name":"Kerry","code":"KY"},{"name":"Kildare","code":"KE"},{"name":"Kilkenny","code":"KK"},{"name":"Laois","code":"LS"},{"name":"Leitrim","code":"LM"},{"name":"Limerick","code":"LK"},{"name":"Longford","code":"LD"},{"name":"Louth","code":"LH"},{"name":"Mayo","code":"MO"},{"name":"Meath","code":"MH"},{"name":"Monaghan","code":"MN"},{"name":"Offaly","code":"OY"},{"name":"Roscommon","code":"RN"},{"name":"Sligo","code":"SO"},{"name":"Tipperary","code":"TA"},{"name":"Waterford","code":"WD"},{"name":"Westmeath","code":"WH"},{"name":"Wexford","code":"WX"},{"name":"Wicklow","code":"WW"}]},{"name":"Isle of Man","code":"IM"},{"name":"Israel","code":"IL"},{"name":"Italy","code":"IT","provinces":[{"name":"Agrigento","code":"AG"},{"name":"Alessandria","code":"AL"},{"name":"Ancona","code":"AN"},{"name":"Aosta Valley","code":"AO"},{"name":"Arezzo","code":"AR"},{"name":"Ascoli Piceno","code":"AP"},{"name":"Asti","code":"AT"},{"name":"Avellino","code":"AV"},{"name":"Bari","code":"BA"},{"name":"Barletta-Andria-Trani","code":"BT"},{"name":"Belluno","code":"BL"},{"name":"Benevento","code":"BN"},{"name":"Bergamo","code":"BG"},{"name":"Biella","code":"BI"},{"name":"Bologna","code":"BO"},{"name":"South Tyrol","code":"BZ"},{"name":"Brescia","code":"BS"},{"name":"Brindisi","code":"BR"},{"name":"Cagliari","code":"CA"},{"name":"Caltanissetta","code":"CL"},{"name":"Campobasso","code":"CB"},{"name":"Carbonia-Iglesias","code":"CI"},{"name":"Caserta","code":"CE"},{"name":"Catania","code":"CT"},{"name":"Catanzaro","code":"CZ"},{"name":"Chieti","code":"CH"},{"name":"Como","code":"CO"},{"name":"Cosenza","code":"CS"},{"name":"Cremona","code":"CR"},{"name":"Crotone","code":"KR"},{"name":"Cuneo","code":"CN"},{"name":"Enna","code":"EN"},{"name":"Fermo","code":"FM"},{"name":"Ferrara","code":"FE"},{"name":"Florence","code":"FI"},{"name":"Foggia","code":"FG"},{"name":"Forlì-Cesena","code":"FC"},{"name":"Frosinone","code":"FR"},{"name":"Genoa","code":"GE"},{"name":"Gorizia","code":"GO"},{"name":"Grosseto","code":"GR"},{"name":"Imperia","code":"IM"},{"name":"Isernia","code":"IS"},{"name":"L’Aquila","code":"AQ"},{"name":"La Spezia","code":"SP"},{"name":"Latina","code":"LT"},{"name":"Lecce","code":"LE"},{"name":"Lecco","code":"LC"},{"name":"Livorno","code":"LI"},{"name":"Lodi","code":"LO"},{"name":"Lucca","code":"LU"},{"name":"Macerata","code":"MC"},{"name":"Mantua","code":"MN"},{"name":"Massa and Carrara","code":"MS"},{"name":"Matera","code":"MT"},{"name":"Medio Campidano","code":"VS"},{"name":"Messina","code":"ME"},{"name":"Milan","code":"MI"},{"name":"Modena","code":"MO"},{"name":"Monza and Brianza","code":"MB"},{"name":"Naples","code":"NA"},{"name":"Novara","code":"NO"},{"name":"Nuoro","code":"NU"},{"name":"Ogliastra","code":"OG"},{"name":"Olbia-Tempio","code":"OT"},{"name":"Oristano","code":"OR"},{"name":"Padua","code":"PD"},{"name":"Palermo","code":"PA"},{"name":"Parma","code":"PR"},{"name":"Pavia","code":"PV"},{"name":"Perugia","code":"PG"},{"name":"Pesaro and Urbino","code":"PU"},{"name":"Pescara","code":"PE"},{"name":"Piacenza","code":"PC"},{"name":"Pisa","code":"PI"},{"name":"Pistoia","code":"PT"},{"name":"Pordenone","code":"PN"},{"name":"Potenza","code":"PZ"},{"name":"Prato","code":"PO"},{"name":"Ragusa","code":"RG"},{"name":"Ravenna","code":"RA"},{"name":"Reggio Calabria","code":"RC"},{"name":"Reggio Emilia","code":"RE"},{"name":"Rieti","code":"RI"},{"name":"Rimini","code":"RN"},{"name":"Rome","code":"RM"},{"name":"Rovigo","code":"RO"},{"name":"Salerno","code":"SA"},{"name":"Sassari","code":"SS"},{"name":"Savona","code":"SV"},{"name":"Siena","code":"SI"},{"name":"Syracuse","code":"SR"},{"name":"Sondrio","code":"SO"},{"name":"Taranto","code":"TA"},{"name":"Teramo","code":"TE"},{"name":"Terni","code":"TR"},{"name":"Turin","code":"TO"},{"name":"Trapani","code":"TP"},{"name":"Trentino","code":"TN"},{"name":"Treviso","code":"TV"},{"name":"Trieste","code":"TS"},{"name":"Udine","code":"UD"},{"name":"Varese","code":"VA"},{"name":"Venice","code":"VE"},{"name":"Verbano-Cusio-Ossola","code":"VB"},{"name":"Vercelli","code":"VC"},{"name":"Verona","code":"VR"},{"name":"Vibo Valentia","code":"VV"},{"name":"Vicenza","code":"VI"},{"name":"Viterbo","code":"VT"}]},{"name":"Jamaica","code":"JM"},{"name":"Japan","code":"JP","provinces":[{"name":"Hokkaido","code":"JP-01"},{"name":"Aomori","code":"JP-02"},{"name":"Iwate","code":"JP-03"},{"name":"Miyagi","code":"JP-04"},{"name":"Akita","code":"JP-05"},{"name":"Yamagata","code":"JP-06"},{"name":"Fukushima","code":"JP-07"},{"name":"Ibaraki","code":"JP-08"},{"name":"Tochigi","code":"JP-09"},{"name":"Gunma","code":"JP-10"},{"name":"Saitama","code":"JP-11"},{"name":"Chiba","code":"JP-12"},{"name":"Tokyo","code":"JP-13"},{"name":"Kanagawa","code":"JP-14"},{"name":"Niigata","code":"JP-15"},{"name":"Toyama","code":"JP-16"},{"name":"Ishikawa","code":"JP-17"},{"name":"Fukui","code":"JP-18"},{"name":"Yamanashi","code":"JP-19"},{"name":"Nagano","code":"JP-20"},{"name":"Gifu","code":"JP-21"},{"name":"Shizuoka","code":"JP-22"},{"name":"Aichi","code":"JP-23"},{"name":"Mie","code":"JP-24"},{"name":"Shiga","code":"JP-25"},{"name":"Kyoto","code":"JP-26"},{"name":"Osaka","code":"JP-27"},{"name":"Hyogo","code":"JP-28"},{"name":"Nara","code":"JP-29"},{"name":"Wakayama","code":"JP-30"},{"name":"Tottori","code":"JP-31"},{"name":"Shimane","code":"JP-32"},{"name":"Okayama","code":"JP-33"},{"name":"Hiroshima","code":"JP-34"},{"name":"Yamaguchi","code":"JP-35"},{"name":"Tokushima","code":"JP-36"},{"name":"Kagawa","code":"JP-37"},{"name":"Ehime","code":"JP-38"},{"name":"Kochi","code":"JP-39"},{"name":"Fukuoka","code":"JP-40"},{"name":"Saga","code":"JP-41"},{"name":"Nagasaki","code":"JP-42"},{"name":"Kumamoto","code":"JP-43"},{"name":"Oita","code":"JP-44"},{"name":"Miyazaki","code":"JP-45"},{"name":"Kagoshima","code":"JP-46"},{"name":"Okinawa","code":"JP-47"}]},{"name":"Jersey","code":"JE"},{"name":"Jordan","code":"JO"},{"name":"Kazakhstan","code":"KZ"},{"name":"Kenya","code":"KE"},{"name":"Kiribati","code":"KI"},{"name":"Kosovo","code":"XK"},{"name":"Kuwait","code":"KW","provinces":[{"name":"Al Ahmadi","code":"KW-AH"},{"name":"Al Asimah","code":"KW-KU"},{"name":"Al Farwaniyah","code":"KW-FA"},{"name":"Al Jahra","code":"KW-JA"},{"name":"Hawalli","code":"KW-HA"},{"name":"Mubarak Al-Kabeer","code":"KW-MU"}]},{"name":"Kyrgyzstan","code":"KG"},{"name":"Laos","code":"LA"},{"name":"Latvia","code":"LV"},{"name":"Lebanon","code":"LB"},{"name":"Lesotho","code":"LS"},{"name":"Liberia","code":"LR"},{"name":"Libya","code":"LY"},{"name":"Liechtenstein","code":"LI"},{"name":"Lithuania","code":"LT"},{"name":"Luxembourg","code":"LU"},{"name":"Macao SAR","code":"MO"},{"name":"Madagascar","code":"MG"},{"name":"Malawi","code":"MW"},{"name":"Malaysia","code":"MY","provinces":[{"name":"Johor","code":"JHR"},{"name":"Kedah","code":"KDH"},{"name":"Kelantan","code":"KTN"},{"name":"Kuala Lumpur","code":"KUL"},{"name":"Labuan","code":"LBN"},{"name":"Malacca","code":"MLK"},{"name":"Negeri Sembilan","code":"NSN"},{"name":"Pahang","code":"PHG"},{"name":"Penang","code":"PNG"},{"name":"Perak","code":"PRK"},{"name":"Perlis","code":"PLS"},{"name":"Putrajaya","code":"PJY"},{"name":"Sabah","code":"SBH"},{"name":"Sarawak","code":"SWK"},{"name":"Selangor","code":"SGR"},{"name":"Terengganu","code":"TRG"}]},{"name":"Maldives","code":"MV"},{"name":"Mali","code":"ML"},{"name":"Malta","code":"MT"},{"name":"Martinique","code":"MQ"},{"name":"Mauritania","code":"MR"},{"name":"Mauritius","code":"MU"},{"name":"Mayotte","code":"YT"},{"name":"Mexico","code":"MX","provinces":[{"name":"Aguascalientes","code":"AGS"},{"name":"Baja California","code":"BC"},{"name":"Baja California Sur","code":"BCS"},{"name":"Campeche","code":"CAMP"},{"name":"Chiapas","code":"CHIS"},{"name":"Chihuahua","code":"CHIH"},{"name":"Ciudad de Mexico","code":"DF"},{"name":"Coahuila","code":"COAH"},{"name":"Colima","code":"COL"},{"name":"Durango","code":"DGO"},{"name":"Guanajuato","code":"GTO"},{"name":"Guerrero","code":"GRO"},{"name":"Hidalgo","code":"HGO"},{"name":"Jalisco","code":"JAL"},{"name":"Mexico State","code":"MEX"},{"name":"Michoacán","code":"MICH"},{"name":"Morelos","code":"MOR"},{"name":"Nayarit","code":"NAY"},{"name":"Nuevo León","code":"NL"},{"name":"Oaxaca","code":"OAX"},{"name":"Puebla","code":"PUE"},{"name":"Querétaro","code":"QRO"},{"name":"Quintana Roo","code":"Q ROO"},{"name":"San Luis Potosí","code":"SLP"},{"name":"Sinaloa","code":"SIN"},{"name":"Sonora","code":"SON"},{"name":"Tabasco","code":"TAB"},{"name":"Tamaulipas","code":"TAMPS"},{"name":"Tlaxcala","code":"TLAX"},{"name":"Veracruz","code":"VER"},{"name":"Yucatán","code":"YUC"},{"name":"Zacatecas","code":"ZAC"}]},{"name":"Moldova","code":"MD"},{"name":"Monaco","code":"MC"},{"name":"Mongolia","code":"MN"},{"name":"Montenegro","code":"ME"},{"name":"Montserrat","code":"MS"},{"name":"Morocco","code":"MA"},{"name":"Mozambique","code":"MZ"},{"name":"Myanmar (Burma)","code":"MM"},{"name":"Namibia","code":"NA"},{"name":"Nauru","code":"NR"},{"name":"Nepal","code":"NP"},{"name":"Netherlands","code":"NL"},{"name":"New Caledonia","code":"NC"},{"name":"New Zealand","code":"NZ","provinces":[{"name":"Auckland","code":"AUK"},{"name":"Bay of Plenty","code":"BOP"},{"name":"Canterbury","code":"CAN"},{"name":"Chatham Islands","code":"CIT"},{"name":"Gisborne","code":"GIS"},{"name":"Hawke’s Bay","code":"HKB"},{"name":"Manawatū-Whanganui","code":"MWT"},{"name":"Marlborough","code":"MBH"},{"name":"Nelson","code":"NSN"},{"name":"Northland","code":"NTL"},{"name":"Otago","code":"OTA"},{"name":"Southland","code":"STL"},{"name":"Taranaki","code":"TKI"},{"name":"Tasman","code":"TAS"},{"name":"Waikato","code":"WKO"},{"name":"Wellington","code":"WGN"},{"name":"West Coast","code":"WTC"}]},{"name":"Nicaragua","code":"NI"},{"name":"Niger","code":"NE"},{"name":"Nigeria","code":"NG","provinces":[{"name":"Abia","code":"AB"},{"name":"Federal Capital Territory","code":"FC"},{"name":"Adamawa","code":"AD"},{"name":"Akwa Ibom","code":"AK"},{"name":"Anambra","code":"AN"},{"name":"Bauchi","code":"BA"},{"name":"Bayelsa","code":"BY"},{"name":"Benue","code":"BE"},{"name":"Borno","code":"BO"},{"name":"Cross River","code":"CR"},{"name":"Delta","code":"DE"},{"name":"Ebonyi","code":"EB"},{"name":"Edo","code":"ED"},{"name":"Ekiti","code":"EK"},{"name":"Enugu","code":"EN"},{"name":"Gombe","code":"GO"},{"name":"Imo","code":"IM"},{"name":"Jigawa","code":"JI"},{"name":"Kaduna","code":"KD"},{"name":"Kano","code":"KN"},{"name":"Katsina","code":"KT"},{"name":"Kebbi","code":"KE"},{"name":"Kogi","code":"KO"},{"name":"Kwara","code":"KW"},{"name":"Lagos","code":"LA"},{"name":"Nasarawa","code":"NA"},{"name":"Niger","code":"NI"},{"name":"Ogun","code":"OG"},{"name":"Ondo","code":"ON"},{"name":"Osun","code":"OS"},{"name":"Oyo","code":"OY"},{"name":"Plateau","code":"PL"},{"name":"Rivers","code":"RI"},{"name":"Sokoto","code":"SO"},{"name":"Taraba","code":"TA"},{"name":"Yobe","code":"YO"},{"name":"Zamfara","code":"ZA"}]},{"name":"Niue","code":"NU"},{"name":"Norfolk Island","code":"NF"},{"name":"North Macedonia","code":"MK"},{"name":"Norway","code":"NO"},{"name":"Oman","code":"OM"},{"name":"Pakistan","code":"PK"},{"name":"Palestinian Territories","code":"PS"},{"name":"Panama","code":"PA","provinces":[{"name":"Bocas del Toro","code":"PA-1"},{"name":"Chiriquí","code":"PA-4"},{"name":"Coclé","code":"PA-2"},{"name":"Colón","code":"PA-3"},{"name":"Darién","code":"PA-5"},{"name":"Emberá","code":"PA-EM"},{"name":"Herrera","code":"PA-6"},{"name":"Guna Yala","code":"PA-KY"},{"name":"Los Santos","code":"PA-7"},{"name":"Ngöbe-Buglé","code":"PA-NB"},{"name":"Panamá","code":"PA-8"},{"name":"West Panamá","code":"PA-10"},{"name":"Veraguas","code":"PA-9"}]},{"name":"Papua New Guinea","code":"PG"},{"name":"Paraguay","code":"PY"},{"name":"Peru","code":"PE","provinces":[{"name":"Amazonas","code":"PE-AMA"},{"name":"Ancash","code":"PE-ANC"},{"name":"Apurímac","code":"PE-APU"},{"name":"Arequipa","code":"PE-ARE"},{"name":"Ayacucho","code":"PE-AYA"},{"name":"Cajamarca","code":"PE-CAJ"},{"name":"El Callao","code":"PE-CAL"},{"name":"Cusco","code":"PE-CUS"},{"name":"Huancavelica","code":"PE-HUV"},{"name":"Huánuco","code":"PE-HUC"},{"name":"Ica","code":"PE-ICA"},{"name":"Junín","code":"PE-JUN"},{"name":"La Libertad","code":"PE-LAL"},{"name":"Lambayeque","code":"PE-LAM"},{"name":"Lima (Department)","code":"PE-LIM"},{"name":"Lima (Metropolitan)","code":"PE-LMA"},{"name":"Loreto","code":"PE-LOR"},{"name":"Madre de Dios","code":"PE-MDD"},{"name":"Moquegua","code":"PE-MOQ"},{"name":"Pasco","code":"PE-PAS"},{"name":"Piura","code":"PE-PIU"},{"name":"Puno","code":"PE-PUN"},{"name":"San Martín","code":"PE-SAM"},{"name":"Tacna","code":"PE-TAC"},{"name":"Tumbes","code":"PE-TUM"},{"name":"Ucayali","code":"PE-UCA"}]},{"name":"Philippines","code":"PH","provinces":[{"name":"Abra","code":"PH-ABR"},{"name":"Agusan del Norte","code":"PH-AGN"},{"name":"Agusan del Sur","code":"PH-AGS"},{"name":"Aklan","code":"PH-AKL"},{"name":"Albay","code":"PH-ALB"},{"name":"Antique","code":"PH-ANT"},{"name":"Apayao","code":"PH-APA"},{"name":"Aurora","code":"PH-AUR"},{"name":"Basilan","code":"PH-BAS"},{"name":"Bataan","code":"PH-BAN"},{"name":"Batanes","code":"PH-BTN"},{"name":"Batangas","code":"PH-BTG"},{"name":"Benguet","code":"PH-BEN"},{"name":"Biliran","code":"PH-BIL"},{"name":"Bohol","code":"PH-BOH"},{"name":"Bukidnon","code":"PH-BUK"},{"name":"Bulacan","code":"PH-BUL"},{"name":"Cagayan","code":"PH-CAG"},{"name":"Camarines Norte","code":"PH-CAN"},{"name":"Camarines Sur","code":"PH-CAS"},{"name":"Camiguin","code":"PH-CAM"},{"name":"Capiz","code":"PH-CAP"},{"name":"Catanduanes","code":"PH-CAT"},{"name":"Cavite","code":"PH-CAV"},{"name":"Cebu","code":"PH-CEB"},{"name":"Cotabato","code":"PH-NCO"},{"name":"Davao Occidental","code":"PH-DVO"},{"name":"Davao Oriental","code":"PH-DAO"},{"name":"Compostela Valley","code":"PH-COM"},{"name":"Davao del Norte","code":"PH-DAV"},{"name":"Davao del Sur","code":"PH-DAS"},{"name":"Dinagat Islands","code":"PH-DIN"},{"name":"Eastern Samar","code":"PH-EAS"},{"name":"Guimaras","code":"PH-GUI"},{"name":"Ifugao","code":"PH-IFU"},{"name":"Ilocos Norte","code":"PH-ILN"},{"name":"Ilocos Sur","code":"PH-ILS"},{"name":"Iloilo","code":"PH-ILI"},{"name":"Isabela","code":"PH-ISA"},{"name":"Kalinga","code":"PH-KAL"},{"name":"La Union","code":"PH-LUN"},{"name":"Laguna","code":"PH-LAG"},{"name":"Lanao del Norte","code":"PH-LAN"},{"name":"Lanao del Sur","code":"PH-LAS"},{"name":"Leyte","code":"PH-LEY"},{"name":"Maguindanao","code":"PH-MAG"},{"name":"Marinduque","code":"PH-MAD"},{"name":"Masbate","code":"PH-MAS"},{"name":"Metro Manila","code":"PH-00"},{"name":"Misamis Occidental","code":"PH-MSC"},{"name":"Misamis Oriental","code":"PH-MSR"},{"name":"Mountain","code":"PH-MOU"},{"name":"Negros Occidental","code":"PH-NEC"},{"name":"Negros Oriental","code":"PH-NER"},{"name":"Northern Samar","code":"PH-NSA"},{"name":"Nueva Ecija","code":"PH-NUE"},{"name":"Nueva Vizcaya","code":"PH-NUV"},{"name":"Occidental Mindoro","code":"PH-MDC"},{"name":"Oriental Mindoro","code":"PH-MDR"},{"name":"Palawan","code":"PH-PLW"},{"name":"Pampanga","code":"PH-PAM"},{"name":"Pangasinan","code":"PH-PAN"},{"name":"Quezon","code":"PH-QUE"},{"name":"Quirino","code":"PH-QUI"},{"name":"Rizal","code":"PH-RIZ"},{"name":"Romblon","code":"PH-ROM"},{"name":"Samar","code":"PH-WSA"},{"name":"Sarangani","code":"PH-SAR"},{"name":"Siquijor","code":"PH-SIG"},{"name":"Sorsogon","code":"PH-SOR"},{"name":"South Cotabato","code":"PH-SCO"},{"name":"Southern Leyte","code":"PH-SLE"},{"name":"Sultan Kudarat","code":"PH-SUK"},{"name":"Sulu","code":"PH-SLU"},{"name":"Surigao del Norte","code":"PH-SUN"},{"name":"Surigao del Sur","code":"PH-SUR"},{"name":"Tarlac","code":"PH-TAR"},{"name":"Tawi-Tawi","code":"PH-TAW"},{"name":"Zambales","code":"PH-ZMB"},{"name":"Zamboanga Sibugay","code":"PH-ZSI"},{"name":"Zamboanga del Norte","code":"PH-ZAN"},{"name":"Zamboanga del Sur","code":"PH-ZAS"}]},{"name":"Pitcairn Islands","code":"PN"},{"name":"Poland","code":"PL"},{"name":"Portugal","code":"PT","provinces":[{"name":"Azores","code":"PT-20"},{"name":"Aveiro","code":"PT-01"},{"name":"Beja","code":"PT-02"},{"name":"Braga","code":"PT-03"},{"name":"Bragança","code":"PT-04"},{"name":"Castelo Branco","code":"PT-05"},{"name":"Coimbra","code":"PT-06"},{"name":"Évora","code":"PT-07"},{"name":"Faro","code":"PT-08"},{"name":"Guarda","code":"PT-09"},{"name":"Leiria","code":"PT-10"},{"name":"Lisbon","code":"PT-11"},{"name":"Madeira","code":"PT-30"},{"name":"Portalegre","code":"PT-12"},{"name":"Porto","code":"PT-13"},{"name":"Santarém","code":"PT-14"},{"name":"Setúbal","code":"PT-15"},{"name":"Viana do Castelo","code":"PT-16"},{"name":"Vila Real","code":"PT-17"},{"name":"Viseu","code":"PT-18"}]},{"name":"Qatar","code":"QA"},{"name":"Réunion","code":"RE"},{"name":"Romania","code":"RO","provinces":[{"name":"Alba","code":"AB"},{"name":"Arad","code":"AR"},{"name":"Argeș","code":"AG"},{"name":"Bacău","code":"BC"},{"name":"Bihor","code":"BH"},{"name":"Bistriţa-Năsăud","code":"BN"},{"name":"Botoşani","code":"BT"},{"name":"Brăila","code":"BR"},{"name":"Braşov","code":"BV"},{"name":"Bucharest","code":"B"},{"name":"Buzău","code":"BZ"},{"name":"Caraș-Severin","code":"CS"},{"name":"Cluj","code":"CJ"},{"name":"Constanța","code":"CT"},{"name":"Covasna","code":"CV"},{"name":"Călărași","code":"CL"},{"name":"Dolj","code":"DJ"},{"name":"Dâmbovița","code":"DB"},{"name":"Galați","code":"GL"},{"name":"Giurgiu","code":"GR"},{"name":"Gorj","code":"GJ"},{"name":"Harghita","code":"HR"},{"name":"Hunedoara","code":"HD"},{"name":"Ialomița","code":"IL"},{"name":"Iași","code":"IS"},{"name":"Ilfov","code":"IF"},{"name":"Maramureş","code":"MM"},{"name":"Mehedinți","code":"MH"},{"name":"Mureş","code":"MS"},{"name":"Neamţ","code":"NT"},{"name":"Olt","code":"OT"},{"name":"Prahova","code":"PH"},{"name":"Sălaj","code":"SJ"},{"name":"Satu Mare","code":"SM"},{"name":"Sibiu","code":"SB"},{"name":"Suceava","code":"SV"},{"name":"Teleorman","code":"TR"},{"name":"Timiș","code":"TM"},{"name":"Tulcea","code":"TL"},{"name":"Vâlcea","code":"VL"},{"name":"Vaslui","code":"VS"},{"name":"Vrancea","code":"VN"}]},{"name":"Russia","code":"RU","provinces":[{"name":"Altai Krai","code":"ALT"},{"name":"Altai","code":"AL"},{"name":"Amur","code":"AMU"},{"name":"Arkhangelsk","code":"ARK"},{"name":"Astrakhan","code":"AST"},{"name":"Belgorod","code":"BEL"},{"name":"Bryansk","code":"BRY"},{"name":"Chechen","code":"CE"},{"name":"Chelyabinsk","code":"CHE"},{"name":"Chukotka Okrug","code":"CHU"},{"name":"Chuvash","code":"CU"},{"name":"Irkutsk","code":"IRK"},{"name":"Ivanovo","code":"IVA"},{"name":"Jewish","code":"YEV"},{"name":"Kabardino-Balkar","code":"KB"},{"name":"Kaliningrad","code":"KGD"},{"name":"Kaluga","code":"KLU"},{"name":"Kamchatka Krai","code":"KAM"},{"name":"Karachay-Cherkess","code":"KC"},{"name":"Kemerovo","code":"KEM"},{"name":"Khabarovsk Krai","code":"KHA"},{"name":"Khanty-Mansi","code":"KHM"},{"name":"Kirov","code":"KIR"},{"name":"Komi","code":"KO"},{"name":"Kostroma","code":"KOS"},{"name":"Krasnodar Krai","code":"KDA"},{"name":"Krasnoyarsk Krai","code":"KYA"},{"name":"Kurgan","code":"KGN"},{"name":"Kursk","code":"KRS"},{"name":"Leningrad","code":"LEN"},{"name":"Lipetsk","code":"LIP"},{"name":"Magadan","code":"MAG"},{"name":"Mari El","code":"ME"},{"name":"Moscow","code":"MOW"},{"name":"Moscow Province","code":"MOS"},{"name":"Murmansk","code":"MUR"},{"name":"Nizhny Novgorod","code":"NIZ"},{"name":"Novgorod","code":"NGR"},{"name":"Novosibirsk","code":"NVS"},{"name":"Omsk","code":"OMS"},{"name":"Orenburg","code":"ORE"},{"name":"Oryol","code":"ORL"},{"name":"Penza","code":"PNZ"},{"name":"Perm Krai","code":"PER"},{"name":"Primorsky Krai","code":"PRI"},{"name":"Pskov","code":"PSK"},{"name":"Adygea","code":"AD"},{"name":"Bashkortostan","code":"BA"},{"name":"Buryat","code":"BU"},{"name":"Dagestan","code":"DA"},{"name":"Ingushetia","code":"IN"},{"name":"Kalmykia","code":"KL"},{"name":"Karelia","code":"KR"},{"name":"Khakassia","code":"KK"},{"name":"Mordovia","code":"MO"},{"name":"North Ossetia-Alania","code":"SE"},{"name":"Tatarstan","code":"TA"},{"name":"Rostov","code":"ROS"},{"name":"Ryazan","code":"RYA"},{"name":"Saint Petersburg","code":"SPE"},{"name":"Sakha","code":"SA"},{"name":"Sakhalin","code":"SAK"},{"name":"Samara","code":"SAM"},{"name":"Saratov","code":"SAR"},{"name":"Smolensk","code":"SMO"},{"name":"Stavropol Krai","code":"STA"},{"name":"Sverdlovsk","code":"SVE"},{"name":"Tambov","code":"TAM"},{"name":"Tomsk","code":"TOM"},{"name":"Tula","code":"TUL"},{"name":"Tver","code":"TVE"},{"name":"Tyumen","code":"TYU"},{"name":"Tuva","code":"TY"},{"name":"Udmurt","code":"UD"},{"name":"Ulyanovsk","code":"ULY"},{"name":"Vladimir","code":"VLA"},{"name":"Volgograd","code":"VGG"},{"name":"Vologda","code":"VLG"},{"name":"Voronezh","code":"VOR"},{"name":"Yamalo-Nenets Okrug","code":"YAN"},{"name":"Yaroslavl","code":"YAR"},{"name":"Zabaykalsky Krai","code":"ZAB"}]},{"name":"Rwanda","code":"RW"},{"name":"Samoa","code":"WS"},{"name":"San Marino","code":"SM"},{"name":"São Tomé \u0026 Príncipe","code":"ST"},{"name":"Saudi Arabia","code":"SA"},{"name":"Senegal","code":"SN"},{"name":"Serbia","code":"RS"},{"name":"Seychelles","code":"SC"},{"name":"Sierra Leone","code":"SL"},{"name":"Singapore","code":"SG"},{"name":"Sint Maarten","code":"SX"},{"name":"Slovakia","code":"SK"},{"name":"Slovenia","code":"SI"},{"name":"Solomon Islands","code":"SB"},{"name":"Somalia","code":"SO"},{"name":"South Africa","code":"ZA","provinces":[{"name":"Eastern Cape","code":"EC"},{"name":"Free State","code":"FS"},{"name":"Gauteng","code":"GP"},{"name":"KwaZulu-Natal","code":"NL"},{"name":"Limpopo","code":"LP"},{"name":"Mpumalanga","code":"MP"},{"name":"North West","code":"NW"},{"name":"Northern Cape","code":"NC"},{"name":"Western Cape","code":"WC"}]},{"name":"South Georgia \u0026 South Sandwich Islands","code":"GS"},{"name":"South Korea","code":"KR","provinces":[{"name":"Busan","code":"KR-26"},{"name":"North Chungcheong","code":"KR-43"},{"name":"South Chungcheong","code":"KR-44"},{"name":"Daegu","code":"KR-27"},{"name":"Daejeon","code":"KR-30"},{"name":"Gangwon","code":"KR-42"},{"name":"Gwangju City","code":"KR-29"},{"name":"North Gyeongsang","code":"KR-47"},{"name":"Gyeonggi","code":"KR-41"},{"name":"South Gyeongsang","code":"KR-48"},{"name":"Incheon","code":"KR-28"},{"name":"Jeju","code":"KR-49"},{"name":"North Jeolla","code":"KR-45"},{"name":"South Jeolla","code":"KR-46"},{"name":"Sejong","code":"KR-50"},{"name":"Seoul","code":"KR-11"},{"name":"Ulsan","code":"KR-31"}]},{"name":"South Sudan","code":"SS"},{"name":"Spain","code":"ES","provinces":[{"name":"A Coruña","code":"C"},{"name":"Álava","code":"VI"},{"name":"Albacete","code":"AB"},{"name":"Alicante","code":"A"},{"name":"Almería","code":"AL"},{"name":"Asturias Province","code":"O"},{"name":"Ávila","code":"AV"},{"name":"Badajoz","code":"BA"},{"name":"Balears Province","code":"PM"},{"name":"Barcelona","code":"B"},{"name":"Burgos","code":"BU"},{"name":"Cáceres","code":"CC"},{"name":"Cádiz","code":"CA"},{"name":"Cantabria Province","code":"S"},{"name":"Castellón","code":"CS"},{"name":"Ceuta","code":"CE"},{"name":"Ciudad Real","code":"CR"},{"name":"Córdoba","code":"CO"},{"name":"Cuenca","code":"CU"},{"name":"Girona","code":"GI"},{"name":"Granada","code":"GR"},{"name":"Guadalajara","code":"GU"},{"name":"Gipuzkoa","code":"SS"},{"name":"Huelva","code":"H"},{"name":"Huesca","code":"HU"},{"name":"Jaén","code":"J"},{"name":"La Rioja Province","code":"LO"},{"name":"Las Palmas","code":"GC"},{"name":"León","code":"LE"},{"name":"Lleida","code":"L"},{"name":"Lugo","code":"LU"},{"name":"Madrid Province","code":"M"},{"name":"Málaga","code":"MA"},{"name":"Melilla","code":"ML"},{"name":"Murcia","code":"MU"},{"name":"Navarra","code":"NA"},{"name":"Ourense","code":"OR"},{"name":"Palencia","code":"P"},{"name":"Pontevedra","code":"PO"},{"name":"Salamanca","code":"SA"},{"name":"Santa Cruz de Tenerife","code":"TF"},{"name":"Segovia","code":"SG"},{"name":"Seville","code":"SE"},{"name":"Soria","code":"SO"},{"name":"Tarragona","code":"T"},{"name":"Teruel","code":"TE"},{"name":"Toledo","code":"TO"},{"name":"Valencia","code":"V"},{"name":"Valladolid","code":"VA"},{"name":"Biscay","code":"BI"},{"name":"Zamora","code":"ZA"},{"name":"Zaragoza","code":"Z"}]},{"name":"Sri Lanka","code":"LK"},{"name":"St. Barthélemy","code":"BL"},{"name":"St. Helena","code":"SH"},{"name":"St. Kitts \u0026 Nevis","code":"KN"},{"name":"St. Lucia","code":"LC"},{"name":"St. Martin","code":"MF"},{"name":"St. Pierre \u0026 Miquelon","code":"PM"},{"name":"St. Vincent \u0026 Grenadines","code":"VC"},{"name":"Sudan","code":"SD"},{"name":"Suriname","code":"SR"},{"name":"Svalbard \u0026 Jan Mayen","code":"SJ"},{"name":"Sweden","code":"SE"},{"name":"Switzerland","code":"CH"},{"name":"Taiwan","code":"TW"},{"name":"Tajikistan","code":"TJ"},{"name":"Tanzania","code":"TZ"},{"name":"Thailand","code":"TH","provinces":[{"name":"Amnat Charoen","code":"TH-37"},{"name":"Ang Thong","code":"TH-15"},{"name":"Bangkok","code":"TH-10"},{"name":"Bueng Kan","code":"TH-38"},{"name":"Buri Ram","code":"TH-31"},{"name":"Chachoengsao","code":"TH-24"},{"name":"Chai Nat","code":"TH-18"},{"name":"Chaiyaphum","code":"TH-36"},{"name":"Chanthaburi","code":"TH-22"},{"name":"Chiang Mai","code":"TH-50"},{"name":"Chiang Rai","code":"TH-57"},{"name":"Chon Buri","code":"TH-20"},{"name":"Chumphon","code":"TH-86"},{"name":"Kalasin","code":"TH-46"},{"name":"Kamphaeng Phet","code":"TH-62"},{"name":"Kanchanaburi","code":"TH-71"},{"name":"Khon Kaen","code":"TH-40"},{"name":"Krabi","code":"TH-81"},{"name":"Lampang","code":"TH-52"},{"name":"Lamphun","code":"TH-51"},{"name":"Loei","code":"TH-42"},{"name":"Lopburi","code":"TH-16"},{"name":"Mae Hong Son","code":"TH-58"},{"name":"Maha Sarakham","code":"TH-44"},{"name":"Mukdahan","code":"TH-49"},{"name":"Nakhon Nayok","code":"TH-26"},{"name":"Nakhon Pathom","code":"TH-73"},{"name":"Nakhon Phanom","code":"TH-48"},{"name":"Nakhon Ratchasima","code":"TH-30"},{"name":"Nakhon Sawan","code":"TH-60"},{"name":"Nakhon Si Thammarat","code":"TH-80"},{"name":"Nan","code":"TH-55"},{"name":"Narathiwat","code":"TH-96"},{"name":"Nong Bua Lam Phu","code":"TH-39"},{"name":"Nong Khai","code":"TH-43"},{"name":"Nonthaburi","code":"TH-12"},{"name":"Pathum Thani","code":"TH-13"},{"name":"Pattani","code":"TH-94"},{"name":"Pattaya","code":"TH-S"},{"name":"Phang Nga","code":"TH-82"},{"name":"Phatthalung","code":"TH-93"},{"name":"Phayao","code":"TH-56"},{"name":"Phetchabun","code":"TH-67"},{"name":"Phetchaburi","code":"TH-76"},{"name":"Phichit","code":"TH-66"},{"name":"Phitsanulok","code":"TH-65"},{"name":"Phra Nakhon Si Ayutthaya","code":"TH-14"},{"name":"Phrae","code":"TH-54"},{"name":"Phuket","code":"TH-83"},{"name":"Prachin Buri","code":"TH-25"},{"name":"Prachuap Khiri Khan","code":"TH-77"},{"name":"Ranong","code":"TH-85"},{"name":"Ratchaburi","code":"TH-70"},{"name":"Rayong","code":"TH-21"},{"name":"Roi Et","code":"TH-45"},{"name":"Sa Kaeo","code":"TH-27"},{"name":"Sakon Nakhon","code":"TH-47"},{"name":"Samut Prakan","code":"TH-11"},{"name":"Samut Sakhon","code":"TH-74"},{"name":"Samut Songkhram","code":"TH-75"},{"name":"Saraburi","code":"TH-19"},{"name":"Satun","code":"TH-91"},{"name":"Sing Buri","code":"TH-17"},{"name":"Si Sa Ket","code":"TH-33"},{"name":"Songkhla","code":"TH-90"},{"name":"Sukhothai","code":"TH-64"},{"name":"Suphanburi","code":"TH-72"},{"name":"Surat Thani","code":"TH-84"},{"name":"Surin","code":"TH-32"},{"name":"Tak","code":"TH-63"},{"name":"Trang","code":"TH-92"},{"name":"Trat","code":"TH-23"},{"name":"Ubon Ratchathani","code":"TH-34"},{"name":"Udon Thani","code":"TH-41"},{"name":"Uthai Thani","code":"TH-61"},{"name":"Uttaradit","code":"TH-53"},{"name":"Yala","code":"TH-95"},{"name":"Yasothon","code":"TH-35"}]},{"name":"Timor-Leste","code":"TL"},{"name":"Togo","code":"TG"},{"name":"Tokelau","code":"TK"},{"name":"Tonga","code":"TO"},{"name":"Trinidad \u0026 Tobago","code":"TT"},{"name":"Tristan da Cunha","code":"TA"},{"name":"Tunisia","code":"TN"},{"name":"Turkey","code":"TR"},{"name":"Turkmenistan","code":"TM"},{"name":"Turks \u0026 Caicos Islands","code":"TC"},{"name":"Tuvalu","code":"TV"},{"name":"U.S. Outlying Islands","code":"UM"},{"name":"Uganda","code":"UG"},{"name":"Ukraine","code":"UA"},{"name":"United Arab Emirates","code":"AE","provinces":[{"name":"Abu Dhabi","code":"AZ"},{"name":"Ajman","code":"AJ"},{"name":"Dubai","code":"DU"},{"name":"Fujairah","code":"FU"},{"name":"Ras al-Khaimah","code":"RK"},{"name":"Sharjah","code":"SH"},{"name":"Umm al-Quwain","code":"UQ"}]},{"name":"United Kingdom","code":"GB","provinces":[{"name":"British Forces","code":"BFP"},{"name":"England","code":"ENG"},{"name":"Northern Ireland","code":"NIR"},{"name":"Scotland","code":"SCT"},{"name":"Wales","code":"WLS"}]},{"name":"United States","code":"US","provinces":[{"name":"Alabama","code":"AL"},{"name":"Alaska","code":"AK"},{"name":"American Samoa","code":"AS"},{"name":"Arizona","code":"AZ"},{"name":"Arkansas","code":"AR"},{"name":"California","code":"CA"},{"name":"Colorado","code":"CO"},{"name":"Connecticut","code":"CT"},{"name":"Delaware","code":"DE"},{"name":"Washington DC","code":"DC"},{"name":"Micronesia","code":"FM"},{"name":"Florida","code":"FL"},{"name":"Georgia","code":"GA"},{"name":"Guam","code":"GU"},{"name":"Hawaii","code":"HI"},{"name":"Idaho","code":"ID"},{"name":"Illinois","code":"IL"},{"name":"Indiana","code":"IN"},{"name":"Iowa","code":"IA"},{"name":"Kansas","code":"KS"},{"name":"Kentucky","code":"KY"},{"name":"Louisiana","code":"LA"},{"name":"Maine","code":"ME"},{"name":"Marshall Islands","code":"MH"},{"name":"Maryland","code":"MD"},{"name":"Massachusetts","code":"MA"},{"name":"Michigan","code":"MI"},{"name":"Minnesota","code":"MN"},{"name":"Mississippi","code":"MS"},{"name":"Missouri","code":"MO"},{"name":"Montana","code":"MT"},{"name":"Nebraska","code":"NE"},{"name":"Nevada","code":"NV"},{"name":"New Hampshire","code":"NH"},{"name":"New Jersey","code":"NJ"},{"name":"New Mexico","code":"NM"},{"name":"New York","code":"NY"},{"name":"North Carolina","code":"NC"},{"name":"North Dakota","code":"ND"},{"name":"Northern Mariana Islands","code":"MP"},{"name":"Ohio","code":"OH"},{"name":"Oklahoma","code":"OK"},{"name":"Oregon","code":"OR"},{"name":"Palau","code":"PW"},{"name":"Pennsylvania","code":"PA"},{"name":"Puerto Rico","code":"PR"},{"name":"Rhode Island","code":"RI"},{"name":"South Carolina","code":"SC"},{"name":"South Dakota","code":"SD"},{"name":"Tennessee","code":"TN"},{"name":"Texas","code":"TX"},{"name":"Utah","code":"UT"},{"name":"Vermont","code":"VT"},{"name":"U.S. Virgin Islands","code":"VI"},{"name":"Virginia","code":"VA"},{"name":"Washington","code":"WA"},{"name":"West Virginia","code":"WV"},{"name":"Wisconsin","code":"WI"},{"name":"Wyoming","code":"WY"},{"name":"Armed Forces Americas","code":"AA"},{"name":"Armed Forces Europe","code":"AE"},{"name":"Armed Forces Pacific","code":"AP"}]},{"name":"Uruguay","code":"UY","provinces":[{"name":"Artigas","code":"UY-AR"},{"name":"Canelones","code":"UY-CA"},{"name":"Cerro Largo","code":"UY-CL"},{"name":"Colonia","code":"UY-CO"},{"name":"Durazno","code":"UY-DU"},{"name":"Flores","code":"UY-FS"},{"name":"Florida","code":"UY-FD"},{"name":"Lavalleja","code":"UY-LA"},{"name":"Maldonado","code":"UY-MA"},{"name":"Montevideo","code":"UY-MO"},{"name":"Paysandú","code":"UY-PA"},{"name":"Río Negro","code":"UY-RN"},{"name":"Rivera","code":"UY-RV"},{"name":"Rocha","code":"UY-RO"},{"name":"Salto","code":"UY-SA"},{"name":"San José","code":"UY-SJ"},{"name":"Soriano","code":"UY-SO"},{"name":"Tacuarembó","code":"UY-TA"},{"name":"Treinta y Tres","code":"UY-TT"}]},{"name":"Uzbekistan","code":"UZ"},{"name":"Vanuatu","code":"VU"},{"name":"Vatican City","code":"VA"},{"name":"Venezuela","code":"VE","provinces":[{"name":"Amazonas","code":"VE-Z"},{"name":"Anzoátegui","code":"VE-B"},{"name":"Apure","code":"VE-C"},{"name":"Aragua","code":"VE-D"},{"name":"Barinas","code":"VE-E"},{"name":"Bolívar","code":"VE-F"},{"name":"Carabobo","code":"VE-G"},{"name":"Cojedes","code":"VE-H"},{"name":"Delta Amacuro","code":"VE-Y"},{"name":"Federal Dependencies","code":"VE-W"},{"name":"Capital","code":"VE-A"},{"name":"Falcón","code":"VE-I"},{"name":"Guárico","code":"VE-J"},{"name":"Vargas","code":"VE-X"},{"name":"Lara","code":"VE-K"},{"name":"Mérida","code":"VE-L"},{"name":"Miranda","code":"VE-M"},{"name":"Monagas","code":"VE-N"},{"name":"Nueva Esparta","code":"VE-O"},{"name":"Portuguesa","code":"VE-P"},{"name":"Sucre","code":"VE-R"},{"name":"Táchira","code":"VE-S"},{"name":"Trujillo","code":"VE-T"},{"name":"Yaracuy","code":"VE-U"},{"name":"Zulia","code":"VE-V"}]},{"name":"Vietnam","code":"VN"},{"name":"Wallis \u0026 Futuna","code":"WF"},{"name":"Western Sahara","code":"EH"},{"name":"Yemen","code":"YE"},{"name":"Zambia","code":"ZM"},{"name":"Zimbabwe","code":"ZW"}],
  "locale": "en",
  
    "localeRootPath": "\/",
  
  
    "adminIsLoggedIn": false
  
  }
;
  window.CF.allCountryOptionTags = `<option value="---" data-provinces="[]">---</option>
<option value="Afghanistan" data-provinces="[]">Afghanistan</option>
<option value="Aland Islands" data-provinces="[]">Åland Islands</option>
<option value="Albania" data-provinces="[]">Albania</option>
<option value="Algeria" data-provinces="[]">Algeria</option>
<option value="Andorra" data-provinces="[]">Andorra</option>
<option value="Angola" data-provinces="[]">Angola</option>
<option value="Anguilla" data-provinces="[]">Anguilla</option>
<option value="Antigua And Barbuda" data-provinces="[]">Antigua & Barbuda</option>
<option value="Argentina" data-provinces="[[&quot;Buenos Aires&quot;,&quot;Buenos Aires Province&quot;],[&quot;Catamarca&quot;,&quot;Catamarca&quot;],[&quot;Chaco&quot;,&quot;Chaco&quot;],[&quot;Chubut&quot;,&quot;Chubut&quot;],[&quot;Ciudad Autónoma de Buenos Aires&quot;,&quot;Buenos Aires (Autonomous City)&quot;],[&quot;Corrientes&quot;,&quot;Corrientes&quot;],[&quot;Córdoba&quot;,&quot;Córdoba&quot;],[&quot;Entre Ríos&quot;,&quot;Entre Ríos&quot;],[&quot;Formosa&quot;,&quot;Formosa&quot;],[&quot;Jujuy&quot;,&quot;Jujuy&quot;],[&quot;La Pampa&quot;,&quot;La Pampa&quot;],[&quot;La Rioja&quot;,&quot;La Rioja&quot;],[&quot;Mendoza&quot;,&quot;Mendoza&quot;],[&quot;Misiones&quot;,&quot;Misiones&quot;],[&quot;Neuquén&quot;,&quot;Neuquén&quot;],[&quot;Río Negro&quot;,&quot;Río Negro&quot;],[&quot;Salta&quot;,&quot;Salta&quot;],[&quot;San Juan&quot;,&quot;San Juan&quot;],[&quot;San Luis&quot;,&quot;San Luis&quot;],[&quot;Santa Cruz&quot;,&quot;Santa Cruz&quot;],[&quot;Santa Fe&quot;,&quot;Santa Fe&quot;],[&quot;Santiago Del Estero&quot;,&quot;Santiago del Estero&quot;],[&quot;Tierra Del Fuego&quot;,&quot;Tierra del Fuego&quot;],[&quot;Tucumán&quot;,&quot;Tucumán&quot;]]">Argentina</option>
<option value="Armenia" data-provinces="[]">Armenia</option>
<option value="Aruba" data-provinces="[]">Aruba</option>
<option value="Ascension Island" data-provinces="[]">Ascension Island</option>
<option value="Australia" data-provinces="[[&quot;Australian Capital Territory&quot;,&quot;Australian Capital Territory&quot;],[&quot;New South Wales&quot;,&quot;New South Wales&quot;],[&quot;Northern Territory&quot;,&quot;Northern Territory&quot;],[&quot;Queensland&quot;,&quot;Queensland&quot;],[&quot;South Australia&quot;,&quot;South Australia&quot;],[&quot;Tasmania&quot;,&quot;Tasmania&quot;],[&quot;Victoria&quot;,&quot;Victoria&quot;],[&quot;Western Australia&quot;,&quot;Western Australia&quot;]]">Australia</option>
<option value="Austria" data-provinces="[]">Austria</option>
<option value="Azerbaijan" data-provinces="[]">Azerbaijan</option>
<option value="Bahamas" data-provinces="[]">Bahamas</option>
<option value="Bahrain" data-provinces="[]">Bahrain</option>
<option value="Bangladesh" data-provinces="[]">Bangladesh</option>
<option value="Barbados" data-provinces="[]">Barbados</option>
<option value="Belarus" data-provinces="[]">Belarus</option>
<option value="Belgium" data-provinces="[]">Belgium</option>
<option value="Belize" data-provinces="[]">Belize</option>
<option value="Benin" data-provinces="[]">Benin</option>
<option value="Bermuda" data-provinces="[]">Bermuda</option>
<option value="Bhutan" data-provinces="[]">Bhutan</option>
<option value="Bolivia" data-provinces="[]">Bolivia</option>
<option value="Bosnia And Herzegovina" data-provinces="[]">Bosnia & Herzegovina</option>
<option value="Botswana" data-provinces="[]">Botswana</option>
<option value="Brazil" data-provinces="[[&quot;Acre&quot;,&quot;Acre&quot;],[&quot;Alagoas&quot;,&quot;Alagoas&quot;],[&quot;Amapá&quot;,&quot;Amapá&quot;],[&quot;Amazonas&quot;,&quot;Amazonas&quot;],[&quot;Bahia&quot;,&quot;Bahia&quot;],[&quot;Ceará&quot;,&quot;Ceará&quot;],[&quot;Distrito Federal&quot;,&quot;Federal District&quot;],[&quot;Espírito Santo&quot;,&quot;Espírito Santo&quot;],[&quot;Goiás&quot;,&quot;Goiás&quot;],[&quot;Maranhão&quot;,&quot;Maranhão&quot;],[&quot;Mato Grosso&quot;,&quot;Mato Grosso&quot;],[&quot;Mato Grosso do Sul&quot;,&quot;Mato Grosso do Sul&quot;],[&quot;Minas Gerais&quot;,&quot;Minas Gerais&quot;],[&quot;Paraná&quot;,&quot;Paraná&quot;],[&quot;Paraíba&quot;,&quot;Paraíba&quot;],[&quot;Pará&quot;,&quot;Pará&quot;],[&quot;Pernambuco&quot;,&quot;Pernambuco&quot;],[&quot;Piauí&quot;,&quot;Piauí&quot;],[&quot;Rio Grande do Norte&quot;,&quot;Rio Grande do Norte&quot;],[&quot;Rio Grande do Sul&quot;,&quot;Rio Grande do Sul&quot;],[&quot;Rio de Janeiro&quot;,&quot;Rio de Janeiro&quot;],[&quot;Rondônia&quot;,&quot;Rondônia&quot;],[&quot;Roraima&quot;,&quot;Roraima&quot;],[&quot;Santa Catarina&quot;,&quot;Santa Catarina&quot;],[&quot;Sergipe&quot;,&quot;Sergipe&quot;],[&quot;São Paulo&quot;,&quot;São Paulo&quot;],[&quot;Tocantins&quot;,&quot;Tocantins&quot;]]">Brazil</option>
<option value="British Indian Ocean Territory" data-provinces="[]">British Indian Ocean Territory</option>
<option value="Virgin Islands, British" data-provinces="[]">British Virgin Islands</option>
<option value="Brunei" data-provinces="[]">Brunei</option>
<option value="Bulgaria" data-provinces="[]">Bulgaria</option>
<option value="Burkina Faso" data-provinces="[]">Burkina Faso</option>
<option value="Burundi" data-provinces="[]">Burundi</option>
<option value="Cambodia" data-provinces="[]">Cambodia</option>
<option value="Republic of Cameroon" data-provinces="[]">Cameroon</option>
<option value="Canada" data-provinces="[[&quot;Alberta&quot;,&quot;Alberta&quot;],[&quot;British Columbia&quot;,&quot;British Columbia&quot;],[&quot;Manitoba&quot;,&quot;Manitoba&quot;],[&quot;New Brunswick&quot;,&quot;New Brunswick&quot;],[&quot;Newfoundland and Labrador&quot;,&quot;Newfoundland and Labrador&quot;],[&quot;Northwest Territories&quot;,&quot;Northwest Territories&quot;],[&quot;Nova Scotia&quot;,&quot;Nova Scotia&quot;],[&quot;Nunavut&quot;,&quot;Nunavut&quot;],[&quot;Ontario&quot;,&quot;Ontario&quot;],[&quot;Prince Edward Island&quot;,&quot;Prince Edward Island&quot;],[&quot;Quebec&quot;,&quot;Quebec&quot;],[&quot;Saskatchewan&quot;,&quot;Saskatchewan&quot;],[&quot;Yukon&quot;,&quot;Yukon&quot;]]">Canada</option>
<option value="Cape Verde" data-provinces="[]">Cape Verde</option>
<option value="Caribbean Netherlands" data-provinces="[]">Caribbean Netherlands</option>
<option value="Cayman Islands" data-provinces="[]">Cayman Islands</option>
<option value="Central African Republic" data-provinces="[]">Central African Republic</option>
<option value="Chad" data-provinces="[]">Chad</option>
<option value="Chile" data-provinces="[[&quot;Antofagasta&quot;,&quot;Antofagasta&quot;],[&quot;Araucanía&quot;,&quot;Araucanía&quot;],[&quot;Arica and Parinacota&quot;,&quot;Arica y Parinacota&quot;],[&quot;Atacama&quot;,&quot;Atacama&quot;],[&quot;Aysén&quot;,&quot;Aysén&quot;],[&quot;Biobío&quot;,&quot;Bío Bío&quot;],[&quot;Coquimbo&quot;,&quot;Coquimbo&quot;],[&quot;Los Lagos&quot;,&quot;Los Lagos&quot;],[&quot;Los Ríos&quot;,&quot;Los Ríos&quot;],[&quot;Magallanes&quot;,&quot;Magallanes Region&quot;],[&quot;Maule&quot;,&quot;Maule&quot;],[&quot;O&#39;Higgins&quot;,&quot;Libertador General Bernardo O’Higgins&quot;],[&quot;Santiago&quot;,&quot;Santiago Metropolitan&quot;],[&quot;Tarapacá&quot;,&quot;Tarapacá&quot;],[&quot;Valparaíso&quot;,&quot;Valparaíso&quot;],[&quot;Ñuble&quot;,&quot;Ñuble&quot;]]">Chile</option>
<option value="China" data-provinces="[[&quot;Anhui&quot;,&quot;Anhui&quot;],[&quot;Beijing&quot;,&quot;Beijing&quot;],[&quot;Chongqing&quot;,&quot;Chongqing&quot;],[&quot;Fujian&quot;,&quot;Fujian&quot;],[&quot;Gansu&quot;,&quot;Gansu&quot;],[&quot;Guangdong&quot;,&quot;Guangdong&quot;],[&quot;Guangxi&quot;,&quot;Guangxi&quot;],[&quot;Guizhou&quot;,&quot;Guizhou&quot;],[&quot;Hainan&quot;,&quot;Hainan&quot;],[&quot;Hebei&quot;,&quot;Hebei&quot;],[&quot;Heilongjiang&quot;,&quot;Heilongjiang&quot;],[&quot;Henan&quot;,&quot;Henan&quot;],[&quot;Hubei&quot;,&quot;Hubei&quot;],[&quot;Hunan&quot;,&quot;Hunan&quot;],[&quot;Inner Mongolia&quot;,&quot;Inner Mongolia&quot;],[&quot;Jiangsu&quot;,&quot;Jiangsu&quot;],[&quot;Jiangxi&quot;,&quot;Jiangxi&quot;],[&quot;Jilin&quot;,&quot;Jilin&quot;],[&quot;Liaoning&quot;,&quot;Liaoning&quot;],[&quot;Ningxia&quot;,&quot;Ningxia&quot;],[&quot;Qinghai&quot;,&quot;Qinghai&quot;],[&quot;Shaanxi&quot;,&quot;Shaanxi&quot;],[&quot;Shandong&quot;,&quot;Shandong&quot;],[&quot;Shanghai&quot;,&quot;Shanghai&quot;],[&quot;Shanxi&quot;,&quot;Shanxi&quot;],[&quot;Sichuan&quot;,&quot;Sichuan&quot;],[&quot;Tianjin&quot;,&quot;Tianjin&quot;],[&quot;Xinjiang&quot;,&quot;Xinjiang&quot;],[&quot;Xizang&quot;,&quot;Tibet&quot;],[&quot;Yunnan&quot;,&quot;Yunnan&quot;],[&quot;Zhejiang&quot;,&quot;Zhejiang&quot;]]">China</option>
<option value="Christmas Island" data-provinces="[]">Christmas Island</option>
<option value="Cocos (Keeling) Islands" data-provinces="[]">Cocos (Keeling) Islands</option>
<option value="Colombia" data-provinces="[[&quot;Amazonas&quot;,&quot;Amazonas&quot;],[&quot;Antioquia&quot;,&quot;Antioquia&quot;],[&quot;Arauca&quot;,&quot;Arauca&quot;],[&quot;Atlántico&quot;,&quot;Atlántico&quot;],[&quot;Bogotá, D.C.&quot;,&quot;Capital District&quot;],[&quot;Bolívar&quot;,&quot;Bolívar&quot;],[&quot;Boyacá&quot;,&quot;Boyacá&quot;],[&quot;Caldas&quot;,&quot;Caldas&quot;],[&quot;Caquetá&quot;,&quot;Caquetá&quot;],[&quot;Casanare&quot;,&quot;Casanare&quot;],[&quot;Cauca&quot;,&quot;Cauca&quot;],[&quot;Cesar&quot;,&quot;Cesar&quot;],[&quot;Chocó&quot;,&quot;Chocó&quot;],[&quot;Cundinamarca&quot;,&quot;Cundinamarca&quot;],[&quot;Córdoba&quot;,&quot;Córdoba&quot;],[&quot;Guainía&quot;,&quot;Guainía&quot;],[&quot;Guaviare&quot;,&quot;Guaviare&quot;],[&quot;Huila&quot;,&quot;Huila&quot;],[&quot;La Guajira&quot;,&quot;La Guajira&quot;],[&quot;Magdalena&quot;,&quot;Magdalena&quot;],[&quot;Meta&quot;,&quot;Meta&quot;],[&quot;Nariño&quot;,&quot;Nariño&quot;],[&quot;Norte de Santander&quot;,&quot;Norte de Santander&quot;],[&quot;Putumayo&quot;,&quot;Putumayo&quot;],[&quot;Quindío&quot;,&quot;Quindío&quot;],[&quot;Risaralda&quot;,&quot;Risaralda&quot;],[&quot;San Andrés, Providencia y Santa Catalina&quot;,&quot;San Andrés \u0026 Providencia&quot;],[&quot;Santander&quot;,&quot;Santander&quot;],[&quot;Sucre&quot;,&quot;Sucre&quot;],[&quot;Tolima&quot;,&quot;Tolima&quot;],[&quot;Valle del Cauca&quot;,&quot;Valle del Cauca&quot;],[&quot;Vaupés&quot;,&quot;Vaupés&quot;],[&quot;Vichada&quot;,&quot;Vichada&quot;]]">Colombia</option>
<option value="Comoros" data-provinces="[]">Comoros</option>
<option value="Congo" data-provinces="[]">Congo - Brazzaville</option>
<option value="Congo, The Democratic Republic Of The" data-provinces="[]">Congo - Kinshasa</option>
<option value="Cook Islands" data-provinces="[]">Cook Islands</option>
<option value="Costa Rica" data-provinces="[[&quot;Alajuela&quot;,&quot;Alajuela&quot;],[&quot;Cartago&quot;,&quot;Cartago&quot;],[&quot;Guanacaste&quot;,&quot;Guanacaste&quot;],[&quot;Heredia&quot;,&quot;Heredia&quot;],[&quot;Limón&quot;,&quot;Limón&quot;],[&quot;Puntarenas&quot;,&quot;Puntarenas&quot;],[&quot;San José&quot;,&quot;San José&quot;]]">Costa Rica</option>
<option value="Croatia" data-provinces="[]">Croatia</option>
<option value="Curaçao" data-provinces="[]">Curaçao</option>
<option value="Cyprus" data-provinces="[]">Cyprus</option>
<option value="Czech Republic" data-provinces="[]">Czechia</option>
<option value="Côte d'Ivoire" data-provinces="[]">Côte d’Ivoire</option>
<option value="Denmark" data-provinces="[]">Denmark</option>
<option value="Djibouti" data-provinces="[]">Djibouti</option>
<option value="Dominica" data-provinces="[]">Dominica</option>
<option value="Dominican Republic" data-provinces="[]">Dominican Republic</option>
<option value="Ecuador" data-provinces="[]">Ecuador</option>
<option value="Egypt" data-provinces="[[&quot;6th of October&quot;,&quot;6th of October&quot;],[&quot;Al Sharqia&quot;,&quot;Al Sharqia&quot;],[&quot;Alexandria&quot;,&quot;Alexandria&quot;],[&quot;Aswan&quot;,&quot;Aswan&quot;],[&quot;Asyut&quot;,&quot;Asyut&quot;],[&quot;Beheira&quot;,&quot;Beheira&quot;],[&quot;Beni Suef&quot;,&quot;Beni Suef&quot;],[&quot;Cairo&quot;,&quot;Cairo&quot;],[&quot;Dakahlia&quot;,&quot;Dakahlia&quot;],[&quot;Damietta&quot;,&quot;Damietta&quot;],[&quot;Faiyum&quot;,&quot;Faiyum&quot;],[&quot;Gharbia&quot;,&quot;Gharbia&quot;],[&quot;Giza&quot;,&quot;Giza&quot;],[&quot;Helwan&quot;,&quot;Helwan&quot;],[&quot;Ismailia&quot;,&quot;Ismailia&quot;],[&quot;Kafr el-Sheikh&quot;,&quot;Kafr el-Sheikh&quot;],[&quot;Luxor&quot;,&quot;Luxor&quot;],[&quot;Matrouh&quot;,&quot;Matrouh&quot;],[&quot;Minya&quot;,&quot;Minya&quot;],[&quot;Monufia&quot;,&quot;Monufia&quot;],[&quot;New Valley&quot;,&quot;New Valley&quot;],[&quot;North Sinai&quot;,&quot;North Sinai&quot;],[&quot;Port Said&quot;,&quot;Port Said&quot;],[&quot;Qalyubia&quot;,&quot;Qalyubia&quot;],[&quot;Qena&quot;,&quot;Qena&quot;],[&quot;Red Sea&quot;,&quot;Red Sea&quot;],[&quot;Sohag&quot;,&quot;Sohag&quot;],[&quot;South Sinai&quot;,&quot;South Sinai&quot;],[&quot;Suez&quot;,&quot;Suez&quot;]]">Egypt</option>
<option value="El Salvador" data-provinces="[[&quot;Ahuachapán&quot;,&quot;Ahuachapán&quot;],[&quot;Cabañas&quot;,&quot;Cabañas&quot;],[&quot;Chalatenango&quot;,&quot;Chalatenango&quot;],[&quot;Cuscatlán&quot;,&quot;Cuscatlán&quot;],[&quot;La Libertad&quot;,&quot;La Libertad&quot;],[&quot;La Paz&quot;,&quot;La Paz&quot;],[&quot;La Unión&quot;,&quot;La Unión&quot;],[&quot;Morazán&quot;,&quot;Morazán&quot;],[&quot;San Miguel&quot;,&quot;San Miguel&quot;],[&quot;San Salvador&quot;,&quot;San Salvador&quot;],[&quot;San Vicente&quot;,&quot;San Vicente&quot;],[&quot;Santa Ana&quot;,&quot;Santa Ana&quot;],[&quot;Sonsonate&quot;,&quot;Sonsonate&quot;],[&quot;Usulután&quot;,&quot;Usulután&quot;]]">El Salvador</option>
<option value="Equatorial Guinea" data-provinces="[]">Equatorial Guinea</option>
<option value="Eritrea" data-provinces="[]">Eritrea</option>
<option value="Estonia" data-provinces="[]">Estonia</option>
<option value="Eswatini" data-provinces="[]">Eswatini</option>
<option value="Ethiopia" data-provinces="[]">Ethiopia</option>
<option value="Falkland Islands (Malvinas)" data-provinces="[]">Falkland Islands</option>
<option value="Faroe Islands" data-provinces="[]">Faroe Islands</option>
<option value="Fiji" data-provinces="[]">Fiji</option>
<option value="Finland" data-provinces="[]">Finland</option>
<option value="France" data-provinces="[]">France</option>
<option value="French Guiana" data-provinces="[]">French Guiana</option>
<option value="French Polynesia" data-provinces="[]">French Polynesia</option>
<option value="French Southern Territories" data-provinces="[]">French Southern Territories</option>
<option value="Gabon" data-provinces="[]">Gabon</option>
<option value="Gambia" data-provinces="[]">Gambia</option>
<option value="Georgia" data-provinces="[]">Georgia</option>
<option value="Germany" data-provinces="[]">Germany</option>
<option value="Ghana" data-provinces="[]">Ghana</option>
<option value="Gibraltar" data-provinces="[]">Gibraltar</option>
<option value="Greece" data-provinces="[]">Greece</option>
<option value="Greenland" data-provinces="[]">Greenland</option>
<option value="Grenada" data-provinces="[]">Grenada</option>
<option value="Guadeloupe" data-provinces="[]">Guadeloupe</option>
<option value="Guatemala" data-provinces="[[&quot;Alta Verapaz&quot;,&quot;Alta Verapaz&quot;],[&quot;Baja Verapaz&quot;,&quot;Baja Verapaz&quot;],[&quot;Chimaltenango&quot;,&quot;Chimaltenango&quot;],[&quot;Chiquimula&quot;,&quot;Chiquimula&quot;],[&quot;El Progreso&quot;,&quot;El Progreso&quot;],[&quot;Escuintla&quot;,&quot;Escuintla&quot;],[&quot;Guatemala&quot;,&quot;Guatemala&quot;],[&quot;Huehuetenango&quot;,&quot;Huehuetenango&quot;],[&quot;Izabal&quot;,&quot;Izabal&quot;],[&quot;Jalapa&quot;,&quot;Jalapa&quot;],[&quot;Jutiapa&quot;,&quot;Jutiapa&quot;],[&quot;Petén&quot;,&quot;Petén&quot;],[&quot;Quetzaltenango&quot;,&quot;Quetzaltenango&quot;],[&quot;Quiché&quot;,&quot;Quiché&quot;],[&quot;Retalhuleu&quot;,&quot;Retalhuleu&quot;],[&quot;Sacatepéquez&quot;,&quot;Sacatepéquez&quot;],[&quot;San Marcos&quot;,&quot;San Marcos&quot;],[&quot;Santa Rosa&quot;,&quot;Santa Rosa&quot;],[&quot;Sololá&quot;,&quot;Sololá&quot;],[&quot;Suchitepéquez&quot;,&quot;Suchitepéquez&quot;],[&quot;Totonicapán&quot;,&quot;Totonicapán&quot;],[&quot;Zacapa&quot;,&quot;Zacapa&quot;]]">Guatemala</option>
<option value="Guernsey" data-provinces="[]">Guernsey</option>
<option value="Guinea" data-provinces="[]">Guinea</option>
<option value="Guinea Bissau" data-provinces="[]">Guinea-Bissau</option>
<option value="Guyana" data-provinces="[]">Guyana</option>
<option value="Haiti" data-provinces="[]">Haiti</option>
<option value="Honduras" data-provinces="[]">Honduras</option>
<option value="Hong Kong" data-provinces="[[&quot;Hong Kong Island&quot;,&quot;Hong Kong Island&quot;],[&quot;Kowloon&quot;,&quot;Kowloon&quot;],[&quot;New Territories&quot;,&quot;New Territories&quot;]]">Hong Kong SAR</option>
<option value="Hungary" data-provinces="[]">Hungary</option>
<option value="Iceland" data-provinces="[]">Iceland</option>
<option value="India" data-provinces="[[&quot;Andaman and Nicobar Islands&quot;,&quot;Andaman and Nicobar Islands&quot;],[&quot;Andhra Pradesh&quot;,&quot;Andhra Pradesh&quot;],[&quot;Arunachal Pradesh&quot;,&quot;Arunachal Pradesh&quot;],[&quot;Assam&quot;,&quot;Assam&quot;],[&quot;Bihar&quot;,&quot;Bihar&quot;],[&quot;Chandigarh&quot;,&quot;Chandigarh&quot;],[&quot;Chhattisgarh&quot;,&quot;Chhattisgarh&quot;],[&quot;Dadra and Nagar Haveli&quot;,&quot;Dadra and Nagar Haveli&quot;],[&quot;Daman and Diu&quot;,&quot;Daman and Diu&quot;],[&quot;Delhi&quot;,&quot;Delhi&quot;],[&quot;Goa&quot;,&quot;Goa&quot;],[&quot;Gujarat&quot;,&quot;Gujarat&quot;],[&quot;Haryana&quot;,&quot;Haryana&quot;],[&quot;Himachal Pradesh&quot;,&quot;Himachal Pradesh&quot;],[&quot;Jammu and Kashmir&quot;,&quot;Jammu and Kashmir&quot;],[&quot;Jharkhand&quot;,&quot;Jharkhand&quot;],[&quot;Karnataka&quot;,&quot;Karnataka&quot;],[&quot;Kerala&quot;,&quot;Kerala&quot;],[&quot;Ladakh&quot;,&quot;Ladakh&quot;],[&quot;Lakshadweep&quot;,&quot;Lakshadweep&quot;],[&quot;Madhya Pradesh&quot;,&quot;Madhya Pradesh&quot;],[&quot;Maharashtra&quot;,&quot;Maharashtra&quot;],[&quot;Manipur&quot;,&quot;Manipur&quot;],[&quot;Meghalaya&quot;,&quot;Meghalaya&quot;],[&quot;Mizoram&quot;,&quot;Mizoram&quot;],[&quot;Nagaland&quot;,&quot;Nagaland&quot;],[&quot;Odisha&quot;,&quot;Odisha&quot;],[&quot;Puducherry&quot;,&quot;Puducherry&quot;],[&quot;Punjab&quot;,&quot;Punjab&quot;],[&quot;Rajasthan&quot;,&quot;Rajasthan&quot;],[&quot;Sikkim&quot;,&quot;Sikkim&quot;],[&quot;Tamil Nadu&quot;,&quot;Tamil Nadu&quot;],[&quot;Telangana&quot;,&quot;Telangana&quot;],[&quot;Tripura&quot;,&quot;Tripura&quot;],[&quot;Uttar Pradesh&quot;,&quot;Uttar Pradesh&quot;],[&quot;Uttarakhand&quot;,&quot;Uttarakhand&quot;],[&quot;West Bengal&quot;,&quot;West Bengal&quot;]]">India</option>
<option value="Indonesia" data-provinces="[[&quot;Aceh&quot;,&quot;Aceh&quot;],[&quot;Bali&quot;,&quot;Bali&quot;],[&quot;Bangka Belitung&quot;,&quot;Bangka–Belitung Islands&quot;],[&quot;Banten&quot;,&quot;Banten&quot;],[&quot;Bengkulu&quot;,&quot;Bengkulu&quot;],[&quot;Gorontalo&quot;,&quot;Gorontalo&quot;],[&quot;Jakarta&quot;,&quot;Jakarta&quot;],[&quot;Jambi&quot;,&quot;Jambi&quot;],[&quot;Jawa Barat&quot;,&quot;West Java&quot;],[&quot;Jawa Tengah&quot;,&quot;Central Java&quot;],[&quot;Jawa Timur&quot;,&quot;East Java&quot;],[&quot;Kalimantan Barat&quot;,&quot;West Kalimantan&quot;],[&quot;Kalimantan Selatan&quot;,&quot;South Kalimantan&quot;],[&quot;Kalimantan Tengah&quot;,&quot;Central Kalimantan&quot;],[&quot;Kalimantan Timur&quot;,&quot;East Kalimantan&quot;],[&quot;Kalimantan Utara&quot;,&quot;North Kalimantan&quot;],[&quot;Kepulauan Riau&quot;,&quot;Riau Islands&quot;],[&quot;Lampung&quot;,&quot;Lampung&quot;],[&quot;Maluku&quot;,&quot;Maluku&quot;],[&quot;Maluku Utara&quot;,&quot;North Maluku&quot;],[&quot;North Sumatra&quot;,&quot;North Sumatra&quot;],[&quot;Nusa Tenggara Barat&quot;,&quot;West Nusa Tenggara&quot;],[&quot;Nusa Tenggara Timur&quot;,&quot;East Nusa Tenggara&quot;],[&quot;Papua&quot;,&quot;Papua&quot;],[&quot;Papua Barat&quot;,&quot;West Papua&quot;],[&quot;Riau&quot;,&quot;Riau&quot;],[&quot;South Sumatra&quot;,&quot;South Sumatra&quot;],[&quot;Sulawesi Barat&quot;,&quot;West Sulawesi&quot;],[&quot;Sulawesi Selatan&quot;,&quot;South Sulawesi&quot;],[&quot;Sulawesi Tengah&quot;,&quot;Central Sulawesi&quot;],[&quot;Sulawesi Tenggara&quot;,&quot;Southeast Sulawesi&quot;],[&quot;Sulawesi Utara&quot;,&quot;North Sulawesi&quot;],[&quot;West Sumatra&quot;,&quot;West Sumatra&quot;],[&quot;Yogyakarta&quot;,&quot;Yogyakarta&quot;]]">Indonesia</option>
<option value="Iraq" data-provinces="[]">Iraq</option>
<option value="Ireland" data-provinces="[[&quot;Carlow&quot;,&quot;Carlow&quot;],[&quot;Cavan&quot;,&quot;Cavan&quot;],[&quot;Clare&quot;,&quot;Clare&quot;],[&quot;Cork&quot;,&quot;Cork&quot;],[&quot;Donegal&quot;,&quot;Donegal&quot;],[&quot;Dublin&quot;,&quot;Dublin&quot;],[&quot;Galway&quot;,&quot;Galway&quot;],[&quot;Kerry&quot;,&quot;Kerry&quot;],[&quot;Kildare&quot;,&quot;Kildare&quot;],[&quot;Kilkenny&quot;,&quot;Kilkenny&quot;],[&quot;Laois&quot;,&quot;Laois&quot;],[&quot;Leitrim&quot;,&quot;Leitrim&quot;],[&quot;Limerick&quot;,&quot;Limerick&quot;],[&quot;Longford&quot;,&quot;Longford&quot;],[&quot;Louth&quot;,&quot;Louth&quot;],[&quot;Mayo&quot;,&quot;Mayo&quot;],[&quot;Meath&quot;,&quot;Meath&quot;],[&quot;Monaghan&quot;,&quot;Monaghan&quot;],[&quot;Offaly&quot;,&quot;Offaly&quot;],[&quot;Roscommon&quot;,&quot;Roscommon&quot;],[&quot;Sligo&quot;,&quot;Sligo&quot;],[&quot;Tipperary&quot;,&quot;Tipperary&quot;],[&quot;Waterford&quot;,&quot;Waterford&quot;],[&quot;Westmeath&quot;,&quot;Westmeath&quot;],[&quot;Wexford&quot;,&quot;Wexford&quot;],[&quot;Wicklow&quot;,&quot;Wicklow&quot;]]">Ireland</option>
<option value="Isle Of Man" data-provinces="[]">Isle of Man</option>
<option value="Israel" data-provinces="[]">Israel</option>
<option value="Italy" data-provinces="[[&quot;Agrigento&quot;,&quot;Agrigento&quot;],[&quot;Alessandria&quot;,&quot;Alessandria&quot;],[&quot;Ancona&quot;,&quot;Ancona&quot;],[&quot;Aosta&quot;,&quot;Aosta Valley&quot;],[&quot;Arezzo&quot;,&quot;Arezzo&quot;],[&quot;Ascoli Piceno&quot;,&quot;Ascoli Piceno&quot;],[&quot;Asti&quot;,&quot;Asti&quot;],[&quot;Avellino&quot;,&quot;Avellino&quot;],[&quot;Bari&quot;,&quot;Bari&quot;],[&quot;Barletta-Andria-Trani&quot;,&quot;Barletta-Andria-Trani&quot;],[&quot;Belluno&quot;,&quot;Belluno&quot;],[&quot;Benevento&quot;,&quot;Benevento&quot;],[&quot;Bergamo&quot;,&quot;Bergamo&quot;],[&quot;Biella&quot;,&quot;Biella&quot;],[&quot;Bologna&quot;,&quot;Bologna&quot;],[&quot;Bolzano&quot;,&quot;South Tyrol&quot;],[&quot;Brescia&quot;,&quot;Brescia&quot;],[&quot;Brindisi&quot;,&quot;Brindisi&quot;],[&quot;Cagliari&quot;,&quot;Cagliari&quot;],[&quot;Caltanissetta&quot;,&quot;Caltanissetta&quot;],[&quot;Campobasso&quot;,&quot;Campobasso&quot;],[&quot;Carbonia-Iglesias&quot;,&quot;Carbonia-Iglesias&quot;],[&quot;Caserta&quot;,&quot;Caserta&quot;],[&quot;Catania&quot;,&quot;Catania&quot;],[&quot;Catanzaro&quot;,&quot;Catanzaro&quot;],[&quot;Chieti&quot;,&quot;Chieti&quot;],[&quot;Como&quot;,&quot;Como&quot;],[&quot;Cosenza&quot;,&quot;Cosenza&quot;],[&quot;Cremona&quot;,&quot;Cremona&quot;],[&quot;Crotone&quot;,&quot;Crotone&quot;],[&quot;Cuneo&quot;,&quot;Cuneo&quot;],[&quot;Enna&quot;,&quot;Enna&quot;],[&quot;Fermo&quot;,&quot;Fermo&quot;],[&quot;Ferrara&quot;,&quot;Ferrara&quot;],[&quot;Firenze&quot;,&quot;Florence&quot;],[&quot;Foggia&quot;,&quot;Foggia&quot;],[&quot;Forlì-Cesena&quot;,&quot;Forlì-Cesena&quot;],[&quot;Frosinone&quot;,&quot;Frosinone&quot;],[&quot;Genova&quot;,&quot;Genoa&quot;],[&quot;Gorizia&quot;,&quot;Gorizia&quot;],[&quot;Grosseto&quot;,&quot;Grosseto&quot;],[&quot;Imperia&quot;,&quot;Imperia&quot;],[&quot;Isernia&quot;,&quot;Isernia&quot;],[&quot;L&#39;Aquila&quot;,&quot;L’Aquila&quot;],[&quot;La Spezia&quot;,&quot;La Spezia&quot;],[&quot;Latina&quot;,&quot;Latina&quot;],[&quot;Lecce&quot;,&quot;Lecce&quot;],[&quot;Lecco&quot;,&quot;Lecco&quot;],[&quot;Livorno&quot;,&quot;Livorno&quot;],[&quot;Lodi&quot;,&quot;Lodi&quot;],[&quot;Lucca&quot;,&quot;Lucca&quot;],[&quot;Macerata&quot;,&quot;Macerata&quot;],[&quot;Mantova&quot;,&quot;Mantua&quot;],[&quot;Massa-Carrara&quot;,&quot;Massa and Carrara&quot;],[&quot;Matera&quot;,&quot;Matera&quot;],[&quot;Medio Campidano&quot;,&quot;Medio Campidano&quot;],[&quot;Messina&quot;,&quot;Messina&quot;],[&quot;Milano&quot;,&quot;Milan&quot;],[&quot;Modena&quot;,&quot;Modena&quot;],[&quot;Monza e Brianza&quot;,&quot;Monza and Brianza&quot;],[&quot;Napoli&quot;,&quot;Naples&quot;],[&quot;Novara&quot;,&quot;Novara&quot;],[&quot;Nuoro&quot;,&quot;Nuoro&quot;],[&quot;Ogliastra&quot;,&quot;Ogliastra&quot;],[&quot;Olbia-Tempio&quot;,&quot;Olbia-Tempio&quot;],[&quot;Oristano&quot;,&quot;Oristano&quot;],[&quot;Padova&quot;,&quot;Padua&quot;],[&quot;Palermo&quot;,&quot;Palermo&quot;],[&quot;Parma&quot;,&quot;Parma&quot;],[&quot;Pavia&quot;,&quot;Pavia&quot;],[&quot;Perugia&quot;,&quot;Perugia&quot;],[&quot;Pesaro e Urbino&quot;,&quot;Pesaro and Urbino&quot;],[&quot;Pescara&quot;,&quot;Pescara&quot;],[&quot;Piacenza&quot;,&quot;Piacenza&quot;],[&quot;Pisa&quot;,&quot;Pisa&quot;],[&quot;Pistoia&quot;,&quot;Pistoia&quot;],[&quot;Pordenone&quot;,&quot;Pordenone&quot;],[&quot;Potenza&quot;,&quot;Potenza&quot;],[&quot;Prato&quot;,&quot;Prato&quot;],[&quot;Ragusa&quot;,&quot;Ragusa&quot;],[&quot;Ravenna&quot;,&quot;Ravenna&quot;],[&quot;Reggio Calabria&quot;,&quot;Reggio Calabria&quot;],[&quot;Reggio Emilia&quot;,&quot;Reggio Emilia&quot;],[&quot;Rieti&quot;,&quot;Rieti&quot;],[&quot;Rimini&quot;,&quot;Rimini&quot;],[&quot;Roma&quot;,&quot;Rome&quot;],[&quot;Rovigo&quot;,&quot;Rovigo&quot;],[&quot;Salerno&quot;,&quot;Salerno&quot;],[&quot;Sassari&quot;,&quot;Sassari&quot;],[&quot;Savona&quot;,&quot;Savona&quot;],[&quot;Siena&quot;,&quot;Siena&quot;],[&quot;Siracusa&quot;,&quot;Syracuse&quot;],[&quot;Sondrio&quot;,&quot;Sondrio&quot;],[&quot;Taranto&quot;,&quot;Taranto&quot;],[&quot;Teramo&quot;,&quot;Teramo&quot;],[&quot;Terni&quot;,&quot;Terni&quot;],[&quot;Torino&quot;,&quot;Turin&quot;],[&quot;Trapani&quot;,&quot;Trapani&quot;],[&quot;Trento&quot;,&quot;Trentino&quot;],[&quot;Treviso&quot;,&quot;Treviso&quot;],[&quot;Trieste&quot;,&quot;Trieste&quot;],[&quot;Udine&quot;,&quot;Udine&quot;],[&quot;Varese&quot;,&quot;Varese&quot;],[&quot;Venezia&quot;,&quot;Venice&quot;],[&quot;Verbano-Cusio-Ossola&quot;,&quot;Verbano-Cusio-Ossola&quot;],[&quot;Vercelli&quot;,&quot;Vercelli&quot;],[&quot;Verona&quot;,&quot;Verona&quot;],[&quot;Vibo Valentia&quot;,&quot;Vibo Valentia&quot;],[&quot;Vicenza&quot;,&quot;Vicenza&quot;],[&quot;Viterbo&quot;,&quot;Viterbo&quot;]]">Italy</option>
<option value="Jamaica" data-provinces="[]">Jamaica</option>
<option value="Japan" data-provinces="[[&quot;Aichi&quot;,&quot;Aichi&quot;],[&quot;Akita&quot;,&quot;Akita&quot;],[&quot;Aomori&quot;,&quot;Aomori&quot;],[&quot;Chiba&quot;,&quot;Chiba&quot;],[&quot;Ehime&quot;,&quot;Ehime&quot;],[&quot;Fukui&quot;,&quot;Fukui&quot;],[&quot;Fukuoka&quot;,&quot;Fukuoka&quot;],[&quot;Fukushima&quot;,&quot;Fukushima&quot;],[&quot;Gifu&quot;,&quot;Gifu&quot;],[&quot;Gunma&quot;,&quot;Gunma&quot;],[&quot;Hiroshima&quot;,&quot;Hiroshima&quot;],[&quot;Hokkaidō&quot;,&quot;Hokkaido&quot;],[&quot;Hyōgo&quot;,&quot;Hyogo&quot;],[&quot;Ibaraki&quot;,&quot;Ibaraki&quot;],[&quot;Ishikawa&quot;,&quot;Ishikawa&quot;],[&quot;Iwate&quot;,&quot;Iwate&quot;],[&quot;Kagawa&quot;,&quot;Kagawa&quot;],[&quot;Kagoshima&quot;,&quot;Kagoshima&quot;],[&quot;Kanagawa&quot;,&quot;Kanagawa&quot;],[&quot;Kumamoto&quot;,&quot;Kumamoto&quot;],[&quot;Kyōto&quot;,&quot;Kyoto&quot;],[&quot;Kōchi&quot;,&quot;Kochi&quot;],[&quot;Mie&quot;,&quot;Mie&quot;],[&quot;Miyagi&quot;,&quot;Miyagi&quot;],[&quot;Miyazaki&quot;,&quot;Miyazaki&quot;],[&quot;Nagano&quot;,&quot;Nagano&quot;],[&quot;Nagasaki&quot;,&quot;Nagasaki&quot;],[&quot;Nara&quot;,&quot;Nara&quot;],[&quot;Niigata&quot;,&quot;Niigata&quot;],[&quot;Okayama&quot;,&quot;Okayama&quot;],[&quot;Okinawa&quot;,&quot;Okinawa&quot;],[&quot;Saga&quot;,&quot;Saga&quot;],[&quot;Saitama&quot;,&quot;Saitama&quot;],[&quot;Shiga&quot;,&quot;Shiga&quot;],[&quot;Shimane&quot;,&quot;Shimane&quot;],[&quot;Shizuoka&quot;,&quot;Shizuoka&quot;],[&quot;Tochigi&quot;,&quot;Tochigi&quot;],[&quot;Tokushima&quot;,&quot;Tokushima&quot;],[&quot;Tottori&quot;,&quot;Tottori&quot;],[&quot;Toyama&quot;,&quot;Toyama&quot;],[&quot;Tōkyō&quot;,&quot;Tokyo&quot;],[&quot;Wakayama&quot;,&quot;Wakayama&quot;],[&quot;Yamagata&quot;,&quot;Yamagata&quot;],[&quot;Yamaguchi&quot;,&quot;Yamaguchi&quot;],[&quot;Yamanashi&quot;,&quot;Yamanashi&quot;],[&quot;Ōita&quot;,&quot;Oita&quot;],[&quot;Ōsaka&quot;,&quot;Osaka&quot;]]">Japan</option>
<option value="Jersey" data-provinces="[]">Jersey</option>
<option value="Jordan" data-provinces="[]">Jordan</option>
<option value="Kazakhstan" data-provinces="[]">Kazakhstan</option>
<option value="Kenya" data-provinces="[]">Kenya</option>
<option value="Kiribati" data-provinces="[]">Kiribati</option>
<option value="Kosovo" data-provinces="[]">Kosovo</option>
<option value="Kuwait" data-provinces="[[&quot;Al Ahmadi&quot;,&quot;Al Ahmadi&quot;],[&quot;Al Asimah&quot;,&quot;Al Asimah&quot;],[&quot;Al Farwaniyah&quot;,&quot;Al Farwaniyah&quot;],[&quot;Al Jahra&quot;,&quot;Al Jahra&quot;],[&quot;Hawalli&quot;,&quot;Hawalli&quot;],[&quot;Mubarak Al-Kabeer&quot;,&quot;Mubarak Al-Kabeer&quot;]]">Kuwait</option>
<option value="Kyrgyzstan" data-provinces="[]">Kyrgyzstan</option>
<option value="Lao People's Democratic Republic" data-provinces="[]">Laos</option>
<option value="Latvia" data-provinces="[]">Latvia</option>
<option value="Lebanon" data-provinces="[]">Lebanon</option>
<option value="Lesotho" data-provinces="[]">Lesotho</option>
<option value="Liberia" data-provinces="[]">Liberia</option>
<option value="Libyan Arab Jamahiriya" data-provinces="[]">Libya</option>
<option value="Liechtenstein" data-provinces="[]">Liechtenstein</option>
<option value="Lithuania" data-provinces="[]">Lithuania</option>
<option value="Luxembourg" data-provinces="[]">Luxembourg</option>
<option value="Macao" data-provinces="[]">Macao SAR</option>
<option value="Madagascar" data-provinces="[]">Madagascar</option>
<option value="Malawi" data-provinces="[]">Malawi</option>
<option value="Malaysia" data-provinces="[[&quot;Johor&quot;,&quot;Johor&quot;],[&quot;Kedah&quot;,&quot;Kedah&quot;],[&quot;Kelantan&quot;,&quot;Kelantan&quot;],[&quot;Kuala Lumpur&quot;,&quot;Kuala Lumpur&quot;],[&quot;Labuan&quot;,&quot;Labuan&quot;],[&quot;Melaka&quot;,&quot;Malacca&quot;],[&quot;Negeri Sembilan&quot;,&quot;Negeri Sembilan&quot;],[&quot;Pahang&quot;,&quot;Pahang&quot;],[&quot;Penang&quot;,&quot;Penang&quot;],[&quot;Perak&quot;,&quot;Perak&quot;],[&quot;Perlis&quot;,&quot;Perlis&quot;],[&quot;Putrajaya&quot;,&quot;Putrajaya&quot;],[&quot;Sabah&quot;,&quot;Sabah&quot;],[&quot;Sarawak&quot;,&quot;Sarawak&quot;],[&quot;Selangor&quot;,&quot;Selangor&quot;],[&quot;Terengganu&quot;,&quot;Terengganu&quot;]]">Malaysia</option>
<option value="Maldives" data-provinces="[]">Maldives</option>
<option value="Mali" data-provinces="[]">Mali</option>
<option value="Malta" data-provinces="[]">Malta</option>
<option value="Martinique" data-provinces="[]">Martinique</option>
<option value="Mauritania" data-provinces="[]">Mauritania</option>
<option value="Mauritius" data-provinces="[]">Mauritius</option>
<option value="Mayotte" data-provinces="[]">Mayotte</option>
<option value="Mexico" data-provinces="[[&quot;Aguascalientes&quot;,&quot;Aguascalientes&quot;],[&quot;Baja California&quot;,&quot;Baja California&quot;],[&quot;Baja California Sur&quot;,&quot;Baja California Sur&quot;],[&quot;Campeche&quot;,&quot;Campeche&quot;],[&quot;Chiapas&quot;,&quot;Chiapas&quot;],[&quot;Chihuahua&quot;,&quot;Chihuahua&quot;],[&quot;Ciudad de México&quot;,&quot;Ciudad de Mexico&quot;],[&quot;Coahuila&quot;,&quot;Coahuila&quot;],[&quot;Colima&quot;,&quot;Colima&quot;],[&quot;Durango&quot;,&quot;Durango&quot;],[&quot;Guanajuato&quot;,&quot;Guanajuato&quot;],[&quot;Guerrero&quot;,&quot;Guerrero&quot;],[&quot;Hidalgo&quot;,&quot;Hidalgo&quot;],[&quot;Jalisco&quot;,&quot;Jalisco&quot;],[&quot;Michoacán&quot;,&quot;Michoacán&quot;],[&quot;Morelos&quot;,&quot;Morelos&quot;],[&quot;México&quot;,&quot;Mexico State&quot;],[&quot;Nayarit&quot;,&quot;Nayarit&quot;],[&quot;Nuevo León&quot;,&quot;Nuevo León&quot;],[&quot;Oaxaca&quot;,&quot;Oaxaca&quot;],[&quot;Puebla&quot;,&quot;Puebla&quot;],[&quot;Querétaro&quot;,&quot;Querétaro&quot;],[&quot;Quintana Roo&quot;,&quot;Quintana Roo&quot;],[&quot;San Luis Potosí&quot;,&quot;San Luis Potosí&quot;],[&quot;Sinaloa&quot;,&quot;Sinaloa&quot;],[&quot;Sonora&quot;,&quot;Sonora&quot;],[&quot;Tabasco&quot;,&quot;Tabasco&quot;],[&quot;Tamaulipas&quot;,&quot;Tamaulipas&quot;],[&quot;Tlaxcala&quot;,&quot;Tlaxcala&quot;],[&quot;Veracruz&quot;,&quot;Veracruz&quot;],[&quot;Yucatán&quot;,&quot;Yucatán&quot;],[&quot;Zacatecas&quot;,&quot;Zacatecas&quot;]]">Mexico</option>
<option value="Moldova, Republic of" data-provinces="[]">Moldova</option>
<option value="Monaco" data-provinces="[]">Monaco</option>
<option value="Mongolia" data-provinces="[]">Mongolia</option>
<option value="Montenegro" data-provinces="[]">Montenegro</option>
<option value="Montserrat" data-provinces="[]">Montserrat</option>
<option value="Morocco" data-provinces="[]">Morocco</option>
<option value="Mozambique" data-provinces="[]">Mozambique</option>
<option value="Myanmar" data-provinces="[]">Myanmar (Burma)</option>
<option value="Namibia" data-provinces="[]">Namibia</option>
<option value="Nauru" data-provinces="[]">Nauru</option>
<option value="Nepal" data-provinces="[]">Nepal</option>
<option value="Netherlands" data-provinces="[]">Netherlands</option>
<option value="New Caledonia" data-provinces="[]">New Caledonia</option>
<option value="New Zealand" data-provinces="[[&quot;Auckland&quot;,&quot;Auckland&quot;],[&quot;Bay of Plenty&quot;,&quot;Bay of Plenty&quot;],[&quot;Canterbury&quot;,&quot;Canterbury&quot;],[&quot;Chatham Islands&quot;,&quot;Chatham Islands&quot;],[&quot;Gisborne&quot;,&quot;Gisborne&quot;],[&quot;Hawke&#39;s Bay&quot;,&quot;Hawke’s Bay&quot;],[&quot;Manawatu-Wanganui&quot;,&quot;Manawatū-Whanganui&quot;],[&quot;Marlborough&quot;,&quot;Marlborough&quot;],[&quot;Nelson&quot;,&quot;Nelson&quot;],[&quot;Northland&quot;,&quot;Northland&quot;],[&quot;Otago&quot;,&quot;Otago&quot;],[&quot;Southland&quot;,&quot;Southland&quot;],[&quot;Taranaki&quot;,&quot;Taranaki&quot;],[&quot;Tasman&quot;,&quot;Tasman&quot;],[&quot;Waikato&quot;,&quot;Waikato&quot;],[&quot;Wellington&quot;,&quot;Wellington&quot;],[&quot;West Coast&quot;,&quot;West Coast&quot;]]">New Zealand</option>
<option value="Nicaragua" data-provinces="[]">Nicaragua</option>
<option value="Niger" data-provinces="[]">Niger</option>
<option value="Nigeria" data-provinces="[[&quot;Abia&quot;,&quot;Abia&quot;],[&quot;Abuja Federal Capital Territory&quot;,&quot;Federal Capital Territory&quot;],[&quot;Adamawa&quot;,&quot;Adamawa&quot;],[&quot;Akwa Ibom&quot;,&quot;Akwa Ibom&quot;],[&quot;Anambra&quot;,&quot;Anambra&quot;],[&quot;Bauchi&quot;,&quot;Bauchi&quot;],[&quot;Bayelsa&quot;,&quot;Bayelsa&quot;],[&quot;Benue&quot;,&quot;Benue&quot;],[&quot;Borno&quot;,&quot;Borno&quot;],[&quot;Cross River&quot;,&quot;Cross River&quot;],[&quot;Delta&quot;,&quot;Delta&quot;],[&quot;Ebonyi&quot;,&quot;Ebonyi&quot;],[&quot;Edo&quot;,&quot;Edo&quot;],[&quot;Ekiti&quot;,&quot;Ekiti&quot;],[&quot;Enugu&quot;,&quot;Enugu&quot;],[&quot;Gombe&quot;,&quot;Gombe&quot;],[&quot;Imo&quot;,&quot;Imo&quot;],[&quot;Jigawa&quot;,&quot;Jigawa&quot;],[&quot;Kaduna&quot;,&quot;Kaduna&quot;],[&quot;Kano&quot;,&quot;Kano&quot;],[&quot;Katsina&quot;,&quot;Katsina&quot;],[&quot;Kebbi&quot;,&quot;Kebbi&quot;],[&quot;Kogi&quot;,&quot;Kogi&quot;],[&quot;Kwara&quot;,&quot;Kwara&quot;],[&quot;Lagos&quot;,&quot;Lagos&quot;],[&quot;Nasarawa&quot;,&quot;Nasarawa&quot;],[&quot;Niger&quot;,&quot;Niger&quot;],[&quot;Ogun&quot;,&quot;Ogun&quot;],[&quot;Ondo&quot;,&quot;Ondo&quot;],[&quot;Osun&quot;,&quot;Osun&quot;],[&quot;Oyo&quot;,&quot;Oyo&quot;],[&quot;Plateau&quot;,&quot;Plateau&quot;],[&quot;Rivers&quot;,&quot;Rivers&quot;],[&quot;Sokoto&quot;,&quot;Sokoto&quot;],[&quot;Taraba&quot;,&quot;Taraba&quot;],[&quot;Yobe&quot;,&quot;Yobe&quot;],[&quot;Zamfara&quot;,&quot;Zamfara&quot;]]">Nigeria</option>
<option value="Niue" data-provinces="[]">Niue</option>
<option value="Norfolk Island" data-provinces="[]">Norfolk Island</option>
<option value="North Macedonia" data-provinces="[]">North Macedonia</option>
<option value="Norway" data-provinces="[]">Norway</option>
<option value="Oman" data-provinces="[]">Oman</option>
<option value="Pakistan" data-provinces="[]">Pakistan</option>
<option value="Palestinian Territory, Occupied" data-provinces="[]">Palestinian Territories</option>
<option value="Panama" data-provinces="[[&quot;Bocas del Toro&quot;,&quot;Bocas del Toro&quot;],[&quot;Chiriquí&quot;,&quot;Chiriquí&quot;],[&quot;Coclé&quot;,&quot;Coclé&quot;],[&quot;Colón&quot;,&quot;Colón&quot;],[&quot;Darién&quot;,&quot;Darién&quot;],[&quot;Emberá&quot;,&quot;Emberá&quot;],[&quot;Herrera&quot;,&quot;Herrera&quot;],[&quot;Kuna Yala&quot;,&quot;Guna Yala&quot;],[&quot;Los Santos&quot;,&quot;Los Santos&quot;],[&quot;Ngöbe-Buglé&quot;,&quot;Ngöbe-Buglé&quot;],[&quot;Panamá&quot;,&quot;Panamá&quot;],[&quot;Panamá Oeste&quot;,&quot;West Panamá&quot;],[&quot;Veraguas&quot;,&quot;Veraguas&quot;]]">Panama</option>
<option value="Papua New Guinea" data-provinces="[]">Papua New Guinea</option>
<option value="Paraguay" data-provinces="[]">Paraguay</option>
<option value="Peru" data-provinces="[[&quot;Amazonas&quot;,&quot;Amazonas&quot;],[&quot;Apurímac&quot;,&quot;Apurímac&quot;],[&quot;Arequipa&quot;,&quot;Arequipa&quot;],[&quot;Ayacucho&quot;,&quot;Ayacucho&quot;],[&quot;Cajamarca&quot;,&quot;Cajamarca&quot;],[&quot;Callao&quot;,&quot;El Callao&quot;],[&quot;Cuzco&quot;,&quot;Cusco&quot;],[&quot;Huancavelica&quot;,&quot;Huancavelica&quot;],[&quot;Huánuco&quot;,&quot;Huánuco&quot;],[&quot;Ica&quot;,&quot;Ica&quot;],[&quot;Junín&quot;,&quot;Junín&quot;],[&quot;La Libertad&quot;,&quot;La Libertad&quot;],[&quot;Lambayeque&quot;,&quot;Lambayeque&quot;],[&quot;Lima (departamento)&quot;,&quot;Lima (Department)&quot;],[&quot;Lima (provincia)&quot;,&quot;Lima (Metropolitan)&quot;],[&quot;Loreto&quot;,&quot;Loreto&quot;],[&quot;Madre de Dios&quot;,&quot;Madre de Dios&quot;],[&quot;Moquegua&quot;,&quot;Moquegua&quot;],[&quot;Pasco&quot;,&quot;Pasco&quot;],[&quot;Piura&quot;,&quot;Piura&quot;],[&quot;Puno&quot;,&quot;Puno&quot;],[&quot;San Martín&quot;,&quot;San Martín&quot;],[&quot;Tacna&quot;,&quot;Tacna&quot;],[&quot;Tumbes&quot;,&quot;Tumbes&quot;],[&quot;Ucayali&quot;,&quot;Ucayali&quot;],[&quot;Áncash&quot;,&quot;Ancash&quot;]]">Peru</option>
<option value="Philippines" data-provinces="[[&quot;Abra&quot;,&quot;Abra&quot;],[&quot;Agusan del Norte&quot;,&quot;Agusan del Norte&quot;],[&quot;Agusan del Sur&quot;,&quot;Agusan del Sur&quot;],[&quot;Aklan&quot;,&quot;Aklan&quot;],[&quot;Albay&quot;,&quot;Albay&quot;],[&quot;Antique&quot;,&quot;Antique&quot;],[&quot;Apayao&quot;,&quot;Apayao&quot;],[&quot;Aurora&quot;,&quot;Aurora&quot;],[&quot;Basilan&quot;,&quot;Basilan&quot;],[&quot;Bataan&quot;,&quot;Bataan&quot;],[&quot;Batanes&quot;,&quot;Batanes&quot;],[&quot;Batangas&quot;,&quot;Batangas&quot;],[&quot;Benguet&quot;,&quot;Benguet&quot;],[&quot;Biliran&quot;,&quot;Biliran&quot;],[&quot;Bohol&quot;,&quot;Bohol&quot;],[&quot;Bukidnon&quot;,&quot;Bukidnon&quot;],[&quot;Bulacan&quot;,&quot;Bulacan&quot;],[&quot;Cagayan&quot;,&quot;Cagayan&quot;],[&quot;Camarines Norte&quot;,&quot;Camarines Norte&quot;],[&quot;Camarines Sur&quot;,&quot;Camarines Sur&quot;],[&quot;Camiguin&quot;,&quot;Camiguin&quot;],[&quot;Capiz&quot;,&quot;Capiz&quot;],[&quot;Catanduanes&quot;,&quot;Catanduanes&quot;],[&quot;Cavite&quot;,&quot;Cavite&quot;],[&quot;Cebu&quot;,&quot;Cebu&quot;],[&quot;Cotabato&quot;,&quot;Cotabato&quot;],[&quot;Davao Occidental&quot;,&quot;Davao Occidental&quot;],[&quot;Davao Oriental&quot;,&quot;Davao Oriental&quot;],[&quot;Davao de Oro&quot;,&quot;Compostela Valley&quot;],[&quot;Davao del Norte&quot;,&quot;Davao del Norte&quot;],[&quot;Davao del Sur&quot;,&quot;Davao del Sur&quot;],[&quot;Dinagat Islands&quot;,&quot;Dinagat Islands&quot;],[&quot;Eastern Samar&quot;,&quot;Eastern Samar&quot;],[&quot;Guimaras&quot;,&quot;Guimaras&quot;],[&quot;Ifugao&quot;,&quot;Ifugao&quot;],[&quot;Ilocos Norte&quot;,&quot;Ilocos Norte&quot;],[&quot;Ilocos Sur&quot;,&quot;Ilocos Sur&quot;],[&quot;Iloilo&quot;,&quot;Iloilo&quot;],[&quot;Isabela&quot;,&quot;Isabela&quot;],[&quot;Kalinga&quot;,&quot;Kalinga&quot;],[&quot;La Union&quot;,&quot;La Union&quot;],[&quot;Laguna&quot;,&quot;Laguna&quot;],[&quot;Lanao del Norte&quot;,&quot;Lanao del Norte&quot;],[&quot;Lanao del Sur&quot;,&quot;Lanao del Sur&quot;],[&quot;Leyte&quot;,&quot;Leyte&quot;],[&quot;Maguindanao&quot;,&quot;Maguindanao&quot;],[&quot;Marinduque&quot;,&quot;Marinduque&quot;],[&quot;Masbate&quot;,&quot;Masbate&quot;],[&quot;Metro Manila&quot;,&quot;Metro Manila&quot;],[&quot;Misamis Occidental&quot;,&quot;Misamis Occidental&quot;],[&quot;Misamis Oriental&quot;,&quot;Misamis Oriental&quot;],[&quot;Mountain Province&quot;,&quot;Mountain&quot;],[&quot;Negros Occidental&quot;,&quot;Negros Occidental&quot;],[&quot;Negros Oriental&quot;,&quot;Negros Oriental&quot;],[&quot;Northern Samar&quot;,&quot;Northern Samar&quot;],[&quot;Nueva Ecija&quot;,&quot;Nueva Ecija&quot;],[&quot;Nueva Vizcaya&quot;,&quot;Nueva Vizcaya&quot;],[&quot;Occidental Mindoro&quot;,&quot;Occidental Mindoro&quot;],[&quot;Oriental Mindoro&quot;,&quot;Oriental Mindoro&quot;],[&quot;Palawan&quot;,&quot;Palawan&quot;],[&quot;Pampanga&quot;,&quot;Pampanga&quot;],[&quot;Pangasinan&quot;,&quot;Pangasinan&quot;],[&quot;Quezon&quot;,&quot;Quezon&quot;],[&quot;Quirino&quot;,&quot;Quirino&quot;],[&quot;Rizal&quot;,&quot;Rizal&quot;],[&quot;Romblon&quot;,&quot;Romblon&quot;],[&quot;Samar&quot;,&quot;Samar&quot;],[&quot;Sarangani&quot;,&quot;Sarangani&quot;],[&quot;Siquijor&quot;,&quot;Siquijor&quot;],[&quot;Sorsogon&quot;,&quot;Sorsogon&quot;],[&quot;South Cotabato&quot;,&quot;South Cotabato&quot;],[&quot;Southern Leyte&quot;,&quot;Southern Leyte&quot;],[&quot;Sultan Kudarat&quot;,&quot;Sultan Kudarat&quot;],[&quot;Sulu&quot;,&quot;Sulu&quot;],[&quot;Surigao del Norte&quot;,&quot;Surigao del Norte&quot;],[&quot;Surigao del Sur&quot;,&quot;Surigao del Sur&quot;],[&quot;Tarlac&quot;,&quot;Tarlac&quot;],[&quot;Tawi-Tawi&quot;,&quot;Tawi-Tawi&quot;],[&quot;Zambales&quot;,&quot;Zambales&quot;],[&quot;Zamboanga Sibugay&quot;,&quot;Zamboanga Sibugay&quot;],[&quot;Zamboanga del Norte&quot;,&quot;Zamboanga del Norte&quot;],[&quot;Zamboanga del Sur&quot;,&quot;Zamboanga del Sur&quot;]]">Philippines</option>
<option value="Pitcairn" data-provinces="[]">Pitcairn Islands</option>
<option value="Poland" data-provinces="[]">Poland</option>
<option value="Portugal" data-provinces="[[&quot;Aveiro&quot;,&quot;Aveiro&quot;],[&quot;Açores&quot;,&quot;Azores&quot;],[&quot;Beja&quot;,&quot;Beja&quot;],[&quot;Braga&quot;,&quot;Braga&quot;],[&quot;Bragança&quot;,&quot;Bragança&quot;],[&quot;Castelo Branco&quot;,&quot;Castelo Branco&quot;],[&quot;Coimbra&quot;,&quot;Coimbra&quot;],[&quot;Faro&quot;,&quot;Faro&quot;],[&quot;Guarda&quot;,&quot;Guarda&quot;],[&quot;Leiria&quot;,&quot;Leiria&quot;],[&quot;Lisboa&quot;,&quot;Lisbon&quot;],[&quot;Madeira&quot;,&quot;Madeira&quot;],[&quot;Portalegre&quot;,&quot;Portalegre&quot;],[&quot;Porto&quot;,&quot;Porto&quot;],[&quot;Santarém&quot;,&quot;Santarém&quot;],[&quot;Setúbal&quot;,&quot;Setúbal&quot;],[&quot;Viana do Castelo&quot;,&quot;Viana do Castelo&quot;],[&quot;Vila Real&quot;,&quot;Vila Real&quot;],[&quot;Viseu&quot;,&quot;Viseu&quot;],[&quot;Évora&quot;,&quot;Évora&quot;]]">Portugal</option>
<option value="Qatar" data-provinces="[]">Qatar</option>
<option value="Reunion" data-provinces="[]">Réunion</option>
<option value="Romania" data-provinces="[[&quot;Alba&quot;,&quot;Alba&quot;],[&quot;Arad&quot;,&quot;Arad&quot;],[&quot;Argeș&quot;,&quot;Argeș&quot;],[&quot;Bacău&quot;,&quot;Bacău&quot;],[&quot;Bihor&quot;,&quot;Bihor&quot;],[&quot;Bistrița-Năsăud&quot;,&quot;Bistriţa-Năsăud&quot;],[&quot;Botoșani&quot;,&quot;Botoşani&quot;],[&quot;Brașov&quot;,&quot;Braşov&quot;],[&quot;Brăila&quot;,&quot;Brăila&quot;],[&quot;București&quot;,&quot;Bucharest&quot;],[&quot;Buzău&quot;,&quot;Buzău&quot;],[&quot;Caraș-Severin&quot;,&quot;Caraș-Severin&quot;],[&quot;Cluj&quot;,&quot;Cluj&quot;],[&quot;Constanța&quot;,&quot;Constanța&quot;],[&quot;Covasna&quot;,&quot;Covasna&quot;],[&quot;Călărași&quot;,&quot;Călărași&quot;],[&quot;Dolj&quot;,&quot;Dolj&quot;],[&quot;Dâmbovița&quot;,&quot;Dâmbovița&quot;],[&quot;Galați&quot;,&quot;Galați&quot;],[&quot;Giurgiu&quot;,&quot;Giurgiu&quot;],[&quot;Gorj&quot;,&quot;Gorj&quot;],[&quot;Harghita&quot;,&quot;Harghita&quot;],[&quot;Hunedoara&quot;,&quot;Hunedoara&quot;],[&quot;Ialomița&quot;,&quot;Ialomița&quot;],[&quot;Iași&quot;,&quot;Iași&quot;],[&quot;Ilfov&quot;,&quot;Ilfov&quot;],[&quot;Maramureș&quot;,&quot;Maramureş&quot;],[&quot;Mehedinți&quot;,&quot;Mehedinți&quot;],[&quot;Mureș&quot;,&quot;Mureş&quot;],[&quot;Neamț&quot;,&quot;Neamţ&quot;],[&quot;Olt&quot;,&quot;Olt&quot;],[&quot;Prahova&quot;,&quot;Prahova&quot;],[&quot;Satu Mare&quot;,&quot;Satu Mare&quot;],[&quot;Sibiu&quot;,&quot;Sibiu&quot;],[&quot;Suceava&quot;,&quot;Suceava&quot;],[&quot;Sălaj&quot;,&quot;Sălaj&quot;],[&quot;Teleorman&quot;,&quot;Teleorman&quot;],[&quot;Timiș&quot;,&quot;Timiș&quot;],[&quot;Tulcea&quot;,&quot;Tulcea&quot;],[&quot;Vaslui&quot;,&quot;Vaslui&quot;],[&quot;Vrancea&quot;,&quot;Vrancea&quot;],[&quot;Vâlcea&quot;,&quot;Vâlcea&quot;]]">Romania</option>
<option value="Russia" data-provinces="[[&quot;Altai Krai&quot;,&quot;Altai Krai&quot;],[&quot;Altai Republic&quot;,&quot;Altai&quot;],[&quot;Amur Oblast&quot;,&quot;Amur&quot;],[&quot;Arkhangelsk Oblast&quot;,&quot;Arkhangelsk&quot;],[&quot;Astrakhan Oblast&quot;,&quot;Astrakhan&quot;],[&quot;Belgorod Oblast&quot;,&quot;Belgorod&quot;],[&quot;Bryansk Oblast&quot;,&quot;Bryansk&quot;],[&quot;Chechen Republic&quot;,&quot;Chechen&quot;],[&quot;Chelyabinsk Oblast&quot;,&quot;Chelyabinsk&quot;],[&quot;Chukotka Autonomous Okrug&quot;,&quot;Chukotka Okrug&quot;],[&quot;Chuvash Republic&quot;,&quot;Chuvash&quot;],[&quot;Irkutsk Oblast&quot;,&quot;Irkutsk&quot;],[&quot;Ivanovo Oblast&quot;,&quot;Ivanovo&quot;],[&quot;Jewish Autonomous Oblast&quot;,&quot;Jewish&quot;],[&quot;Kabardino-Balkarian Republic&quot;,&quot;Kabardino-Balkar&quot;],[&quot;Kaliningrad Oblast&quot;,&quot;Kaliningrad&quot;],[&quot;Kaluga Oblast&quot;,&quot;Kaluga&quot;],[&quot;Kamchatka Krai&quot;,&quot;Kamchatka Krai&quot;],[&quot;Karachay–Cherkess Republic&quot;,&quot;Karachay-Cherkess&quot;],[&quot;Kemerovo Oblast&quot;,&quot;Kemerovo&quot;],[&quot;Khabarovsk Krai&quot;,&quot;Khabarovsk Krai&quot;],[&quot;Khanty-Mansi Autonomous Okrug&quot;,&quot;Khanty-Mansi&quot;],[&quot;Kirov Oblast&quot;,&quot;Kirov&quot;],[&quot;Komi Republic&quot;,&quot;Komi&quot;],[&quot;Kostroma Oblast&quot;,&quot;Kostroma&quot;],[&quot;Krasnodar Krai&quot;,&quot;Krasnodar Krai&quot;],[&quot;Krasnoyarsk Krai&quot;,&quot;Krasnoyarsk Krai&quot;],[&quot;Kurgan Oblast&quot;,&quot;Kurgan&quot;],[&quot;Kursk Oblast&quot;,&quot;Kursk&quot;],[&quot;Leningrad Oblast&quot;,&quot;Leningrad&quot;],[&quot;Lipetsk Oblast&quot;,&quot;Lipetsk&quot;],[&quot;Magadan Oblast&quot;,&quot;Magadan&quot;],[&quot;Mari El Republic&quot;,&quot;Mari El&quot;],[&quot;Moscow&quot;,&quot;Moscow&quot;],[&quot;Moscow Oblast&quot;,&quot;Moscow Province&quot;],[&quot;Murmansk Oblast&quot;,&quot;Murmansk&quot;],[&quot;Nizhny Novgorod Oblast&quot;,&quot;Nizhny Novgorod&quot;],[&quot;Novgorod Oblast&quot;,&quot;Novgorod&quot;],[&quot;Novosibirsk Oblast&quot;,&quot;Novosibirsk&quot;],[&quot;Omsk Oblast&quot;,&quot;Omsk&quot;],[&quot;Orenburg Oblast&quot;,&quot;Orenburg&quot;],[&quot;Oryol Oblast&quot;,&quot;Oryol&quot;],[&quot;Penza Oblast&quot;,&quot;Penza&quot;],[&quot;Perm Krai&quot;,&quot;Perm Krai&quot;],[&quot;Primorsky Krai&quot;,&quot;Primorsky Krai&quot;],[&quot;Pskov Oblast&quot;,&quot;Pskov&quot;],[&quot;Republic of Adygeya&quot;,&quot;Adygea&quot;],[&quot;Republic of Bashkortostan&quot;,&quot;Bashkortostan&quot;],[&quot;Republic of Buryatia&quot;,&quot;Buryat&quot;],[&quot;Republic of Dagestan&quot;,&quot;Dagestan&quot;],[&quot;Republic of Ingushetia&quot;,&quot;Ingushetia&quot;],[&quot;Republic of Kalmykia&quot;,&quot;Kalmykia&quot;],[&quot;Republic of Karelia&quot;,&quot;Karelia&quot;],[&quot;Republic of Khakassia&quot;,&quot;Khakassia&quot;],[&quot;Republic of Mordovia&quot;,&quot;Mordovia&quot;],[&quot;Republic of North Ossetia–Alania&quot;,&quot;North Ossetia-Alania&quot;],[&quot;Republic of Tatarstan&quot;,&quot;Tatarstan&quot;],[&quot;Rostov Oblast&quot;,&quot;Rostov&quot;],[&quot;Ryazan Oblast&quot;,&quot;Ryazan&quot;],[&quot;Saint Petersburg&quot;,&quot;Saint Petersburg&quot;],[&quot;Sakha Republic (Yakutia)&quot;,&quot;Sakha&quot;],[&quot;Sakhalin Oblast&quot;,&quot;Sakhalin&quot;],[&quot;Samara Oblast&quot;,&quot;Samara&quot;],[&quot;Saratov Oblast&quot;,&quot;Saratov&quot;],[&quot;Smolensk Oblast&quot;,&quot;Smolensk&quot;],[&quot;Stavropol Krai&quot;,&quot;Stavropol Krai&quot;],[&quot;Sverdlovsk Oblast&quot;,&quot;Sverdlovsk&quot;],[&quot;Tambov Oblast&quot;,&quot;Tambov&quot;],[&quot;Tomsk Oblast&quot;,&quot;Tomsk&quot;],[&quot;Tula Oblast&quot;,&quot;Tula&quot;],[&quot;Tver Oblast&quot;,&quot;Tver&quot;],[&quot;Tyumen Oblast&quot;,&quot;Tyumen&quot;],[&quot;Tyva Republic&quot;,&quot;Tuva&quot;],[&quot;Udmurtia&quot;,&quot;Udmurt&quot;],[&quot;Ulyanovsk Oblast&quot;,&quot;Ulyanovsk&quot;],[&quot;Vladimir Oblast&quot;,&quot;Vladimir&quot;],[&quot;Volgograd Oblast&quot;,&quot;Volgograd&quot;],[&quot;Vologda Oblast&quot;,&quot;Vologda&quot;],[&quot;Voronezh Oblast&quot;,&quot;Voronezh&quot;],[&quot;Yamalo-Nenets Autonomous Okrug&quot;,&quot;Yamalo-Nenets Okrug&quot;],[&quot;Yaroslavl Oblast&quot;,&quot;Yaroslavl&quot;],[&quot;Zabaykalsky Krai&quot;,&quot;Zabaykalsky Krai&quot;]]">Russia</option>
<option value="Rwanda" data-provinces="[]">Rwanda</option>
<option value="Samoa" data-provinces="[]">Samoa</option>
<option value="San Marino" data-provinces="[]">San Marino</option>
<option value="Sao Tome And Principe" data-provinces="[]">São Tomé & Príncipe</option>
<option value="Saudi Arabia" data-provinces="[]">Saudi Arabia</option>
<option value="Senegal" data-provinces="[]">Senegal</option>
<option value="Serbia" data-provinces="[]">Serbia</option>
<option value="Seychelles" data-provinces="[]">Seychelles</option>
<option value="Sierra Leone" data-provinces="[]">Sierra Leone</option>
<option value="Singapore" data-provinces="[]">Singapore</option>
<option value="Sint Maarten" data-provinces="[]">Sint Maarten</option>
<option value="Slovakia" data-provinces="[]">Slovakia</option>
<option value="Slovenia" data-provinces="[]">Slovenia</option>
<option value="Solomon Islands" data-provinces="[]">Solomon Islands</option>
<option value="Somalia" data-provinces="[]">Somalia</option>
<option value="South Africa" data-provinces="[[&quot;Eastern Cape&quot;,&quot;Eastern Cape&quot;],[&quot;Free State&quot;,&quot;Free State&quot;],[&quot;Gauteng&quot;,&quot;Gauteng&quot;],[&quot;KwaZulu-Natal&quot;,&quot;KwaZulu-Natal&quot;],[&quot;Limpopo&quot;,&quot;Limpopo&quot;],[&quot;Mpumalanga&quot;,&quot;Mpumalanga&quot;],[&quot;North West&quot;,&quot;North West&quot;],[&quot;Northern Cape&quot;,&quot;Northern Cape&quot;],[&quot;Western Cape&quot;,&quot;Western Cape&quot;]]">South Africa</option>
<option value="South Georgia And The South Sandwich Islands" data-provinces="[]">South Georgia & South Sandwich Islands</option>
<option value="South Korea" data-provinces="[[&quot;Busan&quot;,&quot;Busan&quot;],[&quot;Chungbuk&quot;,&quot;North Chungcheong&quot;],[&quot;Chungnam&quot;,&quot;South Chungcheong&quot;],[&quot;Daegu&quot;,&quot;Daegu&quot;],[&quot;Daejeon&quot;,&quot;Daejeon&quot;],[&quot;Gangwon&quot;,&quot;Gangwon&quot;],[&quot;Gwangju&quot;,&quot;Gwangju City&quot;],[&quot;Gyeongbuk&quot;,&quot;North Gyeongsang&quot;],[&quot;Gyeonggi&quot;,&quot;Gyeonggi&quot;],[&quot;Gyeongnam&quot;,&quot;South Gyeongsang&quot;],[&quot;Incheon&quot;,&quot;Incheon&quot;],[&quot;Jeju&quot;,&quot;Jeju&quot;],[&quot;Jeonbuk&quot;,&quot;North Jeolla&quot;],[&quot;Jeonnam&quot;,&quot;South Jeolla&quot;],[&quot;Sejong&quot;,&quot;Sejong&quot;],[&quot;Seoul&quot;,&quot;Seoul&quot;],[&quot;Ulsan&quot;,&quot;Ulsan&quot;]]">South Korea</option>
<option value="South Sudan" data-provinces="[]">South Sudan</option>
<option value="Spain" data-provinces="[[&quot;A Coruña&quot;,&quot;A Coruña&quot;],[&quot;Albacete&quot;,&quot;Albacete&quot;],[&quot;Alicante&quot;,&quot;Alicante&quot;],[&quot;Almería&quot;,&quot;Almería&quot;],[&quot;Asturias&quot;,&quot;Asturias Province&quot;],[&quot;Badajoz&quot;,&quot;Badajoz&quot;],[&quot;Balears&quot;,&quot;Balears Province&quot;],[&quot;Barcelona&quot;,&quot;Barcelona&quot;],[&quot;Burgos&quot;,&quot;Burgos&quot;],[&quot;Cantabria&quot;,&quot;Cantabria Province&quot;],[&quot;Castellón&quot;,&quot;Castellón&quot;],[&quot;Ceuta&quot;,&quot;Ceuta&quot;],[&quot;Ciudad Real&quot;,&quot;Ciudad Real&quot;],[&quot;Cuenca&quot;,&quot;Cuenca&quot;],[&quot;Cáceres&quot;,&quot;Cáceres&quot;],[&quot;Cádiz&quot;,&quot;Cádiz&quot;],[&quot;Córdoba&quot;,&quot;Córdoba&quot;],[&quot;Girona&quot;,&quot;Girona&quot;],[&quot;Granada&quot;,&quot;Granada&quot;],[&quot;Guadalajara&quot;,&quot;Guadalajara&quot;],[&quot;Guipúzcoa&quot;,&quot;Gipuzkoa&quot;],[&quot;Huelva&quot;,&quot;Huelva&quot;],[&quot;Huesca&quot;,&quot;Huesca&quot;],[&quot;Jaén&quot;,&quot;Jaén&quot;],[&quot;La Rioja&quot;,&quot;La Rioja Province&quot;],[&quot;Las Palmas&quot;,&quot;Las Palmas&quot;],[&quot;León&quot;,&quot;León&quot;],[&quot;Lleida&quot;,&quot;Lleida&quot;],[&quot;Lugo&quot;,&quot;Lugo&quot;],[&quot;Madrid&quot;,&quot;Madrid Province&quot;],[&quot;Melilla&quot;,&quot;Melilla&quot;],[&quot;Murcia&quot;,&quot;Murcia&quot;],[&quot;Málaga&quot;,&quot;Málaga&quot;],[&quot;Navarra&quot;,&quot;Navarra&quot;],[&quot;Ourense&quot;,&quot;Ourense&quot;],[&quot;Palencia&quot;,&quot;Palencia&quot;],[&quot;Pontevedra&quot;,&quot;Pontevedra&quot;],[&quot;Salamanca&quot;,&quot;Salamanca&quot;],[&quot;Santa Cruz de Tenerife&quot;,&quot;Santa Cruz de Tenerife&quot;],[&quot;Segovia&quot;,&quot;Segovia&quot;],[&quot;Sevilla&quot;,&quot;Seville&quot;],[&quot;Soria&quot;,&quot;Soria&quot;],[&quot;Tarragona&quot;,&quot;Tarragona&quot;],[&quot;Teruel&quot;,&quot;Teruel&quot;],[&quot;Toledo&quot;,&quot;Toledo&quot;],[&quot;Valencia&quot;,&quot;Valencia&quot;],[&quot;Valladolid&quot;,&quot;Valladolid&quot;],[&quot;Vizcaya&quot;,&quot;Biscay&quot;],[&quot;Zamora&quot;,&quot;Zamora&quot;],[&quot;Zaragoza&quot;,&quot;Zaragoza&quot;],[&quot;Álava&quot;,&quot;Álava&quot;],[&quot;Ávila&quot;,&quot;Ávila&quot;]]">Spain</option>
<option value="Sri Lanka" data-provinces="[]">Sri Lanka</option>
<option value="Saint Barthélemy" data-provinces="[]">St. Barthélemy</option>
<option value="Saint Helena" data-provinces="[]">St. Helena</option>
<option value="Saint Kitts And Nevis" data-provinces="[]">St. Kitts & Nevis</option>
<option value="Saint Lucia" data-provinces="[]">St. Lucia</option>
<option value="Saint Martin" data-provinces="[]">St. Martin</option>
<option value="Saint Pierre And Miquelon" data-provinces="[]">St. Pierre & Miquelon</option>
<option value="St. Vincent" data-provinces="[]">St. Vincent & Grenadines</option>
<option value="Sudan" data-provinces="[]">Sudan</option>
<option value="Suriname" data-provinces="[]">Suriname</option>
<option value="Svalbard And Jan Mayen" data-provinces="[]">Svalbard & Jan Mayen</option>
<option value="Sweden" data-provinces="[]">Sweden</option>
<option value="Switzerland" data-provinces="[]">Switzerland</option>
<option value="Taiwan" data-provinces="[]">Taiwan</option>
<option value="Tajikistan" data-provinces="[]">Tajikistan</option>
<option value="Tanzania, United Republic Of" data-provinces="[]">Tanzania</option>
<option value="Thailand" data-provinces="[[&quot;Amnat Charoen&quot;,&quot;Amnat Charoen&quot;],[&quot;Ang Thong&quot;,&quot;Ang Thong&quot;],[&quot;Bangkok&quot;,&quot;Bangkok&quot;],[&quot;Bueng Kan&quot;,&quot;Bueng Kan&quot;],[&quot;Buriram&quot;,&quot;Buri Ram&quot;],[&quot;Chachoengsao&quot;,&quot;Chachoengsao&quot;],[&quot;Chai Nat&quot;,&quot;Chai Nat&quot;],[&quot;Chaiyaphum&quot;,&quot;Chaiyaphum&quot;],[&quot;Chanthaburi&quot;,&quot;Chanthaburi&quot;],[&quot;Chiang Mai&quot;,&quot;Chiang Mai&quot;],[&quot;Chiang Rai&quot;,&quot;Chiang Rai&quot;],[&quot;Chon Buri&quot;,&quot;Chon Buri&quot;],[&quot;Chumphon&quot;,&quot;Chumphon&quot;],[&quot;Kalasin&quot;,&quot;Kalasin&quot;],[&quot;Kamphaeng Phet&quot;,&quot;Kamphaeng Phet&quot;],[&quot;Kanchanaburi&quot;,&quot;Kanchanaburi&quot;],[&quot;Khon Kaen&quot;,&quot;Khon Kaen&quot;],[&quot;Krabi&quot;,&quot;Krabi&quot;],[&quot;Lampang&quot;,&quot;Lampang&quot;],[&quot;Lamphun&quot;,&quot;Lamphun&quot;],[&quot;Loei&quot;,&quot;Loei&quot;],[&quot;Lopburi&quot;,&quot;Lopburi&quot;],[&quot;Mae Hong Son&quot;,&quot;Mae Hong Son&quot;],[&quot;Maha Sarakham&quot;,&quot;Maha Sarakham&quot;],[&quot;Mukdahan&quot;,&quot;Mukdahan&quot;],[&quot;Nakhon Nayok&quot;,&quot;Nakhon Nayok&quot;],[&quot;Nakhon Pathom&quot;,&quot;Nakhon Pathom&quot;],[&quot;Nakhon Phanom&quot;,&quot;Nakhon Phanom&quot;],[&quot;Nakhon Ratchasima&quot;,&quot;Nakhon Ratchasima&quot;],[&quot;Nakhon Sawan&quot;,&quot;Nakhon Sawan&quot;],[&quot;Nakhon Si Thammarat&quot;,&quot;Nakhon Si Thammarat&quot;],[&quot;Nan&quot;,&quot;Nan&quot;],[&quot;Narathiwat&quot;,&quot;Narathiwat&quot;],[&quot;Nong Bua Lam Phu&quot;,&quot;Nong Bua Lam Phu&quot;],[&quot;Nong Khai&quot;,&quot;Nong Khai&quot;],[&quot;Nonthaburi&quot;,&quot;Nonthaburi&quot;],[&quot;Pathum Thani&quot;,&quot;Pathum Thani&quot;],[&quot;Pattani&quot;,&quot;Pattani&quot;],[&quot;Pattaya&quot;,&quot;Pattaya&quot;],[&quot;Phangnga&quot;,&quot;Phang Nga&quot;],[&quot;Phatthalung&quot;,&quot;Phatthalung&quot;],[&quot;Phayao&quot;,&quot;Phayao&quot;],[&quot;Phetchabun&quot;,&quot;Phetchabun&quot;],[&quot;Phetchaburi&quot;,&quot;Phetchaburi&quot;],[&quot;Phichit&quot;,&quot;Phichit&quot;],[&quot;Phitsanulok&quot;,&quot;Phitsanulok&quot;],[&quot;Phra Nakhon Si Ayutthaya&quot;,&quot;Phra Nakhon Si Ayutthaya&quot;],[&quot;Phrae&quot;,&quot;Phrae&quot;],[&quot;Phuket&quot;,&quot;Phuket&quot;],[&quot;Prachin Buri&quot;,&quot;Prachin Buri&quot;],[&quot;Prachuap Khiri Khan&quot;,&quot;Prachuap Khiri Khan&quot;],[&quot;Ranong&quot;,&quot;Ranong&quot;],[&quot;Ratchaburi&quot;,&quot;Ratchaburi&quot;],[&quot;Rayong&quot;,&quot;Rayong&quot;],[&quot;Roi Et&quot;,&quot;Roi Et&quot;],[&quot;Sa Kaeo&quot;,&quot;Sa Kaeo&quot;],[&quot;Sakon Nakhon&quot;,&quot;Sakon Nakhon&quot;],[&quot;Samut Prakan&quot;,&quot;Samut Prakan&quot;],[&quot;Samut Sakhon&quot;,&quot;Samut Sakhon&quot;],[&quot;Samut Songkhram&quot;,&quot;Samut Songkhram&quot;],[&quot;Saraburi&quot;,&quot;Saraburi&quot;],[&quot;Satun&quot;,&quot;Satun&quot;],[&quot;Sing Buri&quot;,&quot;Sing Buri&quot;],[&quot;Sisaket&quot;,&quot;Si Sa Ket&quot;],[&quot;Songkhla&quot;,&quot;Songkhla&quot;],[&quot;Sukhothai&quot;,&quot;Sukhothai&quot;],[&quot;Suphan Buri&quot;,&quot;Suphanburi&quot;],[&quot;Surat Thani&quot;,&quot;Surat Thani&quot;],[&quot;Surin&quot;,&quot;Surin&quot;],[&quot;Tak&quot;,&quot;Tak&quot;],[&quot;Trang&quot;,&quot;Trang&quot;],[&quot;Trat&quot;,&quot;Trat&quot;],[&quot;Ubon Ratchathani&quot;,&quot;Ubon Ratchathani&quot;],[&quot;Udon Thani&quot;,&quot;Udon Thani&quot;],[&quot;Uthai Thani&quot;,&quot;Uthai Thani&quot;],[&quot;Uttaradit&quot;,&quot;Uttaradit&quot;],[&quot;Yala&quot;,&quot;Yala&quot;],[&quot;Yasothon&quot;,&quot;Yasothon&quot;]]">Thailand</option>
<option value="Timor Leste" data-provinces="[]">Timor-Leste</option>
<option value="Togo" data-provinces="[]">Togo</option>
<option value="Tokelau" data-provinces="[]">Tokelau</option>
<option value="Tonga" data-provinces="[]">Tonga</option>
<option value="Trinidad and Tobago" data-provinces="[]">Trinidad & Tobago</option>
<option value="Tristan da Cunha" data-provinces="[]">Tristan da Cunha</option>
<option value="Tunisia" data-provinces="[]">Tunisia</option>
<option value="Turkey" data-provinces="[]">Türkiye</option>
<option value="Turkmenistan" data-provinces="[]">Turkmenistan</option>
<option value="Turks and Caicos Islands" data-provinces="[]">Turks & Caicos Islands</option>
<option value="Tuvalu" data-provinces="[]">Tuvalu</option>
<option value="United States Minor Outlying Islands" data-provinces="[]">U.S. Outlying Islands</option>
<option value="Uganda" data-provinces="[]">Uganda</option>
<option value="Ukraine" data-provinces="[]">Ukraine</option>
<option value="United Arab Emirates" data-provinces="[[&quot;Abu Dhabi&quot;,&quot;Abu Dhabi&quot;],[&quot;Ajman&quot;,&quot;Ajman&quot;],[&quot;Dubai&quot;,&quot;Dubai&quot;],[&quot;Fujairah&quot;,&quot;Fujairah&quot;],[&quot;Ras al-Khaimah&quot;,&quot;Ras al-Khaimah&quot;],[&quot;Sharjah&quot;,&quot;Sharjah&quot;],[&quot;Umm al-Quwain&quot;,&quot;Umm al-Quwain&quot;]]">United Arab Emirates</option>
<option value="United Kingdom" data-provinces="[[&quot;British Forces&quot;,&quot;British Forces&quot;],[&quot;England&quot;,&quot;England&quot;],[&quot;Northern Ireland&quot;,&quot;Northern Ireland&quot;],[&quot;Scotland&quot;,&quot;Scotland&quot;],[&quot;Wales&quot;,&quot;Wales&quot;]]">United Kingdom</option>
<option value="United States" data-provinces="[[&quot;Alabama&quot;,&quot;Alabama&quot;],[&quot;Alaska&quot;,&quot;Alaska&quot;],[&quot;American Samoa&quot;,&quot;American Samoa&quot;],[&quot;Arizona&quot;,&quot;Arizona&quot;],[&quot;Arkansas&quot;,&quot;Arkansas&quot;],[&quot;Armed Forces Americas&quot;,&quot;Armed Forces Americas&quot;],[&quot;Armed Forces Europe&quot;,&quot;Armed Forces Europe&quot;],[&quot;Armed Forces Pacific&quot;,&quot;Armed Forces Pacific&quot;],[&quot;California&quot;,&quot;California&quot;],[&quot;Colorado&quot;,&quot;Colorado&quot;],[&quot;Connecticut&quot;,&quot;Connecticut&quot;],[&quot;Delaware&quot;,&quot;Delaware&quot;],[&quot;District of Columbia&quot;,&quot;Washington DC&quot;],[&quot;Federated States of Micronesia&quot;,&quot;Micronesia&quot;],[&quot;Florida&quot;,&quot;Florida&quot;],[&quot;Georgia&quot;,&quot;Georgia&quot;],[&quot;Guam&quot;,&quot;Guam&quot;],[&quot;Hawaii&quot;,&quot;Hawaii&quot;],[&quot;Idaho&quot;,&quot;Idaho&quot;],[&quot;Illinois&quot;,&quot;Illinois&quot;],[&quot;Indiana&quot;,&quot;Indiana&quot;],[&quot;Iowa&quot;,&quot;Iowa&quot;],[&quot;Kansas&quot;,&quot;Kansas&quot;],[&quot;Kentucky&quot;,&quot;Kentucky&quot;],[&quot;Louisiana&quot;,&quot;Louisiana&quot;],[&quot;Maine&quot;,&quot;Maine&quot;],[&quot;Marshall Islands&quot;,&quot;Marshall Islands&quot;],[&quot;Maryland&quot;,&quot;Maryland&quot;],[&quot;Massachusetts&quot;,&quot;Massachusetts&quot;],[&quot;Michigan&quot;,&quot;Michigan&quot;],[&quot;Minnesota&quot;,&quot;Minnesota&quot;],[&quot;Mississippi&quot;,&quot;Mississippi&quot;],[&quot;Missouri&quot;,&quot;Missouri&quot;],[&quot;Montana&quot;,&quot;Montana&quot;],[&quot;Nebraska&quot;,&quot;Nebraska&quot;],[&quot;Nevada&quot;,&quot;Nevada&quot;],[&quot;New Hampshire&quot;,&quot;New Hampshire&quot;],[&quot;New Jersey&quot;,&quot;New Jersey&quot;],[&quot;New Mexico&quot;,&quot;New Mexico&quot;],[&quot;New York&quot;,&quot;New York&quot;],[&quot;North Carolina&quot;,&quot;North Carolina&quot;],[&quot;North Dakota&quot;,&quot;North Dakota&quot;],[&quot;Northern Mariana Islands&quot;,&quot;Northern Mariana Islands&quot;],[&quot;Ohio&quot;,&quot;Ohio&quot;],[&quot;Oklahoma&quot;,&quot;Oklahoma&quot;],[&quot;Oregon&quot;,&quot;Oregon&quot;],[&quot;Palau&quot;,&quot;Palau&quot;],[&quot;Pennsylvania&quot;,&quot;Pennsylvania&quot;],[&quot;Puerto Rico&quot;,&quot;Puerto Rico&quot;],[&quot;Rhode Island&quot;,&quot;Rhode Island&quot;],[&quot;South Carolina&quot;,&quot;South Carolina&quot;],[&quot;South Dakota&quot;,&quot;South Dakota&quot;],[&quot;Tennessee&quot;,&quot;Tennessee&quot;],[&quot;Texas&quot;,&quot;Texas&quot;],[&quot;Utah&quot;,&quot;Utah&quot;],[&quot;Vermont&quot;,&quot;Vermont&quot;],[&quot;Virgin Islands&quot;,&quot;U.S. Virgin Islands&quot;],[&quot;Virginia&quot;,&quot;Virginia&quot;],[&quot;Washington&quot;,&quot;Washington&quot;],[&quot;West Virginia&quot;,&quot;West Virginia&quot;],[&quot;Wisconsin&quot;,&quot;Wisconsin&quot;],[&quot;Wyoming&quot;,&quot;Wyoming&quot;]]">United States</option>
<option value="Uruguay" data-provinces="[[&quot;Artigas&quot;,&quot;Artigas&quot;],[&quot;Canelones&quot;,&quot;Canelones&quot;],[&quot;Cerro Largo&quot;,&quot;Cerro Largo&quot;],[&quot;Colonia&quot;,&quot;Colonia&quot;],[&quot;Durazno&quot;,&quot;Durazno&quot;],[&quot;Flores&quot;,&quot;Flores&quot;],[&quot;Florida&quot;,&quot;Florida&quot;],[&quot;Lavalleja&quot;,&quot;Lavalleja&quot;],[&quot;Maldonado&quot;,&quot;Maldonado&quot;],[&quot;Montevideo&quot;,&quot;Montevideo&quot;],[&quot;Paysandú&quot;,&quot;Paysandú&quot;],[&quot;Rivera&quot;,&quot;Rivera&quot;],[&quot;Rocha&quot;,&quot;Rocha&quot;],[&quot;Río Negro&quot;,&quot;Río Negro&quot;],[&quot;Salto&quot;,&quot;Salto&quot;],[&quot;San José&quot;,&quot;San José&quot;],[&quot;Soriano&quot;,&quot;Soriano&quot;],[&quot;Tacuarembó&quot;,&quot;Tacuarembó&quot;],[&quot;Treinta y Tres&quot;,&quot;Treinta y Tres&quot;]]">Uruguay</option>
<option value="Uzbekistan" data-provinces="[]">Uzbekistan</option>
<option value="Vanuatu" data-provinces="[]">Vanuatu</option>
<option value="Holy See (Vatican City State)" data-provinces="[]">Vatican City</option>
<option value="Venezuela" data-provinces="[[&quot;Amazonas&quot;,&quot;Amazonas&quot;],[&quot;Anzoátegui&quot;,&quot;Anzoátegui&quot;],[&quot;Apure&quot;,&quot;Apure&quot;],[&quot;Aragua&quot;,&quot;Aragua&quot;],[&quot;Barinas&quot;,&quot;Barinas&quot;],[&quot;Bolívar&quot;,&quot;Bolívar&quot;],[&quot;Carabobo&quot;,&quot;Carabobo&quot;],[&quot;Cojedes&quot;,&quot;Cojedes&quot;],[&quot;Delta Amacuro&quot;,&quot;Delta Amacuro&quot;],[&quot;Dependencias Federales&quot;,&quot;Federal Dependencies&quot;],[&quot;Distrito Capital&quot;,&quot;Capital&quot;],[&quot;Falcón&quot;,&quot;Falcón&quot;],[&quot;Guárico&quot;,&quot;Guárico&quot;],[&quot;La Guaira&quot;,&quot;Vargas&quot;],[&quot;Lara&quot;,&quot;Lara&quot;],[&quot;Miranda&quot;,&quot;Miranda&quot;],[&quot;Monagas&quot;,&quot;Monagas&quot;],[&quot;Mérida&quot;,&quot;Mérida&quot;],[&quot;Nueva Esparta&quot;,&quot;Nueva Esparta&quot;],[&quot;Portuguesa&quot;,&quot;Portuguesa&quot;],[&quot;Sucre&quot;,&quot;Sucre&quot;],[&quot;Trujillo&quot;,&quot;Trujillo&quot;],[&quot;Táchira&quot;,&quot;Táchira&quot;],[&quot;Yaracuy&quot;,&quot;Yaracuy&quot;],[&quot;Zulia&quot;,&quot;Zulia&quot;]]">Venezuela</option>
<option value="Vietnam" data-provinces="[]">Vietnam</option>
<option value="Wallis And Futuna" data-provinces="[]">Wallis & Futuna</option>
<option value="Western Sahara" data-provinces="[]">Western Sahara</option>
<option value="Yemen" data-provinces="[]">Yemen</option>
<option value="Zambia" data-provinces="[]">Zambia</option>
<option value="Zimbabwe" data-provinces="[]">Zimbabwe</option>`;
  window.CF.shippingZoneCountryOptionTags = `<option value="---" data-provinces="[]">---</option>
<option value="Afghanistan" data-provinces="[]">Afghanistan</option>
<option value="Aland Islands" data-provinces="[]">Åland Islands</option>
<option value="Albania" data-provinces="[]">Albania</option>
<option value="Algeria" data-provinces="[]">Algeria</option>
<option value="Andorra" data-provinces="[]">Andorra</option>
<option value="Angola" data-provinces="[]">Angola</option>
<option value="Anguilla" data-provinces="[]">Anguilla</option>
<option value="Antigua And Barbuda" data-provinces="[]">Antigua & Barbuda</option>
<option value="Argentina" data-provinces="[[&quot;Buenos Aires&quot;,&quot;Buenos Aires Province&quot;],[&quot;Catamarca&quot;,&quot;Catamarca&quot;],[&quot;Chaco&quot;,&quot;Chaco&quot;],[&quot;Chubut&quot;,&quot;Chubut&quot;],[&quot;Ciudad Autónoma de Buenos Aires&quot;,&quot;Buenos Aires (Autonomous City)&quot;],[&quot;Corrientes&quot;,&quot;Corrientes&quot;],[&quot;Córdoba&quot;,&quot;Córdoba&quot;],[&quot;Entre Ríos&quot;,&quot;Entre Ríos&quot;],[&quot;Formosa&quot;,&quot;Formosa&quot;],[&quot;Jujuy&quot;,&quot;Jujuy&quot;],[&quot;La Pampa&quot;,&quot;La Pampa&quot;],[&quot;La Rioja&quot;,&quot;La Rioja&quot;],[&quot;Mendoza&quot;,&quot;Mendoza&quot;],[&quot;Misiones&quot;,&quot;Misiones&quot;],[&quot;Neuquén&quot;,&quot;Neuquén&quot;],[&quot;Río Negro&quot;,&quot;Río Negro&quot;],[&quot;Salta&quot;,&quot;Salta&quot;],[&quot;San Juan&quot;,&quot;San Juan&quot;],[&quot;San Luis&quot;,&quot;San Luis&quot;],[&quot;Santa Cruz&quot;,&quot;Santa Cruz&quot;],[&quot;Santa Fe&quot;,&quot;Santa Fe&quot;],[&quot;Santiago Del Estero&quot;,&quot;Santiago del Estero&quot;],[&quot;Tierra Del Fuego&quot;,&quot;Tierra del Fuego&quot;],[&quot;Tucumán&quot;,&quot;Tucumán&quot;]]">Argentina</option>
<option value="Armenia" data-provinces="[]">Armenia</option>
<option value="Aruba" data-provinces="[]">Aruba</option>
<option value="Ascension Island" data-provinces="[]">Ascension Island</option>
<option value="Australia" data-provinces="[[&quot;Australian Capital Territory&quot;,&quot;Australian Capital Territory&quot;],[&quot;New South Wales&quot;,&quot;New South Wales&quot;],[&quot;Northern Territory&quot;,&quot;Northern Territory&quot;],[&quot;Queensland&quot;,&quot;Queensland&quot;],[&quot;South Australia&quot;,&quot;South Australia&quot;],[&quot;Tasmania&quot;,&quot;Tasmania&quot;],[&quot;Victoria&quot;,&quot;Victoria&quot;],[&quot;Western Australia&quot;,&quot;Western Australia&quot;]]">Australia</option>
<option value="Austria" data-provinces="[]">Austria</option>
<option value="Azerbaijan" data-provinces="[]">Azerbaijan</option>
<option value="Bahamas" data-provinces="[]">Bahamas</option>
<option value="Bahrain" data-provinces="[]">Bahrain</option>
<option value="Bangladesh" data-provinces="[]">Bangladesh</option>
<option value="Barbados" data-provinces="[]">Barbados</option>
<option value="Belarus" data-provinces="[]">Belarus</option>
<option value="Belgium" data-provinces="[]">Belgium</option>
<option value="Belize" data-provinces="[]">Belize</option>
<option value="Benin" data-provinces="[]">Benin</option>
<option value="Bermuda" data-provinces="[]">Bermuda</option>
<option value="Bhutan" data-provinces="[]">Bhutan</option>
<option value="Bolivia" data-provinces="[]">Bolivia</option>
<option value="Bosnia And Herzegovina" data-provinces="[]">Bosnia & Herzegovina</option>
<option value="Botswana" data-provinces="[]">Botswana</option>
<option value="Brazil" data-provinces="[[&quot;Acre&quot;,&quot;Acre&quot;],[&quot;Alagoas&quot;,&quot;Alagoas&quot;],[&quot;Amapá&quot;,&quot;Amapá&quot;],[&quot;Amazonas&quot;,&quot;Amazonas&quot;],[&quot;Bahia&quot;,&quot;Bahia&quot;],[&quot;Ceará&quot;,&quot;Ceará&quot;],[&quot;Distrito Federal&quot;,&quot;Federal District&quot;],[&quot;Espírito Santo&quot;,&quot;Espírito Santo&quot;],[&quot;Goiás&quot;,&quot;Goiás&quot;],[&quot;Maranhão&quot;,&quot;Maranhão&quot;],[&quot;Mato Grosso&quot;,&quot;Mato Grosso&quot;],[&quot;Mato Grosso do Sul&quot;,&quot;Mato Grosso do Sul&quot;],[&quot;Minas Gerais&quot;,&quot;Minas Gerais&quot;],[&quot;Paraná&quot;,&quot;Paraná&quot;],[&quot;Paraíba&quot;,&quot;Paraíba&quot;],[&quot;Pará&quot;,&quot;Pará&quot;],[&quot;Pernambuco&quot;,&quot;Pernambuco&quot;],[&quot;Piauí&quot;,&quot;Piauí&quot;],[&quot;Rio Grande do Norte&quot;,&quot;Rio Grande do Norte&quot;],[&quot;Rio Grande do Sul&quot;,&quot;Rio Grande do Sul&quot;],[&quot;Rio de Janeiro&quot;,&quot;Rio de Janeiro&quot;],[&quot;Rondônia&quot;,&quot;Rondônia&quot;],[&quot;Roraima&quot;,&quot;Roraima&quot;],[&quot;Santa Catarina&quot;,&quot;Santa Catarina&quot;],[&quot;Sergipe&quot;,&quot;Sergipe&quot;],[&quot;São Paulo&quot;,&quot;São Paulo&quot;],[&quot;Tocantins&quot;,&quot;Tocantins&quot;]]">Brazil</option>
<option value="British Indian Ocean Territory" data-provinces="[]">British Indian Ocean Territory</option>
<option value="Virgin Islands, British" data-provinces="[]">British Virgin Islands</option>
<option value="Brunei" data-provinces="[]">Brunei</option>
<option value="Bulgaria" data-provinces="[]">Bulgaria</option>
<option value="Burkina Faso" data-provinces="[]">Burkina Faso</option>
<option value="Burundi" data-provinces="[]">Burundi</option>
<option value="Cambodia" data-provinces="[]">Cambodia</option>
<option value="Republic of Cameroon" data-provinces="[]">Cameroon</option>
<option value="Canada" data-provinces="[[&quot;Alberta&quot;,&quot;Alberta&quot;],[&quot;British Columbia&quot;,&quot;British Columbia&quot;],[&quot;Manitoba&quot;,&quot;Manitoba&quot;],[&quot;New Brunswick&quot;,&quot;New Brunswick&quot;],[&quot;Newfoundland and Labrador&quot;,&quot;Newfoundland and Labrador&quot;],[&quot;Northwest Territories&quot;,&quot;Northwest Territories&quot;],[&quot;Nova Scotia&quot;,&quot;Nova Scotia&quot;],[&quot;Nunavut&quot;,&quot;Nunavut&quot;],[&quot;Ontario&quot;,&quot;Ontario&quot;],[&quot;Prince Edward Island&quot;,&quot;Prince Edward Island&quot;],[&quot;Quebec&quot;,&quot;Quebec&quot;],[&quot;Saskatchewan&quot;,&quot;Saskatchewan&quot;],[&quot;Yukon&quot;,&quot;Yukon&quot;]]">Canada</option>
<option value="Cape Verde" data-provinces="[]">Cape Verde</option>
<option value="Caribbean Netherlands" data-provinces="[]">Caribbean Netherlands</option>
<option value="Cayman Islands" data-provinces="[]">Cayman Islands</option>
<option value="Central African Republic" data-provinces="[]">Central African Republic</option>
<option value="Chad" data-provinces="[]">Chad</option>
<option value="Chile" data-provinces="[[&quot;Antofagasta&quot;,&quot;Antofagasta&quot;],[&quot;Araucanía&quot;,&quot;Araucanía&quot;],[&quot;Arica and Parinacota&quot;,&quot;Arica y Parinacota&quot;],[&quot;Atacama&quot;,&quot;Atacama&quot;],[&quot;Aysén&quot;,&quot;Aysén&quot;],[&quot;Biobío&quot;,&quot;Bío Bío&quot;],[&quot;Coquimbo&quot;,&quot;Coquimbo&quot;],[&quot;Los Lagos&quot;,&quot;Los Lagos&quot;],[&quot;Los Ríos&quot;,&quot;Los Ríos&quot;],[&quot;Magallanes&quot;,&quot;Magallanes Region&quot;],[&quot;Maule&quot;,&quot;Maule&quot;],[&quot;O&#39;Higgins&quot;,&quot;Libertador General Bernardo O’Higgins&quot;],[&quot;Santiago&quot;,&quot;Santiago Metropolitan&quot;],[&quot;Tarapacá&quot;,&quot;Tarapacá&quot;],[&quot;Valparaíso&quot;,&quot;Valparaíso&quot;],[&quot;Ñuble&quot;,&quot;Ñuble&quot;]]">Chile</option>
<option value="China" data-provinces="[[&quot;Anhui&quot;,&quot;Anhui&quot;],[&quot;Beijing&quot;,&quot;Beijing&quot;],[&quot;Chongqing&quot;,&quot;Chongqing&quot;],[&quot;Fujian&quot;,&quot;Fujian&quot;],[&quot;Gansu&quot;,&quot;Gansu&quot;],[&quot;Guangdong&quot;,&quot;Guangdong&quot;],[&quot;Guangxi&quot;,&quot;Guangxi&quot;],[&quot;Guizhou&quot;,&quot;Guizhou&quot;],[&quot;Hainan&quot;,&quot;Hainan&quot;],[&quot;Hebei&quot;,&quot;Hebei&quot;],[&quot;Heilongjiang&quot;,&quot;Heilongjiang&quot;],[&quot;Henan&quot;,&quot;Henan&quot;],[&quot;Hubei&quot;,&quot;Hubei&quot;],[&quot;Hunan&quot;,&quot;Hunan&quot;],[&quot;Inner Mongolia&quot;,&quot;Inner Mongolia&quot;],[&quot;Jiangsu&quot;,&quot;Jiangsu&quot;],[&quot;Jiangxi&quot;,&quot;Jiangxi&quot;],[&quot;Jilin&quot;,&quot;Jilin&quot;],[&quot;Liaoning&quot;,&quot;Liaoning&quot;],[&quot;Ningxia&quot;,&quot;Ningxia&quot;],[&quot;Qinghai&quot;,&quot;Qinghai&quot;],[&quot;Shaanxi&quot;,&quot;Shaanxi&quot;],[&quot;Shandong&quot;,&quot;Shandong&quot;],[&quot;Shanghai&quot;,&quot;Shanghai&quot;],[&quot;Shanxi&quot;,&quot;Shanxi&quot;],[&quot;Sichuan&quot;,&quot;Sichuan&quot;],[&quot;Tianjin&quot;,&quot;Tianjin&quot;],[&quot;Xinjiang&quot;,&quot;Xinjiang&quot;],[&quot;Xizang&quot;,&quot;Tibet&quot;],[&quot;Yunnan&quot;,&quot;Yunnan&quot;],[&quot;Zhejiang&quot;,&quot;Zhejiang&quot;]]">China</option>
<option value="Christmas Island" data-provinces="[]">Christmas Island</option>
<option value="Cocos (Keeling) Islands" data-provinces="[]">Cocos (Keeling) Islands</option>
<option value="Colombia" data-provinces="[[&quot;Amazonas&quot;,&quot;Amazonas&quot;],[&quot;Antioquia&quot;,&quot;Antioquia&quot;],[&quot;Arauca&quot;,&quot;Arauca&quot;],[&quot;Atlántico&quot;,&quot;Atlántico&quot;],[&quot;Bogotá, D.C.&quot;,&quot;Capital District&quot;],[&quot;Bolívar&quot;,&quot;Bolívar&quot;],[&quot;Boyacá&quot;,&quot;Boyacá&quot;],[&quot;Caldas&quot;,&quot;Caldas&quot;],[&quot;Caquetá&quot;,&quot;Caquetá&quot;],[&quot;Casanare&quot;,&quot;Casanare&quot;],[&quot;Cauca&quot;,&quot;Cauca&quot;],[&quot;Cesar&quot;,&quot;Cesar&quot;],[&quot;Chocó&quot;,&quot;Chocó&quot;],[&quot;Cundinamarca&quot;,&quot;Cundinamarca&quot;],[&quot;Córdoba&quot;,&quot;Córdoba&quot;],[&quot;Guainía&quot;,&quot;Guainía&quot;],[&quot;Guaviare&quot;,&quot;Guaviare&quot;],[&quot;Huila&quot;,&quot;Huila&quot;],[&quot;La Guajira&quot;,&quot;La Guajira&quot;],[&quot;Magdalena&quot;,&quot;Magdalena&quot;],[&quot;Meta&quot;,&quot;Meta&quot;],[&quot;Nariño&quot;,&quot;Nariño&quot;],[&quot;Norte de Santander&quot;,&quot;Norte de Santander&quot;],[&quot;Putumayo&quot;,&quot;Putumayo&quot;],[&quot;Quindío&quot;,&quot;Quindío&quot;],[&quot;Risaralda&quot;,&quot;Risaralda&quot;],[&quot;San Andrés, Providencia y Santa Catalina&quot;,&quot;San Andrés \u0026 Providencia&quot;],[&quot;Santander&quot;,&quot;Santander&quot;],[&quot;Sucre&quot;,&quot;Sucre&quot;],[&quot;Tolima&quot;,&quot;Tolima&quot;],[&quot;Valle del Cauca&quot;,&quot;Valle del Cauca&quot;],[&quot;Vaupés&quot;,&quot;Vaupés&quot;],[&quot;Vichada&quot;,&quot;Vichada&quot;]]">Colombia</option>
<option value="Comoros" data-provinces="[]">Comoros</option>
<option value="Congo" data-provinces="[]">Congo - Brazzaville</option>
<option value="Congo, The Democratic Republic Of The" data-provinces="[]">Congo - Kinshasa</option>
<option value="Cook Islands" data-provinces="[]">Cook Islands</option>
<option value="Costa Rica" data-provinces="[[&quot;Alajuela&quot;,&quot;Alajuela&quot;],[&quot;Cartago&quot;,&quot;Cartago&quot;],[&quot;Guanacaste&quot;,&quot;Guanacaste&quot;],[&quot;Heredia&quot;,&quot;Heredia&quot;],[&quot;Limón&quot;,&quot;Limón&quot;],[&quot;Puntarenas&quot;,&quot;Puntarenas&quot;],[&quot;San José&quot;,&quot;San José&quot;]]">Costa Rica</option>
<option value="Croatia" data-provinces="[]">Croatia</option>
<option value="Curaçao" data-provinces="[]">Curaçao</option>
<option value="Cyprus" data-provinces="[]">Cyprus</option>
<option value="Czech Republic" data-provinces="[]">Czechia</option>
<option value="Côte d'Ivoire" data-provinces="[]">Côte d’Ivoire</option>
<option value="Denmark" data-provinces="[]">Denmark</option>
<option value="Djibouti" data-provinces="[]">Djibouti</option>
<option value="Dominica" data-provinces="[]">Dominica</option>
<option value="Dominican Republic" data-provinces="[]">Dominican Republic</option>
<option value="Ecuador" data-provinces="[]">Ecuador</option>
<option value="Egypt" data-provinces="[[&quot;6th of October&quot;,&quot;6th of October&quot;],[&quot;Al Sharqia&quot;,&quot;Al Sharqia&quot;],[&quot;Alexandria&quot;,&quot;Alexandria&quot;],[&quot;Aswan&quot;,&quot;Aswan&quot;],[&quot;Asyut&quot;,&quot;Asyut&quot;],[&quot;Beheira&quot;,&quot;Beheira&quot;],[&quot;Beni Suef&quot;,&quot;Beni Suef&quot;],[&quot;Cairo&quot;,&quot;Cairo&quot;],[&quot;Dakahlia&quot;,&quot;Dakahlia&quot;],[&quot;Damietta&quot;,&quot;Damietta&quot;],[&quot;Faiyum&quot;,&quot;Faiyum&quot;],[&quot;Gharbia&quot;,&quot;Gharbia&quot;],[&quot;Giza&quot;,&quot;Giza&quot;],[&quot;Helwan&quot;,&quot;Helwan&quot;],[&quot;Ismailia&quot;,&quot;Ismailia&quot;],[&quot;Kafr el-Sheikh&quot;,&quot;Kafr el-Sheikh&quot;],[&quot;Luxor&quot;,&quot;Luxor&quot;],[&quot;Matrouh&quot;,&quot;Matrouh&quot;],[&quot;Minya&quot;,&quot;Minya&quot;],[&quot;Monufia&quot;,&quot;Monufia&quot;],[&quot;New Valley&quot;,&quot;New Valley&quot;],[&quot;North Sinai&quot;,&quot;North Sinai&quot;],[&quot;Port Said&quot;,&quot;Port Said&quot;],[&quot;Qalyubia&quot;,&quot;Qalyubia&quot;],[&quot;Qena&quot;,&quot;Qena&quot;],[&quot;Red Sea&quot;,&quot;Red Sea&quot;],[&quot;Sohag&quot;,&quot;Sohag&quot;],[&quot;South Sinai&quot;,&quot;South Sinai&quot;],[&quot;Suez&quot;,&quot;Suez&quot;]]">Egypt</option>
<option value="El Salvador" data-provinces="[[&quot;Ahuachapán&quot;,&quot;Ahuachapán&quot;],[&quot;Cabañas&quot;,&quot;Cabañas&quot;],[&quot;Chalatenango&quot;,&quot;Chalatenango&quot;],[&quot;Cuscatlán&quot;,&quot;Cuscatlán&quot;],[&quot;La Libertad&quot;,&quot;La Libertad&quot;],[&quot;La Paz&quot;,&quot;La Paz&quot;],[&quot;La Unión&quot;,&quot;La Unión&quot;],[&quot;Morazán&quot;,&quot;Morazán&quot;],[&quot;San Miguel&quot;,&quot;San Miguel&quot;],[&quot;San Salvador&quot;,&quot;San Salvador&quot;],[&quot;San Vicente&quot;,&quot;San Vicente&quot;],[&quot;Santa Ana&quot;,&quot;Santa Ana&quot;],[&quot;Sonsonate&quot;,&quot;Sonsonate&quot;],[&quot;Usulután&quot;,&quot;Usulután&quot;]]">El Salvador</option>
<option value="Equatorial Guinea" data-provinces="[]">Equatorial Guinea</option>
<option value="Eritrea" data-provinces="[]">Eritrea</option>
<option value="Estonia" data-provinces="[]">Estonia</option>
<option value="Eswatini" data-provinces="[]">Eswatini</option>
<option value="Ethiopia" data-provinces="[]">Ethiopia</option>
<option value="Falkland Islands (Malvinas)" data-provinces="[]">Falkland Islands</option>
<option value="Faroe Islands" data-provinces="[]">Faroe Islands</option>
<option value="Fiji" data-provinces="[]">Fiji</option>
<option value="Finland" data-provinces="[]">Finland</option>
<option value="France" data-provinces="[]">France</option>
<option value="French Guiana" data-provinces="[]">French Guiana</option>
<option value="French Polynesia" data-provinces="[]">French Polynesia</option>
<option value="French Southern Territories" data-provinces="[]">French Southern Territories</option>
<option value="Gabon" data-provinces="[]">Gabon</option>
<option value="Gambia" data-provinces="[]">Gambia</option>
<option value="Georgia" data-provinces="[]">Georgia</option>
<option value="Germany" data-provinces="[]">Germany</option>
<option value="Ghana" data-provinces="[]">Ghana</option>
<option value="Gibraltar" data-provinces="[]">Gibraltar</option>
<option value="Greece" data-provinces="[]">Greece</option>
<option value="Greenland" data-provinces="[]">Greenland</option>
<option value="Grenada" data-provinces="[]">Grenada</option>
<option value="Guadeloupe" data-provinces="[]">Guadeloupe</option>
<option value="Guatemala" data-provinces="[[&quot;Alta Verapaz&quot;,&quot;Alta Verapaz&quot;],[&quot;Baja Verapaz&quot;,&quot;Baja Verapaz&quot;],[&quot;Chimaltenango&quot;,&quot;Chimaltenango&quot;],[&quot;Chiquimula&quot;,&quot;Chiquimula&quot;],[&quot;El Progreso&quot;,&quot;El Progreso&quot;],[&quot;Escuintla&quot;,&quot;Escuintla&quot;],[&quot;Guatemala&quot;,&quot;Guatemala&quot;],[&quot;Huehuetenango&quot;,&quot;Huehuetenango&quot;],[&quot;Izabal&quot;,&quot;Izabal&quot;],[&quot;Jalapa&quot;,&quot;Jalapa&quot;],[&quot;Jutiapa&quot;,&quot;Jutiapa&quot;],[&quot;Petén&quot;,&quot;Petén&quot;],[&quot;Quetzaltenango&quot;,&quot;Quetzaltenango&quot;],[&quot;Quiché&quot;,&quot;Quiché&quot;],[&quot;Retalhuleu&quot;,&quot;Retalhuleu&quot;],[&quot;Sacatepéquez&quot;,&quot;Sacatepéquez&quot;],[&quot;San Marcos&quot;,&quot;San Marcos&quot;],[&quot;Santa Rosa&quot;,&quot;Santa Rosa&quot;],[&quot;Sololá&quot;,&quot;Sololá&quot;],[&quot;Suchitepéquez&quot;,&quot;Suchitepéquez&quot;],[&quot;Totonicapán&quot;,&quot;Totonicapán&quot;],[&quot;Zacapa&quot;,&quot;Zacapa&quot;]]">Guatemala</option>
<option value="Guernsey" data-provinces="[]">Guernsey</option>
<option value="Guinea" data-provinces="[]">Guinea</option>
<option value="Guinea Bissau" data-provinces="[]">Guinea-Bissau</option>
<option value="Guyana" data-provinces="[]">Guyana</option>
<option value="Haiti" data-provinces="[]">Haiti</option>
<option value="Honduras" data-provinces="[]">Honduras</option>
<option value="Hong Kong" data-provinces="[[&quot;Hong Kong Island&quot;,&quot;Hong Kong Island&quot;],[&quot;Kowloon&quot;,&quot;Kowloon&quot;],[&quot;New Territories&quot;,&quot;New Territories&quot;]]">Hong Kong SAR</option>
<option value="Hungary" data-provinces="[]">Hungary</option>
<option value="Iceland" data-provinces="[]">Iceland</option>
<option value="India" data-provinces="[[&quot;Andaman and Nicobar Islands&quot;,&quot;Andaman and Nicobar Islands&quot;],[&quot;Andhra Pradesh&quot;,&quot;Andhra Pradesh&quot;],[&quot;Arunachal Pradesh&quot;,&quot;Arunachal Pradesh&quot;],[&quot;Assam&quot;,&quot;Assam&quot;],[&quot;Bihar&quot;,&quot;Bihar&quot;],[&quot;Chandigarh&quot;,&quot;Chandigarh&quot;],[&quot;Chhattisgarh&quot;,&quot;Chhattisgarh&quot;],[&quot;Dadra and Nagar Haveli&quot;,&quot;Dadra and Nagar Haveli&quot;],[&quot;Daman and Diu&quot;,&quot;Daman and Diu&quot;],[&quot;Delhi&quot;,&quot;Delhi&quot;],[&quot;Goa&quot;,&quot;Goa&quot;],[&quot;Gujarat&quot;,&quot;Gujarat&quot;],[&quot;Haryana&quot;,&quot;Haryana&quot;],[&quot;Himachal Pradesh&quot;,&quot;Himachal Pradesh&quot;],[&quot;Jammu and Kashmir&quot;,&quot;Jammu and Kashmir&quot;],[&quot;Jharkhand&quot;,&quot;Jharkhand&quot;],[&quot;Karnataka&quot;,&quot;Karnataka&quot;],[&quot;Kerala&quot;,&quot;Kerala&quot;],[&quot;Ladakh&quot;,&quot;Ladakh&quot;],[&quot;Lakshadweep&quot;,&quot;Lakshadweep&quot;],[&quot;Madhya Pradesh&quot;,&quot;Madhya Pradesh&quot;],[&quot;Maharashtra&quot;,&quot;Maharashtra&quot;],[&quot;Manipur&quot;,&quot;Manipur&quot;],[&quot;Meghalaya&quot;,&quot;Meghalaya&quot;],[&quot;Mizoram&quot;,&quot;Mizoram&quot;],[&quot;Nagaland&quot;,&quot;Nagaland&quot;],[&quot;Odisha&quot;,&quot;Odisha&quot;],[&quot;Puducherry&quot;,&quot;Puducherry&quot;],[&quot;Punjab&quot;,&quot;Punjab&quot;],[&quot;Rajasthan&quot;,&quot;Rajasthan&quot;],[&quot;Sikkim&quot;,&quot;Sikkim&quot;],[&quot;Tamil Nadu&quot;,&quot;Tamil Nadu&quot;],[&quot;Telangana&quot;,&quot;Telangana&quot;],[&quot;Tripura&quot;,&quot;Tripura&quot;],[&quot;Uttar Pradesh&quot;,&quot;Uttar Pradesh&quot;],[&quot;Uttarakhand&quot;,&quot;Uttarakhand&quot;],[&quot;West Bengal&quot;,&quot;West Bengal&quot;]]">India</option>
<option value="Indonesia" data-provinces="[[&quot;Aceh&quot;,&quot;Aceh&quot;],[&quot;Bali&quot;,&quot;Bali&quot;],[&quot;Bangka Belitung&quot;,&quot;Bangka–Belitung Islands&quot;],[&quot;Banten&quot;,&quot;Banten&quot;],[&quot;Bengkulu&quot;,&quot;Bengkulu&quot;],[&quot;Gorontalo&quot;,&quot;Gorontalo&quot;],[&quot;Jakarta&quot;,&quot;Jakarta&quot;],[&quot;Jambi&quot;,&quot;Jambi&quot;],[&quot;Jawa Barat&quot;,&quot;West Java&quot;],[&quot;Jawa Tengah&quot;,&quot;Central Java&quot;],[&quot;Jawa Timur&quot;,&quot;East Java&quot;],[&quot;Kalimantan Barat&quot;,&quot;West Kalimantan&quot;],[&quot;Kalimantan Selatan&quot;,&quot;South Kalimantan&quot;],[&quot;Kalimantan Tengah&quot;,&quot;Central Kalimantan&quot;],[&quot;Kalimantan Timur&quot;,&quot;East Kalimantan&quot;],[&quot;Kalimantan Utara&quot;,&quot;North Kalimantan&quot;],[&quot;Kepulauan Riau&quot;,&quot;Riau Islands&quot;],[&quot;Lampung&quot;,&quot;Lampung&quot;],[&quot;Maluku&quot;,&quot;Maluku&quot;],[&quot;Maluku Utara&quot;,&quot;North Maluku&quot;],[&quot;North Sumatra&quot;,&quot;North Sumatra&quot;],[&quot;Nusa Tenggara Barat&quot;,&quot;West Nusa Tenggara&quot;],[&quot;Nusa Tenggara Timur&quot;,&quot;East Nusa Tenggara&quot;],[&quot;Papua&quot;,&quot;Papua&quot;],[&quot;Papua Barat&quot;,&quot;West Papua&quot;],[&quot;Riau&quot;,&quot;Riau&quot;],[&quot;South Sumatra&quot;,&quot;South Sumatra&quot;],[&quot;Sulawesi Barat&quot;,&quot;West Sulawesi&quot;],[&quot;Sulawesi Selatan&quot;,&quot;South Sulawesi&quot;],[&quot;Sulawesi Tengah&quot;,&quot;Central Sulawesi&quot;],[&quot;Sulawesi Tenggara&quot;,&quot;Southeast Sulawesi&quot;],[&quot;Sulawesi Utara&quot;,&quot;North Sulawesi&quot;],[&quot;West Sumatra&quot;,&quot;West Sumatra&quot;],[&quot;Yogyakarta&quot;,&quot;Yogyakarta&quot;]]">Indonesia</option>
<option value="Iraq" data-provinces="[]">Iraq</option>
<option value="Ireland" data-provinces="[[&quot;Carlow&quot;,&quot;Carlow&quot;],[&quot;Cavan&quot;,&quot;Cavan&quot;],[&quot;Clare&quot;,&quot;Clare&quot;],[&quot;Cork&quot;,&quot;Cork&quot;],[&quot;Donegal&quot;,&quot;Donegal&quot;],[&quot;Dublin&quot;,&quot;Dublin&quot;],[&quot;Galway&quot;,&quot;Galway&quot;],[&quot;Kerry&quot;,&quot;Kerry&quot;],[&quot;Kildare&quot;,&quot;Kildare&quot;],[&quot;Kilkenny&quot;,&quot;Kilkenny&quot;],[&quot;Laois&quot;,&quot;Laois&quot;],[&quot;Leitrim&quot;,&quot;Leitrim&quot;],[&quot;Limerick&quot;,&quot;Limerick&quot;],[&quot;Longford&quot;,&quot;Longford&quot;],[&quot;Louth&quot;,&quot;Louth&quot;],[&quot;Mayo&quot;,&quot;Mayo&quot;],[&quot;Meath&quot;,&quot;Meath&quot;],[&quot;Monaghan&quot;,&quot;Monaghan&quot;],[&quot;Offaly&quot;,&quot;Offaly&quot;],[&quot;Roscommon&quot;,&quot;Roscommon&quot;],[&quot;Sligo&quot;,&quot;Sligo&quot;],[&quot;Tipperary&quot;,&quot;Tipperary&quot;],[&quot;Waterford&quot;,&quot;Waterford&quot;],[&quot;Westmeath&quot;,&quot;Westmeath&quot;],[&quot;Wexford&quot;,&quot;Wexford&quot;],[&quot;Wicklow&quot;,&quot;Wicklow&quot;]]">Ireland</option>
<option value="Isle Of Man" data-provinces="[]">Isle of Man</option>
<option value="Israel" data-provinces="[]">Israel</option>
<option value="Italy" data-provinces="[[&quot;Agrigento&quot;,&quot;Agrigento&quot;],[&quot;Alessandria&quot;,&quot;Alessandria&quot;],[&quot;Ancona&quot;,&quot;Ancona&quot;],[&quot;Aosta&quot;,&quot;Aosta Valley&quot;],[&quot;Arezzo&quot;,&quot;Arezzo&quot;],[&quot;Ascoli Piceno&quot;,&quot;Ascoli Piceno&quot;],[&quot;Asti&quot;,&quot;Asti&quot;],[&quot;Avellino&quot;,&quot;Avellino&quot;],[&quot;Bari&quot;,&quot;Bari&quot;],[&quot;Barletta-Andria-Trani&quot;,&quot;Barletta-Andria-Trani&quot;],[&quot;Belluno&quot;,&quot;Belluno&quot;],[&quot;Benevento&quot;,&quot;Benevento&quot;],[&quot;Bergamo&quot;,&quot;Bergamo&quot;],[&quot;Biella&quot;,&quot;Biella&quot;],[&quot;Bologna&quot;,&quot;Bologna&quot;],[&quot;Bolzano&quot;,&quot;South Tyrol&quot;],[&quot;Brescia&quot;,&quot;Brescia&quot;],[&quot;Brindisi&quot;,&quot;Brindisi&quot;],[&quot;Cagliari&quot;,&quot;Cagliari&quot;],[&quot;Caltanissetta&quot;,&quot;Caltanissetta&quot;],[&quot;Campobasso&quot;,&quot;Campobasso&quot;],[&quot;Carbonia-Iglesias&quot;,&quot;Carbonia-Iglesias&quot;],[&quot;Caserta&quot;,&quot;Caserta&quot;],[&quot;Catania&quot;,&quot;Catania&quot;],[&quot;Catanzaro&quot;,&quot;Catanzaro&quot;],[&quot;Chieti&quot;,&quot;Chieti&quot;],[&quot;Como&quot;,&quot;Como&quot;],[&quot;Cosenza&quot;,&quot;Cosenza&quot;],[&quot;Cremona&quot;,&quot;Cremona&quot;],[&quot;Crotone&quot;,&quot;Crotone&quot;],[&quot;Cuneo&quot;,&quot;Cuneo&quot;],[&quot;Enna&quot;,&quot;Enna&quot;],[&quot;Fermo&quot;,&quot;Fermo&quot;],[&quot;Ferrara&quot;,&quot;Ferrara&quot;],[&quot;Firenze&quot;,&quot;Florence&quot;],[&quot;Foggia&quot;,&quot;Foggia&quot;],[&quot;Forlì-Cesena&quot;,&quot;Forlì-Cesena&quot;],[&quot;Frosinone&quot;,&quot;Frosinone&quot;],[&quot;Genova&quot;,&quot;Genoa&quot;],[&quot;Gorizia&quot;,&quot;Gorizia&quot;],[&quot;Grosseto&quot;,&quot;Grosseto&quot;],[&quot;Imperia&quot;,&quot;Imperia&quot;],[&quot;Isernia&quot;,&quot;Isernia&quot;],[&quot;L&#39;Aquila&quot;,&quot;L’Aquila&quot;],[&quot;La Spezia&quot;,&quot;La Spezia&quot;],[&quot;Latina&quot;,&quot;Latina&quot;],[&quot;Lecce&quot;,&quot;Lecce&quot;],[&quot;Lecco&quot;,&quot;Lecco&quot;],[&quot;Livorno&quot;,&quot;Livorno&quot;],[&quot;Lodi&quot;,&quot;Lodi&quot;],[&quot;Lucca&quot;,&quot;Lucca&quot;],[&quot;Macerata&quot;,&quot;Macerata&quot;],[&quot;Mantova&quot;,&quot;Mantua&quot;],[&quot;Massa-Carrara&quot;,&quot;Massa and Carrara&quot;],[&quot;Matera&quot;,&quot;Matera&quot;],[&quot;Medio Campidano&quot;,&quot;Medio Campidano&quot;],[&quot;Messina&quot;,&quot;Messina&quot;],[&quot;Milano&quot;,&quot;Milan&quot;],[&quot;Modena&quot;,&quot;Modena&quot;],[&quot;Monza e Brianza&quot;,&quot;Monza and Brianza&quot;],[&quot;Napoli&quot;,&quot;Naples&quot;],[&quot;Novara&quot;,&quot;Novara&quot;],[&quot;Nuoro&quot;,&quot;Nuoro&quot;],[&quot;Ogliastra&quot;,&quot;Ogliastra&quot;],[&quot;Olbia-Tempio&quot;,&quot;Olbia-Tempio&quot;],[&quot;Oristano&quot;,&quot;Oristano&quot;],[&quot;Padova&quot;,&quot;Padua&quot;],[&quot;Palermo&quot;,&quot;Palermo&quot;],[&quot;Parma&quot;,&quot;Parma&quot;],[&quot;Pavia&quot;,&quot;Pavia&quot;],[&quot;Perugia&quot;,&quot;Perugia&quot;],[&quot;Pesaro e Urbino&quot;,&quot;Pesaro and Urbino&quot;],[&quot;Pescara&quot;,&quot;Pescara&quot;],[&quot;Piacenza&quot;,&quot;Piacenza&quot;],[&quot;Pisa&quot;,&quot;Pisa&quot;],[&quot;Pistoia&quot;,&quot;Pistoia&quot;],[&quot;Pordenone&quot;,&quot;Pordenone&quot;],[&quot;Potenza&quot;,&quot;Potenza&quot;],[&quot;Prato&quot;,&quot;Prato&quot;],[&quot;Ragusa&quot;,&quot;Ragusa&quot;],[&quot;Ravenna&quot;,&quot;Ravenna&quot;],[&quot;Reggio Calabria&quot;,&quot;Reggio Calabria&quot;],[&quot;Reggio Emilia&quot;,&quot;Reggio Emilia&quot;],[&quot;Rieti&quot;,&quot;Rieti&quot;],[&quot;Rimini&quot;,&quot;Rimini&quot;],[&quot;Roma&quot;,&quot;Rome&quot;],[&quot;Rovigo&quot;,&quot;Rovigo&quot;],[&quot;Salerno&quot;,&quot;Salerno&quot;],[&quot;Sassari&quot;,&quot;Sassari&quot;],[&quot;Savona&quot;,&quot;Savona&quot;],[&quot;Siena&quot;,&quot;Siena&quot;],[&quot;Siracusa&quot;,&quot;Syracuse&quot;],[&quot;Sondrio&quot;,&quot;Sondrio&quot;],[&quot;Taranto&quot;,&quot;Taranto&quot;],[&quot;Teramo&quot;,&quot;Teramo&quot;],[&quot;Terni&quot;,&quot;Terni&quot;],[&quot;Torino&quot;,&quot;Turin&quot;],[&quot;Trapani&quot;,&quot;Trapani&quot;],[&quot;Trento&quot;,&quot;Trentino&quot;],[&quot;Treviso&quot;,&quot;Treviso&quot;],[&quot;Trieste&quot;,&quot;Trieste&quot;],[&quot;Udine&quot;,&quot;Udine&quot;],[&quot;Varese&quot;,&quot;Varese&quot;],[&quot;Venezia&quot;,&quot;Venice&quot;],[&quot;Verbano-Cusio-Ossola&quot;,&quot;Verbano-Cusio-Ossola&quot;],[&quot;Vercelli&quot;,&quot;Vercelli&quot;],[&quot;Verona&quot;,&quot;Verona&quot;],[&quot;Vibo Valentia&quot;,&quot;Vibo Valentia&quot;],[&quot;Vicenza&quot;,&quot;Vicenza&quot;],[&quot;Viterbo&quot;,&quot;Viterbo&quot;]]">Italy</option>
<option value="Jamaica" data-provinces="[]">Jamaica</option>
<option value="Japan" data-provinces="[[&quot;Aichi&quot;,&quot;Aichi&quot;],[&quot;Akita&quot;,&quot;Akita&quot;],[&quot;Aomori&quot;,&quot;Aomori&quot;],[&quot;Chiba&quot;,&quot;Chiba&quot;],[&quot;Ehime&quot;,&quot;Ehime&quot;],[&quot;Fukui&quot;,&quot;Fukui&quot;],[&quot;Fukuoka&quot;,&quot;Fukuoka&quot;],[&quot;Fukushima&quot;,&quot;Fukushima&quot;],[&quot;Gifu&quot;,&quot;Gifu&quot;],[&quot;Gunma&quot;,&quot;Gunma&quot;],[&quot;Hiroshima&quot;,&quot;Hiroshima&quot;],[&quot;Hokkaidō&quot;,&quot;Hokkaido&quot;],[&quot;Hyōgo&quot;,&quot;Hyogo&quot;],[&quot;Ibaraki&quot;,&quot;Ibaraki&quot;],[&quot;Ishikawa&quot;,&quot;Ishikawa&quot;],[&quot;Iwate&quot;,&quot;Iwate&quot;],[&quot;Kagawa&quot;,&quot;Kagawa&quot;],[&quot;Kagoshima&quot;,&quot;Kagoshima&quot;],[&quot;Kanagawa&quot;,&quot;Kanagawa&quot;],[&quot;Kumamoto&quot;,&quot;Kumamoto&quot;],[&quot;Kyōto&quot;,&quot;Kyoto&quot;],[&quot;Kōchi&quot;,&quot;Kochi&quot;],[&quot;Mie&quot;,&quot;Mie&quot;],[&quot;Miyagi&quot;,&quot;Miyagi&quot;],[&quot;Miyazaki&quot;,&quot;Miyazaki&quot;],[&quot;Nagano&quot;,&quot;Nagano&quot;],[&quot;Nagasaki&quot;,&quot;Nagasaki&quot;],[&quot;Nara&quot;,&quot;Nara&quot;],[&quot;Niigata&quot;,&quot;Niigata&quot;],[&quot;Okayama&quot;,&quot;Okayama&quot;],[&quot;Okinawa&quot;,&quot;Okinawa&quot;],[&quot;Saga&quot;,&quot;Saga&quot;],[&quot;Saitama&quot;,&quot;Saitama&quot;],[&quot;Shiga&quot;,&quot;Shiga&quot;],[&quot;Shimane&quot;,&quot;Shimane&quot;],[&quot;Shizuoka&quot;,&quot;Shizuoka&quot;],[&quot;Tochigi&quot;,&quot;Tochigi&quot;],[&quot;Tokushima&quot;,&quot;Tokushima&quot;],[&quot;Tottori&quot;,&quot;Tottori&quot;],[&quot;Toyama&quot;,&quot;Toyama&quot;],[&quot;Tōkyō&quot;,&quot;Tokyo&quot;],[&quot;Wakayama&quot;,&quot;Wakayama&quot;],[&quot;Yamagata&quot;,&quot;Yamagata&quot;],[&quot;Yamaguchi&quot;,&quot;Yamaguchi&quot;],[&quot;Yamanashi&quot;,&quot;Yamanashi&quot;],[&quot;Ōita&quot;,&quot;Oita&quot;],[&quot;Ōsaka&quot;,&quot;Osaka&quot;]]">Japan</option>
<option value="Jersey" data-provinces="[]">Jersey</option>
<option value="Jordan" data-provinces="[]">Jordan</option>
<option value="Kazakhstan" data-provinces="[]">Kazakhstan</option>
<option value="Kenya" data-provinces="[]">Kenya</option>
<option value="Kiribati" data-provinces="[]">Kiribati</option>
<option value="Kosovo" data-provinces="[]">Kosovo</option>
<option value="Kuwait" data-provinces="[[&quot;Al Ahmadi&quot;,&quot;Al Ahmadi&quot;],[&quot;Al Asimah&quot;,&quot;Al Asimah&quot;],[&quot;Al Farwaniyah&quot;,&quot;Al Farwaniyah&quot;],[&quot;Al Jahra&quot;,&quot;Al Jahra&quot;],[&quot;Hawalli&quot;,&quot;Hawalli&quot;],[&quot;Mubarak Al-Kabeer&quot;,&quot;Mubarak Al-Kabeer&quot;]]">Kuwait</option>
<option value="Kyrgyzstan" data-provinces="[]">Kyrgyzstan</option>
<option value="Lao People's Democratic Republic" data-provinces="[]">Laos</option>
<option value="Latvia" data-provinces="[]">Latvia</option>
<option value="Lebanon" data-provinces="[]">Lebanon</option>
<option value="Lesotho" data-provinces="[]">Lesotho</option>
<option value="Liberia" data-provinces="[]">Liberia</option>
<option value="Libyan Arab Jamahiriya" data-provinces="[]">Libya</option>
<option value="Liechtenstein" data-provinces="[]">Liechtenstein</option>
<option value="Lithuania" data-provinces="[]">Lithuania</option>
<option value="Luxembourg" data-provinces="[]">Luxembourg</option>
<option value="Macao" data-provinces="[]">Macao SAR</option>
<option value="Madagascar" data-provinces="[]">Madagascar</option>
<option value="Malawi" data-provinces="[]">Malawi</option>
<option value="Malaysia" data-provinces="[[&quot;Johor&quot;,&quot;Johor&quot;],[&quot;Kedah&quot;,&quot;Kedah&quot;],[&quot;Kelantan&quot;,&quot;Kelantan&quot;],[&quot;Kuala Lumpur&quot;,&quot;Kuala Lumpur&quot;],[&quot;Labuan&quot;,&quot;Labuan&quot;],[&quot;Melaka&quot;,&quot;Malacca&quot;],[&quot;Negeri Sembilan&quot;,&quot;Negeri Sembilan&quot;],[&quot;Pahang&quot;,&quot;Pahang&quot;],[&quot;Penang&quot;,&quot;Penang&quot;],[&quot;Perak&quot;,&quot;Perak&quot;],[&quot;Perlis&quot;,&quot;Perlis&quot;],[&quot;Putrajaya&quot;,&quot;Putrajaya&quot;],[&quot;Sabah&quot;,&quot;Sabah&quot;],[&quot;Sarawak&quot;,&quot;Sarawak&quot;],[&quot;Selangor&quot;,&quot;Selangor&quot;],[&quot;Terengganu&quot;,&quot;Terengganu&quot;]]">Malaysia</option>
<option value="Maldives" data-provinces="[]">Maldives</option>
<option value="Mali" data-provinces="[]">Mali</option>
<option value="Malta" data-provinces="[]">Malta</option>
<option value="Martinique" data-provinces="[]">Martinique</option>
<option value="Mauritania" data-provinces="[]">Mauritania</option>
<option value="Mauritius" data-provinces="[]">Mauritius</option>
<option value="Mayotte" data-provinces="[]">Mayotte</option>
<option value="Mexico" data-provinces="[[&quot;Aguascalientes&quot;,&quot;Aguascalientes&quot;],[&quot;Baja California&quot;,&quot;Baja California&quot;],[&quot;Baja California Sur&quot;,&quot;Baja California Sur&quot;],[&quot;Campeche&quot;,&quot;Campeche&quot;],[&quot;Chiapas&quot;,&quot;Chiapas&quot;],[&quot;Chihuahua&quot;,&quot;Chihuahua&quot;],[&quot;Ciudad de México&quot;,&quot;Ciudad de Mexico&quot;],[&quot;Coahuila&quot;,&quot;Coahuila&quot;],[&quot;Colima&quot;,&quot;Colima&quot;],[&quot;Durango&quot;,&quot;Durango&quot;],[&quot;Guanajuato&quot;,&quot;Guanajuato&quot;],[&quot;Guerrero&quot;,&quot;Guerrero&quot;],[&quot;Hidalgo&quot;,&quot;Hidalgo&quot;],[&quot;Jalisco&quot;,&quot;Jalisco&quot;],[&quot;Michoacán&quot;,&quot;Michoacán&quot;],[&quot;Morelos&quot;,&quot;Morelos&quot;],[&quot;México&quot;,&quot;Mexico State&quot;],[&quot;Nayarit&quot;,&quot;Nayarit&quot;],[&quot;Nuevo León&quot;,&quot;Nuevo León&quot;],[&quot;Oaxaca&quot;,&quot;Oaxaca&quot;],[&quot;Puebla&quot;,&quot;Puebla&quot;],[&quot;Querétaro&quot;,&quot;Querétaro&quot;],[&quot;Quintana Roo&quot;,&quot;Quintana Roo&quot;],[&quot;San Luis Potosí&quot;,&quot;San Luis Potosí&quot;],[&quot;Sinaloa&quot;,&quot;Sinaloa&quot;],[&quot;Sonora&quot;,&quot;Sonora&quot;],[&quot;Tabasco&quot;,&quot;Tabasco&quot;],[&quot;Tamaulipas&quot;,&quot;Tamaulipas&quot;],[&quot;Tlaxcala&quot;,&quot;Tlaxcala&quot;],[&quot;Veracruz&quot;,&quot;Veracruz&quot;],[&quot;Yucatán&quot;,&quot;Yucatán&quot;],[&quot;Zacatecas&quot;,&quot;Zacatecas&quot;]]">Mexico</option>
<option value="Moldova, Republic of" data-provinces="[]">Moldova</option>
<option value="Monaco" data-provinces="[]">Monaco</option>
<option value="Mongolia" data-provinces="[]">Mongolia</option>
<option value="Montenegro" data-provinces="[]">Montenegro</option>
<option value="Montserrat" data-provinces="[]">Montserrat</option>
<option value="Morocco" data-provinces="[]">Morocco</option>
<option value="Mozambique" data-provinces="[]">Mozambique</option>
<option value="Myanmar" data-provinces="[]">Myanmar (Burma)</option>
<option value="Namibia" data-provinces="[]">Namibia</option>
<option value="Nauru" data-provinces="[]">Nauru</option>
<option value="Nepal" data-provinces="[]">Nepal</option>
<option value="Netherlands" data-provinces="[]">Netherlands</option>
<option value="New Caledonia" data-provinces="[]">New Caledonia</option>
<option value="New Zealand" data-provinces="[[&quot;Auckland&quot;,&quot;Auckland&quot;],[&quot;Bay of Plenty&quot;,&quot;Bay of Plenty&quot;],[&quot;Canterbury&quot;,&quot;Canterbury&quot;],[&quot;Chatham Islands&quot;,&quot;Chatham Islands&quot;],[&quot;Gisborne&quot;,&quot;Gisborne&quot;],[&quot;Hawke&#39;s Bay&quot;,&quot;Hawke’s Bay&quot;],[&quot;Manawatu-Wanganui&quot;,&quot;Manawatū-Whanganui&quot;],[&quot;Marlborough&quot;,&quot;Marlborough&quot;],[&quot;Nelson&quot;,&quot;Nelson&quot;],[&quot;Northland&quot;,&quot;Northland&quot;],[&quot;Otago&quot;,&quot;Otago&quot;],[&quot;Southland&quot;,&quot;Southland&quot;],[&quot;Taranaki&quot;,&quot;Taranaki&quot;],[&quot;Tasman&quot;,&quot;Tasman&quot;],[&quot;Waikato&quot;,&quot;Waikato&quot;],[&quot;Wellington&quot;,&quot;Wellington&quot;],[&quot;West Coast&quot;,&quot;West Coast&quot;]]">New Zealand</option>
<option value="Nicaragua" data-provinces="[]">Nicaragua</option>
<option value="Niger" data-provinces="[]">Niger</option>
<option value="Nigeria" data-provinces="[[&quot;Abia&quot;,&quot;Abia&quot;],[&quot;Abuja Federal Capital Territory&quot;,&quot;Federal Capital Territory&quot;],[&quot;Adamawa&quot;,&quot;Adamawa&quot;],[&quot;Akwa Ibom&quot;,&quot;Akwa Ibom&quot;],[&quot;Anambra&quot;,&quot;Anambra&quot;],[&quot;Bauchi&quot;,&quot;Bauchi&quot;],[&quot;Bayelsa&quot;,&quot;Bayelsa&quot;],[&quot;Benue&quot;,&quot;Benue&quot;],[&quot;Borno&quot;,&quot;Borno&quot;],[&quot;Cross River&quot;,&quot;Cross River&quot;],[&quot;Delta&quot;,&quot;Delta&quot;],[&quot;Ebonyi&quot;,&quot;Ebonyi&quot;],[&quot;Edo&quot;,&quot;Edo&quot;],[&quot;Ekiti&quot;,&quot;Ekiti&quot;],[&quot;Enugu&quot;,&quot;Enugu&quot;],[&quot;Gombe&quot;,&quot;Gombe&quot;],[&quot;Imo&quot;,&quot;Imo&quot;],[&quot;Jigawa&quot;,&quot;Jigawa&quot;],[&quot;Kaduna&quot;,&quot;Kaduna&quot;],[&quot;Kano&quot;,&quot;Kano&quot;],[&quot;Katsina&quot;,&quot;Katsina&quot;],[&quot;Kebbi&quot;,&quot;Kebbi&quot;],[&quot;Kogi&quot;,&quot;Kogi&quot;],[&quot;Kwara&quot;,&quot;Kwara&quot;],[&quot;Lagos&quot;,&quot;Lagos&quot;],[&quot;Nasarawa&quot;,&quot;Nasarawa&quot;],[&quot;Niger&quot;,&quot;Niger&quot;],[&quot;Ogun&quot;,&quot;Ogun&quot;],[&quot;Ondo&quot;,&quot;Ondo&quot;],[&quot;Osun&quot;,&quot;Osun&quot;],[&quot;Oyo&quot;,&quot;Oyo&quot;],[&quot;Plateau&quot;,&quot;Plateau&quot;],[&quot;Rivers&quot;,&quot;Rivers&quot;],[&quot;Sokoto&quot;,&quot;Sokoto&quot;],[&quot;Taraba&quot;,&quot;Taraba&quot;],[&quot;Yobe&quot;,&quot;Yobe&quot;],[&quot;Zamfara&quot;,&quot;Zamfara&quot;]]">Nigeria</option>
<option value="Niue" data-provinces="[]">Niue</option>
<option value="Norfolk Island" data-provinces="[]">Norfolk Island</option>
<option value="North Macedonia" data-provinces="[]">North Macedonia</option>
<option value="Norway" data-provinces="[]">Norway</option>
<option value="Oman" data-provinces="[]">Oman</option>
<option value="Pakistan" data-provinces="[]">Pakistan</option>
<option value="Palestinian Territory, Occupied" data-provinces="[]">Palestinian Territories</option>
<option value="Panama" data-provinces="[[&quot;Bocas del Toro&quot;,&quot;Bocas del Toro&quot;],[&quot;Chiriquí&quot;,&quot;Chiriquí&quot;],[&quot;Coclé&quot;,&quot;Coclé&quot;],[&quot;Colón&quot;,&quot;Colón&quot;],[&quot;Darién&quot;,&quot;Darién&quot;],[&quot;Emberá&quot;,&quot;Emberá&quot;],[&quot;Herrera&quot;,&quot;Herrera&quot;],[&quot;Kuna Yala&quot;,&quot;Guna Yala&quot;],[&quot;Los Santos&quot;,&quot;Los Santos&quot;],[&quot;Ngöbe-Buglé&quot;,&quot;Ngöbe-Buglé&quot;],[&quot;Panamá&quot;,&quot;Panamá&quot;],[&quot;Panamá Oeste&quot;,&quot;West Panamá&quot;],[&quot;Veraguas&quot;,&quot;Veraguas&quot;]]">Panama</option>
<option value="Papua New Guinea" data-provinces="[]">Papua New Guinea</option>
<option value="Paraguay" data-provinces="[]">Paraguay</option>
<option value="Peru" data-provinces="[[&quot;Amazonas&quot;,&quot;Amazonas&quot;],[&quot;Apurímac&quot;,&quot;Apurímac&quot;],[&quot;Arequipa&quot;,&quot;Arequipa&quot;],[&quot;Ayacucho&quot;,&quot;Ayacucho&quot;],[&quot;Cajamarca&quot;,&quot;Cajamarca&quot;],[&quot;Callao&quot;,&quot;El Callao&quot;],[&quot;Cuzco&quot;,&quot;Cusco&quot;],[&quot;Huancavelica&quot;,&quot;Huancavelica&quot;],[&quot;Huánuco&quot;,&quot;Huánuco&quot;],[&quot;Ica&quot;,&quot;Ica&quot;],[&quot;Junín&quot;,&quot;Junín&quot;],[&quot;La Libertad&quot;,&quot;La Libertad&quot;],[&quot;Lambayeque&quot;,&quot;Lambayeque&quot;],[&quot;Lima (departamento)&quot;,&quot;Lima (Department)&quot;],[&quot;Lima (provincia)&quot;,&quot;Lima (Metropolitan)&quot;],[&quot;Loreto&quot;,&quot;Loreto&quot;],[&quot;Madre de Dios&quot;,&quot;Madre de Dios&quot;],[&quot;Moquegua&quot;,&quot;Moquegua&quot;],[&quot;Pasco&quot;,&quot;Pasco&quot;],[&quot;Piura&quot;,&quot;Piura&quot;],[&quot;Puno&quot;,&quot;Puno&quot;],[&quot;San Martín&quot;,&quot;San Martín&quot;],[&quot;Tacna&quot;,&quot;Tacna&quot;],[&quot;Tumbes&quot;,&quot;Tumbes&quot;],[&quot;Ucayali&quot;,&quot;Ucayali&quot;],[&quot;Áncash&quot;,&quot;Ancash&quot;]]">Peru</option>
<option value="Philippines" data-provinces="[[&quot;Abra&quot;,&quot;Abra&quot;],[&quot;Agusan del Norte&quot;,&quot;Agusan del Norte&quot;],[&quot;Agusan del Sur&quot;,&quot;Agusan del Sur&quot;],[&quot;Aklan&quot;,&quot;Aklan&quot;],[&quot;Albay&quot;,&quot;Albay&quot;],[&quot;Antique&quot;,&quot;Antique&quot;],[&quot;Apayao&quot;,&quot;Apayao&quot;],[&quot;Aurora&quot;,&quot;Aurora&quot;],[&quot;Basilan&quot;,&quot;Basilan&quot;],[&quot;Bataan&quot;,&quot;Bataan&quot;],[&quot;Batanes&quot;,&quot;Batanes&quot;],[&quot;Batangas&quot;,&quot;Batangas&quot;],[&quot;Benguet&quot;,&quot;Benguet&quot;],[&quot;Biliran&quot;,&quot;Biliran&quot;],[&quot;Bohol&quot;,&quot;Bohol&quot;],[&quot;Bukidnon&quot;,&quot;Bukidnon&quot;],[&quot;Bulacan&quot;,&quot;Bulacan&quot;],[&quot;Cagayan&quot;,&quot;Cagayan&quot;],[&quot;Camarines Norte&quot;,&quot;Camarines Norte&quot;],[&quot;Camarines Sur&quot;,&quot;Camarines Sur&quot;],[&quot;Camiguin&quot;,&quot;Camiguin&quot;],[&quot;Capiz&quot;,&quot;Capiz&quot;],[&quot;Catanduanes&quot;,&quot;Catanduanes&quot;],[&quot;Cavite&quot;,&quot;Cavite&quot;],[&quot;Cebu&quot;,&quot;Cebu&quot;],[&quot;Cotabato&quot;,&quot;Cotabato&quot;],[&quot;Davao Occidental&quot;,&quot;Davao Occidental&quot;],[&quot;Davao Oriental&quot;,&quot;Davao Oriental&quot;],[&quot;Davao de Oro&quot;,&quot;Compostela Valley&quot;],[&quot;Davao del Norte&quot;,&quot;Davao del Norte&quot;],[&quot;Davao del Sur&quot;,&quot;Davao del Sur&quot;],[&quot;Dinagat Islands&quot;,&quot;Dinagat Islands&quot;],[&quot;Eastern Samar&quot;,&quot;Eastern Samar&quot;],[&quot;Guimaras&quot;,&quot;Guimaras&quot;],[&quot;Ifugao&quot;,&quot;Ifugao&quot;],[&quot;Ilocos Norte&quot;,&quot;Ilocos Norte&quot;],[&quot;Ilocos Sur&quot;,&quot;Ilocos Sur&quot;],[&quot;Iloilo&quot;,&quot;Iloilo&quot;],[&quot;Isabela&quot;,&quot;Isabela&quot;],[&quot;Kalinga&quot;,&quot;Kalinga&quot;],[&quot;La Union&quot;,&quot;La Union&quot;],[&quot;Laguna&quot;,&quot;Laguna&quot;],[&quot;Lanao del Norte&quot;,&quot;Lanao del Norte&quot;],[&quot;Lanao del Sur&quot;,&quot;Lanao del Sur&quot;],[&quot;Leyte&quot;,&quot;Leyte&quot;],[&quot;Maguindanao&quot;,&quot;Maguindanao&quot;],[&quot;Marinduque&quot;,&quot;Marinduque&quot;],[&quot;Masbate&quot;,&quot;Masbate&quot;],[&quot;Metro Manila&quot;,&quot;Metro Manila&quot;],[&quot;Misamis Occidental&quot;,&quot;Misamis Occidental&quot;],[&quot;Misamis Oriental&quot;,&quot;Misamis Oriental&quot;],[&quot;Mountain Province&quot;,&quot;Mountain&quot;],[&quot;Negros Occidental&quot;,&quot;Negros Occidental&quot;],[&quot;Negros Oriental&quot;,&quot;Negros Oriental&quot;],[&quot;Northern Samar&quot;,&quot;Northern Samar&quot;],[&quot;Nueva Ecija&quot;,&quot;Nueva Ecija&quot;],[&quot;Nueva Vizcaya&quot;,&quot;Nueva Vizcaya&quot;],[&quot;Occidental Mindoro&quot;,&quot;Occidental Mindoro&quot;],[&quot;Oriental Mindoro&quot;,&quot;Oriental Mindoro&quot;],[&quot;Palawan&quot;,&quot;Palawan&quot;],[&quot;Pampanga&quot;,&quot;Pampanga&quot;],[&quot;Pangasinan&quot;,&quot;Pangasinan&quot;],[&quot;Quezon&quot;,&quot;Quezon&quot;],[&quot;Quirino&quot;,&quot;Quirino&quot;],[&quot;Rizal&quot;,&quot;Rizal&quot;],[&quot;Romblon&quot;,&quot;Romblon&quot;],[&quot;Samar&quot;,&quot;Samar&quot;],[&quot;Sarangani&quot;,&quot;Sarangani&quot;],[&quot;Siquijor&quot;,&quot;Siquijor&quot;],[&quot;Sorsogon&quot;,&quot;Sorsogon&quot;],[&quot;South Cotabato&quot;,&quot;South Cotabato&quot;],[&quot;Southern Leyte&quot;,&quot;Southern Leyte&quot;],[&quot;Sultan Kudarat&quot;,&quot;Sultan Kudarat&quot;],[&quot;Sulu&quot;,&quot;Sulu&quot;],[&quot;Surigao del Norte&quot;,&quot;Surigao del Norte&quot;],[&quot;Surigao del Sur&quot;,&quot;Surigao del Sur&quot;],[&quot;Tarlac&quot;,&quot;Tarlac&quot;],[&quot;Tawi-Tawi&quot;,&quot;Tawi-Tawi&quot;],[&quot;Zambales&quot;,&quot;Zambales&quot;],[&quot;Zamboanga Sibugay&quot;,&quot;Zamboanga Sibugay&quot;],[&quot;Zamboanga del Norte&quot;,&quot;Zamboanga del Norte&quot;],[&quot;Zamboanga del Sur&quot;,&quot;Zamboanga del Sur&quot;]]">Philippines</option>
<option value="Pitcairn" data-provinces="[]">Pitcairn Islands</option>
<option value="Poland" data-provinces="[]">Poland</option>
<option value="Portugal" data-provinces="[[&quot;Aveiro&quot;,&quot;Aveiro&quot;],[&quot;Açores&quot;,&quot;Azores&quot;],[&quot;Beja&quot;,&quot;Beja&quot;],[&quot;Braga&quot;,&quot;Braga&quot;],[&quot;Bragança&quot;,&quot;Bragança&quot;],[&quot;Castelo Branco&quot;,&quot;Castelo Branco&quot;],[&quot;Coimbra&quot;,&quot;Coimbra&quot;],[&quot;Faro&quot;,&quot;Faro&quot;],[&quot;Guarda&quot;,&quot;Guarda&quot;],[&quot;Leiria&quot;,&quot;Leiria&quot;],[&quot;Lisboa&quot;,&quot;Lisbon&quot;],[&quot;Madeira&quot;,&quot;Madeira&quot;],[&quot;Portalegre&quot;,&quot;Portalegre&quot;],[&quot;Porto&quot;,&quot;Porto&quot;],[&quot;Santarém&quot;,&quot;Santarém&quot;],[&quot;Setúbal&quot;,&quot;Setúbal&quot;],[&quot;Viana do Castelo&quot;,&quot;Viana do Castelo&quot;],[&quot;Vila Real&quot;,&quot;Vila Real&quot;],[&quot;Viseu&quot;,&quot;Viseu&quot;],[&quot;Évora&quot;,&quot;Évora&quot;]]">Portugal</option>
<option value="Qatar" data-provinces="[]">Qatar</option>
<option value="Reunion" data-provinces="[]">Réunion</option>
<option value="Romania" data-provinces="[[&quot;Alba&quot;,&quot;Alba&quot;],[&quot;Arad&quot;,&quot;Arad&quot;],[&quot;Argeș&quot;,&quot;Argeș&quot;],[&quot;Bacău&quot;,&quot;Bacău&quot;],[&quot;Bihor&quot;,&quot;Bihor&quot;],[&quot;Bistrița-Năsăud&quot;,&quot;Bistriţa-Năsăud&quot;],[&quot;Botoșani&quot;,&quot;Botoşani&quot;],[&quot;Brașov&quot;,&quot;Braşov&quot;],[&quot;Brăila&quot;,&quot;Brăila&quot;],[&quot;București&quot;,&quot;Bucharest&quot;],[&quot;Buzău&quot;,&quot;Buzău&quot;],[&quot;Caraș-Severin&quot;,&quot;Caraș-Severin&quot;],[&quot;Cluj&quot;,&quot;Cluj&quot;],[&quot;Constanța&quot;,&quot;Constanța&quot;],[&quot;Covasna&quot;,&quot;Covasna&quot;],[&quot;Călărași&quot;,&quot;Călărași&quot;],[&quot;Dolj&quot;,&quot;Dolj&quot;],[&quot;Dâmbovița&quot;,&quot;Dâmbovița&quot;],[&quot;Galați&quot;,&quot;Galați&quot;],[&quot;Giurgiu&quot;,&quot;Giurgiu&quot;],[&quot;Gorj&quot;,&quot;Gorj&quot;],[&quot;Harghita&quot;,&quot;Harghita&quot;],[&quot;Hunedoara&quot;,&quot;Hunedoara&quot;],[&quot;Ialomița&quot;,&quot;Ialomița&quot;],[&quot;Iași&quot;,&quot;Iași&quot;],[&quot;Ilfov&quot;,&quot;Ilfov&quot;],[&quot;Maramureș&quot;,&quot;Maramureş&quot;],[&quot;Mehedinți&quot;,&quot;Mehedinți&quot;],[&quot;Mureș&quot;,&quot;Mureş&quot;],[&quot;Neamț&quot;,&quot;Neamţ&quot;],[&quot;Olt&quot;,&quot;Olt&quot;],[&quot;Prahova&quot;,&quot;Prahova&quot;],[&quot;Satu Mare&quot;,&quot;Satu Mare&quot;],[&quot;Sibiu&quot;,&quot;Sibiu&quot;],[&quot;Suceava&quot;,&quot;Suceava&quot;],[&quot;Sălaj&quot;,&quot;Sălaj&quot;],[&quot;Teleorman&quot;,&quot;Teleorman&quot;],[&quot;Timiș&quot;,&quot;Timiș&quot;],[&quot;Tulcea&quot;,&quot;Tulcea&quot;],[&quot;Vaslui&quot;,&quot;Vaslui&quot;],[&quot;Vrancea&quot;,&quot;Vrancea&quot;],[&quot;Vâlcea&quot;,&quot;Vâlcea&quot;]]">Romania</option>
<option value="Russia" data-provinces="[[&quot;Altai Krai&quot;,&quot;Altai Krai&quot;],[&quot;Altai Republic&quot;,&quot;Altai&quot;],[&quot;Amur Oblast&quot;,&quot;Amur&quot;],[&quot;Arkhangelsk Oblast&quot;,&quot;Arkhangelsk&quot;],[&quot;Astrakhan Oblast&quot;,&quot;Astrakhan&quot;],[&quot;Belgorod Oblast&quot;,&quot;Belgorod&quot;],[&quot;Bryansk Oblast&quot;,&quot;Bryansk&quot;],[&quot;Chechen Republic&quot;,&quot;Chechen&quot;],[&quot;Chelyabinsk Oblast&quot;,&quot;Chelyabinsk&quot;],[&quot;Chukotka Autonomous Okrug&quot;,&quot;Chukotka Okrug&quot;],[&quot;Chuvash Republic&quot;,&quot;Chuvash&quot;],[&quot;Irkutsk Oblast&quot;,&quot;Irkutsk&quot;],[&quot;Ivanovo Oblast&quot;,&quot;Ivanovo&quot;],[&quot;Jewish Autonomous Oblast&quot;,&quot;Jewish&quot;],[&quot;Kabardino-Balkarian Republic&quot;,&quot;Kabardino-Balkar&quot;],[&quot;Kaliningrad Oblast&quot;,&quot;Kaliningrad&quot;],[&quot;Kaluga Oblast&quot;,&quot;Kaluga&quot;],[&quot;Kamchatka Krai&quot;,&quot;Kamchatka Krai&quot;],[&quot;Karachay–Cherkess Republic&quot;,&quot;Karachay-Cherkess&quot;],[&quot;Kemerovo Oblast&quot;,&quot;Kemerovo&quot;],[&quot;Khabarovsk Krai&quot;,&quot;Khabarovsk Krai&quot;],[&quot;Khanty-Mansi Autonomous Okrug&quot;,&quot;Khanty-Mansi&quot;],[&quot;Kirov Oblast&quot;,&quot;Kirov&quot;],[&quot;Komi Republic&quot;,&quot;Komi&quot;],[&quot;Kostroma Oblast&quot;,&quot;Kostroma&quot;],[&quot;Krasnodar Krai&quot;,&quot;Krasnodar Krai&quot;],[&quot;Krasnoyarsk Krai&quot;,&quot;Krasnoyarsk Krai&quot;],[&quot;Kurgan Oblast&quot;,&quot;Kurgan&quot;],[&quot;Kursk Oblast&quot;,&quot;Kursk&quot;],[&quot;Leningrad Oblast&quot;,&quot;Leningrad&quot;],[&quot;Lipetsk Oblast&quot;,&quot;Lipetsk&quot;],[&quot;Magadan Oblast&quot;,&quot;Magadan&quot;],[&quot;Mari El Republic&quot;,&quot;Mari El&quot;],[&quot;Moscow&quot;,&quot;Moscow&quot;],[&quot;Moscow Oblast&quot;,&quot;Moscow Province&quot;],[&quot;Murmansk Oblast&quot;,&quot;Murmansk&quot;],[&quot;Nizhny Novgorod Oblast&quot;,&quot;Nizhny Novgorod&quot;],[&quot;Novgorod Oblast&quot;,&quot;Novgorod&quot;],[&quot;Novosibirsk Oblast&quot;,&quot;Novosibirsk&quot;],[&quot;Omsk Oblast&quot;,&quot;Omsk&quot;],[&quot;Orenburg Oblast&quot;,&quot;Orenburg&quot;],[&quot;Oryol Oblast&quot;,&quot;Oryol&quot;],[&quot;Penza Oblast&quot;,&quot;Penza&quot;],[&quot;Perm Krai&quot;,&quot;Perm Krai&quot;],[&quot;Primorsky Krai&quot;,&quot;Primorsky Krai&quot;],[&quot;Pskov Oblast&quot;,&quot;Pskov&quot;],[&quot;Republic of Adygeya&quot;,&quot;Adygea&quot;],[&quot;Republic of Bashkortostan&quot;,&quot;Bashkortostan&quot;],[&quot;Republic of Buryatia&quot;,&quot;Buryat&quot;],[&quot;Republic of Dagestan&quot;,&quot;Dagestan&quot;],[&quot;Republic of Ingushetia&quot;,&quot;Ingushetia&quot;],[&quot;Republic of Kalmykia&quot;,&quot;Kalmykia&quot;],[&quot;Republic of Karelia&quot;,&quot;Karelia&quot;],[&quot;Republic of Khakassia&quot;,&quot;Khakassia&quot;],[&quot;Republic of Mordovia&quot;,&quot;Mordovia&quot;],[&quot;Republic of North Ossetia–Alania&quot;,&quot;North Ossetia-Alania&quot;],[&quot;Republic of Tatarstan&quot;,&quot;Tatarstan&quot;],[&quot;Rostov Oblast&quot;,&quot;Rostov&quot;],[&quot;Ryazan Oblast&quot;,&quot;Ryazan&quot;],[&quot;Saint Petersburg&quot;,&quot;Saint Petersburg&quot;],[&quot;Sakha Republic (Yakutia)&quot;,&quot;Sakha&quot;],[&quot;Sakhalin Oblast&quot;,&quot;Sakhalin&quot;],[&quot;Samara Oblast&quot;,&quot;Samara&quot;],[&quot;Saratov Oblast&quot;,&quot;Saratov&quot;],[&quot;Smolensk Oblast&quot;,&quot;Smolensk&quot;],[&quot;Stavropol Krai&quot;,&quot;Stavropol Krai&quot;],[&quot;Sverdlovsk Oblast&quot;,&quot;Sverdlovsk&quot;],[&quot;Tambov Oblast&quot;,&quot;Tambov&quot;],[&quot;Tomsk Oblast&quot;,&quot;Tomsk&quot;],[&quot;Tula Oblast&quot;,&quot;Tula&quot;],[&quot;Tver Oblast&quot;,&quot;Tver&quot;],[&quot;Tyumen Oblast&quot;,&quot;Tyumen&quot;],[&quot;Tyva Republic&quot;,&quot;Tuva&quot;],[&quot;Udmurtia&quot;,&quot;Udmurt&quot;],[&quot;Ulyanovsk Oblast&quot;,&quot;Ulyanovsk&quot;],[&quot;Vladimir Oblast&quot;,&quot;Vladimir&quot;],[&quot;Volgograd Oblast&quot;,&quot;Volgograd&quot;],[&quot;Vologda Oblast&quot;,&quot;Vologda&quot;],[&quot;Voronezh Oblast&quot;,&quot;Voronezh&quot;],[&quot;Yamalo-Nenets Autonomous Okrug&quot;,&quot;Yamalo-Nenets Okrug&quot;],[&quot;Yaroslavl Oblast&quot;,&quot;Yaroslavl&quot;],[&quot;Zabaykalsky Krai&quot;,&quot;Zabaykalsky Krai&quot;]]">Russia</option>
<option value="Rwanda" data-provinces="[]">Rwanda</option>
<option value="Samoa" data-provinces="[]">Samoa</option>
<option value="San Marino" data-provinces="[]">San Marino</option>
<option value="Sao Tome And Principe" data-provinces="[]">São Tomé & Príncipe</option>
<option value="Saudi Arabia" data-provinces="[]">Saudi Arabia</option>
<option value="Senegal" data-provinces="[]">Senegal</option>
<option value="Serbia" data-provinces="[]">Serbia</option>
<option value="Seychelles" data-provinces="[]">Seychelles</option>
<option value="Sierra Leone" data-provinces="[]">Sierra Leone</option>
<option value="Singapore" data-provinces="[]">Singapore</option>
<option value="Sint Maarten" data-provinces="[]">Sint Maarten</option>
<option value="Slovakia" data-provinces="[]">Slovakia</option>
<option value="Slovenia" data-provinces="[]">Slovenia</option>
<option value="Solomon Islands" data-provinces="[]">Solomon Islands</option>
<option value="Somalia" data-provinces="[]">Somalia</option>
<option value="South Africa" data-provinces="[[&quot;Eastern Cape&quot;,&quot;Eastern Cape&quot;],[&quot;Free State&quot;,&quot;Free State&quot;],[&quot;Gauteng&quot;,&quot;Gauteng&quot;],[&quot;KwaZulu-Natal&quot;,&quot;KwaZulu-Natal&quot;],[&quot;Limpopo&quot;,&quot;Limpopo&quot;],[&quot;Mpumalanga&quot;,&quot;Mpumalanga&quot;],[&quot;North West&quot;,&quot;North West&quot;],[&quot;Northern Cape&quot;,&quot;Northern Cape&quot;],[&quot;Western Cape&quot;,&quot;Western Cape&quot;]]">South Africa</option>
<option value="South Georgia And The South Sandwich Islands" data-provinces="[]">South Georgia & South Sandwich Islands</option>
<option value="South Korea" data-provinces="[[&quot;Busan&quot;,&quot;Busan&quot;],[&quot;Chungbuk&quot;,&quot;North Chungcheong&quot;],[&quot;Chungnam&quot;,&quot;South Chungcheong&quot;],[&quot;Daegu&quot;,&quot;Daegu&quot;],[&quot;Daejeon&quot;,&quot;Daejeon&quot;],[&quot;Gangwon&quot;,&quot;Gangwon&quot;],[&quot;Gwangju&quot;,&quot;Gwangju City&quot;],[&quot;Gyeongbuk&quot;,&quot;North Gyeongsang&quot;],[&quot;Gyeonggi&quot;,&quot;Gyeonggi&quot;],[&quot;Gyeongnam&quot;,&quot;South Gyeongsang&quot;],[&quot;Incheon&quot;,&quot;Incheon&quot;],[&quot;Jeju&quot;,&quot;Jeju&quot;],[&quot;Jeonbuk&quot;,&quot;North Jeolla&quot;],[&quot;Jeonnam&quot;,&quot;South Jeolla&quot;],[&quot;Sejong&quot;,&quot;Sejong&quot;],[&quot;Seoul&quot;,&quot;Seoul&quot;],[&quot;Ulsan&quot;,&quot;Ulsan&quot;]]">South Korea</option>
<option value="South Sudan" data-provinces="[]">South Sudan</option>
<option value="Spain" data-provinces="[[&quot;A Coruña&quot;,&quot;A Coruña&quot;],[&quot;Albacete&quot;,&quot;Albacete&quot;],[&quot;Alicante&quot;,&quot;Alicante&quot;],[&quot;Almería&quot;,&quot;Almería&quot;],[&quot;Asturias&quot;,&quot;Asturias Province&quot;],[&quot;Badajoz&quot;,&quot;Badajoz&quot;],[&quot;Balears&quot;,&quot;Balears Province&quot;],[&quot;Barcelona&quot;,&quot;Barcelona&quot;],[&quot;Burgos&quot;,&quot;Burgos&quot;],[&quot;Cantabria&quot;,&quot;Cantabria Province&quot;],[&quot;Castellón&quot;,&quot;Castellón&quot;],[&quot;Ceuta&quot;,&quot;Ceuta&quot;],[&quot;Ciudad Real&quot;,&quot;Ciudad Real&quot;],[&quot;Cuenca&quot;,&quot;Cuenca&quot;],[&quot;Cáceres&quot;,&quot;Cáceres&quot;],[&quot;Cádiz&quot;,&quot;Cádiz&quot;],[&quot;Córdoba&quot;,&quot;Córdoba&quot;],[&quot;Girona&quot;,&quot;Girona&quot;],[&quot;Granada&quot;,&quot;Granada&quot;],[&quot;Guadalajara&quot;,&quot;Guadalajara&quot;],[&quot;Guipúzcoa&quot;,&quot;Gipuzkoa&quot;],[&quot;Huelva&quot;,&quot;Huelva&quot;],[&quot;Huesca&quot;,&quot;Huesca&quot;],[&quot;Jaén&quot;,&quot;Jaén&quot;],[&quot;La Rioja&quot;,&quot;La Rioja Province&quot;],[&quot;Las Palmas&quot;,&quot;Las Palmas&quot;],[&quot;León&quot;,&quot;León&quot;],[&quot;Lleida&quot;,&quot;Lleida&quot;],[&quot;Lugo&quot;,&quot;Lugo&quot;],[&quot;Madrid&quot;,&quot;Madrid Province&quot;],[&quot;Melilla&quot;,&quot;Melilla&quot;],[&quot;Murcia&quot;,&quot;Murcia&quot;],[&quot;Málaga&quot;,&quot;Málaga&quot;],[&quot;Navarra&quot;,&quot;Navarra&quot;],[&quot;Ourense&quot;,&quot;Ourense&quot;],[&quot;Palencia&quot;,&quot;Palencia&quot;],[&quot;Pontevedra&quot;,&quot;Pontevedra&quot;],[&quot;Salamanca&quot;,&quot;Salamanca&quot;],[&quot;Santa Cruz de Tenerife&quot;,&quot;Santa Cruz de Tenerife&quot;],[&quot;Segovia&quot;,&quot;Segovia&quot;],[&quot;Sevilla&quot;,&quot;Seville&quot;],[&quot;Soria&quot;,&quot;Soria&quot;],[&quot;Tarragona&quot;,&quot;Tarragona&quot;],[&quot;Teruel&quot;,&quot;Teruel&quot;],[&quot;Toledo&quot;,&quot;Toledo&quot;],[&quot;Valencia&quot;,&quot;Valencia&quot;],[&quot;Valladolid&quot;,&quot;Valladolid&quot;],[&quot;Vizcaya&quot;,&quot;Biscay&quot;],[&quot;Zamora&quot;,&quot;Zamora&quot;],[&quot;Zaragoza&quot;,&quot;Zaragoza&quot;],[&quot;Álava&quot;,&quot;Álava&quot;],[&quot;Ávila&quot;,&quot;Ávila&quot;]]">Spain</option>
<option value="Sri Lanka" data-provinces="[]">Sri Lanka</option>
<option value="Saint Barthélemy" data-provinces="[]">St. Barthélemy</option>
<option value="Saint Helena" data-provinces="[]">St. Helena</option>
<option value="Saint Kitts And Nevis" data-provinces="[]">St. Kitts & Nevis</option>
<option value="Saint Lucia" data-provinces="[]">St. Lucia</option>
<option value="Saint Martin" data-provinces="[]">St. Martin</option>
<option value="Saint Pierre And Miquelon" data-provinces="[]">St. Pierre & Miquelon</option>
<option value="St. Vincent" data-provinces="[]">St. Vincent & Grenadines</option>
<option value="Sudan" data-provinces="[]">Sudan</option>
<option value="Suriname" data-provinces="[]">Suriname</option>
<option value="Svalbard And Jan Mayen" data-provinces="[]">Svalbard & Jan Mayen</option>
<option value="Sweden" data-provinces="[]">Sweden</option>
<option value="Switzerland" data-provinces="[]">Switzerland</option>
<option value="Taiwan" data-provinces="[]">Taiwan</option>
<option value="Tajikistan" data-provinces="[]">Tajikistan</option>
<option value="Tanzania, United Republic Of" data-provinces="[]">Tanzania</option>
<option value="Thailand" data-provinces="[[&quot;Amnat Charoen&quot;,&quot;Amnat Charoen&quot;],[&quot;Ang Thong&quot;,&quot;Ang Thong&quot;],[&quot;Bangkok&quot;,&quot;Bangkok&quot;],[&quot;Bueng Kan&quot;,&quot;Bueng Kan&quot;],[&quot;Buriram&quot;,&quot;Buri Ram&quot;],[&quot;Chachoengsao&quot;,&quot;Chachoengsao&quot;],[&quot;Chai Nat&quot;,&quot;Chai Nat&quot;],[&quot;Chaiyaphum&quot;,&quot;Chaiyaphum&quot;],[&quot;Chanthaburi&quot;,&quot;Chanthaburi&quot;],[&quot;Chiang Mai&quot;,&quot;Chiang Mai&quot;],[&quot;Chiang Rai&quot;,&quot;Chiang Rai&quot;],[&quot;Chon Buri&quot;,&quot;Chon Buri&quot;],[&quot;Chumphon&quot;,&quot;Chumphon&quot;],[&quot;Kalasin&quot;,&quot;Kalasin&quot;],[&quot;Kamphaeng Phet&quot;,&quot;Kamphaeng Phet&quot;],[&quot;Kanchanaburi&quot;,&quot;Kanchanaburi&quot;],[&quot;Khon Kaen&quot;,&quot;Khon Kaen&quot;],[&quot;Krabi&quot;,&quot;Krabi&quot;],[&quot;Lampang&quot;,&quot;Lampang&quot;],[&quot;Lamphun&quot;,&quot;Lamphun&quot;],[&quot;Loei&quot;,&quot;Loei&quot;],[&quot;Lopburi&quot;,&quot;Lopburi&quot;],[&quot;Mae Hong Son&quot;,&quot;Mae Hong Son&quot;],[&quot;Maha Sarakham&quot;,&quot;Maha Sarakham&quot;],[&quot;Mukdahan&quot;,&quot;Mukdahan&quot;],[&quot;Nakhon Nayok&quot;,&quot;Nakhon Nayok&quot;],[&quot;Nakhon Pathom&quot;,&quot;Nakhon Pathom&quot;],[&quot;Nakhon Phanom&quot;,&quot;Nakhon Phanom&quot;],[&quot;Nakhon Ratchasima&quot;,&quot;Nakhon Ratchasima&quot;],[&quot;Nakhon Sawan&quot;,&quot;Nakhon Sawan&quot;],[&quot;Nakhon Si Thammarat&quot;,&quot;Nakhon Si Thammarat&quot;],[&quot;Nan&quot;,&quot;Nan&quot;],[&quot;Narathiwat&quot;,&quot;Narathiwat&quot;],[&quot;Nong Bua Lam Phu&quot;,&quot;Nong Bua Lam Phu&quot;],[&quot;Nong Khai&quot;,&quot;Nong Khai&quot;],[&quot;Nonthaburi&quot;,&quot;Nonthaburi&quot;],[&quot;Pathum Thani&quot;,&quot;Pathum Thani&quot;],[&quot;Pattani&quot;,&quot;Pattani&quot;],[&quot;Pattaya&quot;,&quot;Pattaya&quot;],[&quot;Phangnga&quot;,&quot;Phang Nga&quot;],[&quot;Phatthalung&quot;,&quot;Phatthalung&quot;],[&quot;Phayao&quot;,&quot;Phayao&quot;],[&quot;Phetchabun&quot;,&quot;Phetchabun&quot;],[&quot;Phetchaburi&quot;,&quot;Phetchaburi&quot;],[&quot;Phichit&quot;,&quot;Phichit&quot;],[&quot;Phitsanulok&quot;,&quot;Phitsanulok&quot;],[&quot;Phra Nakhon Si Ayutthaya&quot;,&quot;Phra Nakhon Si Ayutthaya&quot;],[&quot;Phrae&quot;,&quot;Phrae&quot;],[&quot;Phuket&quot;,&quot;Phuket&quot;],[&quot;Prachin Buri&quot;,&quot;Prachin Buri&quot;],[&quot;Prachuap Khiri Khan&quot;,&quot;Prachuap Khiri Khan&quot;],[&quot;Ranong&quot;,&quot;Ranong&quot;],[&quot;Ratchaburi&quot;,&quot;Ratchaburi&quot;],[&quot;Rayong&quot;,&quot;Rayong&quot;],[&quot;Roi Et&quot;,&quot;Roi Et&quot;],[&quot;Sa Kaeo&quot;,&quot;Sa Kaeo&quot;],[&quot;Sakon Nakhon&quot;,&quot;Sakon Nakhon&quot;],[&quot;Samut Prakan&quot;,&quot;Samut Prakan&quot;],[&quot;Samut Sakhon&quot;,&quot;Samut Sakhon&quot;],[&quot;Samut Songkhram&quot;,&quot;Samut Songkhram&quot;],[&quot;Saraburi&quot;,&quot;Saraburi&quot;],[&quot;Satun&quot;,&quot;Satun&quot;],[&quot;Sing Buri&quot;,&quot;Sing Buri&quot;],[&quot;Sisaket&quot;,&quot;Si Sa Ket&quot;],[&quot;Songkhla&quot;,&quot;Songkhla&quot;],[&quot;Sukhothai&quot;,&quot;Sukhothai&quot;],[&quot;Suphan Buri&quot;,&quot;Suphanburi&quot;],[&quot;Surat Thani&quot;,&quot;Surat Thani&quot;],[&quot;Surin&quot;,&quot;Surin&quot;],[&quot;Tak&quot;,&quot;Tak&quot;],[&quot;Trang&quot;,&quot;Trang&quot;],[&quot;Trat&quot;,&quot;Trat&quot;],[&quot;Ubon Ratchathani&quot;,&quot;Ubon Ratchathani&quot;],[&quot;Udon Thani&quot;,&quot;Udon Thani&quot;],[&quot;Uthai Thani&quot;,&quot;Uthai Thani&quot;],[&quot;Uttaradit&quot;,&quot;Uttaradit&quot;],[&quot;Yala&quot;,&quot;Yala&quot;],[&quot;Yasothon&quot;,&quot;Yasothon&quot;]]">Thailand</option>
<option value="Timor Leste" data-provinces="[]">Timor-Leste</option>
<option value="Togo" data-provinces="[]">Togo</option>
<option value="Tokelau" data-provinces="[]">Tokelau</option>
<option value="Tonga" data-provinces="[]">Tonga</option>
<option value="Trinidad and Tobago" data-provinces="[]">Trinidad & Tobago</option>
<option value="Tristan da Cunha" data-provinces="[]">Tristan da Cunha</option>
<option value="Tunisia" data-provinces="[]">Tunisia</option>
<option value="Turkey" data-provinces="[]">Türkiye</option>
<option value="Turkmenistan" data-provinces="[]">Turkmenistan</option>
<option value="Turks and Caicos Islands" data-provinces="[]">Turks & Caicos Islands</option>
<option value="Tuvalu" data-provinces="[]">Tuvalu</option>
<option value="United States Minor Outlying Islands" data-provinces="[]">U.S. Outlying Islands</option>
<option value="Uganda" data-provinces="[]">Uganda</option>
<option value="Ukraine" data-provinces="[]">Ukraine</option>
<option value="United Arab Emirates" data-provinces="[[&quot;Abu Dhabi&quot;,&quot;Abu Dhabi&quot;],[&quot;Ajman&quot;,&quot;Ajman&quot;],[&quot;Dubai&quot;,&quot;Dubai&quot;],[&quot;Fujairah&quot;,&quot;Fujairah&quot;],[&quot;Ras al-Khaimah&quot;,&quot;Ras al-Khaimah&quot;],[&quot;Sharjah&quot;,&quot;Sharjah&quot;],[&quot;Umm al-Quwain&quot;,&quot;Umm al-Quwain&quot;]]">United Arab Emirates</option>
<option value="United Kingdom" data-provinces="[[&quot;British Forces&quot;,&quot;British Forces&quot;],[&quot;England&quot;,&quot;England&quot;],[&quot;Northern Ireland&quot;,&quot;Northern Ireland&quot;],[&quot;Scotland&quot;,&quot;Scotland&quot;],[&quot;Wales&quot;,&quot;Wales&quot;]]">United Kingdom</option>
<option value="United States" data-provinces="[[&quot;Alabama&quot;,&quot;Alabama&quot;],[&quot;Alaska&quot;,&quot;Alaska&quot;],[&quot;American Samoa&quot;,&quot;American Samoa&quot;],[&quot;Arizona&quot;,&quot;Arizona&quot;],[&quot;Arkansas&quot;,&quot;Arkansas&quot;],[&quot;Armed Forces Americas&quot;,&quot;Armed Forces Americas&quot;],[&quot;Armed Forces Europe&quot;,&quot;Armed Forces Europe&quot;],[&quot;Armed Forces Pacific&quot;,&quot;Armed Forces Pacific&quot;],[&quot;California&quot;,&quot;California&quot;],[&quot;Colorado&quot;,&quot;Colorado&quot;],[&quot;Connecticut&quot;,&quot;Connecticut&quot;],[&quot;Delaware&quot;,&quot;Delaware&quot;],[&quot;District of Columbia&quot;,&quot;Washington DC&quot;],[&quot;Federated States of Micronesia&quot;,&quot;Micronesia&quot;],[&quot;Florida&quot;,&quot;Florida&quot;],[&quot;Georgia&quot;,&quot;Georgia&quot;],[&quot;Guam&quot;,&quot;Guam&quot;],[&quot;Hawaii&quot;,&quot;Hawaii&quot;],[&quot;Idaho&quot;,&quot;Idaho&quot;],[&quot;Illinois&quot;,&quot;Illinois&quot;],[&quot;Indiana&quot;,&quot;Indiana&quot;],[&quot;Iowa&quot;,&quot;Iowa&quot;],[&quot;Kansas&quot;,&quot;Kansas&quot;],[&quot;Kentucky&quot;,&quot;Kentucky&quot;],[&quot;Louisiana&quot;,&quot;Louisiana&quot;],[&quot;Maine&quot;,&quot;Maine&quot;],[&quot;Marshall Islands&quot;,&quot;Marshall Islands&quot;],[&quot;Maryland&quot;,&quot;Maryland&quot;],[&quot;Massachusetts&quot;,&quot;Massachusetts&quot;],[&quot;Michigan&quot;,&quot;Michigan&quot;],[&quot;Minnesota&quot;,&quot;Minnesota&quot;],[&quot;Mississippi&quot;,&quot;Mississippi&quot;],[&quot;Missouri&quot;,&quot;Missouri&quot;],[&quot;Montana&quot;,&quot;Montana&quot;],[&quot;Nebraska&quot;,&quot;Nebraska&quot;],[&quot;Nevada&quot;,&quot;Nevada&quot;],[&quot;New Hampshire&quot;,&quot;New Hampshire&quot;],[&quot;New Jersey&quot;,&quot;New Jersey&quot;],[&quot;New Mexico&quot;,&quot;New Mexico&quot;],[&quot;New York&quot;,&quot;New York&quot;],[&quot;North Carolina&quot;,&quot;North Carolina&quot;],[&quot;North Dakota&quot;,&quot;North Dakota&quot;],[&quot;Northern Mariana Islands&quot;,&quot;Northern Mariana Islands&quot;],[&quot;Ohio&quot;,&quot;Ohio&quot;],[&quot;Oklahoma&quot;,&quot;Oklahoma&quot;],[&quot;Oregon&quot;,&quot;Oregon&quot;],[&quot;Palau&quot;,&quot;Palau&quot;],[&quot;Pennsylvania&quot;,&quot;Pennsylvania&quot;],[&quot;Puerto Rico&quot;,&quot;Puerto Rico&quot;],[&quot;Rhode Island&quot;,&quot;Rhode Island&quot;],[&quot;South Carolina&quot;,&quot;South Carolina&quot;],[&quot;South Dakota&quot;,&quot;South Dakota&quot;],[&quot;Tennessee&quot;,&quot;Tennessee&quot;],[&quot;Texas&quot;,&quot;Texas&quot;],[&quot;Utah&quot;,&quot;Utah&quot;],[&quot;Vermont&quot;,&quot;Vermont&quot;],[&quot;Virgin Islands&quot;,&quot;U.S. Virgin Islands&quot;],[&quot;Virginia&quot;,&quot;Virginia&quot;],[&quot;Washington&quot;,&quot;Washington&quot;],[&quot;West Virginia&quot;,&quot;West Virginia&quot;],[&quot;Wisconsin&quot;,&quot;Wisconsin&quot;],[&quot;Wyoming&quot;,&quot;Wyoming&quot;]]">United States</option>
<option value="Uruguay" data-provinces="[[&quot;Artigas&quot;,&quot;Artigas&quot;],[&quot;Canelones&quot;,&quot;Canelones&quot;],[&quot;Cerro Largo&quot;,&quot;Cerro Largo&quot;],[&quot;Colonia&quot;,&quot;Colonia&quot;],[&quot;Durazno&quot;,&quot;Durazno&quot;],[&quot;Flores&quot;,&quot;Flores&quot;],[&quot;Florida&quot;,&quot;Florida&quot;],[&quot;Lavalleja&quot;,&quot;Lavalleja&quot;],[&quot;Maldonado&quot;,&quot;Maldonado&quot;],[&quot;Montevideo&quot;,&quot;Montevideo&quot;],[&quot;Paysandú&quot;,&quot;Paysandú&quot;],[&quot;Rivera&quot;,&quot;Rivera&quot;],[&quot;Rocha&quot;,&quot;Rocha&quot;],[&quot;Río Negro&quot;,&quot;Río Negro&quot;],[&quot;Salto&quot;,&quot;Salto&quot;],[&quot;San José&quot;,&quot;San José&quot;],[&quot;Soriano&quot;,&quot;Soriano&quot;],[&quot;Tacuarembó&quot;,&quot;Tacuarembó&quot;],[&quot;Treinta y Tres&quot;,&quot;Treinta y Tres&quot;]]">Uruguay</option>
<option value="Uzbekistan" data-provinces="[]">Uzbekistan</option>
<option value="Vanuatu" data-provinces="[]">Vanuatu</option>
<option value="Holy See (Vatican City State)" data-provinces="[]">Vatican City</option>
<option value="Venezuela" data-provinces="[[&quot;Amazonas&quot;,&quot;Amazonas&quot;],[&quot;Anzoátegui&quot;,&quot;Anzoátegui&quot;],[&quot;Apure&quot;,&quot;Apure&quot;],[&quot;Aragua&quot;,&quot;Aragua&quot;],[&quot;Barinas&quot;,&quot;Barinas&quot;],[&quot;Bolívar&quot;,&quot;Bolívar&quot;],[&quot;Carabobo&quot;,&quot;Carabobo&quot;],[&quot;Cojedes&quot;,&quot;Cojedes&quot;],[&quot;Delta Amacuro&quot;,&quot;Delta Amacuro&quot;],[&quot;Dependencias Federales&quot;,&quot;Federal Dependencies&quot;],[&quot;Distrito Capital&quot;,&quot;Capital&quot;],[&quot;Falcón&quot;,&quot;Falcón&quot;],[&quot;Guárico&quot;,&quot;Guárico&quot;],[&quot;La Guaira&quot;,&quot;Vargas&quot;],[&quot;Lara&quot;,&quot;Lara&quot;],[&quot;Miranda&quot;,&quot;Miranda&quot;],[&quot;Monagas&quot;,&quot;Monagas&quot;],[&quot;Mérida&quot;,&quot;Mérida&quot;],[&quot;Nueva Esparta&quot;,&quot;Nueva Esparta&quot;],[&quot;Portuguesa&quot;,&quot;Portuguesa&quot;],[&quot;Sucre&quot;,&quot;Sucre&quot;],[&quot;Trujillo&quot;,&quot;Trujillo&quot;],[&quot;Táchira&quot;,&quot;Táchira&quot;],[&quot;Yaracuy&quot;,&quot;Yaracuy&quot;],[&quot;Zulia&quot;,&quot;Zulia&quot;]]">Venezuela</option>
<option value="Vietnam" data-provinces="[]">Vietnam</option>
<option value="Wallis And Futuna" data-provinces="[]">Wallis & Futuna</option>
<option value="Western Sahara" data-provinces="[]">Western Sahara</option>
<option value="Yemen" data-provinces="[]">Yemen</option>
<option value="Zambia" data-provinces="[]">Zambia</option>
<option value="Zimbabwe" data-provinces="[]">Zimbabwe</option>`;

(() => {
    const FORM_DATA_TIMEOUT = 10000;

    const devToolsEnabled = true;
    const latestEmbedVersion = "5.2.6";
    const isPreviewApp = "";

    const nativeFormContainsErrors = false;
    const $preInitStyles = document.querySelector('#cf-pre-init-styles');

    let mountedTextEntrypoints = false;

    // i.e. ?view=orig, or "email taken" following a form crash
    if (onFallbackTemplate() || nativeFormContainsErrors) {
      // Reveal the original form
      $preInitStyles.parentElement.removeChild($preInitStyles);
      return;
    }

    function start() {
      initializeForms();
      injectHiddenForms();

      // Try for the next 5s to mount any dynamically injected forms.
      const intervalId = setInterval(() => {
        initializeForms();
      }, 100);

      setTimeout(() => {
        clearInterval(intervalId);
      }, 5000);
    }

    // This fires when a CF form has mounted on the page.
    // More reliable than putting this in start(), since developers can manually call
    // CF.initializeForms().
    window.addEventListener('cf:ready', () => {
      injectHiddenForms();
    });

    if (['interactive', 'complete', 'loaded'].includes(document.readyState)) {
      start();
    } else {
      document.addEventListener('DOMContentLoaded', () => start());
    }

    window.CF.initializeForms = initializeForms;
    const forms = [{"id":"54tBNk","name":"Registration","target":"storefront","version":"4.16.5","updated_at":1752590204}];

    async function initializeForms() {
      // Semi-hack: Prevents older embed scripts from doing anything.
      // Any embed script before 4.12.0 checks only for the presence of this attribute,
      // not if it strictly equals "true".
      document.documentElement.setAttribute('data-cf-initialized', 'loading');

      // Only mount text entrypoints once. This is expensive and causes render blocking time on mobile.
      if (!mountedTextEntrypoints) {
        mountedTextEntrypoints = true;
        mountTextEntrypoints();
      }

      const reactTarget = `<!-- BEGIN app snippet: react-target-markup -->







































<div class="cf-react-target">
  <div class="cf-preload">
    
      <div class="cf-preload-label cf-preload-item"></div>
      <div class="cf-preload-field cf-preload-item"></div>
    
      <div class="cf-preload-label cf-preload-item"></div>
      <div class="cf-preload-field cf-preload-item"></div>
    
      <div class="cf-preload-label cf-preload-item"></div>
      <div class="cf-preload-field cf-preload-item"></div>
    
      <div class="cf-preload-label cf-preload-item"></div>
      <div class="cf-preload-field cf-preload-item"></div>
    
    
      <span class="cf-preload-button cf-preload-item"></span>
    
      <span class="cf-preload-button cf-preload-item"></span>
    
  </div>
</div><!-- END app snippet -->`;
      const $forms = Array.from(document.querySelectorAll('form:not([data-cf-state])'));
      const entrypoints = [];

      for (let $form of $forms) {
        if (isIgnored($form)) continue;

        const id = getFormId($form);
        if (!id) continue;

        const formData = forms.find(form => form.id === id);
        if (!formData) {
          console.error(`[Customer Fields] Unable to find form data with id ${id}`);
          setFormState($form, 'failed');
          continue;
        }

        // Do not try to mount the same form element more than once,
        // otherwise failures are much harder to handle.
        if (isDetected($form)) continue;
        markAsDetected($form);

        const $originalForm = $form.cloneNode(true);

        // Shopify's captcha script can bind to the form that CF mounted to.
        // Their submit handler eventually calls the submit method after generating
        // the captcha response token, causing native submission behavior to occur.
        // We do not want this, so we override it to a no-op. See #2092
        $form.submit = () => {};

        injectReactTarget($form);
        setFormState($form, 'loading');

        const entrypoint = {
          $form,
          registration: isRegistrationForm($form),
          formId: formData.id,
          updatedAt: formData.updated_at,
          target: formData.target,
          originalForm: $originalForm,
          version: formData.version,
          restore: () => restoreEntrypoint(entrypoint),
        };

        entrypoints.push(entrypoint);

        // Required to be backwards compatible with older versions of the JS Form API, and prevent Shopify captcha
        $form.setAttribute('data-cf-form', formData.id);
        $form.setAttribute('action', '');
      }

      if ($preInitStyles && $preInitStyles.parentElement) {
        $preInitStyles.parentElement.removeChild($preInitStyles);
      }

      if (!entrypoints.length) return;

      
      initializeEmbedScript();

      function initializeEmbedScript() {
        if (!window.CF.requestedEmbedJS) {
          const $script = document.createElement('script');
          $script.src = getAssetUrl('customer-fields.js');

          document.head.appendChild($script);
          window.CF.requestedEmbedJS = true;
        }

        if (!window.CF.requestedEmbedCSS) {
          const $link = document.createElement('link');
          $link.href = getAssetUrl('customer-fields.css');
          $link.rel = 'stylesheet';
          $link.type = 'text/css';

          document.head.appendChild($link);
          window.CF.requestedEmbedCSS = true;
        }
      }
      

      const uniqueEntrypoints = entrypoints.reduce((acc, entrypoint) => {
        if (acc.some(e => e.formId === entrypoint.formId)) return acc;
        acc.push(entrypoint);

        return acc;
      }, []);

      const fullForms = await Promise.all(uniqueEntrypoints.map(e => getFormData(e.formId, e.updatedAt)));

      fullForms.forEach((fullForm, index) => {
        // Could be a failed request.
        if (!fullForm) return;

        const invalidFormTargets = ['customer-account'];
        if (invalidFormTargets.includes(fullForm.form.target)) {
          console.error('[Customer Fields] Invalid form target', fullForm);
          return;
        }

        entrypoints
          .filter(e => e.formId === fullForm.form.id)
          .forEach(entrypoint => {
            entrypoint.form = {
              ...fullForm.form,
              currentRevision: fullForm.revision,
            };
          })
      });

      entrypoints.forEach(e => {
        if (!e.form) {
          // Form can be null if the request failed one way or another.
          restoreEntrypoint(e);
          return;
        }
      });

      if (window.CF.entrypoints) {
        window.CF.entrypoints.push(...entrypoints);

        if (window.CF.mountForm) {
          entrypoints.forEach(entrypoint => {
            if (!entrypoint.form) return;
            
            window.CF.mountForm(entrypoint.form);
          });
        }
      } else {
        window.CF.entrypoints = entrypoints;

        // The Core class has some logic that gets invoked as a result of this event
        // that we only want to fire once, so let's not emit this event multiple times.
        document.dispatchEvent(new CustomEvent('cf:entrypoints_ready'));
      }

      function getFormData(formId, updatedAt) {
        return new Promise(resolve => {
          const controller = new AbortController();
          const timeoutId = setTimeout(() => controller.abort(), FORM_DATA_TIMEOUT);
          const maxAttempts = 3;
          let attempts = 0;

          const attemptFetch = () => {
            if (controller.signal.aborted) {
              resolve(null);
              return;
            }

            attempts++;

            fetch(`https://app.customerfields.com/embed_api/v4/forms/${formId}.json?v=${updatedAt}`, {
              headers: {
                'X-Shopify-Shop-Domain': "h6pafc-61.myshopify.com"
              },
              signal: controller.signal
            }).then(response => {
              if (controller.signal.aborted) {
                resolve(null);
                return;
              }

              if (response.ok) {
                response.json().then(resolve);
                return;
              }

              if (attempts < maxAttempts) {
                pause(2000).then(() => attemptFetch());
                return;
              }

              console.error(`[Customer Fields] Received non-OK response from the back-end when fetching form ${formId}`)
              resolve(null);
            }).catch((err) => {
              if (controller.signal.aborted) {
                resolve(null);
                return;
              }

              if (attempts < maxAttempts) {
                pause(2000).then(() => attemptFetch());
                return;
              }

              console.error(`[Customer Fields] Encountered unknown error while fetching form ${formId}`, err);
              resolve(null);
            });
          };

          attemptFetch();
        });
      }

      function restoreEntrypoint(entrypoint) {
        // This has a side effect of removing the Form class' submit handlers.
        // Previously this only replaced the original children within the form, but the submit event
        // was still being handled by our script.
        entrypoint.$form.replaceWith(entrypoint.originalForm);

        // After a form has been restored, make sure we don't touch it again.
        // Otherwise we might treat it as an "async mounted" entrypoint and try to mount it again
        entrypoint.originalForm.setAttribute('data-cf-ignore', 'true');

        // Opacity was set to 0 with the #cf-pre-init-styles element
        entrypoint.$form.style.opacity = 1;

        console.error(`[Customer Fields] Encountered an issue while mounting form, reverting to original form contents.`, entrypoint);
      }

      function getAssetUrl(filename) {
        // We changed this to always get the latest embed assets
        // 4.15.7 included a crucial hotfix for recaptcha, see #2028

        return `https://static.customerfields.com/releases/${isPreviewApp ? '' : `${latestEmbedVersion}/`}${filename}`;
      }

      function injectReactTarget($form) {
        const containsReactTarget = !!$form.querySelector('.cf-react-target');
        if (containsReactTarget) return;

        $form.innerHTML = reactTarget;
      }

      function isIgnored($form) {
        return $form.getAttribute('data-cf-ignore') === 'true';
      }

      function isDetected($form) {
        return $form.__cfDetected === true;
      }

      function markAsDetected($form) {
        $form.__cfDetected = true;
      }

      function isEditAccountForm($form) {
        return $form.getAttribute('data-cf-edit-account') === 'true';
      }

      function isVintageRegistrationForm($form) {
        return (
          window.location.pathname.includes('/account/register')
            && $form.id === 'create_customer'
            && !!$form.getAttribute('data-cf-form')
        );
      }

      function isRegistrationForm($form) {
        try {        
          const isWithinAppBlock = !!$form.closest('.cf-form-block');
          if (isWithinAppBlock) return false;
          
          const action = $form.getAttribute('action');
          if (!action) return false;

          const formActionUrl = new URL(action, window.location.origin);
          const hasAccountPath = formActionUrl.pathname.endsWith('/account');
          const matchesShopDomain = formActionUrl.host === window.location.host;

          const hasPostMethod = $form.method.toLowerCase() === 'post';
          const $formTypeInput = $form.querySelector('[name="form_type"]')
          
          const hasCreateCustomerFormType = $formTypeInput && $formTypeInput.value === 'create_customer';
          return (matchesShopDomain && hasAccountPath && hasPostMethod) || hasCreateCustomerFormType
        } catch (err) {
          return false;
        }
      }

      function mountTextEntrypoints() {
        const tree = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT, (node) => {
          if (typeof node.data !== 'string' || !node.data) return NodeFilter.FILTER_REJECT;

          return node.data.includes('data-cf-form="') ? NodeFilter.FILTER_ACCEPT : NodeFilter.FILTER_REJECT;
        });

        /**
         * Walks through every text node on the document that contains 'data-cf-form="' and attempts to
         * splice a form element in place of every shortcode.
         *
         * @type Node[]
         */
        while (tree.nextNode()) {
          let node = tree.currentNode;
          const parser = new DOMParser();

          while (entrypointContent = node.data.match(/<form.*data-cf-form="[a-zA-Z0-9]+".*>.*<\/form>/)) {
            const [match] = entrypointContent;

            const doc = parser.parseFromString(match, 'text/html');
            const $form = doc.body.firstElementChild;

            // Substring is better than split here in case the text node contains multiple forms.
            const beforeText = node.data.substring(0, node.data.indexOf(match));
            const afterText = node.data.substring(node.data.indexOf(match) + match.length);

            node.replaceWith($form);
            node.data = node.data.replace(match, '');

            if (beforeText) $form.insertAdjacentText('beforebegin', beforeText);
            if (afterText) {
              $form.insertAdjacentText('afterend', afterText);

              // Continue scanning the rest of the node text in case there are more forms
              node = $form.nextSibling;
            }
          }
        }
      }

      function getFormId($form) {
        const currentFormId = $form.getAttribute('data-cf-form');

        let id;

        if (isEditAccountForm($form)) {
          id = "";
        } else if (isVintageRegistrationForm($form) || isRegistrationForm($form)) {
          id = "";
        }

        return id || currentFormId;
      }

      function setFormState($form, state) {
        $form.setAttribute('data-cf-state', state);
      }
    }

    function onFallbackTemplate() {
      const params = new URLSearchParams(window.location.search);

      return location.pathname.includes('/account/register') && params.get('view') === 'orig';
    }

    function injectHiddenForms() {
      if (!devToolsEnabled && !CF.entrypoints?.length) return;
      if (document.querySelector('#cf_hidden_forms')) return;

      const container = document.createElement('div');
      
      container.id = "cf_hidden_forms";
      container.style.display = 'none';
      container.setAttribute('aria-hidden', 'true');

      document.body.appendChild(container);

      const loginForm = createLoginForm();
      const recoverForm = createRecoverPasswordForm();

      container.appendChild(loginForm);
      container.appendChild(recoverForm);

      if (window.Shopify.captcha) {
        // Only applicable for grecaptcha shops, but also safe for hcaptcha
        triggerShopifyRecaptchaLoad(container);

        window.Shopify.captcha.protect(loginForm);
        window.Shopify.captcha.protect(recoverForm);
      }
    }

    function triggerShopifyRecaptchaLoad(container) {
      if (document.getElementById('cf-hidden-recaptcha-trigger__create_customer')) return;
      if (document.getElementById('cf-hidden-recaptcha-trigger__contact')) return;

      // Triggering a focus event on a form causes Shopify to load their recaptcha script.
      // This allows our Customer class to handle the copying/injecting of `grecaptcha` so we can
      // handle multiple `grecaptcha` instances. See methods `injectRecaptchaScript`
      // and `captureShopifyGrecaptcha` in `Customer.ts`.
      // Note: We have to try both types, in case the merchant has only one of the two recaptcha
      // options checked
      const $customerRecaptchaForm = createDummyRecaptchaForm('/account', 'create_customer');
      container.appendChild($customerRecaptchaForm);

      const $contactRecaptchaForm = createDummyRecaptchaForm('/contact', 'contact');
      container.appendChild($contactRecaptchaForm);

      triggerFocusEvent($customerRecaptchaForm);
      triggerFocusEvent($contactRecaptchaForm);
    }

    function createDummyRecaptchaForm(action, type) {
      const dummyRecaptchaForm = document.createElement('form');
      
      dummyRecaptchaForm.action = action;
      dummyRecaptchaForm.method = "post";
      dummyRecaptchaForm.id = `cf-hidden-recaptcha-trigger__${type}`;
      dummyRecaptchaForm.setAttribute('data-cf-ignore', 'true');
      dummyRecaptchaForm.setAttribute('aria-hidden', 'true');
      dummyRecaptchaForm.style.display = 'none';

      const formTypeInput = document.createElement('input');

      formTypeInput.name = "form_type"
      formTypeInput.setAttribute('value', type);

      dummyRecaptchaForm.appendChild(formTypeInput);

      return dummyRecaptchaForm;
    }

    function triggerFocusEvent(element) {
      const event = new Event('focusin', { bubbles: true, cancelable: false });
      element.dispatchEvent(event);
    }

    function createLoginForm() {
      const form = createDummyRecaptchaForm('/account/login', 'customer_login');
      const email = document.createElement('input');
      email.name = 'customer[email]';

      const password = document.createElement('input');
      password.name = 'customer[password]';

      const redirect = document.createElement('input');
      redirect.name = 'return_to';

      form.appendChild(email);
      form.appendChild(password);
      form.appendChild(redirect);
      form.setAttribute('aria-hidden', 'true');

      return form;
    }

    function createRecoverPasswordForm() {
      const parser = new DOMParser();
      const result = parser.parseFromString(`<form method="post" action="/account/recover" accept-charset="UTF-8"><input type="hidden" name="form_type" value="recover_customer_password" /><input type="hidden" name="utf8" value="✓" /><input name="email" value="" /><input name="return_to" value="" /></form>`, 'text/html');
      const form = result.querySelector('form');
      
      form.setAttribute('aria-hidden', 'true');
      form.id = "cf_recover_password_form";

      return form;
    }

    function pause(ms) {
      return new Promise(resolve => setTimeout(resolve, ms));
    }
  })();

document.addEventListener('DOMContentLoaded', async () => {
    let theme;

    if (window.Shopify) {
      theme = {
        name: window.Shopify.theme.schema_name,
        version: window.Shopify.theme.schema_version,
      }
    }

    if (theme) {
      document.documentElement.setAttribute('data-theme-name', theme.name);
      document.documentElement.setAttribute('data-theme-version', theme.version);
    }
  });

window.CF.language = window.CF.language || {};
  window.CF.language.editAccountHeading = "Edit account";
  window.CF.language.editAccountBackLinkText = "Back to account";

(function() {
    const callbacksHandled = [];

    function handleCallback(callback) {
      if (callbacksHandled.indexOf(callback) > -1) return;

      callback();
      callbacksHandled.push(callback);
    };

    function domIsReady() {
      return /complete|interactive|loaded/.test(document.readyState);
    };

    function customerExistsInWindow() {
      const customerPresent = ('customer' in window.CF);
      if (!customerPresent) return false;

      const hasCaptchaEnabled = document.body.getAttribute('data-cf-captcha-enabled') === 'true';
      if (hasCaptchaEnabled) {
        const captchaReady = document.body.getAttribute('data-cf-captcha-ready') === 'true';
        if (!captchaReady) return false;
      }

      return true;
    };

    function embedFormHasMounted() {
      return !!document.querySelector('.cf-form-inner');
    };

    function customerReady(callback) {
      if (customerExistsInWindow()) {
        handleCallback(callback);
      } else {
        function createListener() {
          document.addEventListener("cf:customer_ready", function() {
            handleCallback(callback);
          });
        };

        if (domIsReady()) {
          createListener();
        } else {
          document.addEventListener("DOMContentLoaded", function() {
            if (customerExistsInWindow()) {
              handleCallback(callback);
            } else {
              createListener();
            }
          });
        }
      }
    }

    function formsReady(callback) {
      if (embedFormHasMounted()) {
        handleCallback(callback);
      } else {
        function createListener() {
          document.addEventListener("cf:ready", function() {
            handleCallback(callback);
          });
        };

        if (domIsReady()) {
          createListener();
        } else {
          document.addEventListener("DOMContentLoaded", function() {
            if (embedFormHasMounted()) {
              handleCallback(callback);
            } else {
              createListener();
            }
          });
        }
      }
    };

    window.CF.customerReady = customerReady;
    window.CF.ready = formsReady;

    
      
        initializeApiScript();
      
    

    function initializeApiScript() {
      if (window.CF.requestedAPI) return;
      window.CF.requestedAPI = true;

      const $script = document.createElement('script');
      $script.src = getAssetUrl('cf-api.js');

      document.head.appendChild($script);
    }

    function getAssetUrl(filename) {
      return `https://static.customerfields.com/releases/5.2.6/${filename}`;
    }
  })();

!function(){if(!window.klaviyo){window._klOnsite=window._klOnsite||[];try{window.klaviyo=new Proxy({},{get:function(n,i){return"push"===i?function(){var n;(n=window._klOnsite).push.apply(n,arguments)}:function(){for(var n=arguments.length,o=new Array(n),w=0;w<n;w++)o[w]=arguments[w];var t="function"==typeof o[o.length-1]?o.pop():void 0,e=new Promise((function(n){window._klOnsite.push([i].concat(o,[function(i){t&&t(i),n(i)}]))}));return e}}})}catch(n){window.klaviyo=window.klaviyo||[],window.klaviyo.push=function(){var n;(n=window._klOnsite).push.apply(n,arguments)}}}}();

window.klaviyoReviewsProductDesignMode = false

(function(){if ("sendBeacon" in navigator && "performance" in window) {try {var session_token_from_headers = performance.getEntriesByType('navigation')[0].serverTiming.find(x => x.name == '_s').description;} catch {var session_token_from_headers = undefined;}var session_cookie_matches = document.cookie.match(/_shopify_s=([^;]*)/);var session_token_from_cookie = session_cookie_matches && session_cookie_matches.length === 2 ? session_cookie_matches[1] : "";var session_token = session_token_from_headers || session_token_from_cookie || "";function handle_abandonment_event(e) {var entries = performance.getEntries().filter(function(entry) {return /monorail-edge.shopifysvc.com/.test(entry.name);});if (!window.abandonment_tracked && entries.length === 0) {window.abandonment_tracked = true;var currentMs = Date.now();var navigation_start = performance.timing.navigationStart;var payload = {shop_id: 91571978526,url: window.location.href,navigation_start,duration: currentMs - navigation_start,session_token,page_type: "index"};window.navigator.sendBeacon("https://monorail-edge.shopifysvc.com/v1/produce", JSON.stringify({schema_id: "online_store_buyer_site_abandonment/1.1",payload: payload,metadata: {event_created_at_ms: currentMs,event_sent_at_ms: currentMs}}));}}window.addEventListener('pagehide', handle_abandonment_event);}}());

window.__TREKKIE_SHIM_QUEUE = window.__TREKKIE_SHIM_QUEUE || [];

(function e(e,d,r,n,o){if(void 0===o&&(o={}),!Boolean(null===(a=null===(i=window.Shopify)||void 0===i?void 0:i.analytics)||void 0===a?void 0:a.replayQueue)){var i,a;window.Shopify=window.Shopify||{};var t=window.Shopify;t.analytics=t.analytics||{};var s=t.analytics;s.replayQueue=[],s.publish=function(e,d,r){return s.replayQueue.push([e,d,r]),!0};try{self.performance.mark("wpm:start")}catch(e){}var l=function(){var e={modern:/Edge?\/(1{2}[4-9]|1[2-9]\d|[2-9]\d{2}|\d{4,})\.\d+(\.\d+|)|Firefox\/(1{2}[4-9]|1[2-9]\d|[2-9]\d{2}|\d{4,})\.\d+(\.\d+|)|Chrom(ium|e)\/(9{2}|\d{3,})\.\d+(\.\d+|)|(Maci|X1{2}).+ Version\/(15\.\d+|(1[6-9]|[2-9]\d|\d{3,})\.\d+)([,.]\d+|)( \(\w+\)|)( Mobile\/\w+|) Safari\/|Chrome.+OPR\/(9{2}|\d{3,})\.\d+\.\d+|(CPU[ +]OS|iPhone[ +]OS|CPU[ +]iPhone|CPU IPhone OS|CPU iPad OS)[ +]+(15[._]\d+|(1[6-9]|[2-9]\d|\d{3,})[._]\d+)([._]\d+|)|Android:?[ /-](13[3-9]|1[4-9]\d|[2-9]\d{2}|\d{4,})(\.\d+|)(\.\d+|)|Android.+Firefox\/(13[5-9]|1[4-9]\d|[2-9]\d{2}|\d{4,})\.\d+(\.\d+|)|Android.+Chrom(ium|e)\/(13[3-9]|1[4-9]\d|[2-9]\d{2}|\d{4,})\.\d+(\.\d+|)|SamsungBrowser\/([2-9]\d|\d{3,})\.\d+/,legacy:/Edge?\/(1[6-9]|[2-9]\d|\d{3,})\.\d+(\.\d+|)|Firefox\/(5[4-9]|[6-9]\d|\d{3,})\.\d+(\.\d+|)|Chrom(ium|e)\/(5[1-9]|[6-9]\d|\d{3,})\.\d+(\.\d+|)([\d.]+$|.*Safari\/(?![\d.]+ Edge\/[\d.]+$))|(Maci|X1{2}).+ Version\/(10\.\d+|(1[1-9]|[2-9]\d|\d{3,})\.\d+)([,.]\d+|)( \(\w+\)|)( Mobile\/\w+|) Safari\/|Chrome.+OPR\/(3[89]|[4-9]\d|\d{3,})\.\d+\.\d+|(CPU[ +]OS|iPhone[ +]OS|CPU[ +]iPhone|CPU IPhone OS|CPU iPad OS)[ +]+(10[._]\d+|(1[1-9]|[2-9]\d|\d{3,})[._]\d+)([._]\d+|)|Android:?[ /-](13[3-9]|1[4-9]\d|[2-9]\d{2}|\d{4,})(\.\d+|)(\.\d+|)|Mobile Safari.+OPR\/([89]\d|\d{3,})\.\d+\.\d+|Android.+Firefox\/(13[5-9]|1[4-9]\d|[2-9]\d{2}|\d{4,})\.\d+(\.\d+|)|Android.+Chrom(ium|e)\/(13[3-9]|1[4-9]\d|[2-9]\d{2}|\d{4,})\.\d+(\.\d+|)|Android.+(UC? ?Browser|UCWEB|U3)[ /]?(15\.([5-9]|\d{2,})|(1[6-9]|[2-9]\d|\d{3,})\.\d+)\.\d+|SamsungBrowser\/(5\.\d+|([6-9]|\d{2,})\.\d+)|Android.+MQ{2}Browser\/(14(\.(9|\d{2,})|)|(1[5-9]|[2-9]\d|\d{3,})(\.\d+|))(\.\d+|)|K[Aa][Ii]OS\/(3\.\d+|([4-9]|\d{2,})\.\d+)(\.\d+|)/},d=e.modern,r=e.legacy,n=navigator.userAgent;return n.match(d)?"modern":n.match(r)?"legacy":"unknown"}(),u="modern"===l?"modern":"legacy",c=(null!=n?n:{modern:"",legacy:""})[u],f=function(e){return[e.baseUrl,"/wpm","/b",e.hashVersion,"modern"===e.buildTarget?"m":"l",".js"].join("")}({baseUrl:d,hashVersion:r,buildTarget:u}),m=function(e){var d=e.version,r=e.bundleTarget,n=e.surface,o=e.pageUrl,i=e.monorailEndpoint;return{emit:function(e){var a=e.status,t=e.errorMsg,s=(new Date).getTime(),l=JSON.stringify({metadata:{event_sent_at_ms:s},events:[{schema_id:"web_pixels_manager_load/3.1",payload:{version:d,bundle_target:r,page_url:o,status:a,surface:n,error_msg:t},metadata:{event_created_at_ms:s}}]});if(!i)return console&&console.warn&&console.warn("[Web Pixels Manager] No Monorail endpoint provided, skipping logging."),!1;try{return self.navigator.sendBeacon.bind(self.navigator)(i,l)}catch(e){}var u=new XMLHttpRequest;try{return u.open("POST",i,!0),u.setRequestHeader("Content-Type","text/plain"),u.send(l),!0}catch(e){return console&&console.warn&&console.warn("[Web Pixels Manager] Got an unhandled error while logging to Monorail."),!1}}}}({version:r,bundleTarget:l,surface:e.surface,pageUrl:self.location.href,monorailEndpoint:e.monorailEndpoint});try{o.browserTarget=l,function(e){var d=e.src,r=e.async,n=void 0===r||r,o=e.onload,i=e.onerror,a=e.sri,t=e.scriptDataAttributes,s=void 0===t?{}:t,l=document.createElement("script"),u=document.querySelector("head"),c=document.querySelector("body");if(l.async=n,l.src=d,a&&(l.integrity=a,l.crossOrigin="anonymous"),s)for(var f in s)if(Object.prototype.hasOwnProperty.call(s,f))try{l.dataset[f]=s[f]}catch(e){}if(o&&l.addEventListener("load",o),i&&l.addEventListener("error",i),u)u.appendChild(l);else{if(!c)throw new Error("Did not find a head or body element to append the script");c.appendChild(l)}}({src:f,async:!0,onload:function(){if(!function(){var e,d;return Boolean(null===(d=null===(e=window.Shopify)||void 0===e?void 0:e.analytics)||void 0===d?void 0:d.initialized)}()){var d=window.webPixelsManager.init(e)||void 0;if(d){var r=window.Shopify.analytics;r.replayQueue.forEach((function(e){var r=e[0],n=e[1],o=e[2];d.publishCustomEvent(r,n,o)})),r.replayQueue=[],r.publish=d.publishCustomEvent,r.visitor=d.visitor,r.initialized=!0}}},onerror:function(){return m.emit({status:"failed",errorMsg:"".concat(f," has failed to load")})},sri:function(e){var d=/^sha384-[A-Za-z0-9+/=]+$/;return"string"==typeof e&&d.test(e)}(c)?c:"",scriptDataAttributes:o}),m.emit({status:"loading"})}catch(e){m.emit({status:"failed",errorMsg:(null==e?void 0:e.message)||"Unknown error"})}}})({shopId: 91571978526,storefrontBaseUrl: "https://pickleand.club",extensionsBaseUrl: "https://extensions.shopifycdn.com/cdn/shopifycloud/web-pixels-manager",monorailEndpoint: "https://monorail-edge.shopifysvc.com/unstable/produce_batch",surface: "storefront-renderer",enabledBetaFlags: ["72028870","2dca8a86","d5bdd5d0","5476ea20","5acaffe6"],webPixelsConfigList: [{"id":"2135359774","configuration":"{\"accountID\":\"QUKg9Q\",\"webPixelConfig\":\"eyJlbmFibGVBZGRlZFRvQ2FydEV2ZW50cyI6IHRydWV9\"}","eventPayloadVersion":"v1","runtimeContext":"STRICT","scriptVersion":"524f6c1ee37bacdca7657a665bdca589","type":"APP","apiClientId":123074,"privacyPurposes":["ANALYTICS","MARKETING"],"dataSharingAdjustments":{"protectedCustomerApprovalScopes":["read_customer_address","read_customer_email","read_customer_name","read_customer_personal_data","read_customer_phone"],"dataSharingControls":[]},"dataSharingState":"optimized","enabledFlags":["9a3ed68a"]},{"id":"1865974046","configuration":"{\"subdomain\":\"h6pafc-61\"}","eventPayloadVersion":"v1","runtimeContext":"STRICT","scriptVersion":"c35482b6b2cc46ff1af7ffc6ed1ed137","type":"APP","apiClientId":1615517,"privacyPurposes":["ANALYTICS","MARKETING","SALE_OF_DATA"],"dataSharingAdjustments":{"protectedCustomerApprovalScopes":["read_customer_address","read_customer_email","read_customer_name","read_customer_personal_data","read_customer_phone"],"dataSharingControls":[]},"dataSharingState":"optimized","enabledFlags":["9a3ed68a"]},{"id":"1523941662","configuration":"{\"config\":\"{\\\"google_tag_ids\\\":[\\\"AW-17034514871\\\",\\\"GT-WP4RFFSK\\\",\\\"G-6MMYH6M8S3\\\"],\\\"gtag_events\\\":[{\\\"type\\\":\\\"begin_checkout\\\",\\\"action_label\\\":[\\\"AW-17034514871\\\/2650CJHV370aELej2Lo_\\\",\\\"AW-17034514871\\\",\\\"G-6MMYH6M8S3\\\"]},{\\\"type\\\":\\\"search\\\",\\\"action_label\\\":[\\\"AW-17034514871\\\/W8JLCJ3V370aELej2Lo_\\\",\\\"AW-17034514871\\\",\\\"G-6MMYH6M8S3\\\"]},{\\\"type\\\":\\\"view_item\\\",\\\"action_label\\\":[\\\"AW-17034514871\\\/PDMcCJrV370aELej2Lo_\\\",\\\"AW-17034514871\\\",\\\"G-6MMYH6M8S3\\\"]},{\\\"type\\\":\\\"purchase\\\",\\\"action_label\\\":[\\\"AW-17034514871\\\/9gHmCI7V370aELej2Lo_\\\",\\\"AW-17034514871\\\",\\\"G-6MMYH6M8S3\\\"]},{\\\"type\\\":\\\"page_view\\\",\\\"action_label\\\":[\\\"AW-17034514871\\\/t4EDCJfV370aELej2Lo_\\\",\\\"AW-17034514871\\\",\\\"G-6MMYH6M8S3\\\"]},{\\\"type\\\":\\\"add_payment_info\\\",\\\"action_label\\\":[\\\"AW-17034514871\\\/8z0mCNLa370aELej2Lo_\\\",\\\"AW-17034514871\\\",\\\"G-6MMYH6M8S3\\\"]},{\\\"type\\\":\\\"add_to_cart\\\",\\\"action_label\\\":[\\\"AW-17034514871\\\/m-cuCJTV370aELej2Lo_\\\",\\\"AW-17034514871\\\",\\\"G-6MMYH6M8S3\\\"]}],\\\"enable_monitoring_mode\\\":false}\"}","eventPayloadVersion":"v1","runtimeContext":"OPEN","scriptVersion":"5a723296a9ab7d2cddda5d574ded9c79","type":"APP","apiClientId":1780363,"privacyPurposes":[],"dataSharingAdjustments":{"protectedCustomerApprovalScopes":["read_customer_address","read_customer_email","read_customer_name","read_customer_personal_data","read_customer_phone"],"dataSharingControls":["share_all_events"]},"dataSharingState":"optimized","enabledFlags":["9a3ed68a"]},{"id":"1489109278","configuration":"{\"pixel_id\":\"638188665652068\",\"pixel_type\":\"facebook_pixel\"}","eventPayloadVersion":"v1","runtimeContext":"OPEN","scriptVersion":"d72ab942028ee4f6bccc581083be605e","type":"APP","apiClientId":2329312,"privacyPurposes":["ANALYTICS","MARKETING","SALE_OF_DATA"],"dataSharingAdjustments":{"protectedCustomerApprovalScopes":["read_customer_address","read_customer_email","read_customer_name","read_customer_personal_data","read_customer_phone"],"dataSharingControls":["share_all_events"]},"dataSharingState":"optimized","enabledFlags":["9a3ed68a"]},{"id":"shopify-app-pixel","configuration":"{}","eventPayloadVersion":"v1","runtimeContext":"STRICT","scriptVersion":"0450","apiClientId":"shopify-pixel","type":"APP","privacyPurposes":["ANALYTICS","MARKETING"]},{"id":"shopify-custom-pixel","eventPayloadVersion":"v1","runtimeContext":"LAX","scriptVersion":"0450","apiClientId":"shopify-pixel","type":"CUSTOM","privacyPurposes":["ANALYTICS","MARKETING"]}],isMerchantRequest: false,initData: {"shop":{"name":"PICKLE \u0026","paymentSettings":{"currencyCode":"HKD"},"myshopifyDomain":"h6pafc-61.myshopify.com","countryCode":"HK","storefrontUrl":"https:\/\/pickleand.club"},"customer":null,"cart":{"cost":{"totalAmount":{"amount":450.0,"currencyCode":"HKD"}},"lines":[{"cost":{"totalAmount":{"amount":450.0,"currencyCode":"HKD"}},"merchandise":{"price":{"amount":450.0,"currencyCode":"HKD"},"product":{"title":"COURT GREEN","vendor":"PICKLE \u0026","id":"9675064017182","untranslatedTitle":"COURT GREEN","url":"\/products\/court-dink","type":"Court"},"id":"49750791192862","image":{"src":"\/\/pickleand.club\/cdn\/shop\/files\/250411_-_Pickle__008.jpg?v=1744701445"},"sku":"","title":"PEAK $450","untranslatedTitle":"PEAK $450"},"quantity":1}],"totalQuantity":1,"attributes":[],"id":"hWNBZcAydFSxrYlxPvj7quTk"},"checkout":null,"productVariants":[],"purchasingCompany":null},},"https://pickleand.club/cdn","d93fb6acwa1fec5e6p3f0901a3m63922e87",{"modern":"","legacy":""},{"trekkieShim":true,"shopId":"91571978526","storefrontBaseUrl":"https:\/\/pickleand.club","extensionBaseUrl":"https:\/\/extensions.shopifycdn.com\/cdn\/shopifycloud\/web-pixels-manager","surface":"storefront-renderer","enabledBetaFlags":"[\"72028870\", \"2dca8a86\", \"d5bdd5d0\", \"5476ea20\", \"5acaffe6\"]","isMerchantRequest":"false","hashVersion":"d93fb6acwa1fec5e6p3f0901a3m63922e87","publish":"custom","events":"[[\"page_viewed\",{}]]"});

window.ShopifyAnalytics = window.ShopifyAnalytics || {};
  window.ShopifyAnalytics.meta = window.ShopifyAnalytics.meta || {};
  window.ShopifyAnalytics.meta.currency = 'HKD';
  var meta = {"page":{"pageType":"home","requestId":"c7c202f1-53bb-45fb-8654-4720a7fe2fa2-1777451021"}};
  for (var attr in meta) {
    window.ShopifyAnalytics.meta[attr] = meta[attr];
  }

(function () {
    var customDocumentWrite = function(content) {
      var jquery = null;

      if (window.jQuery) {
        jquery = window.jQuery;
      } else if (window.Checkout && window.Checkout.$) {
        jquery = window.Checkout.$;
      }

      if (jquery) {
        jquery('body').append(content);
      }
    };

    var hasLoggedConversion = function(token) {
      if (token) {
        return document.cookie.indexOf('loggedConversion=' + token) !== -1;
      }
      return false;
    }

    var setCookieIfConversion = function(token) {
      if (token) {
        var twoMonthsFromNow = new Date(Date.now());
        twoMonthsFromNow.setMonth(twoMonthsFromNow.getMonth() + 2);

        document.cookie = 'loggedConversion=' + token + '; expires=' + twoMonthsFromNow;
      }
    }

    var trekkie = window.ShopifyAnalytics.lib = window.trekkie = window.trekkie || [];
    window.ShopifyAnalytics.lib.trekkie = window.trekkie;
    if (trekkie.integrations) {
      return;
    }
    trekkie.methods = [
      'identify',
      'page',
      'ready',
      'track',
      'trackForm',
      'trackLink'
    ];
    trekkie.factory = function(method) {
      return function() {
        var args = Array.prototype.slice.call(arguments);
        args.unshift(method);
        trekkie.push(args);
        if (window.__TREKKIE_SHIM_QUEUE && (method == 'track' || method == 'page')) {
          try {
            window.__TREKKIE_SHIM_QUEUE.push({
              from: 'trekkie-stub',
              method: method,
              args: args.slice(1)
            });
          } catch (e) {
            // no-op
          }
        }
        return trekkie;
      };
    };
    for (var i = 0; i < trekkie.methods.length; i++) {
      var key = trekkie.methods[i];
      trekkie[key] = trekkie.factory(key);
    }
    trekkie.load = function(config) {
      trekkie.config = config || {};
      trekkie.config.initialDocumentCookie = document.cookie;
      var first = document.getElementsByTagName('script')[0];
var script = document.createElement('script');
script.type = 'text/javascript';
script.onerror = function(e) {
  var scriptFallback = document.createElement('script');
  scriptFallback.type = 'text/javascript';
  scriptFallback.onerror = function(error) {
          var Monorail = {
      produce: function produce(monorailDomain, schemaId, payload) {
        var currentMs = new Date().getTime();
        var event = {
          schema_id: schemaId,
          payload: payload,
          metadata: {
            event_created_at_ms: currentMs,
            event_sent_at_ms: currentMs
          }
        };
        return Monorail.sendRequest("https://" + monorailDomain + "/v1/produce", JSON.stringify(event));
      },
      sendRequest: function sendRequest(endpointUrl, payload) {
        // Try the sendBeacon API
        if (window && window.navigator && typeof window.navigator.sendBeacon === 'function' && typeof window.Blob === 'function' && !Monorail.isIos12()) {
          var blobData = new window.Blob([payload], {
            type: 'text/plain'
          });

          if (window.navigator.sendBeacon(endpointUrl, blobData)) {
            return true;
          } // sendBeacon was not successful

        } // XHR beacon

        var xhr = new XMLHttpRequest();

        try {
          xhr.open('POST', endpointUrl);
          xhr.setRequestHeader('Content-Type', 'text/plain');
          xhr.send(payload);
        } catch (e) {
          console.log(e);
        }

        return false;
      },
      isIos12: function isIos12() {
        return window.navigator.userAgent.lastIndexOf('iPhone; CPU iPhone OS 12_') !== -1 || window.navigator.userAgent.lastIndexOf('iPad; CPU OS 12_') !== -1;
      }
    };
    Monorail.produce('monorail-edge.shopifysvc.com',
      'trekkie_storefront_load_errors/1.1',
      {shop_id: 91571978526,
      theme_id: 175452520734,
      app_name: "storefront",
      context_url: window.location.href,
      source_url: "//pickleand.club/cdn/s/trekkie.storefront.40cf76d5f324d17d3a4347e25c36a3e219ef9f79.min.js"});

  };
  scriptFallback.async = true;
  scriptFallback.src = '//pickleand.club/cdn/s/trekkie.storefront.40cf76d5f324d17d3a4347e25c36a3e219ef9f79.min.js';
  first.parentNode.insertBefore(scriptFallback, first);
};
script.async = true;
script.src = '//pickleand.club/cdn/s/trekkie.storefront.40cf76d5f324d17d3a4347e25c36a3e219ef9f79.min.js';
first.parentNode.insertBefore(script, first);

    };
    trekkie.load(
      {"Trekkie":{"appName":"storefront","development":false,"defaultAttributes":{"shopId":91571978526,"isMerchantRequest":null,"themeId":175452520734,"themeCityHash":"10947351531080502845","contentLanguage":"en","currency":"HKD","eventMetadataId":"2fc9a3ab-b1e3-4970-82cb-c1c797faf0aa"},"isServerSideCookieWritingEnabled":true,"monorailRegion":"shop_domain","enabledBetaFlags":["b5387b81","d5bdd5d0"]},"Session Attribution":{},"S2S":{"facebookCapiEnabled":true,"source":"trekkie-storefront-renderer","apiClientId":580111}}
    );

    var loaded = false;
    trekkie.ready(function() {
      if (loaded) return;
      loaded = true;

      window.ShopifyAnalytics.lib = window.trekkie;

      var originalDocumentWrite = document.write;
      document.write = customDocumentWrite;
      try { window.ShopifyAnalytics.merchantGoogleAnalytics.call(this); } catch(error) {};
      document.write = originalDocumentWrite;

      window.ShopifyAnalytics.lib.page(null,{"pageType":"home","requestId":"c7c202f1-53bb-45fb-8654-4720a7fe2fa2-1777451021","shopifyEmitted":true});

      var match = window.location.pathname.match(/checkouts\/(.+)\/(thank_you|post_purchase)/)
      var token = match? match[1]: undefined;
      if (!hasLoggedConversion(token)) {
        setCookieIfConversion(token);
        
      }
    });

    var eventsListenerScript = document.createElement('script');
    eventsListenerScript.async = true;
    eventsListenerScript.src = "//pickleand.club/cdn/shopifycloud/storefront/assets/shop_events_listener-3da45d37.js";
    document.getElementsByTagName('head')[0].appendChild(eventsListenerScript);
})();

function GotoClass(url) {
    window.location.href=url;
  }

document.addEventListener('DOMContentLoaded', function () {
    function isIE() {
      const ua = window.navigator.userAgent;
      const msie = ua.indexOf('MSIE ');
      const trident = ua.indexOf('Trident/');

      return msie > 0 || trident > 0;
    }

    if (!isIE()) return;
    const hiddenInput = document.querySelector('#product-form-template--24127198298398__featured_product_Ne9CkP input[name="id"]');
    const noScriptInputWrapper = document.createElement('div');
    const variantSwitcher =
      document.querySelector('variant-radios[data-section="template--24127198298398__featured_product_Ne9CkP"]') ||
      document.querySelector('variant-selects[data-section="template--24127198298398__featured_product_Ne9CkP"]');
    noScriptInputWrapper.innerHTML = document.querySelector(
      '.product-form__noscript-wrapper-template--24127198298398__featured_product_Ne9CkP'
    ).textContent;
    variantSwitcher.outerHTML = noScriptInputWrapper.outerHTML;

    document.querySelector('#Variants-template--24127198298398__featured_product_Ne9CkP').addEventListener('change', function (event) {
      hiddenInput.value = event.currentTarget.value;
    });
  });

document.addEventListener('DOMContentLoaded', function () {
    function isIE() {
      const ua = window.navigator.userAgent;
      const msie = ua.indexOf('MSIE ');
      const trident = ua.indexOf('Trident/');

      return msie > 0 || trident > 0;
    }

    if (!isIE()) return;
    const hiddenInput = document.querySelector('#product-form-template--24127198298398__featured_product input[name="id"]');
    const noScriptInputWrapper = document.createElement('div');
    const variantSwitcher =
      document.querySelector('variant-radios[data-section="template--24127198298398__featured_product"]') ||
      document.querySelector('variant-selects[data-section="template--24127198298398__featured_product"]');
    noScriptInputWrapper.innerHTML = document.querySelector(
      '.product-form__noscript-wrapper-template--24127198298398__featured_product'
    ).textContent;
    variantSwitcher.outerHTML = noScriptInputWrapper.outerHTML;

    document.querySelector('#Variants-template--24127198298398__featured_product').addEventListener('change', function (event) {
      hiddenInput.value = event.currentTarget.value;
    });
  });

(async function() {
await import("//pickleand.club/cdn/shopifycloud/shop-js/modules/v2/loader.shop-follow-button.en.esm.js");
})();


window.shopUrl = 'https://pickleand.club';
        window.routes = {
          cart_add_url: '/cart/add',
          cart_change_url: '/cart/change',
          cart_update_url: '/cart/update',
          cart_url: '/cart',
          predictive_search_url: '/search/suggest',
        };
  
        window.cartStrings = {
          error: `There was an error while updating your cart. Please try again.`,
          quantityError: `You can only add [quantity] of this item to your cart.`,
        };
  
        window.variantStrings = {
          addToCart: `Add to cart`,
          soldOut: `Sold out`,
          unavailable: `Unavailable`,
          unavailable_with_option: `[value] - Unavailable`,
        };
  
        window.quickOrderListStrings = {
          itemsAdded: `[quantity] items added`,
          itemAdded: `[quantity] item added`,
          itemsRemoved: `[quantity] items removed`,
          itemRemoved: `[quantity] item removed`,
          viewCart: `View cart`,
          each: `[money]/ea`,
        };
  
        window.accessibilityStrings = {
          imageAvailable: `Image [index] is now available in gallery view`,
          shareSuccess: `Link copied to clipboard`,
          pauseSlideshow: `Pause slideshow`,
          playSlideshow: `Play slideshow`,
          recipientFormExpanded: `Gift card recipient form expanded`,
          recipientFormCollapsed: `Gift card recipient form collapsed`,
        };

        function clickAllVisibleVariantLabels() {
        
          document.getElementById('openflowio').addEventListener('click', function(event) {
            event.preventDefault(); // Prevent default link behavior
            
            const aiChatDiv = document.getElementById('AIChat');
            // Toggle display
            if (aiChatDiv.style.display === 'none' || aiChatDiv.style.display === '') {
              aiChatDiv.style.display = 'flex'; // Show the div
            } else {
              aiChatDiv.style.display = 'none'; // Hide the div
            }
          });

          // Find all product-info elements
          const productInfoElements = document.querySelectorAll('product-info');
          
          if (productInfoElements == null || productInfoElements.length === 0) {
            console.error('No product-info elements found');
            return;
          }
          
          console.log(`Found ${productInfoElements.length} product-info elements`);
          
          // Go through each product-info element
          productInfoElements.forEach((productInfo, index) => {
            // Find variant-radios within this product-info
            const variantRadios = productInfo.querySelector('variant-radios');
            
            if (!variantRadios) {
              console.warn(`No variant-radios found in product-info #${index + 1}`);
              return;
            }
            
            // Get all labels within variant-radios
            const labels = variantRadios.querySelectorAll('label');
            let clickedAny = false;
            
            for (const label of labels) {
              // Get computed style to check if the element is truly visible
              const style = window.getComputedStyle(label);
              const isVisible = style.display !== 'none' && 
                                style.visibility !== 'hidden' && 
                                style.opacity !== '0';
              
              if (isVisible) {
                // Get the "for" attribute
                const forAttribute = label.getAttribute('for');
                
                if (forAttribute) {
                  // Find the corresponding radio input
                  const radioInput = document.getElementById(forAttribute);
                  
                  if (radioInput) {
                    console.log(`Product #${index + 1}: Clicking radio input for: ${label.textContent.trim()}`);
                    
                    // Create and dispatch a click event
                    const clickEvent = new MouseEvent('click', {
                      view: window,
                      bubbles: true,
                      cancelable: true
                    });
                    
                    radioInput.dispatchEvent(clickEvent);
                    label.dispatchEvent(clickEvent);
                    clickedAny = true;
                    break; // Move to the next product-info after clicking one label
                  } else {
                    console.warn(`Product #${index + 1}: Could not find radio input with ID: ${forAttribute}`);
                  }
                } else {
                  console.warn(`Product #${index + 1}: Label does not have a "for" attribute`);
                }
              }
            }
            
            if (!clickedAny) {
              console.warn(`Product #${index + 1}: No visible labels found to click`);
            }
          });
        }
        document.addEventListener('DOMContentLoaded', clickAllVisibleVariantLabels);

window['ShopifyForms'] = {
    ...window['ShopifyForms'],
    currentPageType: "index"
  };

var bonShopInfo = {"shopName":"h6pafc-61.myshopify.com","displayWidget":true,"shopInfo":{"currency":"HKD","country_code":"HK","weight_unit":"kg","point_name":"","referral_enabled":false},"appearance":{"theme_configs_json":{"color":{"text_color":"#ffffff","primary_color":"#ed66b2","secondary_color":"#86469c","section_text_color":"#000000","section_border_color":"#EF93C7","section_background_color":"#ffffff","function_button_text_color":"#000000","function_button_border_color":"#EF93C7","function_button_background_color":"#ffffff"},"banner_img":"","showIllustration":true},"is_first_time":false,"widget_button_configs_json":{"placement":{"widget_spacing":{"side":"20px","bottom":"20px"},"widget_button_position":1},"widget_icon":"widget-icon-1.svg","widget_title":"Rewards"},"displayed_text_configs_json":{"vip_tier":{"spend":"Spend {{money}}","next_tier":"Next tier","earn_point":"Earn {{point_amount}} {{point_name}}","entry_text":"You are at the entry level, unlock next tier to receive attractive benefits","current_tier":"Current tier","number_of_use":"Number of use: {{number_of_use}}","purchase_more":"Purchase more","earn_more_point":"Earn more {{point_name}}","highest_tier_txt":"You have reached the highest tier!","next_tier_money_spent_txt":"Next tier: Spend {{money}} more by {{date}}","next_tier_points_earned_txt":"Next tier: Get {{point_amount}} more {{point_name}} by {{date}}","complete_order_multi_points_txt":"x{{multi_points}} {{point_name}} for “Complete an order” rule","next_tier_money_spent_lifetime_txt":"Next tier: Spend {{money}} more","next_tier_points_earned_lifetime_txt":"Next tier: Get {{point_amount}} more {{point_name}}"},"my_balance":{"date":"Date","total":"Total","points":"Points","actions":"Actions","no_value":"There is no activitiy to show at the moment","referred":"Referred by a friend","referrer":"Refer a friend","point_expiry":"Your points have expired","refund_order":"Refund order","return_points":"Return points for redeemed code","new_tier_reward":"New tier's reward: {{reward_name}}","my_balance_button":"Earn more","refund_order_tier":"Return points for VIP Tier's benefit","cancel_order_status":"Cancel order","complete_order_tier":"VIP tier’s benefit for completing an order","store_owner_adjusted":"Store owner just adjusted your points"},"my_rewards":{"expired":"Expired","no_value":"You don't have any rewards at the moment","apply_for":"Apply for {{collection}}","reward_name":"Reward name","used_button":"Used","reward_button":"Use it now","can_combine_with":"Can combine with: {{discount_type}}","get_some_rewards":"Get some rewards","reward_explanation":"Reward details","order_combine_discount":"Order discount","product_combine_discount":"Product discount","shipping_combine_discount":"Shipping discount"},"sign_in_page":{"welcome":"Welcome","vip_tier":"VIP Tiers","earn_point":"Earn points","my_balance":"My balance","my_rewards":"My rewards","your_point":"Your points","join_button":"Join","program_name":"Reward program","redeem_point":"Redeem points","sign_in_button":"Sign in","sign_in_tagline":"Join our program to get attractive rewards!","referral_program":"Referral Program","sign_in_requirement_message":"Oops! You have to sign in to do this action"},"earn_points_tab":{"retweet":"Retweet","no_value":"There is no earning rule to show at the moment","required":"You can only do this once.","save_date":"Save date","follow_tiktok":"Follow on TikTok","join_fb_group":"Join a Facebook group","share_twitter":"Share on X","complete_order":"Complete an order","create_account":"Create an account","earn_for_every":"Earn {{complete_order_reward_point}} points for every {{money_value}}","follow_twitter":"Follow on X","happy_birthday":"Happy birthday","leave_a_review":"Leave a review","share_facebook":"Share on Facebook","share_linkedin":"Share on LinkedIn","sign_up_button":"Do it now","follow_facebook":"Like on Facebook","follow_linkedin":"Follow on LinkedIn","complete_profile":"Complete profile","follow_instagram":"Follow on Instagram","follow_pinterest":"Follow on Pinterest","message_birthday":"Entering a date within 30 days won’t earn you points","subscribe_youtube":"Subscribe on Youtube","subcrible_newletter":"Subscribe to newsletter","happy_birthday_button":"Enter info","leave_a_review_action":"Purchase to review","place_an_order_button":"Purchase","leave_a_review_tooltip":"You’ll get points when your review is published","complete_profile_dialog":"After you fill in all info, please comeback and click this button one more time so our system can reward you correctly","like_on_facebook_button":"Take me there","receive_points_birthday":"You’ll receive points on your birthday"},"complete_profile":{"reset":"Reset","gender":"Gender","complete":"Complete","last_name":"Last name","type_here":"Type here","first_name":"First name","phone_number":"Phone number","warning_text":"You are required to complete this section ","date_of_birth":"Date of birth","gender_dropdown":{"male":"Male","female":"Female","others":"Others"},"select_your_gender":"Select your gender","enter_your_number_here":"Enter your number here"},"notification_tab":{"copied":"Copied","hover_copy":"Copy to clipboard","title_fail":"Oops","message_fail":"Something went wrong! Please enter a valid date","title_success_input":"Yay!","title_success_letter":"Great!","message_success_input":"You will receive points on your birthday","message_success_letter":"You are now subscribed to our newsletter","complete_profile_success":"You completed your profile"},"redeem_points_tab":{"maximum":"The most shipping cost this code covers","minimum":"Lowest spending amount","no_value":"There is no redeeming rule to show at the moment","expire_at":"Expires on","apply_button":"Apply now","apply_message":"Apply this code to your shopping cart. If you do not use this code now, you can always find it in My rewards tab anytime","expires_after":"Expires after {{expire_days}} days","redeem_button":"Redeem","discount_value":"The amount of the discount","max_point_value":"Maximum point value: {{max_point_value}}","min_point_value":"Minimum point value: {{min_point_value}}","apply_for_variant":"Apply for variant: {{variant_name}}","discount_condition":"Discount rules","increments_of_points":"You will get {{money_value}} off your entire order for {{reward_value}} points redeemed","apply_for_all_variants":"Apply for all variants"},"referral_program_tab":{"order_now":"Order now","referral_button":"Refer a friend now","share_and_claim":"Share this link and claim {{reward_name}}","referral_tagline":"Get rewards when your friend uses the referral link to sign up and place an order","sign_up_and_claim":"Sign up and claim your {{reward_name}} now","text_for_referral":"You will get {{referral_name}}","referrer_send_gift":"{{referrer_name}} has sent you a gift!","reward_for_referrer":"Reward for referrer","complete_first_order":"Completing your first order to send a thank-you reward to your referrer.","reward_was_sent_email":"We’ve sent the reward to your email.","text_for_referral_friend":"They will get {{referral_friend_name}}","reward_for_referred_friend":"Reward for referred friend","referral_tagline_successful":"Your referral is successful once the referred friend's first order is fulfilled."}},"hide_on_mobile":false,"show_title":true,"corner":0,"button_type":1,"button_type_mobile":1,"show_brand_mark":true,"visible_urls":"","custom_css":""},"programStatus":false,"shrink_header":false,"widgetTitles":[{"lang":"en","widget_title":"Rewards"}],"baseURL":"https:\/\/app.bonloyalty.com","assetURL":"https:\/\/d31wum4217462x.cloudfront.net","resourceUrl":"https:\/\/cdn.bonloyalty.com","versionBranding":1749550439,"campaigns":[],"orderBoosterPopup":{"status":false,"type":0,"branding_json":{"text_color":"#000000","background_color":"#F3F3F3"},"icon":"popup-icon-1.svg","file":null,"displayed_text_configs_json":{"text":[{"lang":"en","body_text":"Complete {{order_number}} more orders to earn {{point_value}} {{point_name}}","heading_text":"Almost there!"}],"body_font_size":"14","heading_font_size":"14"},"except_url":null}};

var BONLtoConfig = {"type":"flip","duration":2};

var bonAppExtension = {"earningPlaceOrderRule":{"calculate_type":1,"reward_value":10,"is_active":false,"created_at":"2025-02-23T10:54:12.000000Z","extension":{"point":1,"rules":[],"spent":1,"all_product":true,"id":16763}}};

var bonLpInfo = null;

if(typeof __st.cid=='undefined') {
        __st.cid = ''
    }
    if(typeof BONCustomerId=='undefined'){
        var BONCustomerId = ''
    }

window._cowlendarConfig = {
  "shop_id": "91571978526",
  "shop_url": "h6pafc-61.myshopify.com",
  "shop_locale": "en"
  };
  var COW_SHOP_URL = "h6pafc-61.myshopify.com";
  window._cowProductVariants = null;

const chunks = [];
  
    /* New metafields loop */  
    
    
      
      
        chunks.push("[{\"product_handle\":\"book-court\",\"service_id\":\"677e3c9bb6fca5b98389cdd8\",\"type\":\"classic-checkout\",\"product_id\":9672731328798,\"variant_ids\":[49497936855326],\"use_multilanguage\":false,\"authorized_locales\":[\"en-US\"],\"default_locale\":\"en-US\",\"btn_label\":\"Book now\",\"book_now_default_label\":\"Book now\",\"book_now_labels\":null,\"slot_lock_remove_expired\":true,\"hide_variants_selector\":false},{\"product_handle\":\"book-court\",\"service_id\":\"67bb05c411f129505e48ff97\",\"type\":\"classic-checkout\",\"product_id\":9672731328798,\"variant_ids\":[49636278763806],\"use_multilanguage\":false,\"authorized_locales\":[\"en-US\"],\"default_locale\":\"en-US\",\"btn_label\":\"Book now\",\"book_now_default_label\":\"Book now\",\"book_now_labels\":null,\"slot_lock_remove_expired\":true,\"hide_variants_selector\":false},{\"product_handle\":\"book-court\",\"service_id\":\"67c7c5ce96ec9652744b481d\",\"type\":\"multislot-checkout\",\"product_id\":9672731328798,\"variant_ids\":[49636102013214],\"use_multilanguage\":false,\"authorized_locales\":[\"en-US\"],\"default_locale\":\"en-US\",\"btn_label\":\"Book now\",\"book_now_default_label\":\"Book now\",\"book_now_labels\":null,\"slot_lock_remove_expired\":true,\"hide_variants_selector\":false},{\"product_handle\":\"book-court\",\"service_id\":\"67c8156496ec9652744eb816\",\"type\":\"classic-checkout\",\"product_id\":9672731328798,\"variant_ids\":[49636700193054],\"use_multilanguage\":false,\"authorized_locales\":[\"en-US\"],\"default_locale\":\"en-US\",\"btn_label\":\"Book now\",\"book_now_default_label\":\"Book now\",\"book_now_labels\":null,\"slot_lock_remove_expired\":true,\"hide_variants_selector\":false},{\"product_handle\":\"book-court\",\"service_id\":\"67c8169696ec9652744ec7c6\",\"type\":\"classic-checkout\",\"product_id\":9672731328798,\"variant_ids\":[49636700160286],\"use_multilanguage\":false,\"authorized_locales\":[\"en-US\"],\"default_locale\":\"en-US\",\"btn_label\":\"Book now\",\"book_now_default_label\":\"Book now\",\"book_now_labels\":null,\"slot_lock_remove_expired\":true,\"hide_variants_selector\":false},{\"product_handle\":\"book-court\",\"service_id\":\"67d285c00c75c5fcc50fa33d\",\"type\":\"multislot-checkout\",\"product_id\":9672731328798,\"variant_ids\":[49657855082782],\"use_multilanguage\":false,\"authorized_locales\":[\"en-US\"],\"default_locale\":\"en-US\",\"btn_label\":\"Book now\",\"book_now_default_label\":\"Book now\",\"book_now_labels\":null,\"slot_lock_remove_expired\":true,\"hide_variants_selector\":false},{\"product_handle\":\"book-court\",\"service_id\":\"67d285df0c75c5fcc50fa4bd\",\"type\":\"multislot-checkout\",\"product_id\":9672731328798,\"variant_ids\":[49657854427422],\"use_multilanguage\":false,\"authorized_locales\":[\"en-US\"],\"default_locale\":\"en-US\",\"btn_label\":\"Book now\",\"book_now_default_label\":\"Book now\",\"book_now_labels\":null,\"slot_lock_remove_expired\":true,\"hide_variants_selector\":false},{\"product_handle\":\"book-court\",\"service_id\":\"67d3dde85736e386646761c0\",\"type\":\"classic-checkout\",\"product_id\":9672731328798,\"variant_ids\":[49661233955102],\"use_multilanguage\":false,\"authorized_locales\":[\"en-US\"],\"default_locale\":\"en-US\",\"btn_label\":\"Book now\",\"book_now_default_label\":\"Book now\",\"book_now_labels\":null,\"slot_lock_remove_expired\":true,\"hide_variants_selector\":false},{\"product_handle\":\"court-dink\",\"service_id\":\"67d41e325736e386646acedb\",\"type\":\"classic-checkout\",\"product_id\":9675064017182,\"variant_ids\":[49750791225630],\"use_multilanguage\":false,\"authorized_locales\":[\"en-US\"],\"default_locale\":\"en-US\",\"btn_label\":\"Book now\",\"book_now_default_label\":\"Book now\",\"book_now_labels\":null,\"slot_lock_remove_expired\":true,\"hide_variants_selector\":false},{\"product_handle\":\"court-dink\",\"service_id\":\"67d41e575736e386646ad11d\",\"type\":\"classic-checkout\",\"product_id\":9675064017182,\"variant_ids\":[49750791192862],\"use_multilanguage\":false,\"authorized_locales\":[\"en-US\"],\"default_locale\":\"en-US\",\"btn_label\":\"Book now\",\"book_now_default_label\":\"Book now\",\"book_now_labels\":null,\"slot_lock_remove_expired\":true,\"hide_variants_selector\":false},{\"product_handle\":\"court-dink\",\"service_id\":\"67d4201b5736e386646aeb51\",\"type\":\"classic-checkout\",\"product_id\":9675064017182,\"variant_ids\":[49750791291166],\"use_multilanguage\":false,\"authorized_locales\":[\"en-US\"],\"default_locale\":\"en-US\",\"btn_label\":\"Book now\",\"book_now_default_label\":\"Book now\",\"book_now_labels\":null,\"slot_lock_remove_expired\":true,\"hide_variants_selector\":false},{\"product_handle\":\"court-dink\",\"service_id\":\"67d420af5736e386646af5c5\",\"type\":\"classic-checkout\",\"product_id\":9675064017182,\"variant_ids\":[49750791258398],\"use_multilanguage\":false,\"authorized_locales\":[\"en-US\"],\"default_locale\":\"en-US\",\"btn_label\":\"Book now\",\"book_now_default_label\":\"Book now\",\"book_now_labels\":null,\"slot_lock_remove_expired\":true,\"hide_variants_selector\":false},{\"product_handle\":\"court-dink\",\"service_id\":\"67d421795736e386646b01e7\",\"type\":\"classic-checkout\",\"product_id\":9675064017182,\"variant_ids\":[49750791323934],\"use_multilanguage\":false,\"authorized_locales\":[\"en-US\"],\"default_locale\":\"en-US\",\"btn_label\":\"Book now\",\"book_now_default_label\":\"Book now\",\"book_now_labels\":null,\"slot_lock_remove_expired\":true,\"hide_variants_selector\":false},{\"product_handle\":\"court-dink\",\"service_id\":\"6805f840622e6df3303e475c\",\"type\":\"classic-checkout\",\"product_id\":9675064017182,\"variant_ids\":[49750791356702],\"use_multilanguage\":false,\"authorized_locales\":[\"en-US\"],\"default_locale\":\"en-US\",\"btn_label\":\"Book now\",\"book_now_default_label\":\"Book now\",\"book_now_labels\":null,\"slot_lock_remove_expired\":true,\"hide_variants_selector\":false},{\"product_handle\":\"court-dink\",\"service_id\":\"6809ae04622e6df3304b679f\",\"type\":\"classic-checkout\",\"product_id\":9675064017182,\"variant_ids\":[49757753311518],\"use_multilanguage\":false,\"authorized_locales\":[\"en-US\"],\"default_locale\":\"en-US\",\"btn_label\":\"Book now\",\"book_now_default_label\":\"Book now\",\"book_now_labels\":null,\"slot_lock_remove_expired\":true,\"hide_variants_selector\":false},{\"product_handle\":\"court-dink\",\"service_id\":\"680f047f23d88eefdc7d2131\",\"type\":\"classic-checkout\",\"product_id\":9675064017182,\"variant_ids\":[49766686195998],\"use_multilanguage\":false,\"authorized_locales\":[\"en-US\"],\"default_locale\":\"en-US\",\"btn_label\":\"Book now\",\"book_now_default_label\":\"Book now\",\"book_now_labels\":null,\"slot_lock_remove_expired\":true,\"hide_variants_selector\":false},{\"product_handle\":\"court-dink\",\"service_id\":\"682577c8fe6185865187f156\",\"type\":\"classic-checkout\",\"product_id\":9675064017182,\"variant_ids\":[49808198566174],\"use_multilanguage\":false,\"authorized_locales\":[\"en-US\"],\"default_locale\":\"en-US\",\"btn_label\":\"Book now\",\"book_now_default_label\":\"Book now\",\"book_now_labels\":null,\"slot_lock_remove_expired\":true,\"hide_variants_selector\":false},{\"product_handle\":\"court-dink\",\"service_id\":\"682956647f1906462ee104d6\",\"type\":\"classic-checkout\",\"product_id\":9675064017182,\"variant_ids\":[49812798308638],\"use_multilanguage\":false,\"authorized_locales\":[\"en-US\"],\"default_locale\":\"en-US\",\"btn_label\":\"Book now\",\"book_now_default_label\":\"Book now\",\"book_now_labels\":null,\"slot_lock_remove_expired\":true,\"hide_variants_selector\":false},{\"product_handle\":\"court-dink\",\"service_id\":\"683b365070093f0e11e7b8d6\",\"type\":\"classic-checkout\",\"product_id\":9675064017182,\"variant_ids\":[49839797960990],\"use_multilanguage\":false,\"authorized_locales\":[\"en-US\"],\"default_locale\":\"en-US\",\"btn_label\":\"Book now\",\"book_now_default_label\":\"Book now\",\"book_now_labels\":null,\"slot_lock_remove_expired\":true,\"hide_variants_selector\":false},{\"product_handle\":\"court-dink\",\"service_id\":\"683b368470093f0e11e7ba07\",\"type\":\"classic-checkout\",\"product_id\":9675064017182,\"variant_ids\":[49839797993758],\"use_multilanguage\":false,\"authorized_locales\":[\"en-US\"],\"default_locale\":\"en-US\",\"btn_label\":\"Book now\",\"book_now_default_label\":\"Book now\",\"book_now_labels\":null,\"slot_lock_remove_expired\":true,\"hide_variants_selector\":false},{\"product_handle\":\"book-court\",\"service_id\":\"683b387670093f0e11e7c27f\",\"type\":\"classic-checkout\",\"product_id\":9672731328798,\"variant_ids\":[49839806644510],\"use_multilanguage\":false,\"authorized_locales\":[\"en-US\"],\"default_locale\":\"en-US\",\"btn_label\":\"Book now\",\"book_now_default_label\":\"Book now\",\"book_now_labels\":null,\"slot_lock_remove_expired\":true,\"hide_variants_selector\":false},{\"product_handle\":\"book-court\",\"service_id\":\"683b38a470093f0e11e7c387\",\"type\":\"classic-checkout\",\"product_id\":9672731328798,\"variant_ids\":[49839806677278],\"use_multilanguage\":false,\"authorized_locales\":[\"en-US\"],\"default_locale\":\"en-US\",\"btn_label\":\"Book now\",\"book_now_default_label\":\"Book now\",\"book_now_labels\":null,\"slot_lock_remove_expired\":true,\"hide_variants_selector\":false},{\"product_handle\":\"book-court\",\"service_id\":\"6858e25f4edc08b9b3633c26\",\"type\":\"classic-checkout\",\"product_id\":9672731328798,\"variant_ids\":[49890116010270],\"use_multilanguage\":false,\"authorized_locales\":[\"en-US\"],\"default_locale\":\"en-US\",\"btn_label\":\"Book now\",\"book_now_default_label\":\"Book now\",\"book_now_labels\":null,\"slot_lock_remove_expired\":true,\"hide_variants_selector\":false},{\"product_handle\":\"court-dink\",\"service_id\":\"688659ab2799fce83cda6068\",\"type\":\"classic-checkout\",\"product_id\":9675064017182,\"variant_ids\":[49988386357534],\"use_multilanguage\":false,\"authorized_locales\":[\"en-US\"],\"default_locale\":\"en-US\",\"btn_label\":\"Book now\",\"book_now_default_label\":\"Book now\",\"book_now_labels\":null,\"slot_lock_remove_expired\":true,\"hide_variants_selector\":false},{\"product_handle\":\"book-court\",\"service_id\":\"68fefb2aa86077c9fa878f1a\",\"type\":\"classic-checkout\",\"product_id\":9672731328798,\"variant_ids\":[50271079923998],\"use_multilanguage\":false,\"authorized_locales\":[\"en-US\"],\"default_locale\":\"en-US\",\"btn_label\":\"Book now\",\"book_now_default_label\":\"Book now\",\"book_now_labels\":null,\"slot_lock_remove_expired\":true,\"hide_variants_selector\":false}]");
      
    
  
  const bigConfigString = chunks.join("");
  window.cowServices = bigConfigString ? JSON.parse(bigConfigString) : null;
  window.cowSettings = {"use_virtual_form":false,"hide_logs":false,"button_type":"complex","use_master_form":false,"multilanguage_source":"shop","widget_shadow_enabled":false,"widget_shadow_size":13,"widget_shadow_opacity":11,"widget_shadow_color":"#000000"};

