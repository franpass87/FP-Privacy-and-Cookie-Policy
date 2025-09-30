(function ($) {
'use strict';

function getLuminance(hex) {
    hex = hex.replace('#', '');
    if ( hex.length === 3 ) {
        hex = hex[0] + hex[0] + hex[1] + hex[1] + hex[2] + hex[2];
    }

    var r = parseInt( hex.substr( 0, 2 ), 16 ) / 255;
    var g = parseInt( hex.substr( 2, 2 ), 16 ) / 255;
    var b = parseInt( hex.substr( 4, 2 ), 16 ) / 255;
    var arr = [ r, g, b ].map( function ( value ) {
        return value <= 0.03928 ? value / 12.92 : Math.pow( ( value + 0.055 ) / 1.055, 2.4 );
    } );

    return 0.2126 * arr[0] + 0.7152 * arr[1] + 0.0722 * arr[2];
}

function contrastRatio(hex1, hex2) {
    var l1 = getLuminance( hex1 );
    var l2 = getLuminance( hex2 );
    var lighter = Math.max( l1, l2 );
    var darker = Math.min( l1, l2 );

    return ( lighter + 0.05 ) / ( darker + 0.05 );
}

function createPreview(container) {
    var banner = $( '<div class="fp-privacy-banner"></div>' );
    var title = $( '<h2></h2>' );
    var message = $( '<p class="fp-privacy-preview-message"></p>' );
    var revision = $( '<div class="fp-privacy-revision-notice"></div>' ).hide();
    var link = $( '<a class="fp-privacy-link" target="_blank" rel="noopener noreferrer"></a>' ).hide();
    var buttons = $( '<div class="fp-privacy-banner-buttons"></div>' );
    var accept = $( '<button type="button" class="fp-privacy-button fp-privacy-button-primary"></button>' );
    var reject = $( '<button type="button" class="fp-privacy-button fp-privacy-button-secondary"></button>' );
    var prefs = $( '<button type="button" class="fp-privacy-button fp-privacy-button-secondary"></button>' );

    buttons.append( accept, reject, prefs );
    banner.append( title, message, revision, link, buttons );
    container.empty().append( banner );

    return {
        banner: banner,
        title: title,
        message: message,
        revision: revision,
        link: link,
        buttons: {
            accept: accept,
            reject: reject,
            prefs: prefs,
        },
    };
}

$( function () {
    var form = $( '.fp-privacy-settings-form' );
    if ( ! form.length ) {
        return;
    }

    var l10n = window.fpPrivacyL10n || {};

    var notice = $( '<div class="notice notice-warning" style="display:none;"><p></p></div>' );
    form.prepend( notice );

    function evaluateContrast() {
        var surface = form.find( 'input[name="banner_layout[palette][surface_bg]"]' ).val();
        var text = form.find( 'input[name="banner_layout[palette][surface_text]"]' ).val();

        if ( ! surface || ! text ) {
            notice.hide();
            return;
        }

        var ratio = contrastRatio( surface, text );
        if ( ratio < 4.5 ) {
            notice.find( 'p' ).text( l10n.lowContrast || 'The contrast ratio between background and text is below 4.5:1. Please adjust your palette.' );
            notice.show();
        } else {
            notice.hide();
        }
    }

    form.on( 'change', 'input[type="color"]', evaluateContrast );
    evaluateContrast();

    var previewContainer = $( '#fp-privacy-preview-banner' );
    if ( ! previewContainer.length ) {
        return;
    }

    var preview = createPreview( previewContainer );
    var languageSelect = $( '#fp-privacy-preview-language' );
    var paletteFields = form.find( 'input[name^="banner_layout[palette]"]' );
    var layoutType = form.find( 'select[name="banner_layout[type]"]' );
    var layoutPosition = form.find( 'select[name="banner_layout[position]"]' );

    function getLanguagePanel( lang ) {
        var panel = form.find( '.fp-privacy-language-panel[data-lang="' + lang + '"]' );
        if ( ! panel.length ) {
            panel = form.find( '.fp-privacy-language-panel' ).first();
        }

        return panel;
    }

    function collectTexts( lang ) {
        var panel = getLanguagePanel( lang );

        return {
            title: panel.find( '[data-field="title"]' ).val() || '',
            message: panel.find( '[data-field="message"]' ).val() || '',
            btnAccept: panel.find( '[data-field="btn_accept"]' ).val() || '',
            btnReject: panel.find( '[data-field="btn_reject"]' ).val() || '',
            btnPrefs: panel.find( '[data-field="btn_prefs"]' ).val() || '',
            link: panel.find( '[data-field="link_policy"]' ).val() || '',
            revisionNotice: panel.find( '[data-field="revision_notice"]' ).val() || '',
        };
    }

    function collectPalette() {
        return {
            surface_bg: form.find( 'input[name="banner_layout[palette][surface_bg]"]' ).val(),
            surface_text: form.find( 'input[name="banner_layout[palette][surface_text]"]' ).val(),
            button_primary_bg: form.find( 'input[name="banner_layout[palette][button_primary_bg]"]' ).val(),
            button_primary_tx: form.find( 'input[name="banner_layout[palette][button_primary_tx]"]' ).val(),
            button_secondary_bg: form.find( 'input[name="banner_layout[palette][button_secondary_bg]"]' ).val(),
            button_secondary_tx: form.find( 'input[name="banner_layout[palette][button_secondary_tx]"]' ).val(),
            link: form.find( 'input[name="banner_layout[palette][link]"]' ).val(),
            border: form.find( 'input[name="banner_layout[palette][border]"]' ).val(),
            focus: form.find( 'input[name="banner_layout[palette][focus]"]' ).val(),
        };
    }

    function applyPalette( palette ) {
        Object.keys( palette ).forEach( function ( key ) {
            var value = palette[ key ];
            if ( value ) {
                preview.banner[0].style.setProperty( '--fp-privacy-' + key, value );
            } else {
                preview.banner[0].style.removeProperty( '--fp-privacy-' + key );
            }
        } );
    }

    function updatePreview() {
        var lang = languageSelect.val() || previewContainer.data( 'preview-lang' );
        var texts = collectTexts( lang );
        var palette = collectPalette();

        applyPalette( palette );

        preview.title.text( texts.title );
        if ( texts.message ) {
            preview.message.html( texts.message );
        } else {
            preview.message.text( l10n.previewEmpty || '' );
        }

        if ( texts.link ) {
            preview.link.attr( 'href', texts.link ).text( texts.link ).show();
        } else {
            preview.link.removeAttr( 'href' ).text( '' ).hide();
        }

        if ( texts.revisionNotice ) {
            preview.revision.text( texts.revisionNotice ).show();
        } else {
            preview.revision.hide().text( '' );
        }

        preview.buttons.accept.text( texts.btnAccept );
        preview.buttons.reject.text( texts.btnReject );
        preview.buttons.prefs.text( texts.btnPrefs );

        var type = layoutType.val();
        var position = layoutPosition.val();

        preview.banner.toggleClass( 'is-bar', type === 'bar' );
        preview.banner.toggleClass( 'is-floating', type !== 'bar' );
        preview.banner.toggleClass( 'position-top', position === 'top' );
        preview.banner.toggleClass( 'position-bottom', position === 'bottom' );
    }

    form.on( 'input change', '.fp-privacy-language-panel input, .fp-privacy-language-panel textarea', updatePreview );
    paletteFields.on( 'input change', updatePreview );
    layoutType.on( 'change', updatePreview );
    layoutPosition.on( 'change', updatePreview );
    languageSelect.on( 'change', updatePreview );

    updatePreview();
} );
})( window.jQuery );
