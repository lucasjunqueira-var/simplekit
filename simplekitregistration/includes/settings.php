<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// Evaluator-facing texts.
//
// These are the texts shown on the external (restricted) pages used by the
// evaluators: login, form list, responses list, response details and messages.
// They are global (not per form) and can be edited in the Settings page.
// ---------------------------------------------------------------------------

function simplekitregistration_default_evaluator_texts() {
    return [
        // Login.
        'login_heading'        => __('Evaluator access', 'simplekitregistration'),
        'login_email_label'    => __('E-mail', 'simplekitregistration'),
        'login_password_label' => __('Password', 'simplekitregistration'),
        'login_button'         => __('Log in', 'simplekitregistration'),
        'login_error'          => __('Invalid e-mail or password. Please try again.', 'simplekitregistration'),

        // Form list.
        'forms_heading'        => __('Available forms', 'simplekitregistration'),
        /* translators: %s: evaluator e-mail address. */
        'forms_logged_in_as'   => __('Logged in as %s', 'simplekitregistration'),
        'logout_link'          => __('Log out', 'simplekitregistration'),
        'forms_empty'          => __('No forms are available for your account.', 'simplekitregistration'),

        // Responses list.
        /* translators: %s: form title. */
        'entries_heading'      => __('Responses: %s', 'simplekitregistration'),
        'back_to_forms'        => __('Back to forms', 'simplekitregistration'),
        'entries_empty'        => __('No responses received yet.', 'simplekitregistration'),
        'entries_col_id'       => __('ID', 'simplekitregistration'),
        'entries_col_datetime' => __('Date/Time', 'simplekitregistration'),
        'entries_col_details'  => __('Details', 'simplekitregistration'),
        /* translators: 1: current page, 2: total pages. */
        'pagination'           => __('Page %1$d of %2$d', 'simplekitregistration'),

        // Response details.
        'detail_title'         => __('Response details', 'simplekitregistration'),
        'detail_form_label'    => __('Form:', 'simplekitregistration'),
        /* translators: %d: response ID. */
        'detail_heading'       => __('Response #%d', 'simplekitregistration'),
        'detail_close'         => __('Close this tab', 'simplekitregistration'),

        // Messages.
        'messages_heading'     => __('Messages', 'simplekitregistration'),
        'messages_empty'       => __('No messages yet.', 'simplekitregistration'),
        'message_write_label'  => __('Write a message', 'simplekitregistration'),
        'message_send_button'  => __('Send message', 'simplekitregistration'),
        'message_error'        => __('Please write a message before sending.', 'simplekitregistration'),
    ];
}

/**
 * Return the evaluator texts, merged with the configured values.
 */
/**
 * Merge a saved option with its defaults. Empty values fall back to the
 * default text.
 */
function simplekitregistration_merge_texts($defaults, $option_name) {
    $saved = get_option($option_name, []);

    if (!is_array($saved)) {
        $saved = [];
    }

    foreach ($defaults as $key => $default) {
        $value = isset($saved[$key]) ? trim((string) $saved[$key]) : '';
        if ($value === '') {
            $saved[$key] = $default;
        }
    }

    return array_merge($defaults, $saved);
}

function simplekitregistration_get_evaluator_texts() {
    static $texts = null;

    if ($texts !== null) {
        return $texts;
    }

    $texts = simplekitregistration_merge_texts(
        simplekitregistration_default_evaluator_texts(),
        'simplekitregistration_evaluator_texts'
    );

    return $texts;
}

/**
 * Return a single evaluator text by key.
 */
function simplekitregistration_evaluator_text($key) {
    $texts = simplekitregistration_get_evaluator_texts();
    return isset($texts[$key]) ? $texts[$key] : '';
}

// ---------------------------------------------------------------------------
// Settings registration
// ---------------------------------------------------------------------------
add_action('admin_init', 'simplekitregistration_register_settings');

function simplekitregistration_register_settings() {
    register_setting('simplekitregistration_settings', 'simplekitregistration_evaluator_texts', [
        'type'              => 'array',
        'sanitize_callback' => 'simplekitregistration_sanitize_evaluator_texts',
        'default'           => [],
    ]);

    register_setting('simplekitregistration_settings', 'simplekitregistration_spreadsheet_texts', [
        'type'              => 'array',
        'sanitize_callback' => 'simplekitregistration_sanitize_spreadsheet_texts',
        'default'           => [],
    ]);
}

function simplekitregistration_sanitize_evaluator_texts($value) {
    $defaults = simplekitregistration_default_evaluator_texts();
    $clean    = [];

    if (!is_array($value)) {
        return $clean;
    }

    foreach ($defaults as $key => $default) {
        $raw = isset($value[$key]) ? $value[$key] : '';
        $clean[$key] = sanitize_text_field(wp_unslash($raw));
    }

    return $clean;
}

