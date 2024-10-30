<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

//Remove WP version from generator
if( !function_exists( 'tebravo_hide_wp_version' ) )
{
    function tebravo_hide_wp_version()
    {
        if(tebravo_utility::get_option( TEBRAVO_DBPREFIX.'hidewpversion') == 'checked')
        {
            remove_action('wp_head', 'wp_generator');
        }
    }
    //run
    tebravo_hide_wp_version();
}

if( !function_exists( 'tebravo_backup_htaccess_file' ) )
{
	add_action('wp_ajax_tebravo_backup_htaccess_file', 'tebravo_backup_htaccess_file');
	function tebravo_backup_htaccess_file()
	{
		if(empty($_GET['_wpnonce']) || false == wp_verify_nonce($_GET['_wpnonce'], tebravo_utility::get_option(TEBRAVO_DBPREFIX.'security_hash').'tebravo_backup_htaccess_file'))
		{
			tebravo_redirect_js(admin_url("admin.php?page=". TEBRAVO_SLUG ."-settings&err=03"));
			exit;
		}
		
		$file = ABSPATH.".htaccess";
		$filename = ".htaccess_".tebravo_utility::get_option(TEBRAVO_DBPREFIX.'security_hash')."_".@date('dMY');
		if( tebravo_phpsettings::web_server() == 'nginx' )
		{
			$file = ABSPATH."nginx.conf";
			$filename = "nginx.conf_".tebravo_utility::get_option(TEBRAVO_DBPREFIX.'security_hash')."_".@date('dMY');
		}
		if(!file_exists($file)){$file = "../".$file;}
		
		$backup_dir = TEBRAVO_DIR."/".TEBRAVO_BACKUPFOLDER."/htaccess/";
		
		$newFile = $backup_dir.basename($filename);
		
		if(tebravo_store_backup_file($file, $filename, $backup_dir))
		{
			$output = "<font color='green'>".__("Done. You will be able to download it via FTP only because the directory locked.",TEBRAVO_TRANS)."</font>";
		} else {
			$output = "<font color='brown'>".__("Failed! Please make sure that the directory exists and writable.")."</font>";
		}
		
		?>
		<script>
		jQuery("#tebravo_results").html("<?php echo $output;?>");
		setTimeout(function()
				{
					jQuery("#tebravo_results").hide();
				}
				,5000)
		</script>
		<?php
		exit;
	}
}

if( !function_exists( 'tebravo_store_backup_file' ) )
{
	function tebravo_store_backup_file($file, $newFileName, $backup_dir, $rule="", $custom_data="")
	{
		//check if directory writable
		if( !is_writable( $backup_dir ) )
		{
			$message = __("Backup directory is not writable!", TEBRAVO_TRANS);
			$message .= "<br /><i>".$backup_dir."</i>";
			tebravo_die(true, $message, false, false);
		}
		//echo $file; exit;
		if(file_exists($file) && is_writable($backup_dir))
		{
			@ob_start();
			
			if(!$rule){$rule="w+";}
			
			$newFile = $backup_dir."/".$newFileName;
			$fp = fopen($newFile, $rule);
			$data = file_get_contents($file);
			$size = filesize($file);
			
			if($custom_data != '')
			{
				$data .= $custom_data;
				$size += strlen($custom_data);
			}
			
			return fputs($fp,$data,$size);
			@ob_end_clean();
		}
	}
}

if( !function_exists( 'tebravo_store_free_data' ) )
{
	function tebravo_store_free_data($file, $dir, $data, $rule="")
	{
		if(file_exists($file) && is_writable($file))
		{
			@ob_start();
			
			if(!$rule){$rule="w+";}
			
			$newFileName = basename($file);
			$newFile = $dir."/".$newFileName;
			$fp = @fopen($newFile, $rule);
							
			$size = strlen($data);
			#echo $data; exit;
			@fputs($fp,$data,$size);
			
			@ob_end_clean();
		} else {
			tebravo_redirect_js(admin_url('admin.php?page=bravo-security&page=settings&err=04'));
		}
	}
}

