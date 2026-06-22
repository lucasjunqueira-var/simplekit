/**
 * Simple Kit Mailing - Gutenberg Blocks
 * "Simple Kit Mailing Collect", "Simple Kit Mailing Unsubscribe" and "Simple Kit Mailing Confirm" blocks
 */
(function(wp) {
    var el       = wp.element.createElement;
    var register = wp.blocks.registerBlockType;
    var __       = wp.i18n.__;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody         = wp.components.PanelBody;
    var TextControl       = wp.components.TextControl;
    var TextareaControl   = wp.components.TextareaControl;
    var SelectControl     = wp.components.SelectControl;

    // -----------------------------------------------------------------------
    // Block: Simple Kit Mailing Collect
    // -----------------------------------------------------------------------
    register('simplekitmailing/collect', {
        title:       __('Simple Kit Mailing Collect', 'simplekitmailing'),
        description: __('Email collection form for newsletter signup.', 'simplekitmailing'),
        icon:        'email-alt',
        category:    'widgets',
        attributes:  {
            title: {
                type:    'string',
                default: __('Receive our news', 'simplekitmailing'),
            },
            list_id: {
                type:    'integer',
                default: 0,
            },
            custom_css: {
                type:    'string',
                default: '',
            },
        },

        edit: function(props) {
            var title      = props.attributes.title;
            var list_id    = props.attributes.list_id;
            var custom_css = props.attributes.custom_css;

            // Initialize custom_css with default if empty
            if (!custom_css && simplekitmailing_block_data && simplekitmailing_block_data.default_collect_css) {
                props.setAttributes({ custom_css: simplekitmailing_block_data.default_collect_css });
                custom_css = simplekitmailing_block_data.default_collect_css;
            }

            function onChangeTitle(newTitle) {
                props.setAttributes({ title: newTitle });
            }

            function onChangeListId(newListId) {
                props.setAttributes({ list_id: parseInt(newListId, 10) });
            }

            function onChangeCss(newCss) {
                props.setAttributes({ custom_css: newCss });
            }

            // Build list selector options
            var listOptions = [{ value: 0, label: __('Select a list', 'simplekitmailing') }];
            if (simplekitmailing_block_data && simplekitmailing_block_data.lists) {
                simplekitmailing_block_data.lists.forEach(function(list) {
                    listOptions.push({ value: list.value, label: list.label });
                });
            }

            // Determine selected list name for display
            var selectedListName = __('None', 'simplekitmailing');
            if (list_id > 0 && simplekitmailing_block_data && simplekitmailing_block_data.lists) {
                simplekitmailing_block_data.lists.forEach(function(list) {
                    if (list.value === list_id) {
                        selectedListName = list.label;
                    }
                });
            }

            return el('div', { className: 'simplekitmailing-collect-block' },
                el('div', { style: { padding: '20px', border: '1px dashed #ccc', textAlign: 'center' } },
                    el('h3', {}, title),
                    el('p', { style: { color: '#888' } }, __('[Email signup form]', 'simplekitmailing')),
                    el('p', { style: { color: '#888', fontSize: '12px' } },
                        __('Checkbox: I agree with the terms', 'simplekitmailing')
                    ),
                    el('p', { style: { color: '#888', fontSize: '12px' } },
                        __('Button: Subscribe', 'simplekitmailing')
                    ),
                    el('p', { style: { color: '#666', fontSize: '11px', marginTop: '10px' } },
                        __('Target list:', 'simplekitmailing') + ' ' + selectedListName
                    )
                ),
                el(InspectorControls, null,
                    el(PanelBody, { title: __('Settings', 'simplekitmailing'), initialOpen: true },
                        el(TextControl, {
                            label:   __('Block title', 'simplekitmailing'),
                            value:   title,
                            onChange: onChangeTitle,
                        }),
                        el(SelectControl, {
                            label:   __('Target list', 'simplekitmailing'),
                            value:   list_id,
                            options: listOptions,
                            onChange: onChangeListId,
                        })
                    ),
                    el(PanelBody, { title: __('Custom CSS', 'simplekitmailing'), initialOpen: false },
                        el(TextareaControl, {
                            label:   __('CSS code', 'simplekitmailing'),
                            help:    __('Customize the block appearance with CSS. Leave empty to use default styles.', 'simplekitmailing'),
                            value:   custom_css,
                            onChange: onChangeCss,
                            rows:    20,
                        })
                    )
                )
            );
        },

        save: function() {
            // Rendered via PHP (render_callback)
            return null;
        },
    });

    // -----------------------------------------------------------------------
    // Block: Simple Kit Mailing Unsubscribe
    // -----------------------------------------------------------------------
    register('simplekitmailing/unsubscribe', {
        title:       __('Simple Kit Mailing Unsubscribe', 'simplekitmailing'),
        description: __('Email unsubscribe block. Should be placed on the page configured in Simple Kit Mailing > Settings.', 'simplekitmailing'),
        icon:        'no',
        category:    'widgets',
        attributes:  {
            title: {
                type:    'string',
                default: __('Unsubscribe email', 'simplekitmailing'),
            },
            message: {
                type:    'string',
                default: __('Your email has been removed from our mailing list.', 'simplekitmailing'),
            },
            error_message: {
                type:    'string',
                default: __('The email address was not found in our mailing list.', 'simplekitmailing'),
            },
            custom_css: {
                type:    'string',
                default: '',
            },
        },

        edit: function(props) {
            var title         = props.attributes.title;
            var message       = props.attributes.message;
            var error_message = props.attributes.error_message;
            var custom_css    = props.attributes.custom_css;

            // Initialize custom_css with default if empty
            if (!custom_css && simplekitmailing_block_data && simplekitmailing_block_data.default_unsubscribe_css) {
                props.setAttributes({ custom_css: simplekitmailing_block_data.default_unsubscribe_css });
                custom_css = simplekitmailing_block_data.default_unsubscribe_css;
            }

            function onChangeTitle(newTitle) {
                props.setAttributes({ title: newTitle });
            }

            function onChangeMessage(newMessage) {
                props.setAttributes({ message: newMessage });
            }

            function onChangeError(newMessage) {
                props.setAttributes({ error_message: newMessage });
            }

            function onChangeCss(newCss) {
                props.setAttributes({ custom_css: newCss });
            }

            return el('div', { className: 'simplekitmailing-unsubscribe-block' },
                el('div', { style: { padding: '20px', border: '1px dashed #ccc', textAlign: 'center' } },
                    el('h3', {}, title),
                    el('p', { style: { color: '#888' } }, __('[Unsubscribe block - processes ?em=email parameter]', 'simplekitmailing'))
                ),
                el(InspectorControls, null,
                    el(PanelBody, { title: __('Settings', 'simplekitmailing'), initialOpen: true },
                        el(TextControl, {
                            label:   __('Block title', 'simplekitmailing'),
                            value:   title,
                            onChange: onChangeTitle,
                        }),
                        el(TextareaControl, {
                            label:   __('Confirmation message', 'simplekitmailing'),
                            value:   message,
                            onChange: onChangeMessage,
                        }),
                        el(TextareaControl, {
                            label:   __('Error message', 'simplekitmailing'),
                            help:    __('Shown when the email address is not found in the mailing list.', 'simplekitmailing'),
                            value:   error_message,
                            onChange: onChangeError,
                        })
                    ),
                    el(PanelBody, { title: __('Custom CSS', 'simplekitmailing'), initialOpen: false },
                        el(TextareaControl, {
                            label:   __('CSS code', 'simplekitmailing'),
                            help:    __('Customize the block appearance with CSS. Leave empty to use default styles.', 'simplekitmailing'),
                            value:   custom_css,
                            onChange: onChangeCss,
                            rows:    20,
                        })
                    )
                )
            );
        },

        save: function() {
            // Rendered via PHP (render_callback)
            return null;
        },
    });

    // -----------------------------------------------------------------------
    // Block: Simple Kit Mailing Confirm (double opt-in)
    // -----------------------------------------------------------------------
    register('simplekitmailing/confirm', {
        title:       __('Simple Kit Mailing Confirm', 'simplekitmailing'),
        description: __('Subscription confirmation block for double opt-in. Should be placed on the page configured in Simple Kit Mailing > Settings.', 'simplekitmailing'),
        icon:        'yes',
        category:    'widgets',
        attributes:  {
            title: {
                type:    'string',
                default: __('Confirm your subscription', 'simplekitmailing'),
            },
            success_message: {
                type:    'string',
                default: __('Your email has been confirmed! You are now subscribed to our mailing list.', 'simplekitmailing'),
            },
            error_message: {
                type:    'string',
                default: __('Invalid or expired confirmation link. Please try registering again.', 'simplekitmailing'),
            },
            removed_message: {
                type:    'string',
                default: __('This email is in our removed list and cannot be subscribed.', 'simplekitmailing'),
            },
            custom_css: {
                type:    'string',
                default: '',
            },
        },

        edit: function(props) {
            var title           = props.attributes.title;
            var success_message = props.attributes.success_message;
            var error_message   = props.attributes.error_message;
            var removed_message = props.attributes.removed_message;
            var custom_css      = props.attributes.custom_css;

            // Initialize custom_css with default if empty
            if (!custom_css && simplekitmailing_block_data && simplekitmailing_block_data.default_confirm_css) {
                props.setAttributes({ custom_css: simplekitmailing_block_data.default_confirm_css });
                custom_css = simplekitmailing_block_data.default_confirm_css;
            }

            function onChangeTitle(newTitle) {
                props.setAttributes({ title: newTitle });
            }

            function onChangeSuccess(newMessage) {
                props.setAttributes({ success_message: newMessage });
            }

            function onChangeError(newMessage) {
                props.setAttributes({ error_message: newMessage });
            }

            function onChangeRemoved(newMessage) {
                props.setAttributes({ removed_message: newMessage });
            }

            function onChangeCss(newCss) {
                props.setAttributes({ custom_css: newCss });
            }

            return el('div', { className: 'simplekitmailing-confirm-block' },
                el('div', { style: { padding: '20px', border: '1px dashed #ccc', textAlign: 'center' } },
                    el('h3', {}, title),
                    el('p', { style: { color: '#888' } }, __('[Confirmation block - processes ?sm_email, ?sm_code, ?list_id parameters]', 'simplekitmailing'))
                ),
                el(InspectorControls, null,
                    el(PanelBody, { title: __('Settings', 'simplekitmailing'), initialOpen: true },
                        el(TextControl, {
                            label:   __('Block title', 'simplekitmailing'),
                            value:   title,
                            onChange: onChangeTitle,
                        }),
                        el(TextareaControl, {
                            label:   __('Success message', 'simplekitmailing'),
                            value:   success_message,
                            onChange: onChangeSuccess,
                        }),
                        el(TextareaControl, {
                            label:   __('Error message', 'simplekitmailing'),
                            value:   error_message,
                            onChange: onChangeError,
                        }),
                        el(TextareaControl, {
                            label:   __('Removed message', 'simplekitmailing'),
                            value:   removed_message,
                            onChange: onChangeRemoved,
                        })
                    ),
                    el(PanelBody, { title: __('Custom CSS', 'simplekitmailing'), initialOpen: false },
                        el(TextareaControl, {
                            label:   __('CSS code', 'simplekitmailing'),
                            help:    __('Customize the block appearance with CSS. Leave empty to use default styles.', 'simplekitmailing'),
                            value:   custom_css,
                            onChange: onChangeCss,
                            rows:    20,
                        })
                    )
                )
            );
        },

        save: function() {
            // Rendered via PHP (render_callback)
            return null;
        },
    });
})(window.wp);
