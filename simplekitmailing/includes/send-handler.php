<?php
defined('ABSPATH') or exit;

// ---------------------------------------------------------------------------
// "Sends" page
// ---------------------------------------------------------------------------
function simplekitmailing_page_sends() {
    if (!current_user_can('manage_options')) {
        wp_die(esc_html__('Access denied.', 'simplekitmailing'));
    }

    global $wpdb;
    $table_messages = $wpdb->prefix . 'sm_messages';

    // Process pause/cancel/resume actions
    simplekitmailing_handle_send_actions();

    // Keep only the 20 most recent tasks in the list
    // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $messages = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM %i WHERE status != 'draft' ORDER BY created_at DESC LIMIT 20",
            $table_messages
        )
    );

    ?>
    <div class="wrap">
        <h1><?php esc_html_e('Messages', 'simplekitmailing'); ?></h1>

        <?php // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only notification after redirect.
        if (isset($_GET['msg'])) :
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only message type after redirect.
            $msg_type = isset($_GET['type']) ? sanitize_text_field(wp_unslash($_GET['type'])) : 'success';
            // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display-only message text after redirect.
            $msg_text = isset($_GET['msg']) ? sanitize_text_field(wp_unslash($_GET['msg'])) : '';
        ?>
            <div class="notice notice-<?php echo esc_attr($msg_type); ?> is-dismissible">
                <p><?php echo esc_html($msg_text); ?></p>
            </div>
        <?php endif; ?>

        <div id="sm-send-progress" style="display:none;margin-bottom:20px;">
            <div class="notice notice-info">
                <p><strong id="sm-progress-status"><?php esc_html_e('Processing message sending...', 'simplekitmailing'); ?></strong></p>
                <div style="background:#f1f1f1;border-radius:4px;padding:3px;margin-top:10px;">
                    <div id="sm-progress-bar" style="background:#0073aa;width:0%;height:20px;border-radius:3px;transition:width 0.5s;"></div>
                </div>
                <p id="sm-progress-text" style="font-size:12px;color:#666;margin-top:5px;"></p>
            </div>
        </div>

        <table class="wp-list-table widefat fixed striped" id="sm-sends-table">
            <thead>
                <tr>
                    <th scope="col"><?php esc_html_e('ID', 'simplekitmailing'); ?></th>
                    <th scope="col"><?php esc_html_e('Subject', 'simplekitmailing'); ?></th>
                    <th scope="col"><?php esc_html_e('List', 'simplekitmailing'); ?></th>
                    <th scope="col"><?php esc_html_e('Created at', 'simplekitmailing'); ?></th>
                    <th scope="col"><?php esc_html_e('Pending', 'simplekitmailing'); ?></th>
                    <th scope="col"><?php esc_html_e('Last sent', 'simplekitmailing'); ?></th>
                    <th scope="col"><?php esc_html_e('Status', 'simplekitmailing'); ?></th>
                    <th scope="col"><?php esc_html_e('Actions', 'simplekitmailing'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($messages)) : ?>
                    <tr><td colspan="8"><?php esc_html_e('No sends registered.', 'simplekitmailing'); ?></td></tr>
                <?php else : ?>
                    <?php foreach ($messages as $msg) :
                        $pending = $msg->total - $msg->sent;
                        $status_label = simplekitmailing_status_label($msg->status);
                        $list_name = $msg->list_id ? simplekitmailing_get_list_name($msg->list_id) : __('All', 'simplekitmailing');
                        ?>
                        <tr id="sm-send-row-<?php echo (int) $msg->id; ?>">
                            <td><?php echo (int) $msg->id; ?></td>
                            <td><?php echo esc_html($msg->subject); ?></td>
                            <td><?php echo esc_html($list_name ?: '—'); ?></td>
                            <td><?php echo esc_html($msg->created_at); ?></td>
                            <td class="sm-pending-cell"><?php echo (int) max(0, $pending); ?></td>
                            <td class="sm-last-sent-cell"><?php echo $msg->last_sent_at ? esc_html($msg->last_sent_at) : '—'; ?></td>
                            <td class="sm-status-cell"><?php echo esc_html($status_label); ?></td>
                            <td class="sm-actions-cell">
                                <?php if ($msg->status === 'active') : ?>
                                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['page' => 'simplekitmailing-sends', 'action' => 'pause_send', 'send_id' => $msg->id]), 'simplekitmailing_send_action', 'simplekitmailing_nonce')); ?>" class="button button-small">
                                        <?php esc_html_e('Pause', 'simplekitmailing'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['page' => 'simplekitmailing-sends', 'action' => 'cancel_send', 'send_id' => $msg->id]), 'simplekitmailing_send_action', 'simplekitmailing_nonce')); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e('Cancel this send? It cannot be resumed.', 'simplekitmailing'); ?>');">
                                        <?php esc_html_e('Cancel', 'simplekitmailing'); ?>
                                    </a>
                                <?php elseif ($msg->status === 'paused') : ?>
                                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['page' => 'simplekitmailing-sends', 'action' => 'resume_send', 'send_id' => $msg->id]), 'simplekitmailing_send_action', 'simplekitmailing_nonce')); ?>" class="button button-small">
                                        <?php esc_html_e('Resume', 'simplekitmailing'); ?>
                                    </a>
                                    <a href="<?php echo esc_url(wp_nonce_url(add_query_arg(['page' => 'simplekitmailing-sends', 'action' => 'cancel_send', 'send_id' => $msg->id]), 'simplekitmailing_send_action', 'simplekitmailing_nonce')); ?>" class="button button-small" onclick="return confirm('<?php esc_attr_e('Cancel this send? It cannot be resumed.', 'simplekitmailing'); ?>');">
                                        <?php esc_html_e('Cancel', 'simplekitmailing'); ?>
                                    </a>
                                <?php else : ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <script type="text/javascript">
    jQuery(document).ready(function($) {
        var processing = false;
        var paused = false;

        function smProcessBatch() {
            if (processing || paused) return;
            processing = true;

            $.post(ajaxurl, {
                action: 'simplekitmailing_process_batch',
                _ajax_nonce: '<?php echo esc_js(wp_create_nonce('simplekitmailing_process_batch')); ?>'
            }, function(response) {
                processing = false;
                if (response.success && response.data) {
                    var data = response.data;

                    // Update table with returned data
                    if (data.updated) {
                        $.each(data.updated, function(id, row) {
                            var $row = $('#sm-send-row-' + id);
                            if ($row.length) {
                                $row.find('.sm-pending-cell').text(row.pending);
                                $row.find('.sm-last-sent-cell').text(row.last_sent_at || '—');
                                $row.find('.sm-status-cell').text(row.status_label);
                                if (row.status !== 'active') {
                                    $row.find('.sm-actions-cell').html('—');
                                }
                            }
                        });
                    }

                    // Show/hide progress bar
                    if (data.active_count > 0) {
                        $('#sm-send-progress').show();
                        var pct = data.total_pending > 0
                            ? Math.round(((data.total_initial - data.total_pending) / data.total_initial) * 100)
                            : 100;
                        $('#sm-progress-bar').css('width', pct + '%');
                        $('#sm-progress-text').text(
                            data.total_sent + ' / ' + data.total_initial + ' sent (' + data.total_pending + ' pending)'
                        );

                        // Next batch in 10 seconds
                        setTimeout(smProcessBatch, 10000);
                    } else {
                        $('#sm-send-progress').hide();
                        $('#sm-progress-bar').css('width', '100%');
                        $('#sm-progress-text').text('<?php esc_attr_e('All sends completed!', 'simplekitmailing'); ?>');
                    }
                }
            }).fail(function() {
                processing = false;
                setTimeout(smProcessBatch, 10000);
            });
        }

        // Start automatic processing if there are active sends
        var hasActive = <?php echo esc_js(!empty(array_filter($messages, function($m) { return $m->status === 'active'; })) ? 'true' : 'false'); ?>;
        if (hasActive) {
            smProcessBatch();
        }
    });
    </script>
    <?php
}

