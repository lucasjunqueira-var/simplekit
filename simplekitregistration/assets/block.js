/**
 * Simple Kit Registration - Gutenberg blocks
 *  - simplekitregistration/form   : public form
 *  - simplekitregistration/access : restricted area for evaluators
 */
(function(wp) {
    'use strict';

    var el                = wp.element.createElement;
    var register          = wp.blocks.registerBlockType;
    var InspectorControls = wp.blockEditor.InspectorControls;
    var PanelBody         = wp.components.PanelBody;
    var SelectControl     = wp.components.SelectControl;
    var Placeholder       = wp.components.Placeholder;

    var data  = window.simplekitregistration_block_data || {};
    var forms = data.forms || [];

    var formOptions = [{ label: '-- Select form --', value: 0 }];
    for (var i = 0; i < forms.length; i++) {
        formOptions.push({ label: forms[i].title + ' (' + forms[i].code + ')', value: forms[i].id });
    }

    // -----------------------------------------------------------------------
    // Public form block
    // -----------------------------------------------------------------------
    register('simplekitregistration/form', {
        title:       'Simple Kit Registration Form',
        description: 'Displays a registration form created in Simple Kit Registration.',
        icon:        'feedback',
        category:    'widgets',
        attributes:  {
            form_id: { type: 'number', default: 0 }
        },

        edit: function(props) {
            var formId = props.attributes.form_id;
            var selected = null;
            for (var j = 0; j < forms.length; j++) {
                if (forms[j].id === formId) { selected = forms[j]; break; }
            }

            var preview = selected
                ? el('div', { style: { padding: '20px', border: '1px dashed #72aee6', borderRadius: '4px', background: '#f0f6fc' } },
                    el('p', { style: { margin: '0 0 5px 0' } }, el('strong', {}, 'Simple Kit Registration Form')),
                    el('p', { style: { margin: '0 0 5px 0' } }, 'Form: ' + selected.title),
                    el('p', { style: { margin: 0, color: '#666', fontSize: '12px' } }, 'Code: ' + selected.code),
                    el('p', { style: { margin: '5px 0 0 0', color: '#888', fontSize: '12px', fontStyle: 'italic' } }, '[The form will be rendered on the frontend]')
                )
                : el(Placeholder, {
                    icon: 'feedback',
                    label: 'Simple Kit Registration Form',
                    instructions: 'Select a form to display.'
                });

            return el('div', {},
                preview,
                el(InspectorControls, null,
                    el(PanelBody, { title: 'Form settings', initialOpen: true },
                        el(SelectControl, {
                            label: 'Select form',
                            value: formId,
                            options: formOptions,
                            onChange: function(newId) { props.setAttributes({ form_id: parseInt(newId, 10) }); }
                        })
                    )
                )
            );
        },

        save: function() { return null; }
    });

    // -----------------------------------------------------------------------
    // Restricted access block
    // -----------------------------------------------------------------------
    register('simplekitregistration/access', {
        title:       'Simple Kit Registration Access',
        description: 'Restricted area where evaluators log in to review the responses of the forms they can access.',
        icon:        'lock',
        category:    'widgets',
        attributes:  {},

        edit: function() {
            return el(Placeholder, {
                icon: 'lock',
                label: 'Simple Kit Registration Access',
                instructions: 'On the frontend this block shows a login form. After the evaluator logs in, it lists the forms they can access and their responses.'
            });
        },

        save: function() { return null; }
    });

})(window.wp);
