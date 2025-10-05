(function () {
    if ( ! window.fpPrivacyConsent ) {
        window.fpPrivacyConsent = {};
    }

    var consent = window.fpPrivacyConsent;

    function normalizeBoolean( value, fallback ) {
        if ( value === true || value === false ) {
            return value;
        }

        if ( typeof value === 'string' ) {
            if ( value === 'granted' ) {
                return true;
            }

            if ( value === 'denied' ) {
                return false;
            }
        }

        return fallback;
    }

    function cloneDefaults( defaults ) {
        var result = {};

        for ( var key in defaults ) {
            if ( Object.prototype.hasOwnProperty.call( defaults, key ) ) {
                result[ key ] = defaults[ key ];
            }
        }

        return result;
    }

    consent.defaults = function () {
        return window.fpPrivacyConsentDefaults || {};
    };

    consent.mapBannerPayload = function ( payload, context ) {
        payload = payload || {};
        context = context || {};

        var defaults = context.defaults || consent.defaults();
        var result = cloneDefaults( defaults );

        var marketingFallback = result.ad_storage === 'granted' || result.ad_user_data === 'granted' || result.ad_personalization === 'granted';
        var statisticsFallback = result.analytics_storage === 'granted';
        var functionalityFallback = result.functionality_storage === 'granted';
        var personalizationFallback = result.personalization_storage === 'granted';

        var marketing = normalizeBoolean( payload.marketing, marketingFallback );
        var statistics = normalizeBoolean( payload.statistics, statisticsFallback );
        var preferencesFallback = functionalityFallback || personalizationFallback;
        var preferences = normalizeBoolean( payload.preferences, preferencesFallback );
        var necessary = normalizeBoolean( payload.necessary, true );

        result.analytics_storage = statistics ? 'granted' : 'denied';
        result.ad_storage = marketing ? 'granted' : 'denied';
        result.ad_user_data = marketing ? 'granted' : 'denied';
        result.ad_personalization = marketing ? 'granted' : 'denied';
        result.functionality_storage = ( preferences || necessary ) ? 'granted' : 'denied';
        result.personalization_storage = preferences ? 'granted' : 'denied';
        result.security_storage = 'granted';

        return result;
    };

    consent.update = function ( states ) {
        if ( typeof window.dataLayer === 'undefined' ) {
            window.dataLayer = [];
        }

        var defaults = consent.defaults();

        // Optional Global Privacy Control handling on updates too: if enabled client-side
        // via window.fpPrivacyEnableGPC and navigator.globalPrivacyControl is true,
        // coerce non-necessary storages to 'denied'.
        try {
            if ( window.fpPrivacyEnableGPC && navigator && navigator.globalPrivacyControl === true ) {
                var denyKeys = [ 'analytics_storage', 'ad_storage', 'ad_user_data', 'ad_personalization', 'personalization_storage' ];
                for ( var i = 0; i < denyKeys.length; i++ ) {
                    var key = denyKeys[ i ];
                    states[ key ] = 'denied';
                }
            }
        } catch (e) {}
        var payload = cloneDefaults( defaults );

        for ( var consentKey in states ) {
            if ( Object.prototype.hasOwnProperty.call( states, consentKey ) ) {
                payload[ consentKey ] = states[ consentKey ];
            }
        }

        if ( typeof window.gtag === 'function' ) {
            window.gtag( 'consent', 'update', payload );
        } else {
            window.dataLayer.push( [ 'consent', 'update', payload ] );
        }

        window.dataLayer.push( {
            event: 'fp_consent_mode_update',
            consent: payload,
        } );

        return payload;
    };
})();
