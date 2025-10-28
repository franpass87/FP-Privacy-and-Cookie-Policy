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
    console.warn( 'FP Privacy: FP_PRIVACY_DATA not found' );
    return;
}

// Auto-detect user language from browser
function detectUserLanguage() {
    // First, try to get from WordPress locale if available
    if ( data.options && data.options.state && data.options.state.lang ) {
        return data.options.state.lang;
    }
    
    // Try to detect from browser language
    var browserLang = navigator.language || navigator.userLanguage || 'en';
    
    // Check for Italian
    if ( browserLang.indexOf( 'it' ) === 0 ) {
        return 'it_IT';
    }
    
    // Check for English
    if ( browserLang.indexOf( 'en' ) === 0 ) {
        return 'en_US';
    }
    
    // Default to English
    return 'en_US';
}

// Override language detection if not set
if ( ! state.lang ) {
    state.lang = detectUserLanguage();
    debugTiming( 'Lingua utente rilevata: ' + state.lang );
}

// Update the language in the data object for consistency
if ( data.options && data.options.state ) {
    data.options.state.lang = state.lang;
}

// Force language detection for banner texts
if ( data.options && data.options.texts ) {
    // If we have Italian language, ensure we get Italian texts
    if ( state.lang === 'it_IT' || state.lang.indexOf( 'it' ) === 0 ) {
        debugTiming( 'Forzando testi italiani per lingua: ' + state.lang );
    }
    // If we have English language, ensure we get English texts
    else if ( state.lang === 'en_US' || state.lang.indexOf( 'en' ) === 0 ) {
        debugTiming( 'Forzando testi inglesi per lingua: ' + state.lang );
    }
}

// Override texts with language-specific versions if needed
if ( data.options && data.options.texts ) {
    var currentTexts = data.options.texts;
    
    // If we have Italian language, use Italian texts
    if ( state.lang === 'it_IT' || state.lang.indexOf( 'it' ) === 0 ) {
        if ( ! currentTexts.title || currentTexts.title.indexOf( 'privacy' ) === -1 ) {
            currentTexts.title = 'Rispettiamo la tua privacy';
            currentTexts.message = 'Utilizziamo i cookie per migliorare la tua esperienza. Puoi accettare tutti i cookie o gestire le tue preferenze.';
            currentTexts.btn_accept = 'Accetta tutti';
            currentTexts.btn_reject = 'Rifiuta tutti';
            currentTexts.btn_prefs = 'Gestisci preferenze';
            currentTexts.modal_title = 'Preferenze privacy';
            currentTexts.modal_close = 'Chiudi preferenze';
            currentTexts.modal_save = 'Salva preferenze';
            currentTexts.revision_notice = 'Abbiamo aggiornato la nostra policy. Rivedi le tue preferenze.';
            currentTexts.toggle_locked = 'Sempre attivo';
            currentTexts.toggle_enabled = 'Abilitato';
            currentTexts.link_privacy_policy = 'Informativa sulla Privacy';
            currentTexts.link_cookie_policy = 'Cookie Policy';
            debugTiming( 'Testi italiani applicati' );
        }
    }
    // If we have English language, use English texts
    else if ( state.lang === 'en_US' || state.lang.indexOf( 'en' ) === 0 ) {
        if ( ! currentTexts.title || currentTexts.title.indexOf( 'privacy' ) === -1 ) {
            currentTexts.title = 'We respect your privacy';
            currentTexts.message = 'We use cookies to improve your experience. You can accept all cookies or manage your preferences.';
            currentTexts.btn_accept = 'Accept all';
            currentTexts.btn_reject = 'Reject all';
            currentTexts.btn_prefs = 'Manage preferences';
            currentTexts.modal_title = 'Privacy preferences';
            currentTexts.modal_close = 'Close preferences';
            currentTexts.modal_save = 'Save preferences';
            currentTexts.revision_notice = 'We have updated our policy. Please review your preferences.';
            currentTexts.toggle_locked = 'Always active';
            currentTexts.toggle_enabled = 'Enabled';
            currentTexts.link_privacy_policy = 'Privacy Policy';
            currentTexts.link_cookie_policy = 'Cookie Policy';
            debugTiming( 'Testi inglesi applicati' );
        }
    }
}

