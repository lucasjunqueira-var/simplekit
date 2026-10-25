<?php
/**
 * Uninstall routine.
 *
 * Removes the two post meta keys created by the plugin. Deactivation keeps
 * the data untouched; only uninstalling deletes it.
 *
 * @package SimpleKitRedirect
 */

defined('WP_UNINSTALL_PLUGIN') or exit;

delete_post_meta_by_key('_simplekitredirect_url');
delete_post_meta_by_key('_simplekitredirect_status');
