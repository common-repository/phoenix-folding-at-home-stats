<?php
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
    exit;
}
delete_option('phoenix_folding_stats');
delete_transient('phoenix_folding_stats');
wp_cache_flush();