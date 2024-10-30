<?php
/**
 * DASHBOARD CLASS
 * @since 1.0
 * Copyrights (C) 2017 Technoyer Solutions Ltd. <support@technoyer.com>
 */

//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !class_exists( 'tebravo_dashboard' ) )
{
	class tebravo_dashboard
	{
		
		public function __construct()
		{
			
			
		}
		public static function get_template()
		{
			if( defined( 'TEBRAVO_DASHBOARD_TEMPLATE' ) ){ return TEBRAVO_DASHBOARD_TEMPLATE;}
		}
		
		public static function security_percentage()
		{
			$full_degree = 125;
			$total =
			self::get_status('firewall', 'percent') +
			self::get_status('php', 'percent') +
			self::get_status('bruteforce', 'percent') +
			self::get_status('bruteforce_passwd', 'percent') +
			self::get_status('dbprefix', 'percent') +
			self::get_status('file_editor', 'percent') +
			self::get_status('config_perma', 'percent') +
			self::get_status('wp_debug', 'percent') +
			self::get_status('wp_login', 'percent') +
			self::get_status('wp_admin', 'percent') +
			self::get_status('wp_admin_proxy', 'percent') +
			self::get_status('idle', 'percent') +
			self::get_status('two_step', 'percent') +
			self::get_status('error_pages', 'percent') +
			self::get_status('wp_version', 'percent') +
			self::get_status('hotlinking', 'percent') +
			self::get_status('iframes', 'percent') ;
			$percent= ( $total / $full_degree ) * 100 ;
			if( $percent > 100 ){$percent = 100;}
			
			return ceil($percent);
		}
		//get secrity modules status and percentage
		//firewall | php | bruteforce | bruteforce_passwd | dbprefix | file_editor
		//config_perma | wp_debug | wp_login | wp_admin | wp_admin_proxy | idle
		//two_step | error_pages | wp_version | hotlinking | iframes
		public static function get_status( $target, $why=false )
		{
			global $wpdb;
			
			$green_icon = "<img src='".plugins_url('assets/img/ok.png', TEBRAVO_PATH)."' border=0>";
			$yellow_icon = "<img src='".plugins_url('assets/img/alert.png', TEBRAVO_PATH)."' border=0>";
			$red_icon = "<img src='".plugins_url('assets/img/shield_error.png', TEBRAVO_PATH)."' border=0>";
			
			//firewall
			if( $target == 'firewall' )
			{
				$value = trim( esc_html( esc_js( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'firewall_profile' ) ) ) );
				$result = $red_icon.' '.__("Disabled", TEBRAVO_TRANS);
				$percent = 0;
				
				switch ($value)
				{
					case 'high': $percent=10; $result=$green_icon.' '.ucfirst($value); break;
					case 'medium': $percent=6; $result=$yellow_icon.' '.ucfirst($value); break;
					case 'low': $percent=3; $result=$yellow_icon.' '.ucfirst($value); break;
				}
				
			}
			//php security
			else if( $target == 'php' )
			{
				$value = trim( esc_html( esc_js( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'firewall_php_security' ) ) ) );
				$result = $red_icon;
				$percent = 0;
				if( $value== 'checked' ){ $result = $green_icon; $percent = self::get_status('firewall', 'percent');}
				
			}
			//bruteforce
			else if( $target == 'bruteforce' )
			{
				$value = trim( esc_html( esc_js( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'bruteforce_protection' ) ) ) );
				$result = $red_icon;
				$percent = 0;
				if( $value== 'checked' ){ $result = $green_icon; $percent = 8;}
				
			}
			//bruteforce //strongpasswd
			else if( $target == 'bruteforce_passwd' )
			{
				$value = trim( esc_html( esc_js( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'enforce_strongpasswords' ) ) ) );
				$result = $red_icon;
				$percent = 0;
				if( $value== 'checked' ){ $result = $green_icon; $percent = 2;}
				
			}
			//table prefix
			else if( $target == 'dbprefix' )
			{
				$value = $wpdb->prefix;
				$result = $red_icon;
				$percent = 0;
				if( $value!= 'wp_' ){ $result = $green_icon; $percent = 10;}
				
			}
			//file editor
			else if( $target == 'file_editor' )
			{
				$result = $red_icon;
				$percent = 0;
				if( defined( 'DISALLOW_FILE_EDIT' ) && DISALLOW_FILE_EDIT == true )
				{
					$result = $green_icon; $percent = 10;
				}
			}
			//config permissions
			else if( $target == 'config_perma' )
			{
				$perma = tebravo_files::file_perms( ABSPATH.'wp-config.php' );
				$result = $red_icon;
				$percent = 0;
				if( $perma == '0400' || $perma == '0444')
				{
					$result = $green_icon; $percent = 10;
				}
			}
			//WP_DEBUG
			else if( $target == 'wp_debug' )
			{
				$result = $green_icon;
				$percent = 10;
				if( defined( 'WP_DEBUG' ) && WP_DEBUG == true )
				{
					$result = $red_icon; $percent = 0;
				}
			}
			//wp-login
			else if( $target == 'wp_login' )
			{
				$value = trim( esc_html( esc_js( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'hide_wplogin' ) ) ) );
				$login_slug = trim( esc_html( esc_js( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'wplogin_slug' ) ) ) );
				$result = $red_icon;
				$percent = 0;
				if( $value == 'checked' && $login_slug != 'wp-login.php' )
				{
					$result = $green_icon; $percent = 10;
				}
			}
			//wp-admin
			else if( $target == 'wp_admin' )
			{
				$value = trim( esc_html( esc_js( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'hide_wpadmin' ) ) ) );
				$result = $red_icon;
				$percent = 0;
				if( $value == 'checked' )
				{
					$result = $green_icon; $percent = 8;
				}
			}
			//wp-admin proxy
			else if( $target == 'wp_admin_proxy' )
			{
				$value = trim( esc_html( esc_js( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'wpadmin_block_proxy' ) ) ) );
				$result = $red_icon;
				$percent = 0;
				if( $value == 'checked' )
				{
					$result = $green_icon; $percent = 2;
				}
			}
			//idle_logout
			else if( $target == 'idle' )
			{
				$value = trim( esc_html( esc_js( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'idle_logout' ) ) ) );
				$result = $red_icon;
				$percent = 0;
				if( $value == 'checked' )
				{
					$result = $green_icon; $percent = 5;
				}
			}
			//two factor
			else if( $target == 'two_step' )
			{
				$value = trim( esc_html( esc_js( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'two_step_login' ) ) ) );
				$result = $red_icon;
				$percent = 0;
				if( $value == 'checked' )
				{
					$result = $green_icon; $percent = 10;
				}
			}
			//error_pages
			else if( $target == 'error_pages' )
			{
				$value = trim( esc_html( esc_js( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'404_page' ) ) ) );
				$result = $red_icon;
				$percent = 0;
				if( $value == 'checked' )
				{
					$result = $green_icon; $percent = 5;
				}
			}
			//wp_version
			else if( $target == 'wp_version' )
			{
				$value = trim( esc_html( esc_js( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'hidewpversion' ) ) ) );
				$result = $red_icon;
				$percent = 0;
				if( $value == 'checked' )
				{
					$result = $green_icon; $percent = 5;
				}
			}
			//hotlinking
			else if( $target == 'hotlinking' )
			{
				$value = trim( esc_html( esc_js( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'prevent_outside_images' ) ) ) );
				$result = $red_icon;
				$percent = 0;
				if( $value == 'checked' )
				{
					$result = $green_icon; $percent = 5;
				}
			}
			//iframes
			else if( $target == 'iframes' )
			{
				$value = trim( esc_html( esc_js( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'prevent_outside_iframe' ) ) ) );
				$result = $red_icon;
				$percent = 0;
				if( $value == 'checked' )
				{
					$result = $green_icon; $percent = 5;
				}
			}
			
			//output
			if( $why == 'status' ){return $result;}
			else if( $why == 'percent' ){return $percent;}
		}
		
		
		public static function disk_space( $for=false )
		{
			if( !$for ){$for = 'total';}
			$total_space = @disk_total_space("../");
			$free_space = @disk_free_space("../");
			$used_space = '';
			$result= '';
			if( $total_space )
			{
				$used_space = ceil( $total_space - $free_space );
				if( $for == 'total' ){$result = tebravo_ConvertBytes( $total_space );}
				else if( $for == 'free' ){$result = tebravo_ConvertBytes( $free_space );}
				else if( $for == 'used' ){$result = tebravo_ConvertBytes( $used_space );}
				
			}
			
			return $result;
		}
		
		public static function last_failed_login_attempts( $limit=5 )
		{
			global $wpdb;
			
			$dbTable = $wpdb->prefix.TEBRAVO_DBPREFIX.'attemps';
			$query =  "SELECT ipaddress,userid,email,user_login,time_blocked FROM $dbTable WHERE user_login!='' ORDER BY time_blocked DESC Limit ".(int)$limit;
			//$query =  "SELECT ipaddress,userid,email,user_login,time_blocked FROM $dbTable ORDER BY time_blocked DESC Limit ".(int)$limit;
			$results = $wpdb->get_results( $query );
			
			return $results;
			
		}
		
		public static function last_blocked( $limit=5 )
		{
			global $wpdb;
			
			$dbTable = $wpdb->prefix.TEBRAVO_DBPREFIX.'firewall_actions';
			$query =  "SELECT ipaddress,country_code,block_reason,time_blocked FROM $dbTable ORDER BY time_blocked DESC Limit ".(int)$limit;
			$results = $wpdb->get_results( $query );
			
			return $results;
			
		}
		
		public static function last_antivirus( $limit=5 )
		{
			global $wpdb;
			
			$dbTable = $wpdb->prefix.TEBRAVO_DBPREFIX.'scan_ps';
			$query =  "SELECT status,start_at,total_files,infected_files,start_by,scan_type,p_percent FROM $dbTable ORDER BY id DESC Limit ".(int)$limit;
			$results = $wpdb->get_results( $query );
			
			return $results;
			
		}
		
		public static function moderated_users()
		{
			global $wpdb;
			
			//check option
			if( trim( esc_html( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'moderate_new_members')) ) != 'checked' )
			{
				return "<font color=brown><i>".__("Option Disabled", TEBRAVO_TRANS)."</i></font>";
			}
			
			//continue
			$query = "SELECT u.ID, u.user_login, u.user_nicename, u.user_email
			FROM $wpdb->users u
			INNER JOIN $wpdb->usermeta m ON m.user_id = u.ID
			WHERE m.meta_key = '".TEBRAVO_DBPREFIX."user_status'