if( !function_exists( 'tebravo_create_download_file' ) )
{
	function tebravo_create_download_file( $file ){
		$data = "<?php \n";
		$data .= "/**\n";
		$data .= "*".$file.".php created automatically by BRAVO WP Ultimate Security Plugin\n";
		$data .= "*This file is unique file to download some needed data outside wordpress.\n";
		$data .= "*/\n";
		$data .= "\n";
		$data .= "@ini_set('display_errors', 1);";
		$data .= "\n";
		$data .= "\n";
		$data .= 'if(empty($_GET["f"]) || $_GET["s"] != "'.str_replace(".php", "", $file).'"){exit;}';
		$data .= "\n";
		$data .= "\n";
		$data .= '$file = trim(addslashes($_GET["f"]));';
		$data .= "\n";
		$data .= '$quoted = @basename($file);';
		$data .= "\n";
		$data .= '$size   = @filesize($file);';
		$data .= "\n";
		$data .= "\n";
		$data .= '@header(\'Content-Description: File Transfer\');';
		$data .= "\n";
		$data .= '@header(\'Content-Type: application/octet-stream\');';
		$data .= "\n";
		$data .= '@header(\'Content-Disposition: attachment; filename=\' . $quoted);';
		$data .= "\n";
		$data .= '@header(\'Content-Transfer-Encoding: binary\');';
		$data .= "\n";
		$data .= '@header(\'Connection: Keep-Alive\');';
		$data .= "\n";
		$data .= '@header(\'Expires: 0\');';
		$data .= "\n";
		$data .= '@header(\'Cache-Control: must-revalidate, post-check=0, pre-check=0\');';
		$data .= "\n";
		$data .= '@header(\'Pragma: public\');';
		$data .= "\n";
		$data .= '@header(\'Content-Length: \' . $size);';
		$data .= "\n";
		$data .= "\n";
		$data .= '@readfile($file);';
		$data .= "\n?>";
		
		$file = TEBRAVO_DIR."/".$file.".php";
		if(!file_exists($file))
		{
			$fp = @fopen($file, 'w');
			fputs($fp, $data);
		}
	}
}


//Close front-end
//Development Mode & Maintenance Mode
if( !function_exists( 'tebravo_close_website' ))
{
	add_action('wp_head', 'tebravo_close_website');
	function tebravo_close_website()
	{
		$dev_mode = esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'development_mode'));
		$mai_mode = esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'maintenance_mode'));
		
		if($dev_mode == "checked" || $mai_mode == "checked")
		{
			if($dev_mode == "checked" && $mai_mode != "checked")
			{
				$enable_admin = true;
			} else {$enable_admin = false;}
			
			if($enable_admin == true)
			{
				?>
				<style>
				.tebravo_noti{
					width:100%;
					height:35px;
					border-top:solid 2px #F3F3F3;
					background:#36B7DC;
					position:fixed;
					bottom:0px;
					left:0px;
					z-index:9999;
					font-size:12px;
					color:#fff;
					line-height:32px;
					text-align:center;
				}
				</style>
				<?php
				
				$user = wp_get_current_user();
				$dev_mode_user_rules_admin = trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'dev_mode_user_rules_admin')));
				if($dev_mode_user_rules_admin == 'checked')
				{
					$allowed_roles[] = "administrator";
				}
				$dev_mode_user_rules_author = trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'dev_mode_user_rules_author')));
				if($dev_mode_user_rules_author == 'checked')
				{
					$allowed_roles[] = "author";
				}
				$dev_mode_user_rules_editor = trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'dev_mode_user_rules_editor')));
				if($dev_mode_user_rules_editor == 'checked')
				{
					$allowed_roles[] = "editor";
				}
				
				#var_dump($user->roles);
				if( array_intersect($allowed_roles, $user->roles ))
				{
					$icon = "<img src='".plugins_url("assets/img/maintenance-small.png", TEBRAVO_PATH)."'>";
					echo '<div class="tebravo_noti">'.$icon.' '.__("Development Mode Enabled", TEBRAVO_TRANS).'</div>';
				} else {
				    include_once( TEBRAVO_DIR. '/close_site.php');
					exit;
				}
			} else {
			    include_once( TEBRAVO_DIR. '/close_site.php');
				exit;
			}
		}
	}
}

//Show notification bar to notify admin to check unused themes and plugins
if( !function_exists( 'tebravo_wp_notifications_footer' ))
{
	add_action('in_admin_footer', 'tebravo_wp_notifications_footer');
	
	function tebravo_wp_notifications_footer()
	{
		//check capability
		if(! current_user_can('manage_options')){wp_die(); exit;}
		
		#ob_start();
		#echo "<br />DATA ".date('d-m-Y h:i:s', '1494270882')." | ".date('d-m-Y h:i:s');
		$current_time = time();
		$html = new tebravo_html();
		//unused themes
		$unused_themes = trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'remember_delete_unused_themes')));
		$themes_next_notify = trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'themes_next_notify')));
		if( $unused_themes == 'checked'
		&& !empty($themes_next_notify))
		{
			if($html->init->inactive_themes_count() > 0){
				$interval_themes = trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'themes_next_notify')));
				
				if($current_time >= $interval_themes)
				{
					$html->popup_modal(true, __("Please check unused THEMES", TEBRAVO_TRANS)." [<font color=red>".$html->init->inactive_themes_count()."</font>]", 'unusedthemes', false);
					?>
				<script>tebravo_open_modal();</script>
				<?php 
					//
					//$html->print_noti("Please check unused <font color=yellow>THEMES [".$html->init->inactive_themes_count()."] </font>!");
					$newInterval_themes = time()+(6*60*60);
					tebravo_utility::update_option(TEBRAVO_DBPREFIX.'themes_next_notify', $newInterval_themes);
				}
			}
		}
		
		//unused plugins
		$unused_plugins = trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'remember_delete_unused_plugins')));
		$plugins_next_notify = trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'plugins_next_notify')));
		if($unused_plugins == 'checked'
						&& !empty($plugins_next_notify))
		{
			if($html->init->inactive_plugins_count() > 0){
				$interval_plugins = trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'plugins_next_notify')));
				
				if($current_time >= $interval_plugins)
				{
					
					$html->popup_modal(true, __("Please check unused PLUGINS", TEBRAVO_TRANS)." [<font color=red>".$html->init->inactive_plugins_count()."</font>]", 'unusedthemes', false);
					?>
				<script>tebravo_open_modal();</script>
				<?php 
					//$html->print_noti("Please check unused <font color=yellow>PLUGINS [<font color=red>".$html->init->inactive_plugins_count()."</font>]</font> !");
					$newInterval_plugins = time()+(3*60*60);
					tebravo_utility::update_option(TEBRAVO_DBPREFIX.'plugins_next_notify', $newInterval_plugins);
				}
			}
		}
		#ob_end_clean();
	}
}

