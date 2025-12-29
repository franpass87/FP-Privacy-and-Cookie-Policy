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
    var message = $( '<p></p>' );
    var revision = $( '<div class="fp-privacy-revision-notice"></div>' ).hide();
    var linksWrapper = $( '<div class="fp-privacy-banner-links"></div>' );
    var privacyLink = $( '<a class="fp-privacy-link" target="_blank" rel="noopener noreferrer"></a>' ).hide();
    var cookieLink = $( '<a class="fp-privacy-link" target="_blank" rel="noopener noreferrer"></a>' ).hide();
    var buttons = $( '<div class="fp-privacy-banner-buttons"></div>' );
    var accept = $( '<button type="button" class="fp-privacy-button fp-privacy-button-primary"></button>' );
    var reject = $( '<button type="button" class="fp-privacy-button fp-privacy-button-primary"></button>' );
    var prefs = $( '<button type="button" class="fp-privacy-button fp-privacy-button-secondary"></button>' );

    linksWrapper.append( privacyLink, cookieLink );
    buttons.append( accept, reject, prefs );
    banner.append( title, message, revision, linksWrapper, buttons );
    container.empty().append( banner );

    return {
        banner: banner,
        title: title,
        message: message,
        revision: revision,
        linksWrapper: linksWrapper,
        privacyLink: privacyLink,
        cookieLink: cookieLink,
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
    // CHANGE TRACKING VARIABLES (definite prima del loro utilizzo)
    // ========================================
    var originalValues = {};
    var modifiedFields = new Set();
    var formSubmitted = false;
    
    // Funzione updateStickySaveButton - definita prima del suo utilizzo
    function updateStickySaveButton() {
        var stickySave = $( '.fp-privacy-sticky-save' );
        if ( ! stickySave.length ) return;
        
        // Mostra se ci sono modifiche non salvate
        if ( modifiedFields.size > 0 ) {
            stickySave.addClass( 'visible' );
        } else {
            // Verifica anche la visibilità del form per decidere se nascondere
            var scrollTop = $( window ).scrollTop();
            var formOffset = form.offset();
            
            if ( formOffset ) {
                var formBottom = formOffset.top + form.outerHeight();
                var windowHeight = $( window ).height();
                var isFormVisible = formOffset.top < ( scrollTop + windowHeight ) && formBottom > scrollTop;
                
                // Nascondi solo se il form è visibile (il bottone sticky non serve)
                if ( isFormVisible ) {
                    stickySave.removeClass( 'visible' );
                }
            } else {
                stickySave.removeClass( 'visible' );
            }
        }
    }
    
    // Debounce utility - definita prima del suo utilizzo
    function debounce( func, wait ) {
        var timeout;
        return function() {
            var context = this;
            var args = arguments;
            clearTimeout( timeout );
            timeout = setTimeout( function() {
                func.apply( context, args );
            }, wait );
        };
    }
    
    // ========================================
    // GESTIONE TABS
    // ========================================
    var tabButtons = $( '.fp-privacy-tab-button' );
    var tabContents = $( '.fp-privacy-tab-content' );
    var tabMap = {
        '1': 'banner',
        '2': 'cookies',
        '3': 'privacy',
        '4': 'advanced'
    };
    
    // Funzione switchTab - deve essere definita prima del suo utilizzo
    function switchTab( tab ) {
        if ( ! tab ) {
            return;
        }
        
        // Rimuovi classe active da tutti
        tabButtons.removeClass( 'active' ).attr( 'aria-selected', 'false' );
        tabContents.removeClass( 'active' ).attr( 'aria-hidden', 'true' );
        
        // Aggiungi active al tab selezionato
        var $activeButton = tabButtons.filter( '[data-tab="' + tab + '"]' );
        var $activeContent = tabContents.filter( '[data-tab-content="' + tab + '"]' );
        
        if ( $activeButton.length ) {
            $activeButton.addClass( 'active' ).attr( 'aria-selected', 'true' );
        }
        
        if ( $activeContent.length ) {
            $activeContent.addClass( 'active' ).attr( 'aria-hidden', 'false' );
        }
        
        // Scroll to top dopo cambio tab
        var tabsNav = $( '.fp-privacy-tabs-nav' );
        if ( tabsNav.length && tabsNav.offset() ) {
            $( 'html, body' ).animate({ scrollTop: tabsNav.offset().top - 32 }, 300 );
        }
    }
    
    // Ripristina tab attiva dal localStorage
    var activeTab = localStorage.getItem( 'fpPrivacyActiveTab' ) || 'banner';
    if ( tabButtons.length && tabContents.length ) {
        switchTab( activeTab );
    }
    
    // Click sui pulsanti tab
    tabButtons.on( 'click', function( e ) {
        e.preventDefault();
        e.stopPropagation();
        var tab = $( this ).data( 'tab' );
        if ( tab ) {
            switchTab( tab );
            localStorage.setItem( 'fpPrivacyActiveTab', tab );
        }
    });
    
    // Keyboard navigation per tab buttons
    tabButtons.on( 'keydown', function( e ) {
        var currentIndex = tabButtons.index( $( this ) );
        var newIndex = currentIndex;
        
        if ( e.key === 'ArrowLeft' || e.key === 'ArrowRight' ) {
            e.preventDefault();
            if ( e.key === 'ArrowLeft' ) {
                newIndex = currentIndex > 0 ? currentIndex - 1 : tabButtons.length - 1;
            } else {
                newIndex = currentIndex < tabButtons.length - 1 ? currentIndex + 1 : 0;
            }
            tabButtons.eq( newIndex ).focus().trigger( 'click' );
        } else if ( e.key === 'Enter' || e.key === ' ' ) {
            e.preventDefault();
            $( this ).trigger( 'click' );
        }
    } );
    
    // ========================================
    // KEYBOARD NAVIGATION GLOBAL
    // ========================================
    $( document ).on( 'keydown', function( e ) {
        // Ctrl/Cmd + 1-4 per switch tab
        if ( ( e.ctrlKey || e.metaKey ) && e.key >= '1' && e.key <= '4' ) {
            e.preventDefault();
            var tab = tabMap[ e.key ];
            if ( tab ) {
                switchTab( tab );
                localStorage.setItem( 'fpPrivacyActiveTab', tab );
                tabButtons.filter( '[data-tab="' + tab + '"]' ).focus();
            }
        }
        
        // Ctrl/Cmd + S per salvare
        if ( ( e.ctrlKey || e.metaKey ) && e.key === 's' && ! e.target.matches( 'input, textarea' ) ) {
            e.preventDefault();
            form.trigger( 'submit' );
        }
        
        // Esc per chiudere modali/tooltip
        if ( e.key === 'Escape' ) {
            var openModal = $( '#fp-privacy-modal' );
            if ( openModal.length ) {
                openModal.find( '.fp-privacy-modal-close' ).trigger( 'click' );
            }
            // Chiudi tooltip aperti
            $( '.fp-help-icon-wrapper:hover .fp-help-tooltip' ).parent().trigger( 'mouseleave' );
        }
        
        // ? per mostrare shortcuts help
        if ( e.key === '?' && ! e.target.matches( 'input, textarea' ) ) {
            e.preventDefault();
            showKeyboardShortcutsHelp();
        }
        
        // Enter su submit button
        if ( e.key === 'Enter' && $( e.target ).is( 'button[type="submit"], input[type="submit"]' ) ) {
            // Submit già gestito dal form, ma assicuriamoci che funzioni
            if ( ! $( e.target ).closest( 'form' ).length ) {
                form.trigger( 'submit' );
            }
        }
    } );
    
    // ========================================
    // FOCUS MANAGEMENT
    // ========================================
    // Skip links
    if ( $( '.fp-privacy-settings' ).length ) {
        var skipLink = $( '<a href="#fp-privacy-settings-main" class="fp-skip-link">' + ( l10n.skipToContent || 'Skip to main content' ) + '</a>' );
        skipLink.css({
            position: 'absolute',
            top: '-40px',
            left: '6px',
            background: '#000',
            color: '#fff',
            padding: '8px',
            textDecoration: 'none',
            zIndex: 100000,
            borderRadius: '4px'
        } );
        
        skipLink.on( 'focus', function() {
            $( this ).css( 'top', '6px' );
        } ).on( 'blur', function() {
            $( this ).css( 'top', '-40px' );
        } );
        
        $( 'body' ).prepend( skipLink );
        $( '.fp-privacy-settings' ).attr( 'id', 'fp-privacy-settings-main' );
    }
    
    // Funzione per aggiornare badge tab
    function updateTabBadges() {
        tabButtons.each( function() {
            var $tab = $( this );
            var tabName = $tab.data( 'tab' );
            var $badge = $tab.find( '.fp-tab-badge' );
            var $content = tabContents.filter( '[data-tab-content="' + tabName + '"]' );
            
            // Reset badge
            $badge.removeClass( 'completed errors warnings new' ).hide();
            
            // Conta errori e warning nel tab
            var errorCount = $content.find( '.fp-form-field.has-error' ).length;
            var warningCount = $content.find( '.notice-warning' ).length;
            
            if ( errorCount > 0 ) {
                $badge.addClass( 'errors' ).text( errorCount ).show();
            } else if ( warningCount > 0 ) {
                $badge.addClass( 'warnings' ).text( warningCount ).show();
            }
            
            // TODO: Logica per "completato" - può essere implementata in base a criteri specifici
            // Esempio: se tutti i campi obbligatori sono compilati
            // var requiredFields = $content.find( '.fp-form-field [required]' ).length;
            // var filledFields = $content.find( '.fp-form-field [required]:not(:empty)' ).length;
            // if ( requiredFields > 0 && filledFields === requiredFields && errorCount === 0 ) {
            //     $badge.addClass( 'completed' ).show();
            // }
        } );
    }
    
    // La funzione debounce è già definita all'inizio del file
    
    // Aggiorna badge quando ci sono cambiamenti (debounced)
    form.on( 'input change', 'input, textarea, select', debounce( function() {
        updateTabBadges();
    }, 300 ) );
    
    // Aggiorna badge al caricamento
    updateTabBadges();
    
    // Funzione per mostrare help shortcuts
    function showKeyboardShortcutsHelp() {
        var helpContent = '<div class="fp-privacy-shortcuts-help">' +
            '<h3>' + ( l10n.shortcutsHelpTitle || 'Keyboard Shortcuts' ) + '</h3>' +
            '<ul>' +
            '<li><kbd>Ctrl/Cmd + 1</kbd> - ' + ( l10n.shortcutTabBanner || 'Switch to Banner tab' ) + '</li>' +
            '<li><kbd>Ctrl/Cmd + 2</kbd> - ' + ( l10n.shortcutTabCookies || 'Switch to Cookies tab' ) + '</li>' +
            '<li><kbd>Ctrl/Cmd + 3</kbd> - ' + ( l10n.shortcutTabPrivacy || 'Switch to Privacy tab' ) + '</li>' +
            '<li><kbd>Ctrl/Cmd + 4</kbd> - ' + ( l10n.shortcutTabAdvanced || 'Switch to Advanced tab' ) + '</li>' +
            '<li><kbd>Ctrl/Cmd + S</kbd> - ' + ( l10n.shortcutSave || 'Save settings' ) + '</li>' +
            '<li><kbd>Esc</kbd> - ' + ( l10n.shortcutClose || 'Close modals/tooltips' ) + '</li>' +
            '<li><kbd>?</kbd> - ' + ( l10n.shortcutHelp || 'Show this help' ) + '</li>' +
            '</ul>' +
            '</div>';
        
        window.fpPrivacyShowModal( helpContent, l10n.shortcutsHelpTitle || 'Keyboard Shortcuts' );
    }
    
    // ========================================
    // MODAL SYSTEM
    // ========================================
    window.fpPrivacyShowModal = function( content, title ) {
        // Rimuovi modal esistente se presente
        $( '#fp-privacy-modal' ).remove();
        
        var modal = $( '<div id="fp-privacy-modal" class="fp-privacy-modal" role="dialog" aria-labelledby="fp-privacy-modal-title" aria-modal="true"></div>' );
        var overlay = $( '<div class="fp-privacy-modal-overlay"></div>' );
        var modalContent = $( '<div class="fp-privacy-modal-content"></div>' );
        var modalHeader = $( '<div class="fp-privacy-modal-header"><h2 id="fp-privacy-modal-title">' + ( title || '' ) + '</h2><button type="button" class="fp-privacy-modal-close" aria-label="Close modal"><span class="dashicons dashicons-no-alt"></span></button></div>' );
        var modalBody = $( '<div class="fp-privacy-modal-body">' + content + '</div>' );
        
        modalContent.append( modalHeader, modalBody );
        modal.append( overlay, modalContent );
        $( 'body' ).append( modal );
        
        // Focus trap
        var focusableElements = modal.find( 'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])' );
        var firstFocusable = focusableElements.first();
        var lastFocusable = focusableElements.last();
        
        // Focus sul primo elemento
        setTimeout( function() {
            firstFocusable.focus();
        }, 100 );
        
        // Focus trap
        modal.on( 'keydown', function( e ) {
            if ( e.key === 'Tab' ) {
                if ( e.shiftKey ) {
                    if ( document.activeElement === firstFocusable[ 0 ] ) {
                        e.preventDefault();
                        lastFocusable.focus();
                    }
                } else {
                    if ( document.activeElement === lastFocusable[ 0 ] ) {
                        e.preventDefault();
                        firstFocusable.focus();
                    }
                }
            }
        } );
        
        // Close handlers
        function closeModal() {
            modal.addClass( 'fp-modal-closing' );
            setTimeout( function() {
                modal.remove();
            }, 300 );
        }
        
        overlay.on( 'click', closeModal );
        modal.find( '.fp-privacy-modal-close' ).on( 'click', closeModal );
        
        $( document ).on( 'keydown.fpPrivacyModal', function( e ) {
            if ( e.key === 'Escape' && modal.length ) {
                closeModal();
                $( document ).off( 'keydown.fpPrivacyModal' );
            }
        } );
        
        // Animate in
        setTimeout( function() {
            modal.addClass( 'fp-modal-visible' );
        }, 10 );
        
        return modal;
    };
    
    // ========================================
    // HELP SYSTEM - TOOLTIP AND MODAL
    // ========================================
    // Gestione "Learn more" nei tooltip
    $( document ).on( 'click', '.fp-help-learn-more', function( e ) {
        e.preventDefault();
        var $btn = $( this );
        var modalTitle = $btn.data( 'modal-title' ) || '';
        var modalContent = $btn.data( 'modal-content' ) || '';
        
        if ( modalContent ) {
            window.fpPrivacyShowModal( modalContent, modalTitle );
        }
    } );
    
    // ========================================
    // QUICK ACTIONS
    // ========================================
    // Reset modifiche (ripristina valori originali)
    $( '#fp-reset-changes' ).on( 'click', function() {
        if ( modifiedFields.size === 0 ) {
            fpPrivacyShowToast( 'Nessuna modifica da ripristinare', 'info' );
            return;
        }
        
        if ( confirm( 'Vuoi ripristinare tutte le modifiche non salvate?' ) ) {
            // Ripristina valori originali
            Object.keys( originalValues ).forEach( function( name ) {
                var $field = form.find( '[name="' + name + '"]' );
                if ( $field.length ) {
                    if ( $field.is( 'input[type="checkbox"], input[type="radio"]' ) ) {
                        $field.prop( 'checked', originalValues[ name ] === $field.val() || originalValues[ name ] === '1' );
                    } else {
                        $field.val( originalValues[ name ] );
                    }
                    $field.trigger( 'change' );
                }
            } );
            
            modifiedFields.clear();
            $( '.fp-field-modified' ).removeClass( 'fp-field-modified' );
            $( '.fp-modified-badge' ).remove();
            updateStickySaveButton();
            
            fpPrivacyShowToast( 'Modifiche ripristinate', 'success' );
        }
    } );
    
    // Reset a default (conferma via modal)
    $( '#fp-reset-default' ).on( 'click', function() {
        var confirmContent = '<p><strong>' + ( l10n.resetConfirmTitle || 'Attenzione!' ) + '</strong></p>' +
            '<p>' + ( l10n.resetConfirmMessage || 'Questa operazione ripristinerà tutte le impostazioni ai valori di default. Questa azione non può essere annullata.' ) + '</p>' +
            '<p>' + ( l10n.resetConfirmQuestion || 'Sei sicuro di voler continuare?' ) + '</p>';
        
        var modal = window.fpPrivacyShowModal(
            confirmContent,
            l10n.resetConfirmTitle || 'Reset a Default'
        );
        
        var confirmBtn = $( '<button type="button" class="button button-primary">' + ( l10n.resetConfirm || 'Sì, ripristina' ) + '</button>' );
        var cancelBtn = $( '<button type="button" class="button">' + ( l10n.cancel || 'Annulla' ) + '</button>' );
        var actionsDiv = $( '<div style="display:flex;gap:12px;margin-top:20px;"></div>' );
        
        actionsDiv.append( confirmBtn, cancelBtn );
        modal.find( '.fp-privacy-modal-body' ).append( actionsDiv );
        
        cancelBtn.on( 'click', function() {
            modal.remove();
        } );
        
        confirmBtn.on( 'click', function() {
            // TODO: Implementare reset a default tramite AJAX o form submit
            fpPrivacyShowToast( 'Reset a default non ancora implementato', 'warning' );
            modal.remove();
        } );
    } );
    
    // Scroll to preview
    $( '#fp-scroll-to-preview' ).on( 'click', function() {
        // Switch to banner tab se non già attivo
        var activeTab = localStorage.getItem( 'fpPrivacyActiveTab' ) || 'banner';
        if ( activeTab !== 'banner' ) {
            switchTab( 'banner' );
            localStorage.setItem( 'fpPrivacyActiveTab', 'banner' );
        }
        
        // Scroll to preview section
        var previewSection = $( '.fp-privacy-preview' );
        if ( previewSection.length ) {
            $( 'html, body' ).animate({
                scrollTop: previewSection.offset().top - 100
            }, 500 );
            previewSection.addClass( 'fp-preview-highlight' );
            setTimeout( function() {
                previewSection.removeClass( 'fp-preview-highlight' );
            }, 2000 );
        }
    } );
    
    // Gestione input HEX per la palette colori (solo input HEX, senza color picker)
    var inputTimer = null;
    
    // Funzione per aggiornare l'anteprima del colore
    function updateColorPreview( $input ) {
        var val = $input.val().trim();
        var $preview = $input.closest( '.fp-privacy-color-input-wrapper' ).find( '.fp-privacy-color-preview' );
        
        // Valida e normalizza il valore per l'anteprima
        if ( val.length > 0 && val[0] !== '#' ) {
            val = '#' + val;
        }
        
        // Supporta formato corto #RGB -> #RRGGBB per l'anteprima
        if ( val.length === 4 && /^#[0-9A-F]{3}$/i.test( val ) ) {
            val = '#' + val[1] + val[1] + val[2] + val[2] + val[3] + val[3];
        }
        
        // Aggiorna il colore dell'anteprima solo se valido
        if ( /^#[0-9A-F]{6}$/i.test( val ) || /^#[0-9A-F]{3}$/i.test( val ) ) {
            if ( /^#[0-9A-F]{3}$/i.test( val ) ) {
                // Converti formato corto per l'anteprima
                val = '#' + val[1] + val[1] + val[2] + val[2] + val[3] + val[3];
            }
            $preview.css( 'background-color', val );
        } else if ( val.length === 0 ) {
            $preview.css( 'background-color', '#000000' );
        }
    }
    
    // Inizializza le anteprime dei colori al caricamento
    $( '.fp-privacy-hex-input' ).each( function() {
        updateColorPreview( $( this ) );
    });
    
    $( '.fp-privacy-hex-input' ).on( 'paste input keyup', function( e ) {
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
        
        // Aggiorna anteprima colore in tempo reale
        updateColorPreview( $this );
        
        // Validazione e feedback
        var isValid = /^#[0-9A-F]{6}$/i.test( val );
        var isShortValid = /^#[0-9A-F]{3}$/i.test( val );
        
        if ( isValid || isShortValid ) {
            $this.addClass( 'hex-valid' ).css( 'border-color', '#10b981' );
            
            // Debounce aggiornamento preview
            inputTimer = setTimeout( function() {
                $this.trigger( 'input' );
                evaluateContrast();
                
                setTimeout( function() {
                    $this.removeClass( 'hex-valid' ).css( 'border-color', '' );
                }, 800 );
            }, e.type === 'paste' ? 50 : 300 );
            
        } else if ( val.length === 7 || val.length === 4 ) {
            $this.css( 'border-color', '#ef4444' );
        } else {
            $this.css( 'border-color', '' );
        }
    });
    
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

    form.on( 'input change', '.fp-privacy-hex-input', evaluateContrast );
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
            linkPrivacyPolicy: panel.find( '[data-field="link_privacy_policy"]' ).val() || '',
            linkCookiePolicy: panel.find( '[data-field="link_cookie_policy"]' ).val() || '',
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
            // Render message exactly as in the real banner (with innerHTML)
            preview.message.html( texts.message );
        } else {
            preview.message.text( l10n.previewEmpty || '' );
        }

        // Policy links - create two separate links like in the real banner
        var policyUrls = l10n.policyUrls || {};
        var hasPrivacyUrl = policyUrls.privacy && policyUrls.privacy.length > 0;
        var hasCookieUrl = policyUrls.cookie && policyUrls.cookie.length > 0;
        
        if ( hasPrivacyUrl && texts.linkPrivacyPolicy ) {
            preview.privacyLink.attr( 'href', policyUrls.privacy )
                .text( texts.linkPrivacyPolicy )
                .show();
        } else {
            preview.privacyLink.removeAttr( 'href' ).text( '' ).hide();
        }
        
        if ( hasCookieUrl && texts.linkCookiePolicy ) {
            preview.cookieLink.attr( 'href', policyUrls.cookie )
                .text( texts.linkCookiePolicy )
                .show();
        } else {
            preview.cookieLink.removeAttr( 'href' ).text( '' ).hide();
        }
        
        // Show links wrapper only if at least one link is visible
        if ( hasPrivacyUrl && texts.linkPrivacyPolicy || hasCookieUrl && texts.linkCookiePolicy ) {
            preview.linksWrapper.show();
        } else {
            preview.linksWrapper.hide();
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
    
    // ========================================
    // PREVIEW CONTROLS ENHANCEMENT
    // ========================================
    var previewFrame = $( '#fp-privacy-preview-frame' );
    var previewModeBtns = $( '.fp-preview-mode-btn' );
    
    // Desktop/Mobile toggle
    previewModeBtns.on( 'click', function() {
        var mode = $( this ).data( 'mode' );
        previewModeBtns.removeClass( 'active' );
        $( this ).addClass( 'active' );
        
        if ( mode === 'mobile' ) {
            previewFrame.addClass( 'mobile-mode device-frame' );
        } else {
            previewFrame.removeClass( 'mobile-mode device-frame' );
        }
        
        updatePreview();
    } );
    
    // Fullscreen preview
    $( '#fp-preview-fullscreen' ).on( 'click', function() {
        var previewBanner = $( '#fp-privacy-preview-banner' ).html();
        var fullscreenContent = '<div class="fp-fullscreen-preview-container">' +
            '<div class="fp-fullscreen-preview-banner">' + previewBanner + '</div>' +
            '</div>';
        
        window.fpPrivacyShowModal( fullscreenContent, 'Banner Preview - Fullscreen' );
        
        // Apply current preview mode styles
        var modal = $( '#fp-privacy-modal' );
        var modalBody = modal.find( '.fp-privacy-modal-body' );
        modalBody.css( 'padding', '0' );
        modal.find( '.fp-privacy-modal-content' ).css( 'max-width', '100%' ).css( 'width', '100%' ).css( 'height', '100vh' ).css( 'max-height', '100vh' ).css( 'border-radius', '0' );
        
        var previewContainer = modal.find( '.fp-fullscreen-preview-container' );
        previewContainer.css({
            'display': 'flex',
            'align-items': 'center',
            'justify-content': 'center',
            'min-height': '100vh',
            'background': '#f3f4f6',
            'padding': '40px'
        } );
    } );
    
    // Reset preview
    $( '#fp-preview-reset' ).on( 'click', function() {
        // Reset to default values (potrebbe richiedere chiamata AJAX per ottenere default)
        fpPrivacyShowToast( 'Reset preview: ricarica la pagina per vedere i valori di default', 'info' );
    } );

    updatePreview();
    
    // ========================================
    // TOAST NOTIFICATION SYSTEM (IMPROVED)
    // ========================================
    // Live region per screen readers
    if ( $( '#fp-privacy-live-region' ).length === 0 ) {
        var liveRegion = $( '<div id="fp-privacy-live-region" aria-live="polite" aria-atomic="true" class="screen-reader-text"></div>' );
        $( 'body' ).append( liveRegion );
    }
    
    function announceToScreenReader( message ) {
        var liveRegion = $( '#fp-privacy-live-region' );
        liveRegion.text( message );
        setTimeout( function() {
            liveRegion.text( '' );
        }, 1000 );
    }
    
    function initToastContainer() {
        if ( $( '#fp-privacy-toast-container' ).length === 0 ) {
            $( 'body' ).append( '<div id="fp-privacy-toast-container" class="fp-privacy-toast-container" aria-live="polite" aria-atomic="false"></div>' );
        }
        return $( '#fp-privacy-toast-container' );
    }
    
    window.fpPrivacyShowToast = function( message, type, title ) {
        type = type || 'info';
        var container = initToastContainer();
        
        var icons = {
            success: '✓',
            error: '✕',
            warning: '⚠',
            info: 'ℹ'
        };
        
        var liveRole = type === 'error' ? 'alert' : 'status';
        var toast = $( '<div class="fp-privacy-toast" role="' + liveRole + '" aria-live="' + ( type === 'error' ? 'assertive' : 'polite' ) + '" aria-atomic="true"></div>' ).addClass( type );
        
        var iconEl = $( '<div class="fp-privacy-toast-icon" aria-hidden="true"></div>' ).text( icons[ type ] || icons.info );
        var contentEl = $( '<div class="fp-privacy-toast-content"></div>' );
        
        if ( title ) {
            contentEl.append( $( '<div class="fp-privacy-toast-title"></div>' ).text( title ) );
        }
        
        contentEl.append( $( '<div class="fp-privacy-toast-message"></div>' ).text( message ) );
        
        var closeBtn = $( '<button type="button" class="fp-privacy-toast-close" aria-label="' + ( l10n.close || 'Close' ) + '">' )
            .html( '<span class="dashicons dashicons-no-alt" aria-hidden="true"></span>' )
            .on( 'click', function() {
                removeToast( toast );
            } );
        
        toast.append( iconEl, contentEl, closeBtn );
        container.append( toast );
        
        // Annuncia a screen reader
        var announcement = title ? title + ': ' + message : message;
        announceToScreenReader( announcement );
        
        // Trigger animation
        setTimeout( function() {
            toast.addClass( 'visible' );
        }, 10 );
        
        // Auto remove after 5 seconds
        var timeout = setTimeout( function() {
            removeToast( toast );
        }, 5000 );
        
        // Pause timeout on hover
        toast.on( 'mouseenter', function() {
            clearTimeout( timeout );
        } ).on( 'mouseleave', function() {
            timeout = setTimeout( function() {
                removeToast( toast );
            }, 3000 );
        } );
        
        return toast;
    };
    
    function removeToast( toast ) {
        toast.css( 'animation', 'fpToastSlideOut 0.3s cubic-bezier(0.16, 1, 0.3, 1)' );
        setTimeout( function() {
            toast.remove();
        }, 300 );
    }
    
    // Add slide out animation if not exists
    if ( ! $( '#fp-toast-animations' ).length ) {
        $( '<style id="fp-toast-animations"></style>' )
            .html( '@keyframes fpToastSlideOut { from { opacity: 1; transform: translateX(0); } to { opacity: 0; transform: translateX(100%); } }' )
            .appendTo( 'head' );
    }
    
    // ========================================
    // FORM VALIDATION IN REAL-TIME
    // ========================================
    function initFormValidation() {
        // Debounced validation per input frequenti
        var debouncedValidation = debounce( function( $input ) {
            var value = $input.val().trim();
            var $field = $input.closest( '.fp-form-field, label' );
            var fieldId = $input.attr( 'id' );
            var errorId = fieldId ? fieldId + '-error' : '';
            
            if ( ! value ) {
                $field.removeClass( 'has-error has-success' );
                $field.find( '.fp-form-error-message, .fp-form-success-message' ).remove();
                if ( errorId ) {
                    var currentDescribedBy = $input.attr( 'aria-describedby' ) || '';
                    $input.attr( 'aria-describedby', currentDescribedBy.replace( errorId, '' ).trim() ).removeAttr( 'aria-invalid' );
                }
                return;
            }
            
            if ( ! isValidEmail( value ) ) {
                $field.addClass( 'has-error' ).removeClass( 'has-success' );
                if ( errorId ) {
                    $input.attr( 'aria-describedby', ( $input.attr( 'aria-describedby' ) || '' ).replace( errorId, '' ).trim() + ' ' + errorId ).attr( 'aria-invalid', 'true' );
                }
                if ( ! $field.find( '.fp-form-error-message' ).length ) {
                    var errorMsg = $( '<span class="fp-form-error-message" id="' + errorId + '" role="alert" aria-live="polite">Inserire un indirizzo email valido</span>' );
                    $field.append( errorMsg );
                }
            } else {
                $field.removeClass( 'has-error' ).addClass( 'has-success' );
                $field.find( '.fp-form-error-message' ).remove();
                if ( errorId ) {
                    var currentDescribedBy = $input.attr( 'aria-describedby' ) || '';
                    $input.attr( 'aria-describedby', currentDescribedBy.replace( errorId, '' ).trim() ).attr( 'aria-invalid', 'false' );
                }
            }
        }, 300 );
        
        // Email validation (usa debounced per input, immediato per blur)
        form.on( 'input', 'input[type="email"]', function() {
            debouncedValidation( $( this ) );
        } );
        
        form.on( 'blur', 'input[type="email"]', function() {
            var $input = $( this );
            var value = $input.val().trim();
            var $field = $input.closest( '.fp-form-field, label' );
            var fieldId = $input.attr( 'id' );
            var errorId = fieldId ? fieldId + '-error' : '';
            
            if ( ! value ) {
                $field.removeClass( 'has-error has-success' );
                $field.find( '.fp-form-error-message, .fp-form-success-message' ).remove();
                if ( errorId ) {
                    var currentDescribedBy = $input.attr( 'aria-describedby' ) || '';
                    $input.attr( 'aria-describedby', currentDescribedBy.replace( errorId, '' ).trim() ).removeAttr( 'aria-invalid' );
                }
                return;
            }
            
            if ( ! isValidEmail( value ) ) {
                $field.addClass( 'has-error' ).removeClass( 'has-success' );
                if ( errorId ) {
                    $input.attr( 'aria-describedby', ( $input.attr( 'aria-describedby' ) || '' ).replace( errorId, '' ).trim() + ' ' + errorId ).attr( 'aria-invalid', 'true' );
                }
                if ( ! $field.find( '.fp-form-error-message' ).length ) {
                    var errorMsg = $( '<span class="fp-form-error-message" id="' + errorId + '" role="alert" aria-live="polite">Inserire un indirizzo email valido</span>' );
                    $field.append( errorMsg );
                }
            } else {
                $field.removeClass( 'has-error' ).addClass( 'has-success' );
                $field.find( '.fp-form-error-message' ).remove();
                if ( errorId ) {
                    var currentDescribedBy = $input.attr( 'aria-describedby' ) || '';
                    $input.attr( 'aria-describedby', currentDescribedBy.replace( errorId, '' ).trim() ).attr( 'aria-invalid', 'false' );
                }
            }
        } );
        
        // URL validation
        form.on( 'blur', 'input[name*="url"], input[name*="website"]', function() {
            var $input = $( this );
            var value = $input.val().trim();
            var $field = $input.closest( '.fp-form-field, label' );
            
            var fieldId = $input.attr( 'id' );
            var errorId = fieldId ? fieldId + '-error' : '';
            
            if ( value && ! isValidURL( value ) ) {
                $field.addClass( 'has-error' ).removeClass( 'has-success' );
                if ( errorId ) {
                    $input.attr( 'aria-describedby', ( $input.attr( 'aria-describedby' ) || '' ).replace( errorId, '' ).trim() + ' ' + errorId ).attr( 'aria-invalid', 'true' );
                }
                if ( ! $field.find( '.fp-form-error-message' ).length ) {
                    var errorMsg = $( '<span class="fp-form-error-message" id="' + errorId + '" role="alert" aria-live="polite">Inserire un URL valido</span>' );
                    $field.append( errorMsg );
                }
            } else if ( value ) {
                $field.removeClass( 'has-error' ).addClass( 'has-success' );
                $field.find( '.fp-form-error-message' ).remove();
                if ( errorId ) {
                    var currentDescribedBy = $input.attr( 'aria-describedby' ) || '';
                    $input.attr( 'aria-describedby', currentDescribedBy.replace( errorId, '' ).trim() ).attr( 'aria-invalid', 'false' );
                }
            }
        } );
        
        // Hex color validation (already handled, but enhance feedback)
        form.on( 'blur', '.fp-privacy-hex-input', function() {
            var $input = $( this );
            var value = $input.val().trim();
            var $field = $input.closest( '.fp-privacy-palette-item' );
            
            if ( value && isValidHexColor( value ) ) {
                $field.addClass( 'has-success' ).removeClass( 'has-error' );
            } else if ( value ) {
                $field.addClass( 'has-error' ).removeClass( 'has-success' );
            }
        } );
    }
    
    function isValidEmail( email ) {
        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test( email );
    }
    
    function isValidURL( url ) {
        try {
            new URL( url.startsWith( 'http' ) ? url : 'http://' + url );
            return true;
        } catch ( e ) {
            return false;
        }
    }
    
    function isValidHexColor( hex ) {
        return /^#([0-9A-F]{3}|[0-9A-F]{6})$/i.test( hex );
    }
    
    initFormValidation();
    
    // ========================================
    // CHANGE TRACKING & MODIFICATION INDICATORS
    // ========================================
    // Le variabili originalValues e modifiedFields sono già definite sopra
    
    function trackChanges() {
        // Store original values
        form.find( 'input, textarea, select' ).each( function() {
            var $field = $( this );
            var name = $field.attr( 'name' );
            if ( name ) {
                originalValues[ name ] = $field.val();
            }
        } );
        
    // Track changes (debounced per performance)
    var debouncedTrackChanges = debounce( function( $field ) {
        var name = $field.attr( 'name' );
        if ( ! name ) return;
        
        var currentValue = $field.val();
        var originalValue = originalValues[ name ];
        
        if ( currentValue !== originalValue ) {
            modifiedFields.add( name );
            $field.addClass( 'fp-field-modified' );
            
            // Add badge to tab if not already added
            var activeTab = $( '.fp-privacy-tab-button.active' );
            if ( activeTab.length && ! activeTab.find( '.fp-modified-badge' ).length ) {
                activeTab.append( '<span class="fp-modified-badge" title="Modifiche non salvate">●</span>' );
            }
        } else {
            modifiedFields.delete( name );
            $field.removeClass( 'fp-field-modified' );
            
            // Remove badge if no more modifications
            if ( modifiedFields.size === 0 ) {
                $( '.fp-modified-badge' ).remove();
            }
        }
        
        updateStickySaveButton();
    }, 200 );
    
    // Track changes
    form.on( 'input change', 'input, textarea, select', function() {
        var $field = $( this );
        debouncedTrackChanges( $field );
    } );
    
    // La funzione updateStickySaveButton è già definita sopra
    
    // Scroll handler per sticky button (debounced) - inizializzato dopo la definizione della funzione
    if ( $( '.fp-privacy-sticky-save' ).length && ! $( window ).data( 'fp-sticky-scroll-initialized' ) ) {
        var debouncedScrollHandler = debounce( function() {
            updateStickySaveButton();
        }, 150 );
        
        $( window ).on( 'scroll', debouncedScrollHandler );
        $( window ).data( 'fp-sticky-scroll-initialized', true );
        setTimeout( debouncedScrollHandler, 100 ); // Check iniziale
    }
    
    // Style modified fields
    if ( ! $( '#fp-modified-fields-style' ).length ) {
        $( '<style id="fp-modified-fields-style"></style>' )
            .html( '.fp-field-modified { border-left: 3px solid var(--fp-warning) !important; } .fp-modified-badge { color: var(--fp-warning); margin-left: 8px; font-size: 12px; }' )
            .appendTo( 'head' );
    }
    
    trackChanges();
    
    // Warn on navigation with unsaved changes
    // La variabile formSubmitted è già definita sopra
    form.on( 'submit', function() {
        formSubmitted = true;
        modifiedFields.clear();
        originalValues = {};
        $( '.fp-field-modified' ).removeClass( 'fp-field-modified' );
        $( '.fp-modified-badge' ).remove();
        updateStickySaveButton();
    } );
    
    $( window ).on( 'beforeunload', function( e ) {
        if ( modifiedFields.size > 0 && ! formSubmitted ) {
            e.preventDefault();
            e.returnValue = 'Hai modifiche non salvate. Vuoi davvero uscire?';
            return e.returnValue;
        }
    } );
    
    // ========================================
    // LOADING STATES
    // ========================================
    function createLoadingIndicator() {
        return $( '<div class="fp-privacy-saving-indicator visible"><div class="spinner"></div><span>Salvataggio in corso...</span></div>' );
    }
    
    // Intercetta submit form per mostrare indicatore
    form.on( 'submit', function( e ) {
        var $submitBtn = form.find( '.button-primary' );
        var $stickyBtn = $( '.fp-privacy-sticky-save .button-primary' );
        
        // Disable buttons
        $submitBtn.prop( 'disabled', true ).addClass( 'fp-saving' );
        $stickyBtn.prop( 'disabled', true ).addClass( 'fp-saving' );
        
        // Show loading indicator
        var loadingIndicator = createLoadingIndicator();
        $submitBtn.after( loadingIndicator );
        
        // Show toast notification
        setTimeout( function() {
            if ( ! formSubmitted ) {
                fpPrivacyShowToast( 'Impostazioni salvate con successo', 'success', 'Salvataggio completato' );
            }
        }, 500 );
    } );
    
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
            var toggleBtn = header.find( '.fp-privacy-section-toggle' ).first();
            if ( ! toggleBtn.length ) {
                toggleBtn = header;
            }
            
            toggleBtn.attr( 'aria-expanded', 'true' );
            header.attr( 'aria-expanded', 'true' );
            
            header.on( 'click', function() {
                var isCollapsed = accordion.hasClass( 'collapsed' );
                accordion.toggleClass( 'collapsed' );
                
                var newState = ! isCollapsed;
                toggleBtn.attr( 'aria-expanded', newState ? 'false' : 'true' );
                header.attr( 'aria-expanded', newState ? 'false' : 'true' );
                
                // Salva stato in localStorage
                localStorage.setItem( 'fp-privacy-section-' + section.id, newState ? 'collapsed' : 'expanded' );
            });
            
            // Ripristina stato aria-expanded
            var savedState = localStorage.getItem( 'fp-privacy-section-' + section.id );
            if ( savedState === 'collapsed' ) {
                toggleBtn.attr( 'aria-expanded', 'false' );
                header.attr( 'aria-expanded', 'false' );
            }
            
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
