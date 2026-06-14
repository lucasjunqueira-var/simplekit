/**
 * Simple Kit Mailing - Gutenberg Blocks
 * "Simple Kit Mailing Collect" and "Simple Kit Mailing Unsubscribe" blocks
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
        },

        edit: function(props) {
            var title   = props.attributes.title;
            var list_id = props.attributes.list_id;

            function onChangeTitle(newTitle) {
                props.setAttributes({ title: newTitle });
            }

            function onChangeListId(newListId) {
                props.setAttributes({ list_id: parseInt(newListId, 10) });
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
        },

        edit: function(props) {
            var title   = props.attributes.title;
            var message = props.attributes.message;

            function onChangeTitle(newTitle) {
                props.setAttributes({ title: newTitle });
            }

            function onChangeMessage(newMessage) {
                props.setAttributes({ message: newMessage });
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
        },

        edit: function(props) {
            var title           = props.attributes.title;
            var success_message = props.attributes.success_message;
            var error_message   = props.attributes.error_message;
            var removed_message = props.attributes.removed_message;

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