// ---------------------------------------------------------------------------
// Status label
// ---------------------------------------------------------------------------
function simplekitmailing_status_label($status) {
    $labels = [
        'draft'     => __('Draft', 'simplekitmailing'),
        'active'    => __('Active', 'simplekitmailing'),
        'paused'    => __('Paused', 'simplekitmailing'),
        'cancelled' => __('Cancelled', 'simplekitmailing'),
        'completed' => __('Completed', 'simplekitmailing'),
    ];
    return $labels[$status] ?? ucfirst($status);
}

// ---------------------------------------------------------------------------
// Process pause/cancel/resume actions
// ---------------------------------------------------------------------------
function simplekitmailing_handle_send_actions() {
    // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Nonce parameters accessed before wp_verify_nonce() on line 203.
    if (!isset($_GET['simplekitmailing_nonce']) || !isset($_GET['action']) || !isset($_GET['send_id'])) {
        return;
    }

    if (!wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['simplekitmailing_nonce'])), 'simplekitmailing_send_action')) {
        return;
    }

    global $wpdb;
    $table_messages = $wpdb->prefix . 'sm_messages';
    $send_id = absint($_GET['send_id']);
    $action  = sanitize_text_field(wp_unslash($_GET['action']));

    switch ($action) {
        case 'pause_send':
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->update($table_messages, ['status' => 'paused'], ['id' => $send_id]);
            $msg = __('Send paused.', 'simplekitmailing');
            break;
        case 'cancel_send':
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->update($table_messages, ['status' => 'cancelled'], ['id' => $send_id]);
            $msg = __('Send cancelled.', 'simplekitmailing');
            break;
        case 'resume_send':
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->update($table_messages, ['status' => 'active'], ['id' => $send_id]);
            $msg = __('Send resumed.', 'simplekitmailing');
            break;
        default:
            return;
    }

    wp_safe_redirect(add_query_arg([
        'page' => 'simplekitmailing-sends',
        'msg'  => $msg,
        'type' => 'success',
    ], admin_url('admin.php')));
    exit;
}

