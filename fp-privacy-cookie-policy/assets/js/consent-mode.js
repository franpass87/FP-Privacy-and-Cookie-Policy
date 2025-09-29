(function () {
if ( ! window.fpPrivacyConsent ) {
window.fpPrivacyConsent = {};
}

var consent = window.fpPrivacyConsent;

consent.defaults = function () {
return window.fpPrivacyConsentDefaults || {};
};

consent.update = function ( states ) {
if ( typeof window.dataLayer === 'undefined' ) {
window.dataLayer = [];
}

var defaults = consent.defaults();
var payload = {};

for ( var key in defaults ) {
if ( defaults.hasOwnProperty( key ) ) {
payload[ key ] = defaults[ key ];
}
}

for ( var consentKey in states ) {
if ( states.hasOwnProperty( consentKey ) ) {
payload[ consentKey ] = states[ consentKey ];
}
}

if ( typeof window.gtag === 'function' ) {
window.gtag( 'consent', 'update', payload );
}

return payload;
};
})();
