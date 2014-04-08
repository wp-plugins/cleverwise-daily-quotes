<?php
/*
* Copyright 2014 Jeremy O'Connell  (email : cwplugins@cyberws.com)
* License: GPL2 .:. http://opensource.org/licenses/GPL-2.0
*/

////////////////////////////////////////////////////////////////////////////
//	Verify admin panel is loaded, if not fail
////////////////////////////////////////////////////////////////////////////
if (!is_admin()) {
	die();
}

////////////////////////////////////////////////////////////////////////////
//	Menu call
////////////////////////////////////////////////////////////////////////////
add_action('admin_menu', 'cw_daily_quotes_aside_mn');

////////////////////////////////////////////////////////////////////////////
//	Load admin menu option
////////////////////////////////////////////////////////////////////////////
function cw_daily_quotes_aside_mn() {
	add_submenu_page('options-general.php','Daily Quotes Panel','Daily Quotes','manage_options','cw-daily-quotes','cw_daily_quotes_aside');
}

////////////////////////////////////////////////////////////////////////////
//	Load admin functions
////////////////////////////////////////////////////////////////////////////
function cw_daily_quotes_aside() {
Global $wpdb,$dq_wp_option,$cw_daily_quotes_tbl,$cwfa_dq,$dq_memcached,$dq_memcached_conn;

	////////////////////////////////////////////////////////////////////////////
	//	Load options for plugin
	////////////////////////////////////////////////////////////////////////////
	$dq_wp_option_array=get_option($dq_wp_option);
	$dq_wp_option_array=unserialize($dq_wp_option_array);

	////////////////////////////////////////////////////////////////////////////
	//	Set action value
	////////////////////////////////////////////////////////////////////////////
	if (isset($_REQUEST['cw_action'])) {
		$cw_action=$_REQUEST['cw_action'];
	} else {
		$cw_action='main';
	}

	////////////////////////////////////////////////////////////////////////////
	//	Previous page link
	////////////////////////////////////////////////////////////////////////////
	$pplink='<a href="javascript:history.go(-1);">Return to previous page...</a>';

	////////////////////////////////////////////////////////////////////////////
	//	Define Variables
	////////////////////////////////////////////////////////////////////////////
	$cw_daily_quotes_action='';
	$cw_daily_quotes_html='';

	//	Default Layout
$daily_quotes_layout_def .=<<<EOM
<div style="width: 296px; padding: 0px; margin: 0px; border: 1px solid #000000; background-color: #000000; color: #ffffff; font-family: tahoma; font-size: 14px; font-weight: bold; text-align: center; -moz-border-radius: 5px 5px 0px 0px; border-radius: 5px 5px 0px 0px;"><div style="padding: 1px;">{{quote_title}}</div></div>
<div style="width: 296px; padding: 0px; margin-bottom: 10px; border: 1px solid #000000; border-top: 0px; font-family: tahoma; color: #000000; -moz-border-radius: 0px 0px 5px 5px; border-radius: 0px 0px 5px 5px;"><div style="padding: 5px;">{{quote}}</div></div>
EOM;

	////////////////////////////////////////////////////////////////////////////
	//	View Quotes
	////////////////////////////////////////////////////////////////////////////
	if ($cw_action == 'quotesview') {
		$qid=$cwfa_dq->cwf_san_int($_REQUEST['qid']);
		$qod_sid=$qid;
		$qtitle=stripslashes($dq_wp_option_array['section_titles'][$qod_sid]);

		$cw_daily_quotes_action='Viewing Quotes';

		if ($qod_sid > '0') {
			$myrows=$wpdb->get_results("SELECT qod_quote FROM $cw_daily_quotes_tbl where qod_sid='$qod_sid'");
			if ($myrows) {
				$day_cnt='1';
				foreach ($myrows as $myrow) {
					$qod_quote=stripslashes($myrow->qod_quote);
					$quotes .='<div style="width: 400px; margin-bottom: 8px; padding-bottom: 8px; border-bottom: 1px dashed #000000;">Day '.$day_cnt.': '.$qod_quote."</div>";
					$day_cnt++;
				}
			}
		}

$cw_daily_quotes_html .=<<<EOM
<p>Quote Section: <b>$qtitle</b>  .:. <a href="?page=cw-daily-quotes&cw_action=quoteedit&qid=$qid">Edit</a></p>
$quotes
EOM;

	////////////////////////////////////////////////////////////////////////////
	//	Add/Edit Quotes
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'quoteadd' or $cw_action == 'quoteedit') {
		$qid=$cwfa_dq->cwf_san_int($_REQUEST['qid']);
		$qtitle='';
		$quotes='';
		$qcats='';
		$qlayout='';

		$cw_daily_quotes_action_btn='Add';
		if ($cw_action == 'quoteedit') {
			$qod_sid=$qid;

			$cw_daily_quotes_action_btn='Edit';
			$myrows=$wpdb->get_results("SELECT qod_quote FROM $cw_daily_quotes_tbl where qod_sid='$qod_sid'");
			if ($myrows) {
				foreach ($myrows as $myrow) {
					$qod_quote=stripslashes($myrow->qod_quote);
					$quotes .=$qod_quote."\n";
				}
			}

			$qtitle=stripslashes($dq_wp_option_array['section_titles'][$qod_sid]);
			$qtype=$dq_wp_option_array['section_types'][$qod_sid];
			$qcats=$dq_wp_option_array['section_categories'][$qod_sid];
			$qlayout=stripslashes($dq_wp_option_array['section_layouts'][$qod_sid]);
		}

		//	Display Types
		$cw_qtypes_layout='<input type="radio" name="qtype" value="%s"%s> %s ';
		$quote_types=array('a'=>'All categories','e'=>'Exclude the following categories','i'=>'Include the following categories');
		foreach ($quote_types as $quote_type_id => $quote_type_name) {
			$wpcheck='';
			if ($qtype == $quote_type_id) {
				$wpcheck=' checked';
			}
			$qtypesbuild=sprintf($cw_qtypes_layout,$quote_type_id,$wpcheck,$quote_type_name);
			$qtypes .=$qtypesbuild.'<br>';
		}

		//	Get WP Categories
		$cw_category_layout='<input type="checkbox" name="qcategories[]" value="%s"%s> %s ';
		$args=array('orderby'=>'name','order'=>'ASC');
		$wp_categories=get_categories($args);
		foreach ($wp_categories as $wp_category) {
			$wp_cat_data=$wp_category;
			$wpcatid=$wp_cat_data->term_id;
			$wpcatname=$wp_cat_data->name;

			$wpcheck='';
			if (substr_count("$qcats|","$wpcatid|") > '0') {
				$wpcheck=' checked';
			}
			$categorybuild=sprintf($cw_category_layout,$wpcatid,$wpcheck,$wpcatname);
			$categories .=$categorybuild.'<br>';
		}

		$cw_daily_quotes_action=$cw_daily_quotes_action_btn.'ing Quote Section';
		$cw_action .='sv';
$cw_daily_quotes_html .=<<<EOM
<form method="post">
<input type="hidden" name="cw_action" value="$cw_action">
<input type="hidden" name="qid" value="$qid">
<p>Quote Title: <input type="text" name="qtitle" value="$qtitle" style="width: 400px;"></p>
<p>366 Daily Quotes - HTML Markup Supported: (Enter a line break between days)</p>
<p><textarea name="quotes" style="width: 400px; height: 250px;">$quotes</textarea></p>
<p>Where should this daily section be displayed?</p>
<p>$qtypes</p>
<p>Categories:<br>$categories</p>
<p>Custom Layout: <div style="margin-left: 20px;">Optional: This is the layout/theme/style that will be used instead of the general layout.  Leave blank to use general layout.<br><br><b>{{quote_title}}</b> = Display Quote Title<br><b>{{quote}}</b> = Display Daily Quote</div></p>
<p><textarea name="qlayout" style="width: 400px; height: 200px;">$qlayout</textarea></p>
<p><input type="submit" value="$cw_daily_quotes_action_btn" class="button"> &#171;&#171; Please be patient!</p>
</form>
EOM;
		if ($qlayout) {
			$cw_daily_quotes_html .='<p>Saved custom layout preview:</p>'.$qlayout;
		}

		if ($cw_action == 'quoteeditsv') {
$cw_daily_quotes_html .=<<<EOM
<div id="del_link" name="del_link" style="border-top: 1px solid #d6d6cf; margin-top: 20px; padding: 5px; width: 390px;"><a href="javascript:void(0);" onclick="document.getElementById('del_controls').style.display='';document.getElementById('del_link').style.display='none';">Show deletion controls</a></div>
<div name="del_controls" id="del_controls" style="display: none; width: 390px; margin-top: 20px; border: 1px solid #d6d6cf; padding: 5px;">
<a href="javascript:void(0);" onclick="document.getElementById('del_controls').style.display='none';document.getElementById('del_link').style.display='';">Hide deletion controls</a>
<form method="post">
<input type="hidden" name="cw_action" value="quotesdel"><input type="hidden" name="qid" value="$qid">
<p><input type="checkbox" name="dq_confirm_1" value="1"> Check to delete $qtitle</p>
<p><input type="checkbox" name="dq_confirm_2" value="1"> Check to confirm deletion of $qtitle</p>
<p><span style="color: #ff0000; font-weight: bold;">Deletion is final! There is no undoing this action!</span></p>
<p style="text-align: right;"><input type="submit" value="Delete" class="button"></p>
</div>
EOM;
	}

	////////////////////////////////////////////////////////////////////////////
	//	Add/Edit Quotes Save
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'quoteaddsv' or $cw_action == 'quoteeditsv') {
		$qid=$cwfa_dq->cwf_san_int($_REQUEST['qid']);
		$qtitle=trim($_REQUEST['qtitle']);
		$quotes=trim($_REQUEST['quotes']);
		$qtype=$cwfa_dq->cwf_san_an($_REQUEST['qtype']);
		$qcategories=$_REQUEST['qcategories'];
		$qlayout=trim($_REQUEST['qlayout']);

		$error='';

		if (!$qtitle) {
			$error .='<li>No Quote Title</li>';
		}

		$quotes=preg_replace('/\r/','',$quotes);
		if (substr_count($quotes,"\n") != '365') {
			$error .='<li>Need 366 Daily Quotes</li>';
		}

		if (!$qtype) {
			$error .='<li>Choose where to display daily section</li>';
		}

		if ($qcategories) {
			$qcats='';
			foreach ($qcategories as $qcategory) {
				$qcats .=trim($qcategory).'|';
			}
		}

		if (!$qcats and $qtype != 'a') {
			$error .='<li>No Categories selected</li>';
		}

		if ($error) {
			$cw_daily_quotes_action='Error';
			$cw_daily_quotes_html='Please fix the following in order to save settings:<br><ul style="list-style: disc; margin-left: 25px;">'. $error .'</ul>'.$pplink;
		} else {
			$cw_daily_quotes_action='Success';

			//	If set to all categories clear category list
			if ($qtype == 'a') {
				$qcats='';
			}

			//	Set/get quote section id
			if ($cw_action == 'quoteeditsv') {
				$qod_sid=$qid;

				//	If memcached is enabled delete possible record
				if ($dq_memcached == 'on') {
					$curday=date('z');
					$memcache_key=home_url().'-'.$qod_sid.'-'.$curday;
					$memcache_key=hash('whirlpool',$memcache_key);
					$memcached_status=$dq_memcached_conn->get($memcache_key);
					if ($memcached_status) {
						$dq_memcached_conn->delete($memcache_key);
					}
				}
			} else {
				//	Update count
				$dq_count=$dq_wp_option_array['count'];
				$dq_count++;
				$dq_wp_option_array['count']=$dq_count;
				$qod_sid=$dq_count;
			}

			//	Save information
			$dq_wp_option_array['section_titles'][$qod_sid]=$qtitle;
			$dq_wp_option_array['section_types'][$qod_sid]=$qtype;
			$dq_wp_option_array['section_categories'][$qod_sid]=$qcats;
			$dq_wp_option_array['section_layouts'][$qod_sid]=$qlayout;

			$dq_wp_option_array=serialize($dq_wp_option_array);
			$dq_wp_option_chk=get_option($dq_wp_option);

			if (!$dq_wp_option_chk) {
				add_option($dq_wp_option,$dq_wp_option_array);
			} else {
				update_option($dq_wp_option,$dq_wp_option_array);
			}

			//	Save quotes
			$quotes=explode("\n",$quotes);
			$qod_day='0';

			foreach ($quotes as $qod_quote) {
				$data=array();
				$data['qod_quote']=$qod_quote;
			
				if ($cw_action == 'quoteeditsv') {
					$where=array();
					$where['qod_sid']=$qod_sid;
					$where['qod_day']=$qod_day;
					$wpdb->update($cw_daily_quotes_tbl,$data,$where);
				} else {
					$data['qod_sid']=$qod_sid;
					$data['qod_day']=$qod_day;
					$wpdb->insert($cw_daily_quotes_tbl,$data);
					$dl_id=$wpdb->insert_id;
				}
				$qod_day++;
			}

			$qtitle=stripslashes($qtitle);
			$cw_daily_quotes_html='<p>'.$qtitle.' has been successfully saved!</p><p><a href="?page=cw-daily-quotes&cw_action=mainpanel">Continue</a></p>';
		}

	////////////////////////////////////////////////////////////////////////////
	//	Delete Quote Section
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'quotesdel') {
		$qid=$cwfa_dq->cwf_san_int($_REQUEST['qid']);
		$qod_sid=$qid;
		$qtitle=stripslashes($dq_wp_option_array['section_titles'][$qod_sid]);

		if (isset($_REQUEST['dq_confirm_1'])) {
			$dq_confirm_1=$cwfa_dq->cwf_san_int($_REQUEST['dq_confirm_1']);
		} else {
			$dq_confirm_1='0';
		}
		if (isset($_REQUEST['dq_confirm_2'])) {
			$dq_confirm_2=$cwfa_dq->cwf_san_int($_REQUEST['dq_confirm_2']);
		} else {
			$dq_confirm_2='0';
		}

		$cw_daily_quotes_action='Delete Quote Section';

		if (!$qod_sid) {
			$dq_confirm_1='0';
		}

		if ($dq_confirm_1 == '1' and $dq_confirm_2 == '1') {
			$where=array();
			$where['qod_sid']=$qod_sid;
			$wpdb->delete($cw_daily_quotes_tbl,$where);

			unset($dq_wp_option_array['section_titles'][$qod_sid]);
			unset($dq_wp_option_array['section_types'][$qod_sid]);
			unset($dq_wp_option_array['section_categories'][$qod_sid]);
			unset($dq_wp_option_array['section_layouts'][$qod_sid]);

			$dq_wp_option_array=serialize($dq_wp_option_array);
			update_option($dq_wp_option,$dq_wp_option_array);

			//	If memcached is enabled delete possible record
			if ($dq_memcached == 'on') {
				$curday=date('z');
				$memcache_key=home_url().'-'.$qod_sid.'-'.$curday;
				$memcache_key=hash('whirlpool',$memcache_key);
				$memcached_status=$dq_memcached_conn->get($memcache_key);
				if ($memcached_status) {
					$dq_memcached_conn->delete($memcache_key);
				}
			}

			$cw_daily_quotes_html=$qtitle.' has been removed! <a href="?page=cw-daily-quotes">Continue...</a>';
		} else {
			$cw_daily_quotes_html='<span style="color: #ff0000;">Error! You must check both confirmation boxes!</span><br><br>'.$pplink;
		}

	////////////////////////////////////////////////////////////////////////////
	//	Settings
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'settings' or $cw_action == 'settingsv') {

		if ($cw_action == 'settingsv') {
			$cw_daily_quotes_action='Sav';
			$error='';

			$daily_quotes_layout=trim($_REQUEST['daily_quotes_layout']);
			if (!$daily_quotes_layout and $daily_quotes_layout != 'reset') {
				$error .='<li>No General Theme/Layout</li>';
			} else {
				if ($daily_quotes_layout == 'reset') {
					$daily_quotes_layout=$daily_quotes_layout_def;
				}
				$dq_wp_option_array['layout']=$daily_quotes_layout;
			}

			if ($error) {
				$cw_daily_quotes_html='Please fix the following in order to save settings:<br><ul style="list-style: disc; margin-left: 25px;">'. $error .'</ul>'.$pplink;
			} else {
				$dq_wp_option_array=serialize($dq_wp_option_array);
				$dq_wp_option_chk=get_option($dq_wp_option);

				if (!$dq_wp_option_chk) {
					add_option($dq_wp_option,$dq_wp_option_array);
				} else {
					update_option($dq_wp_option,$dq_wp_option_array);
				}

				$cw_daily_quotes_html .='Settings have been saved! <a href="?page=cw-daily-quotes">Continue to Main Menu</a>';
			}

		} else {
			$cw_daily_quotes_action='Edit';
			$daily_quotes_layout=$dq_wp_option_array['layout'];

			if (!$daily_quotes_layout) {
				$daily_quotes_layout=$daily_quotes_layout_def;
			}
			$daily_quotes_layout=stripslashes($daily_quotes_layout);

			$daily_quotes_layout_def=$daily_quotes_layout;
			$daily_quotes_layout_def=preg_replace('/{{quote_title}}/','Quote Title Here',$daily_quotes_layout_def);
			$daily_quotes_layout_def=preg_replace('/{{quote}}/','Daily quote displayed here',$daily_quotes_layout_def);

$cw_daily_quotes_html .=<<<EOM
<form method="post">
<input type="hidden" name="cw_action" value="settingsv">
<p>General Theme/Layout:<div style="margin-left: 20px;">This is the layout/theme/style that will be used when no custom quote layout is provided.<br><br><b>{{quote_title}}</b> = Display Quote Title<br><b>{{quote}}</b> = Display Daily Quote<br><br>Enter the word "reset" without quotes to have the system set the style back to original theme/layout.</div></p>
<p><textarea name="daily_quotes_layout" style="width: 400px; height: 250px;">$daily_quotes_layout</textarea></p>
<p><input type="submit" value="Save" class="button"></p>
</form>
<p>Saved layout preview:</p>
$daily_quotes_layout_def
EOM;
		}
		$cw_daily_quotes_action .='ing Settings';

	////////////////////////////////////////////////////////////////////////////
	//	What Is New?
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'settingsnew') {
		$cw_daily_quotes_action='What Is New?';

$cw_daily_quotes_html .=<<<EOM
<p>The following lists the new changes from version-to-version.</p>
<p>Version: <b>1.0</b></p>
<ul style="list-style: disc; margin-left: 25px;">
<li>Initial release of plugin</li>
</ul>
EOM;

	////////////////////////////////////////////////////////////////////////////
	//	Help Guide
	////////////////////////////////////////////////////////////////////////////
	} elseif ($cw_action == 'settingshelp') {
		$cw_daily_quotes_action='Help Guide';

$cw_daily_quotes_html .=<<<EOM
<div style="margin: 10px 0px 5px 0px; width: 400px; border-bottom: 1px solid #c16a2b; padding-bottom: 5px; font-weight: bold;">Introduction:</div>
<p>This system allows you to display daily changing information such as quotes/tips/snippets on your Wordpress site.  You have total control over the layout/theme of this information.  There is also no limit to the number of daily sections you may place on your site.  In addition you may set a custom layout/theme for any daily section.  Plus you are able to control which categories a specific daily section will be displayed in when your visitors stop by your site.</p>
<p>Note: If you place the shortcode to display daily section information in a text widget and it isn't working, then you need to add a filter code.  At the bottom of this guide you will find the code to add to your theme's <b>functions.php</b> file.  Place the code on a new line and save changes.  If necessary upload the file change to your website.</p>
<p>Steps:</p>
<ol>
<li><p>In <b>Settings</b> edit and save the default/general theme/layout.</p></li>
<li><p>Now add your first daily quotes/tips/snippets section by clicking on <b>Add New Section</b> in the <b>Main Panel</b>.</p></li>
<li><p>There are four pieces of information when adding a daily section:</p>
<ol>
<li>Choose a daily section title.  This will be shown to your visitors unless you remove the daily title token.  When multiple daily sections are to be displayed this title will be used in determining the alphabetical order.</li>
<li>Enter 366 daily pieces of information separated by enter (line break).  This is where you enter your quotes/tips/snippets.  So quote 1 enter quote 2 enter quote 3, etc.  Why 366? This covers leap years, thus the last item will only be shown on those years.</li>
<li>Choose where you want the daily section to appear and if necessary the corresponding categories.  If a post is assigned to multiple categories the first category will be used.  In addition if all categories is selected the daily section will automatically appear in any new categories added to Wordpress.</li>
<li>You may set an unique theme/layout for this daily section.  If left blank the default/general theme in <b>Settings</b> will be used.</li>
</ol>
</li>
<li>Now save the daily section, obviously fixing any errors that are displayed.</li>
<li>Now add the shortcode <b>[cw_daily_quotes]</b> to the area(s) of your Wordpress site (header, footer, widgets, sidebar, post(s), page(s), etc) where you wish the daily sections to be displayed.  Do keep in mind that, by default, Wordpress doesn't process shortcodes in text widgets.  Therefore you will need to add the code below to your <b>functions.php</b> file.</li>
<li>Now add and edit additional daily sections as needed.  Do keep in mind categories with multiple daily sections will display them in alphabetical order by section title.</li>
<li>Optional: This system supports the ability to use the Memcached storage system for optimized daily information loading.  To enable this first verify you have access to Memcached and PHP is setup correctly for this feature; ask your web hosting provider.  Now edit <b>memcached.config.sample.php</b> in the <b>cleverwise-daily-quotes</b> directory in your Wordpress plugins directory.  You'll see two options.  First set the address of the Memcached server.  Second set the port to Memcached, usually the default.  Now save the file as <b>memcached.config.php</b> and upload it to the <b>cleverwise-daily-quotes</b> directory.  To see if it is working load the <b>Main Panel</b> and look at <b>Memcached Status</b>.  It should read "On - optimized quote pulling".  If not check your Memcached settings.  If your Wordpress site displays fatal error don't panic; simply delete <b>memcached.config.php</b> and all will return to normal.  You may try again.  An incorrect Memcached address and/or port won't cause fatal errors, however misediting the file could.</li>
</ol>

<div style="margin: 10px 0px 5px 0px; width: 400px; border-bottom: 1px solid #c16a2b; padding-bottom: 5px; font-weight: bold;">Text widget filter code for your theme's functions.php:</div>
add_filter('widget_text', 'do_shortcode');
EOM;

	////////////////////////////////////////////////////////////////////////////
	//	Main panel
	////////////////////////////////////////////////////////////////////////////
	} else {
		// Current day
		$current_day=date('z')+1;

		// Get daily quote sections
		$daily_sections='';

		$dcnt='0';
		$daily_section_titles=$dq_wp_option_array['section_titles'];

		if ($daily_section_titles) {
			asort($daily_section_titles);
			foreach ($daily_section_titles as $daily_section_title_id => $daily_section_title) {
				$daily_section_title=stripslashes($daily_section_title);
				$dcnt++;
				$daily_sections .='<p>'.$dcnt.') <a href="?page=cw-daily-quotes&cw_action=quotesview&qid='.$daily_section_title_id.'"> '.$daily_section_title.'</a> .:. <a href="?page=cw-daily-quotes&cw_action=quoteedit&qid='.$daily_section_title_id.'">Edit Quote</a></p>';
			}
		}
		if (!$daily_sections) {
			$daily_sections='<p>None! What are you waiting for? Add one!</p>';
		}

		$dq_memcached_chk='Off';
		if ($dq_memcached == 'on') {
			$memcache_key=home_url().'-'.time();
			$memcache_key=hash('whirlpool',$memcache_key);
			$memcached_status=$dq_memcached_conn->set($memcache_key,'pass',0,'15');
			$memcached_status=$dq_memcached_conn->get($memcache_key);
			if ($memcached_status) {
				$dq_memcached_chk='<span style="color: #008000;">On - optimized quote pulling</span>';
			}
		}

$cw_daily_quotes_action='Main Panel';
$cw_daily_quotes_html .=<<<EOM
<p><b>Daily Quote Sections:</b> $dcnt<br>
<a href="?page=cw-daily-quotes&cw_action=quoteadd">Add New Section</a></p>
$daily_sections
<p style="width: 400px; margin-top: 20px; padding-top: 10px; border-top: 1px dashed #000000;">Current Day Count: $current_day</p>
<p>Memcached Status: $dq_memcached_chk</p>
EOM;
	}

	////////////////////////////////////////////////////////////////////////////
	//	Send to print out
	////////////////////////////////////////////////////////////////////////////
	cw_daily_quotes_admin_browser($cw_daily_quotes_html,$cw_daily_quotes_action);
}

