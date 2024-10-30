<?php
/**
 * HOOK: WADMIN
 * @since 1.0
 * Copyrights (C) 2017 Technoyer Solutions Ltd. <support@technoyer.com>
 */

//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !class_exists( 'tebravo_wpadmin' ) )
{
	class tebravo_wpadmin{
		
		private 
			$wplogin_slug="login",
			$wpadmin_slug="myadmin",
			$wpregister_slug="register"
			;
		
		public $hide_wplogin;
		public $hide_wpadmin;
		public $auth_cookie_expire;
		public $html;
		public $userid;
		public $idle;
		public $idle_duration;
		public $twofa;
		public $twofa_default;
		public $block_proxy;
		
		//constructor
		public function __construct()
		{
			$this->wplogin_slug= trim(esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'wplogin_slug' ) ) );
			$this->wpadmin_slug= trim(esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'wpadmin_slug' ) ) );
			$this->wpregister_slug= trim(esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'wpregister_slug' ) ) );
			$this->hide_wplogin = trim(esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'hide_wplogin' ) ) );
			$this->hide_wpadmin= trim(esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'hide_wpadmin' ) ) );
			$this->idle= trim(esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'idle_logout' ) ) );
			$this->idle_duration= trim(esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'idle_logout_duration' ) ) );
			$this->twofa= trim(esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'two_step_login' ) ) );
			$this->twofa_default= trim(esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'two_step_login_default' ) ) );
			$this->block_proxy= trim(esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'wpadmin_block_proxy' ) ) );
			
			//activate options
			if( $this->hide_wplogin == 'checked' )
			{
				//set auth cookie expire
				$this->auth_cookie_expire = false;
				
				//add actions
				$this->add_actions_wplogin();
				
				//add filters
				$this->add_filters();
				
				//clean redirect_to // remove actions
				$this->clean_redirect_to();
				
				if( function_exists('is_multisite') && is_multisite() )
				{
					add_action( 'init', array( $this, 'pass_sites_in_mu'), 999);
				}
			}
			
			if( $this->hide_wpadmin == 'checked' )
			{
				//add actions
				$this->add_actions_wpadmin();
			}
			
			//init
			add_action( 'init' , array ($this , 'init' ) );
			
			if($this->twofa == 'checked')
			{
				add_action( 'admin_init' , array ($this , 'new_form_profile' ) );
			}
			
			if($this->block_proxy == 'checked')
			{
				add_action( 'admin_init' , array ($this , 'block_from_proxy' ) );
			}
			
			add_action( 'admin_init' , array ($this , 'ips_countries_white_list' ) );
			add_action( 'admin_init' , array ($this , 'setcookie_login' ) );
		}
		
		//add in init the WP core functions we need
		public function init()
		{
			//get current user details
			$user = wp_get_current_user();
			$this->userid = $user->ID;
			
			//enqueue idle timer js
			if( $this->userid > 0 && $this->idle == 'checked')
			{
				wp_enqueue_script (TEBRAVO_SLUG."_idle_timer_js", plugins_url(TEBRAVO_SLUG.'/assets/js/idle-timer.min.js'), '', '', true);
				wp_enqueue_script (TEBRAVO_SLUG."_bravo_js", plugins_url(TEBRAVO_SLUG.'/assets/js/bravo.js'), '', '', true);
				
				if(is_admin()){$actions = '1';} else {$actions = '2';}
				
				wp_localize_script( TEBRAVO_SLUG."_bravo_js",'bravo', array(
						'ajaxurl' => admin_url('admin-ajax.php') ,
						'bravo_idle_actions' => $actions,
						'bravo_idle_timer' => floor( $this->idle_duration * 1000 ),
				)
						);
				add_action('wp_footer', array( $this,'wp_footer_log_message' ) );
					
			}
			
			include TEBRAVO_DIR.'/hooks/2fa_actions/tebravo.login.php';
			do_action('bravo_wpadmin_init');
			
		}
		
		public function wp_footer_log_message()
		{
			$message = "Logging out ...";
			tebravo_darken_bg($message, 10, true);
		}
		
		//block user if useing proxy
		public function block_from_proxy()
		{
			if(tebravo_agent::is_proxy() == true && is_admin())
			{
				header("HTTP/1.1 403 Forbidden");
				define('ERROR_LOG_MSG', 'Blocked country or IP.');
				//include TEBRAVO_DIR.'/includes/error_pages/tebravo_404.php';
				$errorpages = new tebravo_errorpages();
				if( !defined( 'tebravo_error_template' ) ){ define( 'tebravo_error_template', '403' );}
				echo $errorpages->template(403, 'Forbidden');
				exit;
			}
		}
		
		//allow to while list IPs and countries only if enabled
		public function ips_countries_white_list()
		{
			
			$user_ip = tebravo_agent::user_ip().",";
			$user_country = tebravo_agent::ip2country().",";
			
			$wl_countries = trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'wpadmin_wl_countries')));
			$wl_ips = trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'wpadmin_wl_ips')));
			
			$count_ = 0;
			if(!empty($wl_countries))
			{
				$exp_c = explode(',', $wl_countries);
				$exp_c_u= explode(',', $user_country);
				
				$countries_result = array_intersect($exp_c, $exp_c_u);
				
				if( count($countries_result) == 0)
				{
					$count_ = 1;
					if( !defined( 'ERROR_LOG_MSG' ) )
					{
						define('ERROR_LOG_MSG' , 'No in countries white list.');
					}
				}
			}
			
			if(!empty($wl_ips))
			{
				$exp_i = explode(',', $wl_ips);
				$exp_i_u = explode(',', $user_ip);
				
				$ips_result = array_intersect($exp_i, $exp_i_u);
				
				if( count($ips_result) == 0 )
				{
					$count_ = 2;
					if( !defined( 'ERROR_LOG_MSG' ) )
					{
						define('ERROR_LOG_MSG' , 'No in IPs white list.');
					}
				}
			}
			
			if( +$count_ > 0)
			{
				header("HTTP/1.1 403 Forbidden");
				if( !defined( 'ERROR_LOG_MSG' ) )
				{
					define('ERROR_LOG_MSG', 'Blocked country or IP.');
				}
				//include TEBRAVO_DIR.'/includes/error_pages/tebravo_404.php';
				$errorpages = new tebravo_errorpages();
				if( !defined( 'tebravo_error_template' ) ){ define( 'tebravo_error_template', 403 );}
				echo $errorpages->template(403, 'Forbidden');
				exit;
			}
		}
		//js
		public function js_idle()
		{
			$output[] = PHP_EOL.'<div id="tebravo_results"></div>';
			
			echo implode(PHP_EOL, $output);
		}
		
		//clear auth cookie //todo
		public function clear_auth_cookie()
		{
			$this->auth_cookie_expire = true;
			tebravo_clear_wp_cookie();
		}
		
		//add actions
		public function add_actions_wplogin()
		{
			add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 11 );
			add_action( 'login_init', array( $this, 'prevent_old_wplogin' ) );
		}
		
		//add actions
		public function add_actions_wpadmin()
		{
			add_action( 'init', array( $this, 'prevent_old_wpadmin' ), 1000 );
			add_filter( 'logout_url', array( $this, 'logout_url' ), 10, 2 );
		}
		
		//add filters
		public function add_filters()
		{
			add_filter( 'loginout' ,  array( $this , 'wplogin_url_filter' ), 10, 3);
			add_filter( 'site_url' ,  array( $this , 'wplogin_url_filter' ), 10, 3);
			add_filter( 'wp_redirect' , array( $this, 'wplogin_url_filter' ), 10, 2 );
			add_filter( 'lostpassword_url', array( $this, 'wplogin_url_filter' ), 10, 2 );
			add_filter( 'retrieve_password_message', array( $this, 'replace_in_messages' ) );
			add_filter( 'comment_moderation_text', array( $this, 'comment_moderation_text' ) );
		}
		
		public function logout_url( $logout_url, $redirect_to ) {
			
			if( false!==strpos($redirect_to, 'wp-admin')
					|| (is_user_logged_in() && is_admin()))
			{
				$logout_url = add_query_arg(
						array(
								'hash' => tebravo_utility::get_securityhash(),
								'pass_via' => TEBRAVO_SLUG,
								'redirect_to' => $redirect_to
						), $logout_url);
			}
			
			return $logout_url;
		}
		
		public function comment_moderation_text( $notify_message ) {
			
			preg_match_all( "#(https?:\/\/((.*)wp-admin(.*)))#", $notify_message, $urls );
			
			if ( isset( $urls ) && is_array( $urls ) && isset( $urls[0] ) ) {
				
				foreach ( $urls[0] as $url ) {
					
					$new_url = add_query_arg( 
							array(
							"hash" => tebravo_utility::get_securityhash(),		
							"pass_via" => TEBRAVO_SLUG,		
							), $url);
					$notify_message = str_replace( trim( $url ), $new_url , $notify_message );
					
				}
				
			}
			
			return $notify_message;
			
		}
		
		//prevent old login url wp-login.php
		public function prevent_old_wplogin()
		{
			if( strpos( tebravo_selfURL() , 'wp-login.php' ) )
			{
				$this->force_disable_php_errors();
				header("HTTP/1.0 404 Not Found");
				define('ERROR_LOG_MSG', 'Try to log in from wp-login.php.');
				//include TEBRAVO_DIR.'/includes/error_pages/tebravo_404.php';
				$errorpages = new tebravo_errorpages();
				if( !defined( 'tebravo_error_template' ) ){ define( 'tebravo_error_template', 404 );}
				echo $errorpages->template();
				//404
				exit;
			}
		}
		//
		public function pass_sites_in_mu()
		{
			$url = tebravo_selfURL();
			if( isset($_REQUEST['hash'])
					&& isset( $_REQUEST['pass_via'])
					&& $_REQUEST['hash'] == tebravo_utility::get_securityhash()
					&& $_REQUEST['pass_via'] == TEBRAVO_SLUG
					&& false!==strpos($url, 'wp-admin')
					&& !is_user_logged_in()
					)
			{
				
				if( function_exists('is_multisite') && is_multisite() )
				{
					
					$blog_id = 1;
					if( defined( 'BLOG_ID_CURRENT_SITE' ) )
					{
						$blog_id = BLOG_ID_CURRENT_SITE;
					}
					$current_siteurl = get_blog_details(get_current_blog_id());
					$main_siteurl = get_blog_details($blog_id);
					
					if( $current_siteurl != $main_siteurl )
					{
						$new_url = str_replace($current_siteurl->siteurl, $main_siteurl->siteurl, $url);
						
						if( $this->hide_wplogin == 'checked' && $this->wplogin_slug!='' )
						{
							$exp_url = explode("wp-admin", $url);
							$redirect_to = str_replace("http://http://", "http://", $url);
							$redirect_to = str_replace("https://https://", "https://", $redirect_to);
							$redirect_to = ( urlencode($redirect_to));
							$new_url = add_query_arg(
									array(
											'hash' => tebravo_utility::get_securityhash(),
											'pass_via' =>TEBRAVO_SLUG,
											'redirect_to' =>$redirect_to,
									)
									,$main_siteurl->siteurl.'/'.$this->wplogin_slug);
						}
						
						tebravo_redirect_js( $new_url, true);
						exit;
					}
					
				}
				
				return;
			}
		}
		//prevent old login url wp-admin
		public function prevent_old_wpadmin()
		{
			if( $this->hide_wpadmin == 'checked'){
				$url = tebravo_selfURL();
				//check variables
				if( isset($_REQUEST['hash'])
						&& isset( $_REQUEST['pass_via'])
						&& $_REQUEST['hash'] == tebravo_utility::get_securityhash()
						&& $_REQUEST['pass_via'] == TEBRAVO_SLUG
						&& false!==strpos($url, 'wp-admin')
						&& !is_user_logged_in()
						)
				{
					return;
				}
				//continue lockout
				if( strpos( tebravo_selfURL() , 'redirect_to' ) )
				{
					$exp = explode('redirect_to', tebravo_selfURL());
					
					
					if( strpos( $exp[0] , 'wp-admin' ) )
					{
						if(!is_user_logged_in()){
							define('ERROR_LOG_MSG', 'Try to reach wp-admin while he is just a guest.');
							$errorpages = new tebravo_errorpages();
							if( !defined( 'tebravo_error_template' ) ){ define( 'tebravo_error_template', 404 );}
							echo $errorpages->template();
							//404
							exit;
						}
					} else {
						return;
					}
				}
				else
				if( strpos( tebravo_selfURL() , 'wp-admin' ) )
				{
				//	if(false === strpos( tebravo_selfURL() , admin_url('admin-ajax.php') ) )
					if( ! isset($_COOKIE['tebravo_idle_detected'])){
						
						if(!is_user_logged_in()){
							
							#$this->force_disable_php_errors();
							header("HTTP/1.0 404 Not Found");
							define('ERROR_LOG_MSG', 'Try to log in from wp-login.php.');
							//include TEBRAVO_DIR.'/includes/error_pages/tebravo_404.php';
							$errorpages = new tebravo_errorpages();
							if( !defined( 'tebravo_error_template' ) ){ define( 'tebravo_error_template', 404 );}
							echo $errorpages->template();
							
							//404
							exit;
						}
					}
					
				}
			}
		}
		
		//logout actions for plugins loaded
		public function plugins_loaded()
		{
			if(
					is_user_logged_in() &&
					isset( $_GET['action'] ) &&
					sanitize_text_field( $_GET['action'] ) == 'logout')
			{
				check_admin_referer('log-out');
				wp_logout();
				
				//set value to redirect_to
				$default_redirect_to = "wp-login.php?loggedout=true";
				if( empty( $_REQUEST['redirect_to'] ) )
				{
					$redirect_to = $default_redirect_to;
				} else {
					$redirect_to = sanitize_text_field( $_REQUEST['redirect_to'] );
				}
				
				tebravo_redirect_js( $redirect_to , true );
				exit;
			}
		}
		
		//new login url
		public function wplogin_url_filter( $url )
		{
			$old  = array( "/(wp-login\.php)/");
			$new  = array( $this->wplogin_slug );
			
			return preg_replace( $old, $new, $url, 1);
			
			//return add_query_arg( array('hash'=>tebravo_utility::get_securityhash(), 'pass_via'=>TEBRAVO_SLUG), $url);
		}
		
		//new login url
		public function wplogout( $url )
		{
			$old  = array( "/(wp-login\.php)/");
			$new  = array( $this->wplogin_slug );
			
			$new_url = preg_replace( $old, $new, $url, 1);
			
			return add_query_arg( array('hash'=>tebravo_utility::get_securityhash(), 'pass_via'=>TEBRAVO_SLUG), $new_url);
		}
		
		//replace old login from messages
		public function replace_in_messages( $message )
		{
			return str_replace('wp-login.php', $this->wplogin_slug, $message);
		}
		
		//prevent redirect filters and actions from executing
		public function clean_redirect_to()
		{
			//domain mapping
			if( defined( 'DOMAIN_MAPPING' ) && DOMAIN_MAPPING == 1)
			{
				remove_action( 'login_head' , 'redirect_login_to_orig');
			}
			
			//remove wordpress actions
			remove_action( 'template_redirect', 'wp_redirect_admin_locations', 1000 );
			
		}
		
		//force disable php errors
		public function force_disable_php_errors()
		{
			error_reporting( 0 );
			@ini_set( 'display_errors', 0 );
		}
		
		//HTML // Dashboard
		public function dashboard()
		{
			$this->html = new tebravo_html();
			
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			if( empty($_GET['p']) )
			{
				$desc = __("The most important section, every admin must sure that his Wordpress admin area is secure.", TEBRAVO_TRANS);
				$this->html->header(__("WPAdmin Security", TEBRAVO_TRANS), $desc, "security.png", false);
				
				if($_POST)
				{
					//check capability
					if(! current_user_can('manage_options')){wp_die(__("Wrong Access", TEBRAVO_TRANS)); exit;}
					
					if(empty($_POST['_nonce']) || false == wp_verify_nonce($_POST['_nonce'], $this->html->init->security_hash.'wpadmin-security'))
					{
						tebravo_redirect_js($this->html->init->admin_url.'-wadmin&err=02');
						exit;
					}
					
					
					echo __("Loading", TEBRAVO_TRANS)."...";
					tebravo_redirect_js($this->html->init->admin_url.'-wadmin&msg=01');
					exit;
				}
				
				//Tabs Data
				$tabs["general"] = array("title"=>"Options",
						"href"=>$this->html->init->admin_url."-settings",
						"is_active"=> "");
				
				$tabs["wpconfig"] = array("title"=>"WP Config",
						"href"=>$this->html->init->admin_url."-wconfig",
						"is_active"=> 'not');
				
				$tabs["wpadmin"] = array("title"=>"WP Admin",
						"href"=>$this->html->init->admin_url."-wadmin",
						"is_active"=> 'active');
				
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
				$this->html->tabs($tabs);
				$this->html->start_tab_content();
				
				$output[] = "<form action='".$this->html->init->admin_url."-wadmin' method=post>";
				$output[] = "<input type='hidden' name='_nonce' value='".$this->html->init->create_nonce('wpadmin-security')."'>";
				$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
				$output[] = "<table border='0' width=100% cellspacing=0>";
				
				//hide wp-login
				if(( esc_html( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'protect_plugin') ) ) == 'checked'){
					$protect_plugin_status = "<font color=green>".__("Enabled", TEBRAVO_TRANS)."</font>";
				} else {
					$protect_plugin_status = "<font color=red>".__("Disabled", TEBRAVO_TRANS)."</font>";
				}
				$protect_plugin_desc = __("This is a perfect tool to set a password for this plugin and you can choose some administrators only to give them the ability to manage BRAVO plugin.", TEBRAVO_TRANS);
				$protect_plugin_desc .= "<hr>".__("Current Status:", TEBRAVO_TRANS)." ".$protect_plugin_status;
				$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Protect Plugin", TEBRAVO_TRANS)."</strong>  <span class='tebravo_trail'>".__("Premium", TEBRAVO_TRANS)."</span></td></tr>";
				$output[] = "<tr class='tebravo_underTD'><td width=80%>".$protect_plugin_desc."</td>";
				$output[] = "<td>".$this->html->button_small_info(__("Open Wizard", TEBRAVO_TRANS), 'button', 'protect-plugin-btn')."</td>";
				$output[] = "</tr>";
				
				//hide wp-login
				if(( esc_html( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'hide_wplogin') ) ) == 'checked'){
					$hide_wplogin_status = "<font color=green>".__("Enabled", TEBRAVO_TRANS)."</font>";
				} else {
					$hide_wplogin_status = "<font color=red>".__("Disabled", TEBRAVO_TRANS)."</font>";
				}
				$hide_wplogin_desc = __("Hiding the wp-login.php is the most powerful trick to make hackers confused, they cannot reach your real login path to the admin area or member area if they are using the random attack.", TEBRAVO_TRANS);
				$hide_wplogin_desc .= "<hr>".__("Current Status:", TEBRAVO_TRANS)." ".$hide_wplogin_status;
				$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Hide wp-login.php", TEBRAVO_TRANS)."</strong></td></tr>";
				$output[] = "<tr class='tebravo_underTD'><td width=80%>".$hide_wplogin_desc."</td>";
				$output[] = "<td>".$this->html->button_small_info(__("Open Wizard", TEBRAVO_TRANS), 'button', 'hide-wplogin-btn')."</td>";
				$output[] = "</tr>";
				
				//hide wp-admin
				if(( esc_html( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'hide_wpadmin') ) ) == 'checked'){
					$hide_wpadmin_status = "<font color=green>".__("Enabled", TEBRAVO_TRANS)."</font>";
				} else {
					$hide_wpadmin_status= "<font color=red>".__("Disabled", TEBRAVO_TRANS)."</font>";
				}
				$hide_wpadmin_desc = __("The admin area is the most dangerous area, so you must make it in the best security level. If you change its name from WP-admin to anything, that means all guests will be denied to reach it, only logged in users who can reach it after using the hidden WP-login new path.", TEBRAVO_TRANS);
				$hide_wpadmin_desc.= "<hr>".__("Current Status:", TEBRAVO_TRANS)." ".$hide_wpadmin_status;
				$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Hide wp-admin", TEBRAVO_TRANS)."</strong> <span class='tebravo_trail'>".__("Premium", TEBRAVO_TRANS)."</span></td></tr>";
				$output[] = "<tr class='tebravo_underTD'><td width=80%>".$hide_wpadmin_desc."</td>";
				$output[] = "<td>".$this->html->button_small_info(__("Open Wizard", TEBRAVO_TRANS), 'button', 'hide--btn')."</td>";
				$output[] = "</tr>";
				
				//idle users
				if(( esc_html( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'idle_logout') ) ) == 'checked'){
					$hide_idle_status = "<font color=green>".__("Enabled", TEBRAVO_TRANS)."</font>";
				} else {
					$hide_idle_status= "<font color=red>".__("Disabled", TEBRAVO_TRANS)."</font>";
				}
				$hide_idle_desc = __("The plugin will clear the current sessions for logged in users if they hold their accounts without using after (n) seconds, you will choose the duration before forcing them to log in again.", TEBRAVO_TRANS);
				$hide_idle_desc.= "<hr>".__("Current Status:", TEBRAVO_TRANS)." ".$hide_idle_status;
				$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Automatically log out the idle users/admins", TEBRAVO_TRANS)."</strong></td></tr>";
				$output[] = "<tr class='tebravo_underTD'><td width=80%>".$hide_idle_desc."</td>";
				$output[] = "<td>".$this->html->button_small_info(__("Open Wizard", TEBRAVO_TRANS), 'button', 'idle-btn')."</td>";
				$output[] = "</tr>";
				
				//2-Step Verification
				if(( esc_html( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'two_step_login') ) ) == 'checked'){
					$hide_twostep_status = "<font color=green>".__("Enabled", TEBRAVO_TRANS)."</font>";
				} else {
					$hide_twostep_status= "<font color=red>".__("Disabled", TEBRAVO_TRANS)."</font>";
				}
				$hide_twostep_desc = __("You are able to choose from many options when you decide to enable 2-Step Verification.", TEBRAVO_TRANS);
				$hide_twostep_desc .= "<br />".__("<u>Available options:</u> Two factor authentication, Facebook Verification, Four numbers pin code and Security question.", TEBRAVO_TRANS);
				$hide_twostep_desc.= "<hr>".__("Current Status:", TEBRAVO_TRANS)." ".$hide_twostep_status;
				$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("2-Step Verification", TEBRAVO_TRANS)."</strong></td></tr>";
				$output[] = "<tr class='tebravo_underTD'><td width=80%>".$hide_twostep_desc."</td>";
				$output[] = "<td>".$this->html->button_small_info(__("Open Wizard", TEBRAVO_TRANS), 'button', 'twostep-btn')."</td>";
				$output[] = "</tr>";
				
				//Access Options
				if(( esc_html( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'wpadmin_block_proxy') ) ) == 'checked'){
					$hide_blockproxy_status = "checked"; $hide_blockproxy_status_no = "";
				} else {
					$hide_blockproxy_status= ""; $hide_blockproxy_status_no = "checked";
				}
				$hide_blockproxy_help = __("Check if current logged user IP is real IP or proxy, If proxy it will be rejected.", TEBRAVO_TRANS);
				//proxy
				$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Access Options", TEBRAVO_TRANS)."</strong>  <span class='tebravo_trail'>".__("Premium", TEBRAVO_TRANS)."</span></td></tr>";
				$output[] = "<tr class='tebravo_underTD'><td width=80%>".__("Block access from proxy", TEBRAVO_TRANS)." ".$this->html->open_window_help('block-proxy',$hide_blockproxy_help);
				$output[] = "<br /><font color=brown>".__("WP-ADMIN may be become slow", TEBRAVO_TRANS)."</font></td>";
				$output[] = "<td>";
				$output[] = "<input type='radio' name='".TEBRAVO_DBPREFIX."wpadmin_block_proxy' value='checked' id='wpadmin_block_proxy' dsabled><label for='wpadmin_block_proxy'><span></span>".__("Yes", TEBRAVO_TRANS)."</label> &nbsp;";
				$output[] = "<input type='radio' name='".TEBRAVO_DBPREFIX."wpadmin_block_proxy' value='no' id='wpadmin_block_proxy_no' disabled><label for='wpadmin_block_proxy_no'><span></span>".__("No", TEBRAVO_TRANS)."</label>";
				$output[] = "</td>";
				$output[] = "</tr>";
				//white list countries
				$allowed_countries_help = __("All countries will be denied from access to wp-admin except those countries you choose to put it in allowed or white list.", TEBRAVO_TRANS);
				$output[] = "<tr class='tebravo_underTD'><td colspan=2>".__("Allowed countries", TEBRAVO_TRANS)." ".$this->html->open_window_help('allowed-countires',$allowed_countries_help);
				$output[] = "<br /><font class='smallfont' style='color:blue'>".__("Be Careful", TEBRAVO_TRANS)."</font> <br />";
				$output[] = "<textarea name='".TEBRAVO_DBPREFIX.'wpadmin_wl_countries'."' style='width:350px; height:65px;' disabled>";
				$output[] = "</textarea><br />";
				$output[] = "<font class='smallfont'>".__("Leave it blank to disable this option.", TEBRAVO_TRANS)."<br />";
				$output[] = "<b>IMPORTANT</b>: Input will be like this: US,UK,FR <font>";
				$output[] = "</td>";
				$output[] = "</tr>";
				//white list IPs
				$allowed_ips_help = __("All IP addresses will be denied from access to wp-admin except those IPs you choose to put it in allowed or white list.", TEBRAVO_TRANS);
				$output[] = "<tr class='tebravo_underTD'><td colspan=2>".__("Allowed IPs", TEBRAVO_TRANS)." ".$this->html->open_window_help('allowed-ips',$allowed_ips_help);
				$output[] = "<br /><font class='smallfont' style='color:blue'>".__("Be Careful", TEBRAVO_TRANS)."</font> <br />";
				$output[] = "<textarea name='".TEBRAVO_DBPREFIX.'wpadmin_wl_ips'."' style='width:350px; height:65px;' disabled>";
				$output[] = "</textarea><br />";
				$output[] = "<font class='smallfont'>".__("Leave it blank to disable this option.", TEBRAVO_TRANS)."<br />";
				$output[] = "<b>IMPORTANT</b>: Input will be like this: 127.0.0.1,10.11.12.13 .. use this symbol , <i>(comma)</i> after IP address if you will add more than one IP. <font>";
				$output[] = "</td>";
				$output[] = "</tr>";
				
				$output[] = "<tr class='tebravo_underTD'><td colspan=2>";
				$output[] = $this->html->button(__("Save Options", TEBRAVO_TRANS), 'submit');
				$output[] = "</td></tr>";
				$output[] = "</table>";
				$output[] = "</div>";
				$output[] = "</form>";
				
				echo implode("\n", $output);
				
				echo '<script>';
				
				
				echo 'jQuery("#hide-wplogin-btn").click(function(){';
				echo 'window.location.href= "'.$this->html->init->admin_url."-wadmin&p=hide-wplogin".'"';
				echo '});';
				
				echo 'jQuery("#hide-wpadmin-btn").click(function(){';
				echo 'window.location.href= "'.$this->html->init->admin_url."-wadmin&p=hide-wpadmin".'"';
				echo '});';
				
				echo 'jQuery("#idle-btn").click(function(){';
				echo 'window.location.href= "'.$this->html->init->admin_url."-wadmin&p=idle".'"';
				echo '});';
				
				echo 'jQuery("#twostep-btn").click(function(){';
				echo 'window.location.href= "'.$this->html->init->admin_url."-wadmin&p=2fa".'"';
				echo '});';
				echo '</script>';
				$this->html->end_tab_content();
				
				$this->html->footer();
			} else if( $_GET['p'] == 'protect-plugin' )
			{
				$this->protect_plugin_dashboard();
			} else if( $_GET['p'] == 'hide-wplogin' )
			{
				$this->hide_wplogin_dashboard();
			}else if( $_GET['p'] == 'hide-wpadmin' )
			{
				$this->hide_wpadmin_dashboard();
			} else if( $_GET['p'] == 'idle' )
			{
				$this->idle_dashboard();
			} else if( $_GET['p'] == '2fa' )
			{
				$this->twofa_dashboard();
			} else if( $_GET['p'] == 'login' )
			{
				$this->login();
			}
			
			
		}
		
		protected function login()
		{
			//posted password
			$pw ='';
			if( !empty( $_POST['pw']))
			{
				$pw = trim( esc_html( $_POST['pw'] ) );
			}
			//redirect URL
			$redirect_to = $this->html->init->admin_url;
			if( !empty( $_POST['url'] ) ){$redirect_to = trim( esc_html( $_POST['url']));}
			
			$current_password = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'password')));
			
			$encoded_pw = tebravo_encodeString($pw, $this->html->init->security_hash);
			
			if( $current_password == $encoded_pw )
			{
				$user = wp_get_current_user();
				$user_id = $user->ID;
				$user_email = $user->user_email;
				$user_login = $user->user_login;
				
				$encoded_str = tebravo_encodeString($user_id.$user_email, $this->html->init->security_hash);
				
				$redirect = add_query_arg( array(
						'hash' => $this->html->init->security_hash,
						'isco' => true
				),$redirect_to);
				tebravo_redirect_js( $redirect );
				exit;
			}
			
			//die
			wp_die(__("Wrong Attempt!", TEBRAVO_TRANS));
		}
		
		//set cookie for plugin session
		public function setcookie_login()
		{
			$helper = new tebravo_html();
			$user = wp_get_current_user();
			$user_id = $user->ID;
			$user_email = $user->user_email;
			$user_login = $user->user_login;
			$Str = $user_id.$user_email;
			$encoded_str = tebravo_encodeString($Str, $helper->init->security_hash);
			if( isset( $_GET['hash']) && isset($_GET['isco']) && $_GET['hash'] = $helper->init->security_hash )
			{
				setcookie('bravo_admin_session', $encoded_str, time()+7200);
				
				$exp_url = explode("hash", tebravo_selfURL());
				$redirect_to = $exp_url[0];
				
				echo __("Loading", TEBRAVO_TRANS)."...";
				tebravo_redirect_js( $redirect_to );
			}
		}
		
		//Hide wp-login.php dashboard //HTML
		protected function hide_wplogin_dashboard()
		{
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			if( !$_POST)
			{
				$desc = __("Hiding the wp-login.php is the most powerful trick to make hackers confused, they cannot reach your real login path to the admin area or member area if they are using the random attack.", TEBRAVO_TRANS);
				$this->html->header(__("WPAdmin Security - Hide WP Login Path", TEBRAVO_TRANS), $desc, "security.png", false);
				
				if( isset($_GET['msg']) )
				{
					if( tebravo_phpsettings::web_server() == 'nginx' )
					{
						$output[] = "<table border=0 width=100% cellspacing=0>";
						$output[] = "<tr style='border:solid 1px #83B5D2; background:#EAF4F9; color:#0E7DBC'><td style='padding:8px;'>";
						$output[] = __("The plugin detects that your server is running on NGINX", TEBRAVO_TRANS)."<br />";
						$output[] = __("Please follow the NGINX guide by click the following link:", TEBRAVO_TRANS)."<br />";
						$output[] = "<a href='".$this->html->init->nginx_docs."' target=blank>".__("NGINX Docs", TEBRAVO_TRANS)."</a>";
						$output[] = "</td></tr>";
						$output[] = "</table>";
					}
				}
				
				$output[] = "<form action='".$this->html->init->admin_url."-wadmin&p=hide-wplogin' method=post>";
				$output[] = "<input type='hidden' name='_nonce' value='".$this->html->init->create_nonce('hide-wp-login')."'>";
				$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
				$output[] = "<table border='0' width=100% cellspacing=0>";
				
				$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Hide wp-login path (wp-login.php)", TEBRAVO_TRANS)."</strong></td></tr>";
				$output[] = "<tr class='tebravo_underTD'><td width=65%>";
				$output[] = __("This will appear 404 page when the old wp-login.php requested", TEBRAVO_TRANS);
				$output[] = "</td>";
				
				if(( esc_html( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'hide_wplogin') ) ) == 'checked'){
					$hide_wplogin_status = "checked"; $hide_wplogin_status_no = "";
				} else {
					$hide_wplogin_status= ""; $hide_wplogin_status_no = "checked";
				}
				//hide wp-login option
				$output[] = "<td><input type='radio' name='".TEBRAVO_DBPREFIX."hide_wplogin' value='checked' id='hide_wplogin' $hide_wplogin_status><label for='hide_wplogin'><span></span>".__("Yes", TEBRAVO_TRANS)."</label> &nbsp;";
				$output[] = "<input type='radio' name='".TEBRAVO_DBPREFIX."hide_wplogin' value='no' id='hide_wplogin_no' $hide_wplogin_status_no><label for='hide_wplogin_no'><span></span>".__("No", TEBRAVO_TRANS)."</label>";
				$output[] = "</td>";
				$output[] = "</tr>";
				//wp-login slug
				$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("New wp-login slug (new path)", TEBRAVO_TRANS)."</strong></td></tr>";
				$output[] = "<tr class='tebravo_underTD'><td colspan=2>";
				$output[] = "<input type='text' pattern='[A-Za-z0-9._%+-]{3,25}' name='".TEBRAVO_DBPREFIX."wplogin_slug' value='".$this->wplogin_slug."'><br />";
				$output[] = "<font class='smallfont'>".__("new path like e.g: mylogin, the_login, new-login or login123<br />(3 to 25) no special characters", TEBRAVO_TRANS)."</font>";
				$output[] = "<hr><strong>".__("Send email now with new link to: ", TEBRAVO_TRANS)."</strong> <font class='smallfont'>".__("(with new login link)", TEBRAVO_TRANS)."</font><br />";
				$output[] = "<input type='checkbox' value='checked' name='Administrator'>".__("Administrator", TEBRAVO_TRANS)."<br />";
				$output[] = "<input type='checkbox' value='checked' name='Authors'>".__("Authors", TEBRAVO_TRANS)."<br />";
				$output[] = "<input type='checkbox' value='checked' name='Editors'>".__("Editors", TEBRAVO_TRANS)."<br />";
				$output[] = "</td></tr>";
				//register slug
				$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("New register slug (new register path)", TEBRAVO_TRANS)."</strong></td></tr>";
				$output[] = "<tr class='tebravo_underTD'><td colspan=2>";
				$output[] = "<input type='text' pattern='[A-Za-z0-9._%+-]{3,25}' name='".TEBRAVO_DBPREFIX."wpregister_slug' value='".$this->wpregister_slug."'><br />";
				$output[] = "<font class='smallfont'>".__("new path like e.g: register, user_register or new-member<br />(3 to 25) no special characters", TEBRAVO_TRANS)."<br>";
				$output[] = "- ".__("New Register Link:", TEBRAVO_TRANS)." ".get_home_url()."/{new_path}";
				$output[] = "</font>";
				$output[] = "</td></tr>";
				
				$output[] = "<tr class='tebravo_underTD'><td colspan=2>";
				$output[] = $this->html->button(__("Save Options", TEBRAVO_TRANS), 'submit');
				$output[] = $this->html->button(__("Back", TEBRAVO_TRANS), "button", "back", false, "arrow_left.png");;
				$output[] = "</td></tr>";
				$output[] = "</table>";
				$output[] = "</div>";
				$output[] = "</form>";
				
				echo implode("\n", $output);
				
				echo '<script>';
				echo 'jQuery("#back").click(function(){';
				echo 'window.location.href= "'.$this->html->init->admin_url."-wadmin".'"';
				echo '});';
				echo '</script>';
				
				$this->html->footer();
			} else {
				//check capability
				if(! current_user_can('manage_options')){wp_die(__("Wrong Access", TEBRAVO_TRANS)); exit;}
				
				$this->hide_wplogin = trim( sanitize_text_field(esc_html($_POST[TEBRAVO_DBPREFIX.'hide_wplogin'])));
				$this->wplogin_slug= trim( sanitize_text_field(esc_html($_POST[TEBRAVO_DBPREFIX.'wplogin_slug'])));
				$this->wpregister_slug= trim( sanitize_text_field(esc_html($_POST[TEBRAVO_DBPREFIX.'wpregister_slug'])));
				
				if(empty($_POST['_nonce']) || false == wp_verify_nonce($_POST['_nonce'], $this->html->init->security_hash.'hide-wp-login'))
				{
					tebravo_redirect_js($this->html->init->admin_url.'-wadmin&p=hide-wplogin&err=02');
					exit;
				}
				
				if(! empty($this->wplogin_slug))
				{
					//update database options
					tebravo_utility::update_option(TEBRAVO_DBPREFIX.'hide_wplogin', $this->hide_wplogin);
					tebravo_utility::update_option(TEBRAVO_DBPREFIX.'wplogin_slug', $this->wplogin_slug);
					tebravo_utility::update_option(TEBRAVO_DBPREFIX.'wpregister_slug', $this->wpregister_slug);
					
					//write htaccess rules
					$this->htaccess_wplogin();
					
					//send emails //Administrator
					if(isset($_POST['Administrator']) && $_POST['Administrator'] == 'checked')
					{
						$admins = tebravo_get_blog_users('Administrator', 'user_email');
						if(is_array($admins)){
							foreach ($admins as $admin_email)
							{
								$this->send_new_wplogin_email( $admin_email );
							}
						}
					}
					//send emails //Authors
					if(isset($_POST['Authors']) && $_POST['Authors'] == 'checked')
					{
						$authors = tebravo_get_blog_users('Author', 'user_email');
						if(is_array($authors)){
							foreach ($authors as $author_email)
							{
								$this->send_new_wplogin_email( $author_email );
							}
						}
					}
					//send emails //Editors
					if(isset($_POST['Editors']) && $_POST['Editors'] == 'checked')
					{
						$editors = tebravo_get_blog_users('Editor', 'user_email');
						if(is_array($editors)){
							foreach ($editors as $editor_email)
							{
								$this->send_new_wplogin_email( $editor_email );
							}
						}
					}
					
					$url_p = '&msg=01';
				} else {
					$url_p = '&err=07';
				}
				echo __("Loading", TEBRAVO_TRANS)."...";
				tebravo_redirect_js($this->html->init->admin_url.'-wadmin&p=hide-wplogin'.$url_p);
			}
		}
		
		//idle users dashboard
		public function idle_dashboard()
		{
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			if( !$_POST)
			{
				$desc = __("The plugin will clear the current sessions for logged in users if they hold their accounts without using after (n) seconds, you will choose the duration before forcing them to log in again.", TEBRAVO_TRANS);
				$this->html->header(__("WPAdmin Security - Idle Users Options", TEBRAVO_TRANS), $desc, "idle.png", false);
				
				$output[] = "<form action='".$this->html->init->admin_url."-wadmin&p=idle' method=post>";
				$output[] = "<input type='hidden' name='_nonce' value='".$this->html->init->create_nonce('idle-users-options')."'>";
				$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
				$output[] = "<table border='0' width=100% cellspacing=0>";
				
				$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Force Idle Users to Log in Again", TEBRAVO_TRANS)."</strong></td></tr>";
				$output[] = "<tr class='tebravo_underTD'><td width=65%>";
				$output[] = __("If the plugin detects an inactive user (logged member), It will be forced to log in again, Agree?", TEBRAVO_TRANS);
				$output[] = "</td>";
				
				if(( esc_html( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'idle_logout') ) ) == 'checked'){
					$idle_logout_status = "checked"; $idle_logout_status_no = "";
				} else {
					$idle_logout_status= ""; $idle_logout_status_no = "checked";
				}
				//enable/disable idle
				$output[] = "<td><input type='radio' name='".TEBRAVO_DBPREFIX."idle_logout' value='checked' id='idle_logout' $idle_logout_status><label for='idle_logout'><span></span>".__("Yes", TEBRAVO_TRANS)."</label> &nbsp;";
				$output[] = "<input type='radio' name='".TEBRAVO_DBPREFIX."idle_logout' value='no' id='idle_logout_no' $idle_logout_status_no><label for='idle_logout_no'><span></span>".__("No", TEBRAVO_TRANS)."</label>";
				$output[] = "</td>";
				$output[] = "</tr>";
				
				//idle duration
				$idle_logout_duration = trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'idle_logout_duration')));
				$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Duration before clear cookies", TEBRAVO_TRANS)."</strong></td></tr>";
				$output[] = "<tr class='tebravo_underTD'><td colspan=2>".__("Set time in seconds", TEBRAVO_TRANS)."<br />";
				$output[] = "<input type='text' style='width:60px;' pattern='[0-9]{1,6}' name='".TEBRAVO_DBPREFIX."idle_logout_duration' value='".$idle_logout_duration."'> Seconds";
				$output[] = "</td></tr>";
				
				$output[] = "<tr class='tebravo_underTD'><td colspan=2>";
				$output[] = $this->html->button(__("Save Options", TEBRAVO_TRANS), 'submit');
				$output[] = $this->html->button(__("Back", TEBRAVO_TRANS), "button", "back", false, "arrow_left.png");;
				$output[] = "</td></tr>";
				$output[] = "</table>";
				$output[] = "</div>";
				$output[] = "</form>";
				
				echo implode("\n", $output);
				
				echo '<script>';
				echo 'jQuery("#back").click(function(){';
				echo 'window.location.href= "'.$this->html->init->admin_url."-wadmin".'"';
				echo '});';
				echo '</script>';
				
				$this->html->footer();
			} else {
				//check permissions
				if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
				
				if(empty($_POST['_nonce']) || false == wp_verify_nonce($_POST['_nonce'], $this->html->init->security_hash.'idle-users-options'))
				{
					tebravo_redirect_js($this->html->init->admin_url.'-wadmin&p=idle&err=02');
					exit;
				} else {
					if(isset($_POST[TEBRAVO_DBPREFIX."idle_logout"])
							&& isset($_POST[TEBRAVO_DBPREFIX.'idle_logout_duration']))
					{
						tebravo_utility::update_option(TEBRAVO_DBPREFIX.'idle_logout', trim(sanitize_text_field(esc_html($_POST[TEBRAVO_DBPREFIX.'idle_logout']))));
						tebravo_utility::update_option(TEBRAVO_DBPREFIX.'idle_logout_duration', trim(sanitize_text_field(esc_html($_POST[TEBRAVO_DBPREFIX.'idle_logout_duration']))));
						
						echo __("Loading", TEBRAVO_TRANS)."...";
						tebravo_redirect_js($this->html->init->admin_url.'-wadmin&p=idle&msg=01');
						
					} else {
						tebravo_redirect_js($this->html->init->admin_url.'-wadmin&p=idle&err=02');
					}
				}
			}
		}
		
		//two factor dahsboard
		public function twofa_dashboard()
		{
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			if( !$_POST)
			{
				$desc = __("You are able to choose from many options when you decide to enable 2-Step Verification.", TEBRAVO_TRANS);
				$desc .= "<br />".__("<u>Available options:</u> Two factor authentication, Facebook Verification, Four numbers pin code and Security question.", TEBRAVO_TRANS);
				$this->html->header(__("WPAdmin Security - Two Steps Verification", TEBRAVO_TRANS), $desc, "2fa.png", false);
				
				$output[] = "<form action='".$this->html->init->admin_url."-wadmin&p=2fa' method=post>";
				$output[] = "<input type='hidden' name='_nonce' value='".$this->html->init->create_nonce('2fa-settings')."'>";
				$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
				$output[] = "<table border='0' width=100% cellspacing=0>";
				
				$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Enable/Disable option", TEBRAVO_TRANS)."</strong></td></tr>";
				$output[] = "<tr class='tebravo_underTD'><td width=65%>";
				$output[] = __("If you choose to enable this option, all users will be available to use it.", TEBRAVO_TRANS);
				$output[] = "</td>";
				
				if(( esc_html( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'two_step_login') ) ) == 'checked'){
					$two_step_login_status = "checked"; $two_step_login_status_no = "";
				} else {
					$two_step_login_status= ""; $two_step_login_status_no = "checked";
				}
				//enable/disable two steps login
				$output[] = "<td><input type='radio' name='".TEBRAVO_DBPREFIX."two_step_login' value='checked' id='two_step_login' $two_step_login_status><label for='two_step_login'><span></span>".__("Yes", TEBRAVO_TRANS)."</label> &nbsp;";
				$output[] = "<input type='radio' name='".TEBRAVO_DBPREFIX."two_step_login' value='no' id='two_step_login_no' $two_step_login_status_no><label for='two_step_login_no'><span></span>".__("No", TEBRAVO_TRANS)."</label>";
				$output[] = "</td>";
				$output[] = "</tr>";
				
				//default method
				$defaults_array = array(
						'2-Factor Authentication' => '2fa',
						'Facebook App' => 'fb', 
						'4 Numbers Pin Code' => 'pin', 
						'Security Question' => 'q');
				
				$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Default Method", TEBRAVO_TRANS)."</strong></td></tr>";
				$output[] = "<tr class='tebravo_underTD'><td colspan=2>";
				#$output[] = "<select name='".TEBRAVO_DBPREFIX."two_step_login_default'>";
				$methods = '';
				foreach ($defaults_array as $default_option => $option_value)
				{
					$methods .= "<p><input type='radio' name='".TEBRAVO_DBPREFIX."two_step_login_default' id='".$option_value."' value='".$option_value."' ";
					if(tebravo_utility::get_option(trim(sanitize_text_field(TEBRAVO_DBPREFIX.'two_step_login_default'))) == $option_value)
					{
						$methods .= "checked";
					}
					$methods .="><label for='".$option_value."'><span></span>".__($default_option, TEBRAVO_TRANS)."</label></p>";
					
				}
				$output[] = $methods;
				#$output[] = "</select>";
				$output[] = "</td>";
				
				//roles options
				$roles_2fa = trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'roles_2fa')));
				if(!empty($roles_2fa))
				{
					$roles_2fa = json_decode(trim(sanitize_text_field(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'roles_2fa'))), true);
				}else {$roles_2fa = '';}
				
				$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Choose roles", TEBRAVO_TRANS)."</strong></td></tr>";
				$output[] = "<tr class='tebravo_underTD'><td colspan=2><p>";
				$output[] = $this->get_roles_list( $roles_2fa);
				$output[] = "</td></tr>";
				
				//2fa settings
				if(! defined('TEBRAVO_APPID')){
					define('TEBRAVO_APPID', trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'two_step_facebook_app'))));
				}
				
				if(! defined('TEBRAVO_APPSECRET')){
					define('TEBRAVO_APPSECRET', trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'two_step_facebook_app_secret'))));
				}
				$user = wp_get_current_user();
				$tebravo_login = new tebravo_login();
				$app_id = trim(sanitize_text_field(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'two_step_facebook_app')));
				$app_secret= trim(sanitize_text_field(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'two_step_facebook_app_secret')));
				$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Facebook Application Settings", TEBRAVO_TRANS)."</strong></td></tr>";
				$output[] = "<tr class='tebravo_underTD'><td colspan=2>";
				$output[] = "App ID:<br />";
				$output[] = "<input type='text' name='".TEBRAVO_DBPREFIX."two_step_facebook_app' value='$app_id'><br />";
				$output[] = "App Secret:<br />";
				$output[] = "<input type='text' name='".TEBRAVO_DBPREFIX."two_step_facebook_app_secret' value='$app_secret'>";
				if( esc_html(esc_js( tebravo_utility::get_option(TEBRAVO_DBPREFIX."two_step_login_default"))) == 'fb' )
				{
					$output[] = "<blockquote>".$tebravo_login->two_fb_form($user, false, __("Test Login", TEBRAVO_TRANS))."</blockquote>";
				}
				$output[] = "</td></tr>";
				
				$output[] = "<tr class='tebravo_underTD'><td colspan=2>";
				$output[] = $this->html->button(__("Save Options", TEBRAVO_TRANS), 'submit');
				$output[] = $this->html->button(__("Back", TEBRAVO_TRANS), "button", "back", false, "arrow_left.png");;
				$output[] = "</td></tr>";
				$output[] = "</table>";
				$output[] = "</div>";
				$output[] = "</form>";
				
				
				echo implode("\n", $output);
				
				@ini_set('display_errors', 1);
				
				echo '<script>';
				echo 'jQuery("#back").click(function(){';
				echo 'window.location.href= "'.$this->html->init->admin_url."-wadmin".'"';
				echo '});';
				echo '</script>';
				
				
				$this->html->footer();
			} else {
				//check capability
				if(! current_user_can('manage_options')){wp_die(__("Wrong Access", TEBRAVO_TRANS)); exit;}
				
				if(empty($_POST['_nonce']) || false === wp_verify_nonce($_POST['_nonce'], $this->html->init->security_hash.'2fa-settings'))
				{
					tebravo_redirect_js($this->html->init->admin_url.'-wadmin&p=2fa&err=02');
				} else {
					if(! empty($_POST[TEBRAVO_DBPREFIX."two_step_login_default"]))
					{
						$roles_2fa_ = '';
						if(isset($_POST['roles_2fa'])):
							$roles_2fa_posted= ($_POST['roles_2fa']);
							if(!empty($roles_2fa_posted))
							{
								if(is_array($roles_2fa_posted))
								{
									$roles_2fa_ = json_encode(array_map('strtolower', $roles_2fa_posted));
								}
							}
							
						endif;
						tebravo_utility::update_option(TEBRAVO_DBPREFIX."roles_2fa", trim(sanitize_text_field($roles_2fa_)));
						tebravo_utility::update_option(TEBRAVO_DBPREFIX."two_step_login", trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX."two_step_login"])));
						tebravo_utility::update_option(TEBRAVO_DBPREFIX."two_step_login_default", trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX."two_step_login_default"])));
						tebravo_utility::update_option(TEBRAVO_DBPREFIX."two_step_facebook_app", trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX."two_step_facebook_app"])));
						tebravo_utility::update_option(TEBRAVO_DBPREFIX."two_step_facebook_app_secret", trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX."two_step_facebook_app_secret"])));
						
						echo __("Loading", TEBRAVO_TRANS)."...";
						tebravo_redirect_js($this->html->init->admin_url.'-wadmin&p=2fa&msg=01');
					} else {
						tebravo_redirect_js($this->html->init->admin_url.'-wadmin&p=2fa&err=02');
					}
					
				}
			}
		}
		
		//get roles list
		public function get_roles_list( $checked=false )
		{
			if(function_exists('get_editable_roles')){
				$roles = get_editable_roles();
				if(is_array($roles))
				{
					$output = '';
					foreach ($roles as $key)
					{
						$output .= "<input type='checkbox' name='roles_2fa[]' value='".strtolower($key['name'])."' id='".md5($key['name'])."' ";
						if(is_array($checked)){
							if(in_array(strtolower($key['name']), $checked)){
								$output .= "checked";
							}
						}
						$output .= "><label for='".md5($key['name'])."'>".$key['name']."</label><br />";
						
					}
					return $output;
				}
			}
		}
		
		//get QR image
		public function qr_img(  )
		{
			$twofa = new tebravo_2fa();
			$user = wp_get_current_user();
			$name = get_bloginfo('name')."(".$user->user_login.")";
			$img = $twofa->getQRCodeGoogleUrl($name, $twofa->createSecret(28));
			return "<img src='".$img."'>";
		}
		
		//add new from to profil
		public function new_form_profile()
		{
			$login = new tebravo_login();
			if(is_user_logged_in()){
				
				switch ($this->twofa_default)
				{
					case '2fa':
						$file = TEBRAVO_DIR.'/hooks/2fa_actions/2fa.php';
						if(file_exists( $file )) {include_once $file;}
						break;
					case 'fb':
						$file = TEBRAVO_DIR.'/hooks/2fa_actions/fb.php';
						if(file_exists( $file )) {include_once $file;}
						break;
					case 'pin':
						$file = TEBRAVO_DIR.'/hooks/2fa_actions/pin.php';
						if(file_exists( $file )) {include_once $file;}
						break;
					case 'q':
						$file = TEBRAVO_DIR.'/hooks/2fa_actions/q.php';
						if(file_exists( $file )) {include_once $file;}
						break;
					default:
						$file = TEBRAVO_DIR.'/hooks/2fa_actions/2fa.php';
						if(file_exists( $file )) {include_once $file;}
						break;
				}
			}
		}
		//write wplogin rules in htaccess file
		protected function htaccess_wplogin()
		{
			include_once TEBRAVO_DIR.'/includes/tebravo.htaccess.php';
			$htaccess = new tebravo_htaccess();
			
			$file = ABSPATH.'.htaccess';
			if( tebravo_phpsettings::web_server() == 'nginx' )
			{
				$file = ABSPATH.'nginx.conf';
			}
			
			//create file
			if( !file_exists( $file ) )
			{
				tebravo_files::write($file, '');
			}
			
			//check if created
			if( !file_exists( $file ) )
			{
				tebravo_die(true, __("Can not create file", TEBRAVO_TRANS)."<br /><i>".$file."</i>", false, true);
			}
			
			$rules = $htaccess->wplogin_rules();
			$current_data = tebravo_files::read( $file );
			
			//take backup
			$htaccess->take_copy($file);
			
			if(strpos($current_data, $htaccess->wplogin_start) !== false)
			{
				//update htaccess
				if( $this->hide_wplogin == 'checked' ){
					$new_updated_data = $rules;
				} else {
					$new_updated_data = '';
				}
				$htaccess->replace_update($htaccess->wplogin_start, $htaccess->wplogin_end, $new_updated_data);
			} else {
				//add new rules
				if( $this->hide_wplogin == 'checked' ){
					tebravo_files::write( $file , $rules.PHP_EOL.$current_data);
				}
			}
		}
		
		//write wpadmin rules in htaccess file
		protected function htaccess_wpadmin()
		{
			include_once TEBRAVO_DIR.'/includes/tebravo.htaccess.php';
			$htaccess = new tebravo_htaccess();
			
			$file = ABSPATH.'.htaccess';
			$rules = $htaccess->wpadmin_rules();
			$current_data = tebravo_files::read( $file );
			
			//take backup
			$htaccess->take_copy();
			
			if(strpos($current_data, $htaccess->wpadmin_start) !== false)
			{
				//update htaccess
				if( $this->hide_wpadmin == 'checked' ){
					$new_updated_data = $rules;
				} else {
					$new_updated_data = '';
				}
				$htaccess->replace_update($htaccess->wpadmin_start, $htaccess->wpadmin_end, $new_updated_data);
			} else {
				//add new rules
				if( $this->hide_wpadmin == 'checked' ){
					tebravo_files::write( $file , PHP_EOL.$rules, true);
				}
			}
		}
		
		//send new wp-login link to admins
		protected function send_new_wplogin_email( $email )
		{
			$file = TEBRAVO_DIR.'/includes/email_templates/new_login_url.html';
			$data = tebravo_files::read( $file );
			
			$user = get_user_by('email', $email);
			$loginurl = get_home_url().'/'.$this->wplogin_slug;
			
			$data = str_replace('{%username%}', $user->user_nicename, $data);
			$data = str_replace('{%sitename%}', get_bloginfo('name'), $data);
			$data = str_replace('{%newloginurl%}', $loginurl, $data);
			
			$message = $data;
			
			$subject = __("Security Alert: Login Link Changed!", TEBRAVO_TRANS);
			
			add_filter( 'wp_mail_content_type', array( $this , 'mail_content_type' ) );
			$site_url = tebravo_utility::get_bloginfo('siteurl');
			#wp_mail($email, $subject, $message);
			tebravo_mail( $email, $subject." [$site_url]", $message );
		}
		
		//to send HTML message via wp_mail
		public function mail_content_type(){
			return "text/html";
		}
		
		
		
	}
	//run
	new tebravo_wpadmin();
}
?>