// ---------------------------------------------------------------------------
// Gradual sending via WP-Cron
// ---------------------------------------------------------------------------
function simplekitmailing_process_sends() {
    global $wpdb;
    $table_messages    = $wpdb->prefix . 'sm_messages';
    $table_subscribers = $wpdb->prefix . 'sm_subscribers';

    // Fetch active sends
    // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $active_sends = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM %i WHERE status = 'active' AND total > sent",
            $table_messages
        )
    );

    if (empty($active_sends)) {
        return;
    }

    foreach ($active_sends as $send) {
        $pending = $send->total - $send->sent;
        $list_id = $send->list_id ?: 0;
        $batch_size = simplekitmailing_get_batch_size($list_id);
        $batch   = min($batch_size, $pending);

        // Get the next N contacts from the specific list
        if ($send->list_id) {
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $subscribers = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM %i WHERE list_id = %d ORDER BY id ASC LIMIT %d OFFSET %d",
                    $table_subscribers, $send->list_id, $batch, $send->sent
                )
            );
        } else {
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $subscribers = $wpdb->get_results(
                $wpdb->prepare(
                    "SELECT * FROM %i ORDER BY id ASC LIMIT %d OFFSET %d",
                    $table_subscribers, $batch, $send->sent
                )
            );
        }

        foreach ($subscribers as $sub) {
            $list_id = $send->list_id ?: 0;

            // Build content with unsubscribe link
            $unsubscribe_page_id = simplekitmailing_get_unsubscribe_page_id($list_id);
            if ($unsubscribe_page_id) {
                $page_url = get_permalink($unsubscribe_page_id);
                if ($page_url) {
                    $unsubscribe_url = add_query_arg([
                        'em' => urlencode($sub->email),
                        'list_id' => $sub->list_id ?: 0,
                    ], $page_url);
                } else {
                    $unsubscribe_url = add_query_arg([
                        'em' => urlencode($sub->email),
                        'list_id' => $sub->list_id ?: 0,
                    ], home_url('/'));
                }
            } else {
                $unsubscribe_url = add_query_arg([
                    'sm_unsubscribe' => '1',
                    'email'          => urlencode($sub->email),
                    'list_id'        => $sub->list_id ?: 0,
                    'nonce'          => wp_create_nonce('sm_unsubscribe_' . $sub->email),
                ], home_url('/'));
            }

            $content = $send->content;
            $content .= "\n\n<hr>\n";
            $content .= '<p style="font-size:12px;color:#888;">';

            $unsub_template = simplekitmailing_get_unsubscribe_text($list_id);
            $link_html = '<a href="' . esc_url($unsubscribe_url) . '">' . esc_url($unsubscribe_url) . '</a>';
            if (strpos($unsub_template, '[LINK]') !== false) {
                $unsub_text = str_replace('[LINK]', $link_html, $unsub_template);
            } else {
                $unsub_text = $unsub_template . ' ' . $link_html;
            }
            $content .= wp_kses_post($unsub_text);
            $content .= '</p>';

            $result = simplekitmailing_send_email($sub->email, $send->subject, $content, $list_id);

            if ($result === true) {
                // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
                // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
                $wpdb->query($wpdb->prepare(
                    "UPDATE %i SET sent = sent + 1, last_sent_at = NOW() WHERE id = %d",
                    $table_messages, $send->id
                ));
            }
        }

        // Check if completed
        // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
        $updated = $wpdb->get_row($wpdb->prepare("SELECT * FROM %i WHERE id = %d", $table_messages, $send->id));
        if ($updated && $updated->sent >= $updated->total) {
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->update($table_messages, ['status' => 'completed'], ['id' => $send->id]);
        }
    }
}

