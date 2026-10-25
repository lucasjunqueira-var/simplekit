<?php
/**
 * Runs only when the plugin is deleted from the Plugins screen.
 *
 * Deactivation never changes data. Deletion removes the settings option and
 * the per-post flag created by the meta box.
 */

defined('WP_UNINSTALL_PLUGIN') or exit;

delete_option('simplekitaccessibility_settings');
delete_post_meta_by_key('_simplekitaccessibility_disabled');