//Ajax: get new prefix
if( !function_exists( 'tebravo_get_new_inline_prefix' ) )
{
	add_action('wp_ajax_tebravo_get_new_inline_prefix', 'tebravo_get_new_inline_prefix');
	function tebravo_get_new_inline_prefix()
	{
		//check capability
		if(! current_user_can('manage_options')){wp_die(__("Wrong Access", TEBRAVO_TRANS)); exit;}
		
		$html = new tebravo_html();
		
		if(empty($_GET['_wpnonce'])
				|| false == wp_verify_nonce($_GET['_wpnonce'], $html->init->security_hash.'tebravo_get_new_inline_prefix')){exit();}
		
				$newHash = $html->init->create_prefix( rand(4,7) );
		
		?>
		<script>
		jQuery("#tebravo_loading_span").hide();
		jQuery("#wpprefix").val('<?php echo $newHash;?>');
		</script>
		<?php
		exit();
	}
}

//Ajax: change db directory backup permissions
if( !function_exists( 'tebravo_fix_db_dir_perms' ) )
{
	add_action('wp_ajax_tebravo_fix_db_dir_perms', 'tebravo_fix_db_dir_perms');
	function tebravo_fix_db_dir_perms()
	{
		//check capability
		if(! current_user_can('manage_options')){wp_die(); exit;}
		
		$success = "<font color=green>".__("Writable", TEBRAVO_TRANS)."</font>";
		
		$html = new tebravo_html();
		
		if(empty($_GET['_wpnonce'])
				|| false == wp_verify_nonce($_GET['_wpnonce'], $html->init->security_hash.'tebravo_fix_db_dir_perms')){exit();}
				
				$phpSettings = new tebravo_phpsettings();
				$backupdir = $html->init->backupdir;
				if(! $phpSettings->is_disabled( 'chmod' ) )
				{
					if(! @chmod($backupdir.'/', 0777))
					{
						$error = 1;
					} else {
						@setcookie("dirperms_changed",'manually', time()+3600);
						@chmod($backupdir.'/manually/', 0777);
						$error = 0;
					}
				} else {
					$error = 1;
				}
				
				if($error == '1')
				{
					$msg = "<font color=red>".__("Failed!", TEBRAVO_TRANS)."</font>";
				} else {
					$msg = "<font color=green>".__("Success!", TEBRAVO_TRANS)."</font>";
					?>
					<script>
					jQuery("#dbdir_status").html('<?php echo $success;?>');
					</script>
					<?php
				}
				?>
				
		<script>
		
		jQuery("#fixDBdirSpan").html('<?php echo $msg;?>');
		setTimeout(function()
				{
					jQuery("#fixDBdirSpan").hide();
					jQuery("#fixDBdir").hide();
				}
				,500);
		</script>
		<?php
		exit();
	}
}

//Ajax: change wp-config permissions
if( !function_exists( 'tebravo_fix_config_file_perms' ) )
{
	add_action('wp_ajax_tebravo_fix_config_file_perms', 'tebravo_fix_config_file_perms');
	function tebravo_fix_config_file_perms()
	{
		//check capability
		if(! current_user_can('manage_options')){wp_die(); exit;}
		
		$success = "<font color=green>".__("Writable", TEBRAVO_TRANS)."</font>";
		
		$html = new tebravo_html();
		
		if(empty($_GET['_wpnonce'])
				|| false == wp_verify_nonce($_GET['_wpnonce'], $html->init->security_hash.'tebravo_fix_config_file_perms')){exit();}
				
				$phpSettings = new tebravo_phpsettings();
				$configpath = ABSPATH.'wp-config.php';
				if(! $phpSettings->is_disabled( 'chmod' ) )
				{
					if(! @chmod($configpath, 0666))
					{
						$error = 1;
					} else {
						@setcookie("fileperms_changed","yes", time()+3600);
						$error = 0;
					}
				} else {
					$error = 1;
				}
				
				if($error == '1')
				{
					$msg = "<font color=red>".__("Failed!", TEBRAVO_TRANS)."</font>";
				} else {
					$msg = "<font color=green>".__("Success!", TEBRAVO_TRANS)."</font>";
					?>
					<script>
					jQuery("#wpcon_status").html('<?php echo $success;?>');
					</script>
					<?php
				}
				?>
				
		<script>
		
		jQuery("#fixwpCoSpan").html('<?php echo $msg;?>');
		setTimeout(function()
				{
					jQuery("#fixwpCoSpan").hide();
					jQuery("#fixwpCo").hide();
				}
				,500);
		</script>
		<?php
		exit();
	}
}

