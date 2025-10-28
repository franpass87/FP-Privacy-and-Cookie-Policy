/**
 * Cookie Banner JavaScript
 * FP Privacy & Cookie Policy
 */

(function() {
    'use strict';

    // Verifica se il consenso è già stato dato
    if (document.cookie.indexOf('fp_privacy_consent') !== -1) {
        return;
    }

    const banner = document.getElementById('fp-privacy-banner');
    const modal = document.getElementById('fp-privacy-modal');
    const modalOverlay = modal?.querySelector('.fp-privacy-modal__overlay');
    const modalClose = modal?.querySelector('.fp-privacy-modal__close');

    // Buttons
    const acceptAllBtn = document.getElementById('fp-privacy-accept-all');
    const rejectAllBtn = document.getElementById('fp-privacy-reject-all');
    const settingsBtn = document.getElementById('fp-privacy-settings');
    const savePreferencesBtn = document.getElementById('fp-privacy-save-preferences');

    // Accept All
    acceptAllBtn?.addEventListener('click', function() {
        const categories = fpPrivacyConfig.categories || {};
        const consent = {};

        Object.keys(categories).forEach(function(key) {
            consent[key] = true;
        });

        saveConsent(consent);
    });

    // Reject All
    rejectAllBtn?.addEventListener('click', function() {
        const categories = fpPrivacyConfig.categories || {};
        const consent = {};

        Object.keys(categories).forEach(function(key) {
            // Solo i cookie necessari
            consent[key] = categories[key].required || false;
        });

        saveConsent(consent);
    });

    // Open Settings Modal
    settingsBtn?.addEventListener('click', function() {
        modal?.classList.add('is-open');
        modal.style.display = 'block';
        document.body.style.overflow = 'hidden';
    });

    // Close Modal
    function closeModal() {
        modal?.classList.remove('is-open');
        modal.style.display = 'none';
        document.body.style.overflow = '';
    }

    modalClose?.addEventListener('click', closeModal);
    modalOverlay?.addEventListener('click', closeModal);

    // Save Preferences
    savePreferencesBtn?.addEventListener('click', function() {
        const checkboxes = modal?.querySelectorAll('input[name="fp_privacy_category[]"]');
        const consent = {};

        checkboxes?.forEach(function(checkbox) {
            consent[checkbox.value] = checkbox.checked;
        });

        saveConsent(consent);
    });

    // Save Consent Function
    function saveConsent(consent) {
        // Salva nel cookie
        const cookieData = {
            consent: consent,
            timestamp: Date.now(),
            version: fpPrivacyConfig.settings?.version || '1.0.0'
        };

        const cookieValue = JSON.stringify(cookieData);
        const expires = new Date();
        expires.setTime(expires.getTime() + (fpPrivacyConfig.cookieDuration * 24 * 60 * 60 * 1000));

        document.cookie = 'fp_privacy_consent=' + encodeURIComponent(cookieValue) + 
                         ';expires=' + expires.toUTCString() + 
                         ';path=/;SameSite=Lax' +
                         (location.protocol === 'https:' ? ';Secure' : '');

        // Invia al server via AJAX
        fetch(fpPrivacyConfig.ajaxUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: new URLSearchParams({
                action: 'fp_privacy_save_consent',
                nonce: fpPrivacyConfig.nonce,
                consent: JSON.stringify(consent)
            })
        }).then(function(response) {
            return response.json();
        }).then(function(data) {
            if (data.success) {
                // Trigger evento personalizzato
                const event = new CustomEvent('fpPrivacyConsentGiven', {
                    detail: consent
                });
                document.dispatchEvent(event);

                // Nascondi banner
                hideBanner();

                // Ricarica script bloccati
                reloadBlockedScripts(consent);
            }
        }).catch(function(error) {
            console.error('FP Privacy: Errore salvataggio consenso', error);
        });
    }

    // Hide Banner
    function hideBanner() {
        if (banner) {
            banner.style.animation = 'fpPrivacySlideOut 0.5s ease';
            setTimeout(function() {
                banner.style.display = 'none';
            }, 500);
        }
        closeModal();
    }

    // Reload Blocked Scripts
    function reloadBlockedScripts(consent) {
        const blockedScripts = document.querySelectorAll('script[type="text/plain"][data-fp-privacy-category]');

        blockedScripts.forEach(function(script) {
            const category = script.dataset.fpPrivacyCategory;

            if (consent[category]) {
                const newScript = document.createElement('script');
                newScript.src = script.src;
                newScript.async = true;

                // Copia attributi
                Array.from(script.attributes).forEach(function(attr) {
                    if (attr.name !== 'type' && attr.name !== 'data-fp-privacy-category') {
                        newScript.setAttribute(attr.name, attr.value);
                    }
                });

                script.parentNode.replaceChild(newScript, script);
            }
        });
    }

    // Animation CSS
    const style = document.createElement('style');
    style.textContent = `
        @keyframes fpPrivacySlideOut {
            from {
                transform: translateY(0);
                opacity: 1;
            }
            to {
                transform: translateY(100%);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);

})();

