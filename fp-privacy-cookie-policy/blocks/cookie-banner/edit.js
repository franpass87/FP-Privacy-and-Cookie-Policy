(function ( blocks, element, i18n, components, blockEditor ) {
    var el = element.createElement;
    var Fragment = element.Fragment;
    var InspectorControls = blockEditor.InspectorControls;
    var PanelBody = components.PanelBody;
    var SelectControl = components.SelectControl;
    var TextControl = components.TextControl;
    var ToggleControl = components.ToggleControl;
    var ComboboxControl = components.ComboboxControl;
    var Button = components.Button;

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

    function getLanguageLabel( value ) {
        if ( ! value ) {
            return '';
        }

        var languages = getLanguages();

        for ( var index = 0; index < languages.length; index++ ) {
            if ( languages[ index ].value === value ) {
                return languages[ index ].label;
            }
        }

        return value;
    }

    function normalizeLang( value ) {
        if ( ! value ) {
            return '';
        }

        return value.replace( /[^A-Za-z0-9_\-]/g, '' );
    }

    function getBannerPreviewMap() {
        var data = window.fpPrivacyBlockData && window.fpPrivacyBlockData.bannerPreview;

        if ( ! data ) {
            return {};
        }

        return data;
    }

    function getBannerPreviewTexts( lang ) {
        var map = getBannerPreviewMap();
        var normalized = normalizeLang( lang );

        if ( normalized && map[ normalized ] ) {
            return map[ normalized ];
        }

        var keys = Object.keys( map );

        if ( keys.length ) {
            return map[ keys[ 0 ] ];
        }

        return null;
    }

    blocks.registerBlockType( 'fp-privacy/cookie-banner', {
        title: i18n.__( 'FP Cookie Banner', 'fp-privacy' ),
        icon: 'megaphone',
        category: 'widgets',
        attributes: {
            layoutType: {
                type: 'string',
                default: 'floating'
            },
            position: {
                type: 'string',
                default: 'bottom'
            },
            lang: {
                type: 'string',
                default: ''
            },
            forceDisplay: {
                type: 'boolean',
                default: false
            }
        },
        edit: function ( props ) {
            var attrs = props.attributes || {};
            var layoutType = attrs.layoutType || 'floating';
            var position = attrs.position || 'bottom';
            var lang = attrs.lang || '';
            var forceDisplay = !! attrs.forceDisplay;
            var options = buildLanguageOptions( lang );
            var hasCombobox = typeof ComboboxControl !== 'undefined';
            var languageControl;
            var languages = getLanguages();
            var hasLanguages = !! languages.length;
            var previewLangCode = lang ? normalizeLang( lang ) : '';
            var previewTexts = getBannerPreviewTexts( previewLangCode );
            var previewTitle = previewTexts && previewTexts.title ? previewTexts.title : i18n.__( 'Sample banner title', 'fp-privacy' );
            var previewMessage =
                previewTexts && previewTexts.message
                    ? previewTexts.message
                    : i18n.__( 'Update the banner texts from the plugin settings to preview them here.', 'fp-privacy' );
            var acceptLabel = previewTexts && previewTexts.accept ? previewTexts.accept : i18n.__( 'Accept', 'fp-privacy' );
            var rejectLabel = previewTexts && previewTexts.reject ? previewTexts.reject : i18n.__( 'Reject', 'fp-privacy' );
            var preferencesLabel = previewTexts && previewTexts.prefs ? previewTexts.prefs : i18n.__( 'Preferences', 'fp-privacy' );
            var hasPreviewTexts = !! previewTexts;

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
                    help: ( function () {
                        var languages = getLanguages();
                        if ( ! languages.length ) {
                            return i18n.__( 'Leave empty to match the current page locale.', 'fp-privacy' );
                        }

                        var readable = languages
                            .map( function ( item ) {
                                return item.label + ' (' + item.value + ')';
                            } )
                            .join( ', ' );

                        return (
                            i18n.__( 'Leave empty to match the current page locale.', 'fp-privacy' ) +
                            ' ' +
                            i18n.__( 'Available languages:', 'fp-privacy' ) +
                            ' ' +
                            readable
                        );
                    } )(),
                    onChange: function ( value ) {
                        props.setAttributes( { lang: normalizeLang( value ) } );
                    },
                } );
            }

            var previewClass = [ 'fp-privacy-banner-preview' ];

            if ( layoutType === 'bar' ) {
                previewClass.push( 'fp-privacy-banner-preview--bar' );
            } else {
                previewClass.push( 'fp-privacy-banner-preview--floating' );
            }

            if ( position === 'top' ) {
                previewClass.push( 'fp-privacy-banner-preview--top' );
            } else {
                previewClass.push( 'fp-privacy-banner-preview--bottom' );
            }

            var layoutLabel = layoutType === 'bar'
                ? i18n.__( 'Full-width bar', 'fp-privacy' )
                : i18n.__( 'Floating panel', 'fp-privacy' );
            var positionLabel = position === 'top'
                ? i18n.__( 'Top of the page', 'fp-privacy' )
                : i18n.__( 'Bottom of the page', 'fp-privacy' );
            var previewLanguageLabel = previewLangCode
                ? i18n.sprintf( i18n.__( 'Language: %s', 'fp-privacy' ), getLanguageLabel( previewLangCode ) )
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
                            title: i18n.__( 'Cookie banner settings', 'fp-privacy' ),
                            initialOpen: true
                        },
                        el( SelectControl, {
                            label: i18n.__( 'Layout', 'fp-privacy' ),
                            value: layoutType,
                            options: [
                                { label: i18n.__( 'Floating panel', 'fp-privacy' ), value: 'floating' },
                                { label: i18n.__( 'Full-width bar', 'fp-privacy' ), value: 'bar' }
                            ],
                            onChange: function ( value ) {
                                props.setAttributes( { layoutType: value === 'bar' ? 'bar' : 'floating' } );
                            }
                        } ),
                        el( SelectControl, {
                            label: i18n.__( 'Position', 'fp-privacy' ),
                            value: position,
                            options: [
                                { label: i18n.__( 'Bottom', 'fp-privacy' ), value: 'bottom' },
                                { label: i18n.__( 'Top', 'fp-privacy' ), value: 'top' }
                            ],
                            onChange: function ( value ) {
                                props.setAttributes( { position: value === 'top' ? 'top' : 'bottom' } );
                            }
                        } ),
                        languageControl,
                        el( ToggleControl, {
                            label: i18n.__( 'Always display this banner instance', 'fp-privacy' ),
                            checked: forceDisplay,
                            onChange: function ( value ) {
                                props.setAttributes( { forceDisplay: !! value } );
                            },
                            help: i18n.__( 'Force the banner to render even if the visitor has already provided consent.', 'fp-privacy' )
                        } ),
                        el( Button, {
                            variant: 'secondary',
                            onClick: function () {
                                props.setAttributes( {
                                    layoutType: 'floating',
                                    position: 'bottom',
                                    lang: '',
                                    forceDisplay: false
                                } );
                            },
                            className: 'fp-privacy-banner-preview__reset'
                        }, i18n.__( 'Reset to plugin defaults', 'fp-privacy' ) )
                    )
                ),
                el(
                    'div',
                    { className: previewClass.join( ' ' ) },
                    el( 'div', { className: 'fp-privacy-banner-preview__header' }, i18n.__( 'Cookie banner preview', 'fp-privacy' ) ),
                    el(
                        'p',
                        { className: 'fp-privacy-banner-preview__description' },
                        i18n.__(
                            'Use the inspector controls to review layout, position, and language overrides before publishing the banner.',
                            'fp-privacy'
                        )
                    ),
                    el(
                        'div',
                        { className: 'fp-privacy-banner-preview__content' },
                        el( 'p', { className: 'fp-privacy-banner-preview__title' }, previewTitle ),
                        el( 'p', { className: 'fp-privacy-banner-preview__message' }, previewMessage )
                    ),
                    ! hasPreviewTexts
                        ? el(
                              'div',
                              { className: 'fp-privacy-banner-preview__notice' },
                              i18n.__(
                                  'Save the plugin settings after editing banner content to preview the actual copy for each language.',
                                  'fp-privacy'
                              )
                          )
                        : null,
                    el(
                        'div',
                        { className: 'fp-privacy-banner-preview__chips' },
                        el( 'span', { className: 'fp-privacy-banner-preview__chip' }, layoutLabel ),
                        el( 'span', { className: 'fp-privacy-banner-preview__chip' }, positionLabel ),
                        el( 'span', { className: 'fp-privacy-banner-preview__chip fp-privacy-banner-preview__chip--language' }, previewLanguageLabel ),
                        forceDisplay
                            ? el(
                                  'span',
                                  { className: 'fp-privacy-banner-preview__chip fp-privacy-banner-preview__chip--warning' },
                                  i18n.__( 'Forced display enabled', 'fp-privacy' )
                              )
                            : null
                    ),
                    hasLanguages
                        ? null
                        : el(
                              'div',
                              { className: 'fp-privacy-banner-preview__notice' },
                              i18n.__(
                                  'No additional languages are configured yet. Add them from the plugin settings to customise the banner per locale.',
                                  'fp-privacy'
                              )
                          ),
                    forceDisplay
                        ? el(
                              'div',
                              { className: 'fp-privacy-banner-preview__alert' },
                              i18n.__(
                                  'Forced display bypasses the automatic hide behaviour. Confirm the banner placement remains accessible across devices.',
                                  'fp-privacy'
                              )
                          )
                        : null,
                    el(
                        'div',
                        { className: 'fp-privacy-banner-preview__buttons' },
                        el( 'span', { className: 'fp-privacy-banner-preview__button is-primary' }, acceptLabel ),
                        el( 'span', { className: 'fp-privacy-banner-preview__button' }, rejectLabel ),
                        el( 'span', { className: 'fp-privacy-banner-preview__button' }, preferencesLabel )
                    ),
                    el(
                        'ul',
                        { className: 'fp-privacy-banner-preview__hints' },
                        el(
                            'li',
                            { className: 'fp-privacy-banner-preview__hint' },
                            i18n.__(
                                'Floating panels anchor near a corner whereas bars stretch across the viewport edge.',
                                'fp-privacy'
                            )
                        ),
                        el(
                            'li',
                            { className: 'fp-privacy-banner-preview__hint' },
                            i18n.__(
                                'Language overrides let you review translated banner texts without switching the editor UI.',
                                'fp-privacy'
                            )
                        ),
                        el(
                            'li',
                            { className: 'fp-privacy-banner-preview__hint' },
                            i18n.__(
                                'Forced display keeps the banner visible when manually embedding it alongside other content.',
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