//Change salts in Ajax
if( !function_exists( 'tebravo_auth_keys_update' ) )
{
	add_action('wp_ajax_tebravo_auth_keys_update', 'tebravo_auth_keys_update' );
	
	function tebravo_auth_keys_update()
	{
		//check capability
		if(! current_user_can('manage_options')){wp_die(); exit;}
		
		$html = new tebravo_html();
		
		if(empty($_GET['_wpnonce'])
				|| false == wp_verify_nonce($_GET['_wpnonce'], $html->init->security_hash.'tebravo_auth_keys_update')){exit();}
				
		$wpcon = new tebravo_wconfig();
		$results = $wpcon->replace_salts();
		
		if($results)
		{
			$message = "<font color=green>".__("Salts updated successfully.", TEBRAVO_TRANS)."</font>";
		} else {
			$message = "<font color=red>".__("Salts update failed!", TEBRAVO_TRANS)."</font>";
		}
		
		$message .= "<hr><pre>";
		$message .= "<b>".__("wp-config.php backup at these directories:", TEBRAVO_TRANS)."</b><br />";
		$message .= $html->init->backupdir.'/tmp/ <br>and<br> '.ABSPATH;
		
		tebravo_utility::update_option(TEBRAVO_DBPREFIX.'auth_keys_last_update', time());
		
		//permissions
		$wp_config_notify_perms = trim(esc_html(tebravo_utility::get_option( TEBRAVO_DBPREFIX.'wp_config_notify_perms' )));
		if( $wp_config_notify_perms == 'checked')
		{
			tebravo_files::dochmod(ABSPATH.'wp-config.php', 0400);
		}
		?>
		<script>
		jQuery("#auth_keys_ajax").html("<?php echo $message?>");
		</script>
		<?php 
		exit;
	}
}

//Change file editor status in wp-config
if( !function_exists( 'tebravo_editor_change' ) )
{
	add_action('wp_ajax_tebravo_editor_change', 'tebravo_editor_change' );
	
	function tebravo_editor_change()
	{
		//check capability
		if(! current_user_can('manage_options')){wp_die(); exit;}
		
		$html = new tebravo_html();
		
		if(empty($_GET['_wpnonce'])
				|| false == wp_verify_nonce($_GET['_wpnonce'], $html->init->security_hash.'tebravo_editor_change')){exit();}
				
				$wpcon = new tebravo_wconfig();
				$results = $wpcon->file_editor();
				
				
				$message = "<font color=green>".__("File editor updated successfully. Refresh page to view changes.", TEBRAVO_TRANS)."</font>";
				
				
				$message .= "<hr><pre>";
				$message .= "<b>".__("wp-config.php backup at these directories:", TEBRAVO_TRANS)."</b><br />";
				$message .= $html->init->backupdir.'/tmp/ <br>and<br> '.ABSPATH;
				
				//permissions
				$wp_config_notify_perms = trim(esc_html(tebravo_utility::get_option( TEBRAVO_DBPREFIX.'wp_config_notify_perms' )));
				if( $wp_config_notify_perms == 'checked')
				{
					tebravo_files::dochmod(ABSPATH.'wp-config.php', 0400);
				}
				#tebravo_utility::update_option(TEBRAVO_DBPREFIX.'auth_keys_last_update', time());
				?>
		<script>
		jQuery("#editor_ajax").html("<?php echo $message?>");
		</script>
		<?php 
		exit;
	}
}

