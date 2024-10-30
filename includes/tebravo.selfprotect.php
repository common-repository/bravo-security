<?php
/**
 * SELF PROTECT TOOL
 * @since 1.0
 * Copyrights (C) 2017 Technoyer Solutions Ltd. <support@technoyer.com>
 */

//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !class_exists( 'tebravo_selfprotect' ) )
{
	class tebravo_selfprotect
	{
		protected $page_slug, $length;
		private $url;
		
		public function __construct()
		{
			$this->page_slug = 'bravo-security';
			$this->length= strlen( $this->page_slug );
			if( isset($_REQUEST['page']) && !empty($_REQUEST['page']))
			{
				$this->url = trim( esc_html( esc_js( $_REQUEST['page'] ) ) );
			}
			
			if( defined( 'TEBRAVO_SELFP' ) && TEBRAVO_SELFP == true )
			{
			    add_action('admin_init', array($this, 'xss_protect'));
			    add_action('admin_head', array($this, 'check_admins'));
			    add_action('admin_head', array($this, 'password_protection'));
			    add_action('admin_notices', array($this, 'check_browser_security'));
			}
		}
		
		//browser versions
		public function check_browser_security()
		{
			$browser = tebravo_agent::getBrowser();
			
			$message = ''; $class = '';
			if( $browser['name'] == 'Chrome' && version_compare($browser['version'], '49.0.2623.112', '<=') )
			{
				$class = 'notice tebravo_notice_error';
				$message = __("You are using an old version of ".$browser['name'], TEBRAVO_TRANS).". ";
				$message .= __("This may cause problems for you, Please update your browser now.", TEBRAVO_TRANS);
				
			} else if( $browser['name'] == 'Firefox' && version_compare($browser['version'], '50.0', '<=') )
			{
				$class = 'notice tebravo_notice_error';
				$message = __("You are using an old version of ".$browser['name'], TEBRAVO_TRANS).". ";
				$message .= __("This may cause problems to you, Please update your browser now.", TEBRAVO_TRANS);
				
			} else if( $browser['name'] == 'Opera' && version_compare($browser['version'], '36.0.2130.75', '<=') )
			{
				$class = 'notice tebravo_notice_error';
				$message = __("You are using an old version of ".$browser['name'], TEBRAVO_TRANS).". ";
				$message .= __("This may cause problems to you, Please update your browser now.", TEBRAVO_TRANS);
				
			}
			
			
			if( $message!='' && $class !='' )
			{
				printf( '<div class="%1$s"><strong>Bravo Alerts</strong><br /><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) ); 
			}
		}
		
		//protect BRAVO pages in wp-admin
		//protect from remote injection and bad query strings
		public function xss_protect()
		{
			if( !empty( $this->url ) ):
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
				
				if( false!==strpos( $current_uri, 'admin.php' ) && substr($this->url, 0, $this->length) == $this->page_slug)
				{
					if( tebravo_agent::is_bot() ) {wp_die(__(TEBRAVO_NO_ACCESS_MSG));}
					
					if( preg_match('/'.implode("|", tebravo_query_filter::$bad_queries_uri).'/i', $current_uri)
							|| preg_match('/'.implode("|", tebravo_query_filter::$bad_queries).'/i', $current_query_string)
							|| preg_match('/'.implode("|", tebravo_query_filter::$bad_user_agents).'/i', $current_user_agent)
							)
					{
						$message = __("Access Denied", TEBRAVO_TRANS);
						if( defined('TEBRAVO_NO_ACCESS_MSG') )
						{
							$message = __(TEBRAVO_NO_ACCESS_MSG, TEBRAVO_TRANS);
						}
						wp_die($message);
					}
				}
				
				
			endif;
		}
		
		//password protection
		public function password_protection()
		{
			$helper = new tebravo_html();
			
			$protect_plugin = trim( esc_html( esc_js( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'protect_plugin') ) ) );
			$password = trim( esc_html( esc_js( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'password') ) ) );
			
			if( $password=='' ){return;}
			
			//URI
			$current_uri = '';
			if( isset( $_SERVER['REQUEST_URI'] ) && !empty( $_SERVER['REQUEST_URI'] ) )
			{
				$current_uri = $_SERVER['REQUEST_URI'];
			}
			//return nothing if it is the login page
			if( isset($_GET['page']) 
					&& $_GET['page'] == $this->page_slug.'-wadmin'
					&& isset( $_GET['p'])
					&& $_GET['p'] == 'login'){return;}
			//check session
			if( false!==strpos( $current_uri, 'admin.php' ) && substr($this->url, 0, $this->length) == $this->page_slug)
			{
				//session_start();
				if( $protect_plugin != 'checked' )
				{
					return;
				}
					if( isset( $_COOKIE['bravo_admin_session'] )
							&& !empty( $_COOKIE['bravo_admin_session'] ))
					{
						$user = wp_get_current_user();
						$user_id = $user->ID;
						$user_email = $user->user_email;
						
						$encoded_str = tebravo_encodeString($user_id.$user_email, $helper->init->security_hash);
						if( $_COOKIE['bravo_admin_session'] == $encoded_str ){return;}
					}
				
				//print login box
				$helper->plugin_login();
			}
			
		}
		
		//check admins permissions
		public function check_admins()
		{
			$helper = new tebravo_html();
			$protect_plugin = trim( esc_html( esc_js( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'protect_plugin') ) ) );
			$admins = trim( esc_html( esc_js( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'admins') ) ) );
			
			//URI
			$current_uri = '';
			if( isset( $_SERVER['REQUEST_URI'] ) && !empty( $_SERVER['REQUEST_URI'] ) )
			{
				$current_uri = $_SERVER['REQUEST_URI'];
			}
			
			if( false!==strpos( $current_uri, 'admin.php' ) && substr($this->url, 0, $this->length) == $this->page_slug)
			{
				if( $protect_plugin == 'checked' && !empty( $admins ) )
				{
					$user = wp_get_current_user();
					$exp_admins = explode(",", $admins);
					
					if( !in_array( $user->ID, $exp_admins ) )
					{
						if( defined('TEBRAVO_NO_ACCESS_MSG'))
						{
							echo "<center><h2>".TEBRAVO_NO_ACCESS_MSG."</h2>";
						}
						echo "<br />";
						echo "<a href='".admin_url()."'>← ".__("Dashboard", TEBRAVO_TRANS)."</a>";
						echo "</center>";
						wp_die();
					}
				}
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
	}
	//run
	new tebravo_selfprotect();
}
?>