// ---------------------------------------------------------------------------
// Texts used in the export spreadsheets.
//
// These are the column headers and the row labels written to the CSV files
// generated by the Responses CSV export and by the individual form backup.
// ---------------------------------------------------------------------------

function simplekitregistration_default_spreadsheet_texts() {
    return [
        // Responses CSV (dashboard export).
        'responses_id'       => __('ID', 'simplekitregistration'),
        'responses_ip'       => __('IP', 'simplekitregistration'),
        'responses_datetime' => __('Date/Time', 'simplekitregistration'),

        // Individual form backup CSV.
        'backup_record'             => __('Record', 'simplekitregistration'),
        'backup_response_id'        => __('Response ID', 'simplekitregistration'),
        'backup_ip'                 => __('IP', 'simplekitregistration'),
        'backup_datetime_response'  => __('Date/Time (response)', 'simplekitregistration'),
        'backup_message_author'     => __('Message author', 'simplekitregistration'),
        'backup_message_email'      => __('Message author e-mail', 'simplekitregistration'),
        'backup_message_text'       => __('Message', 'simplekitregistration'),
        'backup_datetime_message'   => __('Date/Time (message)', 'simplekitregistration'),
        'backup_row_response'       => __('Response', 'simplekitregistration'),
        'backup_row_message'        => __('Message', 'simplekitregistration'),
    ];
}

function simplekitregistration_get_spreadsheet_texts() {
    static $texts = null;

    if ($texts !== null) {
        return $texts;
    }

    $texts = simplekitregistration_merge_texts(
        simplekitregistration_default_spreadsheet_texts(),
        'simplekitregistration_spreadsheet_texts'
    );

    return $texts;
}

/**
 * Return a single spreadsheet text by key.
 */
function simplekitregistration_spreadsheet_text($key) {
    $texts = simplekitregistration_get_spreadsheet_texts();
    return isset($texts[$key]) ? $texts[$key] : '';
}

function simplekitregistration_sanitize_spreadsheet_texts($value) {
    $defaults = simplekitregistration_default_spreadsheet_texts();
    $clean    = [];

    if (!is_array($value)) {
        return $clean;
    }

    foreach ($defaults as $key => $default) {
        $raw = isset($value[$key]) ? $value[$key] : '';
        $clean[$key] = sanitize_text_field(wp_unslash($raw));
    }

    return $clean;
}