////////////////////////////////////////////////////////////////////////////
//	Print out to browser (wp)
////////////////////////////////////////////////////////////////////////////
function cw_daily_quotes_admin_browser($cw_daily_quotes_html,$cw_daily_quotes_action) {
print <<<EOM
<style type="text/css">
#cws-wrap {margin: 20px 20px 20px 0px;}
#cws-wrap a {text-decoration: none; color: #3991bb;}
#cws-wrap a:hover {text-decoration: underline; color: #ce570f;}
#cws-nav {width: 400px; padding: 0px; margin-top: 10px; background-color: #deeaef; -moz-border-radius: 5px; border-radius: 5px;}
#cws-resources {width: 400px; padding: 0px; margin: 40px 0px 20px 0px; background-color: #c6d6ad; -moz-border-radius: 5px; border-radius: 5px; font-size: 12px; color: #000000;}
#cws-resources a {text-decoration: none; color: #28394d;}
#cws-resources a:hover {text-decoration: none; background-color: #28394d; color: #ffffff;}
#cws-inner {padding: 5px;}
</style>
<div id="cws-wrap" name="cws-wrap">
<h2 style="padding: 0px; margin: 0px;">Cleverwise Daily Quotes Management</h2>
<div style="margin-top: 7px; width: 90%; font-size: 10px; line-height: 1;">Adds the ability to display daily changing information sections to your site with total control over the layout/theme.  You may control which categories a daily section appears in and, if desired, a custom theme that is different from the default/general one.  There is no limit to the number of daily sections you may add to your site.</div>
<div id="cws-nav" name="cws-nav"><div id="cws-inner" name="cws-inner"><a href="?page=cw-daily-quotes">Main Panel</a> | <a href="?page=cw-daily-quotes&cw_action=settings">Settings</a> | <a href="?page=cw-daily-quotes&cw_action=settingshelp">Help Guide</a> | <a href="?page=cw-daily-quotes&cw_action=settingsnew">What Is New?</a></div></div>
<p style="font-size: 13px; font-weight: bold;">Current: <span style="color: #ab5c23;">$cw_daily_quotes_action</span></p>
<p>$cw_daily_quotes_html</p>
<div id="cws-resources" name="cws-resources"><div id="cws-inner" name="cws-inner">Resources (open in new windows):<br>
<a href="https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=7VJ774KB9L9Z4" target="_blank">Donate - Thank You!</a> | <a href="http://wordpress.org/support/plugin/cleverwise-daily-quotes" target="_blank">Get Support</a> | <a href="http://wordpress.org/support/view/plugin-reviews/cleverwise-daily-quotes" target="_blank">Review Plugin</a> | <a href="http://www.cyberws.com/cleverwise-plugins/plugin-suggestion/" target="_blank">Suggest Plugin</a><br>
<a href="http://www.cyberws.com/cleverwise-plugins" target="_blank">Cleverwise Plugins</a> | <a href="http://www.cyberws.com/professional-technical-consulting/" target="_blank">Wordpress +PHP,Server Consulting</a></div></div>
</div>
EOM;
}

////////////////////////////////////////////////////////////////////////////
//	Activate
////////////////////////////////////////////////////////////////////////////
function cw_daily_quotes_activate() {
	Global $wpdb,$dq_wp_option_version_txt,$dq_wp_option_version_num,$cw_daily_quotes_tbl;
	require_once(ABSPATH.'wp-admin/includes/upgrade.php');

	$dq_wp_option_db_version=get_option($dq_wp_option_version_txt);

//	Create category table
	$table_name=$cw_daily_quotes_tbl;
$sql .=<<<EOM
CREATE TABLE IF NOT EXISTS `$table_name` (
  `qod_id` int(15) unsigned NOT NULL AUTO_INCREMENT,
  `qod_sid` int(5) unsigned NOT NULL,
  `qod_day` int(3) unsigned NOT NULL,
  `qod_quote` text NOT NULL,
  PRIMARY KEY (`qod_id`),
  KEY `qod_sid` (`qod_sid`,`qod_day`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci AUTO_INCREMENT=1;
EOM;
	dbDelta($sql);
 
//	Insert version number
	if (!$dq_wp_option_db_version) {
		add_option($dq_wp_option_version_txt,$dq_wp_option_version_num);
	}
}