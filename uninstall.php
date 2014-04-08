<?php
/*
* Copyright 2014 Jeremy O'Connell  (email : cwplugins@cyberws.com)
* License: GPL2 .:. http://opensource.org/licenses/GPL-2.0
*/

//	if uninstall not called from WordPress exit
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit();
}

$dq_wp_option='daily_quotes';
$dq_wp_option_version_txt=$dq_wp_option.'_version';

global $wpdb;

//	For Single site
if (!is_multisite()) {
    delete_option($dq_wp_option);
    delete_option($dq_wp_option_version_txt);

//	For Multisite
} else {
    $blog_ids=$wpdb->get_col("SELECT blog_id FROM $wpdb->blogs");
    $original_blog_id=get_current_blog_id();
    foreach ($blog_ids as $blog_id) {
        switch_to_blog($blog_id);
        delete_site_option($dq_wp_option);
        delete_site_option($dq_wp_option_version_txt);
    }
    switch_to_blog($original_blog_id);
}

$wp_db_prefix=$wpdb->prefix;
$cw_daily_quotes_tbl=$wp_db_prefix.'daily_quotes';

$wpdb->query("DROP TABLE IF EXISTS $cw_daily_quotes_tbl");

