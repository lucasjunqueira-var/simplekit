/**
 * Simple Kit Forms - Gutenberg Block
 */
(function(wp) {
    var el              = wp.element.createElement;
    var register        = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody         = wp.components.PanelBody;
    var SelectControl     = wp.components.SelectControl;
    var TextareaControl   = wp.components.TextareaControl;
    var Placeholder       = wp.components.Placeholder;

    var data       = window.simplekitforms_block_data || {};
    var forms      = data.forms || [];
    var defaultCss = data.default_css || '';

    var formOptions = [{ label: '-- Select form --', value: 0 }];
    for (var i = 0; i < forms.length; i++) {
        formOptions.push({
            label: forms[i].title,
            value: forms[i].id,
        });
    }

    register('simplekitforms/form', {
        title:       'Simple Kit Form',
        description: 'Displays a form created in Simple Kit Forms.',
        icon:        'feedback',
        category:    'widgets',
        attributes:  {
            form_id: {
                type:    'number',
                default: 0,
            },
            custom_css: {
                type:    'string',
                default: defaultCss,
            },
        },

        edit: function(props) {
            var formId     = props.attributes.form_id;
            var customCss  = props.attributes.custom_css || '';
            var selectedForm = null;

            for (var j = 0; j < forms.length; j++) {
                if (forms[j].id === formId) {
                    selectedForm = forms[j];
                    break;
                }
            }

            function onChangeForm(newId) {
                props.setAttributes({ form_id: parseInt(newId, 10) });
            }

            function onChangeCss(value) {
                props.setAttributes({ custom_css: value });
            }

            var previewContent;
            if (formId && selectedForm) {
                previewContent = el('div', { style: { padding: '20px', border: '1px dashed #72aee6', borderRadius: '4px', background: '#f0f6fc' } },
                    el('div', { style: { display: 'flex', alignItems: 'center', gap: '8px', marginBottom: '10px' } },
                        el('span', { className: 'dashicons dashicons-feedback', style: { color: '#0073aa' } }),
                        el('strong', {}, 'Simple Kit Form')
                    ),
                    el('p', { style: { margin: '0 0 5px 0', fontSize: '14px' } },
                        'Form: ' + selectedForm.title
                    ),
                    el('p', { style: { margin: 0, color: '#666', fontSize: '12px' } },
                        'ID: ' + selectedForm.id
                    ),
                    el('p', { style: { margin: '5px 0 0 0', color: '#888', fontSize: '12px', fontStyle: 'italic' } },
                        '[The form will be rendered on the frontend]'
                    )
                );
            } else {
                previewContent = el(Placeholder, {
                    icon: 'feedback',
                    label: 'Simple Kit Form',
                    instructions: 'Select a form to display.',
                });
            }

            return el('div', {},
                previewContent,
                el(InspectorControls, null,
                    el(PanelBody, { title: 'Form settings', initialOpen: true },
                        el(SelectControl, {
                            label:   'Select form',
                            value:   formId,
                            options: formOptions,
                            onChange: onChangeForm,
                        })
                    ),
                    el(PanelBody, { title: 'Custom CSS', initialOpen: false },
                        el('p', { style: { color: '#666', fontSize: '12px', fontStyle: 'italic', marginTop: 0 } },
                            'Leave empty to use the plugin default CSS. Add your own CSS rules to override the default styling for this form.'
                        ),
                        el(TextareaControl, {
                            label:   'Custom CSS',
                            help:    'Enter CSS rules (without <style> tags). These will override the plugin default styles for this block instance.',
                            value:   customCss,
                            onChange: onChangeCss,
                            rows:    12,
                        })
                    )
                )
            );
        },

        save: function() {
            return null;
        },
    });

})(window.wp);