// ---------------------------------------------------------------------------
// Send email via direct SMTP (socket)
// ---------------------------------------------------------------------------
function simplekitmailing_send_email($to, $subject, $content, $list_id = 0) {
    $smtp    = simplekitmailing_get_smtp_settings($list_id);
    $sender  = simplekitmailing_get_sender($list_id);
    $replyto = simplekitmailing_get_reply_to($list_id);

    // Generate HTML (with template) and text/plain versions
    $email_parts = simplekitmailing_wrap_html_email($subject, $content, $list_id);
    $html_body   = $email_parts['html'];
    $text_body   = $email_parts['text'];

    // If no SMTP configured, try wp_mail fallback
    if (empty($smtp['host'])) {
        $headers = [
            'Content-Type: text/html; charset=UTF-8',
            'X-Mailer: Simple Kit Mailing WordPress Plugin',
        ];
        $result = wp_mail($to, $subject, $html_body, $headers);
        if (!$result) {
            return 'wp_mail fallback failed';
        }
        return true;
    }

    // --- Send via PHPMailer (WordPress bundled) ---
    require_once ABSPATH . WPINC . '/PHPMailer/PHPMailer.php';
    require_once ABSPATH . WPINC . '/PHPMailer/SMTP.php';
    require_once ABSPATH . WPINC . '/PHPMailer/Exception.php';

    $mail = new PHPMailer\PHPMailer\PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host       = $smtp['host'];
        $mail->Port       = (int) $smtp['port'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $smtp['username'];
        $mail->Password   = $smtp['password'];
        $mail->CharSet    = 'UTF-8';

        if ($smtp['encryption'] === 'ssl') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS;
        } elseif ($smtp['encryption'] === 'tls') {
            $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        }

        $mail->setFrom($sender['email'], $sender['name']);
        $mail->addAddress($to);

        if (!empty($replyto['email'])) {
            $mail->addReplyTo($replyto['email'], $replyto['name']);
        }

        $mail->Subject = $subject;
        $mail->msgHTML($html_body);
        $mail->AltBody = $text_body;

        // Custom headers
        $mail->addCustomHeader('X-Mailer', 'Simple Kit Mailing WordPress Plugin');
        $mail->addCustomHeader('Precedence', 'bulk');
        $mail->addCustomHeader('Auto-Submitted', 'auto-generated');

        $unsubscribe_email = !empty($sender['email']) ? $sender['email'] : get_bloginfo('admin_email');
        $unsubscribe_url   = home_url('/') . '?sm_unsubscribe=1&email=' . urlencode($to);
        $mail->addCustomHeader('List-Unsubscribe', '<mailto:' . $unsubscribe_email . '?subject=unsubscribe>, <' . $unsubscribe_url . '>');
        $mail->addCustomHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');

        $mail->send();
        return true;
    } catch (PHPMailer\PHPMailer\Exception $e) {
        return 'SMTP error: ' . $mail->ErrorInfo;
    }
}

