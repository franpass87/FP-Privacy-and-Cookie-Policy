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

$( function () {
var form = $( '.fp-privacy-settings-form' );
if ( ! form.length ) {
return;
}

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
notice.find( 'p' ).text( window.fpPrivacyL10n ? window.fpPrivacyL10n.lowContrast : 'The contrast ratio between background and text is below 4.5:1. Please adjust your palette.' );
notice.show();
} else {
notice.hide();
}
}

form.on( 'change', 'input[type="color"]', evaluateContrast );
evaluateContrast();
});
})( window.jQuery );