//Enable/Disable error debug from config /AJAX
if( !function_exists( 'tebravo_errors_debug_enable_disable' ) )
{
	add_action('wp_ajax_tebravo_errors_debug_enable_disable', 'tebravo_errors_debug_enable_disable' );
	
	function tebravo_errors_debug_enable_disable()
	{
		//check capability
		if(! current_user_can('manage_options')){wp_die(); exit;}
		
		$html = new tebravo_html();
		
		if(empty($_GET['_wpnonce'])
				|| false == wp_verify_nonce($_GET['_wpnonce'], $html->init->security_hash.'tebravo_errors_debug_enable_disable')){exit();}
				
				$wpcon = new tebravo_wconfig();
				$results = $wpcon->error_debug_config();
				
				
				$message = "<font color=green>".__("wp-config.php updated successfully. WP_DEBUG updated.", TEBRAVO_TRANS)."</font>";
				
				
				$message .= "<hr><pre>";
				$message .= "<b>".__("wp-config.php backup at these directories:", TEBRAVO_TRANS)."</b><br />";
				$message .= $html->init->backupdir.'/tmp/ <br>and<br> '.ABSPATH;
				
				//permissions
				$wp_config_notify_perms = trim(esc_html(tebravo_utility::get_option( TEBRAVO_DBPREFIX.'wp_config_notify_perms' )));
				if( $wp_config_notify_perms == 'checked')
				{
					tebravo_files::dochmod(ABSPATH.'wp-config.php', 0400);
				}
				#tebravo_utility::update_option(TEBRAVO_DBPREFIX.'auth_keys_last_update', time());
				?>
		<script>
		jQuery("#errors_debug_ajax").html("<?php echo $message?>");
		</script>
		<?php 
		exit;
	}
}

//Enable/Disable wordpress auto core updates
if( !function_exists( 'tebravo_wordpress_auto_updates' ) )
{
	add_action('wp_ajax_tebravo_wordpress_auto_updates', 'tebravo_wordpress_auto_updates' );
	
	function tebravo_wordpress_auto_updates()
	{
		//check capability
		if(! current_user_can('manage_options')){wp_die(); exit;}
		
		$html = new tebravo_html();
		
		if(empty($_GET['_wpnonce'])
				|| false == wp_verify_nonce($_GET['_wpnonce'], $html->init->security_hash.'tebravo_wordpress_auto_updates')){exit();}
				
				$wpcon = new tebravo_wconfig();
				$results = $wpcon->wp_core_updates();
				
				
				$message = "<font color=green>".__("wp-config.php updated successfully. WP_AUTO_UPDATE_CORE updated.", TEBRAVO_TRANS)."</font>";
				
				
				$message .= "<hr><pre>";
				$message .= "<b>".__("wp-config.php backup at these directories:", TEBRAVO_TRANS)."</b><br />";
				$message .= $html->init->backupdir.'/tmp/ <br>and<br> '.ABSPATH;
				
				//permissions
				$wp_config_notify_perms = trim(esc_html(tebravo_utility::get_option( TEBRAVO_DBPREFIX.'wp_config_notify_perms' )));
				if( $wp_config_notify_perms == 'checked')
				{
					tebravo_files::dochmod(ABSPATH.'wp-config.php', 0400);
				}
				#tebravo_utility::update_option(TEBRAVO_DBPREFIX.'auth_keys_last_update', time());
				?>
		<script>
		jQuery("#wordpress_ajax").html("<?php echo $message?>");
		</script>
		<?php 
		exit;
	}
}

//Fix wp-config permission by user control
if( !function_exists( 'tebravo_wpconfig_change_permissions' ) )
{
	add_action('wp_ajax_tebravo_wpconfig_change_permissions', 'tebravo_wpconfig_change_permissions' );
	
	function tebravo_wpconfig_change_permissions()
	{
		//check capability
		if(! current_user_can('manage_options')){wp_die(); exit;}
		
		$html = new tebravo_html();
		
		if(empty($_GET['_wpnonce'])
				|| false == wp_verify_nonce($_GET['_wpnonce'], $html->init->security_hash.'tebravo_wpconfig_change_permissions')){exit();}
				
				$config_file = ABSPATH .'wp-config.php';
				$perma_array = array('0400', '0444');
				$current_perma = tebravo_files::file_perms( $config_file );
				if( !in_array( $current_perma, $perma_array))
				{
					$results = tebravo_files::dochmod($config_file, 0400);
				} else {
					$results = tebravo_files::dochmod($config_file, 0644);
				}
				
				if($results== true){
					$message = "<font color=green>".__("wp-config.php permissions fixed successfully.", TEBRAVO_TRANS)."</font>";
				} else {
					$message = "<font color=red>".__("wp-config.php permissions fixed failed.", TEBRAVO_TRANS)."</font>";
				}
				
				
				#tebravo_utility::update_option(TEBRAVO_DBPREFIX.'auth_keys_last_update', time());
				?>
		<script>
		jQuery("#wpconfig_ajax").html("<?php echo $message?>");
		</script>
		<?php 
		exit;
	}
}

