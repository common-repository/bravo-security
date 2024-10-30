<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !class_exists( 'tebravo_wconfig' ) )
{
    class tebravo_wconfig{
        
        //options
        public $dbname;
        public $dbprefix;
        public $posted_wp_prefix_enabled;
        public $wp_prefix_enabled;
        public $posted_wp_prefix;
        public $wp_prefix;
        public $file_perms;
        public $disable_editor;
        public $wp_config_notify_perms;
        public $auto_updates_wp;
        public $auto_updates_themes;
        public $auto_updates_plugins;
        public $wp_errors_debug;
        public $html;
        public $signature;
        public $salt_api;
        public $salt_api_v="1.1";
        
        //constructor
        public function __construct()
        {
        	global $table_prefix;
        	
        	//html class
        	$this->html = new tebravo_html();
        	
        	//wp-config
        	$this->dbname = DB_NAME;
        	$this->dbprefix = $table_prefix;
        	
        	//tebravo options
        	$this->wp_prefix_enabled= trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'wp_prefix_enabled')));
        	
        	$this->wp_prefix= $this->html->init->create_hash(5);
        	
        	$this->disable_editor= trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'disable_editor')));
        	$this->notify_perms= trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'wp_config_notify_perms')));
        	$this->auto_updates_wp= trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'auto_updates_wp')));
        	$this->auto_updates_themes= trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'auto_updates_themes')));
        	$this->auto_updates_plugins= trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'auto_updates_plugins')));
        	$this->disable_wp_errors_debug= trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'disable_wp_errors_debug')));
        	
        	//posted options needed
        	if($_POST){
        		$this->posted_wp_prefix = trim(sanitize_text_field($_POST['wp_prefix']));
        	}
        	
        	//Set signature
        	$signature = PHP_EOL."//Last Update By ".TEBRAVO_PLUGINNAME." Was at ".date('D')." (".date('d - M Y').") [".date('h:i:s')."][".date('A')."]";
        	$this->signature= $signature;
        	
        	//SALTs API
        	$this->salt_api = "https://api.wordpress.org/secret-key/".$this->salt_api_v."/salt/";
        }
        
        //print dashboards
        public function dashboard()
        {
        	global $wpdb;
        	
        	$db = new tebravo_db();
        	if(empty($_GET['p']))
        	{
        		$this->start_dashboard();
        	} else
        	if($_GET['p'] == 'wizard')
        	{
        		$wpdb->show_errors( false );
        		//change prefix wizard
        		$this->change_prefix_wizard();
        	} else 
        	if($_GET['p'] == 'wizard_confirmation')
        	{
        		$wpdb->show_errors( false );
        		//exit and back if is not a posed redirect
        		if(! $_POST){tebravo_redirect_js($this->html->init->admin_url."-wconfig&p=wizard"); exit;}
        		//continue
        		if(!empty($_POST['wp_prefix']))
        		{
        			$wpprefix_string= trim(esc_html( $_POST['wpprefix_string'] ));
        			if($wpprefix_string != 'none')
        			{
        				$new_prefix = $wpprefix_string.$this->posted_wp_prefix;
        			} else {
        				$new_prefix = $this->posted_wp_prefix;
        			}
        		} else {
        			$new_prefix = trim(esc_html($_POST['exists_dbprefix']));
        		}
        		$new_prefix = $new_prefix."_";
        		$dev_mode = '';
        		if( isset($_POST['development_mode']))
        		{
        			$dev_mode = trim(sanitize_text_field($_POST['development_mode']));
        		}
        		$exist_dev_mode = '';
        		if( isset($_POST['exists_development_mode']))
        		{
        			$exist_dev_mode= trim(sanitize_text_field($_POST['exists_development_mode']));
        		}
        		$backupname = $db->backupname;
        		if( isset($_POST['backupname']))
        		{
        			$backupname = trim(sanitize_text_field($_POST['backupname']));
        		}
        		//continue
        		$this->wizard_confirmation(trim(sanitize_text_field($_POST['_nonce'])),
        				trim(sanitize_text_field($_POST['exists_dbprefix'])),
        				$new_prefix,
        				$backupname,
        				$dev_mode,
        				$exist_dev_mode);
        	} else
        		if($_GET['p'] == 'start_change')
        		{
        			$wpdb->show_errors( false );
        			//check capability
        			if(! current_user_can('manage_options')){wp_die(); exit;}
        			$exist_dev_mode = '';
        			if( isset($_POST['development_mode']))
        			{
        				$exist_dev_mode= trim(sanitize_text_field($_POST['exists_devmode']));
        			}
        			//exit and back if is not a posed redirect
        			if(! $_POST){tebravo_redirect_js($this->html->init->admin_url."-wconfig&p=wizard"); exit;}
        			//continue
        			if( isset($_POST['exists_dbprefix']))
        			{
        				$exist_prefix = trim(sanitize_text_field($_POST['exists_dbprefix']));
        			}
        			$new_prefix = $this->wp_prefix;
        			if( isset($_POST['wp_prefix']))
        			{
        				$new_prefix = trim(sanitize_text_field($_POST['wp_prefix']));
        			}
        			$backupname = $db->backupname;
        			if( isset($_POST['backupname']))
        			{
        				$backupname = trim(sanitize_text_field($_POST['backupname']));
        			}
        			
        			//continue
        			$this->change_prefix(trim(sanitize_text_field($_POST['_nonce'])),
        					$exist_prefix,
        					$new_prefix,
        					$exist_dev_mode,
        					$backupname);
        		}
        }
        
        public function start_dashboard()
        {
        	//Tabs Data
        	$tabs["general"] = array("title"=>"Options",
        			"href"=>$this->html->init->admin_url."-settings",
        			"is_active"=> "not");
        	
        	$tabs["wpconfig"] = array("title"=>"WP Config",
        			"href"=>$this->html->init->admin_url."-wconfig",
        			"is_active"=> 'active');
        	
        	$tabs["wpadmin"] = array("title"=>"WP Admin",
        			"href"=>$this->html->init->admin_url."-wadmin",
        			"is_active"=> '');
        	
        	$tabs["bruteforce"] = array("title"=>"Brute Force",
        			"href"=>$this->html->init->admin_url."-bruteforce",
        			"is_active"=> '');
        	
        	$tabs["antivirus"] = array("title"=>"Anti Virus",
        			"href"=>$this->html->init->admin_url."-antivirus&p=settings",
        			"is_active"=> '');
        	
        	$tabs["mail"] = array("title"=>"Email Settings",
        			"href"=>$this->html->init->admin_url."-mail&p=settings",
        			"is_active"=> '');
        	
        	$tabs["recaptcha"] = array("title"=>"reCAPTCHA",
        			"href"=>$this->html->init->admin_url."-recaptcha",
        			"is_active"=> '');
        	
        	$tabs["error_pages"] = array("title"=>"Error Pages",
        			"href"=>$this->html->init->admin_url."-error_pages",
        			"is_active"=> '');
        	
        	//Tabs HTML
        	$desc = "You should take this seriously, You should follow the next steps to be in a better level of security.";
        	$this->html->header(__("WP-Config Tweak Settings", TEBRAVO_TRANS), $desc, 'settings.png');
        	
        	$this->html->tabs($tabs);
        	$this->html->start_tab_content();
        	
        	$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
        	$output[] = "<table border=0 width=100% cellspacing=0>";
        	//DBPREFIX 
        	$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Database Table Prefix", TEBRAVO_TRANS)."</strong></td>";
        	$output[] = "</td>";
        	$output[] = "</tr>";
        	$wp_prefix_desc = "One of the most popular tweaks is to change the default WordPress database prefix, ";
        	$wp_prefix_desc .= "in order to protect your Wordpress against SQL Injection exploits and hijackers attacks.";
        	$output[] = "<tr class='tebravo_underTD'><td width=75%>".__($wp_prefix_desc, TEBRAVO_TRANS)."</td>";
        	$output[] = "<td>".$this->html->button_small_info(__("Open Wizard", TEBRAVO_TRANS), "button", 'open_wizard')."</td>";
        	$output[] = "</tr>";
        	//EDITOR
        	$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Themes and Plugins Editor", TEBRAVO_TRANS)."</strong></td>";
        	$output[] = "</td>";
        	$output[] = "</tr>";
        	$editor_desc = "As a security measure it is recommended to disable the theme and plugin editors in WordPress, ";
        	$editor_desc .= "Why? to protect your files from injecting with malware, blackhat SEO links or inserting SHELL scripts.";
        	$output[] = "<tr class='tebravo_underTD'><td width=75%>".__($editor_desc, TEBRAVO_TRANS)."</td>";
        	if(!defined('DISALLOW_FILE_EDIT') || (defined('DISALLOW_FILE_EDIT') && DISALLOW_FILE_EDIT== false))
        	{
        		$editor_button_action = "Disable Editor";
        		$current_editor_status = "<font color=green>".__("Enabled", TEBRAVO_TRANS)."</font>";
        		$output[] = "<td>".$this->html->button_small_alert(__($editor_button_action, TEBRAVO_TRANS), "button", 'editor_action');
        	} else {
        		$editor_button_action = "Enable Editor";
        		$current_editor_status = "<font color=green>".__("Disabled", TEBRAVO_TRANS)."</font>";
        		$output[] = "<td>".$this->html->button_small_info(__($editor_button_action, TEBRAVO_TRANS), "button", 'editor_action');
        	}
        	
        	$output[] = "<br />".__("Status:", TEBRAVO_TRANS)." ".$current_editor_status."</td></tr>";
        	$output[] = "<tr id='editor_ajax' style='display:none;'><td colspan=2><br /><center><img src='".plugins_url('assets/img/loading.gif', $this->html->init->path)."'></center></td></tr>";
        	$ajax_editor_url = $this->html->init->create_nonce_ajax_url('tebravo_editor_change', 'tebravo_editor_change');
        	
        	//WPCONFIG PERMISSION
        	$output[] = "<A name='configperma'></A><tr class='tebravo_headTD'><td colspan=2><strong>".__("WPConfig File Permissions 'wp-config.php'", TEBRAVO_TRANS)."</strong></td>";
        	$output[] = "</td>";
        	$output[] = "</tr>";
        	$wpconfig_desc = "File Permissions mean, which permissions you as the owner will give to the file, read , write or both. ";
        	$wpconfig_desc.= "We highly recommend to change wp-config.php permissions to read-only '0400' or '0444'.";
        	$wpconfig_desc.= "<br><input type='checkbox' name='".TEBRAVO_DBPREFIX."wp_config_notify_perms' id='wp_config_notify_perms' ";
        	if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'wp_config_notify_perms'))) == 'checked'){$wpconfig_desc.="checked";}
        	$wpconfig_desc.="><label for='wp_config_notify_perms' style='color:blue;'>".__("Always back to read-only 0400 permissions after every process.", TEBRAVO_TRANS)."</label>";
        	$output[] = "<tr class='tebravo_underTD'><td width=75%>".__($wpconfig_desc, TEBRAVO_TRANS)."</td>";
        	$wpconfig_perms = tebravo_files::file_perms(ABSPATH.'wp-config.php');
        	if($wpconfig_perms == '0400' || $wpconfig_perms == '0444')
        	{
        		$wpconfig_button_action = "Back to normal";
        		$current_wpconfig_perms = "<font color=green>".$wpconfig_perms." read-only</font>";
        		$output[] = "<td>".$this->html->button_small_alert(__($wpconfig_button_action, TEBRAVO_TRANS), "button", 'wpconfig_button_action');
        	} else {
        		$wpconfig_button_action= "Enable read-only";
        		$current_wpconfig_perms= "<font color=red>".$wpconfig_perms."</font>";
        		$output[] = "<td>".$this->html->button_small_info(__($wpconfig_button_action, TEBRAVO_TRANS), "button", 'wpconfig_button_action');
        	}
        	
        	$output[] = "<br />".__("Current:", TEBRAVO_TRANS)." ".$current_wpconfig_perms."</td></tr>";
        	$output[] = "<tr id='wpconfig_ajax' style='display:none;'><td colspan=2><br /><center><img src='".plugins_url('assets/img/loading.gif', $this->html->init->path)."'></center></td></tr>";
        	$ajax_wpconfig_url = $this->html->init->create_nonce_ajax_url('tebravo_wpconfig_change_permissions', 'tebravo_wpconfig_change_permissions');
        	
        	//AUTO UPDATES
        	$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Wordpress Automatic Updates", TEBRAVO_TRANS)."</strong></td>";
        	$output[] = "</td>";
        	$output[] = "</tr>";
        	//Wordpress Core
        	$wordpress_desc = __("Wordpress Core Updates", TEBRAVO_TRANS)." <br />&nbsp;&nbsp;<font color=red><i>".__("Very Very Important", TEBRAVO_TRANS)."</i></font>";
        	$output[] = "<tr class='tebravo_underTD'><td width=75%>".__($wordpress_desc, TEBRAVO_TRANS)."</td>";
        	if(!defined('WP_AUTO_UPDATE_CORE') || (defined('WP_AUTO_UPDATE_CORE') && WP_AUTO_UPDATE_CORE != true))
        	{
        		$wordpress_button_action = "Enable Updates";
        		$current_wordpress_status= "<font color=red>".__("Disabled", TEBRAVO_TRANS)."</font>";
        		$output[] = "<td>".$this->html->button_small_info(__($wordpress_button_action, TEBRAVO_TRANS), "button", 'wordpress_button_action');
        	} else {
        		$wordpress_button_action= "Disable Updates";
        		$current_wordpress_status= "<font color=green>".__("Enabled", TEBRAVO_TRANS)."</font>";
        		$output[] = "<td>".$this->html->button_small_alert(__($wordpress_button_action, TEBRAVO_TRANS), "button", 'wordpress_button_action');
        	}
        	
        	$output[] = "<br />".__("Current:", TEBRAVO_TRANS)." ".$current_wordpress_status."</td></tr>";
        	$output[] = "<tr id='wordpress_ajax' style='display:none;'><td colspan=2><br /><center><img src='".plugins_url('assets/img/loading.gif', $this->html->init->path)."'></center></td></tr>";
        	$ajax_wordpress_url= $this->html->init->create_nonce_ajax_url('tebravo_wordpress_auto_updates', 'tebravo_wordpress_auto_updates');
        	//Themes
        	$themes_desc = __("Themes Automatic Updates", TEBRAVO_TRANS)." <br />";
        	$output[] = "<tr class='tebravo_underTD'><td width=75%>".__($themes_desc, TEBRAVO_TRANS)."</td>";
        	if(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'auto_updates_themes') != 'checked')
        	{
        		$themes_button_action = "Enable Updates";
        		$current_themes_status= "<font color=red>".__("Disabled", TEBRAVO_TRANS)."</font>";
        		$output[] = "<td>".$this->html->button_small_info(__($themes_button_action, TEBRAVO_TRANS), "button", 'themes_button_action');
        	} else {
        		$themes_button_action= "Disable Updates";
        		$current_themes_status= "<font color=green>".__("Enabled", TEBRAVO_TRANS)."</font>";
        		$output[] = "<td>".$this->html->button_small_alert(__($themes_button_action, TEBRAVO_TRANS), "button", 'themes_button_action');
        	}
        	
        	$output[] = "<br />".__("Status:", TEBRAVO_TRANS)." ".$current_themes_status."</td></tr>";
        	$output[] = "<tr id='themes_ajax' style='display:none;'><td colspan=2><br /><center><img src='".plugins_url('assets/img/loading.gif', $this->html->init->path)."'></center></td></tr>";
        	$ajax_themes_url = $this->html->init->create_nonce_ajax_url('tebravo_themes_auto_updates', 'tebravo_themes_auto_updates');
        	//Plugins
        	$plugins_desc = __("Plugins Automatic Updates", TEBRAVO_TRANS)." <br />";
        	$output[] = "<tr class='tebravo_underTD'><td width=75%>".__($plugins_desc, TEBRAVO_TRANS)."</td>";
        	if(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'auto_updates_plugins') != 'checked')
        	{
        		$plugins_button_action = "Enable Updates";
        		$current_plugins_status= "<font color=red>".__("Disabled", TEBRAVO_TRANS)."</font>";
        		$output[] = "<td>".$this->html->button_small_info(__($plugins_button_action, TEBRAVO_TRANS), "button", 'plugins_button_action');
        	} else {
        		$plugins_button_action= "Disable Updates";
        		$current_plugins_status= "<font color=green>".__("Enabled", TEBRAVO_TRANS)."</font>";
        		$output[] = "<td>".$this->html->button_small_alert(__($plugins_button_action, TEBRAVO_TRANS), "button", 'plugins_button_action');
        	}
        	
        	$output[] = "<br />".__("Status:", TEBRAVO_TRANS)." ".$current_plugins_status."</td></tr>";
        	$output[] = "<tr id='plugins_ajax' style='display:none;'><td colspan=2><br /><center><img src='".plugins_url('assets/img/loading.gif', $this->html->init->path)."'></center></td></tr>";
        	$ajax_plugins_url = $this->html->init->create_nonce_ajax_url('tebravo_plugins_auto_updates', 'tebravo_plugins_auto_updates');
        	
        	//errors_debug
        	$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Display Errors & Debug", TEBRAVO_TRANS)."</strong></td>";
        	$output[] = "</td>";
        	$output[] = "</tr>";
        	$errors_debug_desc = "Debug and error display is a dangerous tool for a public website, but it is a useful tool for developers,";
        	$errors_debug_desc .= "So, If you do not need it, please disable it to prevent hackers from knowing what's errors and bugs in your code or files.<br />";
        	$errors_debug_desc .= "<i><font color=blue>Do not worry, BRAVO will enable a local and secure debug you'll browse it under 'Log Watching' menu.</font></i>";
        	$output[] = "<tr class='tebravo_underTD'><td width=75%>".__($errors_debug_desc, TEBRAVO_TRANS)."</td>";

        	if( !defined( 'WP_DEBUG' ) 
        			|| (defined( 'WP_DEBUG' ) && WP_DEBUG !== true ) )
        	{
        		$errors_debug_button_action = "Enable Debug";
        		$current_errors_debug = "<font color=green>Disabled</font>";
        		$output[] = "<td>".$this->html->button_small_alert(__($errors_debug_button_action, TEBRAVO_TRANS), "button", 'errors_debug_button_action');
        	} else {
        		$errors_debug_button_action= "Disable Debug";
        		$current_errors_debug= "<font color=red>Enabled</font>";
        		$output[] = "<td>".$this->html->button_small_info(__($errors_debug_button_action, TEBRAVO_TRANS), "button", 'errors_debug_button_action');
        	}
        	
        	$output[] = "<br />".__("Status:", TEBRAVO_TRANS)." ".$current_errors_debug."</td></tr>";
        	$output[] = "<tr id='errors_debug_ajax' style='display:none;'><td colspan=2><br /><center><img src='".plugins_url('assets/img/loading.gif', $this->html->init->path)."'></center></td></tr>";
        	$ajax_errors_debug_url = $this->html->init->create_nonce_ajax_url('tebravo_errors_debug_enable_disable', 'tebravo_errors_debug_enable_disable');
        	
        	//auth_keys
        	$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Authentication Unique Keys and Salts", TEBRAVO_TRANS)."</strong></td>";
        	$output[] = "</td>";
        	$output[] = "</tr>";
        	$auth_keys_desc = "WordPress depends on the safety of these salts, once they are compromised the security behind authentication is relatively weak. ";
        	$auth_keys_desc .= "So, you must update these SALTs or keys periodically.<br />";
        	$auth_keys_desc .= "<font color=red><b>Alert:</b> You and all logged in users will be logged out once the change has been done!<font>";
        	$output[] = "<tr class='tebravo_underTD'><td width=75%>".__($auth_keys_desc, TEBRAVO_TRANS)."</td>";
        	
        	
        		$auth_keys_button_action = "Update Now";
        		if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'auth_keys_last_update'))) != '')
        		{
        			$current_auth_keys_lastupdate = "<font color=green>".tebravo_ago(trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'auth_keys_last_update'))))."</font>";
        		} else {
        			$current_auth_keys_lastupdate= "N/A";
        		}
        		
        		$output[] = "<td>".$this->html->button_small_info(__($auth_keys_button_action, TEBRAVO_TRANS), "button", 'auth_keys_button_action');
        	
        	
        		$output[] = "<br />".__("Last Update:", TEBRAVO_TRANS)." ".$current_auth_keys_lastupdate."</td></tr>";
        	$output[] = "<tr id='auth_keys_ajax' style='display:none;'><td colspan=2><br /><center><img src='".plugins_url('assets/img/loading.gif', $this->html->init->path)."'></center></td></tr>";
        	$ajax_auth_keys_url = $this->html->init->create_nonce_ajax_url('tebravo_auth_keys_update', 'tebravo_auth_keys_update');
        	
        	$notify_config_perms_url= $this->html->init->create_nonce_ajax_url('tebravo_notify_config_perms', 'tebravo_notify_config_perms');
        	
        	
        	$output[] = "</table>";
        	$output[] = "</div>";
        	$output[] = "<div id='tebravo_results'></div>";
        	
        	echo implode("\n", $output);
        	
        	echo "<script>tebravo_open_modal();</script>";
        	echo '<script>';
        	echo 'jQuery("#open_wizard").click(function(){';
        	echo 'window.location.href= "'.$this->html->init->admin_url.'-wconfig&p=wizard'.'"';
        	echo '});';
        	//Editor
        	echo 'jQuery("#editor_action").click(function(){';
        	echo 'jQuery("#editor_ajax").show(500);';
        	echo 'jQuery("#editor_ajax").load("'.$ajax_editor_url.'");';
        	echo '});';
        	//WPCONFIG
        	echo 'jQuery("#wpconfig_button_action").click(function(){';
        	echo 'jQuery("#wpconfig_ajax").show(500);';
        	echo 'jQuery("#wpconfig_ajax").load("'.$ajax_wpconfig_url.'");';
        	echo '});';
        	//WORDPRESS
        	echo 'jQuery("#wordpress_button_action").click(function(){';
        	echo 'jQuery("#wordpress_ajax").show(500);';
        	echo 'jQuery("#wordpress_ajax").load("'.$ajax_wordpress_url.'");';
        	echo '});';
        	//THEMES
        	echo 'jQuery("#themes_button_action").click(function(){';
        	echo 'jQuery("#themes_ajax").show(500);';
        	echo 'jQuery("#themes_ajax").load("'.$ajax_themes_url.'");';
        	echo '});';
        	//PLUGINS
        	echo 'jQuery("#plugins_button_action").click(function(){';
        	echo 'jQuery("#plugins_ajax").show(500);';
        	echo 'jQuery("#plugins_ajax").load("'.$ajax_plugins_url.'");';
        	echo '});';
        	//DEBUG
        	echo 'jQuery("#errors_debug_button_action").click(function(){';
        	echo 'jQuery("#errors_debug_ajax").show(500);';
        	echo 'jQuery("#errors_debug_ajax").load("'.$ajax_errors_debug_url.'");';
        	echo '});';
        	//AUTH
        	echo 'jQuery("#auth_keys_button_action").click(function(){';
        	echo 'jQuery("#auth_keys_ajax").show(500);';
        	echo 'jQuery("#auth_keys_ajax").load("'.$ajax_auth_keys_url.'");';
        	echo '});';
        	//Notify 0400
        	echo 'jQuery("#wp_config_notify_perms").change(function() {';
        	echo 'jQuery("#tebravo_results").load("'.$notify_config_perms_url.'");';
        	echo '});';
        	echo '</script>';
        	#$this->replace_slats();
        	$this->html->end_tab_content();
        	$this->html->footer();
        }
        
        //changes on wpconfig to wp core updates
        public function wp_core_updates()
        {
        	$config_path = ABSPATH.'wp-config.php';
        	
        	//backup config
        	$this->backup_wpconfig(false);
        	
        	if(defined('WP_AUTO_UPDATE_CORE'))
        	{
        		$configContent = $big_configContent = tebravo_files::read( $config_path );
        		
        		if(WP_AUTO_UPDATE_CORE == true)
        		{
        			$value = 'false';
        			$old_value = 'true';
        		} else {
        			$value = 'true';
        			$old_value = 'false';
        		}
        		
        		$configContent = $this->replace_string_config($old_value, $value, 'WP_AUTO_UPDATE_CORE');
        		//write to config
        		$current_config_perma = tebravo_files::file_perms($config_path);
        		$new_perma = $current_config_perma;
        		if( !is_writable( $config_path ) )
        		{
        			$new_perma = '0666';
        			tebravo_files::dochmod($config_path, $new_perma);
        		}
        		$written = tebravo_files::write( $config_path , $configContent);
        		
        		if( $new_perma != $current_config_perma )
        		{
        			tebravo_files::dochmod($config_path, $current_config_perma);
        		}
        		
        		$def_str = 'define("WP_AUTO_UPDATE_CORE", '.$value.');';
        		$str = preg_replace('/\s+/', '', $def_str);
        		$length_small = strlen($str);
        		$length_big = @sizeof($big_configContent);
        		
        		if(($length_big - $length_small) == @sizeof($configContent)){
        			return true;
        		} else {
        			return false;
        		}
        	} else {
        		$configContent = PHP_EOL.'define("WP_AUTO_UPDATE_CORE", true);'.PHP_EOL;
        		//write to config
        		$results = tebravo_files::write( $config_path , $configContent, true);
        		return $results;
        	}
        	
        }
        
        //changes on wpconfig to change file error debug status
        public function error_debug_config()
        {
        	$config_path = ABSPATH.'wp-config.php';
        	
        	//backup config
        	$this->backup_wpconfig(false);
        	
        	if(defined('WP_DEBUG'))
        	{
        		$configContent = $big_configContent = tebravo_files::read( $config_path );
        		
        		if(WP_DEBUG == true)
        		{
        			$value = 'false';
        			$old_value = 'true';
        		} else {
        			$value = 'true';
        			$old_value = 'false';
        		}
        		
        		$configContent = $this->replace_string_config($old_value, $value, 'WP_DEBUG');
        		//write to config
        		$written = tebravo_files::write( $config_path , $configContent);
        		$def_str = 'define("WP_DEBUG", '.$value.');';
        		$str = preg_replace('/\s+/', '', $def_str);
        		$length_small = strlen($str);
        		$length_big = @sizeof($big_configContent);
        		
        		if(($length_big - $length_small) == @sizeof($configContent)){
        			return true;
        		} else {
        			return false;
        		}
        	} else {
        		$configContent = PHP_EOL.'define("WP_DEBUG", true);'.PHP_EOL;
        		//write to config
        		$results = tebravo_files::write( $config_path , $configContent, true);
        		return $results;
        	}
        	
        	
        }
        
        //changes on wpconfig to change file editor status
        public function file_editor()
        {
        	$config_path = ABSPATH.'wp-config.php';
        	
        	//backup config
        	$this->backup_wpconfig(false);
        	
        	if(defined('DISALLOW_FILE_EDIT'))
        	{
        		$configContent = $big_configContent = tebravo_files::read( $config_path );
        		$constants = array(
        				'DISALLOW_FILE_EDIT'
        		);
        		
        		if(DISALLOW_FILE_EDIT == true)
        		{
        			$value = 'false';
        			$old_value = 'true';
        		} else {
        			$value = 'true';
        			$old_value = 'false';
        		}
        		
        		$configContent = $this->replace_string_config($old_value, $value, 'DISALLOW_FILE_EDIT');
        		//write to config
        		$written = tebravo_files::write( $config_path , $configContent);
        		$def_str = 'define("DISALLOW_FILE_EDIT", '.$value.');';
        		$str = preg_replace('/\s+/', '', $def_str); 
        		$length_small = strlen($str);
        		$length_big = @sizeof($big_configContent);
        		
        		if(($length_big - $length_small) == @sizeof($configContent)){
        			return true;
        		} else {
        			return false;
        		}
        	} else {
        		$configContent = PHP_EOL.'define("DISALLOW_FILE_EDIT", true);'.PHP_EOL;
        		//write to config
        		$results = tebravo_files::write( $config_path , $configContent, true);
        		return $results;
        	}
        	
        	//permissions
        	if((esc_html(tebravo_utility::get_option( TEBRAVO_DBPREFIX.'wp_config_notify_perms' ))) == 'checked')
        	{
        		tebravo_files::dochmod($config_path, 0400);
        	}
        }
        
        //replace salts
        public function replace_salts()
        {
        	$salts = $this->get_new_salts();
        	$config_path = ABSPATH.'wp-config.php';
        	
        	if(!empty($salts))
        	{
        		$configContent = tebravo_files::read( $config_path );
        		$constants = array(
        			'AUTH_KEY',	
        			'SECURE_AUTH_KEY',	
        			'LOGGED_IN_KEY',	
        			'NONCE_KEY',	
        			'AUTH_SALT',	
        			'SECURE_AUTH_SALT',	
        			'LOGGED_IN_SALT',	
        			'NONCE_SALT',	
        		);
        		
        		foreach ($constants as $define)
        		{
        			$salt = array_pop( $salts );
        			//create salt manually if ematy
        			if(empty($salt))
        			{
        				$salt = $this->html->init->create_salt( 64 );
        			}
        			//Config Data
        			$salt = str_replace( '$', '\\$', $salt );
        			$regex = "/(define\s*\(\s*(['\"])$define\\2\s*,\s*)(['\"]).+?\\3(\s*\)\s*;)/";
        			$configContent = preg_replace( $regex, "\${1}'$salt'\${4}", $configContent);
        		}
        		
        		//backup config
        		$this->backup_wpconfig(false);
        		//write to config
        		$results = tebravo_files::write( $config_path , $configContent);
        		return $results;
        	} else {
        		//error
        		return false;
        	}
        	
        }
        
        //get fresh SALTs
        public function get_new_salts()
        {
        	$content = wp_remote_get( $this->salt_api );;
        	
        	$content= explode( "\n", wp_remote_retrieve_body( $content) );
        	foreach ( $content as $key => $value ) {
        		$content[$key] = substr( $value, 28, 64 );
        	}
        	
        	return $content;
        }
        //databse backup from tebravo_db class
        protected function db_backup( $filename )
        {
        	//call tebravo_db class
        	$db = new tebravo_db();
        	
        	//backup database now
        	$db->backup(false, $filename);
        	
        	//check it backup done or exit process
        	if( !$db->is_backuped )
        	{
        		tebravo_redirect_js($this->html->init->admin_url."-wconfig&p=wizard&err=010"); 
        		exit;
        	}
        }
        
        //Alter table
        protected function alter_table($oldname, $newname)
        {
        	global $wpdb;
        	$query = "RENAME TABLE `".$oldname."` TO `".$newname."`";
        	if(false === $wpdb->query($query))
        	{
        		return false;
        	} else {
        		return true;
        	}
        }
        
        //Update column
        protected function update_column($oldname, $newname, $tbl_name, $column_name, $where, $args)
        {
        	global $wpdb;
        	$query = $wpdb->prepare("UPDATE ".$tbl_name.
        			"SET ".$column_name." = '" .$newname. "' 
					WHERE ".$column_name." = ".$where , $args);
        	
        	if(false === $wpdb->query($query))
        	{
        		return false;
        	} else {
        		return true;
        	}
        }
        
        //change prefix
        public function change_prefix($nonce, $oldprefix, $newprefix, $exists_devmode, $backupname)
        {
        	global $wpdb;
        	global $table_prefix;
        	$wpdb->show_errors(false);
        	//check posted nonce
        	if(empty($nonce)
        			|| false == wp_verify_nonce($nonce, $this->html->init->security_hash.'confirm_change_wp_prefix'))
        	{
        		tebravo_redirect_js($this->html->init->admin_url."-wconfig&p=wizard&err=02"); exit;
        	}
        	
        	//check posted prefixs
        	if($oldprefix == $newprefix
        			|| empty($oldprefix)
        			|| empty($newprefix))
        	{
        		tebravo_redirect_js($this->html->init->admin_url."-wconfig&p=wizard&err=07"); exit;
        	}
        	
        	//check posted backupfilename
        	if(empty($backupname))
        	{
        		tebravo_redirect_js($this->html->init->admin_url."-wconfig&p=wizard&err=07"); exit;
        	}
        	
        	//databse backup
        	$this->db_backup( $backupname );
        	//wp-config backup
        	$this->backup_wpconfig();
        	//set oldprefix lenght
        	$oldprefix_length = strlen($oldprefix);
        	
        	if($_POST['tbl'] 
        			&&$_POST['options'])
        	{
        		
        		$desc = "Changing Wordpress database prefix. Check the green color status, If okay click `Continue` button or you should to replace your new config and database with the files were stored in the backup directory.";
        		$this->html->header("Changing WP Prefix Wizard -Confirmation", $desc, "wizard.png");
        		
        		$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
        		$output[] = "<table border=0 width=100% cellspacing=0>";
        		//List Tables
        		$output[] = "<tr class='tebravo_headTD'><td colspan=4><strong>".__("Tables Process", TEBRAVO_TRANS)."</strong></td>";
        		$output[] = "</td>";
        		$output[] = "</tr>";
        		
        		//Tables
        		foreach ($_POST['tbl'] as $tbl_name)
        		{
        		    $tbl_name = sanitize_text_field( $tbl_name );
        			$new_tbl_name = str_replace($oldprefix, $newprefix, $tbl_name);
        			if($this->alter_table($tbl_name, $new_tbl_name)){
        				$error = 0;
        				$tbl_status = "<font color=green>".__("Success", TEBRAVO_TRANS)."</font>";
        			} else {
        				$error = 1;
        				$tbl_status = "<font color=red>".__("Failed", TEBRAVO_TRANS)."</font>";
        			}
        			$output[] = "<tr class='tebravo_underTD'>";
        			$output[] = "<td width=45%>".$tbl_name."</td>";
        			$output[] = "<td width=5%><center>&rArr;</center></td>";
        			$output[] = "<td width=45%>".$new_tbl_name."</td>";
        			$output[] = "<td width=5%>".$tbl_status."</td>";
        			$output[] = "</tr>";
        			
        		}
        		
        		$output[] = "<tr class='tebravo_headTD'><td colspan=4><strong>".__("OPTION Table Process", TEBRAVO_TRANS)."</strong></td>";
        		$output[] = "</td>";
        		$output[] = "</tr>";
        		
        		//Options
        		foreach ($_POST['options'] as $options_name)
        		{
        		    $options_name = sanitize_text_field ( $options_name );
        			$options_table = $newprefix . substr($oldprefix.'options', $oldprefix_length);
        			
        			$new_options_name = $newprefix . substr($options_name, $oldprefix_length);
        			#echo $options_table . ": " .$new_options_name." - ".$options_name;
        		
        			$query_options = $wpdb->prepare("UPDATE " . $options_table. "
                                                                  SET option_name = '".$new_options_name."'
                                                                  WHERE option_name = %s LIMIT 1", $options_name);
        			
        			//multisite option table
        			if( isset($_POST['mu_options']) && !empty( $_POST['mu_options'] ) )
        			{
        			    $this->update_mu_options( esc_html( $_POST['mu_options'] ), $oldprefix, $newprefix);
        			}
        			
        			if ( false !== $wpdb->query($query_options) ) 
        			{
        				$error = 0;
        				$option_status= "<font color=green>".__("Success", TEBRAVO_TRANS)."</font>";
        			} else {
        				$error = 1;
        				$option_status= "<font color=red>".__("Failed", TEBRAVO_TRANS)."</font>";
        			}
        			
        			$output[] = "<tr class='tebravo_underTD'>";
        			$output[] = "<td width=45%>".$options_name."</td>";
        			$output[] = "<td width=5%><center>&rArr;</center></td>";
        			$output[] = "<td width=45%>".$new_options_name."</td>";
        			$output[] = "<td width=5%>".$option_status."</td>";
        			$output[] = "</tr>";
        		}
        		
        		$output[] = "<tr class='tebravo_headTD'><td colspan=4><strong>".__("USERMETA Table Process", TEBRAVO_TRANS)."</strong></td>";
        		$output[] = "</td>";
        		$output[] = "</tr>";
        		
        		//UserMeta
        		$query_meta = "SELECT user_id, meta_key
                        FROM " . $newprefix . "usermeta
                        WHERE meta_key
                        LIKE '" . $oldprefix . "%'";
        		
        		$meta_keys = $wpdb->get_results( $query_meta);
        		
        		foreach ($meta_keys as $meta_key)
        		{
        			$new_meta_key = $newprefix . substr($meta_key->meta_key, $oldprefix_length);
        			$new_meta_key_query = $wpdb->prepare("UPDATE " . $newprefix . "usermeta
                                                            SET meta_key='" . $new_meta_key . "'
                                                            WHERE meta_key=%s AND user_id=%s", $meta_key->meta_key, $meta_key->user_id);
        			
        			if(false !== $wpdb->query($new_meta_key_query))
        			{
        				$error = 0;
        				$user_meta_status= "<font color=green>".__("updated successfully", TEBRAVO_TRANS)."</font>";
        			} else {
        				$error = 1;
        				$user_meta_status= "<font color=green>".__("failed to update", TEBRAVO_TRANS)."</font>";
        			}
        		} 
        		
        		$output[] = "<tr class='tebravo_headTD'><td colspan=4><i>".__("Needed data ", TEBRAVO_TRANS)." ".$user_meta_status."</i></td>";
        		
        		$output[] = "</table>";
        		
        		
        		$output[] = $this->html->button(__("Continue", TEBRAVO_TRANS), "button", "continue");
        		$output[] = "</div>";
        		
        		//Dev Mode
        		if($error < 1)
        		{
        			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'development_mode', $exists_devmode);
        		}
        		
        		echo implode("\n", $output);
        		
        		echo '<script>';
        		echo 'jQuery("#continue").click(function(){';
        		echo 'window.location.href= "'.$this->html->init->admin_url.'-wconfig&p=wizard&msg=04'.'"';
        		echo '});';
        		echo '</script>';
        	} else {
        		tebravo_redirect_js($this->html->init->admin_url."-wconfig&p=wizard&err=011"); exit;
        	}
        	
        	//write new config
        	$config_file = ABSPATH.'wp-config.php';
        	$new_config_data = $this->replace_string_config($oldprefix, $newprefix, '$table_prefix=');
        	//$this->copy_file($new_config_data, $config_file);
        	
        	$current_notis_value = trim( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'wp_config_notify_perms'));
        	$current_config_perma = tebravo_files::file_perms($config_file);
        	
        	$new_notis_value = '';
        	$reset_notis = '';
        	if( $current_notis_value != $new_notis_value )
        	{
        		tebravo_utility::update_option(TEBRAVO_DBPREFIX.'wp_config_notify_perms', '');
        		$reset_notis = 'yes';
        	}
        	$dirperms_changed = 0;
        	if( !is_writable( $config_file) )
        	{
        		$new_perma = '0666';
        		tebravo_files::dochmod($config_file, $new_perma);
        		$dirperms_changed = 1;
        	}
        	$written = tebravo_files::write( $config_file, $new_config_data);
        	
        	if( !is_writable($config_file) )
        	{
        		$old_prefix_alert = '$table_prefix  = \''.trim($oldprefix).'\';';
        		$new_prefix_alert = '$table_prefix  = \''.trim($newprefix).'\';';
        		
        		$message = __("If wp-config.php do not update, Please do it manually to avoid the blog stop!", TEBRAVO_TRANS);
        		$message .= "<hr><br />";
        		$message .= "<strong>".__("Config Path", TEBRAVO_TRANS)."</strong>:<br />";
        		$message .= "<i>".$config_file."</i><br />";
        		$message .= "<br />".__("Find this string", TEBRAVO_TRANS)." <u><i>".$old_prefix_alert."</i></u>";
        		$message .= " and replace it with this new string <u><i>".$new_prefix_alert."</i></u>";
        		tebravo_die(true, $message, false, true);
        	}
        	
        	if( $reset_notis=='yes' ){tebravo_utility::update_option(TEBRAVO_DBPREFIX.'wp_config_notify_perms', 'checked');}
        	
        	//if permessions changed by BRAVO trun it back to read only
        	if( $dirperms_changed == 1 )
        	{
        		tebravo_files::dochmod($config_file, $current_config_perma);
        	}
        	
        	$this->html->footer();
        }
        
        //change user_roles in MU options tables
        private function update_mu_options( $posted=array(), $oldprefix, $newprefix)
        {
        	global $wpdb;
        	
        	$wpdb->show_errors(true);
        	foreach ($_POST['mu_options'] as $optionsTable )
        	{
        	    $optionsTable = sanitize_text_field( $optionsTable );
        		$options_table = str_replace($oldprefix, $newprefix, $optionsTable);
        		
        		$query = "SELECT * FROM ".$options_table." WHERE `option_name` like'%user_roles%'";
        		$row = $wpdb->get_row( $query );
        		//var_dump( $row )."<hr>";
        		$new_options_name = str_replace($oldprefix, $newprefix, $row->option_name);
        		//echo $options_table." : ".$new_options_name." -> ".$row->option_name."<br />";
        		
        		$wpdb->update($options_table,
        				array( 'option_name' => $new_options_name),
        				array( 'option_id' => $row->option_id));
        		
        	}
        }
        //the wizard confirmation before change dbprefix
        public function wizard_confirmation($nonce, $oldprefix, $newprefix, $backupname, $devmode, $exists_devmode)
        {
        	global $wpdb;
        	global $table_prefix;
        	
        	//call tebravo_db class
        	$db = new tebravo_db();
        	
        	//check posted nonce
        	if(empty($nonce)
        			|| false == wp_verify_nonce($nonce, $this->html->init->security_hash.'change-db-prefix'))
        	{
        		tebravo_redirect_js($this->html->init->admin_url."-wconfig&p=wizard&err=02"); exit;
        	}
        	//check posted prefixs
        	if($oldprefix == $newprefix
        			|| empty($oldprefix)
        			|| empty($newprefix))
        	{
        		tebravo_redirect_js($this->html->init->admin_url."-wconfig&p=wizard&err=07"); exit;
        	}
        	
        	//check posted backupfilename
        	if(empty($backupname))
        	{
        		tebravo_redirect_js($this->html->init->admin_url."-wconfig&p=wizard&err=07"); exit;
        	}
        	 
        	//Development Mode
        	if($devmode == 'checked')
        	{
        		tebravo_utility::update_option(TEBRAVO_DBPREFIX.'development_mode', 'checked');
        	}
        	//set oldprefix lenght
        	$oldprefix_length = strlen($oldprefix);
        	
        	//HTML
        	$desc = "Please check the list below and confirm this process to complete.";
        	$this->html->header("Changing WP Prefix Wizard -Confirmation", $desc, "wizard.png");
        	
        	$tables = $wpdb->get_results('SHOW TABLES');
        	$slug = 'Tables_in_'.DB_NAME;
        	
        	$print_compars= '';
        	foreach ($tables as $table)
        	{
        		$old_table_name = $table->$slug;
        		$new_table_name = $newprefix . substr($old_table_name, $oldprefix_length);
        		
        		$print_compars .= "<tr class='tebravo_underTD'><td width=4%><input type='checkbox' checked name='tbl[]#tebravo' value='$old_table_name'></td>";
        		$print_compars .= "<td width=36%>".str_replace($oldprefix, '<font color=red>'.$oldprefix.'</font>', $old_table_name)."</td>";
        		$print_compars .= "<td width=14%>&rArr;</td>";
        		$print_compars .= "<td width=46%>".str_replace($newprefix, '<font color=blue>'.$newprefix.'</font>', $new_table_name)."</td></tr>";
        	}
        	
        	$old_user_roles = $oldprefix.'user_roles';
        	$new_user_roles = $newprefix.'user_roles';
        	
        	$user_roles_compars = "<tr class='tebravo_underTD'><td width=4%><input type='checkbox' checked name='options[]#tebravo' value='".$old_user_roles."'></td>";
        	$user_roles_compars .= "<td width=36%>".str_replace($oldprefix, '<font color=red>'.$oldprefix.'</font>', $old_user_roles)."</td>";
        	$user_roles_compars .= "<td width=14%>&rArr;</td>";
        	$user_roles_compars .= "<td width=46%>".str_replace($newprefix, '<font color=blue>'.$newprefix.'</font>', $new_user_roles)."</td></tr>";
        	
        	//Gte old Table meta
        	
        	
        	//HTML
        	$output[] = "<form action='".$this->html->init->admin_url."-wconfig&p=start_change' method=post>";
        	$output[] = "<input type='hidden' name='_nonce' value='".$this->html->init->create_nonce('confirm_change_wp_prefix')."'>";
        	$output[] = "<input type='hidden' name='exists_dbprefix' value='".$table_prefix."'>";
        	$output[] = "<input type='hidden' name='wp_prefix' value='".$newprefix."'>";
        	$output[] = "<input type='hidden' name='exists_devmode' value='".$exists_devmode."'>";
        	$output[] = "<input type='hidden' name='backupname' value='".$backupname."'>";
        	$mu = '';
        	if( function_exists('is_multisite') && is_multisite() )
        	{
        		$sites = tebravo_utility::get_sites();
        		if( is_array($sites) )
        		{
        			foreach ( $sites as $site )
        			{
        				if( $site->blog_id != 1)
        				{
        					$mu .= "<input type='hidden' name='mu_options[]' value='".$oldprefix.$site->blog_id."_options'>";
        				}
        			}
        		}
        	}
        	$output[] = $mu;
        	$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
        	$output[] = "<table border=0 width=100% cellspacing=0>";
        	//List Tables
        	$output[] = "<tr class='tebravo_headTD'><td colspan=4><strong>".__("Tables List", TEBRAVO_TRANS)."</strong></td>";
        	$output[] = "</td>";
        	$output[] = "</tr>";
        	$output[] = "<tr class='tebravo_underTD'><td width=4%></td>";
        	$output[] = "<td width=36%><strong>".__("Old Tables",TEBRAVO_TRANS)."</strong></td>";
        	$output[] = "<td width=14%></td>";
        	$output[] = "<td width=46%><strong>".__("New Tables",TEBRAVO_TRANS)."</strong></td></tr>";
        	$output[] = $print_compars;
        	//Options Table
        	$output[] = "<tr class='tebravo_headTD'><td colspan=4><strong>".__("Options Table", TEBRAVO_TRANS)."</strong></td>";
        	$output[] = "</td>";
        	$output[] = "</tr>";
        	if(get_option($old_user_roles)){
        		$output[] = $user_roles_compars;
        	}
        	//Usermeta Table
        	$output[] = "<tr class='tebravo_headTD'><td colspan=4><strong>".__("Usermeta Table", TEBRAVO_TRANS)."</strong></td>";
        	$output[] = "</td>";
        	$output[] = "</tr>";
        	$output[] = "<tr class='tebravo_underTD'><td colspan=4><i>".__("Some data will be altered here.", TEBRAVO_TRANS)."</i></td>";
        	
        	
        	$output[] = "</table>";
        	
        	
        	$output[] = $this->html->button(__("Continue", TEBRAVO_TRANS), "submit");
        	$output[] = $this->html->button(__("Start Over", TEBRAVO_TRANS), "button", "back", false, "arrow_left.png");
        	
        	$output[] = "</div>";
        	$output[] = "</form>";
        	
        	echo implode("\n", $output);
        	echo '<script>';
        	echo 'jQuery("#back").click(function(){';
        	echo 'window.location.href= "'.$this->html->init->admin_url.'-wconfig&p=wizard'.'"';
        	echo '});';
        	echo '</script>';
        	$this->html->footer();
        }
        
        //chaning dbprefix wizard and confirmation
        public function change_prefix_wizard()
        {
        	global $table_prefix;
        	
        			//call backup database class
        			$db = new tebravo_db();
        			
        			$desc = __("When you click 'Start' button, this wizard will automatically backup wordpress database with old `dbprefix`.", TEBRAVO_TRANS);
        			$desc .= "<br />";
        			$desc .= __("So, please make sure that the backup directory is <i>writable</i>.", TEBRAVO_TRANS);
        			$desc .= "<br /><hr><pre>";
        			$desc .= "<b><div style='font-size:0.9em;color:#BA3E21'>".__("DB Backup Directory: ", TEBRAVO_TRANS)."</b><i>".$this->html->init->backupdir."/manually/</i>";
        			$desc .= "<br />";
        			$desc .= "<b>".__("DB Backup File Name: ", TEBRAVO_TRANS)."</b><i>".$db->backupname."</i></div></pre>";
        			$this->html->header("Changing WP Prefix Wizard", $desc, "wizard.png");
        			
        			//Start Content
        			$output[] = "<form action='".$this->html->init->admin_url."-wconfig&p=wizard_confirmation' method=post>";
        			$output[] = "<input type='hidden' name='_nonce' value='".$this->html->init->create_nonce('change-db-prefix')."'>";
        			$output[] = "<input type='hidden' name='exists_dbprefix' value='".$table_prefix."'>";
        			$output[] = "<input type='hidden' name='backupname' value='".$db->backupname."'>";
        			$output[] = "<input type='hidden' name='exists_development_mode' value='".trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'development_mode')))."'>";
        			$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
        			$output[] = "<table border='0' width=100% cellspacing=0>";
        			
        			//check if backdir is writable
        			if(is_writable($this->html->init->backupdir."/manually/"))
        			{
        				$if_is_writable = "<font color=green>".__("Writable", TEBRAVO_TRANS)."</font>";
        				$fixDB= "";
        			} else {
        				$if_is_writable = "<font color=brown>".__("Unwritable", TEBRAVO_TRANS)."</font>";
        				$fixDB= "<span id='fixDBdir' class='tebravoFixInline'> ".__("Try Fix", TEBRAVO_TRANS)."</span> <span id='fixDBdirSpan' class='tebravo_loading_span'>Loading ...</span>";
        			}
        			
        			//check if wp.config.php is writable
        			if(is_writable(ABSPATH."wp-config.php"))
        			{
        				$if_is_writable_config = "<font color=green>".__("Writable", TEBRAVO_TRANS)."</font>";
        				$fixCon= "";
        			} else {
        				$if_is_writable_config= "<font color=brown>".__("Unwritable", TEBRAVO_TRANS)."</font>";
        				$fixCon= "<span id='fixwpCo' class='tebravoFixInline'> ".__("Try Fix", TEBRAVO_TRANS)."</span> <span id='fixwpCoSpan' class='tebravo_loading_span'>Loading ...</span>";
        			}
        			
        			$output[] = "<tr class='tebravo_underTD'><td width='25%'><b>Database Name</b></td><td>".DB_NAME."</td></tr>";
        			$output[] = "<tr class='tebravo_underTD'><td width='25%'><b>Current DB Prefix</b></td><td>".$table_prefix."</td></tr>";
        			$output[] = "<tr class='tebravo_underTD'><td width='25%'><b><label for='wpprefix'>New DB Prefix</label></b></td><td>";
        			$output[] = "<select name='wpprefix_string'>";
        			$output[] = "<option value='wp_' selected>wp_</option>";
        			$output[] = "<option value='wp'>wp</option>";
        			$output[] = "<option value='bravo_'>bravo_</option>";
        			$output[] = "<option value='bravo'>bravo</option>";
        			$output[] = "<option value='none'>none</option></select>";
        			$output[] = "<input type='text' pattern='[A-Za-z0-9]{3,7}' name='wp_prefix' id='wpprefix' value='".$this->wp_prefix."'> <span id='tebravoCreatePrefix'> ".__("Get New One", TEBRAVO_TRANS)."</span> <span class='tebravo_loading_span' id='tebravo_loading_span'>Loading ...</span>";
        			$output[] = "<br /><font class='smallfont'>".__("from 3 to 7 letters , numbers or alphanumeric (no special characters)",TEBRAVO_TRANS)."</font>";
        			$output[] = "</td></tr>";
        			$output[] = "<tr class='tebravo_underTD'><td width='25%'><b>".__("Database Backup Directory", TEBRAVO_TRANS)."</b></td><td><span id='dbdir_status'>".$if_is_writable."</span> $fixDB</td></tr>";
        			$output[] = "<tr class='tebravo_underTD'><td width='25%'><b>".__("wp-config.php", TEBRAVO_TRANS)."</b></td><td><span id='wpcon_status'>".$if_is_writable_config."</span> $fixCon</td></tr>";
        			$output[] = "<tr class='tebravo_underTD'><td width='25%'></td><td>";
					$output[] = "<input type='checkbox' name='development_mode' value='checked' checked id='devmode'><label for='devmode'>".__("Enable development mode until the process complete",TEBRAVO_TRANS)."</label>";
					$output[] = "</td></tr>";
        			
        			
        			$output[] = "<tr><td width='25%'></td><td>".$this->html->button(__("Start", TEBRAVO_TRANS), "submit");
        			$output[] = $this->html->button(__("Back", TEBRAVO_TRANS), "button", "back", false, "arrow_left.png");
        			$output[] = "</td></tr>";
        			
        			$output[] = "</table>";
        			
        			$output[] = "</div></form>";
        			$output[] = "<span id=tebravo_results></span>";
        			
        			echo implode("\n", $output);
        			
        			#chmod(ABSPATH.'wp-config.php', 0400);
        			#$this->backup_wpconfig();
        			#var_dump($this->replace_string_config('wp_', 'wp_adham'));
        			
        			$ajax_url = $this->html->init->create_nonce_ajax_url('tebravo_get_new_inline_prefix', ('tebravo_get_new_inline_prefix'));
        			$ajax_urlDB= $this->html->init->create_nonce_ajax_url('tebravo_fix_db_dir_perms', ('tebravo_fix_db_dir_perms'));
        			$ajax_urlCon= $this->html->init->create_nonce_ajax_url('tebravo_fix_config_file_perms', ('tebravo_fix_config_file_perms'));
        			
        			echo '<script>';
        			echo 'jQuery("#back").click(function(){';
        			echo 'window.location.href= "'.$this->html->init->admin_url."-wconfig".'"';
        			echo '});';
        			//Change Prefix Ajax
        			echo 'jQuery("#tebravoCreatePrefix").click(function( $ ){';
        			echo 'jQuery("#tebravo_loading_span").show();';
        			echo 'jQuery("#tebravo_results").load("'.$ajax_url.'");';
        			echo '});';
        			//Change back directory permissions Ajax
        			echo 'jQuery("#fixDBdir").click(function( $ ){';
        			echo 'jQuery("#fixDBdirSpan").show();';
        			echo 'jQuery("#tebravo_results").load("'.$ajax_urlDB.'");';
        			echo '});';
        			//Change wp-config permissions Ajax
        			echo 'jQuery("#fixwpCo").click(function( $ ){';
        			echo 'jQuery("#fixwpCoSpan").show();';
        			echo 'jQuery("#tebravo_results").load("'.$ajax_urlCon.'");';
        			echo '});';
        			echo '</script>';
        			
        			#error_reporting(E_ALL);
        			
        			$this->html->footer();
        		
        }
        
        //backup wpconfig
        protected function backup_wpconfig( $redirect=true )
        {
        	$helper = new tebravo_html();
        	//options
        	$backup_dir = $this->html->init->backupdir."/tmp/";
        	$config_file = ABSPATH."wp-config.php";
        	
        	//backup path(s)
        	$new_name_backuptmp = $backup_dir."wp-config.php.".date('dmY_h-i-s')."_backup.php";
        	$new_name_backup_abspath = ABSPATH."wp-config.php.".date('dmY_h-i-s')."_backup.php";
        	if( file_exists( $config_file ) )
        	{
        		//Store backup to ABSPATH
        		if( !@copy( $config_file , $new_name_backup_abspath ) ){
        			$data = file( $config_file );
        			$this->copy_file( $data , $new_name_backuptmp );
        		}
        		
        		//Store backup to BRAVO backups /tmp
        		if( !@copy( $config_file , $new_name_backuptmp) ){
        			$data = file( $config_file );
        			$this->copy_file( $data , $new_name_backuptmp );
        		}
        		
        		//check if wp-config backup done or exit process
        		if( !file_exists( $new_name_backuptmp) ||
        				!file_exists( $new_name_backup_abspath))
        		{
        			if( $redirect )
        			{
        				tebravo_redirect_js($this->html->init->admin_url.'&wpconfig&p=wizard&err=09');
        			} else {
        				$msg = __("BRAVO can not backup the wp-config.php, Please do it manually!", TEBRAVO_TRANS);
        				echo "<script>
							alert('".$msg."');
							</script>";
        			}
        			exit;
        		}
        		
        	} else {
        		if( $redirect )
        		{
        			tebravo_redirect_js($this->html->init->admin_url.'&wpconfig&p=wizard&err=08');
        		} else {
        			$msg = __("wp-config.php does not exists in path <i>".ABSPATH."wp-config.php", TEBRAVO_TRANS);
        			echo "<script>
							alert('".$msg."');
							</script>";
        		}
        		exit;
        	}
        }
        
        //copy data to new file
        //data is array()
        public function copy_file($data, $full_path)
        {
        	$fp = @fopen($full_path, 'w');
        	foreach ($data as $lines => $line)
        	{
        		@fwrite($fp, $line);
        	}
        	@fclose($fp);
        }
        
        //write new config file
        protected function replace_string_config( $oldString, $newString, $search_for)
        {
        	// config path
        	$config_file = ABSPATH."wp-config.php";
        	
        	$config_data = file($config_file);
        	
        	//searching for
        	$search_string = $search_for;
        	
        	foreach ($config_data as $line_number => $this_line)
        	{
        		// now, escape white space for great search
        		$str = preg_replace('/\s+/', '', $this_line); 
        		if(strpos($str, $search_string) !== false)
        		{
        			$config_data[$line_number] = str_replace($oldString, $newString, $this_line);
        		}
        	}
        	$config_data[] = $this->signature;
        	return $config_data;
        }
       
    }
}
?>