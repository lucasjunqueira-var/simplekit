<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Shortcode para exibir formulário (alternativa ao bloco)
// ---------------------------------------------------------------------------
add_shortcode('simplekitforms', 'simplekitforms_shortcode');

function simplekitforms_shortcode($atts) {
    $atts = shortcode_atts(['id' => 0], $atts);
    $form_id = (int) $atts['id'];
    if ($form_id <= 0) {
        return '<p>Form ID not informed.</p>';
    }
    return simplekitforms_render_form($form_id);
}

// ---------------------------------------------------------------------------
// Default plugin CSS (used as initial value for blocks and fallback for shortcode)
// ---------------------------------------------------------------------------
function simplekitforms_get_default_css() {
    return '
        .simplekitforms-form-block {
            max-width: 600px;
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 8px;
            background: #f9f9f9;
        }
        .simplekitforms-form-block h3 {
            margin-top: 0;
            color: #333;
        }
        .simplekitforms-form-block .sf-field {
            margin-bottom: 16px;
        }
        .simplekitforms-form-block .sf-field label {
            display: block;
            margin-bottom: 4px;
            font-weight: 600;
        }
        .simplekitforms-form-block .sf-field label.sf-checkbox-label,
        .simplekitforms-form-block .sf-field label.sf-radio-label {
            font-weight: 400;
            display: flex;
            align-items: center;
            gap: 6px;
            margin-bottom: 2px;
        }
        .simplekitforms-form-block .sf-field .required-star {
            color: #d63638;
        }
        .simplekitforms-form-block .sf-field input[type="text"],
        .simplekitforms-form-block .sf-field input[type="email"],
        .simplekitforms-form-block .sf-field input[type="password"],
        .simplekitforms-form-block .sf-field input[type="url"],
        .simplekitforms-form-block .sf-field input[type="number"],
        .simplekitforms-form-block .sf-field input[type="tel"],
        .simplekitforms-form-block .sf-field textarea,
        .simplekitforms-form-block .sf-field select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }
        .simplekitforms-form-block .sf-field textarea {
            min-height: 100px;
        }
        .simplekitforms-form-block .sf-submit {
            background: #0073aa;
            color: #fff;
            border: none;
            padding: 12px 24px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        .simplekitforms-form-block .sf-submit:hover {
            background: #005a87;
        }
        .simplekitforms-form-block .sf-submit:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }
        .simplekitforms-form-block .sf-message {
            margin-top: 12px;
            padding: 10px 14px;
            border-radius: 4px;
            display: none;
        }
        .simplekitforms-form-block .sf-message.error {
            display: block;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .simplekitforms-form-block .sf-message.success {
            display: block;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .simplekitforms-form-block .sf-thank-you {
            padding: 20px;
            text-align: center;
            font-size: 16px;
            color: #155724;
            background: #d4edda;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
        }
    ';
}

// ---------------------------------------------------------------------------
// Registro do bloco Gutenberg "Simple Kit Form"
// ---------------------------------------------------------------------------
add_action('init', 'simplekitforms_register_block');

function simplekitforms_register_block() {
    wp_register_script(
        'simplekitforms-block',
        SIMPLEKITFORMS_PLUGIN_URL . 'assets/block.js',
        ['wp-blocks', 'wp-element', 'wp-i18n', 'wp-components', 'wp-block-editor'],
        SIMPLEKITFORMS_VERSION,
        true
    );

    // Passa a lista de formulários e o CSS padrão para o bloco
    $forms = simplekitforms_get_all_forms();
    $forms_data = [];
    foreach ($forms as $f) {
        $forms_data[] = [
            'id'    => (int) $f->id,
            'title' => esc_html($f->title),
        ];
    }

    wp_localize_script('simplekitforms-block', 'simplekitforms_block_data', [
        'forms'        => $forms_data,
        'ajax_url'     => admin_url('admin-ajax.php'),
        'rest_url'     => esc_url_raw(rest_url('simplekitforms/v1/submit')),
        'rest_nonce'   => wp_create_nonce('wp_rest'),
        'nonce'        => wp_create_nonce('simplekitforms_submit_nonce'),
        'default_css'  => simplekitforms_get_default_css(),
    ]);

    register_block_type('simplekitforms/form', [
        'editor_script'   => 'simplekitforms-block',
        'render_callback' => 'simplekitforms_render_block',
        'attributes'      => [
            'form_id' => [
                'type'    => 'number',
                'default' => 0,
            ],
            'custom_css' => [
                'type'    => 'string',
                'default' => '',
            ],
        ],
    ]);
}

// ---------------------------------------------------------------------------
// Renderização do bloco no frontend
// ---------------------------------------------------------------------------
function simplekitforms_render_block($attributes) {
    $form_id    = (int) ($attributes['form_id'] ?? 0);
    $custom_css = $attributes['custom_css'] ?? '';
    if ($form_id <= 0) {
        return '<p>Select a form in the block settings.</p>';
    }
    return simplekitforms_render_form($form_id, $custom_css);
}

// ---------------------------------------------------------------------------
// Renderizar formulário no frontend (usado pelo shortcode e bloco)
// ---------------------------------------------------------------------------
function simplekitforms_render_form($form_id, $custom_css = '') {
    $form = simplekitforms_get_form($form_id);
    if (!$form) {
        return '<p>Form not found.</p>';
    }

    $fields     = json_decode($form->fields_json, true) ?: [];
    $submit_text = !empty($form->submit_text) ? $form->submit_text : 'Submit';
    $thank_you  = !empty($form->thank_you_message)
        ? wp_kses_post($form->thank_you_message)
        : 'Thank you! Your form has been submitted successfully.';

    // Use custom CSS if provided, otherwise fall back to the default
    $css = !empty($custom_css) ? wp_strip_all_tags($custom_css) : simplekitforms_get_default_css();

    ob_start();

    $sf_protection = simplekitforms_get_protection();
    $sf_site_key   = simplekitforms_get_recaptcha_site_key();
    ?>
    <div class="simplekitforms-form-block" data-form-id="<?php echo (int) $form->id; ?>" data-rest-url="<?php echo esc_url(rest_url('simplekitforms/v1/submit')); ?>" data-rest-nonce="<?php echo esc_attr(wp_create_nonce('wp_rest')); ?>"<?php if ($sf_protection === 'recaptcha' && !empty($sf_site_key)) : ?> data-site-key="<?php echo esc_attr($sf_site_key); ?>"<?php endif; ?>>
        <style>
            <?php echo $css; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- CSS is either default (safe) or wp_strip_all_tags() sanitized. ?>
        </style>
        <div class="sf-form-wrapper">
            <h3><?php echo esc_html($form->title); ?></h3>
            <form class="simplekitforms-form" method="post">
                <?php foreach ($fields as $field) : ?>
                    <?php echo simplekitforms_render_frontend_field($field); ?>
                <?php endforeach; ?>
                <?php if ($sf_protection === 'recaptcha' && !empty($sf_site_key)) : ?>
                    <input type="hidden" name="sf_recaptcha_token" class="sf-recaptcha-token" value="" />
                <?php endif; ?>
                <div class="sf-field">
                    <button type="button" class="sf-submit"><?php echo esc_html($submit_text); ?></button>
                </div>
                <div class="sf-message"></div>
            </form>
        </div>
        <?php if ($sf_protection === 'recaptcha' && !empty($sf_site_key)) : wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js?render=' . urlencode($sf_site_key), array(), null, true); endif; ?>
        <script>
        /* <![CDATA[ */
        (function(){
            var c=document.querySelector('.simplekitforms-form-block[data-form-id="<?php echo (int) $form->id; ?>"]');
            if(!c){return}
            var b=c.querySelector('.sf-submit');
            if(!b){return}
            var f=c.querySelector('.simplekitforms-form');
            if(!f){return}
            var u=c.getAttribute('data-rest-url');
            var n=c.getAttribute('data-rest-nonce');
            var i=<?php echo (int) $form->id; ?>;
            var s=c.getAttribute('data-site-key'); // reCAPTCHA site key (if set)
            var recaptchaInput=c.querySelector('.sf-recaptcha-token');
            if(!u||!n){return}

            function doFetch() {
                var m=c.querySelector('.sf-message');
                var d=new FormData(f);
                d.append('form_id',i);
                d.append('nonce','<?php echo esc_js(wp_create_nonce('simplekitforms_submit_nonce')); ?>');
                b.disabled=true;
                b.textContent='Sending...';
                if(m){m.className='sf-message';m.textContent=''}
                fetch(u,{method:'POST',headers:{'X-WP-Nonce':n},body:d})
                .then(function(r){return r.json().then(function(d){return{status:r.status,data:d}})})
                .then(function(r){
                    var ok=true;
                    if(r.status<200){ok=false}
                    if(r.status>=300){ok=false}
                    if(!r.data){ok=false}
                    if(!r.data.success){ok=false}
                    if(ok){
                        var w=c.querySelector('.sf-form-wrapper');
                        if(w){w.innerHTML='<div class=sf-thank-you>'+r.data.message+'</div>'}
                    }else{
                        var g=[];
                        if(r.data){
                            if(r.data.data&&r.data.data.messages){g=r.data.data.messages}
                            else if(r.data.message){g=[r.data.message]}
                        }
                        if(g.length===0){g=['Error submitting. Please try again.']}
                        if(m){m.className='sf-message error';m.innerHTML=g.join('<br>')}
                    }
                })
                .catch(function(){
                    if(m){m.className='sf-message error';m.textContent='Connection error. Please try again.'}
                })
                .finally(function(){
                    b.disabled=false;
                    b.textContent='<?php echo esc_js($submit_text); ?>'
                })
            }

            b.addEventListener('click',function(e){
                e.preventDefault();
                // If reCAPTCHA is enabled, get token first
                if(s&&recaptchaInput&&typeof grecaptcha!=='undefined'){
                    grecaptcha.ready(function(){
                        grecaptcha.execute(s,{action:'submit'}).then(function(token){
                            recaptchaInput.value=token;
                            doFetch();
                        });
                    });
                }else{
                    doFetch();
                }
            })
        })();
        /* ]]> */
        </script>
    </div>
    <?php
    return ob_get_clean();
}

// ---------------------------------------------------------------------------
// Renderizar campo individual no frontend
// ---------------------------------------------------------------------------
function simplekitforms_render_frontend_field($field) {
    $type        = $field['type'] ?? 'text';
    $subtype     = $field['subtype'] ?? '';
    $label       = $field['label'] ?? '';
    $name        = $field['name'] ?? '';
    $required    = !empty($field['required']);
    $placeholder = $field['placeholder'] ?? '';
    $options     = $field['options'] ?? [];
    $required_mark = $required ? ' <span class="required-star">*</span>' : '';

    if (empty($name)) {
        return '';
    }

    $html = '<div class="sf-field">';

    switch ($type) {
        case 'text':
            $input_type = in_array($subtype, ['email', 'password', 'url', 'number', 'tel']) ? $subtype : 'text';
            $html .= '<label for="sf-' . esc_attr($name) . '">' . esc_html($label) . $required_mark . '</label>';
            $html .= '<input type="' . esc_attr($input_type) . '" id="sf-' . esc_attr($name) . '" name="' . esc_attr($name) . '" placeholder="' . esc_attr($placeholder) . '" ' . ($required ? 'required' : '') . '>';
            break;

        case 'textarea':
            $html .= '<label for="sf-' . esc_attr($name) . '">' . esc_html($label) . $required_mark . '</label>';
            $html .= '<textarea id="sf-' . esc_attr($name) . '" name="' . esc_attr($name) . '" placeholder="' . esc_attr($placeholder) . '" ' . ($required ? 'required' : '') . '></textarea>';
            break;

        case 'checkboxes':
            $html .= '<div><strong>' . esc_html($label) . '</strong>' . $required_mark . '</div>';
            foreach ($options as $opt) {
                $html .= '<label class="sf-checkbox-label">';
                $html .= '<input type="checkbox" name="' . esc_attr($name) . '[]" value="' . esc_attr($opt) . '">';
                $html .= ' ' . esc_html($opt);
                $html .= '</label>';
            }
            break;

        case 'radio':
            $html .= '<div><strong>' . esc_html($label) . '</strong>' . $required_mark . '</div>';
            foreach ($options as $opt) {
                $html .= '<label class="sf-radio-label">';
                $html .= '<input type="radio" name="' . esc_attr($name) . '" value="' . esc_attr($opt) . '" ' . ($required ? 'required' : '') . '>';
                $html .= ' ' . esc_html($opt);
                $html .= '</label>';
            }
            break;

        case 'select':
            $html .= '<label for="sf-' . esc_attr($name) . '">' . esc_html($label) . $required_mark . '</label>';
            $html .= '<select id="sf-' . esc_attr($name) . '" name="' . esc_attr($name) . '" ' . ($required ? 'required' : '') . '>';
            $html .= '<option value="">-- Select --</option>';
            foreach ($options as $opt) {
                $html .= '<option value="' . esc_attr($opt) . '">' . esc_html($opt) . '</option>';
            }
            $html .= '</select>';
            break;
    }

    $html .= '</div>';
    return $html;
}