//Enable/Disable auto updates for themes
if( !function_exists( 'tebravo_themes_auto_updates' ) )
{
	add_action('wp_ajax_tebravo_themes_auto_updates', 'tebravo_themes_auto_updates' );
	
	function tebravo_themes_auto_updates()
	{
		//check capability
		if(! current_user_can('manage_options')){wp_die(); exit;}
		
		$html = new tebravo_html();
		
		if(empty($_GET['_wpnonce'])
				|| false == wp_verify_nonce($_GET['_wpnonce'], $html->init->security_hash.'tebravo_themes_auto_updates')){exit();}
				
				$auto_updates_themes = trim(esc_html(tebravo_utility::get_option( TEBRAVO_DBPREFIX.'auto_updates_themes' )));
				if( $auto_updates_themes == 'checked' )
				{
					$new_value = 'no';
				} else {
					$new_value = 'checked';
				}
				tebravo_utility::update_option(TEBRAVO_DBPREFIX.'auto_updates_themes', $new_value);
				
				$message = "<font color=green>".__("Settings updated successfully for themes automatic updates.", TEBRAVO_TRANS)."</font>";
				
				?>
		<script>
		jQuery("#themes_ajax").html("<?php echo $message?>");
		</script>
		<?php 
		exit;
	}
}

//Enable/Disable auto updates for plugins
if( !function_exists( 'tebravo_plugins_auto_updates' ) )
{
	add_action('wp_ajax_tebravo_plugins_auto_updates', 'tebravo_plugins_auto_updates' );
	
	function tebravo_plugins_auto_updates()
	{
		//check capability
		if(! current_user_can('manage_options')){wp_die(); exit;}
		
		$html = new tebravo_html();
		
		if(empty($_GET['_wpnonce'])
				|| false == wp_verify_nonce($_GET['_wpnonce'], $html->init->security_hash.'tebravo_plugins_auto_updates')){exit();}
				
				$auto_updates_plugins = trim(esc_html(tebravo_utility::get_option( TEBRAVO_DBPREFIX.'auto_updates_plugins' )));
				if($auto_updates_plugins == 'checked' )
				{
					$new_value = 'no';
					
				} else {
					$new_value = 'checked';
				}
				tebravo_utility::update_option(TEBRAVO_DBPREFIX.'auto_updates_plugins', $new_value);
				
				$message = "<font color=green>".__("Settings updated successfully for themes automatic updates.", TEBRAVO_TRANS)."</font>";
				
				?>
		<script>
		jQuery("#plugins_ajax").html("<?php echo $message?>");

		</script>
		<?php 
		exit;
	}
}

//Enable/Disable always read-only config
if( !function_exists( 'tebravo_notify_config_perms' ) )
{
	add_action('wp_ajax_tebravo_notify_config_perms', 'tebravo_notify_config_perms' );
	
	function tebravo_notify_config_perms()
	{
		$html = new tebravo_html();
		
		//check capability
		if(! current_user_can('manage_options')){wp_die(); exit;}
		
		if(empty($_GET['_wpnonce'])
				|| false == wp_verify_nonce($_GET['_wpnonce'], $html->init->security_hash.'tebravo_notify_config_perms')){exit();}
				
				$wp_config_notify_perms = trim(esc_html(tebravo_utility::get_option( TEBRAVO_DBPREFIX.'wp_config_notify_perms' )));
				if($wp_config_notify_perms == 'checked' )
				{
					$new_value = 'no';
					$message = 'Not Wise Decision.';
					
				} else {
					$new_value = 'checked';
					$message = 'Great Decision.';
				}
				tebravo_utility::update_option(TEBRAVO_DBPREFIX.'wp_config_notify_perms', $new_value);
				
				$message = "<font color=blue>".__($message, TEBRAVO_TRANS)."</font>";
				
				?>
		<script>
		jQuery("#wpconfig_ajax").show(0);
		jQuery("#wpconfig_ajax").html("<?php echo $message?>");
		setTimeout(function()
				{
					jQuery("#wpconfig_ajax").hide(300);
				}
				,1500)
		</script>
		<?php 
		exit;
	}
}

if( !function_exists( 'tebravo_get_blog_users' ) )
{
	/**
	 * Request wordpress users by user role to get user details
	 * @param $role
	 * @param $request
	 * @return string
	 */
	function tebravo_get_blog_users($role, $request)
	{
		//Administrator
		$blogusers = get_users('role='.$role);
		#var_dump($blogusers);
		//print_r($blogusers);
		if(! empty($blogusers) && is_array($blogusers)){
			foreach ($blogusers as $user) {
				$output[] = $user->$request;
			} 
			return $output;
		}
	}
}

//logout the idle user in front-end
if(! function_exists( 'tebravo_idle_action_frontend' ))
{
	add_action( 'wp_ajax_tebravo_idle_action_frontend' , 'tebravo_idle_action_frontend');
	function tebravo_idle_action_frontend()
	{
		global $msg;
		$redirect_to = wp_get_referer();
		
		#sleep(10);
		setcookie('tebravo_idle_detected', time()+360, time()+360);
		wp_logout();
		
		sleep(10);
		do_action('tebravo_idle_user_logout');
		echo $redirect_to;
		exit;
	}
}

