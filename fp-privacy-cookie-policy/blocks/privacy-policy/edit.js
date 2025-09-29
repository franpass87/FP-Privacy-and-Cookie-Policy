(function ( blocks, element, i18n, components, blockEditor ) {
    var el = element.createElement;
    var Fragment = element.Fragment;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var TextControl = components.TextControl;
    var ComboboxControl = components.ComboboxControl;

    function getLanguages() {
        var source = window.fpPrivacyBlockData && window.fpPrivacyBlockData.languages;
        if ( ! source || ! source.length ) {
            return [];
        }

        return source
            .filter( function ( entry ) {
                return entry && entry.code;
            } )
            .map( function ( entry ) {
                return {
                    value: entry.code,
                    label: entry.label || entry.code,
                };
            } );
    }

    function buildLanguageOptions( current ) {
        var options = [
            {
                value: '',
                label: i18n.__( 'Inherit current locale', 'fp-privacy' ),
            },
        ];

        var languages = getLanguages();
        var hasCurrent = current === '';

        languages.forEach( function ( item ) {
            options.push( item );
            if ( item.value === current ) {
                hasCurrent = true;
            }
        } );

        if ( current && ! hasCurrent ) {
            options.push( { value: current, label: current } );
        }

        return options;
    }

    function normalizeLang( value ) {
        if ( ! value ) {
            return '';
        }

        return value.replace( /[^A-Za-z0-9_\-]/g, '' );
    }

    blocks.registerBlockType( 'fp-privacy/privacy-policy', {
        title: i18n.__( 'FP Privacy Policy', 'fp-privacy' ),
        icon: 'shield',
        category: 'widgets',
        edit: function ( props ) {
            var lang = props.attributes.lang || '';
            var options = buildLanguageOptions( lang );
            var hasCombobox = typeof ComboboxControl !== 'undefined';
            var languageControl;
            var languages = getLanguages();
            var hasLanguages = !! languages.length;
            var previewLanguageLabel = lang
                ? i18n.sprintf( i18n.__( 'Language: %s', 'fp-privacy' ), lang )
                : i18n.__( 'Using the current site locale', 'fp-privacy' );

            if ( hasCombobox ) {
                languageControl = el( ComboboxControl, {
                    label: i18n.__( 'Language', 'fp-privacy' ),
                    value: lang,
                    options: options,
                    onChange: function ( value ) {
                        props.setAttributes( { lang: normalizeLang( value ) } );
                    },
                    help: i18n.__( 'Pick one of the configured languages or type a custom code.', 'fp-privacy' ),
                } );
            } else {
                languageControl = el( TextControl, {
                    label: i18n.__( 'Language code', 'fp-privacy' ),
                    value: lang,
                    onChange: function ( value ) {
                        props.setAttributes( { lang: normalizeLang( value ) } );
                    },
                    help: ( function () {
                        if ( ! languages.length ) {
                            return i18n.__( 'Leave empty to use the current site locale.', 'fp-privacy' );
                        }

                        var readable = languages
                            .map( function ( item ) {
                                return item.label + ' (' + item.value + ')';
                            } )
                            .join( ', ' );

                        return (
                            i18n.__( 'Leave empty to use the current site locale.', 'fp-privacy' ) +
                            ' ' +
                            i18n.__( 'Available languages:', 'fp-privacy' ) +
                            ' ' +
                            readable
                        );
                    } )(),
                } );
            }

            return el(
                Fragment,
                {},
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        {
                            title: i18n.__( 'Privacy policy settings', 'fp-privacy' ),
                            initialOpen: true,
                        },
                        languageControl
                    )
                ),
                el(
                    'div',
                    { className: 'fp-privacy-policy-preview' },
                    el(
                        'div',
                        { className: 'fp-privacy-policy-preview__header' },
                        i18n.__( 'Privacy policy preview', 'fp-privacy' )
                    ),
                    el(
                        'p',
                        { className: 'fp-privacy-policy-preview__description' },
                        i18n.__(
                            'This block prints the generated privacy policy on the front-end. Configure the language to review locale-specific content while editing.',
                            'fp-privacy'
                        )
                    ),
                    el( 'div', { className: 'fp-privacy-policy-preview__language' }, previewLanguageLabel ),
                    hasLanguages
                        ? null
                        : el(
                              'div',
                              { className: 'fp-privacy-policy-preview__notice' },
                              i18n.__(
                                  'No additional languages are configured yet. Add them from the plugin settings to manage locale-specific policies.',
                                  'fp-privacy'
                              )
                          ),
                    el(
                        'ul',
                        { className: 'fp-privacy-policy-preview__sections' },
                        el(
                            'li',
                            { className: 'fp-privacy-policy-preview__section' },
                            i18n.__( 'Owner details, contact references, and data subject rights.', 'fp-privacy' )
                        ),
                        el(
                            'li',
                            { className: 'fp-privacy-policy-preview__section' },
                            i18n.__( 'Legal bases and processing purposes grouped by service categories.', 'fp-privacy' )
                        ),
                        el(
                            'li',
                            { className: 'fp-privacy-policy-preview__section' },
                            i18n.__( 'Automatic updates include detected services and consent revision notes.', 'fp-privacy' )
                        )
                    )
                )
            );
        },
        save: function () {
            return null;
        }
    } );
})(
    window.wp.blocks,
    window.wp.element,
    window.wp.i18n,
    window.wp.components,
    window.wp.blockEditor || window.wp.editor
);
