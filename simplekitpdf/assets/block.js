/**
 * Simple Kit PDF - Gutenberg block editor script.
 */
(function (wp) {
    'use strict';

    if (!wp || !wp.blocks || !wp.element || !wp.blockEditor || !wp.components) {
        return;
    }

    var el = wp.element.createElement;
    var __ = wp.i18n.__;
    var sprintf = wp.i18n.sprintf;

    var registerBlockType = wp.blocks.registerBlockType;
    var blockEditor = wp.blockEditor;
    var components = wp.components;

    var InspectorControls = blockEditor.InspectorControls;
    var useBlockProps = blockEditor.useBlockProps;
    var MediaUpload = blockEditor.MediaUpload;
    var MediaUploadCheck = blockEditor.MediaUploadCheck;

    var PanelBody = components.PanelBody;
    var Button = components.Button;
    var TextControl = components.TextControl;
    var RangeControl = components.RangeControl;
    var ToggleControl = components.ToggleControl;
    var SelectControl = components.SelectControl;
    var Placeholder = components.Placeholder;
    var Notice = components.Notice;

    var blockData = window.simplekitpdf_block_data || {};
    var siteUrl = blockData.siteUrl || window.location.origin;
    var siteHost = normalizeHost(blockData.siteHost || window.location.hostname);
    var defaultHeight = blockData.defaultHeight || 600;
    var minHeight = blockData.minHeight || 100;
    var maxHeight = blockData.maxHeight || 2000;

    function normalizeHost(host) {
        return String(host || '').toLowerCase().replace(/^www\./, '');
    }

    /**
     * Validates a PDF URL against the same-domain rule used by the PHP renderer.
     *
     * @param {string} value Raw URL or path.
     * @return {string} Empty string when valid, error message otherwise.
     */
    function validateUrl(value) {
        if (!value) {
            return '';
        }

        var parsed;
        try {
            parsed = new URL(value, siteUrl);
        } catch (error) {
            return __('Enter a valid URL.', 'simplekitpdf');
        }

        if (parsed.protocol !== 'http:' && parsed.protocol !== 'https:') {
            return __('Only http and https URLs are allowed.', 'simplekitpdf');
        }

        if (normalizeHost(parsed.hostname) !== siteHost) {
            return sprintf(
                /* translators: %s: site host name. */
                __('The PDF must be hosted on this site (%s).', 'simplekitpdf'),
                siteHost
            );
        }

        return '';
    }

    function mediaControl(props) {
        var attributes = props.attributes;

        return el(MediaUploadCheck, null,
            el(MediaUpload, {
                allowedTypes: ['application/pdf'],
                value: attributes.attachmentId || 0,
                onSelect: function (media) {
                    props.setAttributes({
                        attachmentId: media.id,
                        pdfUrl: media.url,
                        fileName: media.filename || media.title || '',
                        source: 'media'
                    });
                },
                render: function (open) {
                    return el('div', { className: 'simplekitpdf-media-control' },
                        el(Button, { variant: 'secondary', onClick: open.open },
                            attributes.attachmentId
                                ? __('Replace PDF', 'simplekitpdf')
                                : __('Select PDF', 'simplekitpdf')
                        ),
                        attributes.fileName
                            ? el('p', { className: 'simplekitpdf-file-name' }, attributes.fileName)
                            : el('p', { className: 'simplekitpdf-file-name' },
                                __('No PDF file selected yet. Files must be uploaded to the Media Library as PDF.', 'simplekitpdf')
                            )
                    );
                }
            })
        );
    }

    function settingsSummary(attributes) {
        var parts = [];
        var heightMode = attributes.heightMode || 'fixed';

        if (heightMode === 'first-page') {
            parts.push(__('Height: first page', 'simplekitpdf'));
        } else if (heightMode === 'full-document') {
            parts.push(__('Height: full document', 'simplekitpdf'));
        } else {
            parts.push(sprintf(
                /* translators: %s: height in pixels. */
                __('Height: %s px', 'simplekitpdf'),
                attributes.height || defaultHeight
            ));
        }

        parts.push(attributes.showToolbar
            ? __('Toolbar: visible', 'simplekitpdf')
            : __('Toolbar: hidden', 'simplekitpdf'));

        if (attributes.showToolbar) {
            parts.push(attributes.allowDownload
                ? __('Download: allowed', 'simplekitpdf')
                : __('Download: blocked', 'simplekitpdf'));
        }

        return parts.join(' \u00b7 ');
    }

    registerBlockType('simplekitpdf/pdf', {
        apiVersion: 3,
        title: __('Simple Kit PDF', 'simplekitpdf'),
        description: __('Embeds a PDF file rendered with pdf.js.', 'simplekitpdf'),
        icon: 'media-document',
        category: 'media',
        keywords: [
            __('pdf', 'simplekitpdf'),
            __('document', 'simplekitpdf'),
            __('embed', 'simplekitpdf')
        ],
        attributes: {
            source: {
                type: 'string',
                default: 'media'
            },
            attachmentId: {
                type: 'number',
                default: 0
            },
            pdfUrl: {
                type: 'string',
                default: ''
            },
            fileName: {
                type: 'string',
                default: ''
            },
            height: {
                type: 'number',
                default: defaultHeight
            },
            heightMode: {
                type: 'string',
                default: 'fixed'
            },
            showToolbar: {
                type: 'boolean',
                default: false
            },
            allowDownload: {
                type: 'boolean',
                default: false
            }
        },
        supports: {
            html: false,
            align: false
        },

        edit: function (props) {
            var attributes = props.attributes;
            var height = attributes.height || defaultHeight;
            var urlError = attributes.source === 'url' ? validateUrl(attributes.pdfUrl) : '';

            var sourcePanel = el(PanelBody, { title: __('PDF source', 'simplekitpdf'), initialOpen: true },
                el(SelectControl, {
                    label: __('Source', 'simplekitpdf'),
                    value: attributes.source,
                    options: [
                        { label: __('Media Library', 'simplekitpdf'), value: 'media' },
                        { label: __('Manual URL', 'simplekitpdf'), value: 'url' }
                    ],
                    onChange: function (value) {
                        props.setAttributes({ source: value });
                    }
                }),
                attributes.source === 'media'
                    ? mediaControl(props)
                    : el('div', null,
                        el(TextControl, {
                            label: __('PDF URL', 'simplekitpdf'),
                            value: attributes.pdfUrl,
                            placeholder: siteUrl + 'files/document.pdf',
                            help: __('Use a PDF hosted on this site. Relative paths are resolved against the site URL.', 'simplekitpdf'),
                            onChange: function (value) {
                                props.setAttributes({ pdfUrl: value });
                            }
                        }),
                        urlError ? el(Notice, { status: 'warning', isDismissible: false }, urlError) : null
                    ),
                attributes.pdfUrl
                    ? el(Button, {
                        variant: 'link',
                        isDestructive: true,
                        onClick: function () {
                            props.setAttributes({
                                attachmentId: 0,
                                pdfUrl: '',
                                fileName: ''
                            });
                        }
                    }, __('Remove PDF', 'simplekitpdf'))
                    : null
            );

            var heightMode = attributes.heightMode || 'fixed';
            var isFixedHeight = heightMode === 'fixed';

            var heightControl = isFixedHeight
                ? el(RangeControl, {
                    label: __('Fixed height (px)', 'simplekitpdf'),
                    value: height,
                    min: minHeight,
                    max: maxHeight,
                    step: 10,
                    help: sprintf(
                        /* translators: 1: minimum height, 2: maximum height. */
                        __('Between %1$s and %2$s pixels. The width always follows the block width.', 'simplekitpdf'),
                        minHeight,
                        maxHeight
                    ),
                    onChange: function (value) {
                        props.setAttributes({ height: parseInt(value, 10) || defaultHeight });
                    }
                })
                : el('p', { className: 'simplekitpdf-height-note' },
                    __('The height is measured in the browser after the PDF loads, following the current zoom level. The fixed height value is used only while the document is loading.', 'simplekitpdf')
                );

            var displayPanel = el(PanelBody, { title: __('Display', 'simplekitpdf'), initialOpen: true },
                el(SelectControl, {
                    label: __('Height mode', 'simplekitpdf'),
                    value: heightMode,
                    options: [
                        { label: __('Fixed height', 'simplekitpdf'), value: 'fixed' },
                        { label: __('First page', 'simplekitpdf'), value: 'first-page' },
                        { label: __('Full document', 'simplekitpdf'), value: 'full-document' }
                    ],
                    onChange: function (value) {
                        props.setAttributes({ heightMode: value });
                    }
                }),
                heightControl,
                el(ToggleControl, {
                    label: __('Show toolbar', 'simplekitpdf'),
                    checked: !!attributes.showToolbar,
                    help: attributes.showToolbar
                        ? __('Page navigation, zoom controls and the download option are shown above the document.', 'simplekitpdf')
                        : __('The document is displayed without controls.', 'simplekitpdf'),
                    onChange: function (value) {
                        var next = { showToolbar: !!value };
                        if (!value) {
                            next.allowDownload = false;
                        }
                        props.setAttributes(next);
                    }
                }),
                attributes.showToolbar
                    ? el(ToggleControl, {
                        label: __('Allow download', 'simplekitpdf'),
                        checked: !!attributes.allowDownload,
                        help: __('Shows a download button in the toolbar. This is a display policy only: the file URL itself stays public.', 'simplekitpdf'),
                        onChange: function (value) {
                            props.setAttributes({ allowDownload: !!value });
                        }
                    })
                    : null
            );

            var inspector = el(InspectorControls, null, sourcePanel, displayPanel);

            var previewContent;

            if (!attributes.pdfUrl) {
                previewContent = el(Placeholder, {
                    icon: 'media-document',
                    label: __('Simple Kit PDF', 'simplekitpdf'),
                    instructions: __('Select a PDF from the Media Library or enter a URL in the block settings.', 'simplekitpdf')
                }, attributes.source === 'media'
                    ? mediaControl(props)
                    : el('p', { className: 'simplekitpdf-placeholder-hint' },
                        __('Enter the PDF URL in the block settings sidebar.', 'simplekitpdf'))
                );
            } else {
                previewContent = el('div', {
                    className: 'simplekitpdf-editor-preview',
                    style: {
                        height: height + 'px',
                        display: 'flex',
                        flexDirection: 'column',
                        alignItems: 'center',
                        justifyContent: 'center',
                        gap: '6px',
                        padding: '16px',
                        textAlign: 'center',
                        border: '1px dashed #72aee6',
                        borderRadius: '4px',
                        background: '#f0f6fc',
                        boxSizing: 'border-box',
                        width: '100%'
                    }
                },
                    el('span', {
                        className: 'dashicons dashicons-media-document',
                        style: { fontSize: '32px', width: '32px', height: '32px', color: '#2271b1' }
                    }),
                    el('strong', null, __('Simple Kit PDF', 'simplekitpdf')),
                    el('span', { className: 'simplekitpdf-editor-preview-name' },
                        attributes.fileName || attributes.pdfUrl
                    ),
                    el('span', { className: 'simplekitpdf-editor-preview-meta' },
                        settingsSummary(attributes)
                    ),
                    el('span', { className: 'simplekitpdf-editor-preview-note' },
                        __('The PDF is rendered with pdf.js on the frontend.', 'simplekitpdf')
                    )
                );
            }

            return el('div', useBlockProps({ className: 'simplekitpdf-block-editor' }),
                inspector,
                urlError ? el(Notice, { status: 'warning', isDismissible: false }, urlError) : null,
                previewContent
            );
        },

        save: function () {
            return null;
        }
    });
})(window.wp);