//the logout redirect URL
if(!function_exists( 'tebravo_action_logout_redirect' ) )
{
	function tebravo_action_logout_redirect()
	{
		$redirect_to = tebravo_selfURL();
		#$msg = "Logging out ...";
		#tebravo_darken_bg( $msg );
		tebravo_redirect_js( $redirect_to );
		exit;
	}
}

//pring loading 
if( !function_exists( 'tebravo_darken_bg' ) ){
	function tebravo_darken_bg( $msg , $seconds=false, $timer=false)
	{
		if(!$seconds){$seconds = 10;}
		if($timer):
		?>
		<script>
		var timeleft = <?php echo $seconds?>;
		var downloadTimer = setInterval(function(){
		  document.getElementById("progressBar").value = <?php echo $seconds?> - --timeleft;
		  if(timeleft <= 0)
		    clearInterval(downloadTimer);
		},1000);
		</script>
		<?php endif;?>
		<div id="tebravo_results"></div>
		<div class='tebravo_darkenBG'><div id='tebravo_loader' class='loader'><br /><?php if($timer):?><progress value="0" max="<?php echo $seconds?>" id="tebravo_progressBar"><?php endif;?></progress><br /><?php echo $msg;?></div></div>
		<?php 
	}
}

//logout the idle user in wp-admin
if(! function_exists( 'tebravo_idle_logout_in_admin' ))
{
	add_action( 'wp_ajax_tebravo_idle_logout_in_admin' , 'tebravo_idle_logout_in_admin');
	function tebravo_idle_logout_in_admin()
	{
		if(is_admin() && is_user_logged_in())
		{
			setcookie('tebravo_idle_detected', time()+360, time()+360);
			do_action('tebravo_idle_admin_before_logout');
		}
		tebravo_clear_wp_cookie();
		do_action('tebravo_idle_admin_after_logout');
		exit;
	}
}

//send auth code to user email address
if(! function_exists( 'tebravo_send_new_auth_code' ) )
{
	add_action( 'wp_ajax_tebravo_send_new_auth_code' , 'tebravo_send_new_auth_code');
	function tebravo_send_new_auth_code( $user )
	{
		if( !$user )
		{
			$user = wp_get_current_user();
		}
		
		if($user->ID > 0)
		{
			if(!empty($_GET['_wpnonce'])
					&& false !== wp_verify_nonce($_GET['_wpnonce'], 'tebravo_send_new_auth_code_nonce')){
						
						if(!empty($_COOKIE['tebravo_auth_code_status']))
						{
							echo "<p><font color='brown'>";
							echo __("You can not resend another in short time, please try again later.", TEBRAVO_TRANS);
							echo "</font></p>";
							exit;
						}
						$email = $user->user_email;
						$username = $user->user_login;
						$sitename = get_bloginfo( 'name' );
						
						$email_template = TEBRAVO_DIR.'/includes/email_templates/auth_code.html';
						$data = tebravo_files::read( $email_template );
						
						$two_fa_secret= trim(esc_html(get_user_meta($user->ID, TEBRAVO_DBPREFIX.'2fa_secret_key', true)));
						
						$twofa = new tebravo_2fa();
						$code = $twofa->getCode($two_fa_secret);
						
						$data = str_replace('{%username%}', $username, $data);
						$data = str_replace('{%sitename%}', $sitename, $data);
						$data = str_replace('{%code%}', $code, $data);
						
						update_user_meta($user->ID, TEBRAVO_DBPREFIX.'2fa_fresh_email_code', $code);
						
						$subject = __("Login Security: Authentication Code", TEBRAVO_TRANS);
						$message = $data;
						
						add_filter( 'wp_mail_content_type' , 'tebravo_content_type');
							
							wp_mail($email, $subject, $message);
							setcookie('tebravo_auth_code_status', 'sent', time()+60);
							
							echo "<p><font color='green'>";
							echo __("Sent! Please check your email.", TEBRAVO_TRANS);
							echo "</font></p>";
			} else {
				echo "Wrong Nonce";
			}
		} else {
			echo "Wrong User";
		}
		exit;
	}
}

//security questions list
if( !function_exists( 'tebravo_questions_list' ) )
{
	function tebravo_questions_list()
	{
		$q = array(
				"01" => __("What is the first name of the person you first kissed?", TEBRAVO_TRANS),
				"02" => __("What is the last name of the teacher who gave you your first failing grade?", TEBRAVO_TRANS),
				"03" => __("What was the name of your elementary / primary school?", TEBRAVO_TRANS),
				"04" => __("In what city or town does your nearest sibling live?", TEBRAVO_TRANS),
				"05" => __("What time of the day were you born?", TEBRAVO_TRANS),
				"06" => __("What is your pet's name?", TEBRAVO_TRANS),
				"07" => __("In what year was your father born?", TEBRAVO_TRANS),
				"08" => __("What is your favorite car?", TEBRAVO_TRANS),
		);
		
		return $q;
	}
}

