<?php
/**
* Plugin Name: Cleverwise Daily Quotes
* Description: Adds daily quotes (tips, snippets, etc) sections with the ability to choose the categories.  Plus total control of themes and layouts.
* Version: 1.1
* Author: Jeremy O'Connell
* Author URI: http://www.cyberws.com/cleverwise-plugins/
* License: GPL2 .:. http://opensource.org/licenses/GPL-2.0
*/

////////////////////////////////////////////////////////////////////////////
//	Load Cleverwise Framework Library
////////////////////////////////////////////////////////////////////////////
include_once('cwfa.php');
$cwfa_dq=new cwfa_dq;

////////////////////////////////////////////////////////////////////////////
//	Wordpress database option
////////////////////////////////////////////////////////////////////////////
Global $wpdb,$dq_wp_option_version_txt,$dq_wp_option,$dq_wp_option_version_num;

$dq_wp_option_version_num='1.1';
$dq_wp_option='daily_quotes';
$dq_wp_option_version_txt=$dq_wp_option.'_version';

////////////////////////////////////////////////////////////////////////////
//	Get db prefix and set correct table names
////////////////////////////////////////////////////////////////////////////
Global $cw_daily_quotes_tbl;

$wp_db_prefix=$wpdb->prefix;
$cw_daily_quotes_tbl=$wp_db_prefix.'daily_quotes';

////////////////////////////////////////////////////////////////////////////
//	Memcache Support
////////////////////////////////////////////////////////////////////////////
$dq_memcached='off';
$dq_memcached_file=plugin_dir_path(__FILE__).'memcached.config.php';
$dq_memcached_conn='';
if (file_exists($dq_memcached_file)) {
	include_once($dq_memcached_file);
	$dq_memcached_conn=new Memcache;
	$dq_memcached_conn->connect($dq_memcached_server,$dq_memcached_port);
	$dq_memcached='on';
}

////////////////////////////////////////////////////////////////////////////
//	If admin panel is showing and user can manage options load menu option
////////////////////////////////////////////////////////////////////////////
if (is_admin()) {
	//	Hook admin code
	include_once("dqa.php");

	//	Activation code
	register_activation_hook( __FILE__, 'cw_daily_quotes_activate');

	//	Check installed version and if mismatch upgrade
	Global $wpdb;
	$dq_wp_option_db_version=get_option($dq_wp_option_version_txt);
	if ($dq_wp_option_db_version < $dq_wp_option_version_num) {
		update_option($dq_wp_option_version_txt,$dq_wp_option_version_num);
	}
}

////////////////////////////////////////////////////////////////////////////
//	Register shortcut to display visitor side
////////////////////////////////////////////////////////////////////////////
add_shortcode('cw_daily_quotes', 'cw_daily_quotes_vside');

////////////////////////////////////////////////////////////////////////////
//	Visitor Display
////////////////////////////////////////////////////////////////////////////
function cw_daily_quotes_vside() {
Global $wpdb,$dq_wp_option,$cw_daily_quotes_tbl,$dq_memcached,$dq_memcached_conn;

	////////////////////////////////////////////////////////////////////////////
	//	Load data from wp db
	////////////////////////////////////////////////////////////////////////////
	$dq_wp_option_array=get_option($dq_wp_option);
	$dq_wp_option_array=unserialize($dq_wp_option_array);

	////////////////////////////////////////////////////////////////////////////
	//	Load current day
	////////////////////////////////////////////////////////////////////////////
	$curday=date('z');

	////////////////////////////////////////////////////////////////////////////
	//	Load current category
	////////////////////////////////////////////////////////////////////////////
	$wpcategory=get_the_category($post->ID);
	$wpcurcat=$wpcategory[0]->term_id.'|';

	////////////////////////////////////////////////////////////////////////////
	//	Display necessary quote sections
	////////////////////////////////////////////////////////////////////////////
	//	Load default layout
	$dq_daily_quote_layout=stripslashes($dq_wp_option_array['layout']);

	// 	Load quote titles
	$dq_daily_quote_titles=$dq_wp_option_array['section_titles'];

	//	Check each quote section
	if ($dq_daily_quote_titles) {
		isset($daily_quotes_build);
		asort($dq_daily_quote_titles);
		foreach ($dq_daily_quote_titles as $daily_quote_qid => $dq_daily_quote_title) {
			//	Load category
			$daily_quote_qcats=$dq_wp_option_array['section_categories'][$daily_quote_qid];
			$dq_daily_quote_title=stripslashes($dq_daily_quote_title);

			// 	Load quote type
			$dq_daily_section_type=$dq_wp_option_array['section_types'][$daily_quote_qid];

			//	Display quote check
			$dq_daily_section_display='';
			if ($dq_daily_section_type == 'a') {
				$dq_daily_section_display='on';
			} elseif ($dq_daily_section_type == 'e' and substr_count($daily_quote_qcats,"$wpcurcat") == '0') {
				$dq_daily_section_display='on';
			} elseif ($dq_daily_section_type == 'i' and substr_count($daily_quote_qcats,"$wpcurcat") == '1') {
				$dq_daily_section_display='on';
			} else {
				$dq_daily_section_display='off';
			}

			//	Display quote
			if ($dq_daily_section_display == 'on') {
				//	Grab quote
				$db_statement="SELECT qod_quote FROM $cw_daily_quotes_tbl where qod_sid='$daily_quote_qid' and qod_day='$curday'";

				//	Memcached - Load data from key
				if ($dq_memcached == 'on') {
					$memcache_key=home_url().'-'.$daily_quote_qid.'-'.$curday;
					$memcache_key=hash('whirlpool',$memcache_key);
					$myrows=$dq_memcached_conn->get($memcache_key);
				}

				//	Database load, plus Memcached queue if on
				if (!$myrows) {
					$myrows=$wpdb->get_results("$db_statement");
					//	Memcached - Save data with one hour expiration
					if ($dq_memcached == 'on') {
						$memcached_myrows=serialize($myrows);
						$dq_memcached_conn->set($memcache_key,$myrows,0,'3600');
					}
				}

				if ($myrows) {
					foreach ($myrows as $myrow) {
						$qod_quote=stripslashes($myrow->qod_quote);
					}
				}

				$layout_theme=$dq_daily_quote_layout;
				//	If custom theme over default
				if (strlen($dq_wp_option_array['section_layouts'][$daily_quote_qid]) > '1') {
					$layout_theme=stripslashes($dq_wp_option_array['section_layouts'][$daily_quote_qid]);
				}

				//	Load quote section title and quote into theme
				$layout_theme=preg_replace('/{{quote_title}}/',$dq_daily_quote_title,$layout_theme);
				$layout_theme=preg_replace('/{{quote}}/',$qod_quote,$layout_theme);

				//	Add daily quote to build
				$daily_quotes_build .=$layout_theme;
			}
		}
		//	Display to browser/site
		return $daily_quotes_build;
	}

}