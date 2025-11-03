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
    
    // ========================================
    // GESTIONE TABS
    // ========================================
    var tabButtons = $( '.fp-privacy-tab-button' );
    var tabContents = $( '.fp-privacy-tab-content' );
    
    // Ripristina tab attiva dal localStorage
    var activeTab = localStorage.getItem( 'fpPrivacyActiveTab' ) || 'banner';
    switchTab( activeTab );
    
    // Click sui pulsanti tab
    tabButtons.on( 'click', function() {
        var tab = $( this ).data( 'tab' );
        switchTab( tab );
        localStorage.setItem( 'fpPrivacyActiveTab', tab );
    });
    
    function switchTab( tab ) {
        // Rimuovi classe active da tutti
        tabButtons.removeClass( 'active' );
        tabContents.removeClass( 'active' );
        
        // Aggiungi active al tab selezionato
        tabButtons.filter( '[data-tab="' + tab + '"]' ).addClass( 'active' );
        tabContents.filter( '[data-tab-content="' + tab + '"]' ).addClass( 'active' );
        
        // Scroll to top dopo cambio tab
        $( 'html, body' ).animate({ scrollTop: $( '.fp-privacy-tabs-nav' ).offset().top - 32 }, 300 );
    }
    
    // QUICK WIN #1: Inizializza WordPress Color Picker con gestione robusta
    if ( $.fn.wpColorPicker ) {
        var allPickers = [];
        var isUpdatingProgrammatically = false; // Flag per prevenire loop
        
        $( '.fp-privacy-color-picker' ).each( function() {
            var $input = $( this );
            var pickerData = {
                input: $input,
                container: null,
                inputWrap: null,
                hexInput: null,
                colorButton: null,
                pickerHolder: null,
                observer: null, // Memorizza observer per cleanup
                isObserving: false // Flag per prevenire loop MutationObserver
            };
            
            // Initialize color picker (con error handling)
            try {
                $input.wpColorPicker({
                    change: function( event, ui ) {
                        // Previeni loop quando aggiorniamo programmaticamente
                        if ( ! isUpdatingProgrammatically ) {
                            $( this ).trigger( 'input' );
                            evaluateContrast();
                        }
                    },
                    clear: function() {
                        $( this ).trigger( 'input' );
                        evaluateContrast();
                    }
                });
            } catch ( error ) {
                console.error( 'FP Privacy: Error initializing color picker', error );
                return; // Skip this picker if initialization fails
            }
            
            // Memorizza riferimenti agli elementi (con controlli sicurezza)
            pickerData.container = $input.closest( '.wp-picker-container' );
            
            if ( ! pickerData.container.length ) {
                console.warn( 'FP Privacy: wp-picker-container not found for', $input );
                return; // Skip this picker
            }
            
            pickerData.inputWrap = pickerData.container.find( '.wp-picker-input-wrap' );
            pickerData.hexInput = pickerData.inputWrap.find( 'input[type="text"]' );
            pickerData.colorButton = pickerData.container.find( '.wp-color-result' );
            pickerData.pickerHolder = pickerData.container.find( '.wp-picker-holder' );
            
            // Verifica che tutti gli elementi esistano
            if ( ! pickerData.inputWrap.length || ! pickerData.hexInput.length ) {
                console.warn( 'FP Privacy: Required elements not found for color picker', $input );
                return; // Skip this picker
            }
            
            allPickers.push( pickerData );
            
            // Funzione per forzare visibilità input HEX (con protezione loop)
            function ensureInputVisible() {
                if ( pickerData.isObserving ) {
                    return; // Previeni loop infinito
                }
                
                pickerData.isObserving = true;
                pickerData.inputWrap.attr( 'style', 
                    'display: flex !important; visibility: visible !important; opacity: 1 !important;'
                );
                
                setTimeout( function() {
                    pickerData.isObserving = false;
                }, 100 );
            }
            
            // Applica visibilità iniziale
            ensureInputVisible();
            
            // Usa MutationObserver per mantenere sempre visibile l'input (con cleanup)
            if ( window.MutationObserver ) {
                pickerData.observer = new MutationObserver( function( mutations ) {
                    if ( pickerData.isObserving ) {
                        return; // Previeni loop se stiamo già aggiornando
                    }
                    
                    mutations.forEach( function( mutation ) {
                        if ( mutation.type === 'attributes' && mutation.attributeName === 'style' ) {
                            var display = pickerData.inputWrap.css( 'display' );
                            if ( display === 'none' || display === '' ) {
                                ensureInputVisible();
                            }
                        }
                    });
                });
                
                pickerData.observer.observe( pickerData.inputWrap[0], {
                    attributes: true,
                    attributeFilter: ['style']
                });
            }
            
            // Gestione click sul pulsante colore - chiudi altri picker
            pickerData.colorButton.on( 'click', function( e ) {
                var isOpening = ! pickerData.pickerHolder.is( ':visible' );
                
                if ( isOpening ) {
                    // Chiudi tutti gli altri picker
                    allPickers.forEach( function( otherPicker ) {
                        if ( otherPicker !== pickerData && otherPicker.pickerHolder.is( ':visible' ) ) {
                            otherPicker.colorButton.click();
                        }
                    });
                }
                
                // Assicura visibilità input dopo apertura/chiusura
                setTimeout( ensureInputVisible, 50 );
            });
            
            // CRITICAL: Impedisci che click/focus su input HEX apra la palette
            if ( pickerData.hexInput.length ) {
                // Blocca propagazione eventi su input HEX (mouse + touch)
                pickerData.hexInput.on( 'mousedown click focus touchstart', function( e ) {
                    e.stopPropagation();
                    
                    // Chiudi la palette se aperta
                    if ( pickerData.pickerHolder.is( ':visible' ) ) {
                        pickerData.colorButton.click();
                    }
                });
                
                // Blocca propagazione anche sul wrapper (mouse + touch)
                pickerData.inputWrap.on( 'mousedown click touchstart', function( e ) {
                    e.stopPropagation();
                });
                
                // ACCESSIBILITY: Gestione keyboard
                pickerData.hexInput.on( 'keydown', function( e ) {
                    // ESC chiude picker se aperto
                    if ( e.key === 'Escape' && pickerData.pickerHolder.is( ':visible' ) ) {
                        pickerData.colorButton.click();
                        e.preventDefault();
                    }
                    
                    // Enter conferma e chiude
                    if ( e.key === 'Enter' ) {
                        var val = $( this ).val();
                        if ( /^#[0-9A-F]{6}$/i.test( val ) ) {
                            pickerData.hexInput.blur(); // Conferma
                        }
                        e.preventDefault();
                    }
                });
                
                // ACCESSIBILITY: ARIA attributes
                pickerData.hexInput.attr({
                    'aria-label': 'Codice colore esadecimale',
                    'role': 'textbox',
                    'aria-describedby': 'hex-input-help-' + allPickers.length
                });
                
                // Color button accessibility
                pickerData.colorButton.attr({
                    'aria-label': 'Apri selettore colore visuale',
                    'aria-haspopup': 'true',
                    'aria-expanded': 'false'
                });
                
                // Update aria-expanded quando si apre/chiude
                pickerData.colorButton.on( 'click', function() {
                    var isOpen = pickerData.pickerHolder.is( ':visible' );
                    pickerData.colorButton.attr( 'aria-expanded', isOpen ? 'true' : 'false' );
                });
                
                // Setup input HEX
                pickerData.hexInput.attr({
                    'placeholder': '#000000',
                    'title': 'Incolla o digita un codice HEX (es: #FF5733)'
                });
                
                // FEATURE: Copy to clipboard button
                var $copyBtn = $( '<button type="button" class="fp-hex-copy-btn" title="Copia codice HEX">' +
                    '<span class="dashicons dashicons-clipboard"></span>' +
                    '</button>' );
                
                pickerData.inputWrap.append( $copyBtn );
                
                $copyBtn.on( 'click', function( e ) {
                    e.preventDefault();
                    e.stopPropagation();
                    
                    var hexValue = pickerData.hexInput.val();
                    
                    if ( ! hexValue || hexValue.length < 4 ) {
                        return;
                    }
                    
                    // Copy to clipboard (con fallback)
                    if ( navigator.clipboard && navigator.clipboard.writeText ) {
                        navigator.clipboard.writeText( hexValue ).then( function() {
                            showCopySuccess( $copyBtn );
                        }).catch( function() {
                            fallbackCopy( hexValue, $copyBtn );
                        });
                    } else {
                        fallbackCopy( hexValue, $copyBtn );
                    }
                });
                
                // Fallback copy per browser vecchi
                function fallbackCopy( text, $btn ) {
                    pickerData.hexInput.select();
                    try {
                        document.execCommand( 'copy' );
                        showCopySuccess( $btn );
                    } catch ( err ) {
                        console.warn( 'Copy failed', err );
                    }
                    pickerData.hexInput.blur();
                }
                
                // Feedback visivo per copy success
                function showCopySuccess( $btn ) {
                    $btn.addClass( 'copied' );
                    setTimeout( function() {
                        $btn.removeClass( 'copied' );
                    }, 1500 );
                }
                
                // Timer per debouncing
                var inputTimer = null;
                
                // Gestione input manuale con validazione e debouncing
                pickerData.hexInput.on( 'paste input keyup', function( e ) {
                    var $this = $( this );
                    
                    // Clear timer precedente
                    if ( inputTimer ) {
                        clearTimeout( inputTimer );
                    }
                    
                    var val = $this.val().trim().toUpperCase();
                    
                    // Normalizza formato
                    val = val.replace( /[^0-9A-F#]/gi, '' );
                    
                    if ( val.length > 0 && val[0] !== '#' ) {
                        val = '#' + val;
                    }
                    
                    // Supporta formato corto #RGB -> #RRGGBB
                    if ( val.length === 4 && /^#[0-9A-F]{3}$/i.test( val ) ) {
                        val = '#' + val[1] + val[1] + val[2] + val[2] + val[3] + val[3];
                    }
                    
                    if ( val.length > 7 ) {
                        val = val.substring( 0, 7 );
                    }
                    
                    if ( $this.val() !== val ) {
                        $this.val( val );
                    }
                    
                    // Validazione e feedback (supporta anche #RGB)
                    var isValid = /^#[0-9A-F]{6}$/i.test( val );
                    var isShortValid = /^#[0-9A-F]{3}$/i.test( val );
                    
                    if ( isValid || isShortValid ) {
                        $this.addClass( 'hex-valid' ).css( 'border-color', '#10b981' );
                        
                        // Debounce aggiornamento color picker (evita troppi update durante typing)
                        inputTimer = setTimeout( function() {
                            var colorValue = isShortValid ? 
                                '#' + val[1] + val[1] + val[2] + val[2] + val[3] + val[3] : val;
                            
                            // Aggiorna il color picker (con protezione loop)
                            isUpdatingProgrammatically = true;
                            pickerData.input.wpColorPicker( 'color', colorValue );
                            
                            // CRITICAL FIX: Trigger manuale per aggiornare preview
                            // Dato che blocchiamo il trigger automatico nel change handler
                            pickerData.input.trigger( 'input' );
                            evaluateContrast();
                            
                            setTimeout( function() {
                                isUpdatingProgrammatically = false;
                            }, 100 );
                            
                            // Badge successo
                            showSuccessBadge( pickerData.inputWrap );
                        }, e.type === 'paste' ? 50 : 300 ); // Paste più veloce, typing con delay
                        
                        setTimeout( function() {
                            $this.removeClass( 'hex-valid' ).css( 'border-color', '' );
                        }, 800 );
                        
                    } else if ( val.length === 7 || val.length === 4 ) {
                        $this.css( 'border-color', '#ef4444' );
                    } else {
                        $this.css( 'border-color', '' );
                    }
                });
            }
        });
        
        // Funzione helper per badge successo
        function showSuccessBadge( $wrapper ) {
            if ( $wrapper.find( '.copy-success' ).length ) {
                return;
            }
            
            var $badge = $( '<span class="copy-success">✓ Valido</span>' );
            $wrapper.append( $badge );
            
            setTimeout( function() { $badge.addClass( 'show' ); }, 50 );
            setTimeout( function() {
                $badge.removeClass( 'show' );
                setTimeout( function() { $badge.remove(); }, 300 );
            }, 1500 );
        }
        
        // Chiudi tutti i picker quando si clicca fuori (con namespace per cleanup)
        $( document ).off( 'click.fpPrivacyColorPicker' ).on( 'click.fpPrivacyColorPicker', function( e ) {
            if ( ! $( e.target ).closest( '.wp-picker-container' ).length ) {
                allPickers.forEach( function( picker ) {
                    if ( picker.pickerHolder && picker.pickerHolder.is( ':visible' ) ) {
                        picker.colorButton.click();
                    }
                });
            }
        });
        
        // ACCESSIBILITY: ESC chiude tutti i picker aperti (global handler)
        $( document ).off( 'keydown.fpPrivacyColorPicker' ).on( 'keydown.fpPrivacyColorPicker', function( e ) {
            if ( e.key === 'Escape' || e.keyCode === 27 ) {
                var hadOpenPicker = false;
                allPickers.forEach( function( picker ) {
                    if ( picker.pickerHolder && picker.pickerHolder.is( ':visible' ) ) {
                        picker.colorButton.click();
                        hadOpenPicker = true;
                    }
                });
                
                // Previeni propagazione solo se abbiamo chiuso un picker
                if ( hadOpenPicker ) {
                    e.stopPropagation();
                }
            }
        });
        
        // Cleanup quando la pagina viene scaricata (prevenzione memory leak)
        $( window ).on( 'beforeunload.fpPrivacyColorPicker', function() {
            allPickers.forEach( function( picker ) {
                // Disconnetti MutationObserver con protezione null
                if ( picker && picker.observer ) {
                    try {
                        picker.observer.disconnect();
                    } catch ( e ) {
                        console.warn( 'FP Privacy: Error disconnecting observer', e );
                    }
                    picker.observer = null;
                }
            });
            
            // Rimuovi event listener globali
            $( document ).off( 'click.fpPrivacyColorPicker' );
            $( document ).off( 'keydown.fpPrivacyColorPicker' );
            $( window ).off( 'beforeunload.fpPrivacyColorPicker' );
            
            // Clear array
            allPickers = [];
        });
    }
    
    // Aggiungi toggle mobile/desktop per il preview
    var previewControls = $( '.fp-privacy-preview-controls' );
    if ( previewControls.length ) {
        var modeToggle = $( '<div class="fp-privacy-preview-mode-toggle"></div>' );
        var desktopBtn = $( '<button type="button" class="active" data-mode="desktop">🖥️ Desktop</button>' );
        var mobileBtn = $( '<button type="button" data-mode="mobile">📱 Mobile</button>' );
        
        modeToggle.append( desktopBtn, mobileBtn );
        previewControls.append( modeToggle );
        
        modeToggle.on( 'click', 'button', function() {
            var btn = $( this );
            var mode = btn.data( 'mode' );
            var previewFrame = $( '.fp-privacy-preview-frame' );
            
            modeToggle.find( 'button' ).removeClass( 'active' );
            btn.addClass( 'active' );
            
            if ( mode === 'mobile' ) {
                previewFrame.addClass( 'mobile-mode' );
            } else {
                previewFrame.removeClass( 'mobile-mode' );
            }
        });
    }
    
    // Auto-save indicator
    var autoSaveIndicator = $( '<div class="fp-privacy-saving-indicator"><div class="spinner"></div><span>Salvataggio...</span></div>' );
    form.find( '.button-primary' ).after( autoSaveIndicator );
    
    // Sticky save button
    var originalButton = form.find( '.button-primary' );
    var stickyContainer = $( '<div class="fp-privacy-sticky-save"></div>' );
    var stickyButton = $( '<button type="button" class="button button-primary">Salva impostazioni</button>' );
    stickyContainer.append( stickyButton );
    $( 'body' ).append( stickyContainer );
    
    // Click handler per il bottone sticky
    stickyButton.on( 'click', function() {
        // Scrolla al bottone originale e cliccalo
        $( 'html, body' ).animate({
            scrollTop: originalButton.offset().top - 100
        }, 500, function() {
            originalButton.click();
        });
    });
    
    // Mostra/nascondi il bottone sticky durante lo scroll
    var scrollTimeout;
    var formBottom = form.offset().top + form.outerHeight();
    
    $( window ).on( 'scroll', function() {
        clearTimeout( scrollTimeout );
        
        scrollTimeout = setTimeout( function() {
            var scrollTop = $( window ).scrollTop();
            var windowHeight = $( window ).height();
            var documentHeight = $( document ).height();
            
            // Calcola se il bottone originale è visibile
            var buttonTop = originalButton.offset().top;
            var buttonBottom = buttonTop + originalButton.outerHeight();
            var isButtonVisible = buttonTop < ( scrollTop + windowHeight ) && buttonBottom > scrollTop;
            
            // Mostra il bottone sticky solo se:
            // 1. L'utente ha scrollato oltre una certa soglia (200px)
            // 2. Il bottone originale non è visibile
            // 3. Non siamo in fondo alla pagina
            if ( scrollTop > 200 && ! isButtonVisible && ( scrollTop + windowHeight ) < ( documentHeight - 50 ) ) {
                stickyContainer.addClass( 'visible' );
                
                // Aggiungi un effetto pulse se l'utente ha scrollato molto
                if ( scrollTop > 500 ) {
                    stickyButton.addClass( 'pulse' );
                }
            } else {
                stickyContainer.removeClass( 'visible' );
                stickyButton.removeClass( 'pulse' );
            }
        }, 50 );
    });
    
    // Trigger iniziale
    $( window ).trigger( 'scroll' );

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
    
    // Monitora il checkbox dark mode per aggiornare il preview
    form.on( 'change', 'input[name="banner_layout[enable_dark_mode]"]', function() {
        var isDarkMode = $( this ).is( ':checked' );
        var previewFrame = $( '.fp-privacy-preview-frame' );
        
        if ( isDarkMode ) {
            previewFrame.addClass( 'dark-mode-preview' );
            $( 'body' ).addClass( 'fp-privacy-dark-mode-enabled' );
        } else {
            previewFrame.removeClass( 'dark-mode-preview' );
            $( 'body' ).removeClass( 'fp-privacy-dark-mode-enabled' );
        }
        
        updatePreview();
    });

    updatePreview();
    
    // Toast notification system
    window.fpPrivacyShowToast = function( message, type ) {
        type = type || 'info';
        var toast = $( '<div class="fp-privacy-toast"></div>' ).addClass( type );
        
        var icon = '🔔';
        if ( type === 'success' ) icon = '✓';
        if ( type === 'error' ) icon = '✕';
        if ( type === 'warning' ) icon = '⚠';
        
        toast.html( '<span style="font-size:20px;">' + icon + '</span><span>' + message + '</span>' );
        $( 'body' ).append( toast );
        
        setTimeout( function() {
            toast.fadeOut( 300, function() { toast.remove(); } );
        }, 4000 );
    };
    
    // Intercetta submit form per mostrare indicatore
    form.on( 'submit', function() {
        autoSaveIndicator.addClass( 'visible' );
        form.find( '.button-primary' ).prop( 'disabled', true ).css( 'opacity', '0.6' );
    });
    
    // Filtri per tabella servizi rilevati
    var detectedTable = $( '.fp-privacy-detected' );
    if ( detectedTable.length ) {
        var filterRow = $( '<div class="fp-privacy-table-filters"></div>' );
        var searchInput = $( '<input type="text" placeholder="🔍 Cerca servizio..." />' );
        var categoryFilter = $( '<select><option value="">Tutte le categorie</option><option value="marketing">Marketing</option><option value="analytics">Analytics</option><option value="necessary">Necessari</option><option value="preferences">Preferenze</option></select>' );
        var statusFilter = $( '<select><option value="">Tutti gli stati</option><option value="detected">Rilevati</option><option value="not-detected">Non rilevati</option></select>' );
        
        filterRow.append( searchInput, categoryFilter, statusFilter );
        detectedTable.before( filterRow );
        
        function filterTable() {
            var searchTerm = searchInput.val().toLowerCase();
            var categoryValue = categoryFilter.val().toLowerCase();
            var statusValue = statusFilter.val();
            
            detectedTable.find( 'tbody tr' ).each( function() {
                var row = $( this );
                var serviceName = row.find( 'td' ).eq( 0 ).text().toLowerCase();
                var category = row.find( 'td' ).eq( 1 ).text().toLowerCase();
                var hasDetected = row.find( '.status-detected' ).length > 0;
                
                var matchSearch = serviceName.indexOf( searchTerm ) !== -1;
                var matchCategory = ! categoryValue || category.indexOf( categoryValue ) !== -1;
                var matchStatus = ! statusValue || 
                    ( statusValue === 'detected' && hasDetected ) || 
                    ( statusValue === 'not-detected' && ! hasDetected );
                
                if ( matchSearch && matchCategory && matchStatus ) {
                    row.show();
                } else {
                    row.hide();
                }
            });
        }
        
        searchInput.on( 'input', filterTable );
        categoryFilter.on( 'change', filterTable );
        statusFilter.on( 'change', filterTable );
        
        // Aggiungi badge alle categorie nella tabella
        detectedTable.find( 'tbody tr' ).each( function() {
            var row = $( this );
            var categoryCell = row.find( 'td' ).eq( 1 );
            var categoryText = categoryCell.text().trim().toLowerCase();
            
            if ( categoryText ) {
                var badge = $( '<span class="fp-privacy-category-badge"></span>' )
                    .addClass( categoryText )
                    .text( categoryText );
                categoryCell.html( badge );
            }
        });
    }
    
    // Sistema accordion per organizzare le sezioni
    function initAccordion() {
        var sections = [
            { id: 'languages', title: '🌐 Lingue', selector: 'h2:contains("Languages"), h2:contains("Lingue")' },
            { id: 'banner', title: '📢 Contenuto Banner', selector: 'h2:contains("Banner content"), h2:contains("Banner")' },
            { id: 'preview', title: '👁️ Anteprima', selector: '.fp-privacy-preview' },
            { id: 'layout', title: '🎨 Layout', selector: 'h2:contains("Layout")' },
            { id: 'palette', title: '🎨 Palette', selector: 'h2:contains("Palette")' },
            { id: 'consent-mode', title: '⚙️ Consent Mode', selector: 'h2:contains("Consent Mode")' },
            { id: 'gpc', title: '🌍 GPC', selector: 'h2:contains("Global Privacy Control"), h2:contains("GPC")' },
            { id: 'retention', title: '📅 Retention', selector: 'h2:contains("Retention")' },
            { id: 'controller', title: '🏢 Controller & DPO', selector: 'h2:contains("Controller"), h2:contains("DPO")' },
            { id: 'alerts', title: '🔔 Alerts', selector: 'h2:contains("Integration alerts"), h2:contains("alert")' },
            { id: 'scripts', title: '🚫 Script Blocking', selector: 'h2:contains("Script blocking"), h2:contains("Script")' }
        ];
        
        sections.forEach( function( section ) {
            var sectionElement = $( section.selector ).first();
            if ( ! sectionElement.length ) return;
            
            // Wrap section in accordion
            var nextElements = sectionElement.nextUntil( 'h2' ).addBack();
            if ( nextElements.length === 1 ) {
                nextElements = sectionElement.nextUntil( 'h2, .fp-privacy-preview' ).addBack();
            }
            
            var accordion = $( '<div class="fp-privacy-section-accordion" id="section-' + section.id + '"></div>' );
            var header = $( '<div class="fp-privacy-section-header"><h3>' + section.title + '</h3><span class="fp-privacy-section-toggle">▼</span></div>' );
            var content = $( '<div class="fp-privacy-section-content"></div>' );
            
            nextElements.wrapAll( content );
            content = sectionElement.nextUntil( 'h2' ).parent();
            
            sectionElement.before( accordion );
            accordion.append( header );
            
            // Sposta contenuto nell'accordion
            var elementsToMove = sectionElement.nextUntil( 'h2, .fp-privacy-section-accordion' );
            if ( section.selector === '.fp-privacy-preview' ) {
                elementsToMove = sectionElement;
            }
            
            var contentWrapper = $( '<div class="fp-privacy-section-content"></div>' );
            accordion.append( contentWrapper );
            contentWrapper.append( sectionElement );
            contentWrapper.append( elementsToMove );
            
            // Toggle accordion
            header.on( 'click', function() {
                accordion.toggleClass( 'collapsed' );
                
                // Salva stato in localStorage
                var collapsed = accordion.hasClass( 'collapsed' );
                localStorage.setItem( 'fp-privacy-section-' + section.id, collapsed ? 'collapsed' : 'expanded' );
            });
            
            // Ripristina stato da localStorage
            var savedState = localStorage.getItem( 'fp-privacy-section-' + section.id );
            if ( savedState === 'collapsed' ) {
                accordion.addClass( 'collapsed' );
            }
        });
        
        // Espandi/Collassa tutto
        var toggleAll = $( '<div style="margin-bottom:20px;"><button type="button" class="button" id="fp-expand-all">⬇️ Espandi tutto</button> <button type="button" class="button" id="fp-collapse-all">⬆️ Collassa tutto</button></div>' );
        form.before( toggleAll );
        
        $( '#fp-expand-all' ).on( 'click', function() {
            $( '.fp-privacy-section-accordion' ).removeClass( 'collapsed' );
        });
        
        $( '#fp-collapse-all' ).on( 'click', function() {
            $( '.fp-privacy-section-accordion' ).addClass( 'collapsed' );
        });
    }
    
    // Inizializza accordion se ci sono abbastanza sezioni
    if ( $( 'h2' ).length > 3 ) {
        setTimeout( initAccordion, 100 );
    }
} );
})( window.jQuery );
