<?php
/**
 * Plugin Name: Simple Kit QRCode
 * Plugin URI:  https://github.com/example/simplekitqrcode
 * Description: Generate QR codes for WordPress posts and pages with download in PNG 2048x2048px and access tracking.
 * Version:     1.1.0
 * Requires at least: 6.8
 * Tested up to: 7.0
 * Requires PHP: 7.4
 * Author:      Lucas Junqueira
 * License:     GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: simplekitqrcode
 * Domain Path: /languages
 */

if (!defined('ABSPATH')) {
    exit;
}

define('SIMPLEKITQRCODE_VERSION', '1.1.0');
define('SIMPLEKITQRCODE_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('SIMPLEKITQRCODE_PLUGIN_URL', plugin_dir_url(__FILE__));

class SimpleKitQRCode {

    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'qr_code_stats';

        register_activation_hook(__FILE__, array($this, 'activate'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_simplekitqrcode_generate', array($this, 'ajax_generate_qr_code'));
        add_action('wp_ajax_simplekitqrcode_download', array($this, 'ajax_download_qr_code'));
        add_action('wp_ajax_simplekitqrcode_delete', array($this, 'ajax_delete_qr_stat'));
        add_action('template_redirect', array($this, 'track_qr_access'));
    }

    public function activate() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id mediumint(9) NOT NULL AUTO_INCREMENT,
            post_id bigint(20) NOT NULL,
            post_title text NOT NULL,
            post_url text NOT NULL,
            access_count bigint(20) DEFAULT 0,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            last_access datetime DEFAULT NULL,
            PRIMARY KEY (id),
            UNIQUE KEY post_id (post_id)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);
    }

    public function add_admin_menu() {
        add_menu_page(
            __('Simple Kit QRCode', 'simplekitqrcode'),
            __('SK QRCode', 'simplekitqrcode'),
            'manage_options',
            'simplekitqrcode',
            array($this, 'render_generator_page'),
            'dashicons-smartphone',
            30
        );

        add_submenu_page(
            'simplekitqrcode',
            __('Generate QR Code', 'simplekitqrcode'),
            __('Generate QR Code', 'simplekitqrcode'),
            'manage_options',
            'simplekitqrcode',
            array($this, 'render_generator_page')
        );

        add_submenu_page(
            'simplekitqrcode',
            __('Statistics', 'simplekitqrcode'),
            __('Statistics', 'simplekitqrcode'),
            'manage_options',
            'simplekitqrcode-stats',
            array($this, 'render_stats_page')
        );

        add_submenu_page(
            'simplekitqrcode',
            __('Help', 'simplekitqrcode'),
            __('Help', 'simplekitqrcode'),
            'manage_options',
            'simplekitqrcode-help',
            'simplekitqrcode_page_help'
        );

        add_submenu_page(
            'simplekitqrcode',
            __('Backup', 'simplekitqrcode'),
            __('Backup', 'simplekitqrcode'),
            'manage_options',
            'simplekitqrcode-backup',
            'simplekitqrcode_page_backup'
        );

        add_submenu_page(
            'simplekitqrcode',
            __('Donate', 'simplekitqrcode'),
            __('Donate', 'simplekitqrcode'),
            'manage_options',
            'simplekitqrcode-donate',
            'simplekitqrcode_page_donate'
        );
    }

    public function enqueue_scripts($hook) {
        if (strpos($hook, 'simplekitqrcode') === false) {
            return;
        }

        wp_enqueue_script('jquery');

        wp_register_style('simplekitqrcode-admin', false);
        wp_enqueue_style('simplekitqrcode-admin');
        wp_add_inline_style('simplekitqrcode-admin', $this->get_inline_css());

        wp_register_script('simplekitqrcode-admin', false, array('jquery'), SIMPLEKITQRCODE_VERSION, true);
        wp_enqueue_script('simplekitqrcode-admin');

        wp_localize_script('simplekitqrcode-admin', 'simplekitqrcode_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('simplekitqrcode_nonce')
        ));

        wp_add_inline_script('simplekitqrcode-admin', $this->get_inline_js());
    }

    private function get_inline_css() {
        return '
            .qr-generator-container {
                max-width: 900px;
                margin: 20px 0;
                background: #fff;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            }
            .qr-generator-container h1 {
                margin-top: 0;
                color: #23282d;
            }
            .qr-form-group {
                margin-bottom: 25px;
            }
            .qr-form-group label {
                display: block;
                margin-bottom: 8px;
                font-weight: 600;
                color: #23282d;
            }
            .qr-form-group select {
                width: 100%;
                max-width: 500px;
                padding: 8px 12px;
                font-size: 14px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .qr-form-group input[type="text"] {
                width: 100%;
                max-width: 500px;
                padding: 8px 12px;
                font-size: 14px;
                border: 1px solid #ddd;
                border-radius: 4px;
            }
            .qr-search-info {
                font-size: 12px;
                color: #666;
                margin-top: 5px;
            }
            .qr-tabs {
                display: flex;
                gap: 10px;
                margin-bottom: 20px;
                border-bottom: 2px solid #ddd;
            }
            .qr-tab {
                padding: 10px 20px;
                background: #f5f5f5;
                border: none;
                cursor: pointer;
                font-size: 14px;
                font-weight: 600;
                color: #555;
                border-radius: 4px 4px 0 0;
                transition: all 0.2s;
            }
            .qr-tab:hover {
                background: #e0e0e0;
            }
            .qr-tab.active {
                background: #0073aa;
                color: #fff;
            }
            .qr-tab-content {
                display: none;
            }
            .qr-tab-content.active {
                display: block;
            }
            .qr-button {
                background: #0073aa;
                color: #fff;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 600;
                transition: background 0.2s;
            }
            .qr-button:hover {
                background: #005177;
            }
            .qr-button:disabled {
                background: #ccc;
                cursor: not-allowed;
            }
            .qr-button.delete {
                background: #dc3232;
                padding: 5px 10px;
                font-size: 12px;
            }
            .qr-button.delete:hover {
                background: #a00;
            }
            .qr-result {
                margin-top: 30px;
                padding: 20px;
                background: #f5f5f5;
                border-radius: 8px;
                display: none;
            }
            .qr-result.active {
                display: block;
            }
            .qr-code-display {
                text-align: center;
                margin: 20px 0;
            }
            .qr-code-display img {
                max-width: 400px;
                height: auto;
                border: 1px solid #ddd;
                padding: 10px;
                background: #fff;
            }
            .qr-info {
                margin-top: 15px;
                padding: 15px;
                background: #fff;
                border-left: 4px solid #0073aa;
            }
            .qr-info p {
                margin: 5px 0;
                color: #555;
            }
            .qr-download-btn {
                background: #00a32a;
                margin-top: 15px;
            }
            .qr-download-btn:hover {
                background: #008a20;
            }
            .qr-loading {
                display: none;
                margin-left: 10px;
            }
            .qr-loading.active {
                display: inline-block;
            }
            .qr-stats-table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 20px;
                background: #fff;
            }
            .qr-stats-table th,
            .qr-stats-table td {
                padding: 12px;
                text-align: left;
                border-bottom: 1px solid #ddd;
            }
            .qr-stats-table th {
                background: #f5f5f5;
                font-weight: 600;
                color: #23282d;
            }
            .qr-stats-table tr:hover {
                background: #f9f9f9;
            }
            .qr-stats-table .qr-count {
                font-weight: 600;
                color: #0073aa;
                font-size: 16px;
            }
            .qr-stats-table .qr-url {
                color: #666;
                font-size: 12px;
                word-break: break-all;
            }
            .qr-no-data {
                text-align: center;
                padding: 40px;
                color: #666;
                font-style: italic;
            }
            .qr-tracking-url {
                margin-top: 15px;
                padding: 15px;
                background: #fff3cd;
                border-left: 4px solid #ffc107;
                border-radius: 4px;
            }
            .qr-tracking-url strong {
                display: block;
                margin-bottom: 8px;
                color: #856404;
            }
            .qr-tracking-url code {
                background: #fff;
                padding: 8px 12px;
                border-radius: 4px;
                display: block;
                word-break: break-all;
                font-size: 12px;
                border: 1px solid #ffc107;
            }
        ';
    }

    private function get_inline_js() {
        return '
            jQuery(document).ready(function($) {
                // Tabs
                $(".qr-tab").on("click", function() {
                    var target = $(this).data("tab");

                    $(".qr-tab").removeClass("active");
                    $(this).addClass("active");

                    $(".qr-tab-content").removeClass("active");
                    $("#" + target).addClass("active");
                });

                // Post search
                $("#post-search").on("input", function() {
                    var searchTerm = $(this).val().toLowerCase();
                    $("#post-select option").each(function() {
                        var text = $(this).text().toLowerCase();
                        if (text.indexOf(searchTerm) > -1 || $(this).val() === "") {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                });

                // Page search
                $("#page-search").on("input", function() {
                    var searchTerm = $(this).val().toLowerCase();
                    $("#page-select option").each(function() {
                        var text = $(this).text().toLowerCase();
                        if (text.indexOf(searchTerm) > -1 || $(this).val() === "") {
                            $(this).show();
                        } else {
                            $(this).hide();
                        }
                    });
                });

                // Generate QR Code
                $("#generate-qr-btn").on("click", function(e) {
                    e.preventDefault();

                    var activeTab = $(".qr-tab.active").data("tab");
                    var postId;

                    if (activeTab === "posts-tab") {
                        postId = $("#post-select").val();
                    } else {
                        postId = $("#page-select").val();
                    }

                    if (!postId) {
                        alert("Please select a " + (activeTab === "posts-tab" ? "post" : "page") + ".");
                        return;
                    }

                    $("#generate-qr-btn").prop("disabled", true);
                    $(".qr-loading").addClass("active");

                    $.ajax({
                        url: simplekitqrcode_ajax.ajax_url,
                        type: "POST",
                        data: {
                            action: "simplekitqrcode_generate",
                            nonce: simplekitqrcode_ajax.nonce,
                            post_id: postId
                        },
                        success: function(response) {
                            if (response.success) {
                                $("#qr-code-img").attr("src", response.data.qr_code);
                                $("#qr-post-title").text(response.data.post_title);
                                $("#qr-post-url").text(response.data.post_url);
                                $("#qr-tracking-url").text(response.data.tracking_url);
                                $("#download-qr-btn").data("post-id", postId);
                                $(".qr-result").addClass("active");
                            } else {
                                alert("Error generating QR Code: " + response.data.message);
                            }
                        },
                        error: function() {
                            alert("AJAX request error.");
                        },
                        complete: function() {
                            $("#generate-qr-btn").prop("disabled", false);
                            $(".qr-loading").removeClass("active");
                        }
                    });
                });

                $("#download-qr-btn").on("click", function(e) {
                    e.preventDefault();

                    var postId = $(this).data("post-id");
                    var downloadUrl = simplekitqrcode_ajax.ajax_url +
                        "?action=simplekitqrcode_download&post_id=" + postId +
                        "&nonce=" + simplekitqrcode_ajax.nonce;

                    window.location.href = downloadUrl;
                });

                $(".delete-qr-stat").on("click", function(e) {
                    e.preventDefault();

                    if (!confirm("Are you sure you want to delete this record?")) {
                        return;
                    }

                    var statId = $(this).data("id");
                    var $row = $(this).closest("tr");

                    $.ajax({
                        url: simplekitqrcode_ajax.ajax_url,
                        type: "POST",
                        data: {
                            action: "simplekitqrcode_delete",
                            nonce: simplekitqrcode_ajax.nonce,
                            stat_id: statId
                        },
                        success: function(response) {
                            if (response.success) {
                                $row.fadeOut(300, function() {
                                    $(this).remove();
                                    if ($(".qr-stats-table tbody tr").length === 0) {
                                        $(".qr-stats-table").replaceWith("<div class=\"qr-no-data\">No QR Codes have been generated yet.</div>");
                                    }
                                });
                            } else {
                                alert("Error deleting record: " + response.data.message);
                            }
                        },
                        error: function() {
                            alert("AJAX request error.");
                        }
                    });
                });
            });
        ';
    }

    public function render_generator_page() {
        $posts = $this->get_posts_by_type('post');
        $pages = $this->get_posts_by_type('page');
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Generate QR Code', 'simplekitqrcode'); ?></h1>
                <p><?php esc_html_e('Select a post or page to generate a QR code that leads to its link.', 'simplekitqrcode'); ?></p>

                <div class="qr-tabs">
                    <button class="qr-tab active" data-tab="posts-tab"><?php esc_html_e('Posts', 'simplekitqrcode'); ?> (<?php echo count($posts); ?>)</button>
                    <button class="qr-tab" data-tab="pages-tab"><?php esc_html_e('Pages', 'simplekitqrcode'); ?> (<?php echo count($pages); ?>)</button>
                </div>

                <form id="qr-generator-form">
                    <!-- Posts Tab -->
                    <div id="posts-tab" class="qr-tab-content active">
                        <div class="qr-form-group">
                            <label for="post-search"><?php esc_html_e('Search Posts:', 'simplekitqrcode'); ?></label>
                            <input type="text" id="post-search" placeholder="<?php esc_attr_e('Type to search...', 'simplekitqrcode'); ?>">
                            <div class="qr-search-info"><?php esc_html_e('Type the post title to filter the list', 'simplekitqrcode'); ?></div>
                        </div>

                        <div class="qr-form-group">
                            <label for="post-select"><?php esc_html_e('Select the Post:', 'simplekitqrcode'); ?></label>
                            <select id="post-select" name="post_id" size="10" style="height: 250px;">
                                <option value="">-- <?php esc_html_e('Select a Post', 'simplekitqrcode'); ?> --</option>
                                <?php foreach ($posts as $post): ?>
                                    <option value="<?php echo esc_attr($post->ID); ?>">
                                        <?php echo esc_html($post->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <!-- Pages Tab -->
                    <div id="pages-tab" class="qr-tab-content">
                        <div class="qr-form-group">
                            <label for="page-search"><?php esc_html_e('Search Pages:', 'simplekitqrcode'); ?></label>
                            <input type="text" id="page-search" placeholder="<?php esc_attr_e('Type to search...', 'simplekitqrcode'); ?>">
                            <div class="qr-search-info"><?php esc_html_e('Type the page title to filter the list', 'simplekitqrcode'); ?></div>
                        </div>

                        <div class="qr-form-group">
                            <label for="page-select"><?php esc_html_e('Select the Page:', 'simplekitqrcode'); ?></label>
                            <select id="page-select" name="page_id" size="10" style="height: 250px;">
                                <option value="">-- <?php esc_html_e('Select a Page', 'simplekitqrcode'); ?> --</option>
                                <?php foreach ($pages as $page): ?>
                                    <option value="<?php echo esc_attr($page->ID); ?>">
                                        <?php echo esc_html($page->post_title); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <button type="submit" id="generate-qr-btn" class="qr-button">
                        <?php esc_html_e('Generate QR Code', 'simplekitqrcode'); ?>
                    </button>
                    <span class="qr-loading spinner"></span>
                </form>

                <div class="qr-result" id="qr-result">
                    <h2><?php esc_html_e('QR Code Generated', 'simplekitqrcode'); ?></h2>
                    <div class="qr-code-display">
                        <img id="qr-code-img" src="" alt="<?php esc_attr_e('QR Code', 'simplekitqrcode'); ?>">
                    </div>
                    <div class="qr-info">
                        <p><strong><?php esc_html_e('Title:', 'simplekitqrcode'); ?></strong> <span id="qr-post-title"></span></p>
                        <p><strong><?php esc_html_e('Original URL:', 'simplekitqrcode'); ?></strong> <span id="qr-post-url"></span></p>
                    </div>
                    <div class="qr-tracking-url">
                        <strong><?php esc_html_e('IMPORTANT - URL for the QR Code:', 'simplekitqrcode'); ?></strong>
                        <p style="margin: 5px 0 10px 0; font-size: 13px;"><?php esc_html_e('Use this URL in the QR Code to track accesses:', 'simplekitqrcode'); ?></p>
                        <code id="qr-tracking-url"></code>
                    </div>
                    <button id="download-qr-btn" class="qr-button qr-download-btn">
                        <?php esc_html_e('Download QR Code PNG (2048x2048px)', 'simplekitqrcode'); ?>
                    </button>
                </div>
        </div>
        <?php
    }

    public function render_stats_page() {
        global $wpdb;

        $cache_key = 'simplekitqrcode_stats';
        $stats = wp_cache_get($cache_key, 'simplekitqrcode');
        if (false === $stats) {
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $stats = $wpdb->get_results(
                $wpdb->prepare("SELECT * FROM %i ORDER BY access_count DESC, created_at DESC", $this->table_name)
            );
            wp_cache_set($cache_key, $stats, 'simplekitqrcode', 300);
        }
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('QR Code Statistics', 'simplekitqrcode'); ?></h1>
                <p><?php esc_html_e('See how many times each QR Code has been accessed.', 'simplekitqrcode'); ?></p>

                <?php if (empty($stats)): ?>
                    <div class="qr-no-data">
                        <?php esc_html_e('No QR Codes have been generated yet.', 'simplekitqrcode'); ?>
                    </div>
                <?php else: ?>
                    <table class="qr-stats-table">
                        <thead>
                            <tr>
                                <th><?php esc_html_e('Post/Page Title', 'simplekitqrcode'); ?></th>
                                <th><?php esc_html_e('URL', 'simplekitqrcode'); ?></th>
                                <th style="text-align: center;"><?php esc_html_e('Accesses', 'simplekitqrcode'); ?></th>
                                <th style="text-align: center;"><?php esc_html_e('Created At', 'simplekitqrcode'); ?></th>
                                <th style="text-align: center;"><?php esc_html_e('Last Access', 'simplekitqrcode'); ?></th>
                                <th style="text-align: center;"><?php esc_html_e('Actions', 'simplekitqrcode'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($stats as $stat): ?>
                                <tr>
                                    <td>
                                        <strong><?php echo esc_html($stat->post_title); ?></strong>
                                    </td>
                                    <td>
                                        <div class="qr-url">
                                            <?php echo esc_html($stat->post_url); ?>
                                        </div>
                                    </td>
                                    <td style="text-align: center;">
                                        <span class="qr-count"><?php echo esc_html($stat->access_count); ?></span>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php echo esc_html(gmdate('d/m/Y H:i', strtotime($stat->created_at))); ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <?php
                                        echo $stat->last_access
                                            ? esc_html(gmdate('d/m/Y H:i', strtotime($stat->last_access)))
                                            : '-';
                                        ?>
                                    </td>
                                    <td style="text-align: center;">
                                        <button class="qr-button delete delete-qr-stat" data-id="<?php echo esc_attr($stat->id); ?>">
                                            <?php esc_html_e('Delete', 'simplekitqrcode'); ?>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
        </div>
        <?php
    }

    private function get_posts_by_type($post_type) {
        $args = array(
            'post_type' => $post_type,
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        );

        return get_posts($args);
    }

    private function get_all_posts_and_pages() {
        $args = array(
            'post_type' => array('post', 'page'),
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'orderby' => 'date',
            'order' => 'DESC'
        );

        return get_posts($args);
    }

    public function ajax_generate_qr_code() {
        check_ajax_referer('simplekitqrcode_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'simplekitqrcode')));
        }

        if (!isset($_POST['post_id'])) {
            wp_send_json_error(array('message' => __('Invalid post ID.', 'simplekitqrcode')));
        }
        $post_id = intval($_POST['post_id']);
        if (!$post_id) {
            wp_send_json_error(array('message' => __('Invalid post ID.', 'simplekitqrcode')));
        }

        $post = get_post($post_id);
        if (!$post) {
            wp_send_json_error(array('message' => __('Post not found.', 'simplekitqrcode')));
        }

        $post_url = get_permalink($post_id);
        $tracking_url = add_query_arg('qr_track', base64_encode($post_id), $post_url);

        $this->register_qr_code($post_id, $post->post_title, $post_url);

        $qr_code_data = $this->generate_qr_code($tracking_url, 400);

        wp_send_json_success(array(
            'qr_code' => $qr_code_data,
            'post_title' => $post->post_title,
            'post_url' => $post_url,
            'tracking_url' => $tracking_url
        ));
    }

    public function ajax_download_qr_code() {
        if (!isset($_GET['nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_GET['nonce'])), 'simplekitqrcode_nonce')) {
            wp_die(esc_html__('Invalid nonce.', 'simplekitqrcode'));
        }

        if (!current_user_can('manage_options')) {
            wp_die(esc_html__('Permission denied.', 'simplekitqrcode'));
        }

        if (!isset($_GET['post_id'])) {
            wp_die(esc_html__('Invalid post ID.', 'simplekitqrcode'));
        }
        $post_id = intval($_GET['post_id']);
        if (!$post_id) {
            wp_die(esc_html__('Invalid post ID.', 'simplekitqrcode'));
        }

        $post = get_post($post_id);
        if (!$post) {
            wp_die(esc_html__('Post not found.', 'simplekitqrcode'));
        }

        $post_url = get_permalink($post_id);
        $tracking_url = add_query_arg('qr_track', base64_encode($post_id), $post_url);

        $qr_image = $this->generate_qr_image($tracking_url, 2048);

        $filename = sanitize_file_name($post->post_title) . '-qrcode.png';

        header('Content-Type: image/png');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        imagepng($qr_image);
        imagedestroy($qr_image);
        exit;
    }

    public function ajax_delete_qr_stat() {
        check_ajax_referer('simplekitqrcode_nonce', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Permission denied.', 'simplekitqrcode')));
        }

        global $wpdb;
        if (!isset($_POST['stat_id'])) {
            wp_send_json_error(array('message' => __('Invalid statistic ID.', 'simplekitqrcode')));
        }
        $stat_id = intval($_POST['stat_id']);

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $result = $wpdb->delete(
            $this->table_name,
            array('id' => $stat_id),
            array('%d')
        );
        wp_cache_delete('simplekitqrcode_stats', 'simplekitqrcode');

        if ($result === false) {
            wp_send_json_error(array('message' => __('Error deleting record.', 'simplekitqrcode')));
        }

        wp_send_json_success();
    }

    public function track_qr_access() {
        // Public tracking endpoint: nonce verification not applicable here
        // as QR code scanners cannot send nonces. Input is validated below.
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if (!isset($_GET['qr_track'])) {
            return;
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $qr_track = sanitize_text_field(wp_unslash($_GET['qr_track']));
        $post_id = intval(base64_decode($qr_track));
        if (!$post_id) {
            return;
        }

        global $wpdb;

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->query($wpdb->prepare(
            "UPDATE %i
            SET access_count = access_count + 1,
                last_access = NOW()
            WHERE post_id = %d",
            $this->table_name,
            $post_id
        ));
        wp_cache_delete('simplekitqrcode_stats', 'simplekitqrcode');
    }

    private function register_qr_code($post_id, $post_title, $post_url) {
        global $wpdb;

        $cache_key = 'simplekitqrcode_qr_' . $post_id;
        $existing = wp_cache_get($cache_key, 'simplekitqrcode');
        if (false === $existing) {
            // data is being adjusted at processing time directly from the plugin's table at the database, no caching is possible
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $existing = $wpdb->get_row($wpdb->prepare(
                "SELECT id FROM %i WHERE post_id = %d",
                $this->table_name,
                $post_id
            ));
            wp_cache_set($cache_key, $existing, 'simplekitqrcode', 300);
        }

        if (!$existing) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
            $wpdb->insert(
                $this->table_name,
                array(
                    'post_id' => $post_id,
                    'post_title' => $post_title,
                    'post_url' => $post_url,
                    'access_count' => 0,
                    'created_at' => current_time('mysql')
                ),
                array('%d', '%s', '%s', '%d', '%s')
            );
            wp_cache_delete('simplekitqrcode_stats', 'simplekitqrcode');
            wp_cache_delete('simplekitqrcode_qr_' . $post_id, 'simplekitqrcode');
        }
    }

    private function generate_qr_code($data, $size = 400) {
        $qr_image = $this->generate_qr_image($data, $size);

        ob_start();
        imagepng($qr_image);
        $image_data = ob_get_clean();
        imagedestroy($qr_image);

        return 'data:image/png;base64,' . base64_encode($image_data);
    }

    private function generate_qr_image($data, $size = 400) {
        $matrix = $this->create_qr_matrix($data);
        $module_count = count($matrix);
        $module_size = floor($size / $module_count);
        $actual_size = $module_size * $module_count;

        $image = imagecreatetruecolor($actual_size, $actual_size);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);

        imagefill($image, 0, 0, $white);

        for ($row = 0; $row < $module_count; $row++) {
            for ($col = 0; $col < $module_count; $col++) {
                if ($matrix[$row][$col]) {
                    imagefilledrectangle(
                        $image,
                        $col * $module_size,
                        $row * $module_size,
                        ($col + 1) * $module_size - 1,
                        ($row + 1) * $module_size - 1,
                        $black
                    );
                }
            }
        }

        return $image;
    }

    private function create_qr_matrix($data) {
        $api_url = 'https://api.qrserver.com/v1/create-qr-code/?data=' . urlencode($data) . '&size=400x400&format=png';

        $response = wp_remote_get($api_url);
        if (is_wp_error($response)) {
            return $this->create_simple_qr_matrix($data);
        }

        $image_data = wp_remote_retrieve_body($response);

        $source_image = imagecreatefromstring($image_data);
        if (!$source_image) {
            return $this->create_simple_qr_matrix($data);
        }

        $width = imagesx($source_image);
        $matrix = array();

        for ($y = 0; $y < $width; $y++) {
            $matrix[$y] = array();
            for ($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($source_image, $x, $y);
                $colors = imagecolorsforindex($source_image, $rgb);
                $brightness = ($colors['red'] + $colors['green'] + $colors['blue']) / 3;
                $matrix[$y][$x] = $brightness < 128 ? 1 : 0;
            }
        }

        imagedestroy($source_image);

        return $matrix;
    }

    private function create_simple_qr_matrix($data) {
        $size = 41;
        $matrix = array_fill(0, $size, array_fill(0, $size, 0));

        for ($i = 0; $i < 7; $i++) {
            for ($j = 0; $j < 7; $j++) {
                if ($i == 0 || $i == 6 || $j == 0 || $j == 6 || ($i >= 2 && $i <= 4 && $j >= 2 && $j <= 4)) {
                    $matrix[$i][$j] = 1;
                    $matrix[$i][$size - 7 + $j] = 1;
                    $matrix[$size - 7 + $i][$j] = 1;
                }
            }
        }

        $hash = md5($data);
        $index = 0;
        for ($i = 9; $i < $size - 9; $i++) {
            for ($j = 9; $j < $size - 9; $j++) {
                if ($index < strlen($hash)) {
                    $matrix[$i][$j] = (hexdec($hash[$index]) % 2);
                    $index++;
                }
            }
        }

        return $matrix;
    }
}

new SimpleKitQRCode();

// Include additional modules
require_once SIMPLEKITQRCODE_PLUGIN_DIR . 'includes/backup.php';
require_once SIMPLEKITQRCODE_PLUGIN_DIR . 'includes/help.php';
require_once SIMPLEKITQRCODE_PLUGIN_DIR . 'includes/donate.php';
