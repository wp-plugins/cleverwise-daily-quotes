<?php
/**
* Plugin Name: Cleverwise Daily Quotes
* Description: Adds daily quotes (tips, snippets, etc) sections with the ability to choose the categories.  Plus total control of themes and layouts.
* Version: 1.4
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

$dq_wp_option_version_num='1.4';
$dq_wp_option='daily_quotes';
$dq_wp_option_version_txt=$dq_wp_option.'_version';

////////////////////////////////////////////////////////////////////////////
//	Get db prefix and set correct table names
////////////////////////////////////////////////////////////////////////////
Global $cw_daily_quotes_tbl;

$wp_db_prefix=$wpdb->prefix;
$cw_daily_quotes_tbl=$wp_db_prefix.'daily_quotes';
$cw_posts_tbl=$wp_db_prefix.'posts';

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
//	Register Widget
////////////////////////////////////////////////////////////////////////////
add_action('widgets_init',
     create_function('', 'return register_widget("cw_dq_widget");')
);

////////////////////////////////////////////////////////////////////////////
//	Visitor Display
////////////////////////////////////////////////////////////////////////////
function cw_daily_quotes_vside($atts) {
Global $wpdb,$dq_wp_option,$cw_daily_quotes_tbl,$dq_memcached,$dq_memcached_conn;

	////////////////////////////////////////////////////////////////////////////
	//	Load data from wp db
	////////////////////////////////////////////////////////////////////////////
	$dq_wp_option_array=get_option($dq_wp_option);
	$dq_wp_option_array=unserialize($dq_wp_option_array);
	
	////////////////////////////////////////////////////////////////////////////
	//	Set variables
	////////////////////////////////////////////////////////////////////////////
	$daily_quotes_build='';

	////////////////////////////////////////////////////////////////////////////
	//	Load current day
	////////////////////////////////////////////////////////////////////////////
	$curday=date('z');
	
	////////////////////////////////////////////////////////////////////////////
	//	Load current category
	////////////////////////////////////////////////////////////////////////////
	if (isset($post->ID)) {
		$wpcategory=get_the_category($post->ID);
		$wpcurcat=$wpcategory[0]->term_id.'|';
	}
	
	////////////////////////////////////////////////////////////////////////////
	//	Check for section id attribute
	////////////////////////////////////////////////////////////////////////////
	$cw_ds_id='0';
	if (isset($atts['cw_ds_id'])) {
		$cw_ds_id=$atts['cw_ds_id'];
	}

	////////////////////////////////////////////////////////////////////////////
	//	Display necessary quote sections
	////////////////////////////////////////////////////////////////////////////
	//	Load default layout
	$dq_daily_quote_layout=stripslashes($dq_wp_option_array['layout']);

	// 	Load quote titles
	if ($cw_ds_id > '0') {
		$dq_daily_quote_titles=array();
			$dq_daily_quote_titles[$cw_ds_id]=$dq_wp_option_array['section_titles'][$cw_ds_id];
	} else {
		$dq_daily_quote_titles=$dq_wp_option_array['section_titles'];
	}

	//	Check each quote section
	if (isset($dq_daily_quote_titles)) {
		isset($daily_quotes_build);
		asort($dq_daily_quote_titles);
			
		foreach ($dq_daily_quote_titles as $daily_quote_qid => $dq_daily_quote_title) {
			//	Load category
			$daily_quote_qcats=$dq_wp_option_array['section_categories'][$daily_quote_qid];
			$dq_daily_quote_title=stripslashes($dq_daily_quote_title);

			// 	Load quote type
			$dq_daily_section_type=$dq_wp_option_array['section_types'][$daily_quote_qid];

			//	Display quote check
			$dq_daily_section_display='off';
			if ($dq_daily_section_type == 'a') {
				$dq_daily_section_display='on';
			} elseif ($dq_daily_section_type == 'e' and substr_count($daily_quote_qcats,"$wpcurcat") == '0') {
				$dq_daily_section_display='on';
			} elseif ($dq_daily_section_type == 'i' and substr_count($daily_quote_qcats,"$wpcurcat") == '1') {
				$dq_daily_section_display='on';
			} else {
				$dq_daily_section_display='off';
			}
			
			// 	Override display setting if direct section id call
			if ($cw_ds_id > '0') {
				$dq_daily_section_display='on';
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
		}
		//	Display to browser/site
		return $daily_quotes_build;
	}

}

////////////////////////////////////////////////////////////////////////////
//	Widget Logic
////////////////////////////////////////////////////////////////////////////
class cw_dq_widget extends WP_Widget {

	/**
	 * Sets up the widgets name etc
	 */
	public function __construct() {
		/* Widget settings. */
		parent::__construct(
			'cw_dq_widget', // Base ID
			__('Daily Quote Sections', 'text_domain'), // Name
			array( 'description'=>__('This will display your daily quote sections.', 'text_domain'),) // Args
		);
	}

	/**
	 * Outputs the content of the widget
	 *
	 * @param array $args
	 * @param array $instance
	 */
	public function widget($args,$instance) {
		Global $atts;
		$cw_daily_quotes_widget_html=cw_daily_quotes_vside($atts);
		print $cw_daily_quotes_widget_html;
	}

	/**
	 * Outputs the options form on admin
	 *
	 * @param array $instance The widget options
	 */
	public function form($instance) {
		// outputs the options form on admin
	}

	/**
	 * Processing widget options on save
	 *
	 * @param array $new_instance The new options
	 * @param array $old_instance The previous options
	 */
	public function update($new_instance, $old_instance) {
		// processes widget options to be saved
	}
}