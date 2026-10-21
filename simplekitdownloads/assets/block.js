/**
 * Simple Kit Downloads - Gutenberg block
 */
(function (wp) {
    'use strict';

    var el                = wp.element.createElement;
    var register          = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody         = wp.components.PanelBody;
    var SelectControl     = wp.components.SelectControl;
    var TextControl       = wp.components.TextControl;
    var TextareaControl   = wp.components.TextareaControl;
    var Placeholder       = wp.components.Placeholder;

    var data              = window.simplekitdownloads_block_data || {};
    var downloads         = data.downloads || [];
    var defaultText       = data.default_text || 'Download';
    var defaultTextPeriod = data.default_text_version || 'Download v%version%';

    function findDownload(id) {
        for (var i = 0; i < downloads.length; i++) {
            if (downloads[i].id === id) {
                return downloads[i];
            }
        }
        return null;
    }

    var downloadOptions = [{ label: '-- Select download --', value: 0 }];
    for (var i = 0; i < downloads.length; i++) {
        downloadOptions.push({
            label: downloads[i].name,
            value: downloads[i].id,
        });
    }

    function buildLabel(attributes) {
        var text = (attributes.text || '').trim();
        if (text !== '') {
            return text;
        }
        if (!attributes.version || attributes.version === 'current') {
            return defaultText;
        }
        return defaultTextPeriod.replace('%version%', attributes.version);
    }

    register('simplekitdownloads/download', {
        title:       'Simple Kit Download',
        description: 'Displays a download button and records download statistics.',
        icon:        'download',
        category:    'widgets',
        attributes:  {
            download_id: {
                type:    'number',
                default: 0,
            },
            version: {
                type:    'string',
                default: 'current',
            },
            text: {
                type:    'string',
                default: '',
            },
            custom_css: {
                type:    'string',
                default: '',
            },
        },

        edit: function (props) {
            var attributes     = props.attributes;
            var downloadId     = parseInt(attributes.download_id, 10) || 0;
            var selected       = findDownload(downloadId);
            var versionOptions = selected
                ? selected.versions
                : [{ label: 'Current (latest)', value: 'current' }];
            var label          = buildLabel(attributes);

            function onChangeDownload(value) {
                props.setAttributes({
                    download_id: parseInt(value, 10) || 0,
                    version: 'current',
                });
            }

            function onChangeVersion(value) {
                props.setAttributes({ version: value });
            }

            function onChangeText(value) {
                props.setAttributes({ text: value });
            }

            function onChangeCss(value) {
                props.setAttributes({ custom_css: value });
            }

            var preview;
            if (!selected) {
                preview = el(Placeholder, {
                    icon: 'download',
                    label: 'Simple Kit Download',
                    instructions: downloads.length
                        ? 'Select a download in the block settings.'
                        : 'Create a download in Simple Kit Downloads first.',
                });
            } else {
                preview = el('div', {
                        style: {
                            padding: '16px',
                            border: '1px dashed #72aee6',
                            borderRadius: '4px',
                            background: '#f0f6fc',
                        },
                    },
                    el('div', {
                        style: { marginBottom: '8px', fontSize: '12px', color: '#0073aa' },
                    }, 'Simple Kit Download'),
                    el('span', {
                        className: 'simplekitdownloads-button',
                        style: {
                            display: 'inline-block',
                            padding: '10px 20px',
                            background: '#0073aa',
                            color: '#fff',
                            borderRadius: '4px',
                        },
                    }, label),
                    el('div', {
                        style: { marginTop: '8px', fontSize: '12px', color: '#646970' },
                    }, 'Download: ' + selected.name + ' | Version: ' + attributes.version)
                );
            }

            return el('div', {},
                preview,
                el(InspectorControls, null,
                    el(PanelBody, { title: 'Download settings', initialOpen: true },
                        el(SelectControl, {
                            label: 'Download',
                            value: downloadId,
                            options: downloadOptions,
                            onChange: onChangeDownload,
                        }),
                        el(SelectControl, {
                            label: 'Version',
                            help: 'Current always points to the latest version when the button is clicked.',
                            value: attributes.version || 'current',
                            options: versionOptions,
                            onChange: onChangeVersion,
                        }),
                        el(TextControl, {
                            label: 'Button text',
                            help: 'Leave empty to use the default text from Setup. For example: Download v%version%',
                            value: attributes.text || '',
                            onChange: onChangeText,
                        })
                    ),
                    el(PanelBody, { title: 'Custom CSS', initialOpen: false },
                        el('p', {
                            style: {
                                color: '#646970',
                                fontSize: '12px',
                                fontStyle: 'italic',
                                marginTop: 0,
                            },
                        }, 'These rules are printed with this block only and override the default styles.'),
                        el(TextareaControl, {
                            label: 'Custom CSS',
                            help: 'Enter CSS rules without <style> tags.',
                            value: attributes.custom_css || '',
                            onChange: onChangeCss,
                            rows: 10,
                        })
                    )
                )
            );
        },

        save: function () {
            return null;
        },
    });
})(window.wp);