AND m.meta_value = 'pending'
ORDER BY u.user_registered";
			
			$results = $wpdb->get_results( $query );
			$users = array();
			//var_dump($results);
			if( null!= $results )
			{
				$users = $results;
			}
			
			return $users;
		}
		
		public static function change_username( $user_id )
		{
			$html = new tebravo_html();
			$nonce = $html->init->create_nonce( 'do-change-admin-name');
			if( $user_id > 0 )
			{
				$user = get_user_by( 'ID', $user_id );
				//check if is dangerous
				$dangs_array = array( "admin", "wordpress" );
				if( !preg_match('/'.implode("|", $dangs_array).'/i', strtolower($user->user_login)) )
				{
					$die_msg = __("This wizard made for dangerous login names only!", TEBRAVO_TRANS);
					tebravo_die(true, $die_msg, false, true);
				}
				//continue
				if( !$_POST )
				{
					if( $user != '' ){
						$output = self::change_username_form($user, $nonce);
					} else {
						$output = "<font color=brown>".__("Wrong User ID", TEBRAVO_TRANS)."</font>";
					}
					
					return $output;
				} else {
					global $wpdb;
					
					$user_login = trim( esc_html( esc_js( $_POST['user_login'] ) ) );
					$count_exists = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->users WHERE user_login='".$user_login."'");
					if( $count_exists == 0 )
					{
						$wpdb->update( $wpdb->users, array("user_login" => sanitize_text_field($user_login)), array("ID" => $user_id) );
						$output = "<font color=green>".__("Username Changed Successfully", TEBRAVO_TRANS)."</font><br />";
						$edit_href = admin_url().'user-edit.php?user_id='.$user_id;
						$output .= "<a href='$edit_href'>".__("Edit", TEBRAVO_TRANS)."</a>";
					} else {
						$output = "<font color=brown>".__("Username Already Exists", TEBRAVO_TRANS)."</font>";
						$output .= self::change_username_form($user, $nonce);
					}
				}
			} else {
				$output = "<font color=brown>".__("Wrong User ID", TEBRAVO_TRANS)."</font>";
			}
			
			return $output;
		}
		
		protected static function change_username_form( $user=array(), $nonce )
		{
			$html = new tebravo_html();
			$output = "<div class='tebravo_block_blank' style='width:100%'><form method=post>";
			$output .= "<input type='hidden' name='_nonce' value='$nonce'>";
			$output .= "<table border=0 width=100% cellspacing=0>";
			$output .= "<tr class='tebravo_headTD'><td><strong>".__("Old Login Name", TEBRAVO_TRANS)."</strong></td><tr>";
			$output .= "<tr class='tebrvo_underTD'><td>".$user->user_login."<br />";
			$output .= "<font class='smallfont'>".$user->user_email."</font>";
			$output .= "</td><tr>";
			$output .= "<tr class='tebravo_headTD'><td><strong>".__("New Login Name", TEBRAVO_TRANS)."</strong></td><tr>";
			$output .= "<tr class='tebravo_underTD'><td><input type='text' name='user_login' placeholder='".__("New Login Name", TEBRAVO_TRANS)."'></td></tr>";
			$output .= "<tr class='tebravo_underTD'><td>".$html->button_small(__("Change", TEBRAVO_TRANS), "submit")."</td></tr>";
			$output .= "</table>";
			$output .= "</form></div>";
			
			return $output;
		}
		
		
		public static function contact_form( $for='support' )
		{
			$helper = new tebravo_html();
			//set TO
			$to = 'support@technoyer.com';
			if( defined( 'TEBRAVO_SUPPORT_EMAIL' ) 
					&& TEBRAVO_SUPPORT_EMAIL!='' 
					&& filter_var(TEBRAVO_SUPPORT_EMAIL, FILTER_VALIDATE_EMAIL) )
			{
				$to = TEBRAVO_SUPPORT_EMAIL;
			}
			
			//subject & title
			$ms_hook = is_multisite() ? 'network_' : '';
			$title = __("Report a Bug", TEBRAVO_TRANS);
			$subject = tebravo_getDomainUrl($ms_hook.site_url()).' - Report a Bug #'.tebravo_create_hash(6);
			if( $for == 'support' )
			{
				$title = __("Contact Support", TEBRAVO_TRANS);
				$subject = tebravo_getDomainUrl($ms_hook.site_url()).' - Support Request #'.tebravo_create_hash(6);
			}
			
			//send message
			if( $_POST )
			{
				$log_file = TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/log/log.txt';
				$phplog_file = TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/log/php_log.txt';
				$config_file = ABSPATH.'wp-config.php';
				$rewrite_file = ABSPATH.'.htaccess';
				
				
				if( !file_exists($rewrite_file) ){$rewrite_file = '';}
				//attach log files
				$attachments[] = '';
				if( isset( $_POST['include_logs'] ) && $_POST['include_logs'] == 'checked' )
				{
					if( file_exists( $log_file) ){$attachments[] = realpath($log_file);}
					if( file_exists( $phplog_file) ){$attachments[] = realpath($phplog_file);}
					if( file_exists( $config_file) ){$attachments[] = realpath($config_file);}
					if( file_exists( $rewrite_file) ){$attachments[] = realpath($rewrite_file);}
				}
				//message
				$message = '';
				if( isset( $_POST['message'] ) && $_POST['message'] != '' )
				{
					$message= $_POST['message'];
				}
				//cc
				$cc = 'reports@technoyer.com';
				tebravo_mail($to, $subject, $message, 'technoyer_report', $attachments, $cc);
				
				//back to dashboard
				$redirect_to = add_query_arg( array('action' => 'thankyou' ) , $helper->init->admin_url);
				tebravo_redirect_js($redirect_to);
			} else {
				$helper->header($title, false, 'contact_support.png');
				
				$action = 'report';
				if( $for == 'support' ){$action = 'contact';}
				
				$form_url = add_query_arg( array("action" => $action), $helper->init->admin_url);
				$output[] = "<form action='".$form_url."' method=post>";
				$output[] = "<input type='hidden' name='_nonce' value='".$helper->init->create_nonce('contact-support-reporting')."'>";
				$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
				$output[] = "<table border='0' width=100% cellspacing=0>";
				$output[] = "<tr class='tebravo_headTD'><td>".__("Write to support ...", TEBRAVO_TRANS)."</td></tr>";
				$output[] = "<tr class='tebravo_underTD'><td>";
				$output[] = "<textarea name='message' style='width:350px; height:130px;' required></textarea> <br />";
				$output[] = "<input type='checkbox' name='include_logs' value='checked' id='include_logs'>";
				$output[] = "<label for='include_logs'>".__("Include Log Files", TEBRAVO_TRANS)."</label>";
				$output[] = "</td></tr>";
				$output[] = "<tr class='tebravo_underTD'><td>";
				$output[] = $helper->button_small_info(__("Do it", TEBRAVO_TRANS), "submit");
				$output[] = $helper->button_small(__("Back", TEBRAVO_TRANS), "button", "back");
				$output[] = "</td></tr>";
				$output[] = "</table></div></form>";
				
				echo implode("\n", $output);
				?>
				<script>
				jQuery("#back").click(function()
						{
							window.location.href = "<?php echo $helper->init->admin_url; ?>";
						}
						);
				</script>
				<?php 
				$helper->footer();
			}
		}
	}
	
}
?>