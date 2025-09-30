(function () {
'use strict';

var data = window.FP_PRIVACY_DATA;
if ( ! data ) {
return;
}

var root = document.getElementById( 'fp-privacy-banner-root' );
if ( ! root ) {
var shortcodeRoot = document.querySelector( '[data-fp-privacy-banner]' );
if ( shortcodeRoot ) {
root = shortcodeRoot;
}
}

if ( ! root ) {
return;
}

var state = data.options.state || {};
var categories = data.options.categories || {};
var layout = data.options.layout || {};
var texts = data.options.texts || {};
var rest = data.rest || {};
var consentDefaults = data.options.mode || {};
var consentCookie = data.cookie || {};
var focusableSelector = 'a[href], button:not([disabled]), input:not([disabled]), textarea:not([disabled]), select:not([disabled]), [tabindex]:not([tabindex="-1"])';
var lastFocusedElement = null;

var dataset = root.dataset || {};
var forceDisplay = false;

if ( dataset.layoutType === 'bar' || dataset.layoutType === 'floating' ) {
layout.type = dataset.layoutType;
}

if ( dataset.layoutPosition === 'top' || dataset.layoutPosition === 'bottom' ) {
layout.position = dataset.layoutPosition;
}

if ( dataset.lang ) {
state.lang = dataset.lang;
}

if ( dataset.forceDisplay === '1' || dataset.forceDisplay === 'true' ) {
forceDisplay = true;
}

var modalOverlay;
var modal;
var banner;
var revisionNotice;

document.addEventListener( 'DOMContentLoaded', function () {
buildBanner();
if ( state.should_display || forceDisplay ) {
showBanner();
}
});

function buildBanner() {
banner = document.createElement( 'div' );
banner.className = 'fp-privacy-banner';
if ( layout.type === 'bar' ) {
banner.style.width = '100%';
banner.style.left = '0';
banner.style.transform = 'none';
banner.style.maxWidth = '100%';
banner.style.borderRadius = '0';
banner.style[ layout.position === 'top' ? 'top' : 'bottom' ] = '0';
} else {
banner.style.bottom = layout.position === 'top' ? '' : '24px';
banner.style.top = layout.position === 'top' ? '24px' : '';
}

var title = document.createElement( 'h2' );
title.textContent = texts.title || '';
banner.appendChild( title );

var message = document.createElement( 'p' );
message.innerHTML = texts.message || '';
banner.appendChild( message );

revisionNotice = document.createElement( 'div' );
revisionNotice.className = 'fp-privacy-revision-notice';
revisionNotice.style.display = 'none';
if ( texts.revision_notice ) {
    revisionNotice.textContent = texts.revision_notice;
}
banner.appendChild( revisionNotice );

if ( texts.link_policy ) {
var link = document.createElement( 'a' );
link.href = texts.link_policy;
link.className = 'fp-privacy-link';
link.setAttribute( 'target', '_blank' );
link.rel = 'noopener noreferrer';
link.textContent = texts.link_policy;
banner.appendChild( link );
}

var buttons = document.createElement( 'div' );
buttons.className = 'fp-privacy-banner-buttons';

var accept = createButton( texts.btn_accept, 'fp-privacy-button fp-privacy-button-primary' );
accept.addEventListener( 'click', function () {
handleAcceptAll();
});
buttons.appendChild( accept );

var reject = createButton( texts.btn_reject, 'fp-privacy-button fp-privacy-button-secondary' );
reject.addEventListener( 'click', function () {
handleRejectAll();
});
buttons.appendChild( reject );

var prefs = createButton( texts.btn_prefs, 'fp-privacy-button fp-privacy-button-secondary' );
prefs.addEventListener( 'click', function () {
openModal();
});
buttons.appendChild( prefs );

banner.appendChild( buttons );

root.appendChild( banner );
buildModal();

if ( state.preview_mode ) {
renderCookieDebug();
}
}

function createButton( label, className ) {
var btn = document.createElement( 'button' );
btn.type = 'button';
btn.className = className;
btn.textContent = label || '';
return btn;
}

function buildModal() {
modalOverlay = document.createElement( 'div' );
modalOverlay.className = 'fp-privacy-modal-overlay';
modalOverlay.setAttribute( 'aria-hidden', 'true' );
modalOverlay.setAttribute( 'tabindex', '-1' );

modal = document.createElement( 'div' );
modal.className = 'fp-privacy-modal';
modal.setAttribute( 'role', 'dialog' );
modal.setAttribute( 'aria-modal', 'true' );

var close = document.createElement( 'button' );
close.type = 'button';
close.className = 'close';
    close.setAttribute( 'aria-label', texts.modal_close || texts.btn_prefs || '' );
close.innerHTML = '&times;';
close.addEventListener( 'click', closeModal );
modal.appendChild( close );

var heading = document.createElement( 'h2' );
heading.id = 'fp-privacy-modal-title';
    heading.textContent = texts.modal_title || texts.btn_prefs || '';
modal.appendChild( heading );
modal.setAttribute( 'aria-labelledby', heading.id );

    var savedCategories = state.categories || {};

    for ( var key in categories ) {
        if ( ! categories.hasOwnProperty( key ) ) {
            continue;
        }
        var cat = categories[ key ];
        var wrapper = document.createElement( 'div' );
        wrapper.className = 'fp-privacy-category';

        var title = document.createElement( 'h3' );
        title.textContent = cat.label || key;
        wrapper.appendChild( title );

        var desc = document.createElement( 'p' );
        desc.innerHTML = cat.description || '';
        wrapper.appendChild( desc );

        var toggle = document.createElement( 'label' );
        toggle.className = 'fp-privacy-switch';

        var checkbox = document.createElement( 'input' );
        checkbox.type = 'checkbox';
        checkbox.value = key;
        checkbox.name = 'fp_privacy_category_' + key;
        var saved = Object.prototype.hasOwnProperty.call( savedCategories, key ) ? savedCategories[ key ] : null;
        if ( cat.locked ) {
            checkbox.checked = true;
            checkbox.disabled = true;
        } else if ( saved !== null ) {
            checkbox.checked = !! saved;
        } else {
            checkbox.checked = true;
        }
        if ( ! cat.locked ) {
            checkbox.disabled = false;
        }
        checkbox.dataset.category = key;

toggle.appendChild( checkbox );

var toggleText = document.createElement( 'span' );
        toggleText.textContent = cat.locked ? texts.toggle_locked || '' : texts.toggle_enabled || '';
toggle.appendChild( toggleText );

wrapper.appendChild( toggle );
modal.appendChild( wrapper );
}

var actions = document.createElement( 'div' );
actions.className = 'fp-privacy-modal-actions';

    var saveLabel = texts.modal_save || texts.btn_prefs || '';
    var save = createButton( saveLabel, 'fp-privacy-button fp-privacy-button-primary' );
save.addEventListener( 'click', handleSavePreferences );
actions.appendChild( save );

var acceptAll = createButton( texts.btn_accept, 'fp-privacy-button fp-privacy-button-secondary' );
acceptAll.addEventListener( 'click', function () {
handleAcceptAll();
closeModal();
});
actions.appendChild( acceptAll );

modal.appendChild( actions );
modalOverlay.appendChild( modal );
document.body.appendChild( modalOverlay );

modalOverlay.addEventListener( 'click', function ( event ) {
if ( event.target === modalOverlay ) {
closeModal();
}
});

document.addEventListener( 'keydown', handleModalKeydown );

updateRevisionNotice();
}

function showBanner() {
banner.style.display = 'block';
state.should_display = true;
updateRevisionNotice();
}

function hideBanner() {
banner.style.display = 'none';
state.should_display = false;
updateRevisionNotice();
}

function openModal() {
lastFocusedElement = document.activeElement;
modalOverlay.style.display = 'flex';
modalOverlay.setAttribute( 'aria-hidden', 'false' );

var focusable = modalOverlay.querySelectorAll( focusableSelector );
if ( focusable.length ) {
focusable[0].focus();
} else {
modalOverlay.focus();
}
}

function closeModal() {
modalOverlay.style.display = 'none';
modalOverlay.setAttribute( 'aria-hidden', 'true' );

if ( lastFocusedElement && lastFocusedElement.focus ) {
lastFocusedElement.focus();
}
}

function buildConsentPayload( grantAll, denyAll ) {
var payload = {};
for ( var key in categories ) {
if ( ! categories.hasOwnProperty( key ) ) {
continue;
}
var cat = categories[ key ];
if ( cat.locked ) {
payload[ key ] = true;
continue;
}

if ( grantAll ) {
payload[ key ] = true;
} else if ( denyAll ) {
payload[ key ] = false;
} else {
var input = modal.querySelector( 'input[data-category="' + key + '"]' );
payload[ key ] = input ? input.checked : false;
}
}

return payload;
}

function mapToConsentMode( payload ) {
var result = {};
for ( var key in consentDefaults ) {
if ( consentDefaults.hasOwnProperty( key ) ) {
result[ key ] = consentDefaults[ key ];
}
}

var marketing = payload.marketing === true;
var statistics = payload.statistics === true;
var preferences = payload.preferences === true;

if ( statistics ) {
result.analytics_storage = 'granted';
} else {
result.analytics_storage = 'denied';
}

if ( marketing ) {
result.ad_storage = 'granted';
result.ad_user_data = 'granted';
result.ad_personalization = 'granted';
} else {
result.ad_storage = 'denied';
result.ad_user_data = 'denied';
result.ad_personalization = 'denied';
}

result.functionality_storage = preferences || payload.necessary ? 'granted' : 'denied';
result.security_storage = 'granted';

return result;
}

function handleAcceptAll() {
var payload = buildConsentPayload( true, false );
persistConsent( 'accept_all', payload );
}

function handleRejectAll() {
var payload = buildConsentPayload( false, true );
persistConsent( 'reject_all', payload );
}

function handleSavePreferences() {
var payload = buildConsentPayload( false, false );
persistConsent( 'consent', payload );
closeModal();
}

function persistConsent( event, payload ) {
    var consentMode = mapToConsentMode( payload );
    if ( window.fpPrivacyConsent ) {
        window.fpPrivacyConsent.update( consentMode );
    }

    if ( typeof window.dataLayer === 'undefined' ) {
        window.dataLayer = [];
    }

    var timestamp = Date.now();
    var consentId = ensureConsentId();

    window.dataLayer.push( {
        event: 'fp_consent_update',
        consent: consentMode,
        rev: state.revision,
        ts: timestamp,
        timestamp: timestamp,
        consentId: consentId,
    } );

    document.dispatchEvent(
        new CustomEvent( 'fp-consent-change', {
            detail: {
                consent: consentMode,
                event: event,
                revision: state.revision,
                timestamp: timestamp,
                consentId: consentId,
            },
        } )
    );

    state.consent_id = consentId;
    state.last_event = timestamp;
    state.categories = Object.assign( {}, payload );
    state.last_revision = state.revision;
    state.should_display = false;
    updateRevisionNotice();

    var lang = state.lang || ( data.options.state ? data.options.state.lang : '' ) || document.documentElement.lang || 'en';

if ( ! state.preview_mode && rest.url ) {
window.fetch( rest.url, {
method: 'POST',
headers: {
'Content-Type': 'application/json',
'X-WP-Nonce': rest.nonce,
},
credentials: 'same-origin',
body: JSON.stringify( {
event: event,
states: payload,
lang: lang,
consent_id: consentId,
} ),
} )
    .then( function ( response ) {
        if ( response && response.ok ) {
            return response.json();
        }

        return null;
    } )
    .then( function ( result ) {
        if ( result && result.consent_id ) {
            state.consent_id = result.consent_id;
        }
    } )
    .catch( function () {} );
}

hideBanner();

function readConsentIdFromCookie() {
    var name = ( consentCookie.name || 'fp_consent_state_id' ) + '=';
    var parts = document.cookie ? document.cookie.split( ';' ) : [];

    for ( var i = 0; i < parts.length; i++ ) {
        var cookie = parts[ i ].trim();
        if ( cookie.indexOf( name ) === 0 ) {
            var value = cookie.substring( name.length );
            var segments = value.split( '|' );
            return segments[ 0 ] || '';
        }
    }

    return '';
}

function ensureConsentId() {
    if ( state.consent_id ) {
        return state.consent_id;
    }

    var existing = readConsentIdFromCookie();
    if ( existing ) {
        state.consent_id = existing;
        return existing;
    }

    var generated = generateConsentId();
    state.consent_id = generated;

    return generated;
}

function generateConsentId() {
    if ( window.crypto && window.crypto.getRandomValues ) {
        var bytes = new Uint8Array( 16 );
        window.crypto.getRandomValues( bytes );
        var output = '';

        for ( var i = 0; i < bytes.length; i++ ) {
            var hex = bytes[ i ].toString( 16 );
            output += hex.length === 1 ? '0' + hex : hex;
        }

        return output;
    }

    return 'fpconsent' + Math.random().toString( 36 ).slice( 2 ) + Date.now().toString( 36 );
}

function updateRevisionNotice() {
    if ( ! revisionNotice ) {
        return;
    }

    if ( state.should_display && state.last_revision && state.last_revision < state.revision && texts.revision_notice ) {
        revisionNotice.textContent = texts.revision_notice;
        revisionNotice.style.display = 'block';
    } else {
        revisionNotice.style.display = 'none';
    }
}

function handleModalKeydown( event ) {
    if ( modalOverlay.style.display !== 'flex' ) {
        return;
    }

    if ( event.key === 'Escape' ) {
        event.preventDefault();
        closeModal();
        return;
    }

    if ( event.key !== 'Tab' ) {
        return;
    }

    var focusable = modalOverlay.querySelectorAll( focusableSelector );
    if ( ! focusable.length ) {
        return;
    }

    var first = focusable[0];
    var last = focusable[ focusable.length - 1 ];
    var active = document.activeElement;

    if ( event.shiftKey ) {
        if ( active === first || active === modalOverlay ) {
            event.preventDefault();
            last.focus();
        }
    } else if ( active === last ) {
        event.preventDefault();
        first.focus();
    }
}

hideBanner();
}

function renderCookieDebug() {
var panel = document.createElement( 'div' );
panel.className = 'fp-privacy-cookie-debug';
    var label = texts.debug_label || '';
    panel.innerHTML = '<strong>' + label + '</strong> ' + document.cookie;
banner.appendChild( panel );
}
})();
