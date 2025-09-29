(function ( blocks, element, i18n, components, blockEditor ) {
    var el = element.createElement;
    var Fragment = element.Fragment;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var TextControl = components.TextControl;
    var TextareaControl = components.TextareaControl;
    var ComboboxControl = components.ComboboxControl;

    var DEFAULT_LABEL = i18n.__( 'Manage cookie preferences', 'fp-privacy' );
    var DEFAULT_DESCRIPTION = i18n.__(
        'Opens the cookie preferences modal so visitors can review or update their consent.',
        'fp-privacy'
    );

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

    blocks.registerBlockType( 'fp-privacy/cookie-preferences', {
        title: i18n.__( 'FP Cookie Preferences', 'fp-privacy' ),
        icon: 'admin-settings',
        category: 'widgets',
        attributes: {
            label: {
                type: 'string',
                default: ''
            },
            description: {
                type: 'string',
                default: ''
            },
            lang: {
                type: 'string',
                default: ''
            }
        },
        edit: function ( props ) {
            var attrs = props.attributes || {};
            var label = attrs.label || '';
            var description = attrs.description || '';
            var lang = attrs.lang || '';
            var options = buildLanguageOptions( lang );
            var hasCombobox = typeof ComboboxControl !== 'undefined';
            var languageControl;
            var languages = getLanguages();
            var hasLanguages = !! languages.length;

            if ( hasCombobox ) {
                languageControl = el( ComboboxControl, {
                    label: i18n.__( 'Language', 'fp-privacy' ),
                    value: lang,
                    options: options,
                    onChange: function ( value ) {
                        var normalized = normalizeLang( value );
                        props.setAttributes( { lang: normalized } );
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
                        var languages = getLanguages();
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

            var previewLabel = label || DEFAULT_LABEL;
            var previewDescription = description || DEFAULT_DESCRIPTION;
            var previewLanguageLabel = lang
                ? i18n.sprintf( i18n.__( 'Language: %s', 'fp-privacy' ), lang )
                : i18n.__( 'Using the current site locale', 'fp-privacy' );

            return el(
                Fragment,
                {},
                el(
                    InspectorControls,
                    {},
                    el(
                        PanelBody,
                        {
                            title: i18n.__( 'Cookie preferences settings', 'fp-privacy' ),
                            initialOpen: true
                        },
                        el( TextControl, {
                            label: i18n.__( 'Button label', 'fp-privacy' ),
                            value: label,
                            placeholder: DEFAULT_LABEL,
                            onChange: function ( value ) {
                                props.setAttributes( { label: value } );
                            }
                        } ),
                        el( TextareaControl, {
                            label: i18n.__( 'Accessibility description', 'fp-privacy' ),
                            value: description,
                            placeholder: DEFAULT_DESCRIPTION,
                            help: i18n.__( 'Announced by assistive technologies when the button receives focus.', 'fp-privacy' ),
                            onChange: function ( value ) {
                                props.setAttributes( { description: value } );
                            }
                        } ),
                        languageControl
                    )
                ),
                el(
                    'div',
                    { className: 'fp-privacy-preferences-preview' },
                    el(
                        'div',
                        { className: 'fp-privacy-preferences-preview__header' },
                        i18n.__( 'Cookie preferences preview', 'fp-privacy' )
                    ),
                    el(
                        'p',
                        { className: 'fp-privacy-preferences-preview__description' },
                        i18n.__(
                            'The block renders a button that opens the cookie preferences modal so visitors can change their choices.',
                            'fp-privacy'
                        )
                    ),
                    el( 'div', { className: 'fp-privacy-preferences-preview__language' }, previewLanguageLabel ),
                    hasLanguages
                        ? null
                        : el(
                              'div',
                              { className: 'fp-privacy-preferences-preview__notice' },
                              i18n.__(
                                  'No additional languages are configured yet. Add them from the plugin settings to customize button text per locale.',
                                  'fp-privacy'
                              )
                          ),
                    el(
                        'div',
                        { className: 'fp-privacy-preferences-preview__button' },
                        previewLabel
                    ),
                    el(
                        'p',
                        { className: 'fp-privacy-preferences-preview__assistive' },
                        previewDescription
                    ),
                    el(
                        'ul',
                        { className: 'fp-privacy-preferences-preview__hints' },
                        el(
                            'li',
                            { className: 'fp-privacy-preferences-preview__hint' },
                            i18n.__(
                                'On the front-end the button receives focus styles that meet AA contrast requirements.',
                                'fp-privacy'
                            )
                        ),
                        el(
                            'li',
                            { className: 'fp-privacy-preferences-preview__hint' },
                            i18n.__(
                                'Screen readers announce the accessibility description before opening the modal.',
                                'fp-privacy'
                            )
                        ),
                        el(
                            'li',
                            { className: 'fp-privacy-preferences-preview__hint' },
                            i18n.__(
                                'The modal displays a summary of the last recorded consent for additional context.',
                                'fp-privacy'
                            )
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