// Update the texts object reference
if ( data.options && data.options.texts ) {
    texts = data.options.texts;
}

// Debug function for timing issues
function debugTiming( message ) {
    if ( typeof console !== 'undefined' && console.log ) {
        console.log( 'FP Privacy Debug: ' + message + ' (readyState: ' + document.readyState + ')' );
    }
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
                // Wait for banner to be initialized before opening modal
                var checkBanner = function() {
                    if ( banner ) {
                        openModal();
                    } else {
                        setTimeout( checkBanner, 10 );
                    }
                };
                checkBanner();
                return;
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

// Improved DOM ready handling with retry logic
function ensureDOMReady() {
    debugTiming( 'Starting DOM ready check' );
    
    if ( document.readyState === 'loading' ) {
        debugTiming( 'DOM still loading, waiting for DOMContentLoaded' );
        document.addEventListener( 'DOMContentLoaded', function () {
            debugTiming( 'DOMContentLoaded fired' );
            restoreBlockedNodes( state.categories || {} );
            startPlaceholderObserver();
            initializeBanner();
        } );
    } else {
        debugTiming( 'DOM already ready, initializing with timeout' );
        // DOM is already ready, but ensure elements exist
        setTimeout(function() {
            debugTiming( 'Timeout executed, initializing banner' );
            restoreBlockedNodes( state.categories || {} );
            startPlaceholderObserver();
            initializeBanner();
        }, 0);
    }
}

ensureDOMReady();

function initializeBanner() {
    debugTiming( 'initializeBanner called' );
    
    if ( banner ) {
        debugTiming( 'Banner already exists, returning' );
        return;
    }

    // Ensure root element exists before building banner
    var root = document.querySelector( '[data-fp-privacy-banner]' );
    if ( ! root ) {
        root = document.getElementById( 'fp-privacy-banner-root' );
    }
    
    if ( ! root ) {
        debugTiming( 'Root element not found, retrying in 50ms' );
        // Retry after a short delay if root element is not found
        setTimeout( initializeBanner, 50 );
        return;
    }

    debugTiming( 'Root element found, building banner' );
    buildBanner();
    refreshExternalOpeners();
    updateOpenersExpanded( false );

    // Verifica se c'è un cookie di consenso esistente
    var existingConsentId = readConsentIdFromCookie();
    if ( existingConsentId && ! state.consent_id ) {
        state.consent_id = existingConsentId;
        debugTiming( 'Consent ID recuperato dal cookie: ' + existingConsentId );
        
        // Verifica se la revisione è aggiornata
        if ( state.last_revision && state.last_revision >= state.revision ) {
            debugTiming( 'Revisione aggiornata, nascondendo banner' );
            state.should_display = false;
        }
    }
    
    // CORREZIONE RAFFORZATA: Migliora la logica di controllo del consenso
    // Se abbiamo un consent ID valido, controlla più accuratamente se il consenso è già stato dato
    if ( state.consent_id ) {
        debugTiming( 'Consent ID presente: ' + state.consent_id );
        
        // Controlla se abbiamo categorie salvate (indica che il consenso è stato dato)
        var hasSavedCategories = state.categories && Object.keys( state.categories ).length > 0;
        
        // Controlla se la revisione è aggiornata
        var isRevisionUpToDate = state.last_revision && state.last_revision >= state.revision;
        
        // FIX CRITICO: Se abbiamo un consent ID valido, assumiamo che il consenso sia stato dato
        // a meno che non ci sia stata una nuova revisione
        if ( hasSavedCategories || isRevisionUpToDate || ( state.consent_id && ! state.revision ) ) {
            debugTiming( 'Consenso già dato (categorie: ' + hasSavedCategories + ', revisione: ' + isRevisionUpToDate + '), nascondendo banner' );
            state.should_display = false;
            
            // CORREZIONE AGGIUNTIVA: Ripristina le categorie salvate immediatamente
            if ( hasSavedCategories ) {
                debugTiming( 'Ripristino categorie salvate' );
                restoreBlockedNodes( state.categories );
            }
        }
    } else {
        // Se non abbiamo un consent ID, il banner DEVE essere mostrato
        debugTiming( 'Nessun consent ID trovato, banner verrà mostrato' );
        state.should_display = true;
    }

    if ( state.should_display || forceDisplay ) {
        debugTiming( 'Showing banner' );
        showBanner();
    } else {
        debugTiming( 'Hiding banner' );
        hideBanner();
    }
    
    // Se il consenso è già stato dato, ripristina i nodi bloccati
    if ( ! state.should_display && state.categories ) {
        debugTiming( 'Ripristinando nodi bloccati con consenso esistente' );
        restoreBlockedNodes( state.categories );
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

// Add policy links
var policyUrls = data.options.policy_urls || {};

// Debug: Log the policy URLs to help identify the issue
if ( typeof console !== 'undefined' && console.log ) {
    console.log( 'FP Privacy Debug - Policy URLs:', policyUrls );
}

if ( policyUrls.privacy || policyUrls.cookie ) {
    var linksWrapper = document.createElement( 'div' );
    linksWrapper.className = 'fp-privacy-banner-links';
    
    if ( policyUrls.privacy ) {
        var privacyLink = document.createElement( 'a' );
        privacyLink.href = policyUrls.privacy;
        privacyLink.className = 'fp-privacy-link';
        privacyLink.setAttribute( 'target', '_blank' );
        privacyLink.rel = 'noopener noreferrer';
        privacyLink.textContent = texts.link_privacy_policy || '';
        linksWrapper.appendChild( privacyLink );
    }
    
    if ( policyUrls.cookie ) {
        var cookieLink = document.createElement( 'a' );
        cookieLink.href = policyUrls.cookie;
        cookieLink.className = 'fp-privacy-link';
        cookieLink.setAttribute( 'target', '_blank' );
        cookieLink.rel = 'noopener noreferrer';
        cookieLink.textContent = texts.link_cookie_policy || '';
        linksWrapper.appendChild( cookieLink );
    }
    
    banner.appendChild( linksWrapper );
}

var buttons = document.createElement( 'div' );
buttons.className = 'fp-privacy-banner-buttons';

var accept = createButton( texts.btn_accept, 'fp-privacy-button fp-privacy-button-primary' );
accept.addEventListener( 'click', function ( event ) {
    debugTiming( 'Accept button clicked' );
    event.preventDefault();
    event.stopPropagation();
    handleAcceptAll();
});
buttons.appendChild( accept );

var reject = createButton( texts.btn_reject, 'fp-privacy-button fp-privacy-button-primary' );
reject.addEventListener( 'click', function ( event ) {
    debugTiming( 'Reject button clicked' );
    event.preventDefault();
    event.stopPropagation();
    handleRejectAll();
});
buttons.appendChild( reject );

var prefs = createButton( texts.btn_prefs, 'fp-privacy-button fp-privacy-button-secondary' );
preferencesButton = prefs;
prefs.setAttribute( 'aria-expanded', 'false' );
prefs.setAttribute( 'aria-haspopup', 'dialog' );
prefs.setAttribute( 'data-fp-privacy-open', 'true' );
prefs.addEventListener( 'click', function ( event ) {
    debugTiming( 'Preferences button clicked' );
    event.preventDefault();
    event.stopPropagation();
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
close.addEventListener( 'click', function( event ) {
    event.preventDefault();
    event.stopPropagation();
    closeModal();
});
modal.appendChild( close );

var heading = document.createElement( 'h2' );
heading.id = 'fp-privacy-modal-title';
    heading.textContent = texts.modal_title || texts.btn_prefs || '';
modal.appendChild( heading );
modal.setAttribute( 'aria-labelledby', heading.id );

    // Add policy links
    var policyUrls = data.options.policy_urls || {};
    
    // Debug: Log the policy URLs in modal
    if ( typeof console !== 'undefined' && console.log ) {
        console.log( 'FP Privacy Debug - Modal Policy URLs:', policyUrls );
    }
    
    if ( policyUrls.privacy || policyUrls.cookie ) {
        var linksWrapper = document.createElement( 'div' );
        linksWrapper.className = 'fp-privacy-modal-links';
        
        if ( policyUrls.privacy ) {
            var privacyLink = document.createElement( 'a' );
            privacyLink.href = policyUrls.privacy;
            privacyLink.className = 'fp-privacy-link';
            privacyLink.setAttribute( 'target', '_blank' );
            privacyLink.rel = 'noopener noreferrer';
            privacyLink.textContent = texts.link_privacy_policy || '';
            linksWrapper.appendChild( privacyLink );
        }
        
        if ( policyUrls.cookie ) {
            var cookieLink = document.createElement( 'a' );
            cookieLink.href = policyUrls.cookie;
            cookieLink.className = 'fp-privacy-link';
            cookieLink.setAttribute( 'target', '_blank' );
            cookieLink.rel = 'noopener noreferrer';
            cookieLink.textContent = texts.link_cookie_policy || '';
            linksWrapper.appendChild( cookieLink );
        }
        
        modal.appendChild( linksWrapper );
    }

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
        toggleText.textContent = cat.locked ? (texts.toggle_locked || '') : (texts.toggle_enabled || '');
toggle.appendChild( toggleText );

wrapper.appendChild( toggle );
modal.appendChild( wrapper );
}

var actions = document.createElement( 'div' );
actions.className = 'fp-privacy-modal-actions';

    var saveLabel = texts.modal_save || texts.btn_prefs || '';
    var save = createButton( saveLabel, 'fp-privacy-button fp-privacy-button-primary' );
save.addEventListener( 'click', function( event ) {
    event.preventDefault();
    event.stopPropagation();
    handleSavePreferences();
});
actions.appendChild( save );

var acceptAll = createButton( texts.btn_accept, 'fp-privacy-button fp-privacy-button-secondary' );
acceptAll.addEventListener( 'click', function ( event ) {
    event.preventDefault();
    event.stopPropagation();
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
        event.preventDefault();
        event.stopPropagation();
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
        event.stopPropagation();
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
    debugTiming( 'setButtonsLoading called with isLoading: ' + isLoading );
    var buttons = document.querySelectorAll( '.fp-privacy-button' );
    debugTiming( 'Found ' + buttons.length + ' buttons to update' );
    
    for ( var i = 0; i < buttons.length; i++ ) {
        if ( isLoading ) {
            debugTiming( 'Setting button ' + i + ' to loading state' );
            buttons[ i ].classList.add( 'fp-loading' );
            buttons[ i ].disabled = true;
        } else {
            debugTiming( 'Removing loading state from button ' + i );
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
    debugTiming( 'handleAcceptAll called' );
    setButtonsLoading( true );
    
    // TIMEOUT DI SICUREZZA: Forza la chiusura dopo 500ms
    // Garantisce che il banner si chiuda SEMPRE, anche in caso di errori
    var safetyTimeout = setTimeout( function() {
        debugTiming( 'Safety timeout triggered - forcing banner close' );
        if ( banner && banner.style.display !== 'none' ) {
            banner.style.display = 'none';
            if ( modal && modalOverlay ) {
                modalOverlay.style.display = 'none';
            }
        }
    }, 500 );
    
    try {
        var payload = buildConsentPayload( true, false );
        debugTiming( 'Payload built, calling persistConsent' );
        
        // FIX CRITICO: Salva il cookie IMMEDIATAMENTE in locale
        var consentId = ensureConsentId();
        setConsentCookie( consentId, state.revision );
        
        // FIX CRITICO: Nascondi il banner IMMEDIATAMENTE
        // Non aspettare la risposta del server
        state.categories = Object.assign( {}, payload );
        state.last_revision = state.revision;
        state.should_display = false;
        hideBanner();
        restoreBlockedNodes( state.categories );
        updateReopenVisibility();
        
        // Invia al server in background (non bloccante)
        persistConsent( 'accept_all', payload );
        
        // Cancella il timeout di sicurezza se tutto va bene
        clearTimeout( safetyTimeout );
    } catch ( error ) {
        debugTiming( 'Error in handleAcceptAll: ' + error.message );
        // Il timeout di sicurezza chiuderà comunque il banner
    }
}

function handleRejectAll() {
    debugTiming( 'handleRejectAll called' );
    setButtonsLoading( true );
    
    // TIMEOUT DI SICUREZZA: Forza la chiusura dopo 500ms
    var safetyTimeout = setTimeout( function() {
        debugTiming( 'Safety timeout triggered - forcing banner close' );
        if ( banner && banner.style.display !== 'none' ) {
            banner.style.display = 'none';
            if ( modal && modalOverlay ) {
                modalOverlay.style.display = 'none';
            }
        }
    }, 500 );
    
    try {
        var payload = buildConsentPayload( false, true );
        debugTiming( 'Payload built, calling persistConsent' );
        
        // FIX CRITICO: Salva il cookie IMMEDIATAMENTE in locale
        var consentId = ensureConsentId();
        setConsentCookie( consentId, state.revision );
        
        // FIX CRITICO: Nascondi il banner IMMEDIATAMENTE
        state.categories = Object.assign( {}, payload );
        state.last_revision = state.revision;
        state.should_display = false;
        hideBanner();
        restoreBlockedNodes( state.categories );
        updateReopenVisibility();
        
        // Invia al server in background (non bloccante)
        persistConsent( 'reject_all', payload );
        
        clearTimeout( safetyTimeout );
    } catch ( error ) {
        debugTiming( 'Error in handleRejectAll: ' + error.message );
        // Il timeout di sicurezza chiuderà comunque il banner
    }
}

function handleSavePreferences() {
    setButtonsLoading( true );
    
    // TIMEOUT DI SICUREZZA: Forza la chiusura dopo 500ms
    var safetyTimeout = setTimeout( function() {
        debugTiming( 'Safety timeout triggered - forcing modal/banner close' );
        if ( modalOverlay && modalOverlay.style.display !== 'none' ) {
            modalOverlay.style.display = 'none';
        }
        if ( banner && banner.style.display !== 'none' ) {
            banner.style.display = 'none';
        }
    }, 500 );
    
    try {
        var payload = buildConsentPayload( false, false );
        
        // FIX CRITICO: Salva il cookie IMMEDIATAMENTE in locale
        var consentId = ensureConsentId();
        setConsentCookie( consentId, state.revision );
        
        // FIX CRITICO: Nascondi il banner IMMEDIATAMENTE
        state.categories = Object.assign( {}, payload );
        state.last_revision = state.revision;
        state.should_display = false;
        closeModal();
        hideBanner();
        restoreBlockedNodes( state.categories );
        updateReopenVisibility();
        
        // Invia al server in background (non bloccante)
        persistConsent( 'consent', payload );
        
        clearTimeout( safetyTimeout );
    } catch ( error ) {
        debugTiming( 'Error in handleSavePreferences: ' + error.message );
        // Il timeout di sicurezza chiuderà comunque il banner
    }
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
        debugTiming( 'markSuccess called' );
        setButtonsLoading( false );
        
        // Aggiorna il consent_id dal server se disponibile
        if ( typeof handleConsentResponse === 'function' ) {
            handleConsentResponse( result );
        } else if ( result && result.consent_id ) {
            state.consent_id = result.consent_id;
            // Aggiorna il cookie con l'ID dal server
            setConsentCookie( state.consent_id, state.revision );
        }

        if ( ! state.consent_id ) {
            state.consent_id = consentId;
        }

        state.last_event = timestamp;
        
        // NOTA: Banner già nascosto e categorie già salvate in handleAcceptAll/handleRejectAll
        // Questo è solo per confermare che il server ha ricevuto il consenso
        debugTiming( 'Server consent sync completed successfully' );
    };

    var handleFailure = function () {
        debugTiming( 'handleFailure called - Server sync failed but local consent is saved' );
        setButtonsLoading( false );
        
        // FIX CRITICO: Non mostrare nuovamente il banner
        // Il consenso è già salvato in locale (cookie + localStorage)
        // L'utente ha già dato il consenso, non dobbiamo chiederlo di nuovo
        debugTiming( 'Local consent preserved despite server error' );
        
        // Il banner resta nascosto, il consenso è valido in locale
    };

    if ( state.preview_mode || ! rest.url ) {
        debugTiming( 'Preview mode or no REST URL, using local success' );
        markSuccess( { consent_id: consentId } );
        return;
    }
    
    // If no nonce is available, try without it (for same-origin requests)
    if ( ! rest.nonce ) {
        debugTiming( 'No nonce available, attempting request without nonce' );
    }

    var requestBody = JSON.stringify( {
        event: event,
        states: payload,
        lang: lang,
        consent_id: consentId,
    } );

    var sendConsentRequest = function ( retry ) {
        debugTiming( 'sendConsentRequest called with retry: ' + retry + ', nonce: ' + rest.nonce );
        
        // Prepare headers
        var headers = {
            'Content-Type': 'application/json',
        };
        
        // Only add nonce if available
        if ( rest.nonce ) {
            headers['X-WP-Nonce'] = rest.nonce;
        }
        
        if ( typeof window.fetch === 'function' ) {
            return window.fetch( rest.url, {
                method: 'POST',
                headers: headers,
                credentials: 'same-origin',
                body: requestBody,
            } ).then( function ( response ) {
                debugTiming( 'Fetch response status: ' + response.status );
                if ( response && response.ok ) {
                    return response.json().catch( function () {
                        return { consent_id: consentId };
                    } );
                }

                if ( ! retry && response && response.status === 403 ) {
                    debugTiming( 'Received 403, attempting to refresh nonce' );
                    return response
                        .json()
                        .then( function ( payload ) {
                            debugTiming( '403 response payload: ' + JSON.stringify( payload ) );
                            var nextNonce = payload && payload.data ? payload.data.refresh_nonce : undefined;

                            if ( nextNonce ) {
                                debugTiming( 'Got new nonce, retrying request' );
                                rest.nonce = nextNonce;
                                return sendConsentRequest( true );
                            }

                            debugTiming( 'No refresh nonce available, failing' );
                            throw new Error( 'consent_request_failed' );
                        } )
                        .catch( function ( error ) {
                            debugTiming( 'Error parsing 403 response: ' + error.message );
                            throw new Error( 'consent_request_failed' );
                        } );
                }

                debugTiming( 'Fetch request failed with status: ' + response.status );
                throw new Error( 'consent_request_failed' );
            } );
        }

        return new Promise( function ( resolve, reject ) {
            debugTiming( 'Using XMLHttpRequest fallback with nonce: ' + rest.nonce );
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
                    debugTiming( 'XHR request successful with status: ' + xhr.status );
                    var result = { consent_id: consentId };

                    try {
                        var parsed = JSON.parse( xhr.responseText );
                        if ( parsed && typeof parsed === 'object' ) {
                            result = parsed;
                        }
                        debugTiming( 'XHR parsed response: ' + JSON.stringify( result ) );
                    } catch ( error ) {
                        debugTiming( 'XHR JSON parse error: ' + error.message );
                        // Ignore malformed JSON responses.
                    }

                    resolve( result );
                    return;
                }

                if ( ! retry && xhr.status === 403 ) {
                    debugTiming( 'XHR received 403, attempting to refresh nonce' );
                    try {
                        var payload = JSON.parse( xhr.responseText );
                        debugTiming( 'XHR 403 response payload: ' + JSON.stringify( payload ) );
                        var refresh = payload && payload.data ? payload.data.refresh_nonce : undefined;

                        if ( refresh ) {
                            debugTiming( 'XHR got new nonce, retrying request' );
                            rest.nonce = refresh;
                            sendConsentRequest( true ).then( resolve ).catch( reject );
                            return;
                        }
                        debugTiming( 'XHR no refresh nonce available' );
                    } catch ( error ) {
                        debugTiming( 'XHR error parsing 403 response: ' + error.message );
                        // Ignore JSON parsing issues so the failure can bubble up.
                    }
                }

                debugTiming( 'XHR request failed with status: ' + xhr.status );
                reject( new Error( 'consent_request_failed' ) );
            };
            xhr.send( requestBody );
        } );
    };

    sendConsentRequest( false )
        .then( markSuccess )
        .catch( function ( error ) {
            debugTiming( 'sendConsentRequest failed: ' + error.message );
            handleFailure();
        } );
}

function readConsentIdFromCookie() {
    var cookieName = consentCookie.name || 'fp_consent_state_id';
    var name = cookieName + '=';
    var parts = document.cookie ? document.cookie.split( ';' ) : [];

    for ( var i = 0; i < parts.length; i++ ) {
        var cookie = parts[ i ].trim();
        if ( cookie.indexOf( name ) === 0 ) {
            var value = cookie.substring( name.length );
            var segments = value.split( '|' );
            debugTiming( 'Cookie trovato: ' + value + ', ID: ' + ( segments[ 0 ] || '' ) + ', Rev: ' + ( segments[ 1 ] || '0' ) );
            
            // CORREZIONE: Aggiorna anche la revisione se presente e assicurati che sia valida
            if ( segments[ 1 ] ) {
                var revision = parseInt( segments[ 1 ], 10 ) || 0;
                state.last_revision = revision;
                debugTiming( 'Revisione dal cookie: ' + revision );
            }
            
            // CORREZIONE: Verifica che l'ID del consenso sia valido (non vuoto)
            var consentId = segments[ 0 ] || '';
            if ( consentId && consentId.length > 0 ) {
                debugTiming( 'Consent ID valido trovato: ' + consentId );
                return consentId;
            } else {
                debugTiming( 'Consent ID vuoto o non valido nel cookie' );
                return '';
            }
        }
    }

    debugTiming( 'Nessun cookie di consenso trovato nei cookie del browser' );
    
    // FALLBACK: Prova a leggere da localStorage
    try {
        if ( window.localStorage ) {
            var storedValue = localStorage.getItem( cookieName );
            if ( storedValue ) {
                debugTiming( 'Consenso trovato in localStorage: ' + storedValue );
                var segments = storedValue.split( '|' );
                var consentId = segments[ 0 ] || '';
                if ( consentId && consentId.length > 0 ) {
                    // Aggiorna la revisione
                    if ( segments[ 1 ] ) {
                        var revision = parseInt( segments[ 1 ], 10 ) || 0;
                        state.last_revision = revision;
                    }
                    debugTiming( 'Consenso recuperato da localStorage: ' + consentId );
                    // Prova a ripristinare il cookie
                    setConsentCookie( consentId, state.revision );
                    return consentId;
                }
            }
        }
    } catch ( error ) {
        debugTiming( 'Errore lettura localStorage: ' + error.message );
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

function setConsentCookie( consentId, revision ) {
    if ( ! consentId ) {
        debugTiming( 'setConsentCookie: Consent ID vuoto, cookie non impostato' );
        return;
    }

    var cookieName = consentCookie.name || 'fp_consent_state_id';
    var days = consentCookie.duration || 180;
    var expires = new Date();
    expires.setTime( expires.getTime() + ( days * 24 * 60 * 60 * 1000 ) );
    
    var cookieValue = consentId + '|' + revision;
    var cookieString = cookieName + '=' + cookieValue + '; expires=' + expires.toUTCString() + '; path=/; SameSite=Lax';
    
    // CORREZIONE: Aggiungi secure se siamo su HTTPS
    if ( window.location.protocol === 'https:' ) {
        cookieString += '; Secure';
    }
    
    // CORREZIONE: Migliora la gestione del dominio
    var domain = window.location.hostname;
    if ( domain && domain !== 'localhost' && ! domain.match( /^\d+\.\d+\.\d+\.\d+$/ ) ) {
        // Per domini, usa solo il dominio principale
        var domainParts = domain.split( '.' );
        if ( domainParts.length > 1 ) {
            var mainDomain = '.' + domainParts.slice( -2 ).join( '.' );
            cookieString += '; domain=' + mainDomain;
        }
    }
    
    // CORREZIONE: Prova a impostare il cookie e verifica che sia stato impostato
    document.cookie = cookieString;
    
    // CORREZIONE AGGIUNTIVA: Salva anche in localStorage come backup
    try {
        if ( window.localStorage ) {
            localStorage.setItem( cookieName, cookieValue );
            debugTiming( 'Consenso salvato anche in localStorage: ' + cookieValue );
        }
    } catch ( error ) {
        debugTiming( 'Impossibile salvare in localStorage: ' + error.message );
    }
    
    // Verifica che il cookie sia stato impostato correttamente
    setTimeout( function() {
        var testCookie = readConsentIdFromCookie();
        if ( testCookie === consentId ) {
            debugTiming( 'Cookie verificato con successo: ' + consentId );
        } else {
            debugTiming( 'ERRORE: Cookie non impostato correttamente. Atteso: ' + consentId + ', Trovato: ' + testCookie );
            // FALLBACK: Se il cookie fallisce, usa localStorage
            if ( window.localStorage ) {
                debugTiming( 'Utilizzo localStorage come fallback' );
            }
        }
    }, 100 );
    
    debugTiming( 'Cookie impostato: ' + cookieString );
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

