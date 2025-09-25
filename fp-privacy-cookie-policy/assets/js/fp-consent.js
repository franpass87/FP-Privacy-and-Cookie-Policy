(function () {
    'use strict';

    if (typeof window === 'undefined') {
        return;
    }

    var settings = window.fpPrivacySettings || {};

    function logWarning() {
        if (typeof console === 'undefined' || !console || typeof console.warn !== 'function') {
            return;
        }

        try {
            console.warn.apply(console, arguments);
        } catch (error) {
            console.warn(error);
        }
    }
    var cookieName = settings.cookieName || 'fp_consent_state';
    var consentId = settings.consentId || '';
    var categories = settings.categories || {};
    var googleDefaults = settings.googleDefaults || {};
    var cookieTtlDays = parseInt(settings.cookieTtlDays, 10);
    var cookieOptions = settings.cookieOptions || {};
    var texts = settings.texts || {};
    var statusLabelText = texts.updatedAt || '';
    var consentMetadata = {
        updatedAt: null
    };
    var statusElement;
    var statusValueElement;
    var preferredLocales = [];

    function addPreferredLocale(locale) {
        if (!locale || typeof locale !== 'string') {
            return;
        }

        var normalized = locale.trim().replace(/_/g, '-');

        if (!normalized) {
            return;
        }

        if (preferredLocales.indexOf(normalized) === -1) {
            preferredLocales.push(normalized);
        }
    }

    if (settings.language) {
        addPreferredLocale(settings.language);
    }

    if (typeof navigator !== 'undefined') {
        if (Array.isArray(navigator.languages)) {
            navigator.languages.forEach(function (locale) {
                addPreferredLocale(locale);
            });
        }

        if (navigator.language) {
            addPreferredLocale(navigator.language);
        }
    }

    if (typeof document !== 'undefined' && document.documentElement) {
        var documentLang = document.documentElement.getAttribute('lang') || document.documentElement.lang;
        if (documentLang) {
            addPreferredLocale(documentLang);
        }
    }

    addPreferredLocale('en-GB');
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
    var isInitialized = false;

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        setTimeout(initialize, 0);
    }

    function initialize() {
        if (isInitialized) {
            return;
        }

        isInitialized = true;
        bannerElement = document.querySelector('.fp-consent-banner');
        modalElement = document.querySelector('.fp-consent-modal');
        manageButton = document.querySelector('[data-consent-manage]');
        statusElement = document.querySelector('[data-consent-status]');
        statusValueElement = statusElement ? statusElement.querySelector('[data-consent-updated]') : null;

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

        updateUpdatedAtUI();
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
        consentMetadata.updatedAt = null;
        try {
            var cookie = readCookie(cookieName);
            if (!cookie) {
                return null;
            }
            var parsed = JSON.parse(cookie);
            if (parsed && typeof parsed === 'object') {
                if (parsed.__fpTimestamp) {
                    consentMetadata.updatedAt = parsed.__fpTimestamp;
                    delete parsed.__fpTimestamp;
                }
                return parsed;
            }
        } catch (error) {
            logWarning('[FP Privacy] Unable to parse consent cookie', error);
        }
        return null;
    }

    function saveConsent(state, eventType) {
        if (!state) {
            return;
        }

        var timestamp = new Date().toISOString();
        requiredKeys.forEach(function (key) {
            state[key] = true;
        });

        consentMetadata.updatedAt = timestamp;
        currentConsent = state;
        applyConsentToInterface(state);
        storeConsentCookie(state, timestamp);
        updateGoogleConsent(state, false);
        hideBanner();
        closeModal();
        dispatchConsentEvent(state, eventType);
        sendConsentToServer(state, eventType);
        toggleManageButton(true);
        updateUpdatedAtUI();
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
        var timestamp = consentMetadata.updatedAt;
        var isoTimestamp;
        if (timestamp) {
            var date = new Date(timestamp);
            if (!isNaN(date.getTime())) {
                isoTimestamp = date.toISOString();
            }
        }
        if (!isoTimestamp) {
            isoTimestamp = new Date().toISOString();
        }
        window.dataLayer.push({
            event: 'fp_consent_update',
            consent_id: consentId,
            consent_state: Object.assign({}, state),
            google_consent: Object.assign({}, googleState || {}),
            consent_language: settings.language || '',
            consent_timestamp: isoTimestamp
        });
    }

    function buildRequestBody(state, eventType) {
        var pairs = [];

        function append(key, value) {
            pairs.push(encodeURIComponent(key) + '=' + encodeURIComponent(value));
        }

        append('action', 'fp_save_consent');
        append('nonce', settings.nonce);
        append('consentId', consentId || '');
        append('event', eventType);

        Object.keys(state).forEach(function (key) {
            append('consent[' + key + ']', state[key] ? '1' : '0');
        });

        return pairs.join('&');
    }

    function sendConsentToServer(state, eventType) {
        if (!settings.ajaxUrl || !settings.nonce) {
            return;
        }

        var body = buildRequestBody(state, eventType);

        if (typeof window.fetch === 'function') {
            fetch(settings.ajaxUrl, {
                method: 'POST',
                credentials: 'same-origin',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: body
            })
                .then(function (response) {
                    if (!response || !response.ok) {
                        throw new Error('HTTP ' + (response ? response.status : '0'));
                    }
                    return response.json().catch(function () {
                        return null;
                    });
                })
                .then(function (payload) {
                    if (!payload || typeof payload !== 'object') {
                        return;
                    }
                    if (payload.success && payload.data && payload.data.consentId) {
                        consentId = payload.data.consentId;
                    }
                })
                .catch(function (error) {
                    logWarning('[FP Privacy] Unable to log consent', error);
                });
            return;
        }

        var xhr = new XMLHttpRequest();
        xhr.open('POST', settings.ajaxUrl, true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded; charset=UTF-8');
        xhr.withCredentials = true;
        xhr.onload = function () {
            if (xhr.status < 200 || xhr.status >= 300) {
                return;
            }

            var payload = null;

            if (xhr.responseText) {
                try {
                    payload = JSON.parse(xhr.responseText);
                } catch (error) {
                    payload = null;
                }
            }

            if (payload && payload.success && payload.data && payload.data.consentId) {
                consentId = payload.data.consentId;
            }
        };
        xhr.onerror = function () {
            logWarning('[FP Privacy] Unable to log consent');
        };
        xhr.send(body);
    }

    function storeConsentCookie(state, timestamp) {
        var payload = Object.assign({}, state);
        var storedTimestamp = timestamp || consentMetadata.updatedAt;
        if (storedTimestamp) {
            payload.__fpTimestamp = storedTimestamp;
        }

        var value = encodeURIComponent(JSON.stringify(payload));
        var attributes = [];
        var maxAgeSeconds = null;

        if (cookieTtlDays === 0) {
            maxAgeSeconds = null;
        } else if (cookieTtlDays && cookieTtlDays > 0) {
            maxAgeSeconds = cookieTtlDays * 24 * 60 * 60;
        } else {
            maxAgeSeconds = 365 * 24 * 60 * 60;
        }

        var path = typeof cookieOptions.path === 'string' && cookieOptions.path ? cookieOptions.path : '/';
        attributes.push('path=' + path);

        var sameSite = typeof cookieOptions.sameSite === 'string' && cookieOptions.sameSite ? cookieOptions.sameSite : 'Lax';
        sameSite = normalizeSameSite(sameSite);
        attributes.push('SameSite=' + sameSite);

        if (typeof cookieOptions.domain === 'string' && cookieOptions.domain) {
            attributes.push('domain=' + cookieOptions.domain);
        }

        if (maxAgeSeconds) {
            attributes.push('max-age=' + maxAgeSeconds);
            var expires = new Date();
            expires.setTime(expires.getTime() + maxAgeSeconds * 1000);
            attributes.push('expires=' + expires.toUTCString());
        }

        var secure;
        if (typeof cookieOptions.secure === 'boolean') {
            secure = cookieOptions.secure;
        } else {
            secure = !!(window.location && window.location.protocol === 'https:');
        }

        if (sameSite.toLowerCase() === 'none') {
            secure = true;
        }

        if (secure) {
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
        var updatedAt = consentMetadata.updatedAt;
        if (!updatedAt) {
            updatedAt = new Date().toISOString();
        }
        var detail = {
            consentId: consentId,
            state: Object.assign({}, state),
            eventType: eventType,
            updatedAt: updatedAt
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
            updateUpdatedAtUI();
        } else {
            manageButton.classList.remove('is-visible');
            manageButton.hidden = true;
            manageButton.setAttribute('aria-hidden', 'true');
            manageButton.setAttribute('aria-expanded', 'false');
            if (statusElement) {
                statusElement.hidden = true;
                statusElement.classList.remove('is-visible');
            }
            if (statusValueElement) {
                statusValueElement.textContent = '';
                statusValueElement.removeAttribute('datetime');
            }
            manageButton.removeAttribute('title');
            manageButton.removeAttribute('aria-describedby');
        }
    }

    function updateUpdatedAtUI() {
        if (!statusElement || !statusValueElement) {
            if (manageButton) {
                manageButton.removeAttribute('title');
                manageButton.removeAttribute('aria-describedby');
            }
            return;
        }

        if (manageButton && (manageButton.hidden || !manageButton.classList.contains('is-visible'))) {
            statusElement.hidden = true;
            statusElement.classList.remove('is-visible');
            return;
        }

        if (!consentMetadata.updatedAt) {
            statusElement.hidden = true;
            statusElement.classList.remove('is-visible');
            statusValueElement.textContent = '';
            statusValueElement.removeAttribute('datetime');
            if (manageButton) {
                manageButton.removeAttribute('title');
                manageButton.removeAttribute('aria-describedby');
            }
            return;
        }

        var formatted = formatTimestamp(consentMetadata.updatedAt);
        if (!formatted) {
            statusElement.hidden = true;
            statusElement.classList.remove('is-visible');
            statusValueElement.textContent = '';
            statusValueElement.removeAttribute('datetime');
            if (manageButton) {
                manageButton.removeAttribute('title');
                manageButton.removeAttribute('aria-describedby');
            }
            return;
        }

        statusValueElement.textContent = formatted.text;
        statusValueElement.setAttribute('datetime', formatted.iso);
        statusElement.hidden = false;
        statusElement.classList.add('is-visible');

        if (manageButton) {
            if (statusLabelText) {
                manageButton.setAttribute('title', statusLabelText + ': ' + formatted.text);
            } else {
                manageButton.setAttribute('title', formatted.text);
            }
            if (statusElement.id) {
                manageButton.setAttribute('aria-describedby', statusElement.id);
            } else {
                manageButton.removeAttribute('aria-describedby');
            }
        }
    }

    function formatTimestamp(timestamp) {
        if (!timestamp) {
            return null;
        }

        var date = new Date(timestamp);
        if (isNaN(date.getTime())) {
            return null;
        }

        var options = {
            year: 'numeric',
            month: '2-digit',
            day: '2-digit',
            hour: '2-digit',
            minute: '2-digit',
            hour12: false
        };
        var seenLocales = {};
        var text = '';

        for (var i = 0; i < preferredLocales.length; i++) {
            var locale = preferredLocales[i];
            if (!locale || seenLocales[locale]) {
                continue;
            }
            seenLocales[locale] = true;
            try {
                text = date.toLocaleString(locale, options);
                if (text) {
                    break;
                }
            } catch (error) {
                continue;
            }
        }

        if (!text) {
            try {
                text = date.toLocaleString(undefined, options);
            } catch (error) {
                text = '';
            }
        }

        if (!text) {
            text = date.toISOString().replace('T', ' ').replace(/\..*/, ' UTC');
        }

        return {
            text: text,
            iso: date.toISOString()
        };
    }

    function normalizeSameSite(value) {
        var normalized = (value || '').toString().trim().toLowerCase();
        switch (normalized) {
            case 'strict':
                return 'Strict';
            case 'none':
                return 'None';
            case 'lax':
            default:
                return 'Lax';
        }
    }
})();
