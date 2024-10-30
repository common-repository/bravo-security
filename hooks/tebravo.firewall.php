<?php 
/**
 * Hook: BRAVO.FIREWALL
 * Firewall to protect Wordpress.
 * @since 1.0
 * Copyrights (C) 2017 Technoyer Solutions Ltd. <support@technoyer.com>
 */

//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if(!class_exists( 'tebravo_firewall' ))
{
	class tebravo_firewall 
	{
		//options static
		public static 
		$ip,
		$profile,
		$block_screen,
		$block_message,
		$max_connection,
		$action_on_maxconnection,
		$block_ip,
		$block_country,
		$symlink,
		$php_secure_level,
		$detect_404,
		$max_404,
		$action_on_404,
		$block_xmlrpc,
		$block_pingback,
		$hide_apache_info,
		$indexable,
		$block_bot_comments,
		$block_proxy_comments,
		$block_outsite_comments,
		$fake_google_crawles,
		$query_string_filter,
		$max_query_attempts,
		$action_on_max_queryattempts,
		$block_for_recaptcha_error,
		$max_recaptcha_attempts,
		$max_403,
		$action_on_403,
		$block_period,
		$phpini_header,
		$php_errorlog,
		$php_security,
		$country_code,
		$country,
		$current_page,
		$browser,
		$advanced_lock,
		$emailSettings=array(),
		$standard_profile=array();
		//other not static
		public $html;
		
		//constructor
		public function __construct()
		{
			
			self::$profile = trim( esc_html(tebravo_utility::get_option( TEBRAVO_DBPREFIX.'firewall_profile' ) ) );
			self::$block_screen = trim( esc_html(tebravo_utility::get_option( TEBRAVO_DBPREFIX.'firewall_block_screen' ) ) );
			self::$block_message = trim( esc_html(tebravo_utility::get_option( TEBRAVO_DBPREFIX.'firewall_block_message' ) ) );
			self::$block_period = trim( esc_html(tebravo_utility::get_option( TEBRAVO_DBPREFIX.'firewall_block_period' ) ) );
			self::$php_security = trim( esc_html(tebravo_utility::get_option( TEBRAVO_DBPREFIX.'firewall_php_security' ) ) );
			
			self::$standard_profile = array( "high", "medium", "low", "disabled" );
			
			self::$phpini_header = "[PHP]".PHP_EOL.";Created By ".TEBRAVO_PLUGINNAME.PHP_EOL;
			self::$php_errorlog = TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/log/php_log.txt';
			
			self::$advanced_lock = 'checked';
			
			//email settings
			$bravo_mail = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'email' ) ) );
			$bravo_cc = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'cc' ) ) );
			$admin_mail = tebravo_utility::get_option( 'admin_email' );
			//email
			self::$emailSettings['mail'] = $admin_mail;
			if( isset( $bravo_mail ) && !empty( $bravo_mail ) )
			{
				self::$emailSettings['mail'] = $bravo_mail;
			}
			//cc
			self::$emailSettings['cc'] = $bravo_cc;
			if( !isset( $bravo_cc ) || empty( $bravo_cc ) )
			{
				self::$emailSettings['cc'] = false;
			}
			//notifications on human blocking
			self::$emailSettings['notify']['human'] = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'email_firewall_blocked_human' ) ) );
			//notifications on bot blocking
			self::$emailSettings['notify']['bot'] = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'email_firewall_blocked_bot' ) ) );
			
			//actions and filters
			
			add_action( 'init', array( $this, 'init' ) );
			
			if( !is_admin() )
			{
				
				add_action('init', array($this, 'advanced_lock'));
			}
		}
		
		//init
		public function init()
		{
			if( $this->php_settings_device() == 'INISET'
					&& self::$profile != 'disabled' )
			{
				add_action( 'init', array( $this, 'set_php' ) );
				add_action( 'admin_init', array( $this, 'set_php' ) );
			}
			
			if( self::$profile != 'disabled' )
			{
				add_action( 'wp_head', array( $this, 'check_locked' ) );
				add_action( 'tebravo_errorpages_404', array( $this, 'check_locked' ) );
				add_action( 'wp_footer', array( $this, 'max_connection_action' ) );
				add_action( 'pre_ping', array( $this, 'disable_pingback' ) );
				if( $this->get_rule( 'block_xmlrpc', self::$profile) == true )
				{
					add_filter('xmlrpc_enabled', '__return_false');
				}
				add_action( 'wp_head', array( $this, 'detect_googbot' ) );
				add_action( 'wp_head', array( $this, 'query_string_filter' ) );
				
				if( trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'404_page' ) ) ) == 'checked')
				{
					add_action( 'tebravo_errorpages_404', array( $this, 'detect_404' ) );
					add_action( 'tebravo_errorpages_404', array( $this, 'query_string_filter' ) );
				} else {
					add_action( 'wp_head', array( $this, 'detect_404' ) );
					add_action( 'wp_head', array( $this, 'query_string_filter' ) );
				}
				
				add_action( 'tebravo_recaptcha_validate', array( $this, 'validate_recaptcha' ) );
				add_action( 'preprocess_comment', array( $this, 'comments' ) );
			}
			
			add_action( 'wp_ajax_remove_blocked', array( $this, 'remove_blocked' ) );
			add_action( 'wp_ajax_whitelist_blocked', array( $this, 'whitelist_blocked' ) );
		}
		
		public function advanced_lock()
		{
			if( isset($_REQUEST['bravo']) && $_REQUEST['bravo']='locked')
			{
				$screens_array = array("403", "404");
				$screen = 403;
				if( in_array(self::$block_screen, $screens_array))
				{
					$screen = self::$block_screen;
				}
				header("HTTP/1.1 ".$screen." Not Found");
				$e = new tebravo_errorpages();
				$e->print_page($screen);
				exit;
			}
		
		}
		
		//comments
		public function comments( $comment_data )
		{
			//bots
			if( $this->get_rule( 'block_bot_comments', self::$profile ) == true )
			{
				if( tebravo_agent::is_bot( $this->constants( 'user_agent' ) ) == true )
				{
					wp_die( __("Comments from bots disallowed!", TEBRAVO_TRANS ) );
				}
			}
			
			//proxy
			if( $this->get_rule( 'block_proxy_comments', self::$profile ) == true )
			{
				if( tebravo_agent::is_proxy() == true )
				{
					wp_die( __("Comments from proxy disallowed!", TEBRAVO_TRANS ) );
				}
			}
			
			return $comment_data;
		}
		//check if IP or country locked (block or slowdown)
		public function check_locked()
		{
			$helper = new tebravo_html();
			$ip = $this->constants( 'ip' );
			$country = trim( $this->constants( 'country_code' ) );
			
			$user_id = 0;
			if( is_user_logged_in() )
			{
				$user = wp_get_current_user();
				$user_id = $user->ID;
			}
			
			$result = false;
			
			
			global $wpdb;
			
			$block_action = 'block';
			$row = $wpdb->get_row( "SELECT block_action,block_type,country_code FROM ". tebravo_utility::dbprefix()."firewall_actions 
								WHERE ipaddress='$ip' Limit 1");
			
			if( null!==$row )
			{
				//try to unblock
				self::try_unblock();
				
				$block_action = $row->block_action;
				//avoiding many redirects
				if( isset($_REQUEST['bravo']) && $_REQUEST['bravo'] == 'locked'){exit;}
				//do lock in init by headers
					if( self::$advanced_lock == 'checked' && $block_action == 'block')
					{
						$url = home_url();
						$redirect_to = add_query_arg(array("bravo"=>"locked"), $url);
						tebravo_redirect_js($redirect_to, true); exit;
					}
				
				$this->lockout_take_action( $block_action );
				
			}
			//check for country
			$this->check_locked_country( $country );
			
		}
		//check if custom IP locked
		public function is_locked_ip( $ip=false )
		{
			global $wpdb;
			
			if( !$ip ){$ip = $this->constants( 'ip' );}
			
			$row = $wpdb->get_row( "SELECT id,block_action FROM ". tebravo_utility::dbprefix()."firewall_actions
					WHERE ipaddress='$ip' Limit 1");
			if( null!==$row )
			{
				define( 'TEBRAVO_IP_BLOCK_ACTION', $row->block_action );
				return true;
			}
			
			return false;
		}
		//check if custom IP locked
		public function is_locked_country( $country_code=false )
		{
			global $wpdb;
			
			if( !$country_code ){$country_code = $this->constants( 'country_code' );}
			
			$row = $wpdb->get_row( "SELECT id,block_action FROM ". tebravo_utility::dbprefix()."firewall_actions
					WHERE country_code='$country_code' Limit 1");
			if( null!==$row )
			{
				define( 'TEBRAVO_COUNTRY_BLOCK_ACTION', $row->block_action );
				return true;
			}
			
			return false;
		}
		//check if visitor country locked
		protected function check_locked_country( $country_code )
		{
			global $wpdb;
			
			$country = trim( $this->constants( 'country_code' ) );
			$block_action = 'block';
			if( defined( 'TEBRAVO_IP_BLOCK_ACTION' ) ){$block_action = TEBRAVO_IP_BLOCK_ACTION;}
			//echo $country_code;
			if( $this->is_locked_ip() ){ $this->lockout_take_action( $block_action ); exit;}
			if( true == $this->whitelisted( 'country', trim($country_code)) ){ return; }
			
			$block_action = 'block';
			$row = $wpdb->get_row( "SELECT block_action,block_type,country_code FROM ". tebravo_utility::dbprefix()."firewall_actions
					WHERE country_code='$country' Limit 1");
			
			if( null!==$row )
			{
				//try to unblock
				self::try_unblock('country');
				
				if( $row->block_type == 'ipaddress' ) {return;} 
				
				$block_action = $row->block_action;
				
				$this->lockout_take_action( $block_action );
			}
			
		}
		//do the punish action
		protected function lockout_take_action($block_action)
		{
			if( $block_action == 'block' )
			{
				self::get_block_screen();
			} else {
				sleep(25);
			}
		}
		//check it is the unblock time
		protected static function try_unblock($for=false)
		{
			global $wpdb;
			$wpdb->show_errors( false );
			
			$ipaddress = tebravo_agent::user_ip();
			$country = tebravo_agent::ip2country();
			
			if( !$for )
			{
				$for = 'ipaddress';
			}
			
			$dbWhere = "WHERE ipaddress='$ipaddress'";
			if( $for == 'ipaddress') {$dbWhere = "WHERE ipaddress='$ipaddress'";}
			else if( $for == 'country') {$dbWhere = "WHERE country_code='$country'";}
			
			$dbTable = tebravo_utility::dbprefix()."firewall_actions";
			$row = $wpdb->get_row( "SELECT id,time_blocked,time_to_unblock FROM $dbTable
								 $dbWhere Limit 1");
			//var_dump($row); exit;
			if( null!==$row )
			{
				$time_now = time();
				
				if( $row->time_to_unblock != 'never' )
				{
					if( $time_now > $row->time_to_unblock )
					{
						$wpdb->delete($dbTable, array(
								'id' => $row->id,
						));
						
						tebravo_redirect_js( home_url() ); exit;
					}
				}
			}
			//$wpdb->print_error( $wpdb->show_errors() );
		}
		
		//retrieve block screen
		protected static function get_block_screen( $screen=false )
		{
			//start showing screen
			if( !$screen )
			{
				$screen = self::$block_screen;
			}
			
			//description
			switch ($screen)
			{
				case 403:
					$desc = 'Forbidden';
					break;
				case 404:
					$desc = 'Not Found';
					break;
					default:$desc = 'Not Found';
			}
			
			$error_pages_status = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'404_page' ) ) );
			$msg = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'firewall_block_message' ) ) );
			$blocked_message = __("You were blocked!", TEBRAVO_TRANS);
			if( !empty( $msg ) )
			{
				$blocked_message = $msg;
			}
			if( $error_pages_status == 'checked' 
					&& class_exists( 'tebravo_errorpages' ) )
			{
				$errorPages = new tebravo_errorpages();
				$content = $errorPages->template( $screen, $blocked_message);
				
				echo $content;
				exit;
			} else {
				add_action('init', function() use($screen, $desc)
						{
							header('HTTP/1.1 '.$screen.' '.$desc);
							header('Status: '.$screen.' '.$desc);
							header('Connection: Close');
				});
				
				echo "<h2 style='padding:35px; text-align:center; font-size:300%; font-weight:bold'>".$screen."</h2>";
				echo "<p style='padding:35px; text-align:center; font-size:200%;'>".$blocked_message."</p>";
				exit;
			}
		}
		
		//class constants
		public function constants( $option )
		{
			$result = '';
			if( $option == 'ip' )
			{
				$_ip = tebravo_agent::user_ip();
				$result = tebravo_GetRealIP();
				if( $_ip != '' )
				{
					$result = $_ip;
				}
			} else if( $option == 'country_name' )
			{
				//get country name
				$_country = tebravo_agent::ip2country( tebravo_agent::user_ip(), 'country_name' );
				$result = '';
				if( $_country != '' )
				{
					$result = $_country;
				}
			} else if( $option == 'country_code' )
			{
				//get country code
				$_country_code = tebravo_agent::ip2country( tebravo_agent::user_ip() );
				$result = '';
				if( $_country_code != '' )
				{
					$result = $_country_code;
				}
			} else if( $option == 'device' )
			{
				//get device
				$_device = tebravo_agent::device();
				$result = '';
				if( $_device != '' )
				{
					$result = $_device;
				}
			} else if( $option == 'browser' )
			{
				//get browser name
				$browser_array = tebravo_agent::getBrowser();
				$result = '';
				if( is_array( $browser_array ) )
				{
					$result = $browser_array['name'];
				}
			} else if( $option == 'ISP' )
			{
				//get user ISP
				$_ISP = tebravo_agent::ISP();
				$result = '';
				if( $_ISP != '' )
				{
					$result = $_ISP;
				}
			} else if( $option == 'proxy' )
			{
				//check if proxy
				$result = 'false';
				if( tebravo_agent::is_proxy() )
				{
					$result = 'true';
				}
			} else if( $option == 'bot' )
			{
				//check if bot
				$result = 'false';
				if( tebravo_agent::is_bot() )
				{
					$result = 'true';
				}
			} else if( $option == 'current_page' )
			{
				//get current page
				$result = '';
				if( isset( $_SERVER['REQUEST_URI'] ) && !empty( $_SERVER['REQUEST_URI'] ) )
				{
					$result = $_SERVER['REQUEST_URI'];
				}
			} else if( $option == 'came_from' )
			{
				//get http referer (came from any website)
				$result = '';
				if( isset( $_SERVER['HTTP_REFERER'] ) && !empty( $_SERVER['HTTP_REFERER'] ) )
				{
					$visitor_came_from = $_SERVER['HTTP_REFERER'];
					$result = $this->get_host( $visitor_came_from );
				}
			} else if( $option == 'user_agent' )
			{
				$result = (isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '');
			}
			
			return $result;
		}
		
		//check if whilelisted (IP or Country Code)
		public function whitelisted( $for , $target )
		{
			$for_array = array("ipaddress", "country");
			
			//check 'for' options
			if( !in_array( $for , $for_array ) )
			{
				$for = 'ipaddress';
			}
			
			//get lists from DB
			if( $for == 'ipaddress' )
			{
				$data = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'firewall_whiteips') ) );
			} else if( $for == 'country' )
			{
				$data = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'firewall_whitecountries') ) );
			}
			//convert to array
			$exp = explode(PHP_EOL, $data);
			$exp = array_map( 'trim', $exp );
			
			//check for security
			if( $for == 'ipaddress'
					&& !filter_var($target, FILTER_VALIDATE_IP))
			{
				return false;
			}
			/*
			if( $for == 'country'
					&& !in_array( trim($target), tebravo_countries::$countries ) ) //tebravo_countries class /includes/AgentSrc
			{
				return false;
			}
			*/
			//return to results
			
			if( null!==$exp )
			{
				if( in_array($target, $exp)) {return true;}
			}
			
			return false;
		}
		//define where will new php settings be saved
		public static function php_settings_device()
		{
			$php_settings = new tebravo_phpsettings();
			
			$device = '';
			//testing ini_set and ini_get
			if( !$php_settings->is_disabled( 'ini_set' ) 
					&& !$php_settings->is_disabled( 'ini_get' ) )
			{
				$old_value = ini_get( 'html_errors' );
				
				$new_value = 1;
				if( $old_value == 1 )
				{
					$new_value = 0;
				}
				
				ini_set( 'html_errors', $new_value );
				$current_value = ini_get( 'html_errors' );
				if( $current_value== $new_value )
				{
					$device = 'INISET';
					ini_set( 'html_errors', $old_value );
				}
			}
			//get device based on PHP handler
			if( $device != 'INISET' )
			{
				if( tebravo_phpsettings::get_php_handler() == 'CGI' )
				{
					$device = 'PHPINI'; //php.ini
				} else {
					$device = 'PHPFLAG'; //htaccess
				}
			}
			//test php.ini file 
			if( $device == 'PHPINI' )
			{
				$file_path = ABSPATH.'php.ini';
				
				if( !file_exists( $file_path ) )
				{
					tebravo_files::write( $file_path , self::$phpini_header );
				}
				if( file_exists( $file_path) 
						&& tebravo_files::writable( $file_path ) )
				{
					$device = 'PHPINI';
				} else {
					$device = 'INISET';
				}
			}
			//default value for $device
			if( $device == '' ){$device = 'INISET';}
			
			return $device;
		}
		
		//get rule from profile rules list
		public static function get_rule( $rule, $profile=false )
		{
			if( !$profile || !in_array($profile, self::$standard_profile) )
			{
				$profile = self::$profile;
			}
			
			$profile_rules = self::rules( $profile );
			
			if( is_array( $profile_rules ) )
			{
				if( in_array( $rule, $profile_rules ) )
				{
					$the_rule = $profile_rules[$rule];
					
					if( !empty( $the_rule ) )
					{
						return $the_rule;
					}
				}
			}
		}
		
		//firewall rules list
		private static function rules( $profile=false )
		{
			if( !$profile || !in_array($profile, self::$standard_profile) )
			{
				$profile = self::$profile;
			}
			
			//get rule
			if ( $profile != 'disabled' )
			{
				$profile_rules = array
				(
						//HIGH Level of Firewall
						"high" => array
						(
						    "max_connection" => 25,
						    "action_on_maxconnection" => 'slowdown',
						    "block_ip" => true,
						    "block_country" => false,
						    "symlink" => true,
						    "php_secure_level" => 'medium',
						    "detect_404" => true,
						    "max_404" => 10,
						    "action_on_403" => 'block',
						    "action_on_404" => 'slowdown',
						    "block_xmlrpc" => false,
						    "block_pingback" => true,
						    "hide_apache_info" => true,
						    "indexable" => true,
						    "block_bot_comments" => true,
						    "block_proxy_comments" => true,
						    "fake_google_crawles" => true,
						    "query_string_filter" => true,
						    "max_query_attempts" => 4,
						    "action_on_max_queryattempts" => 'block',
						    "block_for_recaptcha_error" => true,
						    "max_recaptcha_attempts" => 5,
						),
						//MEDIUM Level of Firewall
						"medium" => array
						(
								"max_connection" => 25,
								"action_on_maxconnection" => 'slowdown',
								"block_ip" => true,
								"block_country" => false,
								"symlink" => true,
								"php_secure_level" => 'medium',
								"detect_404" => true,
								"max_404" => 10,
								"action_on_403" => 'block',
								"action_on_404" => 'slowdown',
								"block_xmlrpc" => false,
								"block_pingback" => true,
								"hide_apache_info" => true,
								"indexable" => true,
								"block_bot_comments" => true,
								"block_proxy_comments" => true,
								"fake_google_crawles" => true,
								"query_string_filter" => true,
								"max_query_attempts" => 4,
								"action_on_max_queryattempts" => 'block',
								"block_for_recaptcha_error" => true,
								"max_recaptcha_attempts" => 5,
						),
						//LOW Level of Firewall
						"low" => array
						(
								"max_connection" => 35,
								"action_on_maxconnection" => 'slowdown',
								"block_ip" => true,
								"block_country" => false,
								"symlink" => true,
								"php_secure_level" => 'low',
								"detect_404" => true,
								"max_404" => 15,
								"action_on_403" => 'slowdown',
								"action_on_404" => 'slowdown',
								"block_xmlrpc" => false,
								"block_pingback" => false,
								"hide_apache_info" => true,
								"indexable" => true,
								"block_bot_comments" => true,
								"block_proxy_comments" => false,
								"fake_google_crawles" => false,
								"query_string_filter" => true,
								"max_query_attempts" => 5,
								"action_on_max_queryattempts" => 'slowdown',
								"block_for_recaptcha_error" => false,
								"max_recaptcha_attempts" => 0,
						)
				);
				
				if( is_array($profile_rules) && $profile_rules !=''):
				if( isset($profile_rules[$profile]))
				return $profile_rules[$profile];
				endif;
			}
		}
		
		//block for recaptch error attempts
		public function validate_recaptcha()
		{
			$ip = $this->constants( 'ip') ;
			if( $this->get_rule( 'block_for_recaptcha_error', self::$profile ) == true ):
				if( !isset( $_COOKIE['tebravo_recaptcha'] ) )
				{
					@setcookie( 'tebravo_recaptcha', 1 , time()+3600 );
					@setcookie( 'tebravo_recaptcha_user', str_replace('.', '', $ip ), time()+3600 );
					
				}
				
				if( isset( $_COOKIE['tebravo_recaptcha'] )
						&& !empty( $_COOKIE['tebravo_recaptcha'] ) )
				{
					
					@setcookie( 'tebravo_recaptcha', ($_COOKIE['tebravo_recaptcha'] + 1), time()+3600 );
					@setcookie( 'tebravo_recaptcha_user', str_replace('.', '', $ip ) , time()+3600 );
					
					
					if( ($_COOKIE['tebravo_recaptcha']+1) >= $this->get_rule( 'max_recaptcha_attempts', self::$profile )
							&& $_COOKIE['tebravo_recaptcha_user'] == str_replace('.', '', $ip ) )
					{
						$this->lockout( 'block', 'max_recaptcha_error_attempts');
					}
				}
			endif;
		}
		
		//query string filters
		public function query_string_filter()
		{
			$this->helper = new tebravo_html();
			//options
			$rule = $this->get_rule( 'query_string_filter', self::$profile );
			$max = $this->get_rule( 'max_query_attempts', self::$profile );
			$action = $this->get_rule( 'action_on_max_queryattempts', self::$profile );
			
			//URI
			$current_uri = '';
			if( isset( $_SERVER['REQUEST_URI'] ) && !empty( $_SERVER['REQUEST_URI'] ) )
			{
				$current_uri = $_SERVER['REQUEST_URI'];
			}
			
			//QUERY_STRING
			$current_query_string = '';
			if( isset( $_SERVER['QUERY_STRING'] ) && !empty( $_SERVER['QUERY_STRING'] ) )
			{
				$current_query_string = $_SERVER['QUERY_STRING'];
			}
			
			//USER AGENT
			$current_user_agent = $this->constants( 'user_agent' );
			
			include TEBRAVO_DIR.'/hooks/firewall/query_filter.php';
			if( !class_exists( 'tebravo_query_filter' ) ) {
				tebravo_errorlog::errorlog( "The Class tebravo_query_filter does not exits" );
				return;}
			//check
			if( preg_match('/'.implode("|", tebravo_query_filter::$bad_queries_uri).'/i', $current_uri)
					|| preg_match('/'.implode("|", tebravo_query_filter::$bad_queries).'/i', $current_query_string)
					|| preg_match('/'.implode("|", tebravo_query_filter::$bad_user_agents).'/i', $current_user_agent)
					)
			{
				global $wpdb;
				
				$attempts_badreqs = 0;
				
				//set cookie key
				if( isset( $_COOKIE['tebravo_session'] )
						&& !empty( $_COOKIE['tebravo_session'] )
						&& strlen( $_COOKIE['tebravo_session'] ) == 8)
				{
					$cookie_key = $_COOKIE['tebravo_session'];
				} else {
					$cookie_key = $this->helper->init->create_hash(8);
					add_action( 'init', function()
					{
						setcookie( 'tebravo_session', $GLOBALS['cookie_key'] );
						
					});
				}
				
				//options
				$ip = $this->constants( 'ip' );
				$dbTable = tebravo_utility::dbprefix().'traffic';
				
				$user_id = 0;
				if( is_user_logged_in() )
				{
					$user = wp_get_current_user();
					$user_id = $user->ID;
				}
				
				//check locked
				$row_locked = $wpdb->get_row( "SELECT id FROM ". tebravo_utility::dbprefix()."firewall_actions
						WHERE ipaddress='$ip' Limit 1");
				if( null!==$row_locked ){ return; }
				
				//db query
				$wpdb->show_errors( false );
				$row = $wpdb->get_row("SELECT id,attempts_badreqs,start_time,last_active
										FROM ".$dbTable."
						WHERE ipaddress='$ip' Limit 1");
				
				if( null=== $row )
				{
					if( class_exists( 'tebravo_traffic') )
					{
						//$traffic = new tebravo_traffic();
						$wpdb->insert( $dbTable, array(
								"ipaddress" => ($this->constants( 'ip' )),
								"userid" => ($user_id),
								"country" => ($this->constants( 'country_name' )),
								"country_code" => ($this->constants( 'country_code' )),
								"attempts_404" => 1,
								"attempts_badreqs" => 1,
								"is_bot" => ($this->constants( 'bot' )),
								"device" => ($this->constants( 'device' )),
								"browser" => ($this->constants( 'browser' )),
								"user_isp" => ($this->constants( 'ISP' )),
								"past_sessions" => 0,
								"current_sessions" => 1,
								"cookie_key" => ($cookie_key),
								"current_page" => ($this->constants( 'current_page' )),
								"http_referer" => ($this->constants( 'came_from' )),
								"start_time" => time(),
								"last_active" => time(),
						));
						
					}
					
					$id = $wpdb->insert_id;
				} else {
					$id = $row->id;
					$attempts_badreqs = (int)$row->attempts_badreqs;
					//update database
					$this->calc_new_bad_query( $ip, $id, ($attempts_badreqs + 1));
					
					//take actions
					if( ($attempts_badreqs+ 1) >= $max )
					{
						//do lockout
						$this->lockout( $action, 'max_query_attempts' );
					}
				}
			}
			//define action
			do_action( 'tebravo_query_string_filter' );
		}
		
		//calculate bad queires attempts
		protected function calc_new_bad_query( $ip, $id, $attempts_badreqs)
		{
			global $wpdb;
			
			$dbTable = tebravo_utility::dbprefix().'traffic';
			$wpdb->show_errors( false );
			$wpdb->update( $dbTable, array("attempts_badreqs" => $attempts_badreqs),
					array(
							"id" => $id,
							"ipaddress" => $ip
					));
			
		}
		
		//retrieve white list for 404 requests
		protected function whitelist_404_requests( $target )
		{
			if( !$target ){$target = 'files';}
			
			if( $target == 'files' )
			{
				$whitelist = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'firewall_404_whitefiles' ) ) );
			} else if( $target == 'extension' )
			{
				$whitelist = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'firewall_404_whiteext' ) ) );
			}
			
			$results = array();
			if( !empty( $whitelist ) )
			{
				$results = explode(PHP_EOL, $whitelist );
				$results = array_map( 'trim', $results );
				$results = array_map( 'strtolower', $results );
				$results = str_replace( '/', '', $results );
			}
			
			return $results;
		}
		
		//detect 404 pages
		public function detect_404()
		{
			global $wpdb;
			
			//check white list
			$current_page = $this->constants( 'current_page' );
			$extension = '';
			$basename = '';
			if( isset($current_page) && !empty( $current_page ) )
			{
				$pathinfo = pathinfo( $current_page );
				$basename = (isset($pathinfo['basename']) ? $pathinfo['basename']: '');
				$extension = (isset($pathinfo['extension']) ? $pathinfo['extension']: '');
				
				if( $basename != '' )
				{
					if( in_array( $basename, $this->whitelist_404_requests( 'files' ) ) ){ return;}
				}
				if( $extension != '')
				{
					if( in_array( $extension, $this->whitelist_404_requests( 'extension' ) ) ){ return;}
				}
			}
			
			//continue
			$rule = $this->get_rule( 'detect_404', self::$profile );
			$max = $this->get_rule( 'max_404', self::$profile );
			$action = $this->get_rule( 'action_on_404', self::$profile );
			
			if( $rule == true )
			{
				if( !is_404() )
				{
					return;
				}
				
				$this->helper = new tebravo_html();
				//set cookie key
				if( isset( $_COOKIE['tebravo_session'] )
						&& !empty( $_COOKIE['tebravo_session'] )
						&& strlen( $_COOKIE['tebravo_session'] ) == 8)
				{
					$cookie_key = $_COOKIE['tebravo_session'];
				} else {
					$cookie_key = $this->helper->init->create_hash(8);
					add_action( 'init', function()
					{
						setcookie( 'tebravo_session', $GLOBALS['cookie_key'] );
						
					});
				}
				
				//options
				$ip = $this->constants( 'ip' );
				$dbTable = tebravo_utility::dbprefix().'traffic';
				
				$user_id = 0;
				if( is_user_logged_in() )
				{
					$user = wp_get_current_user();
					$user_id = $user->ID;
				}
				
				//check locked
				$row_locked = $wpdb->get_row( "SELECT id FROM ". tebravo_utility::dbprefix()."firewall_actions
						WHERE ipaddress='$ip' Limit 1");
				if( null!==$row_locked ){ return; }
				
				//db query
				$wpdb->show_errors( false );
				$row = $wpdb->get_row("SELECT id,attempts_404,start_time,last_active
										FROM ".$dbTable."
										WHERE ipaddress='$ip' Limit 1");
				
				if( null=== $row )
				{
					if( class_exists( 'tebravo_traffic') )
					{
						//$traffic = new tebravo_traffic();
						$wpdb->insert( $dbTable, array(
								"ipaddress" => ($this->constants( 'ip' )),
								"userid" => ($user_id),
								"country" => ($this->constants( 'country_name' )),
								"country_code" => ($this->constants( 'country_code' )),
								"attempts_404" => 1,
								"is_bot" => ($this->constants( 'bot' )),
								"device" => ($this->constants( 'device' )),
								"browser" => ($this->constants( 'browser' )),
								"user_isp" => ($this->constants( 'ISP' )),
								"past_sessions" => 0,
								"current_sessions" => 1,
								"cookie_key" => ($cookie_key),
								"current_page" => ($this->constants( 'current_page' )),
								"http_referer" => ($this->constants( 'came_from' )),
								"start_time" => time(),
								"last_active" => time(),
						));
						
					}
					
					$id = $wpdb->insert_id;
				} else {
					$id = $row->id;
					$attempts_404 = (int)$row->attempts_404;
					//update database
					$this->calc_new_404( $ip, $id, ($attempts_404 + 1));
					
					//take actions
					if( ($attempts_404 + 1) >= $max )
					{
						//do lockout
						$this->lockout( $action, 'max_404_attempts' );
					}
				}
			}
			
			//define action
			do_action( 'tebravo_firewall_detect_404' );
		}
		
		//calculate 404 attempts
		protected function calc_new_404( $ip, $id, $attempts_404)
		{
			global $wpdb;
			
			$dbTable = tebravo_utility::dbprefix().'traffic';
			$wpdb->show_errors( false );
			$wpdb->update( $dbTable, array("attempts_404" => $attempts_404), 
					array(
							"id" => $id,
							"ipaddress" => $ip
					));
			
		}
		//google bot user agent token
		private function googe_user_agent_token()
		{
			return array(
					"Googlebot",
					"Googlebot-News",
					"Googlebot-Image",
					"Googlebot-Video",
					"Mediapartners-Google",
					"AdsBot-Google",
					"APIs-Google",
			);
		}
		
		
		//detect fake google bot for init
		public function detect_googbot()
		{
			if( $this->get_rule( 'fake_google_crawles', self::$profile ) == true )
			{
				$is_fake = $this->is_fake_googlebot();
				
				if( !empty( $is_fake) && $is_fake == true )
				{
					$this->lockout( 'block', 'fake_googbot' );
					
					exit; //it is fake
				}
			}
		}
		//detect fake google bot
		protected function is_fake_googlebot()
		{
			//false: means it is not fake
			//true: means it is fake
			$results = '';
			
			$user_agent = $this->constants( 'user_agent' );
			$ip = $this->constants( 'ip' );
			$host_name = @gethostbyaddr( $ip );
			
			$domain_1st = substr( $host_name,-13 ); //googlebot.com
			$domain_2nd = substr( $host_name,-10 ); //google.com
			//compare coming ip with the ip of hostname
			if (preg_match('/'.implode("|", $this->googe_user_agent_token()).'/i', $user_agent, $matches)){
				
				if( isset( $host_name ) && !empty( $host_name ) )
				{
					$host_ip = @gethostbyname( $host_name );
					
					if(preg_match('/Googlebot/i', $host_name , $matches)){
						
						if( $host_ip == $ip ){$results = false;}
						else{ $results = true;}
					}
				}
			}
			//comapre domains from host name
			//https://support.google.com/webmasters/answer/80553
			if (preg_match('/'.implode("|", $this->googe_user_agent_token()).'/i', $user_agent, $matches)){
				if( $domain_1st == 'googlebot.com'
						|| $domain_2nd == 'google.com' )
				{
					$results = false;
				} else { $results = true; }
			}
			
			return $results;
		}
			
		//disable pinback
		public function disable_pingback( &$links )
		{
			$rule = $this->get_rule( 'block_pingback' , self::$profile );
			if( $rule == true ){
				
				$home = tebravo_utility::get_option( 'home' );
				foreach ( $links as $l => $link )
				{
					if ( 0 === strpos( $link, $home ) )
					{
						unset($links[$l]);
					}
				}
			}
			
		}
		
		//creating index files for non-indexed directories
		public function indexing( $profile=false, $dir=false )
		{
			if( !$profile ){$profile = self::$profile;}
			
			if( $this->get_rule( 'indexable' , $profile ) == true )
			{
				if( !$dir){$dir = ABSPATH;}
				
				if( is_dir( $dir ) ):
					$files = tebravo_dirs::read( $dir ) ;
				endif;
				
				
				if( is_array( $files ) ):
				foreach ($files as $file)
				{
					if( is_dir( $dir.'/'.$file ) )
					{
						$this->indexing( $profile, $dir.'/'.$file );
						tebravo_dirs::indexing( $dir.'/'.$file );
					}
					
				}
				endif;
			}
		}
		//take action for over load max connections
		public function max_connection_action()
		{
			$rule = self::get_rule( 'max_connection', self::$profile );
			$rule_action = self::get_rule( 'action_on_maxconnection', self::$profile );
			
			if( class_exists( 'tebravo_traffic' ) )
			{
				$traffic = new tebravo_traffic();
				$results = $traffic->session_by();
				
				if( $results != '' )
				{
					$current_sessions = $results->current_sessions;
					$start_time = $results->start_time;
					$last_active = $results->last_active;
					
					$max_time = (5*60);
					$current_connection_time = $last_active - $start_time;
					if( $current_connection_time <= $max_time
							&& $current_sessions >= $rule)
					{
						//except admin screen
						if( !is_admin() )
						{
							$reason = 'max_connection';
							$this->lockout( $rule_action, $reason );
						}
					}
				}
			}
			
		}
		//lockout IP
		public function lockout( $action, $reason )
		{
			global $wpdb;
			
			$time_blocked = time();
			$time_to_unblock = '';
			
			if( self::$block_period != 'never' )
			{
				$time_to_unblock = $time_blocked + ( self::$block_period * 60 );
			}
			
			$block_ip = $this->get_rule( 'block_ip', self::$profile );
			$block_country = $this->get_rule( 'block_country', self::$profile );
			
			$block_type = 'ipaddress';
			$blocked_ip = $this->constants( 'ip' );
			$blocked_country = '';
			$country_code = $this->constants( 'country_code' );
			$dbWhere = 'ipaddress=\''.$blocked_ip.'\'';
			if( $block_ip && $block_country )
			{
				$block_type = 'both';
				$blocked_ip = $this->constants( 'ip' );
				$blocked_country = $country_code;
				$dbWhere = 'ipaddress=\''.$blocked_ip.'\' and country_code=\''.$blocked_country.'\'';
				
			} else if( $block_country && !$block_ip )
			{
				$block_type = 'country_code';
				$blocked_country = $country_code;
				$dbWhere = 'country_code=\''.$blocked_country.'\'';
			}
			//check for whilte listed IP
			if( $blocked_ip != '' && $this->whitelisted('ipaddress', $blocked_ip) == true )
			{
				return;
			}
			//check for whilte listed country code
			if( $blocked_country != '' && $this->whitelisted('country', $blocked_country) == true )
			{
				return;
			}
			//continue
			$dbSelect = 'ipaddress';
			
			if( $block_type != 'both' )
			{
				$dbSelect = esc_html( $block_type );
			}
			
			$dbTable = tebravo_utility::dbprefix()."firewall_actions";
			$dbTable_traffic = tebravo_utility::dbprefix()."traffic";
			$row = $wpdb->get_row( "SELECT {$dbSelect} FROM {$dbTable}
			WHERE $dbWhere Limit 1");
			
			if( $row === null )
			{
				if( isset( $blocked_ip ) && 
						(!empty( $blocked_ip ) || !empty( $blocked_country )))
				{
					$wpdb->insert($dbTable, array(
							'ipaddress' => sanitize_text_field( $blocked_ip ),	
							'blocked_country' => sanitize_text_field( $blocked_country ),	
							'country_code' => sanitize_text_field( $country_code ),	
							'block_type' => sanitize_text_field( $block_type ),	
							'block_action' => sanitize_text_field( $action ),	
							'block_reason' => sanitize_text_field( $reason ),	
							'time_blocked' => sanitize_text_field( $time_blocked ),	
							'time_to_unblock' => sanitize_text_field( $time_to_unblock ),	
					));
					//delete from online visitors to reset start_time and last_active at the next time
					$wpdb->delete($dbTable_traffic, array(
							'ipaddress' => esc_html( $blocked_ip )
					));
					
					$the_message = "<strong>".__("New IP Blocked", TEBRAVO_TRANS)."</strong>: ".tebravo_agent::user_ip()."<br />";
					$the_message .= "<i>".__("Visit", TEBRAVO_TRANS).": wp-admin > Bravo Security > Firewall & Rules > Watch Log</i>";
					$the_message .= "<br />";
					$the_message .= "<table border=0 width=100% cellspacing=0>";
					$the_message .= "<tr><td style='border-bottom:solid 1px #D9D9D9; background:#F5F5F5;padding:8px;' width=25%>";
					$the_message .= __("Country", TEBRAVO_TRANS)."</td><td style='border-bottom:solid 1px #D9D9D9;'>".$this->constants( 'country_name' )."</td></tr>";
					$the_message .= "<tr><td style='border-bottom:solid 1px #D9D9D9; background:#F5F5F5;padding:8px;' width=25%>";
					$the_message .= __("ISP", TEBRAVO_TRANS)."</td><td style='border-bottom:solid 1px #D9D9D9;'>".$this->constants( 'ISP' )."</td></tr>";
					$the_message .= "<tr><td style='border-bottom:solid 1px #D9D9D9; background:#F5F5F5;padding:8px;' width=25%>";
					$the_message .= __("Current Page", TEBRAVO_TRANS)."</td><td style='border-bottom:solid 1px #D9D9D9;'>".$this->constants( 'current_page' )."</td></tr>";
					$the_message .= "<tr><td style='border-bottom:solid 1px #D9D9D9; background:#F5F5F5;padding:8px;' width=25%>";
					$the_message .= __("Device", TEBRAVO_TRANS)."</td><td style='border-bottom:solid 1px #D9D9D9;'>".$this->constants( 'device' )."</td></tr>";
					$the_message .= "<tr><td style='border-bottom:solid 1px #D9D9D9; background:#F5F5F5;padding:8px;' width=25%>";
					$the_message .= __("Browser", TEBRAVO_TRANS)."</td><td style='border-bottom:solid 1px #D9D9D9;'>".$this->constants( 'browser' )."</td></tr>";
					$the_message .= "</table>";
					
					$message = $the_message;
					if( tebravo_agent::is_bot() 
							&& self::$emailSettings['notify']['bot'] == 'checked')
					{
						$this-> send_email( __("Bot Blocked!", TEBRAVO_TRANS), $message );
					}
					
					if( !tebravo_agent::is_bot()
							&& self::$emailSettings['notify']['human'] == 'checked')
					{
						$this-> send_email( __("IP Blocked!", TEBRAVO_TRANS), $message );
					}
					//logout if is user
					if( is_user_logged_in() ){wp_logout();}
				}
			}
			
			//define action
			do_action( 'tebravo_firewall_lockout' );
		}
		//send email
		protected function send_email( $subject, $message )
		{
			tebravo_mail(
					self::$emailSettings['mail'],
					$subject,
					$message,
					false,
					false,
					self::$emailSettings['cc']
					);
		}
		//set new php settings
		public function set_php()
		{
			if( isset( $_POST['firewall_profile'] ) && in_array( $_POST['firewall_profile'] , self::$standard_profile ) )
			{
			    $level = $this->get_rule( 'php_secure_level' , sanitize_text_field($_POST['firewall_profile']) );
			} else {
				$level = $this->get_rule( 'php_secure_level' , self::$profile );
			}
			
			if( $level != '' && self::$php_security == 'checked' ):
				$settings = $this->php_security( $level );
				
				if( !empty( $level ) && is_array( $settings ) )
				{
					$htaccess_start = "#Bravo Security Start PHP Settings";
					$htaccess_end = "#Bravo Security End PHP Settings";
					$htaccess_data = '';
					$phpini_data = '';
					
					
					foreach ($settings as $key => $value)
					{
						if( $this->php_settings_device() == 'PHPINI' )
						{
							$phpini_data .=$key.'= '.$value.PHP_EOL;
						} else if( $this->php_settings_device() == 'INISET'){
							ini_set($key, $value);
						} else {
							if( $value == 'false' ){$value = 0;}
							$htaccess_data .= "php_value {$key} {$value}".PHP_EOL;
						}
					}
					
					//write to htaccess
					if( $htaccess_data != '')
					{
						include_once TEBRAVO_DIR.'/includes/tebravo.htaccess.php';
						$htaccess = new tebravo_htaccess();
						$htaccess->take_copy();
						if( self::$profile != 'disabled' )
						{
							$htaccess->replace_update($htaccess_start, $htaccess_end, $htaccess_data);
						} else {
							$htaccess->replace_update($htaccess_start, $htaccess_end, '');
						}
					}
					//write to php.ini
					if( $phpini_data != '')
					{
						if( self::$profile != 'disabled' )
						{
							tebravo_files::write(ABSPATH.'php.ini', self::$phpini_header.$phpini_data);
						} else {
							tebravo_files::write(ABSPATH.'php.ini', '');
						}
					}
				}
			endif;
		}
		
		//PHP security settings
		protected static function php_security( $level="low" )
		{
			$expose_php = "0";
			$display_startup_errors = "false";
			$display_errors= "false";
			$allow_url_include= "false";
			$html_errors= "false";
			$log_errors= "true";
			$ignore_repeated_errors= "false";
			$ignore_repeated_source= "false";
			$report_memleaks= "true";
			$track_errors= "true";
			$docref_root= "0";
			$docref_ext= "0";
			$error_reporting= "999999999";
			$log_errors_max_len= "0";
			$error_log = self::$php_errorlog;
			
			if( $level == "high" )
			{
				$settings = array(
						"expose_php" => $expose_php,
						"display_startup_errors" => $display_startup_errors,
						"display_errors" => $display_errors,
						"allow_url_include" => $allow_url_include,
						"html_errors" => $html_errors,
						"log_errors" => $log_errors,
						"ignore_repeated_errors" => $ignore_repeated_errors,
						"ignore_repeated_source" => $ignore_repeated_source,
						"report_memleaks" => $report_memleaks,
						"track_errors" => $track_errors,
						"docref_root" => $docref_root,
						"docref_ext" => $docref_ext,
						"error_reporting" => $error_reporting,
						"log_errors_max_len" => $log_errors_max_len,
						"error_log" => $error_log,
				);
			} else if( $level == "medium" )
			{
				$settings = array(
						"display_startup_errors" => $display_startup_errors,
						"display_errors" => $display_errors,
						"allow_url_include" => $allow_url_include,
						"html_errors" => $html_errors,
						"log_errors" => $log_errors,
						"ignore_repeated_errors" => $ignore_repeated_errors,
						"ignore_repeated_source" => $ignore_repeated_source,
						"report_memleaks" => $report_memleaks,
						"track_errors" => $track_errors,
						"docref_root" => $docref_root,
						"docref_ext" => $docref_ext,
				);
			} else if( $level == "low" )
			{
				$settings = array(
						"display_errors" => $display_errors,
						"allow_url_include" => $allow_url_include,
						"report_memleaks" => $report_memleaks,
						"track_errors" => $track_errors,
						"docref_root" => $docref_root,
						"docref_ext" => $docref_ext,
				);
			}
			
			if( is_array( $settings) )
			{
				return $settings;
			}
			
		}
		
		public function remove_blocked()
		{
			if( isset( $_GET['d'] ) 
					&& !empty( $_GET['_nonce'])
					&& false !== wp_verify_nonce( $_GET['_nonce'], 'remove_blocked'))
			{ 
				global $wpdb;
				$wpdb->delete( tebravo_utility::dbprefix().'firewall_actions',
						array( "id" => trim( esc_html( esc_js( $_GET['d'] ) ) ) ) );
			} 
			exit;
		}
		
		public function whitelist_blocked()
		{
			if( isset( $_GET['d'] )
					&& !empty( $_GET['_nonce'])
					&& false !== wp_verify_nonce( $_GET['_nonce'], 'whitelist_blocked'))
			{
				$firewall_whitecountries = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'firewall_whitecountries' ) ) );
				$firewall_whiteips = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'firewall_whiteips' ) ) );
				
				global $wpdb;
				$id = trim( esc_html( esc_js( $_GET['d'] ) ) );
				$dbTable = tebravo_utility::dbprefix()."firewall_actions";
				$row = $wpdb->get_row( "SELECT ipaddress,blocked_country,block_type FROM ".$dbTable." WHERE id='$id' Limit 1");
				
				if( null!==$row )
				{
					$ip = $row->ipaddress;
					$country = $row->blocked_country;
					
					$ip_exp = explode(PHP_EOL, $firewall_whiteips);
					$country_exp = explode(PHP_EOL, $firewall_whitecountries);
					
					if( !empty( $ip ) && !in_array( $ip, $ip_exp ) )
					{
						$new_whitelist_ips = $firewall_whiteips.PHP_EOL.$ip;
						tebravo_utility::update_option( TEBRAVO_DBPREFIX.'firewall_whiteips' , $new_whitelist_ips );
					}
					
					if( !empty( $country ) && !in_array( $country, $country_exp ) )
					{
						$new_whitelist_countries = $firewall_whitecountries.PHP_EOL.$country;
						tebravo_utility::update_option( TEBRAVO_DBPREFIX.'firewall_whitecountries' , $new_whitelist_countries );
					}
					
					$wpdb->delete( tebravo_utility::dbprefix().'firewall_actions',
							array( "id" => trim( esc_html( esc_js( $_GET['d'] ) ) ) ) );
				}
			}
			exit;
		}
		
		public function log_dashboard()
		{
			global $wpdb;
			
			$this->html = new tebravo_html();
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			$desc = "Log watch and control.";
			$this->html->header(__("Firewall Log", TEBRAVO_TRANS), $desc, 'firewall.png');
			
			//Tabs Data
			$tabs["firewall-settings"] = array("title"=>"Settings",
					"href"=>$this->html->init->admin_url."-firewall",
					"is_active"=> "not");
			
			$tabs["firewall-log"] = array("title"=>"Log",
					"href"=>$this->html->init->admin_url."-firewall&p=log",
					"is_active"=> 'active');
			
			$tabs["firewall-rules"] = array("title"=>"Profile Rules",
					"href"=>$this->html->init->admin_url."-firewall&p=rules",
					"is_active"=> 'not');
			
			//Tabs HTML
			$this->html->tabs($tabs);
			$this->html->start_tab_content();
			
			$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
			$output[] = "<table border='0' width=100% cellspacing=0>";
			
			$dbTable = tebravo_utility::dbprefix().'firewall_actions';
			$current_page = isset( $_GET['bravopage'] ) ? absint( $_GET['bravopage'] ) : 1;
			$limit = 10;
			$offest = (( $current_page - 1 ) * $limit);
			$total_rows = $wpdb->get_var( "SELECT count(`id`) FROM $dbTable" );
			$total_pages = ceil( $total_rows/$limit );
			
			$current_show_calc = ceil( ($limit * $current_page) );
			$current_show = $current_show_calc;
			if( $limit > $total_rows 
					|| $current_show_calc > $total_rows ) 
			{
				$current_show = $total_rows;
			}
			
			$results = $wpdb->get_results( "SELECT * FROM $dbTable ORDER BY id DESC Limit $offest,$limit" );
			$output[] = "<tr class=tebravo_headTD><td colspan=5>".__("Showing ", TEBRAVO_TRANS)." ".$current_show."/".$total_rows."</td></tr>";
			if( null!==$results )
			{
				$list = '';
				$tools = '';
				$remove_link = add_query_arg( array(
						'action' => 'remove_blocked',
						'_nonce' => wp_create_nonce( 'remove_blocked' )
				), admin_url('admin-ajax.php'));
				$whitelist_link = add_query_arg( array(
						'action' => 'whitelist_blocked',
						'_nonce' => wp_create_nonce( 'whitelist_blocked' )
				), admin_url('admin-ajax.php'));
				$js = '<script>';
				foreach ( $results as $row )
				{
					//actions
					$action = __("Slow Down", TEBRAVO_TRANS);
					$action_icon = "<font color=#E9A334>&diams;</font>";
					if( $row->block_action == 'block'){$action = __("Block", TEBRAVO_TRANS); $action_icon = "<font color=#CC2343>&diams;</font>";}
					//country flag
					$flag = '';
					if( $row->country_code !='' )
					{
						$flag = '<img src="'.plugins_url('assets/img/blank.png', TEBRAVO_PATH).'" class="tebravo_flag flag-'.strtolower($row->country_code).'" alt="'.$row->country_code.'" /> ';
					}
					//block type
					$ip_action = "<font color=green>&radic;</font>";
					$country_action = "<font color=green>&radic;</font>";
					if($row->block_type == 'ipaddress' )
					{
						$country_action= "<font color=#EB552D>&otimes;</font>";
					}if($row->block_type == 'country_code' )
					{
						$ip_action = "<font color=#EB552D>&otimes;</font>";
					}
					//date and time
					$date_time = '';
					if( !empty( $row->time_blocked ) )
					{
						$the_day = date('d M Y', $row->time_blocked);
						$today = date('d M Y');
						$yesterday = date('d M Y', (time() -(24*60*60)));
						
						$datatime = $the_day;
						if( $the_day == $today ) {$datatime = __("Today", TEBRAVO_TRANS);}
						else if( $the_day == $yesterday ) {$datatime = __("Yesterday", TEBRAVO_TRANS);}
						$date_time .= $datatime." <font color='#EB552D'>".date('h:i a', $row->time_blocked)."</font>";
					}
					//reason
					$reason = '';
					if( !empty( $row->block_reason ) )
					{
						$reason = esc_html( $row->block_reason );
					}
					//row table
					$tools = "<span style='display:none;' id='tools{$row->id}'>&nbsp;&nbsp;<span class=tebravo_breadcrumbs id='remove{$row->id}'>".__("Remove", TEBRAVO_TRANS)."</span>
 . <span class=tebravo_breadcrumbs id='whitelist{$row->id}'>".__("Whitelist", TEBRAVO_TRANS)."</span></span>";
					$list .= "<tr class='tebravo_underTD' id='list".$row->id."'><td width=35%>$flag ".esc_html( $row->ipaddress)." $tools<br />$action_icon <font color='#2D94EB'>$action</font> [ <b>$ip_action</b> ".__("IP", TEBRAVO_TRANS)."  <b>$country_action</b> ".__("Country", TEBRAVO_TRANS)." ]
							</td><td width=15%>$date_time</td><td width=25%>$reason</td></tr>";
					
					//JS
					$js .= "jQuery('#list{$row->id}').mouseover(function(){".PHP_EOL;
					$js .= "jQuery('#tools{$row->id}').show();".PHP_EOL;
					$js .= "});".PHP_EOL;
					$js .= "jQuery('#list{$row->id}').mouseout(function(){".PHP_EOL;
					$js .= "jQuery('#tools{$row->id}').hide();".PHP_EOL;
					$js .= "});".PHP_EOL;
					$js .= "jQuery('#remove{$row->id}').click(function(){".PHP_EOL;
					$js .= "jQuery('#list{$row->id}').hide();".PHP_EOL;
					$js .= "jQuery('#tebravo_results').load('{$remove_link}&d={$row->id}');".PHP_EOL;
					$js .= "});".PHP_EOL;
					$js .= "jQuery('#whitelist{$row->id}').click(function(){".PHP_EOL;
					$js .= "jQuery('#list{$row->id}').hide();".PHP_EOL;
					$js .= "jQuery('#tebravo_results').load('{$whitelist_link}&d={$row->id}');".PHP_EOL;
					$js .= "});".PHP_EOL;
				}
				$js .= "</script>";
				$output[] = $list."<div id='tebravo_results'></div>";
				$output[] = $js;
			} else {
				$output[] = "<tr><td colspan=5>".__("Log is empty!", TEBRAVO_TRANS)."</td></tr>";
			}
			
			$output[] = "</table></div>";
			$page_links = paginate_links( array(
					'base' => add_query_arg('bravopage' , '%#%'),
					'format' => '',
					'prev_text' => '&laquo; '.__("Previous", TEBRAVO_TRANS),
					'next_text' => __("Next", TEBRAVO_TRANS).' &raquo;',
					'total' => $total_pages,
					'current' => $current_page,
			));
			if ( $page_links ) {
				$output[] = '<div class="tablenav"><div class="tablenav-pages" style="margin: 1em 0;">' . $page_links . '</div></div>';
			}
			echo implode("\n", $output);
			$this->html->end_tab_content();
			$this->html->footer();
			
		}
		
		public function rules_dashboard()
		{
			//ob_start();
			$this->html = new tebravo_html();
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			$desc = "Setup & Watch your Wordpress firewall.";
			$extra = "<a href='".$this->html->init->admin_url."-firewall&p=log' class='tebravo_curved'>".__("Watch Log", TEBRAVO_TRANS)."</a>";
			$this->html->header(__("Firewall Dashboard", TEBRAVO_TRANS), $desc, 'firewall.png', $extra);
			
			//Tabs Data
			$tabs["firewall-settings"] = array("title"=>"Settings",
					"href"=>$this->html->init->admin_url."-firewall",
					"is_active"=> "not");
			
			$tabs["firewall-log"] = array("title"=>"Log",
					"href"=>$this->html->init->admin_url."-firewall&p=log",
					"is_active"=> 'not');
			
			$tabs["firewall-rules"] = array("title"=>"Profile Rules",
					"href"=>$this->html->init->admin_url."-firewall&p=rules",
					"is_active"=> 'active');
			
			//Tabs HTML
			$this->html->tabs($tabs);
			$this->html->start_tab_content();
			
			$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
			$output[] = "<table border='0' width=100% cellspacing=0>";
			if( self::$profile != 'disabled' )
			{
				$output[] = $this->profile_options_view();
			} else {
				$output[] = __("Firewall Disabled! No Profile Selected.", TEBRAVO_TRANS);
			}
			$output[] = "</table></div>";
			
			echo implode("\n", $output);
			
			$this->html->end_tab_content();
			$this->html->footer();
		}
		//dashboard //HTML
		public function dashboard()
		{
			if( empty( $_GET['p']) ) {$this->dashboard_settings();}
			else if( $_GET['p'] == 'log' ) {$this->log_dashboard();}
			else if( $_GET['p'] == 'rules' ) {$this->rules_dashboard();}
		}
		public function dashboard_settings()
		{
			//ob_start();
			$this->html = new tebravo_html();
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			$desc = "Setup & Watch your Wordpress firewall.";
			$extra = "<a href='".$this->html->init->admin_url."-firewall&p=log' class='tebravo_curved'>".__("Watch Log", TEBRAVO_TRANS)."</a>";
			$this->html->header(__("Firewall Dashboard", TEBRAVO_TRANS), $desc, 'firewall.png', $extra);
			
			//Tabs Data
			$tabs["firewall-settings"] = array("title"=>"Settings",
					"href"=>$this->html->init->admin_url."-firewall",
					"is_active"=> "active");
			
			$tabs["firewall-log"] = array("title"=>"Log",
					"href"=>$this->html->init->admin_url."-firewall&p=log",
					"is_active"=> 'not');
			
			$tabs["firewall-rules"] = array("title"=>"Profile Rules",
					"href"=>$this->html->init->admin_url."-firewall&p=rules",
					"is_active"=> 'not');
			
			//Tabs HTML
			$this->html->tabs($tabs);
			$this->html->start_tab_content();
			
			$output[] = "<form action='".$this->html->init->admin_url."-firewall' method=post>";
			$output[] = "<input type='hidden' name='_nonce' value='".$this->html->init->create_nonce('firewall-settings')."'>";
			$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
			$output[] = "<table border='0' width=100% cellspacing=0>";
			
			//profile
			$profile_options = '';
			foreach (self::$standard_profile as $profile)
			{
			    if( $profile == 'high'){$profile_options .= "<option value='medium' ";}
			    else {$profile_options .= "<option value='".$profile."' ";}
			    
				if( self::$profile == $profile ){$profile_options .= "selected";}
				if( $profile == 'high'){$profile_options .= ">".ucfirst($profile)." (".__("Pro Only", TEBRAVO_TRANS).")</option>";}
				else {$profile_options .= ">".ucfirst($profile)."</option>";}
			}
			$profile_desc = __("Choose a profile that fits your needs, There are three profiles: High, Medium and Low, If you want to disable firewall choose > Disabled.", TEBRAVO_TRANS);
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Firewall Profile (Security Level)", TEBRAVO_TRANS)."</strong></td></tr>";
			$output[] = "<tr class='tebravo_underTD'>";
			$output[] = "<td width=75%>".$profile_desc."</td>";
			$output[] = "<td><select name='firewall_profile'>".$profile_options."</select></td>";
			
			//PHP Security
			$php_security_array = array(
				__("Enabled", TEBRAVO_TRANS) => 'checked',	
				__("Disabled", TEBRAVO_TRANS) => 'no',	
			);
			$php_security_options = '';
			foreach ($php_security_array as $key => $value)
			{
				$php_security_options .= "<option value='".$value."' ";
				if( self::$php_security == $value ){$php_security_options .= "selected";}
				$php_security_options .= ">".$key."</option>";
			}
			$php_security_desc = __("PHP Security level and Firewall level will be the same .", TEBRAVO_TRANS);
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("PHP Security", TEBRAVO_TRANS)."</strong></td></tr>";
			$output[] = "<tr class='tebravo_underTD'>";
			$output[] = "<td width=75%>".$php_security_desc."</td>";
			$output[] = "<td><select name='firewall_php_security'>".$php_security_options."</select></td>";
			
			//block screen
			$block_screen_options = '';
			foreach ( array( 403, 404 ) as $screen )
			{
				$block_screen_options .= "<option value='".$screen."' ";
				if( self::$block_screen == $screen ){$block_screen_options .= "selected";}
				$block_screen_options .= ">".$screen." ".__("Page", TEBRAVO_TRANS)."</options>";
			}
			$block_screen_desc = __("The screen which visitor will see it if firewall blocked him.", TEBRAVO_TRANS);
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Block Screen (Page)", TEBRAVO_TRANS)."</strong></td></tr>";
			$output[] = "<tr class='tebravo_underTD'>";
			$output[] = "<td width=75%>".$block_screen_desc."</td>";
			$output[] = "<td><select name='firewall_block_screen'>".$block_screen_options."</select></td>";
			
			//block period
			$periods = array(
				"never" => __("Never", TEBRAVO_TRANS),
					"15" => __("15 Minutes", TEBRAVO_TRANS),
					"30" => __("30 Minutes", TEBRAVO_TRANS),
					"60" => __("60 Minutes", TEBRAVO_TRANS),
					"180" => __("3 Hours", TEBRAVO_TRANS),
					"720" => __("12 Hours", TEBRAVO_TRANS),
					"1440" => __("1 Day", TEBRAVO_TRANS)
			);
			$block_period_options = '';
			foreach ( $periods as $period => $period_value)
			{
				$block_period_options .= "<option value='".$period."' ";
				if( self::$block_period == $period ){$block_period_options .= "selected";}
				$block_period_options .= ">".$period_value."</options>";
			}
			$block_period_desc = __("Choose, when firewall will re-allow visitor (agent) to use the website.", TEBRAVO_TRANS);
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("The Ignore (block) Period", TEBRAVO_TRANS)."</strong></td></tr>";
			$output[] = "<tr class='tebravo_underTD'>";
			$output[] = "<td width=75%>".$block_period_desc."</td>";
			$output[] = "<td><select name='firewall_block_period'>".$block_period_options."</select></td>";
			
			//block message
			$firewall_block_message = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'firewall_block_message' ) ) );
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Message for blocked visitors", TEBRAVO_TRANS)."</strong></td></tr>";
			$output[] = "<tr class='tebravo_underTD'>";
			$output[] = "<td colspan=2>";
			$output[] = "<textarea name='firewall_block_message' style='width:250px; height:95px;'>".$firewall_block_message."</textarea>";
			$output[] = "<br /><font class='smallfont'>".__("NO HTML!", TEBRAVO_TRANS)."</font><br />";
			$output[] = "<font class='smallfont' style='color:brown'>".__("Will be active only if error pages option enabled", TEBRAVO_TRANS)."</font></td>";
			
			//404 white list files
			$firewall_404_whitefiles = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'firewall_404_whitefiles' ) ) );
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("404 White List Files", TEBRAVO_TRANS)."</strong></td></tr>";
			$output[] = "<tr class='tebravo_underTD'>";
			$output[] = "<td colspan=2>";
			$output[] = "<textarea name='firewall_404_whitefiles' style='width:250px; height:95px;'>".$firewall_404_whitefiles."</textarea>";
			$output[] = "<br /><font class='smallfont'>".__("one item per line", TEBRAVO_TRANS)."</font></td>";
			
			//404 white list extensions
			$firewall_404_whiteext= trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'firewall_404_whiteext' ) ) );
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("404 White List Extensions", TEBRAVO_TRANS)."</strong></td></tr>";
			$output[] = "<tr class='tebravo_underTD'>";
			$output[] = "<td colspan=2>";
			$output[] = "<textarea name='firewall_404_whiteext' style='width:250px; height:95px;'>".$firewall_404_whiteext."</textarea>";
			$output[] = "<br /><font class='smallfont'>".__("one item per line", TEBRAVO_TRANS)."</font></td>";
			
			//404 white list IPs
			$firewall_whiteips= trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'firewall_whiteips' ) ) );
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("White List IPs", TEBRAVO_TRANS)."</strong></td></tr>";
			$output[] = "<tr class='tebravo_underTD'>";
			$output[] = "<td colspan=2>";
			$output[] = "<textarea name='firewall_whiteips' style='width:250px; height:95px;'>".$firewall_whiteips."</textarea>";
			$output[] = "<br /><font class='smallfont'>".__("one item per line", TEBRAVO_TRANS)."</font></td>";
			
			//404 white list extensions
			$firewall_whitecountries= trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'firewall_whitecountries' ) ) );
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("White List Countries", TEBRAVO_TRANS)."</strong></td></tr>";
			$output[] = "<tr class='tebravo_underTD'>";
			$output[] = "<td colspan=2>";
			$output[] = "<textarea name='firewall_whitecountries' style='width:250px; height:95px;'>".$firewall_whitecountries."</textarea>";
			$output[] = "<br /><font class='smallfont'>".__("one item per line", TEBRAVO_TRANS)."<br />".__("Only country code (shorcode) like US,UK or DE", TEBRAVO_TRANS)."</font></td>";
			
			//white list bots
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("White List Bots", TEBRAVO_TRANS)."</strong> <span class='tebravo_trail'>".__("Premium", TEBRAVO_TRANS)."</td></tr>";
			$output[] = "<tr class='tebravo_underTD'>";
			$output[] = "<td colspan=2>";
			$output[] = "<textarea name='' style='width:35%; height:45px;' disabled></textarea>";
			$output[] = "<br /><font class='smallfont'>".__("seprate it by comma ','", TEBRAVO_TRANS)."<br />".__("google.com,bing.com,yahoo.com ...etc", TEBRAVO_TRANS)."</font></td>";
			
			
			$output[] = "<tr class='tebravo_underTD'><td colspan=2>";
			$output[] = $this->html->button(__("Save Settings", TEBRAVO_TRANS), 'submit');
			$output[] = "</td></tr>";
			
			$output[] = "</table></div>";
			if( !$_POST )
			{
				echo implode("\n", $output);
				
			} else {
				if( !empty( $_POST['firewall_profile'] )
						&& in_array( $_POST['firewall_profile'], self::$standard_profile )
						&& !empty( $_POST['firewall_block_screen'] )
						&& !empty( $_POST['firewall_block_period'] ) 
						&& !empty( $_POST['_nonce'] )
						&& false!== wp_verify_nonce( $_POST['_nonce'], $this->html->init->security_hash.'firewall-settings'))
				{
					tebravo_utility::update_option( TEBRAVO_DBPREFIX.'firewall_profile', trim( esc_html( $_POST['firewall_profile'] ) ) );
					tebravo_utility::update_option( TEBRAVO_DBPREFIX.'firewall_block_screen', trim( esc_html( $_POST['firewall_block_screen'] ) ) );
					tebravo_utility::update_option( TEBRAVO_DBPREFIX.'firewall_block_message', trim( esc_html( $_POST['firewall_block_message'] ) ) );
					tebravo_utility::update_option( TEBRAVO_DBPREFIX.'firewall_block_period', trim( esc_html( $_POST['firewall_block_period'] ) ) );
					tebravo_utility::update_option( TEBRAVO_DBPREFIX.'firewall_php_security', trim( esc_html( $_POST['firewall_php_security'] ) ) );
					tebravo_utility::update_option( TEBRAVO_DBPREFIX.'firewall_404_whitefiles', trim( esc_html( $_POST['firewall_404_whitefiles'] ) ) );
					tebravo_utility::update_option( TEBRAVO_DBPREFIX.'firewall_404_whiteext', trim( esc_html( $_POST['firewall_404_whiteext'] ) ) );
					tebravo_utility::update_option( TEBRAVO_DBPREFIX.'firewall_whiteips', trim( esc_html( $_POST['firewall_whiteips'] ) ) );
					tebravo_utility::update_option( TEBRAVO_DBPREFIX.'firewall_whitecountries', trim( esc_html( $_POST['firewall_whitecountries'] ) ) );
				
					$this->set_php();
					$this->indexing( trim( esc_html( $_POST['firewall_profile'] ) ) );
					
					echo "Saving ...";
					tebravo_redirect_js($this->html->init->admin_url.'-firewall&msg=01');
				} else {
					tebravo_redirect_js($this->html->init->admin_url.'-firewall&err=02');
				}
			}
			
			$this->html->end_tab_content();
			$this->html->footer();
		}
		
		public function profile_options_view( $profile=false )
		{
			if( !$profile ){$profile = self::$profile;}
			
			$red = "<img src='".plugins_url('assets/img/shield_error.png', TEBRAVO_PATH)."'>";
			$green = "<img src='".plugins_url('assets/img/ok.png', TEBRAVO_PATH)."'>";
			//profile level title
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Current Profile Options", TEBRAVO_TRANS)."</strong></td></tr>";
			//max connections
			$output[] = "<tr class='tebravo_underTD'><td><strong>".__("Level", TEBRAVO_TRANS)."</strong></td><td>".ucfirst( $profile )."</td></tr>";
			//max connections
			$max_connection = $this->get_rule('max_connection', $profile);
			$output[] = "<tr class='tebravo_underTD'><td><strong>".__("Max Connections Per IP", TEBRAVO_TRANS)."</strong></td><td>".(int)$max_connection."</td></tr>";
			//action on max connections
			$action_on_maxconnection= $this->get_rule('action_on_maxconnection', $profile);
			$output[] = "<tr class='tebravo_underTD'><td><strong>".__("Action on Max Connections", TEBRAVO_TRANS)."</strong></td><td>".strtoupper($action_on_maxconnection)."</td></tr>";
			//block_ip
			$block_ip = $this->get_rule('block_ip', $profile);
			$block_ip_icon = (isset($block_ip)? $green : $red );
			$output[] = "<tr class='tebravo_underTD'><td><strong>".__("Block IP", TEBRAVO_TRANS)."</strong></td><td>".$block_ip_icon."</td></tr>";
			//block_country
			$block_country= $this->get_rule('block_country', $profile);
			$block_country_icon = (isset($block_country)? $green : $red );
			$output[] = "<tr class='tebravo_underTD'><td><strong>".__("Block Country", TEBRAVO_TRANS)."</strong></td><td>".$block_country_icon."</td></tr>";
			//PHP Security Level
			$php_secure_level= $this->get_rule('php_secure_level', $profile);
			$output[] = "<tr class='tebravo_underTD'><td><strong>".__("PHP Security Level", TEBRAVO_TRANS)."</strong></td><td>".ucfirst($php_secure_level)."</td></tr>";
			//detect_404
			$detect_404= $this->get_rule('detect_404', $profile);
			$detect_404_icon = (isset($detect_404)? $green : $red );
			$output[] = "<tr class='tebravo_underTD'><td><strong>".__("Detect 404 Pages", TEBRAVO_TRANS)."</strong></td><td>".$detect_404_icon."</td></tr>";
			//max_404
			$max_404= $this->get_rule('max_404', $profile);
			$output[] = "<tr class='tebravo_underTD'><td><strong>".__("404 Pages Maximum Attempts", TEBRAVO_TRANS)."</strong></td><td>".(int)$max_404."</td></tr>";
			//action_on_404
			$action_on_404= $this->get_rule('action_on_404', $profile);
			$output[] = "<tr class='tebravo_underTD'><td><strong>".__("Action on 404 Maximum Attempts", TEBRAVO_TRANS)."</strong></td><td>".strtoupper($action_on_404)."</td></tr>";
			//block_xmlrpc
			$block_xmlrpc= $this->get_rule('block_xmlrpc', $profile);
			$block_xmlrpc_icon = (isset($block_xmlrpc)? $green : $red );
			$output[] = "<tr class='tebravo_underTD'><td><strong>".__("Disable XMLRPC", TEBRAVO_TRANS)."</strong></td><td>".$block_xmlrpc_icon."</td></tr>";
			//block_pingback
			$block_pingback= $this->get_rule('block_pingback', $profile);
			$block_pingback_icon = (isset($block_pingback)? $green : $red );
			$output[] = "<tr class='tebravo_underTD'><td><strong>".__("Disable Ping Back", TEBRAVO_TRANS)."</strong></td><td>".$block_pingback_icon."</td></tr>";
			//indexable
			$indexable= $this->get_rule('indexable', $profile);
			$indexable_icon = (isset($indexable)? $green : $red );
			$output[] = "<tr class='tebravo_underTD'><td><strong>".__("Create indexes for non-indexed directories", TEBRAVO_TRANS)."</strong></td><td>".$indexable_icon."</td></tr>";
			//block_bot_comments
			$block_bot_comments= $this->get_rule('block_bot_comments', $profile);
			$block_bot_comments_icon = (isset($block_bot_comments)? $green : $red );
			$output[] = "<tr class='tebravo_underTD'><td><strong>".__("Disable BOTs Comments", TEBRAVO_TRANS)."</strong></td><td>".$block_bot_comments_icon."</td></tr>";
			//block_proxy_comments
			$block_proxy_comments= $this->get_rule('block_proxy_comments', $profile);
			$block_proxy_comments_icon = (isset($block_proxy_comments)? $green : $red );
			$output[] = "<tr class='tebravo_underTD'><td><strong>".__("Disable Comments From Proxy", TEBRAVO_TRANS)."</strong></td><td>".$block_proxy_comments_icon."</td></tr>";
			//fake_google_crawles
			$fake_google_crawles= $this->get_rule('fake_google_crawles', $profile);
			$fake_google_crawles_icon = (isset($fake_google_crawles)? $green : $red );
			$output[] = "<tr class='tebravo_underTD'><td><strong>".__("Block Fake Google Crawlers and Bots", TEBRAVO_TRANS)."</strong></td><td>".$fake_google_crawles_icon."</td></tr>";
			//query_string_filter
			$query_string_filter= $this->get_rule('query_string_filter', $profile);
			$query_string_filter_icon = (isset($query_string_filter)? $green : $red );
			$output[] = "<tr class='tebravo_underTD'><td><strong>".__("Query Filtering (Protect from XSS and SQL Injection)", TEBRAVO_TRANS)."</strong></td><td>".$query_string_filter_icon."</td></tr>";
			//max_query_attempts
			$max_query_attempts= $this->get_rule('max_query_attempts', $profile);
			$output[] = "<tr class='tebravo_underTD'><td><strong>".__("Maximum Attempts for Bad Queries", TEBRAVO_TRANS)."</strong></td><td>".(int)$max_query_attempts."</td></tr>";
			//action_on_max_queryattempts
			$action_on_max_queryattempts= $this->get_rule('action_on_max_queryattempts', $profile);
			$output[] = "<tr class='tebravo_underTD'><td><strong>".__("Action on Maximum Attempts for Bad Queries", TEBRAVO_TRANS)."</strong></td><td>".strtoupper($action_on_max_queryattempts)."</td></tr>";
			//block_for_recaptcha_error
			$block_for_recaptcha_error= $this->get_rule('block_for_recaptcha_error', $profile);
			$block_for_recaptcha_error_icon = (isset($block_for_recaptcha_error)? $green : $red );
			$output[] = "<tr class='tebravo_underTD'><td><strong>".__("Block for reCAPTCHA error attempts", TEBRAVO_TRANS)."</strong></td><td>".$block_for_recaptcha_error_icon."</td></tr>";
			//max_recaptcha_attempts
			$max_recaptcha_attempts= $this->get_rule('max_recaptcha_attempts', $profile);
			$output[] = "<tr class='tebravo_underTD'><td><strong>".__("Maximum reCAPTCHA error Attempts", TEBRAVO_TRANS)."</strong></td><td>".(int)$max_recaptcha_attempts."</td></tr>";
			
			return implode("\n", $output);
		}
		
		
		
	}
	//run
	new tebravo_firewall();
}
?>