if( !function_exists( 'tebravo_mail' ) )
{
	function tebravo_mail( $to=false, $subject, $message=array(), $template=false, $attachment=false, $cc=false)
	{
		$standard_file = TEBRAVO_DIR.'/includes/email_templates/standard.html';
		if( !$template )
		{
			$file = $standard_file;
		} else {
			$file = TEBRAVO_DIR.'/includes/email_templates/'.$template.'.html';
		}
		
		if( !file_exists($file) ) { $file = $standard_file;}
		
			$data = tebravo_files::read( $file );
			//tebravo_die(true, 'aaa');
			$user = get_user_by('email', $to);
			$username = $to;
			if( !empty($user->display_name) && isset($user->display_name))
			{
				$username = $user->display_name;
			} 
			
			$blog_name= tebravo_utility::get_bloginfo('name');
			$data = str_replace('{%username%}', $username, $data);
			
			$data = str_replace('{%sitename%}', $blog_name, $data);
			$data = str_replace('{%message%}', $message, $data);
			
			$message = $data;
			
			add_filter( 'wp_mail_content_type', 'tebravo_content_type');
			
			$attachments = '';
			if( $attachment && is_array($attachment) ){
				$attachments = $attachment;
			}
			
			//get email to send
			$admin_email = tebravo_utility::get_option( 'admin_email' );
			
			if( !$to )
			{
				//admin email
				$to = trim( esc_html( $admin_email ) );
				
				//tebravo email
				$tebravo_email = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'email' ) ) );
				if( !empty( $tebravo_email ) ){
					$to = $tebravo_email;
				}
			}
			
			if( !filter_var($to, FILTER_VALIDATE_EMAIL ) )
			{
				$to = $admin_email;
			}
			
			//set CC
			if( !$cc )
			{
				//tebravo email
				$tebravo_cc = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'cc' ) ) );
				if( !empty( $tebravo_cc ) ){
					$cc = $tebravo_cc;
				}
			}
			
			$headers[] = '';
			if( $cc && filter_var($cc, FILTER_VALIDATE_EMAIL ))
			{
				$headers[] = 'Cc: '.$cc;
			}
			
			$headers = '';
			//$tebravo_mail = new tebravo_mail();
			#add_action
			
			wp_mail($to, $subject, $message, $headers, $attachments);
		
	}
}

if( !function_exists( 'tebravo_content_type' ) )
{
	function tebravo_content_type()
	{
		return "text/html";
	}
}

if( !function_exists( 'tebravo_shorten_url') )
{
	function tebravo_shorten_url($url)
	{
		$url = str_replace("//", "/", $url);
		$length = strlen( $url );
		if( $length > 40 )
		{
			$url_part1 = substr($url, 0, 35);
			$url_part2 = substr($url, -5);
			
			return $url_part1."...".$url_part2;
		}
		
		return $url;
	}
}

if( !function_exists( 'tebravo_contact_support' ) )
{
	add_action('wp_ajax_tebravo_contact_support', 'tebravo_contact_support');
	function tebravo_contact_support()
	{
		if( empty( $_GET['_nonce'] )
				|| false === wp_verify_nonce( $_GET['_nonce'], 'contact-support')){exit;}
		
		$domain = tebravo_getDomainUrl(tebravo_selfURL());
		$admin_email = tebravo_utility::get_option('admin_email');
		
		$title = 'BRAVO -Client Support Request';
		$report = '';
		if( isset($_POST['message']))
		{
		    $report = "<p>".sanitize_text_field($_POST['message'])."</p><hr>";
		}
		
		if( !isset($_POST['report'])){$report .= $domain."<br />".$admin_email;}
		
		$page=''; $p=''; $action='';
		if( isset($_POST['title']) ){$title = trim( sanitize_text_field($_POST['title'] ));}
		if( isset($_POST['report']) ){$report .= "<blockquote>".trim( sanitize_text_field($_POST['report']))."</blockquote>";}
		if( isset($_POST['page']) ){$page = trim( sanitize_text_field($_POST['page']));}
		if( isset($_POST['p']) ){$p = trim( sanitize_text_field($_POST['p']));}
		if( isset($_POST['action']) ){$action= trim( sanitize_text_field($_POST['action']));}
		
		$report .= "<hr><strong>Location Details:</strong><br />";
		$report .= "<u>Page:</u> ".$page."<br />";
		$report .= "<u>Sub Page[p]:</u> ".$p."<br />";
		$report .= "<u>Sub Page[action]:</u> ".$action."<br />";
		
		tebravo_mail(TEBRAVO_SUPPORT_EMAIL, $title.' #'.tebravo_create_hash(6), $report);
		
		echo "<font color=green>".__("Message Sent", TEBRAVO_TRANS);
		?>
			<script>
jQuery("#tebravo_send_loading").hide();
setTimeout(function()
		{
jQuery('#contact_support_form').hide(150);
jQuery('#contact-support-btn').show(150);
		},3000);
			</script>
		<?php
		exit;
	}
}

?>