// ---------------------------------------------------------------------------
// Helper: wrap content in full HTML with responsive template
// ---------------------------------------------------------------------------
function simplekitmailing_wrap_html_email($subject, $html_content, $list_id = 0) {
    // Read template settings (per list, with global fallback)
    $header_html = simplekitmailing_get_email_header_html($list_id);
    $footer_html = simplekitmailing_get_email_footer_html($list_id);

    // Read color settings
    $email_bg_color   = simplekitmailing_get_email_bg_color($list_id);
    $email_text_color = simplekitmailing_get_email_text_color($list_id);
    $email_link_color = simplekitmailing_get_email_link_color($list_id);
    $email_h1_color   = simplekitmailing_get_email_h1_color($list_id);
    $email_h2_color   = simplekitmailing_get_email_h2_color($list_id);
    $email_h3_color   = simplekitmailing_get_email_h3_color($list_id);
    $email_h4_color   = simplekitmailing_get_email_h4_color($list_id);

    // --- Text/plain version ---
    $text_content = $html_content;
    $text_content = preg_replace(
        '/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>([^<]+)<\/a>/i',
        '$1',
        $text_content
    );
    $text_content = wp_strip_all_tags($text_content);
    $text_content = html_entity_decode($text_content, ENT_QUOTES, 'UTF-8');
    $text_content = preg_replace('/\n{3,}/', "\n\n", $text_content);

    $text_footer = $footer_html;
    $text_footer = preg_replace(
        '/<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>([^<]+)<\/a>/i',
        '$2 ($1)',
        $text_footer
    );
    $text_footer = wp_strip_all_tags($text_footer);
    $text_footer = html_entity_decode($text_footer, ENT_QUOTES, 'UTF-8');
    $text_footer = trim(preg_replace('/\n{3,}/', "\n\n", $text_footer));

    if (!empty($text_footer)) {
        $text_content .= "\n\n---\n" . $text_footer;
    }

    // --- HTML version ---
    $out_bg = esc_attr($email_bg_color);
    $html = '<!DOCTYPE html>';
    $html .= '<html xmlns="http://www.w3.org/1999/xhtml" lang="' . get_bloginfo('language') . '">';
    $html .= '<head>';
    $html .= '<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />';
    $html .= '<meta name="viewport" content="width=device-width, initial-scale=1.0" />';
    $html .= '<title>' . esc_html($subject) . '</title>';
    $html .= '<style type="text/css">';
    $html .= 'body, table, td { font-family: Arial, Helvetica, sans-serif; }';
    $html .= 'a { color: ' . esc_attr($email_link_color) . '; text-decoration: underline; }';
    $html .= 'h1 { color: ' . esc_attr($email_h1_color) . '; }';
    $html .= 'h2 { color: ' . esc_attr($email_h2_color) . '; }';
    $html .= 'h3 { color: ' . esc_attr($email_h3_color) . '; }';
    $html .= 'h4 { color: ' . esc_attr($email_h4_color) . '; }';
    $html .= '</style>';
    $html .= '</head>';
    $html .= '<body style="margin:0;padding:0;background-color:' . $out_bg . ';font-family:Arial,Helvetica,sans-serif;">';
    $html .= '<div id="email_content" style="padding:25px;font-size:16px;line-height:1.2;color:' . esc_attr($email_text_color) . ';">';
    $html .= '<div id="email_header" style="margin-bottom:25px;">';
    $html .= nl2br($header_html);
    $html .= '</div>';
    $html .= '<div id="email_body" style="margin-bottom:25px;">';
    $html .= nl2br($html_content);
    $html .= '</div>';
    $html .= '<div id="email_footer">';
    $html .= nl2br($footer_html);
    $html .= '</div>';
    $html .= '</div>';
    $html .= '</body>';
    $html .= '</html>';

    return [
        'html' => $html,
        'text' => $text_content,
    ];
}

// ---------------------------------------------------------------------------
// AJAX: process batch of sends and return updated progress
// ---------------------------------------------------------------------------
add_action('wp_ajax_simplekitmailing_process_batch', 'simplekitmailing_ajax_process_batch');
function simplekitmailing_ajax_process_batch() {
    check_ajax_referer('simplekitmailing_process_batch');
    if (!current_user_can('manage_options')) {
        wp_send_json_error();
    }

    global $wpdb;
    $table_messages = $wpdb->prefix . 'sm_messages';

    // Process a batch (10 emails)
    simplekitmailing_process_sends();

    // Return updated state of all sends
    // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.DirectDatabaseQuery.DirectQuery
    $messages = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT * FROM %i WHERE status != 'draft' ORDER BY created_at DESC",
            $table_messages
        )
    );

    $updated = [];
    $total_pending = 0;
    $total_sent = 0;
    $total_initial = 0;
    $active_count = 0;

    foreach ($messages as $msg) {
        $pending = max(0, $msg->total - $msg->sent);
        $total_pending += $pending;
        $total_sent += $msg->sent;
        $total_initial += $msg->total;

        if ($msg->status === 'active') {
            $active_count++;
        }

        $updated[$msg->id] = [
            'pending'      => (int) $pending,
            'sent'         => (int) $msg->sent,
            'total'        => (int) $msg->total,
            'last_sent_at' => $msg->last_sent_at,
            'status'       => $msg->status,
            'status_label' => simplekitmailing_status_label($msg->status),
        ];
    }

    wp_send_json_success([
        'updated'       => $updated,
        'total_pending' => $total_pending,
        'total_sent'    => $total_sent,
        'total_initial' => $total_initial,
        'active_count'  => $active_count,
    ]);
}
