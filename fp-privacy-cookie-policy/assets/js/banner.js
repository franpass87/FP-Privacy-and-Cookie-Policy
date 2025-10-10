(function () {
'use strict';

// Observe dynamic placeholders so AJAX-injected embeds are restored when consent is granted.

function createCustomEvent( name, detail ) {
    if ( typeof window.CustomEvent === 'function' ) {
        return new CustomEvent( name, { detail: detail } );
    }

    if ( document && document.createEvent ) {
        try {
            var event = document.createEvent( 'CustomEvent' );
            event.initCustomEvent( name, false, false, detail );
            return event;
        } catch ( error ) {
            // Fall through to null.
        }
    }

    return null;
}

var data = window.FP_PRIVACY_DATA;
if ( ! data ) {
return;
}

var root = document.querySelector( '[data-fp-privacy-banner]' );
if ( ! root ) {
root = document.getElementById( 'fp-privacy-banner-root' );
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
var externalOpeners = [];
var externalListenerBound = false;
var placeholderObserver = null;
var observerTeardownBound = false;
var reopenButton = null;

function decodePlaceholder( value ) {
    if ( ! value ) {
        return '';
    }

    try {
        if ( typeof window.atob === 'function' ) {
            return window.atob( value );
        }
    } catch ( error ) {
        return '';
    }

    return '';
}

function parseBlockedOriginal( node ) {
    var encoded = node.getAttribute( 'data-fp-privacy-replace' );

    if ( ! encoded ) {
        return null;
    }

    var html = decodePlaceholder( encoded );

    if ( ! html ) {
        return null;
    }

    var template = document.createElement( 'template' );
    template.innerHTML = html.trim();

    return template.content.firstElementChild || null;
}

function cloneAttributes( source, target ) {
    if ( ! source || ! source.attributes ) {
        return;
    }

    for ( var i = 0; i < source.attributes.length; i++ ) {
        var attr = source.attributes[ i ];
        target.setAttribute( attr.name, attr.value );
    }
}

function shouldAllowCategory( category, payload ) {
    if ( ! category ) {
        return false;
    }

    if ( payload && Object.prototype.hasOwnProperty.call( payload, category ) ) {
        return payload[ category ] === true;
    }

    if ( categories && categories[ category ] && categories[ category ].locked ) {
        return true;
    }

    return false;
}

function activateBlockedNode( node ) {
    if ( ! node || node.getAttribute( 'data-fp-privacy-activated' ) === '1' ) {
        return;
    }

    var original = parseBlockedOriginal( node );
    var parent = node.parentNode;

    if ( ! parent ) {
        return;
    }

    if ( ! original ) {
        parent.removeChild( node );
        return;
    }

    if ( node.getAttribute( 'data-fp-privacy-live' ) === '1' ) {
        return;
    }

    var blockedType = node.getAttribute( 'data-fp-privacy-blocked' ) || '';
    var tag = original.tagName ? original.tagName.toLowerCase() : '';

    if ( blockedType === 'script' || tag === 'script' ) {
        var replacement = document.createElement( 'script' );
        cloneAttributes( original, replacement );
        replacement.text = original.text || original.textContent || '';
        parent.insertBefore( replacement, node );
        node.__fpPrivacyReplacement = replacement;
        node.setAttribute( 'data-fp-privacy-live', '1' );
        node.setAttribute( 'data-fp-privacy-activated', '1' );
        node.style.display = 'none';
        return;
    }

    if ( blockedType === 'style' || tag === 'link' || tag === 'style' ) {
        var styleReplacement = original.cloneNode( true );
        parent.insertBefore( styleReplacement, node );
        node.__fpPrivacyReplacement = styleReplacement;
        node.setAttribute( 'data-fp-privacy-live', '1' );
        node.setAttribute( 'data-fp-privacy-activated', '1' );
        node.style.display = 'none';
        return;
    }

    if ( blockedType === 'iframe' ) {
        if ( typeof node.__fpPrivacyPlaceholder === 'undefined' ) {
            node.__fpPrivacyPlaceholder = node.innerHTML;
        }

        if ( typeof node.__fpPrivacyOriginalClass === 'undefined' ) {
            node.__fpPrivacyOriginalClass = node.className;
        }

        node.innerHTML = '';
        node.classList.remove( 'fp-privacy-blocked' );
        var clone = original.cloneNode( true );
        node.appendChild( clone );
        node.__fpPrivacyReplacement = clone;
        node.setAttribute( 'data-fp-privacy-live', '1' );
        node.setAttribute( 'data-fp-privacy-activated', '1' );
        node.style.display = '';
        return;
    }

    var fallbackClone = original.cloneNode( true );
    parent.insertBefore( fallbackClone, node );
    node.__fpPrivacyReplacement = fallbackClone;
    node.setAttribute( 'data-fp-privacy-live', '1' );
    node.setAttribute( 'data-fp-privacy-activated', '1' );
    node.style.display = 'none';
}

function deactivateBlockedNode( node ) {
    if ( ! node ) {
        return;
    }

    var replacement = node.__fpPrivacyReplacement;

    if ( replacement && replacement.parentNode ) {
        replacement.parentNode.removeChild( replacement );
    }

    delete node.__fpPrivacyReplacement;

    var blockedType = node.getAttribute( 'data-fp-privacy-blocked' ) || '';

    if ( blockedType === 'iframe' ) {
        if ( typeof node.__fpPrivacyPlaceholder === 'string' ) {
            node.innerHTML = node.__fpPrivacyPlaceholder;
        }

        if ( typeof node.__fpPrivacyOriginalClass === 'string' ) {
            node.className = node.__fpPrivacyOriginalClass;
        }
    }

    node.removeAttribute( 'data-fp-privacy-live' );
    node.removeAttribute( 'data-fp-privacy-activated' );
    node.style.display = '';
}

function isBlockedPlaceholder( node ) {
    if ( ! node || typeof node.getAttribute !== 'function' ) {
        return false;
    }

    if ( node.hasAttribute( 'data-fp-privacy-blocked' ) ) {
        return true;
    }

    if ( node.querySelector && node.querySelector( '[data-fp-privacy-blocked]' ) ) {
        return true;
    }

    return false;
}

function stopPlaceholderObserver() {
    if ( placeholderObserver && typeof placeholderObserver.disconnect === 'function' ) {
        placeholderObserver.disconnect();
    }

    placeholderObserver = null;
}

function startPlaceholderObserver() {
    if ( placeholderObserver || typeof window.MutationObserver !== 'function' ) {
        return;
    }

    placeholderObserver = new window.MutationObserver( function ( mutations ) {
        var shouldRestore = false;

        for ( var i = 0; i < mutations.length; i++ ) {
            var mutation = mutations[ i ];

            if ( ! mutation || mutation.type !== 'childList' ) {
                continue;
            }

            if ( mutation.addedNodes ) {
                for ( var j = 0; j < mutation.addedNodes.length; j++ ) {
                    var added = mutation.addedNodes[ j ];

                    if ( isBlockedPlaceholder( added ) ) {
                        shouldRestore = true;
                        break;
                    }
                }
            }

            if ( shouldRestore ) {
                break;
            }
        }

        if ( shouldRestore ) {
            restoreBlockedNodes( state.categories || {} );
        }
    } );

    if ( document.body ) {
        placeholderObserver.observe( document.body, { childList: true, subtree: true } );
    }

    if ( ! observerTeardownBound ) {
        window.addEventListener( 'beforeunload', stopPlaceholderObserver );
        observerTeardownBound = true;
    }
}

function restoreBlockedNodes( payload ) {
    var states = payload || ( state.categories || {} );
    var nodes = document.querySelectorAll( '[data-fp-privacy-blocked]' );

    for ( var i = 0; i < nodes.length; i++ ) {
        var node = nodes[ i ];
        var category = node.getAttribute( 'data-fp-privacy-category' ) || '';
        var isActive = node.getAttribute( 'data-fp-privacy-live' ) === '1';

        if ( shouldAllowCategory( category, states ) ) {
            if ( ! isActive ) {
                activateBlockedNode( node );
            }
        } else if ( isActive ) {
            deactivateBlockedNode( node );
        }
    }

    refreshExternalOpeners();
}

function refreshExternalOpeners() {
    var nodes = document.querySelectorAll( '[data-fp-privacy-open]' );
    externalOpeners = [];

    for ( var i = 0; i < nodes.length; i++ ) {
        externalOpeners.push( nodes[ i ] );
        nodes[ i ].setAttribute( 'aria-expanded', 'false' );
    }
}

function updateOpenersExpanded( expanded ) {
    var value = expanded ? 'true' : 'false';

    if ( preferencesButton ) {
        preferencesButton.setAttribute( 'aria-expanded', value );
    }

    if ( reopenButton ) {
        reopenButton.setAttribute( 'aria-expanded', value );
    }

    for ( var i = 0; i < externalOpeners.length; i++ ) {
        externalOpeners[ i ].setAttribute( 'aria-expanded', value );
    }
}

function handleExternalOpeners( event ) {
    if ( event.defaultPrevented ) {
        return;
    }

    var target = event.target;

    while ( target && target !== document ) {
        if ( target.getAttribute && target.hasAttribute( 'data-fp-privacy-open' ) ) {
            event.preventDefault();
            refreshExternalOpeners();

            if ( ! banner ) {
                initializeBanner();
            }

            openModal();
            return;
        }

        target = target.parentNode;
    }
}

if ( ! externalListenerBound ) {
    document.addEventListener( 'click', handleExternalOpeners );
    externalListenerBound = true;
}

document.addEventListener( 'fp-consent-change', function ( event ) {
    if ( event && event.detail && event.detail.states ) {
        restoreBlockedNodes( event.detail.states );
    }
} );

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
var preferencesButton = null;

if ( document.readyState === 'loading' ) {
    document.addEventListener( 'DOMContentLoaded', function () {
        restoreBlockedNodes( state.categories || {} );
        startPlaceholderObserver();
    } );
    document.addEventListener( 'DOMContentLoaded', initializeBanner );
} else {
    restoreBlockedNodes( state.categories || {} );
    startPlaceholderObserver();
    initializeBanner();
}

function initializeBanner() {
    if ( banner ) {
        return;
    }

    buildBanner();
    refreshExternalOpeners();
    updateOpenersExpanded( false );

    if ( state.should_display || forceDisplay ) {
        showBanner();
    } else {
        hideBanner();
    }
}

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

var reject = createButton( texts.btn_reject, 'fp-privacy-button fp-privacy-button-primary' );
reject.addEventListener( 'click', function () {
handleRejectAll();
});
buttons.appendChild( reject );

var prefs = createButton( texts.btn_prefs, 'fp-privacy-button fp-privacy-button-secondary' );
preferencesButton = prefs;
prefs.setAttribute( 'aria-expanded', 'false' );
prefs.setAttribute( 'aria-haspopup', 'dialog' );
prefs.setAttribute( 'data-fp-privacy-open', 'true' );
prefs.addEventListener( 'click', function ( event ) {
event.preventDefault();
openModal();
} );
buttons.appendChild( prefs );

banner.appendChild( buttons );

root.appendChild( banner );
buildModal();
buildReopenButton();

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
modalOverlay.id = 'fp-privacy-modal-overlay';
modalOverlay.setAttribute( 'aria-hidden', 'true' );
modalOverlay.setAttribute( 'tabindex', '-1' );

modal = document.createElement( 'div' );
modal.className = 'fp-privacy-modal';
modal.id = 'fp-privacy-modal';
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
            checkbox.checked = false;
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
enableAllToggles();
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

    if ( preferencesButton ) {
        preferencesButton.setAttribute( 'aria-controls', modal.id );
    }

    if ( reopenButton ) {
        reopenButton.setAttribute( 'aria-controls', modal.id );
    }

updateRevisionNotice();
}

function buildReopenButton() {
    if ( reopenButton || state.preview_mode ) {
        return;
    }

    reopenButton = document.createElement( 'button' );
    reopenButton.type = 'button';
    reopenButton.className = 'fp-privacy-reopen';
    reopenButton.setAttribute( 'data-fp-privacy-open', 'true' );
    reopenButton.setAttribute( 'aria-haspopup', 'dialog' );

    var label = texts.btn_prefs || texts.modal_title || '';

    if ( label ) {
        reopenButton.setAttribute( 'aria-label', label );
        reopenButton.title = label;
    } else {
        reopenButton.setAttribute( 'aria-label', 'Cookie preferences' );
    }

    if ( modal && modal.id ) {
        reopenButton.setAttribute( 'aria-controls', modal.id );
    }

    var icon = document.createElement( 'span' );
    icon.className = 'fp-privacy-reopen-icon';
    icon.setAttribute( 'aria-hidden', 'true' );
    icon.textContent = '\u2699';
    reopenButton.appendChild( icon );

    reopenButton.addEventListener( 'click', function ( event ) {
        event.preventDefault();
        openModal();
    } );

    document.body.appendChild( reopenButton );
    updateReopenVisibility();
}

function updateReopenVisibility() {
    if ( ! reopenButton ) {
        return;
    }

    if ( state.preview_mode || forceDisplay ) {
        reopenButton.style.display = 'none';
        return;
    }

    reopenButton.style.display = state.should_display ? 'none' : 'flex';
}

function showBanner() {
banner.style.display = 'block';
state.should_display = true;
updateRevisionNotice();
    updateReopenVisibility();
}

function hideBanner() {
    if ( ! banner ) {
        return;
    }

    banner.style.display = 'none';
    state.should_display = false;
    updateRevisionNotice();
    updateReopenVisibility();
}

function openModal() {
lastFocusedElement = document.activeElement;
modalOverlay.style.display = 'flex';
modalOverlay.setAttribute( 'aria-hidden', 'false' );
    updateOpenersExpanded( true );

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
    updateOpenersExpanded( false );

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
    if ( window.fpPrivacyConsent && typeof window.fpPrivacyConsent.mapBannerPayload === 'function' ) {
        return window.fpPrivacyConsent.mapBannerPayload( payload, { defaults: consentDefaults } );
    }

    var result = {};
    for ( var key in consentDefaults ) {
        if ( Object.prototype.hasOwnProperty.call( consentDefaults, key ) ) {
            result[ key ] = consentDefaults[ key ];
        }
    }

    var marketingFallback = result.ad_storage === 'granted' || result.ad_user_data === 'granted' || result.ad_personalization === 'granted';
    var statisticsFallback = result.analytics_storage === 'granted';
    var functionalityFallback = result.functionality_storage === 'granted';
    var personalizationFallback = result.personalization_storage === 'granted';

    var marketing = Object.prototype.hasOwnProperty.call( payload, 'marketing' ) ? payload.marketing === true : marketingFallback;
    var statistics = Object.prototype.hasOwnProperty.call( payload, 'statistics' ) ? payload.statistics === true : statisticsFallback;
    var preferencesFallback = functionalityFallback || personalizationFallback;
    var preferences = Object.prototype.hasOwnProperty.call( payload, 'preferences' ) ? payload.preferences === true : preferencesFallback;
    var necessary = Object.prototype.hasOwnProperty.call( payload, 'necessary' ) ? payload.necessary === true : true;

    result.analytics_storage = statistics ? 'granted' : 'denied';
    result.ad_storage = marketing ? 'granted' : 'denied';
    result.ad_user_data = marketing ? 'granted' : 'denied';
    result.ad_personalization = marketing ? 'granted' : 'denied';
    result.functionality_storage = ( preferences || necessary ) ? 'granted' : 'denied';
    result.personalization_storage = preferences ? 'granted' : 'denied';
    result.security_storage = 'granted';

    return result;
}

function setButtonsLoading( isLoading ) {
    var buttons = document.querySelectorAll( '.fp-privacy-button' );
    for ( var i = 0; i < buttons.length; i++ ) {
        if ( isLoading ) {
            buttons[ i ].classList.add( 'fp-loading' );
            buttons[ i ].disabled = true;
        } else {
            buttons[ i ].classList.remove( 'fp-loading' );
            buttons[ i ].disabled = false;
        }
    }
}

function enableAllToggles() {
var checkboxes = modal.querySelectorAll( 'input[type="checkbox"][data-category]' );
for ( var i = 0; i < checkboxes.length; i++ ) {
if ( ! checkboxes[ i ].disabled ) {
checkboxes[ i ].checked = true;
}
}
}

function handleAcceptAll() {
setButtonsLoading( true );
var payload = buildConsentPayload( true, false );
persistConsent( 'accept_all', payload );
}

function handleRejectAll() {
setButtonsLoading( true );
var payload = buildConsentPayload( false, true );
persistConsent( 'reject_all', payload );
}

function handleSavePreferences() {
setButtonsLoading( true );
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

    var consentEvent = createCustomEvent( 'fp-consent-change', {
        consent: consentMode,
        event: event,
        revision: state.revision,
        timestamp: timestamp,
        consentId: consentId,
        states: payload,
    } );

    if ( consentEvent ) {
        document.dispatchEvent( consentEvent );
    }

    var lang = state.lang || ( data.options.state ? data.options.state.lang : '' ) || document.documentElement.lang || 'en';

    var markSuccess = function ( result ) {
        setButtonsLoading( false );
        
        if ( typeof handleConsentResponse === 'function' ) {
            handleConsentResponse( result );
        } else if ( result && result.consent_id ) {
            state.consent_id = result.consent_id;
        }

        if ( ! state.consent_id ) {
            state.consent_id = consentId;
        }

        state.last_event = timestamp;
        state.categories = Object.assign( {}, payload );
        state.last_revision = state.revision;
        state.should_display = false;
        updateRevisionNotice();
        hideBanner();
        restoreBlockedNodes( state.categories );
        updateReopenVisibility();
    };

    var handleFailure = function () {
        setButtonsLoading( false );
        state.should_display = true;
        showBanner();
    };

    if ( state.preview_mode || ! rest.url ) {
        markSuccess( { consent_id: consentId } );
        return;
    }

    var requestBody = JSON.stringify( {
        event: event,
        states: payload,
        lang: lang,
        consent_id: consentId,
    } );

    var sendConsentRequest = function ( retry ) {
        if ( typeof window.fetch === 'function' ) {
            return window.fetch( rest.url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-WP-Nonce': rest.nonce,
                },
                credentials: 'same-origin',
                body: requestBody,
            } ).then( function ( response ) {
                if ( response && response.ok ) {
                    return response.json().catch( function () {
                        return { consent_id: consentId };
                    } );
                }

                if ( ! retry && response && response.status === 403 ) {
                    return response
                        .json()
                        .then( function ( payload ) {
                            var nextNonce = payload && payload.data ? payload.data.refresh_nonce : undefined;

                            if ( nextNonce ) {
                                rest.nonce = nextNonce;
                                return sendConsentRequest( true );
                            }

                            throw new Error( 'consent_request_failed' );
                        } )
                        .catch( function () {
                            throw new Error( 'consent_request_failed' );
                        } );
                }

                throw new Error( 'consent_request_failed' );
            } );
        }

        return new Promise( function ( resolve, reject ) {
            var xhr = new XMLHttpRequest();
            xhr.open( 'POST', rest.url, true );
            xhr.withCredentials = true;
            xhr.setRequestHeader( 'Content-Type', 'application/json' );
            if ( rest.nonce ) {
                xhr.setRequestHeader( 'X-WP-Nonce', rest.nonce );
            }
            xhr.onreadystatechange = function () {
                if ( xhr.readyState !== 4 ) {
                    return;
                }

                if ( xhr.status >= 200 && xhr.status < 300 ) {
                    var result = { consent_id: consentId };

                    try {
                        var parsed = JSON.parse( xhr.responseText );
                        if ( parsed && typeof parsed === 'object' ) {
                            result = parsed;
                        }
                    } catch ( error ) {
                        // Ignore malformed JSON responses.
                    }

                    resolve( result );
                    return;
                }

                if ( ! retry && xhr.status === 403 ) {
                    try {
                        var payload = JSON.parse( xhr.responseText );
                        var refresh = payload && payload.data ? payload.data.refresh_nonce : undefined;

                        if ( refresh ) {
                            rest.nonce = refresh;
                            sendConsentRequest( true ).then( resolve ).catch( reject );
                            return;
                        }
                    } catch ( error ) {
                        // Ignore JSON parsing issues so the failure can bubble up.
                    }
                }

                reject( new Error( 'consent_request_failed' ) );
            };
            xhr.send( requestBody );
        } );
    };

    sendConsentRequest( false )
        .then( markSuccess )
        .catch( function () {
            handleFailure();
        } );
}

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

function renderCookieDebug() {
    var panel = document.createElement( 'div' );
    panel.className = 'fp-privacy-cookie-debug';
    var label = texts.debug_label || '';
    var strong = document.createElement( 'strong' );
    strong.textContent = label;
    panel.appendChild( strong );
    panel.appendChild( document.createTextNode( ' ' + document.cookie ) );
    banner.appendChild( panel );
}
function handleConsentResponse( result ) {
    if ( result && result.consent_id ) {
        state.consent_id = result.consent_id;
    }
}

})();

