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
    var requiredKeys = Object.keys(categories).filter(function (key) {
        return categories[key] && categories[key].required;
    });
    var enabledKeys = Object.keys(categories).filter(function (key) {
        return categories[key] && (categories[key].enabled || categories[key].required);
    });
    var translations = settings.translations || {};
    var translationStrings = translations.strings || {};
    var availableLanguages = Array.isArray(translations.available) ? translations.available.slice() : (settings.language && Array.isArray(settings.language.available) ? settings.language.available.slice() : []);
    var fallbackLanguage = translations.fallback || (settings.language && settings.language.fallback) || 'it';
    availableLanguages = availableLanguages.map(normalizeLanguageCode).filter(Boolean);
    fallbackLanguage = normalizeLanguageCode(fallbackLanguage) || (availableLanguages.length ? availableLanguages[0] : 'it');
    if (availableLanguages.indexOf(fallbackLanguage) === -1) {
        availableLanguages.push(fallbackLanguage);
    }
    var activeLanguage = determineLanguage();
    var bannerElement;
    var modalElement;
    var currentConsent = null;

    document.addEventListener('DOMContentLoaded', initialize);

    function initialize() {
        bannerElement = document.querySelector('.fp-consent-banner');
        modalElement = document.querySelector('.fp-consent-modal');

        applyTranslations(activeLanguage);
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
        } else {
            currentConsent = buildDefaultConsent();
            showBanner();
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

    function normalizeLanguageCode(language) {
        if (!language || typeof language !== 'string') {
            return '';
        }

        var normalized = language.trim().toLowerCase();
        if (!normalized) {
            return '';
        }

        normalized = normalized.replace(/_/g, '-');
        var parts = normalized.split('-');

        return parts[0] || '';
    }

    function determineLanguage() {
        var languages = availableLanguages.slice();
        if (!languages.length) {
            languages = Object.keys(translationStrings);
        }

        var candidates = [];
        if (Array.isArray(navigator.languages) && navigator.languages.length) {
            candidates = navigator.languages.slice();
        } else if (navigator.language) {
            candidates = [navigator.language];
        }

        if (typeof document !== 'undefined' && document.documentElement && document.documentElement.lang) {
            candidates.push(document.documentElement.lang);
        }

        for (var i = 0; i < candidates.length; i++) {
            var normalized = normalizeLanguageCode(candidates[i]);
            if (normalized && languages.indexOf(normalized) !== -1) {
                return normalized;
            }
        }

        if (fallbackLanguage && languages.indexOf(fallbackLanguage) !== -1) {
            return fallbackLanguage;
        }

        return languages.length ? languages[0] : (fallbackLanguage || 'it');
    }

    function getTranslationsFor(language) {
        var normalized = normalizeLanguageCode(language);
        if (normalized && translationStrings[normalized]) {
            return translationStrings[normalized];
        }
        if (fallbackLanguage && translationStrings[fallbackLanguage]) {
            return translationStrings[fallbackLanguage];
        }
        var keys = Object.keys(translationStrings);
        return keys.length ? translationStrings[keys[0]] : null;
    }

    function applyTranslations(language) {
        var data = getTranslationsFor(language);
        if (!data) {
            return;
        }

        activeLanguage = normalizeLanguageCode(language) || activeLanguage;

        if (!bannerElement) {
            bannerElement = document.querySelector('.fp-consent-banner');
        }
        if (!modalElement) {
            modalElement = document.querySelector('.fp-consent-modal');
        }

        var bannerTexts = data.banner || {};
        if (bannerElement) {
            var titleEl = bannerElement.querySelector('.fp-consent-title');
            if (titleEl && (bannerTexts.title || bannerTexts.banner_title)) {
                titleEl.textContent = bannerTexts.title || bannerTexts.banner_title;
            }
            var messageEl = bannerElement.querySelector('.fp-consent-text');
            if (messageEl && (bannerTexts.message || bannerTexts.banner_message)) {
                messageEl.innerHTML = bannerTexts.message || bannerTexts.banner_message;
            }
        }

        setButtonText('accept-all', bannerTexts.acceptAll || bannerTexts.accept_all_label);
        setButtonText('reject-all', bannerTexts.rejectAll || bannerTexts.reject_all_label);
        setButtonText('open-preferences', bannerTexts.preferences || bannerTexts.preferences_label);
        setButtonText('save-preferences', bannerTexts.save || bannerTexts.save_preferences_label);

        if (modalElement && data.modal) {
            var closeButton = modalElement.querySelector('.fp-consent-modal__close');
            if (closeButton && data.modal.close) {
                closeButton.setAttribute('aria-label', data.modal.close);
            }

            var modalTitle = modalElement.querySelector('#fp-consent-modal-title');
            if (modalTitle && data.modal.title) {
                modalTitle.textContent = data.modal.title;
            }

            var modalIntro = modalElement.querySelector('.fp-consent-modal__intro');
            if (modalIntro && data.modal.intro) {
                modalIntro.textContent = data.modal.intro;
            }

            var requiredLabels = modalElement.querySelectorAll('.fp-consent-required');
            Array.prototype.forEach.call(requiredLabels, function (node) {
                if (data.modal.alwaysActive) {
                    node.textContent = data.modal.alwaysActive;
                }
            });

            var categoryMap = data.categories || {};
            var categoryElements = modalElement.querySelectorAll('.fp-consent-category');
            Array.prototype.forEach.call(categoryElements, function (element) {
                var key = element.getAttribute('data-category-key');
                if (!key || !categoryMap[key]) {
                    return;
                }

                var texts = categoryMap[key];
                var headerTitle = element.querySelector('.fp-consent-category__header h4');
                if (headerTitle && texts.label) {
                    headerTitle.textContent = texts.label;
                }

                var descriptionEl = element.querySelector('.fp-consent-category__header > div > p');
                if (descriptionEl && typeof texts.description === 'string') {
                    descriptionEl.textContent = texts.description;
                }

                var detailsEl = element.querySelector('details');
                if (detailsEl) {
                    var summaryEl = detailsEl.querySelector('summary');
                    if (summaryEl && data.modal.services) {
                        summaryEl.textContent = data.modal.services;
                    }
                    var detailsContent = detailsEl.querySelector('p');
                    if (detailsContent) {
                        if (texts.services) {
                            detailsContent.textContent = texts.services;
                            detailsEl.style.display = '';
                        } else {
                            detailsContent.textContent = '';
                            detailsEl.open = false;
                            detailsEl.style.display = 'none';
                        }
                    }
                }

                var srText = element.querySelector('.screen-reader-text');
                if (srText && data.modal.toggle && texts.label) {
                    srText.textContent = data.modal.toggle.replace('%s', texts.label);
                }
            });
        }
    }

    function setButtonText(action, text) {
        if (!text) {
            return;
        }
        var buttons = document.querySelectorAll('[data-consent-action="' + action + '"]');
        Array.prototype.forEach.call(buttons, function (button) {
            button.textContent = text;
        });
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
        var attributes = ['path=/','max-age=' + 60 * 60 * 24 * 365,'SameSite=Lax'];
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
    }

    function closeModal() {
        if (!modalElement) {
            return;
        }
        modalElement.hidden = true;
        modalElement.classList.remove('is-visible');
        document.body.classList.remove('fp-consent-modal-open');
    }

    function showBanner() {
        if (!bannerElement) {
            bannerElement = document.querySelector('.fp-consent-banner');
        }
        if (bannerElement) {
            bannerElement.classList.add('is-visible');
        }
    }

    function hideBanner() {
        if (!bannerElement) {
            bannerElement = document.querySelector('.fp-consent-banner');
        }
        if (bannerElement) {
            bannerElement.classList.remove('is-visible');
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
})();