// ---------------------------------------------------------------------------
// Page: Settings
// ---------------------------------------------------------------------------
function simplekitregistration_page_settings() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitregistration'));
    }

    $texts                = simplekitregistration_get_evaluator_texts();
    $defaults             = simplekitregistration_default_evaluator_texts();
    $spreadsheet_texts    = simplekitregistration_get_spreadsheet_texts();
    $spreadsheet_defaults = simplekitregistration_default_spreadsheet_texts();

    $groups = [
        'login' => [
            'option' => 'simplekitregistration_evaluator_texts',
            'title'  => __('Login', 'simplekitregistration'),
            'fields' => [
                'login_heading'        => __('Page heading', 'simplekitregistration'),
                'login_email_label'    => __('E-mail field label', 'simplekitregistration'),
                'login_password_label' => __('Password field label', 'simplekitregistration'),
                'login_button'         => __('Login button text', 'simplekitregistration'),
                'login_error'          => __('Invalid credentials message', 'simplekitregistration'),
            ],
        ],
        'forms' => [
            'option' => 'simplekitregistration_evaluator_texts',
            'title'  => __('Form list', 'simplekitregistration'),
            'fields' => [
                'forms_heading'      => __('Page heading', 'simplekitregistration'),
                'forms_logged_in_as' => __('Logged in as', 'simplekitregistration'),
                'logout_link'        => __('Log out link', 'simplekitregistration'),
                'forms_empty'        => __('Message when there are no forms', 'simplekitregistration'),
            ],
        ],
        'entries' => [
            'option' => 'simplekitregistration_evaluator_texts',
            'title'  => __('Responses list', 'simplekitregistration'),
            'fields' => [
                'entries_heading'      => __('Page heading', 'simplekitregistration'),
                'back_to_forms'        => __('Back link', 'simplekitregistration'),
                'entries_empty'        => __('Message when there are no responses', 'simplekitregistration'),
                'entries_col_id'       => __('ID column', 'simplekitregistration'),
                'entries_col_datetime' => __('Date/Time column', 'simplekitregistration'),
                'entries_col_details'  => __('Details link', 'simplekitregistration'),
                'pagination'           => __('Pagination label', 'simplekitregistration'),
            ],
        ],
        'detail' => [
            'option' => 'simplekitregistration_evaluator_texts',
            'title'  => __('Response details', 'simplekitregistration'),
            'fields' => [
                'detail_title'      => __('Page title', 'simplekitregistration'),
                'detail_form_label' => __('Form label', 'simplekitregistration'),
                'detail_heading'    => __('Response heading', 'simplekitregistration'),
                'detail_close'      => __('Close link', 'simplekitregistration'),
            ],
        ],
        'messages' => [
            'option' => 'simplekitregistration_evaluator_texts',
            'title'  => __('Messages', 'simplekitregistration'),
            'fields' => [
                'messages_heading'    => __('Section heading', 'simplekitregistration'),
                'messages_empty'      => __('Message when there are no messages', 'simplekitregistration'),
                'message_write_label' => __('Textarea label', 'simplekitregistration'),
                'message_send_button' => __('Send button', 'simplekitregistration'),
                'message_error'       => __('Empty message error', 'simplekitregistration'),
            ],
        ],
        'spreadsheets' => [
            'option' => 'simplekitregistration_spreadsheet_texts',
            'title'  => __('Export spreadsheets', 'simplekitregistration'),
            'fields' => [
                'responses_id'             => __('Responses CSV: ID column', 'simplekitregistration'),
                'responses_ip'             => __('Responses CSV: IP column', 'simplekitregistration'),
                'responses_datetime'       => __('Responses CSV: Date/Time column', 'simplekitregistration'),
                'backup_record'            => __('Form backup CSV: Record column', 'simplekitregistration'),
                'backup_response_id'       => __('Form backup CSV: Response ID column', 'simplekitregistration'),
                'backup_ip'                => __('Form backup CSV: IP column', 'simplekitregistration'),
                'backup_datetime_response' => __('Form backup CSV: response Date/Time column', 'simplekitregistration'),
                'backup_message_author'    => __('Form backup CSV: message author column', 'simplekitregistration'),
                'backup_message_email'     => __('Form backup CSV: message e-mail column', 'simplekitregistration'),
                'backup_message_text'      => __('Form backup CSV: message text column', 'simplekitregistration'),
                'backup_datetime_message'  => __('Form backup CSV: message Date/Time column', 'simplekitregistration'),
                'backup_row_response'      => __('Form backup CSV: label of the response rows', 'simplekitregistration'),
                'backup_row_message'       => __('Form backup CSV: label of the message rows', 'simplekitregistration'),
            ],
        ],
    ];

    $hints = [
        /* translators: %s: placeholder token. */
        'forms_logged_in_as' => __('Use %s for the evaluator e-mail address.', 'simplekitregistration'),
        /* translators: %s: placeholder token. */
        'entries_heading'    => __('Use %s for the form title.', 'simplekitregistration'),
        /* translators: %d: placeholder token. */
        'detail_heading'     => __('Use %d for the response ID.', 'simplekitregistration'),
        /* translators: 1: placeholder token for the current page, 2: placeholder token for the total pages. */
        'pagination'         => __('Use %1$d for the current page and %2$d for the total number of pages.', 'simplekitregistration'),
    ];
    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Settings', 'simplekitregistration'); ?></h1>
        <p class="description"><?php esc_html_e('Customize the texts displayed to the evaluators on the restricted pages of your site (login, form list, responses list, response details and messages) and the texts used in the export spreadsheets. These settings apply globally. Leave a field empty to restore its default text.', 'simplekitregistration'); ?></p>
        <hr class="wp-header-end">

        <?php settings_errors(); ?>

        <form method="post" action="options.php">
            <?php settings_fields('simplekitregistration_settings'); ?>

            <?php foreach ($groups as $group) : ?>
                <?php
                $group_option   = $group['option'];
                $group_texts    = ($group_option === 'simplekitregistration_spreadsheet_texts') ? $spreadsheet_texts : $texts;
                $group_defaults = ($group_option === 'simplekitregistration_spreadsheet_texts') ? $spreadsheet_defaults : $defaults;
                ?>
                <h2><?php echo esc_html($group['title']); ?></h2>
                <table class="form-table" role="presentation">
                    <?php foreach ($group['fields'] as $key => $label) : ?>
                        <tr>
                            <th scope="row"><label for="skr-text-<?php echo esc_attr($key); ?>"><?php echo esc_html($label); ?></label></th>
                            <td>
                                <input
                                    type="text"
                                    class="large-text"
                                    id="skr-text-<?php echo esc_attr($key); ?>"
                                    name="<?php echo esc_attr($group_option); ?>[<?php echo esc_attr($key); ?>]"
                                    value="<?php echo esc_attr($group_texts[$key] ?? ''); ?>"
                                    placeholder="<?php echo esc_attr($group_defaults[$key] ?? ''); ?>"
                                >
                                <?php if (isset($hints[$key])) : ?>
                                    <p class="description"><?php echo esc_html($hints[$key]); ?></p>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </table>
            <?php endforeach; ?>

            <?php submit_button(); ?>
        </form>
    </div>
    <?php
}
