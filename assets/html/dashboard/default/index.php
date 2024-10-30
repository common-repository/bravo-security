<?php
/**
 * DASHBOARD TEMPLATE
 * TEMPLATE NAME: default
 * @since 1.0
 * Copyrights (C) 2017 Technoyer Solutions Ltd. <support@technoyer.com>
 */

//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !class_exists( 'tebravo_dashboard_template' ) )
{
	class tebravo_dashboard_template
	{
		public $backup_folder, $backup_path, $tmp, $log, $php_log, $email_log;
		//constructor
		public function __construct()
		{
			if( defined( 'TEBRAVO_BACKUPFOLDER') ){$this->backup_folder = TEBRAVO_BACKUPFOLDER;}
			$this->backup_path = TEBRAVO_DIR.'/'.$this->backup_folder;
			$this->tmp = $this->backup_path.'/tmp';
			$this->log = $this->backup_path.'/log/log.txt';
			$this->php_log = $this->backup_path.'/log/php_log.txt';
			$maillog_filename = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'maillog_filename') ) );
			$this->email_log= $this->backup_path.'/log/'.$maillog_filename;
			
		}
		
		//styles and scripts
		public function enqueue()
		{
			if ( is_admin() && current_user_can( 'manage_options' ) == true)
			{
				wp_enqueue_style (TEBRAVO_SLUG."circle_css", plugins_url(TEBRAVO_SLUG.'/assets/html/dashboard/default/css/circle.css'));
				wp_enqueue_style (TEBRAVO_SLUG."dashboard_css", plugins_url(TEBRAVO_SLUG.'/assets/html/dashboard/default/css/dashboard.css'));
				wp_enqueue_style (TEBRAVO_SLUG."_installer_css", plugins_url('/includes/install/style.css', TEBRAVO_PATH));
				//wp_enqueue_script (TEBRAVO_SLUG."_bravo_installer_script_js", plugins_url('/includes/install/script.js', TEBRAVO_PATH), '', '', true);
				
			}
		}
		
		//dashboard //HTML
		public function dashboard()
		{
			// echo $this->circle('38', '25', 'medium');
			$this->html = new tebravo_html();
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			//tour
			tebravo_tour::dashboard();
			
			$title = __("Bravo WP Ultimate Security", TEBRAVO_TRANS);
			
			if( isset($_GET['action']) && $_GET['action'] == 'changeusername' && isset($_GET['user_id'] ) )
			{
				if( empty( $_GET['_nonce'])
						|| false === wp_verify_nonce($_GET['_nonce'], $this->html->init->security_hash.'change-admin-name'))
				{
					tebravo_redirect_js( $this->html->init->admin_url.'&err=02'); exit;
				}
				
				$user_id = trim( esc_html( esc_js( $_GET['user_id'] ) ) );
				$title = " ".__("Change User Login Name", TEBRAVO_TRANS);
				$this->html->header($title, false, 'dashboard.png');
				$output = tebravo_dashboard::change_username($user_id);
				
				echo $output;
				$this->html->footer();
				
				exit;
			}
			
			if( isset($_GET['action']) && $_GET['action'] == 'end_tour' )
			{
				tebravo_utility::update_option(TEBRAVO_DBPREFIX.'tour', 'done');
				tebravo_redirect_js($this->html->init->admin_url); exit;
			}
			
			//enable / disable admins_online
			if( isset( $_GET['action'] )
					&& $_GET['action'] == 'enable_adminsonline'
					&& isset( $_GET['_nonce'] )
					&& false !== wp_verify_nonce($_GET['_nonce'], $this->html->init->security_hash.'enable-adminsonline')
					)
			{
				tebravo_utility::update_option( TEBRAVO_DBPREFIX.'admins_online', 'checked' );
			} else if( isset( $_GET['action'] )
					&& $_GET['action'] == 'disable_adminsonline'
					&& isset( $_GET['_nonce'] )
					&& false !== wp_verify_nonce($_GET['_nonce'], $this->html->init->security_hash.'disable-adminsonline')
					)
			{
				tebravo_utility::update_option( TEBRAVO_DBPREFIX.'admins_online', 'no' );
			} else if( isset( $_GET['action'] )
					&& $_GET['action'] == 'contact'
					)
			{
				tebravo_dashboard::contact_form( 'support' );
				exit;
			} else if( isset( $_GET['action'] )
					&& $_GET['action'] == 'report'
					)
			{
				tebravo_dashboard::contact_form( 'report' );
				exit;
			} else if( isset( $_GET['action'] )
					&& $_GET['action'] == 'thankyou'
					)
			{
				$this->html->popup_modal(true, __("Message Sent. Thank you.", TEBRAVO_TRANS), "thankyou", false);
				?>
				<script>tebravo_open_modal();</script>
				<?php 
			}
			
			$title .= " ".__("Dashboard", TEBRAVO_TRANS);
			$extra = '';
			if( defined('TEBRAVO_VERSIONTYPE') && TEBRAVO_VERSIONTYPE != 'PRO' )
			{
				
				$extra = "<a href='".tebravo_create_donate_url( 'dashboard' )."' target=_blank class='tebravo_curved'>".__("Go Pro", TEBRAVO_TRANS)."</a>";
			}
			$this->html->header($title, false, 'dashboard.png', $extra);
			
			$output[] = "<div class='tebravo_dashboard_default' style='width:100%'>";
			
			$user = wp_get_current_user();
			
			$contact_url = add_query_arg(array( "action"=>"contact"), $this->html->init->admin_url);
			$report_url = add_query_arg(array( "action"=>"report"), $this->html->init->admin_url);
			$output[] = "<a href='".$contact_url."'>".__("Contact Support")."</a> | ";
			$output[] = "<a href='".$report_url."'>".__("Report a Bug")."</a>";
			$output[] = "<br />";
			$output[] = "<div class='tebravo_cell'>";
			$output[] = $this->box( __("Welcome", TEBRAVO_TRANS)." ".$user->display_name, $this->last_login_details($user));
			$output[] = $this->box( __("Security Alerts", TEBRAVO_TRANS), $this->security_alerts());
			$output[] = $this->box( __("Notifications", TEBRAVO_TRANS), $this->notifications());
			$output[] = $this->box( __("Admins Online", TEBRAVO_TRANS), $this->admins_online());
			$output[] = "</div>";
			
			$output[] = "<div class='tebravo_cell'>";
			$output[] = $this->box( __("PHP Info", TEBRAVO_TRANS), $this->system_info());
			$output[] = $this->box( __("Last 5 Blocked IPs by Firewall", TEBRAVO_TRANS), $this->last_firewall_blocked(5), true, 'firewall_more');
			$output[] = $this->box( __("Last 5 Blocked for Failed Login Attempts", TEBRAVO_TRANS), $this->last_failed_login(5));
			$output[] = $this->box( __("Last 5 Antivirus Processes", TEBRAVO_TRANS), $this->last_antivirus(5), true, 'antivirus_more');
			$output[] = $this->box( __("Users Wait Approval", TEBRAVO_TRANS), $this->users_need_approval());
			$output[] = "</div>";
			//$output[] = "<div class='cell'>2</div>";
			
			//security status
			$defense_status = $this->circle(tebravo_dashboard::security_percentage(), tebravo_dashboard::security_percentage(), 'medium');
			$defense_status .= "<div class='tebravo_clear'></div>";
			$defense_status .= $this->defense_status();
			$output[] = "<div class='tebravo_cell'>";
			$output[] = $this->box( __("Defense Stats", TEBRAVO_TRANS), $defense_status);
			$output[] = "</div>";
			
			$output[] = "</div>";
			
			echo implode("\n", $output);
			
			//admins online JS
			$nonce = $this->html->init->create_nonce('enable-adminsonline');
			$nonce_disable= $this->html->init->create_nonce('disable-adminsonline');
			
			$ajax_url = add_query_arg( array(
					'action' => 'adminsonline_update',
					'_nonce' => wp_create_nonce('adminsonline_update')
			), admin_url('admin-ajax.php'));
			
			$js = "<script>";
			//enable
			$js .= "jQuery('#admins_online').load('$ajax_url');";
			$js .= "jQuery('#adminsonline_enable').click(function(){";
			$js .= "window.location.href='".$this->html->init->admin_url."&action=enable_adminsonline&_nonce=$nonce#adminsonline';";
			$js .= "jQuery('#admins_online').load('$ajax_url');";
			$js .= "});";
			//disable
			$js .= "jQuery('#adminsonline_disable').click(function(){";
			$js .= "window.location.href='".$this->html->init->admin_url."&action=disable_adminsonline&_nonce=$nonce_disable#adminsonline';";
			$js .= "});";
			//refresh
			$js .= "jQuery('#adminsonline_refresh').click(function(){";
			$js .= "jQuery('#admins_online').html('".__("Loading", TEBRAVO_TRANS)."...');";
			$js .= "jQuery('#admins_online').load('$ajax_url');";
			$js .= "});";
			//more //firewall
			$js .= "jQuery('#firewall_more').click(function(){";
			$js .= "window.location.href='".$this->html->init->admin_url."-firewall&p=log';";
			$js .= "});";
			//more //attempts
			$js .= "jQuery('#loginattempts_more').click(function(){";
			$js .= "window.location.href='".$this->html->init->admin_url."-logwatch&p=login-attempts';";
			$js .= "});";
			//more //antivirus
			$js .= "jQuery('#antivirus_more').click(function(){";
			$js .= "window.location.href='".$this->html->init->admin_url."-antivirus';";
			$js .= "});";
			$js .= "</script>";
			echo $js;
			
			$this->html->footer();
		}
		
		protected function last_antivirus( $limit=false )
		{
			global $wpdb;
			
			$results = tebravo_dashboard::last_antivirus( $limit );
			
			$output = "<font color=green><i>".__("Everything is just fine", TEBRAVO_TRANS)."</i></font>";
			if( null!=$results )
			{
				$output = "<div  style='max-height:100px; overflow-y:scroll;'><table border=0 width=100% cellspacing=0>";
				
				foreach ( $results as $row )
				{
					$status = $row->status;
					$infected = $row->infected_files;
					$start_by = $row->start_by;
					$scan_type = $row->scan_type;
					$date = '';
					if( !empty($row->start_at) )
					{
						$date = tebravo_ago( $row->start_at)." ".__("ago", TEBRAVO_TRANS);
					}
					
					$owner = 'CRONJOB';
					if( $start_by != 'cronjob')
					{
						if( $start_by > 0){
							$user = get_user_by('ID', $start_by);
							$owner = $user->display_name;
						} else {
							$owner = '--';
						}
					}
					
					$color = '#2DA0E3';
					if( $infected > 0)
					{
						$color = '#B43A3D';
					}
					$output .= "<tr class='tebravo_underTD'> <td width=60%>".strtoupper($scan_type)."<br />";
					$output .= "<font class='smallfont'>".__("Owner", TEBRAVO_TRANS).": $owner | ".__("Affected", TEBRAVO_TRANS).": <font color='$color'>".(int)$infected."</font></font></td>";
					$output .= "<td>".$date."</td></tr>";
					
				}
				$output .= "</table></div>";
			}
			
			return $output;
		}
		
		//get online admins
		protected function admins_online()
		{
			$helper = new tebravo_html();
			
			$admins_online_option = trim( esc_html( esc_js( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'admins_online' ) ) ) );
			$tools = "<span class='tebravo_breadcrumbs' id='adminsonline_enable'>".__("Enable", TEBRAVO_TRANS)."</span>";
			$results = "<font color=brown><i>".__("Option Disabled", TEBRAVO_TRANS)."</i></font>";
			if( $admins_online_option == 'checked'
					|| (isset( $_GET['action'] )
							&& $_GET['action'] == 'enable_adminsonline'
							&& ( isset( $_GET['_nonce'] )
									&& false !== wp_verify_nonce($_GET['_nonce'], $helper->init->security_hash.'enable-adminsonline'))
							))
			{
				$tools= "<span class='tebravo_breadcrumbs' id='adminsonline_disable'>".__("Disable", TEBRAVO_TRANS)."</span> . ";
				$tools.= "<span class='tebravo_breadcrumbs' id='adminsonline_refresh'>".__("Refresh", TEBRAVO_TRANS)."</span>";
				
				$results = "<div id='admins_online' style='max-height:100px; overflow-y:scroll;'>".__("Loading", TEBRAVO_TRANS)."...</div>";
			}
			
			$output[] = "<A name='adminsonline'></A><table border=0 width=100% cellspacing=0>";
			$output[] = "<tr class='tebravo_headTD'><td>".$tools."</td></tr>";
			$output[] = "</table>";
			$output[] = $results;
			$output[] = "<div id='tebravo_results'></div>";
			
			return implode("\n", $output);
		}
		//notifications
		protected function notifications()
		{
			$backup_size = tebravo_dirs::dir_size( $this->backup_path );
			$tmp_size = tebravo_dirs::dir_size( $this->tmp );
			$log_size = @filesize( $this->log );
			$php_log_size = @filesize( $this->php_log );
			$email_log_size = @filesize( $this->email_log );
			
			$yellow = '<img src="'.plugins_url('/assets/html/dashboard/default/img/yellowled.png', TEBRAVO_PATH).'">';
			$red = '<img src="'.plugins_url('/assets/html/dashboard/default/img/redled.png', TEBRAVO_PATH).'">';
			
			$notifications = '<table border=0 width=100% cellspacing=0>';
			$issues=0;
			//return tebravo_ConvertBytes($backup_size);
			//backup
			if( $backup_size > (550*1000*1000) && $backup_size < (800*1000*1000))
			{
				$notifications .= "<tr class='tebravo_underTD'><td>".__("Backups Size", TEBRAVO_TRANS);
				$notifications .= "<br /><font class='smallfont'>".tebravo_ConvertBytes($backup_size)." ".__("Large", TEBRAVO_TRANS)."</font>";
				$notifications .= "</td><td width=20>".$yellow."</td></tr>";
				$issues +=1;
			} else if( $backup_size > (800*1000*1000))
			{
				$notifications .= "<tr class='tebravo_underTD'><td>".__("Backups Size", TEBRAVO_TRANS);
				$notifications .= "<br /><font class='smallfont'>".tebravo_ConvertBytes($backup_size)." ".__("Too Large", TEBRAVO_TRANS)."</font>";
				$notifications .= "</td><td width=20>".$red."</td></tr>";
				$issues +=1;
			}
			//tmp
			if( $tmp_size > (10*1000*1000) && $tmp_size < (20*1000*1000))
			{
				$notifications .= "<tr class='tebravo_underTD'><td>".__("/tmp Size", TEBRAVO_TRANS);
				$notifications .= "<br /><font class='smallfont'>".tebravo_ConvertBytes($tmp_size)." ".__("Large", TEBRAVO_TRANS)."</font>";
				$notifications .= "</td><td width=20>".$yellow."</td></tr>";
				$issues +=1;
			} else if( $tmp_size > (20*1000*1000))
			{
				$notifications .= "<tr class='tebravo_underTD'><td>".__("/tmp Size", TEBRAVO_TRANS);
				$notifications .= "<br /><font class='smallfont'>".tebravo_ConvertBytes($tmp_size)." ".__("Too Large", TEBRAVO_TRANS)."</font>";
				$notifications .= "</td><td width=20>".$red."</td></tr>";
				$issues +=1;
			}
			//log
			if( $log_size > (3*1000*1000) && $log_size < (5*1000*1000))
			{
				$notifications .= "<tr class='tebravo_underTD'><td>".__("Log Size", TEBRAVO_TRANS);
				$notifications .= "<br /><font class='smallfont'>".tebravo_ConvertBytes($log_size)." ".__("Large", TEBRAVO_TRANS)."</font>";
				$notifications .= "</td><td width=20>".$yellow."</td></tr>";
				$issues +=1;
			} else if( $log_size > (5*1000*1000))
			{
				$notifications .= "<tr class='tebravo_underTD'><td>".__("Log Size", TEBRAVO_TRANS);
				$notifications .= "<br /><font class='smallfont'>".tebravo_ConvertBytes($log_size)." ".__("Too Large", TEBRAVO_TRANS)."</font>";
				$notifications .= "</td><td width=20>".$red."</td></tr>";
				$issues +=1;
			}
			//phplog
			if( $php_log_size > (3*1000*1000) && $php_log_size < (5*1000*1000))
			{
				$notifications .= "<tr class='tebravo_underTD'><td>".__("PHP Log Size", TEBRAVO_TRANS);
				$notifications .= "<br /><font class='smallfont'>".tebravo_ConvertBytes($php_log_size)." ".__("Large", TEBRAVO_TRANS)."</font>";
				$notifications .= "</td><td width=20>".$yellow."</td></tr>";
				$issues +=1;
			} else if( $php_log_size > (5*1000*1000))
			{
				$notifications .= "<tr class='tebravo_underTD'><td>".__("PHP Log Size", TEBRAVO_TRANS);
				$notifications .= "<br /><font class='smallfont'>".tebravo_ConvertBytes($php_log_size)." ".__("Too Large", TEBRAVO_TRANS)."</font>";
				$notifications .= "</td><td width=20>".$red."</td></tr>";
				$issues +=1;
			}
			//emaillog
			if( $email_log_size > (3*1000*1000) && $email_log_size < (5*1000*1000))
			{
				$notifications .= "<tr class='tebravo_underTD'><td>".__("Email Log Size", TEBRAVO_TRANS);
				$notifications .= "<br /><font class='smallfont'>".tebravo_ConvertBytes($email_log_size)." ".__("Large", TEBRAVO_TRANS)."</font>";
				$notifications .= "</td><td width=20>".$yellow."</td></tr>";
				$issues +=1;
			} else if( $email_log_size > (5*1000*1000))
			{
				$notifications .= "<tr class='tebravo_underTD'><td>".__("Email Log Size", TEBRAVO_TRANS);
				$notifications .= "<br /><font class='smallfont'>".tebravo_ConvertBytes($email_log_size)." ".__("Too Large", TEBRAVO_TRANS)."</font>";
				$notifications .= "</td><td width=20>".$red."</td></tr>";
				$issues +=1;
			}
			//security hash
			$security_hash = ( esc_html( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'security_hash')));
			if( empty($security_hash) )
			{
				$notifications .= "<tr class='tebravo_underTD'><td>".__("Security Hash", TEBRAVO_TRANS);
				$notifications .= "<br /><font class='smallfont'>".__("Please add security hash", TEBRAVO_TRANS)."</font>";
				$notifications .= "</td><td width=20>".$red."</td></tr>";
				$issues +=1;
			}
			
			if( $issues == 0)
			{
				$notifications .= "<tr class='tebravo_underTD'><td colspan=2><font color=green><i>".__(" Everything is just fine", TEBRAVO_TRANS)."</i></font></td></tr>";
			}
			
			$notifications .= '</table>';
			
			return $notifications;
		}
		
		//security alerts
		protected function security_alerts()
		{
			global $wpdb;
			
			$helper = new tebravo_html();
			
			$alerts = '<table border=0 width=100% cellspacing=0>';
			
			//admin login names
			$results_admin = $wpdb->get_results("SELECT ID,user_login,user_email FROM $wpdb->users WHERE user_login like'%admin%' or user_login like'%wordpress%'");
			$alerts .= "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Dangerous Login Names", TEBRAVO_TRANS)."</strong></td></tr>";
			if( null!=$results_admin )
			{
				foreach ($results_admin as $admin_user)
				{
					$manage_href = $helper->init->admin_url.'&action=changeusername&user_id='.$admin_user->ID."&_nonce=".$helper->init->create_nonce('change-admin-name');
					$manage = "<a href='".$manage_href."' target=_blank>".__("Change", TEBRAVO_TRANS)."</a>";
					$alerts .= "<tr class='tebravo_underTD'><td>".$admin_user->user_login."<br /><font class='smallfont'>".$admin_user->user_email."</font></td><td width=30>$manage</td></tr>";
				}
			} else {
				$alerts .= "<tr class='tebravo_underTD'><td colspan=2><font color=green><i>".__(" Everything is just fine", TEBRAVO_TRANS)."</i></font></td></tr>";
			}
			
			//security issues
			$alerts .= "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Security Issues", TEBRAVO_TRANS)."</strong></td></tr>";
			$issues = 0;
			//wp prefix
			if( $wpdb->prefix == 'wp_' )
			{
				$edit_dbprefix_href = $helper->init->admin_url."-wconfig&p=wizard";
				$edit_dbprefix= "<a href='".$edit_dbprefix_href."' target=_blank>".__("Change", TEBRAVO_TRANS)."</a>";
				$alerts .= "<tr class='tebravo_underTD'><td>DB Prefix (".$wpdb->prefix.")<br /><font class='smallfont'>".__("Not Safe", TEBRAVO_TRANS)."</font></td><td width=30>$edit_dbprefix</td></tr>";
				
				$issues += 1;
			}
			//wp-config.php perma
			$wp_config_chmode = tebravo_files::file_perms(ABSPATH.'wp-config.php');
			$not_writable_array = array('0400', '0444');
			if( !in_array($wp_config_chmode, $not_writable_array) )
			{
				$config_chmod_href = $helper->init->admin_url."-wconfig#configperma";
				$config_chmod = "<a href='".$config_chmod_href."' target=_blank>".__("Change", TEBRAVO_TRANS)."</a>";
				$alerts .= "<tr class='tebravo_underTD'><td>wp-config.php ".__("Permissions", TEBRAVO_TRANS)." (".$wp_config_chmode.")<br /><font class='smallfont'>".__("Not Safe", TEBRAVO_TRANS)."</font></td><td width=30>$config_chmod</td></tr>";
				
				$issues += 1;
			}
			//bruteforece
			$bruteforce = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'bruteforce_protection' ) ) );
			if( $bruteforce!='checked' )
			{
				$bruteforce_tool_href = $helper->init->admin_url."-bruteforce";
				$bruteforce_tool = "<a href='".$bruteforce_tool_href."' target=_blank>".__("Enable", TEBRAVO_TRANS)."</a>";
				$alerts .= "<tr class='tebravo_underTD'><td>".__("Brute Force Protection", TEBRAVO_TRANS)." <font color=brown><i>".__("Disabled", TEBRAVO_TRANS)."</i></font><br /><font class='smallfont'>".__("Not Safe", TEBRAVO_TRANS)."</font></td><td width=30>$bruteforce_tool</td></tr>";
				
				$issues += 1;
			}
			//wp-login
			$hide_wplogin = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'hide_wplogin') ) );
			$wplogin_slug = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'wplogin_slug') ) );
			if( $hide_wplogin!='checked' || $wplogin_slug=='wp-login.php')
			{
				$hide_wplogin_tool_href = $helper->init->admin_url."-wadmin&p=hide-wplogin";
				$hide_wplogin_tool = "<a href='".$hide_wplogin_tool_href."' target=_blank>".__("Hide", TEBRAVO_TRANS)."</a>";
				$alerts .= "<tr class='tebravo_underTD'><td>`wp-login.php` ".__("Still Appear", TEBRAVO_TRANS)."<br /><font class='smallfont'>".__("Not Safe", TEBRAVO_TRANS)."</font></td><td width=30>$hide_wplogin_tool</td></tr>";
				
				$issues += 1;
			}
			//two factors
			$two_factors = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'two_step_login') ) );
			if( $two_factors!='checked')
			{
				$two_factors_tool_href = $helper->init->admin_url."-wadmin&p=2fa";
				$two_factors_tool = "<a href='".$two_factors_tool_href."' target=_blank>".__("Enable", TEBRAVO_TRANS)."</a>";
				$alerts .= "<tr class='tebravo_underTD'><td>Two Factors <font color=brown><i>".__("Disabled", TEBRAVO_TRANS)."</i></font><br /><font class='smallfont'>".__("Not Safe", TEBRAVO_TRANS)."</font></td><td width=30>$two_factors_tool</td></tr>";
				
				$issues += 1;
			}
			//blog desc
			$blog_desc = get_bloginfo( 'description' );
			if( __($blog_desc, 'textdomain') == 'Just another WordPress site')
			{
				$blog_desc_tool_href = admin_url()."options-general.php";
				$blog_desc_tool = "<a href='".$blog_desc_tool_href."' target=_blank>".__("Change", TEBRAVO_TRANS)."</a>";
				$alerts .= "<tr class='tebravo_underTD'><td> ".__("Blog Description", TEBRAVO_TRANS)."<br /><font class='smallfont'>".$blog_desc."</font></td><td width=30>$blog_desc_tool</td></tr>";
				
				$issues += 1;
			}
			
			if( $issues == 0)
			{
				$alerts .= "<tr class='tebravo_underTD'><td colspan=2><font color=green><i>".__(" Everything is just fine", TEBRAVO_TRANS)."</i></font></td></tr>";
			}
			
			$alerts .= '</table>';
			
			return $alerts;
		}
		//last failed login attempts
		protected function last_failed_login( $limit=5 )
		{
			global $wpdb;
			
			$results = tebravo_dashboard::last_failed_login_attempts( $limit );
			
			$output = "<font color=green><i>".__("Everything is just fine", TEBRAVO_TRANS)."</i></font>";
			if( null!=$results )
			{
				$output = "<div  style='max-height:100px; overflow-y:scroll;'><table border=0 width=100% cellspacing=0>";
				
				foreach ( $results as $row )
				{
					$ipaddress = trim( esc_html( esc_js( $row->ipaddress) ) );
					if( filter_var($ipaddress, FILTER_VALIDATE_IP ) ):
					$country_code = tebravo_agent::ip2country( $ipaddress);
					$user_login = trim( esc_html( esc_js( $row->user_login )));
					
					
					$date = '';
					if( !empty($row->time_blocked) )
					{
						$date = tebravo_ago( $row->time_blocked)." ".__("ago", TEBRAVO_TRANS);
					}
					$flag = '<img src="'.plugins_url('assets/img/blank.png', TEBRAVO_PATH).'" class="tebravo_flag flag-'.strtolower($country_code).'" alt="'.$country_code.'" /> ';
					$output .= "<tr class='tebravo_underTD'><td width=60%>".$flag.$ipaddress."<br />";
					$output .= "<font class='smallfont'>".$user_login."</font></td>";
					$output .= "<td>".$date."</td></tr>";
					endif;
				}
				$output .= "</table></div>";
			}
			
			return $output;
			
		}
		protected function users_need_approval()
		{
			$helper = new tebravo_html();
			$moderated_users = tebravo_dashboard::moderated_users();
			
			if( !is_array( $moderated_users ) ) {return $moderated_users;}
			
			if( null!=$moderated_users )
			{
				$output = "<div style='max-heigth:100px; overflow-y:scroll;'><table border=0 width=100% cellspacing=0>";
				foreach ( $moderated_users as $key=>$user )
				{
					$approve_btn = add_query_arg( array(
							'action' => 'approved',
							'user' => $user->ID,
							'_nonce' => $helper->init->create_nonce('approved-user-'.$user->ID)
					), admin_url().'users.php');
					$output .= "<tr class='tebravo_underTD'><td width=15>".$user->ID."</td>";
					$output .= "<td>".$user->user_login."<br /><font class='smallfont'>".$user->user_email."</font></td>";
					$output .= "<td width=50><a href='$approve_btn' target=_blank>".__("Approve", TEBRAVO_TRANS)."</a></td></tr>";
				}
				
				$output .= '</table></div>';
			} else { $output = __("Nothing!", TEBRAVO_TRANS);}
			
			return $output;
			
		}
		protected function last_login_details($user=false)
		{
			if( !$user )
			{
				$user = wp_get_current_user();
			}
			
			if( $user->ID > 0)
			{
				$last_login_details = get_user_meta( $user->ID, TEBRAVO_DBPREFIX.'last_login' ,true);
				
				$last_login = json_decode($last_login_details, true);
				
				if( null==$last_login || empty($last_login) ){
					$last_login = array();
					$last_login['ip'] = tebravo_agent::user_ip();
					$last_login['country'] = tebravo_agent::ip2country();
					$last_login['time'] = time();
					$last_login['device'] = tebravo_agent::device();
					$browser = tebravo_agent::getBrowser();
					$last_login['browser'] = $browser['name'];
				}
				$flag = '<img src="'.plugins_url('assets/img/blank.png', TEBRAVO_PATH).'" class="tebravo_flag flag-'.strtolower($last_login['country']).'" alt="'.$last_login['country'].'" /> ';
				$time = '';
				if( isset($last_login['time']) && !empty( $last_login['time'] ))
				{
					$time = tebravo_ago($last_login['time'])." ".__("ago", TEBRAVO_TRANS);
				}
				$output = "<table border=0 width=100% cellspacing=0>";
				$output .= "<tr class='tebravo_headTD'><td><strong>".__("Last Login Details", TEBRAVO_TRANS)."</strong></td></tr>";
				$output .= "<tr class='tebravo_underTD'><td>".$flag." ".$last_login['ip']."</td></tr>";
				$output .= "<tr class='tebravo_underTD'><td>".(isset($last_login['device'])?$last_login['device']:'--').", ".(isset($last_login['browser'])?$last_login['browser']:'--')."</td></tr>";
				$output .= "<tr class='tebravo_underTD'><td>".$time."</td></tr>";
				
				$output .= "</table>";
				
				return $output;
			}
		}
		public function system_info()
		{
			global $wpdb;
			$output[] = "<table border=0 width=100% cellspacing=0 style='text-align:left'>";
			//Web Server
			$output[] = "<tr class='tebravo_underTD'><td width=60%>".__("Web Server", TEBRAVO_TRANS)."</td>";
			$output[] = "<td style='max-width:50px;'>".tebravo_phpsettings::web_server()."</td></tr>";
			//PHP V
			$output[] = "<tr class='tebravo_underTD'><td width=60%>".__("PHP Version", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".phpversion()."</td></tr>";
			//Memory Usage
			$output[] = "<tr class='tebravo_underTD'><td width=60%>".__("Memory Usage", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".tebravo_ConvertBytes(memory_get_usage())."</td></tr>";
			//Memory Limit
			$output[] = "<tr class='tebravo_underTD'><td width=60%>".__("Memory Usage Limit", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".ini_get('memory_limit')."</td></tr>";
			//Max Upload Size
			$output[] = "<tr class='tebravo_underTD'><td width=60%>".__("Max Upload Size", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".ini_get('upload_max_filesize')."</td></tr>";
			//Max Post Size
			$output[] = "<tr class='tebravo_underTD'><td width=60%>".__("Max Post Size", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".ini_get('post_max_size')."</td></tr>";
			//Max Execution Time
			$output[] = "<tr class='tebravo_underTD'><td width=60%>".__("Max Execution Time", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".ceil(ini_get('max_execution_time')/60)." ".__("Seconds", TEBRAVO_TRANS)."</td></tr>";
			//Allow URL fopen
			$url_fopen = (ini_get('allow_url_fopen')==1)?__('On', TEBRAVO_TRANS):__('Off', TEBRAVO_TRANS);
			$output[] = "<tr class='tebravo_underTD'><td width=60%>".__("Allow URL fopen ", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".$url_fopen."</td></tr>";
			
			$output[] = "</table>";
			return implode("\n", $output);
		}
		
		public function last_firewall_blocked( $limit=5 )
		{
			$results = tebravo_dashboard::last_blocked( $limit );
			
			$output = "<font color=green><i>".__("Everything is just fine", TEBRAVO_TRANS)."</i></font>";
			if( null!=$results )
			{
				$output = "<div  style='max-height:100px; overflow-y:scroll;'><table border=0 width=100% cellspacing=0>";
				
				foreach ( $results as $row )
				{
					if( filter_var($row->ipaddress, FILTER_VALIDATE_IP ) ):
					$country_code = $row->country_code;
					if( empty( $country_code ) )
					{
						$country_code = tebravo_agent::ip2country( $row->ipaddress );
					}
					$date = '';
					if( !empty($row->time_blocked) )
					{
						$date = tebravo_ago( $row->time_blocked )." ".__("ago", TEBRAVO_TRANS);
					}
					$flag = '<img src="'.plugins_url('assets/img/blank.png', TEBRAVO_PATH).'" class="tebravo_flag flag-'.strtolower($country_code).'" alt="'.$country_code.'" /> ';
					$output .= "<tr class='tebravo_underTD'><td width=60%>".$flag.$row->ipaddress."<br />";
					$output .= "<font class='smallfont'>".$row->block_reason."</font></td>";
					$output .= "<td>".$date."</td></tr>";
					endif;
				}
				$output .= "</table></div>";
			}
			
			return $output;
		}
		
		public function disk_space()
		{
			$total = tebravo_dashboard::disk_space('total');
			$free = tebravo_dashboard::disk_space('free');
			$used = tebravo_dashboard::disk_space('used');
			$color = '#F3F3F3';
			$width= '0px';
			if( $total > 0 && $total != '' )
			{
				$percent = ceil( ($used / $total) * 100 );
				if( $percent < 40){$color = '#4DAA15';}
				else if( $percent > 40 && $percent < 80 ){$color = '#EB6819';}
				else if( $percent > 90 ){$color = '#C81919';}
				$output[] = "<table border=0 width=100% cellspacing=0 style='text-align:left'>";
				$output[] = "<tr class=''><td colspan=2>".$used." / ".$total;
				$output[] = "<div style='height:4px; border:solid 1px #CDCDCD; background:#F3F3F3; width:100%'>";
				$output[] = "<div style='background:$color; width:$percent%; height:4px;'></div>";
				$output[] = "</div>";
				$output[] = __("USED", TEBRAVO_TRANS).": ".$percent."%</td></tr>";
				$output[] = "</table>";
				
				return implode("\n", $output);
			} else {
				return '';
			}
		}
		
		public function defense_status()
		{
			$output[] = "<table border=0 width=100% cellspacing=0 style='text-align:left'>";
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Your Defense Meter", TEBRAVO_TRANS).": ";
			$output[] = tebravo_dashboard::security_percentage()."%</strong></td></tr>";
			//firewall
			$output[] = "<tr class='tebravo_underTD'><td width=65%>".__("Firewall", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".tebravo_dashboard::get_status('firewall', 'status')."</td>";
			
			//bruteforce
			$output[] = "<tr class='tebravo_underTD'><td width=65%>".__("Brute Force Protection", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".tebravo_dashboard::get_status('bruteforce', 'status')."</td>";
			
			//bruteforce PWD
			$output[] = "<tr class='tebravo_underTD'><td width=65%>".__("Enforce Strong Passwords", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".tebravo_dashboard::get_status('bruteforce_passwd', 'status')."</td>";
			
			//dbprefix
			$output[] = "<tr class='tebravo_underTD'><td width=65%>".__("DB Prefix", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".tebravo_dashboard::get_status('dbprefix', 'status')."</td>";
			
			//file_editor
			$output[] = "<tr class='tebravo_underTD'><td width=65%>".__("Disable File Editing", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".tebravo_dashboard::get_status('file_editor', 'status')."</td>";
			
			//config_perma
			$output[] = "<tr class='tebravo_underTD'><td width=65%>".__("CONFIG read-only Permissions", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".tebravo_dashboard::get_status('config_perma', 'status')."</td>";
			
			//wp_debug
			$output[] = "<tr class='tebravo_underTD'><td width=65%>".__("Disable WP_DEBUG", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".tebravo_dashboard::get_status('wp_debug', 'status')."</td>";
			
			//wp_login
			$output[] = "<tr class='tebravo_underTD'><td width=65%>".__("Hide wp-login", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".tebravo_dashboard::get_status('wp_login', 'status')."</td>";
			
			//wp_admin
			$output[] = "<tr class='tebravo_underTD'><td width=65%>".__("Hide wp-admin", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".tebravo_dashboard::get_status('wp_admin', 'status')."</td>";
			
			//wp_admin proxy check
			$output[] = "<tr class='tebravo_underTD'><td width=65%>".__("Disable Proxy for wp-admin", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".tebravo_dashboard::get_status('wp_admin_proxy', 'status')."</td>";
			
			//idle
			$output[] = "<tr class='tebravo_underTD'><td width=65%>".__("Log-out Idle Users", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".tebravo_dashboard::get_status('idle', 'status')."</td>";
			
			//two_step
			$output[] = "<tr class='tebravo_underTD'><td width=65%>".__("Enable Two Steps Login", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".tebravo_dashboard::get_status('two_step', 'status')."</td>";
			
			//error_pages
			$output[] = "<tr class='tebravo_underTD'><td width=65%>".__("Enable Error Pages", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".tebravo_dashboard::get_status('error_pages', 'status')."</td>";
			
			//wp_version
			$output[] = "<tr class='tebravo_underTD'><td width=65%>".__("Hide WP Version", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".tebravo_dashboard::get_status('wp_version', 'status')."</td>";
			
			//hotlinking
			$output[] = "<tr class='tebravo_underTD'><td width=65%>".__("Prevent Image Hotlinking", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".tebravo_dashboard::get_status('hotlinking', 'status')."</td>";
			
			//iframes
			$output[] = "<tr class='tebravo_underTD'><td width=65%>".__("Prevent iFrame Traffic", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".tebravo_dashboard::get_status('iframes', 'status')."</td>";
			
			$output[] = "</table>";
			
			return implode("\n", $output);
		}
		//print percentage cricle
		public function circle( $percent, $size=50, $shape='big' )
		{
			
			$output = '<div class="c100 p'.(int)$size.' '.$shape.' center">
            <span>'.(int)$percent.'%</span>
            <div class="slice">
            <div class="bar"></div>
            <div class="fill"></div>
            </div>
            </div>';
			
			if( $percent > 95 )
			{
				$output .= "<br /><center><img src='".plugins_url('/assets/img/clap.png', TEBRAVO_PATH)."'>";
				$output .= "<h3 style='color:green'>BRAVO</h3></center>";
			}
			return $output;
		}
		
		public function box($title, $content, $more=false, $more_id=false)
		{
			
			$output[] = "<div class='tebravo_box'><div class='tebravo_title'>";
			$output[] = "<table border=0 width=100% cellspacing=0>";
			$output[] = "<tr><td>".$title."</td>";
			if( $more && $more_id )
			{
				$output[] = "<td width=25><div class='more' id='$more_id'>".__("More", TEBRAVO_TRANS)."</div></td>";
			}
			$output[] = "</tr></table>";
			$output[] = "</div><div class='tebravo_content'>";
			$output[] = ( $content );
			$output[] = "</div></div><div class='tebravo_clear'></div>";
			
			return implode("\n", $output);
		}
	}
}
?>