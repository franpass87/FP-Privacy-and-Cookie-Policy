(function () {
    'use strict';

    if (typeof window === 'undefined') {
        return;
    }

    var settings = window.fpPrivacySettings || {};
    var cookieName = settings.cookieName || 'fp_consent_state';
    var consentId = settings.consentId || '';
    var categories = settings.categories || {};
    var googleDefaults = settings.googleDefaults || {};
    var cookieTtlDays = parseInt(settings.cookieTtlDays, 10);
    if (isNaN(cookieTtlDays)) {
        cookieTtlDays = null;
    }
    var requiredKeys = Object.keys(categories).filter(function (key) {
        return categories[key] && categories[key].required;
    });
    var enabledKeys = Object.keys(categories).filter(function (key) {
        return categories[key] && (categories[key].enabled || categories[key].required);
    });
    var bannerElement;
    var modalElement;
    var manageButton;
    var currentConsent = null;

    document.addEventListener('DOMContentLoaded', initialize);

    function initialize() {
        bannerElement = document.querySelector('.fp-consent-banner');
        modalElement = document.querySelector('.fp-consent-modal');
        manageButton = document.querySelector('[data-consent-manage]');

        if (settings.language) {
            document.documentElement.setAttribute('data-fp-consent-lang', settings.language);
        }

        ensureGtag();
        if (googleDefaults && Object.keys(googleDefaults).length) {
            window.gtag('consent', 'default', googleDefaults);
        }

        currentConsent = getStoredConsent();

        if (currentConsent) {
            applyConsentToInterface(currentConsent);
            var mapped = updateGoogleConsent(currentConsent, true);
            pushDataLayer(currentConsent, mapped);
            hideBanner();
            toggleManageButton(true);
        } else {
            currentConsent = buildDefaultConsent();
            showBanner();
            toggleManageButton(false);
        }

        bindActions();
    }

    function bindActions() {
        document.addEventListener('click', function (event) {
            var target = event.target.closest('[data-consent-action]');
            if (!target) {
                return;
            }

            var action = target.getAttribute('data-consent-action');

            switch (action) {
                case 'accept-all':
                    event.preventDefault();
                    handleAcceptAll();
                    break;
                case 'reject-all':
                    event.preventDefault();
                    handleRejectAll();
                    break;
                case 'open-preferences':
                    event.preventDefault();
                    openModal();
                    break;
                case 'save-preferences':
                    event.preventDefault();
                    handleSavePreferences();
                    break;
                case 'close':
                    event.preventDefault();
                    closeModal();
                    break;
                default:
                    break;
            }
        });
    }

    function handleAcceptAll() {
        var state = {};
        enabledKeys.forEach(function (key) {
            state[key] = true;
        });
        requiredKeys.forEach(function (key) {
            state[key] = true;
        });
        saveConsent(state, 'accept_all');
    }

    function handleRejectAll() {
        var state = buildDefaultConsent();
        saveConsent(state, 'reject_all');
    }

    function handleSavePreferences() {
        if (!modalElement) {
            return;
        }

        var toggles = modalElement.querySelectorAll('[data-category-toggle]');
        var state = buildDefaultConsent();

        toggles.forEach(function (toggle) {
            var key = toggle.getAttribute('data-category-toggle');
            if (!key) {
                return;
            }
            state[key] = toggle.checked;
        });

        requiredKeys.forEach(function (key) {
            state[key] = true;
        });

        saveConsent(state, 'save_preferences');
    }

    function buildDefaultConsent() {
        var defaults = {};
        Object.keys(categories).forEach(function (key) {
            var cat = categories[key];
            defaults[key] = !!(cat && cat.required);
        });
        return defaults;
    }

    function getStoredConsent() {
        try {
            var cookie = readCookie(cookieName);
            if (!cookie) {
                return null;
            }
            var parsed = JSON.parse(cookie);
            if (parsed && typeof parsed === 'object') {
                return parsed;
            }
        } catch (error) {
            console.warn('[FP Privacy] Unable to parse consent cookie', error);
        }
        return null;
    }

    function saveConsent(state, eventType) {
        if (!state) {
            return;
        }

        requiredKeys.forEach(function (key) {
            state[key] = true;
        });

        currentConsent = state;
        applyConsentToInterface(state);
        storeConsentCookie(state);
        updateGoogleConsent(state, false);
        hideBanner();
        closeModal();
        dispatchConsentEvent(state, eventType);
        sendConsentToServer(state, eventType);
        toggleManageButton(true);
    }

    function applyConsentToInterface(state) {
        if (!modalElement) {
            modalElement = document.querySelector('.fp-consent-modal');
        }

        if (!modalElement) {
            return;
        }

        var toggles = modalElement.querySelectorAll('[data-category-toggle]');
        toggles.forEach(function (toggle) {
            var key = toggle.getAttribute('data-category-toggle');
            if (!key || requiredKeys.indexOf(key) !== -1) {
                toggle.checked = true;
                return;
            }
            toggle.checked = !!state[key];
        });
    }

    function updateGoogleConsent(state, silent) {
        var mapped = mapStateToGoogle(state);

        if (typeof window.gtag === 'function') {
            window.gtag('consent', 'update', mapped);
        }

        if (!silent) {
            pushDataLayer(state, mapped);
        }

        return mapped;
    }

    function mapStateToGoogle(state) {
        var consent = state || {};
        var analyticsGranted = !!consent.statistics;
        var marketingGranted = !!consent.marketing;
        var preferencesGranted = !!consent.preferences;
        var necessaryGranted = !!consent.necessary;

        var mapped = Object.assign({}, googleDefaults);
        mapped.analytics_storage = analyticsGranted ? 'granted' : 'denied';
        mapped.ad_storage = marketingGranted ? 'granted' : 'denied';
        mapped.ad_user_data = marketingGranted ? 'granted' : 'denied';
        mapped.ad_personalization = marketingGranted ? 'granted' : 'denied';
        mapped.functionality_storage = (preferencesGranted || necessaryGranted) ? 'granted' : 'denied';
        mapped.security_storage = necessaryGranted ? 'granted' : 'denied';

        return mapped;
    }

    function pushDataLayer(state, googleState) {
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({
            event: 'fp_consent_update',
            consent_id: consentId,
            consent_state: Object.assign({}, state),
            google_consent: Object.assign({}, googleState || {}),
            consent_language: settings.language || '',
            consent_timestamp: new Date().toISOString()
        });
    }

    function sendConsentToServer(state, eventType) {
        if (!settings.ajaxUrl || !settings.nonce) {
            return;
        }

        var params = new URLSearchParams();
        params.append('action', 'fp_save_consent');
        params.append('nonce', settings.nonce);
        params.append('consentId', consentId);
        params.append('event', eventType);

        Object.keys(state).forEach(function (key) {
            params.append('consent[' + key + ']', state[key] ? '1' : '0');
        });

        fetch(settings.ajaxUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
            },
            body: params.toString()
        }).catch(function (error) {
            console.warn('[FP Privacy] Unable to log consent', error);
        });
    }

    function storeConsentCookie(state) {
        var value = encodeURIComponent(JSON.stringify(state));
        var attributes = ['path=/', 'SameSite=Lax'];
        var maxAgeSeconds = null;

        if (cookieTtlDays === 0) {
            maxAgeSeconds = null;
        } else if (cookieTtlDays && cookieTtlDays > 0) {
            maxAgeSeconds = cookieTtlDays * 24 * 60 * 60;
        } else {
            maxAgeSeconds = 365 * 24 * 60 * 60;
        }

        if (maxAgeSeconds) {
            attributes.push('max-age=' + maxAgeSeconds);
            var expires = new Date();
            expires.setTime(expires.getTime() + maxAgeSeconds * 1000);
            attributes.push('expires=' + expires.toUTCString());
        }

        if (window.location && window.location.protocol === 'https:') {
            attributes.push('Secure');
        }
        document.cookie = cookieName + '=' + value + ';' + attributes.join(';');
    }

    function readCookie(name) {
        var nameEQ = name + '=';
        var ca = document.cookie ? document.cookie.split(';') : [];
        for (var i = 0; i < ca.length; i++) {
            var c = ca[i].trim();
            if (c.indexOf(nameEQ) === 0) {
                return decodeURIComponent(c.substring(nameEQ.length, c.length));
            }
        }
        return null;
    }

    function openModal() {
        if (!modalElement) {
            modalElement = document.querySelector('.fp-consent-modal');
        }
        if (!modalElement) {
            return;
        }
        applyConsentToInterface(currentConsent || buildDefaultConsent());
        modalElement.hidden = false;
        modalElement.classList.add('is-visible');
        document.body.classList.add('fp-consent-modal-open');
        if (manageButton) {
            manageButton.setAttribute('aria-expanded', 'true');
        }
    }

    function closeModal() {
        if (!modalElement) {
            return;
        }
        modalElement.hidden = true;
        modalElement.classList.remove('is-visible');
        document.body.classList.remove('fp-consent-modal-open');
        if (manageButton) {
            manageButton.setAttribute('aria-expanded', 'false');
        }
    }

    function showBanner() {
        if (!bannerElement) {
            bannerElement = document.querySelector('.fp-consent-banner');
        }
        if (bannerElement) {
            bannerElement.classList.add('is-visible');
        }
        toggleManageButton(false);
    }

    function hideBanner() {
        if (!bannerElement) {
            bannerElement = document.querySelector('.fp-consent-banner');
        }
        if (bannerElement) {
            bannerElement.classList.remove('is-visible');
        }
        if (currentConsent) {
            toggleManageButton(true);
        }
    }

    function dispatchConsentEvent(state, eventType) {
        var detail = {
            consentId: consentId,
            state: Object.assign({}, state),
            eventType: eventType
        };
        var event;
        try {
            event = new CustomEvent('fp-consent-change', { detail: detail });
        } catch (e) {
            event = document.createEvent('CustomEvent');
            event.initCustomEvent('fp-consent-change', true, true, detail);
        }
        document.dispatchEvent(event);
    }

    function ensureGtag() {
        window.dataLayer = window.dataLayer || [];
        if (typeof window.gtag !== 'function') {
            window.gtag = function () {
                window.dataLayer.push(arguments);
            };
        }
    }

    function toggleManageButton(visible) {
        if (!manageButton) {
            return;
        }

        if (visible) {
            manageButton.hidden = false;
            manageButton.classList.add('is-visible');
            manageButton.setAttribute('aria-hidden', 'false');
            manageButton.setAttribute('aria-expanded', 'false');
        } else {
            manageButton.classList.remove('is-visible');
            manageButton.hidden = true;
            manageButton.setAttribute('aria-hidden', 'true');
            manageButton.setAttribute('aria-expanded', 'false');
        }
    }
})();
