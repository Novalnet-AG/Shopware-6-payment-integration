(window.webpackJsonp=window.webpackJsonp||[]).push([["novalnet-payment"],{"9cgp":function(e,t,n){"use strict";(function(e){n.d(t,"a",(function(){return d}));var o=n("FGIj"),r=n("k8s9");function a(e){return(a="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function c(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}function i(e,t){return!t||"object"!==a(t)&&"function"!=typeof t?function(e){if(void 0===e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return e}(e):t}function l(e){return(l=Object.setPrototypeOf?Object.getPrototypeOf:function(e){return e.__proto__||Object.getPrototypeOf(e)})(e)}function u(e,t){return(u=Object.setPrototypeOf||function(e,t){return e.__proto__=t,e})(e,t)}var d=function(t){function n(){return function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,n),i(this,l(n).apply(this,arguments))}var o,a,d;return function(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function");e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,writable:!0,configurable:!0}}),t&&u(e,t)}(n,t),o=n,(a=[{key:"init",value:function(){var e=this;this.client=new r.a;var t=document.querySelectorAll(this.getSelectors().paymentRadioButton),n=document.querySelector(this.getSelectors().selectedPaymentId),o=document.querySelector(this.getSelectors().paypalId),a=document.querySelectorAll(this.getSelectors().radioInputs),c=document.querySelector(this.getSelectors().radioInputChecked);null!=c&&this.showComponents(c),this._createScript((function(){document.getElementById("novalnet-payment")})),null!=n&&void 0!==o&&n.value===o.value&&(document.getElementById("novalnetpaypal-payment").style.display="block"),a.forEach((function(t){t.addEventListener("click",(function(){e.showComponents(t)}))})),t.forEach((function(t){t.addEventListener("click",(function(){e.showPaymentForm(t)}))})),document.querySelectorAll("#confirmPaymentForm .remove_paypal_account_details").forEach((function(t){t.addEventListener("click",(function(){e.removeStoredCard(t)}))}))}},{key:"_createScript",value:function(e){var t="https://cdn.novalnet.de/js/v2/NovalnetUtility.js";if(0===document.querySelectorAll('script[src="'.concat(t,'"]')).length){var n=document.createElement("script");n.type="text/javascript",n.src=t,n.addEventListener("load",e.bind(this),!1),document.head.appendChild(n)}}},{key:"getSelectors",value:function(){return{paypalId:"#novalnetpaypalId",paymentRadioButton:'#confirmPaymentForm input[name="paymentMethodId"]',selectedPaymentId:"#confirmPaymentForm input[name=paymentMethodId]:checked",radioInputs:'#confirmPaymentForm input[type="radio"].novalnetpaypal-SavedPaymentMethods-tokenInput',radioInputChecked:'#confirmPaymentForm input[type="radio"].novalnetpaypal-SavedPaymentMethods-tokenInput:checked'}}},{key:"showPaymentForm",value:function(e){var t=document.querySelector(this.getSelectors().paypalId);void 0!==t&&""!==t.value&&e.value===t.value?document.getElementById("novalnetpaypal-payment").style.display="block":document.getElementById("novalnetpaypal-payment").style.display="none"}},{key:"removeStoredCard",value:function(t){var n=document.querySelector('input[name="novalnetpaypalFormData[paymentToken]"]:checked');void 0!==n&&""!==n&&1==confirm(document.getElementById("removeConfirmMessage").value)&&(this.client.post(e("#cardRemoveUrl").val(),JSON.stringify({token:n.value}),""),window.location.reload())}},{key:"showComponents",value:function(e){"new"!==e.value?document.getElementById("novalnetpaypal-payment-form").classList.add("nnhide"):document.getElementById("novalnetpaypal-payment-form").classList.remove("nnhide")}}])&&c(o.prototype,a),d&&c(o,d),n}(o.a)}).call(this,n("UoTJ"))},"ew+X":function(e,t,n){"use strict";(function(e){n.d(t,"a",(function(){return d}));var o=n("FGIj"),r=n("k8s9");function a(e){return(a="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function c(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}function i(e,t){return!t||"object"!==a(t)&&"function"!=typeof t?function(e){if(void 0===e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return e}(e):t}function l(e){return(l=Object.setPrototypeOf?Object.getPrototypeOf:function(e){return e.__proto__||Object.getPrototypeOf(e)})(e)}function u(e,t){return(u=Object.setPrototypeOf||function(e,t){return e.__proto__=t,e})(e,t)}var d=function(t){function n(){return function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,n),i(this,l(n).apply(this,arguments))}var o,a,d;return function(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function");e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,writable:!0,configurable:!0}}),t&&u(e,t)}(n,t),o=n,(a=[{key:"init",value:function(){var e=this;this.client=new r.a;var t=document.getElementById("novalnet-payment-name").value,n=document.querySelector(this.getSelectors(t).submitButton),o=document.querySelector(this.getSelectors(t).sepaId),a=document.querySelectorAll(this.getSelectors(t).radioInputs),c=document.querySelector(this.getSelectors(t).radioInputChecked),i=document.querySelector(this.getSelectors(t).selectedPaymentId),l=document.querySelectorAll(this.getSelectors(t).paymentRadioButton);null!=i&&void 0!==o&&i.value===o.value&&(null!=document.getElementById(t+"HideButton")&&1==document.getElementById(t+"HideButton").value&&this._disableSubmitButton(),document.getElementById(t+"-payment").style.display="block"),this._createScript((function(){document.getElementById("novalnet-payment")})),null!=c&&this.showComponents(c,t),n.addEventListener("click",(function(n){var r=document.querySelector(e.getSelectors(t).selectedPaymentId),a=document.querySelector(e.getSelectors(t).radioInputChecked),c=document.getElementById(t+"AccountData"),i=document.getElementById(t+"Dob"),l=JSON.parse(document.getElementById(t+"-payment").getAttribute("data-"+t+"-payment-config"));if(void 0!==o.value&&""!==o.value&&r.value===o.value&&(null==a||"new"==a.value))if(void 0===c||""===c.value)e.preventForm(c,t,l.text.invalidIban);else if("novalnetsepaguarantee"!==t||void 0!==i&&""!==i.value){if("novalnetsepaguarantee"===t&&void 0!==i&&""!==i.value){var u=e.validateAge(i.value);u<18&&null!=l.forceGuarantee&&1!=l.forceGuarantee?e.preventForm(i,t,l.text.dobInvalid):u<18&&void 0!==document.getElementById("novalnetsepaId")&&null!=l.forceGuarantee&&1==l.forceGuarantee&&(r.value=document.getElementById("novalnetsepaId").value,document.getElementById("doForceSepaPayment").value=1)}}else void 0!==document.getElementById("novalnetsepaId")&&null!=l.forceGuarantee&&1==l.forceGuarantee?(r.value=document.getElementById("novalnetsepaId").value,document.getElementById("doForceSepaPayment").value=1):e.preventForm(i,t,l.text.dobEmpty)})),a.forEach((function(n){n.addEventListener("click",(function(){e.showComponents(n,t)}))})),l.forEach((function(n){n.addEventListener("click",(function(){e.showPaymentForm(n,t)}))})),document.querySelectorAll("#confirmPaymentForm .remove_card_details").forEach((function(n){n.addEventListener("click",(function(){e.removeStoredCard(n,t)}))}))}},{key:"_createScript",value:function(e){var t="https://cdn.novalnet.de/js/v2/NovalnetUtility.js";if(0===document.querySelectorAll('script[src="'.concat(t,'"]')).length){var n=document.createElement("script");n.type="text/javascript",n.src=t,n.addEventListener("load",e.bind(this),!1),document.head.appendChild(n)}}},{key:"getSelectors",value:function(e){return{sepaId:"#"+e+"Id",paymentForm:"#confirmPaymentForm",iban:"#novalnetsepaAccountData",paymentRadioButton:'#confirmPaymentForm input[name="paymentMethodId"]',selectedPaymentId:"#confirmPaymentForm input[name=paymentMethodId]:checked",submitButton:'#confirmPaymentForm button[type="submit"]',radioInputs:'#confirmPaymentForm input[type="radio"].'+e+"-SavedPaymentMethods-tokenInput",radioInputChecked:'#confirmPaymentForm input[type="radio"].'+e+"-SavedPaymentMethods-tokenInput:checked"}}},{key:"showComponents",value:function(e,t){"new"!==e.value?document.getElementById(t+"-payment-form").classList.add("nnhide"):document.getElementById(t+"-payment-form").classList.remove("nnhide")}},{key:"validateAge",value:function(e){var t=new Date;if(void 0===e||""===e)return NaN;var n=e.split("."),o=t.getFullYear()-n[2],r=t.getMonth()-n[1];return((r+=1)<0||"0"==r&&t.getDate()<n[0])&&o--,o}},{key:"preventForm",value:function(e,t,n){e.style.borderColor="red",event.preventDefault(),event.stopImmediatePropagation();var o=document.getElementById(t+"-error-container"),r=o.querySelector(".alert-content");return r.innerHTML="",void 0!==n&&""!==n?(r.innerHTML=n,o.style.display="block",r.focus()):o.style.display="none",!1}},{key:"showPaymentForm",value:function(e,t){var n=document.querySelector(this.getSelectors(t).sepaId);void 0!==n.value&&""!==n.value&&e.value===n.value?document.getElementById(t+"-payment").style.display="block":document.getElementById(t+"-payment").style.display="none"}},{key:"removeStoredCard",value:function(t,n){var o=document.querySelector('input[name="'+n+'FormData[paymentToken]"]:checked');void 0!==o&&""!==o&&1==confirm(document.getElementById("removeConfirmMessage").value)&&(this.client.post(e("#cardRemoveUrl").val(),JSON.stringify({token:o.value}),""),window.location.reload())}},{key:"_disableSubmitButton",value:function(){var e=document.querySelector("#confirmOrderForm button");e&&e.setAttribute("disabled","disabled")}}])&&c(o.prototype,a),d&&c(o,d),n}(o.a)}).call(this,n("UoTJ"))},pzgC:function(e,t,n){"use strict";(function(e){n.d(t,"a",(function(){return d}));var o=n("FGIj"),r=n("k8s9");function a(e){return(a="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function c(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}function i(e,t){return!t||"object"!==a(t)&&"function"!=typeof t?function(e){if(void 0===e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return e}(e):t}function l(e){return(l=Object.setPrototypeOf?Object.getPrototypeOf:function(e){return e.__proto__||Object.getPrototypeOf(e)})(e)}function u(e,t){return(u=Object.setPrototypeOf||function(e,t){return e.__proto__=t,e})(e,t)}var d=function(t){function n(){return function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,n),i(this,l(n).apply(this,arguments))}var o,a,d;return function(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function");e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,writable:!0,configurable:!0}}),t&&u(e,t)}(n,t),o=n,(a=[{key:"init",value:function(){var e=this;this.client=new r.a;var t=document.querySelector(this.getSelectors().submitButton),n=document.querySelector(this.getSelectors().creditCardId),o=document.querySelectorAll(this.getSelectors().radioInputs),a=document.querySelector(this.getSelectors().radioInputChecked),c=document.querySelector(this.getSelectors().selectedPaymentId),i=document.querySelectorAll(this.getSelectors().paymentRadioButton);null!=c&&void 0!==n&&c.value===n.value&&(null!=document.getElementById("novalnetcreditcardHideButton")&&1==document.getElementById("novalnetcreditcardHideButton").value&&this._disableSubmitButton(),document.getElementById("novalnetcreditcard-payment").style.display="block"),this._createScript((function(){var t=JSON.parse(document.getElementById("novalnetcreditcard-payment").getAttribute("data-novalnetcreditcard-payment-config"));e.loadIframe(t)})),null!=a&&this.showComponents(a),t.addEventListener("click",(function(t){var o=document.querySelector(e.getSelectors().selectedPaymentId),r=document.querySelector(e.getSelectors().radioInputChecked);void 0===n||""===n.value||o.value!==n.value||null!=r&&"new"!=r.value||(t.preventDefault(),t.stopImmediatePropagation(),NovalnetUtility.getPanHash())})),o.forEach((function(t){t.addEventListener("click",(function(){e.showComponents(t)}))})),i.forEach((function(t){t.addEventListener("click",(function(){e.showPaymentForm(t)}))})),document.querySelectorAll("#confirmPaymentForm .remove_cc_card_details").forEach((function(t){t.addEventListener("click",(function(){e.removeStoredCard(t)}))}))}},{key:"_createScript",value:function(e){var t="https://cdn.novalnet.de/js/v2/NovalnetUtility.js";if(0===document.querySelectorAll('script[src="'.concat(t,'"]')).length){var n=document.createElement("script");n.type="text/javascript",n.src=t,n.addEventListener("load",e.bind(this),!1),document.head.appendChild(n)}}},{key:"loadIframe",value:function(t){var n=document.querySelector(this.getSelectors().paymentForm);NovalnetUtility.setClientKey(t.clientKey);var o={callback:{on_success:function(t){return document.getElementById("novalnetcreditcard-panhash").value=t.hash,document.getElementById("novalnetcreditcard-uniqueid").value=t.unique_id,document.getElementById("novalnetcreditcard-doRedirect").value=t.do_redirect,null!=t.card_exp_month&&null!=t.card_exp_year&&(document.getElementById("novalnetcreditcard-expiry-date").value=t.card_exp_month+"/"+t.card_exp_year),document.getElementById("novalnetcreditcard-masked-card-no").value=t.card_number,document.getElementById("novalnetcreditcard-card-type").value=t.card_type,e("#novalnetcreditcard-secure-data").val(JSON.stringify(t)),n.submit(),!0},on_error:function(e){var t=document.getElementById("novalnetcreditcard-error-container"),n=t.querySelector(".alert-content");return n.innerHTML="",void 0!==e.error_message&&""!==e.error_message?(n.innerHTML=e.error_message,t.style.display="block",n.focus()):t.style.display="none",!1},on_show_overlay:function(e){document.getElementById("novalnetCreditcardIframe").classList.add("novalnet-challenge-window-overlay")},on_hide_overlay:function(e){document.getElementById("novalnetCreditcardIframe").classList.remove("novalnet-challenge-window-overlay")}},iframe:t.iframe,customer:t.customer,transaction:t.transaction};NovalnetUtility.createCreditCardForm(o)}},{key:"getSelectors",value:function(){return{creditCardId:"#novalnetcreditcardId",paymentForm:"#confirmPaymentForm",panHash:"#novalnetcreditcard-panhash",paymentRadioButton:'#confirmPaymentForm input[name="paymentMethodId"]',selectedPaymentId:"#confirmPaymentForm input[name=paymentMethodId]:checked",submitButton:'#confirmPaymentForm button[type="submit"]',radioInputs:'#confirmPaymentForm input[type="radio"].novalnetcreditcard-SavedPaymentMethods-tokenInput',radioInputChecked:'#confirmPaymentForm input[type="radio"].novalnetcreditcard-SavedPaymentMethods-tokenInput:checked'}}},{key:"showComponents",value:function(e){"new"!==e.value?document.getElementById("novalnetcreditcard-payment-form").classList.add("nnhide"):(NovalnetUtility.setCreditCardFormHeight(),document.getElementById("novalnetcreditcard-payment-form").classList.remove("nnhide"))}},{key:"showPaymentForm",value:function(e){var t=document.querySelector(this.getSelectors().creditCardId);void 0!==t&&""!==t.value&&e.value===t.value?(NovalnetUtility.setCreditCardFormHeight(),document.getElementById("novalnetcreditcard-payment").style.display="block"):document.getElementById("novalnetcreditcard-payment").style.display="none"}},{key:"removeStoredCard",value:function(t){var n=document.querySelector('input[name="novalnetcreditcardFormData[paymentToken]"]:checked');void 0!==n&&""!==n&&1==confirm(document.getElementById("removeConfirmMessage").value)&&(this.client.post(e("#cardRemoveUrl").val(),JSON.stringify({token:n.value}),""),window.location.reload())}},{key:"_disableSubmitButton",value:function(){var e=document.querySelector("#confirmOrderForm button");e&&e.setAttribute("disabled","disabled")}}])&&c(o.prototype,a),d&&c(o,d),n}(o.a)}).call(this,n("UoTJ"))},qseZ:function(e,t,n){"use strict";n.r(t);var o=n("pzgC"),r=n("9cgp"),a=n("ew+X");function c(e){return(c="function"==typeof Symbol&&"symbol"==typeof Symbol.iterator?function(e){return typeof e}:function(e){return e&&"function"==typeof Symbol&&e.constructor===Symbol&&e!==Symbol.prototype?"symbol":typeof e})(e)}function i(e,t){for(var n=0;n<t.length;n++){var o=t[n];o.enumerable=o.enumerable||!1,o.configurable=!0,"value"in o&&(o.writable=!0),Object.defineProperty(e,o.key,o)}}function l(e,t){return!t||"object"!==c(t)&&"function"!=typeof t?function(e){if(void 0===e)throw new ReferenceError("this hasn't been initialised - super() hasn't been called");return e}(e):t}function u(e){return(u=Object.setPrototypeOf?Object.getPrototypeOf:function(e){return e.__proto__||Object.getPrototypeOf(e)})(e)}function d(e,t){return(d=Object.setPrototypeOf||function(e,t){return e.__proto__=t,e})(e,t)}var m=function(e){function t(){return function(e,t){if(!(e instanceof t))throw new TypeError("Cannot call a class as a function")}(this,t),l(this,u(t).apply(this,arguments))}var n,o,r;return function(e,t){if("function"!=typeof t&&null!==t)throw new TypeError("Super expression must either be null or a function");e.prototype=Object.create(t&&t.prototype,{constructor:{value:e,writable:!0,configurable:!0}}),t&&d(e,t)}(t,e),n=t,(o=[{key:"init",value:function(){var e=this,t=document.getElementById("novalnetinvoice-payment-name").value,n=document.querySelector(this.getSelectors().selectedPaymentId),o=document.querySelector(this.getSelectors().submitButton),r=document.querySelector(this.getSelectors().invoiceId),a=document.querySelectorAll(this.getSelectors().paymentRadioButton);this._createScript((function(){document.getElementById("novalnet-payment")})),null!=n&&void 0!==r&&n.value===r.value&&(document.getElementById("novalnetinvoiceguarantee-payment").style.display="block"),o.addEventListener("click",(function(n){var o=document.querySelector(e.getSelectors().selectedPaymentId),a=JSON.parse(document.getElementById("novalnetinvoiceguarantee-payment").getAttribute("data-novalnetinvoiceguarantee-payment-config")),c=document.getElementById("novalnetinvoiceguaranteeDob");if(void 0!==r.value&&""!==r.value&&o.value===r.value)if("novalnetinvoiceguarantee"!==t||void 0!==c&&""!==c.value){if("novalnetinvoiceguarantee"===t&&void 0!==c&&""!==c.value){var i=e.validateAge(c.value);i<18&&null!=a.forceGuarantee&&1!=a.forceGuarantee?e.preventForm(c,"novalnetinvoiceguarantee",a.text.dobInvalid):i<18&&void 0!==document.getElementById("novalnetinvoiceId")&&null!=a.forceGuarantee&&1==a.forceGuarantee&&(o.value=document.getElementById("novalnetinvoiceId").value,document.getElementById("doForceInvoicePayment").value=1)}}else void 0!==document.getElementById("novalnetinvoiceId")&&null!=a.forceGuarantee&&1==a.forceGuarantee?(o.value=document.getElementById("novalnetinvoiceId").value,document.getElementById("doForceInvoicePayment").value=1):e.preventForm(c,"novalnetinvoiceguarantee",a.text.dobEmpty)})),a.forEach((function(t){t.addEventListener("click",(function(){e.showPaymentForm(t)}))}))}},{key:"_createScript",value:function(e){var t="https://cdn.novalnet.de/js/v2/NovalnetUtility.js";if(0===document.querySelectorAll('script[src="'.concat(t,'"]')).length){var n=document.createElement("script");n.type="text/javascript",n.src=t,n.addEventListener("load",e.bind(this),!1),document.head.appendChild(n)}}},{key:"getSelectors",value:function(){return{invoiceId:"#novalnetinvoiceguaranteeId",selectedPaymentId:"#confirmPaymentForm input[name=paymentMethodId]:checked",submitButton:'#confirmPaymentForm button[type="submit"]',paymentRadioButton:'#confirmPaymentForm input[name="paymentMethodId"]'}}},{key:"validateAge",value:function(e){var t=new Date;if(void 0===e||""===e)return NaN;var n=e.split("."),o=t.getFullYear()-n[2],r=t.getMonth()-n[1];return((r+=1)<0||"0"==r&&t.getDate()<n[0])&&o--,o}},{key:"showPaymentForm",value:function(e){var t=document.querySelector(this.getSelectors().invoiceId);void 0!==t&&""!==t.value&&e.value===t.value?document.getElementById("novalnetinvoiceguarantee-payment").style.display="block":document.getElementById("novalnetinvoiceguarantee-payment").style.display="none"}},{key:"preventForm",value:function(e,t,n){e.style.borderColor="red",event.preventDefault(),event.stopImmediatePropagation();var o=document.getElementById(t+"-error-container"),r=o.querySelector(".alert-content");return r.innerHTML="",void 0!==n&&""!==n?(r.innerHTML=n,o.style.display="block",r.focus()):o.style.display="none",!1}}])&&i(n.prototype,o),r&&i(n,r),t}(n("FGIj").a),y=window.PluginManager;y.register("NovalnetCreditCardPayment",o.a,"[data-novalnetcreditcard-payment]"),y.register("NovalnetPaypalPayment",r.a,"[data-novalnetpaypal-payment]"),y.register("NovalnetSepaPayment",a.a,"[data-novalnetsepa-payment]"),y.register("NovalnetSepaPayment",a.a,"[data-novalnetsepaguarantee-payment]"),y.register("NovalnetInvoicePayment",m,"[data-novalnetinvoiceguarantee-payment]")}},[["qseZ","runtime","vendor-node","vendor-shared"]]]);