( function( blocks, element, i18n ) {
    const { registerBlockType } = blocks;
    const { createElement: el } = element;
    const { __ } = i18n;

    const blockDefinitions = [
        {
            name: 'fp/privacy-policy',
            title: __( 'FP Privacy Policy', 'fp-privacy-cookie-policy' ),
            description: __( 'Visualizza il testo della privacy policy generato o personalizzato.', 'fp-privacy-cookie-policy' ),
            placeholder: __( 'Il contenuto della privacy policy sarà mostrato sul sito pubblico.', 'fp-privacy-cookie-policy' ),
            icon: 'shield-alt',
        },
        {
            name: 'fp/cookie-policy',
            title: __( 'FP Cookie Policy', 'fp-privacy-cookie-policy' ),
            description: __( 'Mostra la cookie policy aggiornata per gli utenti.', 'fp-privacy-cookie-policy' ),
            placeholder: __( 'La cookie policy aggiornata verrà resa sul front-end.', 'fp-privacy-cookie-policy' ),
            icon: 'list-view',
        },
        {
            name: 'fp/cookie-preferences',
            title: __( 'FP Gestisci preferenze cookie', 'fp-privacy-cookie-policy' ),
            description: __( 'Inserisci un pulsante per riaprire la finestra delle preferenze di consenso.', 'fp-privacy-cookie-policy' ),
            placeholder: __( 'In anteprima viene mostrato un pulsante di esempio.', 'fp-privacy-cookie-policy' ),
            icon: 'button',
        },
        {
            name: 'fp/cookie-banner',
            title: __( 'FP Banner cookie', 'fp-privacy-cookie-policy' ),
            description: __( 'Posiziona manualmente il banner cookie nelle tue pagine.', 'fp-privacy-cookie-policy' ),
            placeholder: __( 'Il banner verrà caricato sul front-end con le impostazioni del plugin.', 'fp-privacy-cookie-policy' ),
            icon: 'visibility',
        },
    ];

    blockDefinitions.forEach( function( block ) {
        registerBlockType( block.name, {
            title: block.title,
            description: block.description,
            icon: block.icon,
            category: 'widgets',
            supports: {
                html: false,
            },
            edit: function() {
                return el(
                    'div',
                    { className: 'fp-block-placeholder' },
                    block.placeholder
                );
            },
            save: function() {
                return null;
            },
        } );
    } );
} )( window.wp.blocks, window.wp.element, window.wp.i18n );
