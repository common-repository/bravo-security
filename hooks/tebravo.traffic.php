<?php
/**
 * Hook: BRAVO.TRAFFIC
 * Trafic Tracker for Wordpress.
 * @since 1.0
 * Copyrights (C) 2017 Technoyer Solutions Ltd. <support@technoyer.com>
 */

//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if(!class_exists( 'tebravo_traffic' ))
{
	class tebravo_traffic
	{
		//options
		public $calc_admin_visits;
		public $block_period;
		//constructor
		public function __construct()
		{
			$this->calc_admin_visits = 'no';
			$admin_visits = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'calc_admin_visits')));
			if( !empty( $admin_visits ) )
			{
				$this->calc_admin_visits = $admin_visits;
			}
			
			$this->block_period = trim( esc_html(tebravo_utility::get_option( TEBRAVO_DBPREFIX.'firewall_block_period' ) ) );
			
			//actions and filters
			//store online
			if( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'traffic_enabled') == 'checked' )
			{
				add_action( 'wp_footer', array( $this, 'check_online' ) );
				add_action( 'tebravo_errorpages_template', array( $this, 'check_online' ) );
			}
			if( $this->calc_admin_visits == 'checked' )
			{
				add_action( 'admin_footer', array( $this, 'check_online' ) );
			}
			
			//add_action( 'init', array( $this, 'setCookie' ) );
			add_action( 'wp_ajax_tebravo_online_monitor', array( $this, 'tebravo_online_monitor' ) );
			add_action('wp_ajax_tebravo_online_table_update', array($this,'tebravo_online_table_update'));
			add_action('wp_ajax_list_online_details', array($this,'list_online_details'));
			add_action('wp_ajax_firewall_traffic_actions', array($this,'firewall_traffic_actions'));
			add_action('wp_ajax_adminsonline_update', array($this,'adminsonline_update'));
			add_action('admin_footer', array($this,'admins_online_update'));
		}
		
		
		//visitor details
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
				if( true==tebravo_agent::is_proxy() )
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
			}
			
			return $result;
		}
		//register session
		public function check_online( )
		{
			//helper to get init
			$this->helper = new tebravo_html();
			//user id if is_logged ind
			$user_id = 0;
			if( is_user_logged_in() )
			{
				$user = wp_get_current_user();
				$user_id = $user->ID;
			}
			
			if( false === strpos( $this->constants( 'current_page' ) , 'wp-cron.php' )
					&& false === strpos( $this->constants( 'current_page' ) ,  admin_url('admin-ajax.php'))):
					
					/*if( $this->calc_admin_visits != 'check')
					 {
					 if( false === strpos( $this->constants( 'current_page' ) ,  'wp-admin') )
					 {
					 return;
					 }
					 }*/
			//current session details
			$current_session = $this->session_by();
			
			$is_admin = false;
			if( is_admin() && current_user_can('manage_options'))
			{
				$is_admin = true;
			}
			//insert new (unique visitor)
			if( empty( $current_session ) )
			{
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
				
				global $wpdb;
				$wpdb->show_errors( false );
				
				//$wpdb->print_error();
				$this->insert( array(
						"ipaddress" => ($this->constants( 'ip' )),
						"userid" => ($user_id),
						"country" => ($this->constants( 'country_name' )),
						"country_code" => ($this->constants( 'country_code' )),
						"is_admin" => $is_admin,
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
				
			} else {
				//update exists visitor
				if( null!==$current_session  )
				{
					$new_session = 0;
					$currentPage = $this->session_column( 'current_page' );
					if( $currentPage )
					{
						if( $currentPage != $this->constants( 'current_page' ))
						{
							$new_session = 1;
						}
					}
					
					$current_sessions = 1;
					$currentSession = $this->session_column( 'current_sessions' );
					if( $currentSession )
					{
						$current_sessions = $currentSession + $new_session;
					}
					
					$past_sessions = @floor( $current_sessions - 1);
					if( $past_sessions < 1 )
					{
						$past_sessions = 0;
					}
					
					global $wpdb;
					$wpdb->show_errors( false );
					
					$this->update( array(
							"userid" => ($user_id),
							"is_admin" => $is_admin,
							"device" => ($this->constants( 'device' )),
							"browser" => ($this->constants( 'browser' )),
							"past_sessions" => $past_sessions,
							"current_sessions" => $current_sessions,
							"current_page" => ($this->constants( 'current_page' )),
							"last_active" => time(),
					),
							array(
									'ipaddress' => $this->constants( 'ip' )
							));
				}
				
			}
			endif;
			//define action
			do_action( 'tebravo_traffic_check_online' );
		}
		
		//extract URL to get (domain) and (query)
		protected function get_host( $url, $key=false )
		{
			$value = '';
			if( filter_var( $url, FILTER_VALIDATE_URL ) )
			{
				$parse = parse_url( $url );
				switch ($key)
				{
					case 'host':
						if( isset( $parse['host'] ) )
						{
							$value = $parse['host'];
						}
						break;
					case 'query':
						if( isset( $parse['query'] ) )
						{
							$value = $parse['query'];
						}
						break;
				}
			}
			
			return $value;
		}
		
		//insert row to database
		public function insert( $params=array())
		{
			global $wpdb;$wpdb->show_errors(false);$wpdb->insert( tebravo_utility::dbprefix()."traffic", $params );
		}
		//update row in database
		public function update( $params=array(), $where=array())
		{
			global $wpdb;$wpdb->show_errors(false);$wpdb->update( tebravo_utility::dbprefix()."traffic", $params , $where);
		}
		//return session by any column
		public function session_by( $column=false, $value=false )
		{
			global $wpdb;
			
			if( !$column )
			{
				$column = "ipaddress";
			}
			
			if( !$value )
			{
				$value = sanitize_text_field($this->constants( 'ip' ));
			}
			$wpdb->show_errors(false);
			$column = esc_html( $column );
			$value = esc_html( $value );
			
			$query = $wpdb->get_row( "SELECT * FROM " .tebravo_utility::dbprefix()."traffic
					WHERE {$column}='{$value}' " );
			if( null!== $query )
			{
				return $query;
			}
			
		}
		//get column from DB
		protected function session_column( $column=false )
		{
			global $wpdb;
			
			$wpdb->show_errors(false);
			$column = esc_html( $column );
			
			$query = $wpdb->get_row( "SELECT * FROM " .tebravo_utility::dbprefix()."traffic
					WHERE ipaddress='".$this->constants( 'ip' )."' " );
			if( null!== $query )
			{
				return $query->$column;
			}
			
			return false;
			
		}
		//dashboard //HTML
		public function dashboard()
		{
			//ob_start();
			$this->html = new tebravo_html();
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			$desc = __("Watch your online visitors and what are they doing?!.", TEBRAVO_TRANS);
			$extra = "<a href='".$this->html->init->admin_url."-traffic&p=flush' class='tebravo_curved'>".__("Flush", TEBRAVO_TRANS)."</a>";
			$this->html->header(__("Traffic Tracker (Real-time Online Visitors)", TEBRAVO_TRANS), $desc, 'traffic_tracker.png', $extra);
			
			//empty table
			if( !empty( $_GET['p']) && $_GET['p'] == 'flush' )
			{
				echo __("Loading", TEBRAVO_TRANS)."...";
				global $wpdb;
				$dbTable = tebravo_utility::dbprefix().'traffic';
				$delete = $wpdb->query("TRUNCATE TABLE `$dbTable`");
				tebravo_redirect_js( $this->html->init->admin_url.'-traffic');
				
				$this->html->footer();
				exit;
			}
			
			//update stats
			if( !empty( $_GET['p']) && $_GET['p'] == 'enable_tracker' )
			{
				echo __("Loading", TEBRAVO_TRANS)."...";
				
				if( !isset( $_GET['_nonce']) 
						|| false === wp_verify_nonce( $_GET['_nonce'], $this->html->init->security_hash.'enable-tracker') ) 
				{
					tebravo_die(true, __("Access Denied", TEBRAVO_TRANS), false, true);
				}
				
				tebravo_utility::update_option( TEBRAVO_DBPREFIX.'traffic_enabled' , 'checked' );
				tebravo_redirect_js( $this->html->init->admin_url.'-traffic&groupby=ipaddress&filterby=all&sortby=any' );
				
				$this->html->footer();
				exit;
			}
			
			//update stats
			if( !empty( $_GET['p']) && $_GET['p'] == 'disable_tracker' )
			{
				echo __("Loading", TEBRAVO_TRANS)."...";
				
				if( !isset( $_GET['_nonce'])
						|| false === wp_verify_nonce( $_GET['_nonce'], $this->html->init->security_hash.'disable-tracker') )
				{
					tebravo_die(true, __("Access Denied", TEBRAVO_TRANS), false, true);
				}
				
				tebravo_utility::update_option( TEBRAVO_DBPREFIX.'traffic_enabled' , 'no' );
				tebravo_redirect_js( $this->html->init->admin_url.'-traffic&groupby=ipaddress&filterby=all&sortby=any' );
				
				$this->html->footer();
				exit;
			}
			
			if( empty($_GET['groupby'])
					|| empty($_GET['filterby'])
					|| empty($_GET['sortby']))
			{
				echo __("Loading", TEBRAVO_TRANS)."...";
				tebravo_redirect_js( $this->html->init->admin_url.'-traffic&groupby=ipaddress&filterby=all&sortby=any');
				$this->html->footer();
				exit;
			}
			
			$select_option_css_style = "padding:4px;height:30px";
			//group by
			$groupby_array = array(__("IP", TEBRAVO_TRANS) => 'ipaddress',
					__("Country", TEBRAVO_TRANS)=> 'country_code',
					__("Current Page", TEBRAVO_TRANS)=> 'current_page');
			$current_groupby = 'ipaddress';
			if( isset($_REQUEST['groupby']) && in_array($_REQUEST['groupby'], $groupby_array))
			{
				$current_groupby = esc_html( esc_js( $_REQUEST['groupby']));
			}
			$groupby='';
			foreach ($groupby_array as $key => $value)
			{
				$groupby .= "<option value='".$value."' ";
				if( $current_groupby == $value ){$groupby .= "selected";}
				$groupby .= ">".$key."</option>";
			}
			//filter by
			$filterby_array = array(__("All Hits", TEBRAVO_TRANS) => 'all',
					__("Human Only", TEBRAVO_TRANS)=> 'human',
					__("Bot Only", TEBRAVO_TRANS)=> 'bot');
			$current_filterby= 'all';
			if( isset($_REQUEST['filterby']) && in_array($_REQUEST['filterby'], $filterby_array))
			{
				$current_filterby= esc_html( esc_js( $_REQUEST['filterby']));
			}
			$filterby='';
			foreach ($filterby_array as $key => $value)
			{
				$filterby .= "<option value='".$value."' ";
				if( $current_filterby == $value ){$filterby .= "selected";}
				$filterby .= ">".$key."</option>";
			}
			//sortby
			$sortby_array = array(__("Any", TEBRAVO_TRANS) => 'any',
					__("Total Sessions", TEBRAVO_TRANS)=> 'current_sessions',
					__("404 Attempts", TEBRAVO_TRANS)=> 'attempts_404');
			$current_sortby= 'any';
			if( isset($_REQUEST['sortby']) && in_array($_REQUEST['sortby'], $sortby_array))
			{
				$current_sortby= esc_html( esc_js( $_REQUEST['sortby']));
			}
			$sortby='';
			foreach ($sortby_array as $key => $value)
			{
				$sortby .= "<option value='".$value."' ";
				if( $current_sortby == $value ){$sortby .= "selected";}
				$sortby .= ">".$key."</option>";
			}
			
			$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
			
			//check stats
			$tracker_stats_icon = plugins_url('assets/img/050.png', TEBRAVO_PATH);
			$tracker_stats = "<i>".__("Disabled", TEBRAVO_TRANS)."</i>";
			$tracker_stats .= " [ <a href='".$this->html->init->admin_url.'-traffic&p=enable_tracker&_nonce='.$this->html->init->create_nonce('enable-tracker')."'>".__("Enable Tracker", TEBRAVO_TRANS)."</a> ]";
			if( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'traffic_enabled' ) == 'checked' )
			{
				$tracker_stats_icon = plugins_url('assets/img/ok.png', TEBRAVO_PATH);
				$tracker_stats = "<i>".__("Enabled", TEBRAVO_TRANS)."</i>";
				$tracker_stats .= " [ <a href='".$this->html->init->admin_url.'-traffic&p=disable_tracker&_nonce='.$this->html->init->create_nonce('disable-tracker')."'>".__("Disable Tracker", TEBRAVO_TRANS)."</a> ]";
			}
			
			$output[] = "<table border='0' width=100% cellspacing=0>";
			$output[] = "<tr class='tebravo_underTD'><td><img src='".$tracker_stats_icon."'> ".$tracker_stats."</td></tr>";
			$output[] = "</table>";
			
			$output[] = "<form>";
			$output[] = "<input type='hidden' name='page' value='".TEBRAVO_SLUG."-traffic'>";
			$output[] = "<table border='0' width=100% cellspacing=0>";
			$output[] = "<tr class=tebravo_headTD><td><strong>".__("Online Now", TEBRAVO_TRANS)."</strong> [ <span id='tebravo_online_now'>0</span> ]</td></tr>";
			$output[] = "<tr><td>".__("Group By", TEBRAVO_TRANS).": <select name='groupby' style='$select_option_css_style'>".$groupby."</select> ";
			$output[] = __("Filter By", TEBRAVO_TRANS).": <select name='filterby' style='$select_option_css_style'>".$filterby."</select> ";
			$output[] = __("Sort By", TEBRAVO_TRANS).": <select name='sortby' style='$select_option_css_style'>".$sortby."</select> ";
			$output[] = $this->html->button_small(__("Apply", TEBRAVO_TRANS), "submit", "apply")."<hr>";
			$output[] = "</td></tr>";
			$output[] = "</table></form>";
			
			$_groupby = (isset($_REQUEST['groupby'])?$_REQUEST['groupby']:'ipaddress');
			$_filterby = (isset($_REQUEST['filterby'])?$_REQUEST['filterby']:'');
			$_sortby= (isset($_REQUEST['sortby'])?$_REQUEST['sortby']:'start_time');
			
			$output[] = "<table border=0 width=100% cellspacing=0>";
			$output[] = "<tr><td width=65% id='online_table' valign=top>";
			$output[] = $this->list_online_table($_groupby, $_filterby, $_sortby);
			$output[] = "</td>";
			$output[] = "<td width=35% id='details' valign=top>";
			
			$output[] = "</td></tr>";
			$output[] = "</table>";
			$output[] = "</div>";
			$output[] = "<div id='tebravo_results'></div>";
			
			$ajax_url = add_query_arg( array(
					'action' => 'tebravo_online_monitor',
					'_nonce' => wp_create_nonce( 'tebravo_online_monitor' ),
					'groupby' => (isset($_REQUEST['groupby'])?$_REQUEST['groupby']:''),
					'filterby' => (isset($_REQUEST['filterby'])?$_REQUEST['filterby']:''),
					'sortby' => (isset($_REQUEST['sortby'])?$_REQUEST['sortby']:''),
			), admin_url('admin-ajax.php'));
			
			$time_update = 5000;
			if( defined( 'TEBRAVO_LIVEUPDATE_TIME' ) && TEBRAVO_LIVEUPDATE_TIME != '' )
			{
				$time_update = (int)TEBRAVO_LIVEUPDATE_TIME;
			}
			
			$js = "<script>".PHP_EOL;
			$js .= "jQuery(document).ready(function(){".PHP_EOL;
			$js .= "setTimeout(function(){";
			$js .= "tebravo_load_online();";
			$js .= "},500);".PHP_EOL;
			$js .= "setInterval(function(){";
			$js .= "tebravo_load_online();";
			$js .= "},".$time_update.");".PHP_EOL;
			$js .= "function tebravo_load_online(){".PHP_EOL;
			$js .= "jQuery('#tebravo_results').load('{$ajax_url}');".PHP_EOL;
			$js .= "}".PHP_EOL;
			$js .= "});".PHP_EOL;
			$js .= "</script>".PHP_EOL;
			echo implode("\n", $output);
			echo $js;
			
			/*$list =  $this->list_online($_groupby, $_filterby, $_sortby) ;
			 echo $list['counter']."<hr>";
			 if( null!= $list )
			 {
			 foreach ( $list['list'] as $key )
			 {
			 echo $key[$_groupby]."<br />";
			 }
			 
			 }
			 echo '<pre>';
			 var_dump( $list['list'] );
			 var_dump( $list );
			 echo '</pre>';*/
			//$out = ob_get_contents();
			//ob_end_clean();
			//echo $out;
			//ob_flush();
			
			
			$this->html->footer();
		}
		//create ajax url for admin-ajax.php
		private function ajax_url( $for )
		{
			$ajax_url_details = '';
			if( $for == 'details' )
			{
				$ajax_url_details = add_query_arg(array(
						'action' => 'list_online_details',
						'_nonce' => wp_create_nonce('list_online_details')
				), admin_url('admin-ajax.php'));
			}
			
			return $ajax_url_details;
		}
		//list online main table
		public function list_online_table($groupby, $filterby, $sortby)
		{
			$this->html = new tebravo_html();
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			global $wpdb;
			
			$list =  $this->list_online($groupby, $filterby, $sortby) ;
			
			$groupby_array = array('ipaddress', 'country_code', 'current_page');
			$sortby_array = array('start_time', 'current_sessions', 'attempts_404');
			
			if( !in_array($groupby, $groupby_array)){exit;}
			if( (isset($sortby) && $sortby!='any') && !in_array($sortby, $sortby_array)){exit;}
			
			$list_output = "<div style='min-height:300px; max-height:450px;overflow-y:scroll'><table border=0 cellspacing=0 width=100% id='tebravo_online_table'>";
			$output = "<tr><td colspan=3>".__("No Activity!", TEBRAVO_TRANS)."</td></tr>";
			$dbTable = tebravo_utility::dbprefix().'traffic';
			
			
			$js = '<script>'.PHP_EOL;
			if( null!= $list )
			{
				$output = '';
				foreach ( $list['list'] as $key )
				{
					$counter = $wpdb->get_var("SELECT COUNT(*) FROM $dbTable WHERE $groupby='".$key[$groupby]."'");
					
					//current sessions
					$row_current_sessions = $wpdb->get_results("SELECT SUM(current_sessions) as result_value FROM $dbTable WHERE $groupby='".$key[$groupby]."'");
					$current_sessions = $row_current_sessions [0]->result_value;
					//404 attemtps
					$row_404 = $wpdb->get_results("SELECT SUM(attempts_404) as result_value FROM $dbTable WHERE $groupby='".$key[$groupby]."'");
					$attempts_404 = $row_404[0]->result_value;
					
					$flag = '';
					$url = '';
					if( $groupby =='country_code' && $key[$groupby] !='' )
					{
						$flag = '<img src="'.plugins_url('assets/img/blank.png', TEBRAVO_PATH).'" class="tebravo_flag flag-'.strtolower($key[$groupby]).'" alt="'.$key[$groupby].'" /> ';
					} else if(  $groupby =='ipaddress' && $key[$groupby] !='' ){
						$row_flag = $wpdb->get_row("SELECT country_code FROM $dbTable WHERE $groupby='".$key[$groupby]."' Limit 1");
						if( null!==$row_flag )
						{
							if( $row_flag->country_code != '' )
							{
								$flag = '<img src="'.plugins_url('assets/img/blank.png', TEBRAVO_PATH).'" class="tebravo_flag flag-'.strtolower($row_flag->country_code).'" alt="'.$row_flag->country_code.'" /> ';
							}
						}
					}
					
					if( $groupby == 'current_page' )
					{
						$img = plugins_url('/assets/img/share2-16.png', TEBRAVO_PATH);
						$image = "<img src='$img'>";
						$url = "<a href='".tebravo_getDomainUrl(tebravo_selfURL())."/".$key[$groupby]."' target=_blang>$image</a>";
					}
					
					$row = $wpdb->get_row("SELECT start_time FROM $dbTable WHERE $groupby='".$key[$groupby]."' ORDER BY start_time DESC");
					$start_time = $row->start_time;
					
					if( $start_time >= (time()-(7)) )
					{
						
						$js .= 'jQuery("#tr'.$start_time.'").css("background-color", "#E9FBE9").animate({ backgroundColor: "#FFFFFF"}, 100);'.PHP_EOL;
						
					}
					
					$js .= 'jQuery("#btn'.$start_time.'").click(function(){';
					$js .= 'jQuery("#details").html("'.__("Loading", TEBRAVO_TRANS).'...");';
					$js .= 'jQuery("#details").load("'.$this->ajax_url( 'details' ).'&k='.$groupby.'&v='.$key[$groupby].'");';
					$js .= '});';
					$tools = $this->html->button_small(__(">>", TEBRAVO_TRANS), 'button', 'btn'.$start_time);
					$output .= "<tr class='tebravo_underTD list".$start_time."' id='tr$start_time'><td width=5%>".$counter."</td>";
					$output .= "<td>$flag <strong>".$key[$groupby]."</strong> $url";
					if( $groupby != 'current_page' ){
						$output .= "<table border=0 width=100%><tr class='tebravo_headTD'><td>";
						$output .= __("Sessions", TEBRAVO_TRANS)." <font color=blue>".$current_sessions."</font>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;";
						$output .= __("404 Pages", TEBRAVO_TRANS)." <font color=brown>".$attempts_404."</font>";
						$output .= "</td></tr></table>";
					}
					$output .= "</td><td width=12%>".$tools."</td></tr>";
				}
				
				
			}
			$list_output .= $output. "</table></div>";
			$js .= '</script>'.PHP_EOL;
			return $list_output.$js;
			exit;
		}
		//list online details for ajax window
		public function list_online_details()
		{
			$this->html = new tebravo_html();
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			ob_start();
			//verify nonce
			if( empty( $_GET['_nonce'])
					|| false === wp_verify_nonce( $_GET['_nonce'], 'list_online_details'))
			{
				exit;
			}
			//verify params
			$keys_array = array( 'ipaddress', 'country_code', 'current_page' );
			$key = trim( esc_html( esc_js( $_GET['k'] ) ) );
			$value = trim( esc_html( esc_js( $_GET['v'] ) ) );
			
			//
			$firewall_ajax_url = add_query_arg(array(
					'action' => 'firewall_traffic_actions',
					'_nonce' => wp_create_nonce( 'firewall_traffic_actions' )
			), admin_url('admin-ajax.php'));
			if( in_array( $key, $keys_array ) )
			{
				$country_code = ''; $country = ''; $where = '';
				if( $key == 'ipaddress' && filter_var($value, FILTER_VALIDATE_IP) )
				{
					$country_code = tebravo_agent::ip2country($value);
					$country = tebravo_agent::ip2country($value, "country_name");
					$where = 'where ipaddress=\''.$value.'\'';
				} else if ( $key == 'country_code' )
				{
					$country_code = $value;
					$countries = tebravo_countries::$countries;
					$country = $countries[$country_code];
					$where = 'where country_code=\''.$value.'\'';
				}
				
				$flag = '';
				if( $country_code != '')
				{
					$flag = '<img src="'.plugins_url('assets/img/blank.png', TEBRAVO_PATH).'" class="tebravo_flag flag-'.strtolower($country_code).'" alt="'.$country_code.'" /> ';
				}
				
				$url = '';
				$img = plugins_url('/assets/img/share2-16.png', TEBRAVO_PATH);
				$image = "<img src='$img'>";
				if( $key == 'current_page' )
				{
					$url = "<a href='http://".tebravo_getDomainUrl(tebravo_selfURL())."/".$value."' target=_blang>$image</a>";
					$where = 'where current_page=\''.$value.'\'';
				}
				
				if( $where != '' ):
				global $wpdb;
				$dbTable = tebravo_utility::dbprefix().'traffic';
				$results = $wpdb->get_results("SELECT * FROM $dbTable $where");
				if( null!==$results ):
				//current sessions
				$row_current_sessions = $wpdb->get_results("SELECT SUM(current_sessions) as result_value FROM $dbTable $where");
				$current_sessions = $row_current_sessions [0]->result_value;
				//404 attemtps
				$row_404 = $wpdb->get_results("SELECT SUM(attempts_404) as result_value FROM $dbTable $where");
				$attempts_404 = $row_404[0]->result_value;
				$sessions = $current_sessions;
				
				$output[] = "<div style='overflow-y:auto; max-height:300px;position:fixed;background:#fff;min-width:25%;max-width:32%;width:auto;'>";
				$output[] = "<table border=0 width=100% cellspacing=0>";
				$output[] = "<tr><td colspan=3>".$flag.$url." <strong>".$value."</strong> <i>".$country."</i><br />";
				$output[]= __("Sessions", TEBRAVO_TRANS)." [$sessions] ";
				if( $key != 'current_page' ){$output[]= __("404", TEBRAVO_TRANS)." [$attempts_404] ";}
				$output[]= "<hr></td></tr>";
				$list = '';
				$firewall_tools = '';
				$firewall_status = '';
				$firewall_js = '';
				$past_sessions = 0;
				foreach ($results as $c )
				{
					$userid = $c->userid;
					$sessions+= (is_numeric($c->current_sessions))?$c->current_sessions:0;
					$past_sessions += (is_numeric($c->past_sessions))?$c->past_sessions:0;
					$ipaddress = $c->ipaddress;
					$countryCode = $c->country_code;
					$countryName = $c->country;
					$start_time = $c->start_time;
					$last_active = $c->last_active;
					$current_page = urldecode($c->current_page);
					$attempts_404 += (is_numeric($c->attempts_404))?$c->attempts_404:0;;
					$device = $c->device;
					$browser= $c->browser;
					$is_bot = $c->is_bot;
					
					$username = '';
					$displayname = '';
					if( $userid != '' && $userid > 0)
					{
						$user = get_user_by('ID', $userid);
						$displayname = '<a href="user-edit.php?user_id='.$userid.'">'.$user->display_name.'</a>';
						$username.= "<tr class='tebravo_underTD'>";
						$username.= "<td width='17'><img src='".plugins_url('/assets/img/isuser-16.png', TEBRAVO_PATH)."'></td>";
						$username.= "<td>".$displayname." </td>";
						$username.= "</tr>";
					}
					
					$firewall = new tebravo_firewall();
					if( $key == 'country_code' )
					{
						if( $is_bot == 'true' ){$icon = 'Spider-16';} else {$icon = 'user-16';}
						$list .= "<tr class='tebravo_underTD'>";
						$list .= "<td width='17'><img src='".plugins_url('/assets/img/'.$icon.'.png', TEBRAVO_PATH)."'></td>";
						$list .= "<td>".$ipaddress." $displayname<br /><font class='smallfont'>".$current_page."</font></td>";
						$list .= "</tr>";
						$firewall_status = '';
						if( $firewall->is_locked_country( $value ) == true )
						{
							$firewall_status.= "<tr class='tebravo_underTD'>";
							$firewall_status.= "<td width='17'><img src='".plugins_url('/assets/img/blocked.png', TEBRAVO_PATH)."'></td>";
							$firewall_status.= "<td>".__("Country Blocked", TEBRAVO_TRANS)." </td>";
							$firewall_status.= "</tr>";
							
							$firewall_tools = $this->html->button_small(__("Unblock", TEBRAVO_TRANS), "button", "unblockcountry-".$country_code);
							$firewall_js .= "jQuery('#unblockcountry-".$country_code."').click(function(){";
							$firewall_js .= "jQuery('#details_actions').load('".$firewall_ajax_url."&act=unblockcountry&t=".$country_code."');";
							$firewall_js .= "});";
						} else {
							$firewall_tools = $this->html->button_small(__("Block", TEBRAVO_TRANS), "button", "blockcountry-".$country_code);
							$firewall_js .= "jQuery('#blockcountry-".$country_code."').click(function(){";
							$firewall_js .= "jQuery('#details_actions').load('".$firewall_ajax_url."&act=blockcountry&t=".$country_code."');";
							$firewall_js .= "});";
						}
					} else if( $key == 'ipaddress' )
					{
						if( $is_bot == 'true' ){$icon = 'Spider-16';} else {$icon = 'user-16';}
						$the_url = "<a href='http://".tebravo_getDomainUrl(tebravo_selfURL())."/".$current_page."' target=_blang>$image</a>";
						$list .= "<tr class='tebravo_underTD'>";
						$list .= "<td width='17'><img src='".plugins_url('/assets/img/'.$icon.'.png', TEBRAVO_PATH)."'></td>";
						$list .= "<td>".$current_page." $the_url";
						$list .= "<br />".__("Past Sessions", TEBRAVO_TRANS).": <font color=blue>".(int)$past_sessions."</font></td>";
						$list .= "</tr>";
						$ip_id = str_replace(".","",$ipaddress);
						if( $firewall->is_locked_ip( $ipaddress ) == true )
						{
							$firewall_status.= "<tr class='tebravo_underTD'>";
							$firewall_status.= "<td width='17'><img src='".plugins_url('/assets/img/blocked.png', TEBRAVO_PATH)."'></td>";
							$firewall_status.= "<td>".__("IP Blocked", TEBRAVO_TRANS)." </td>";
							$firewall_status.= "</tr>";
							
							$firewall_tools = $this->html->button_small(__("Unblock", TEBRAVO_TRANS), "button", "unblockip-".$ip_id);
							$firewall_js .= "jQuery('#unblockip-".$ip_id."').click(function(){";
							$firewall_js .= "jQuery('#details_actions').load('".$firewall_ajax_url."&act=unblockip&t=".$ipaddress."');";
							$firewall_js .= "});";
						} else {
							$firewall_tools = $this->html->button_small(__("Block", TEBRAVO_TRANS), "button", "blockip-".$ip_id);
							$firewall_js .= "jQuery('#blockip-".$ip_id."').click(function(){";
							$firewall_js .= "jQuery('#details_actions').load('".$firewall_ajax_url."&act=blockip&t=".$ipaddress."');";
							$firewall_js .= "});";
						}
						
						$list .= $username;
						//ISP
						$list .= "<tr class='tebravo_underTD'>";
						$list .= "<td width='17'><img src='".plugins_url('/assets/img/Streamline-08-16.png', TEBRAVO_PATH)."'></td>";
						$list .= "<td>".__("Network (ISP)", TEBRAVO_TRANS);
						$list .= "<br />".tebravo_agent::ISP($ipaddress)."</td>";
						$list .= "</tr>";
						//DEVICE
						$list .= "<tr class='tebravo_underTD'>";
						$list .= "<td width='17'><img src='".plugins_url('/assets/img/computer-16.png', TEBRAVO_PATH)."'></td>";
						$list .= "<td>".__("Device", TEBRAVO_TRANS);
						$list .= "<br />".$device."</td>";
						$list .= "</tr>";
						//BROWSER
						$list .= "<tr class='tebravo_underTD'>";
						$list .= "<td width='17'><img src='".plugins_url('/assets/img/internet_explorer-16.png', TEBRAVO_PATH)."'></td>";
						$list .= "<td>".__("Browser", TEBRAVO_TRANS);
						$list .= "<br />".$browser."</td>";
						$list .= "</tr>";
					} else if( $key == 'current_page' )
					{
						if( $is_bot == 'true' ){$icon = 'Spider-16';} else {$icon = 'user-16';}
						$list .= "<tr class='tebravo_underTD'>";
						$list .= "<td width='17'><img src='".plugins_url('/assets/img/'.$icon.'.png', TEBRAVO_PATH)."'></td>";
						$list .= "<td>$flag ".$ipaddress." <i>$country_code</i> $displayname</td>";
						$list .= "</tr>";
						//$list .= $username;
						
					}
				}
				
				$refresh = $this->html->button_small(__("Refresh", TEBRAVO_TRANS), "button", "refresh");
				$refresh.= "<script>jQuery('#refresh').click(function(){";
				$refresh.= "jQuery('#details').html('".__("Loading",TEBRAVO_TRANS)."...');";
				$refresh.= "jQuery('#details').load('".$this->ajax_url('details')."&k=".$key."&v=".$value."');";
				$refresh.= "});</script>";
				$output[] = $list;
				$output[] = $firewall_status;
				$output[] = "<tr><td colspan=2>".$firewall_tools.$refresh."</td></tr>";
				$output[] = "</table>";
				$output[] = "<div id='details_actions'></div></div>";
				
				echo implode("\n", $output);
				echo '<script>'.$firewall_js.'</script>';
				endif;
				endif;
			}
			$out = @ob_get_contents();
			@ob_end_clean();
			echo $out;
			exit;
		}
		
		//take actions for firewall
		public function firewall_traffic_actions()
		{
			$this->html = new tebravo_html();
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			if( empty( $_GET['_nonce'])
					|| false === wp_verify_nonce( $_GET['_nonce'], 'firewall_traffic_actions')
					|| empty( $_GET['act'])
					|| empty( $_GET['t']))
			{
				exit;
			}
			
			global $wpdb;
			$wpdb->show_errors(false);
			$dbTable = tebravo_utility::dbprefix().'firewall_actions';
			$action = trim( esc_html( esc_js( $_GET['act'] ) ) );
			$target = trim( esc_html( esc_js( $_GET['t'] ) ) );
			
			
			$result = 0;
			if( $action == 'blockip'
					&& filter_var( $target, FILTER_VALIDATE_IP) )
			{
				$this->custom_lockout( $target, false, tebravo_agent::ip2country($target), 'ipaddress' );
				$result = 1;
				$key = 'ipaddress';
			} else if( $action == 'unblockip'
					&& filter_var( $target, FILTER_VALIDATE_IP) )
			{
				$wpdb->delete($dbTable, array("ipaddress" => $target));
				$result = 1;
				$key = 'ipaddress';
			} else if( $action == 'blockcountry' )
			{
				$this->custom_lockout( false, $target, $target, 'country_code' );
				$result = 1;
				$key = 'country_code';
			} else if( $action == 'unblockcountry' )
			{
				$wpdb->delete($dbTable, array("blocked_country" => $target));
				$result = 1;
				$key = 'country_code';
			}
			
			if( $result > 0 )
			{
				$msg =  "<center><font color=green>".__("Your action successfully complete.", TEBRAVO_TRANS)."</font>";
				$msg .= "<br>".__("Loading", TEBRAVO_TRANS)."...</center>";
				echo  "<script>";
				echo "jQuery('#details').html('".$msg."');";
				echo "setTimeout(function(){";
				echo "jQuery('#details').load('".$this->ajax_url( 'details' )."&k=$key&v=$target');";
				echo "},1000);";
				echo '</script>';
			}
			exit;
		}
		
		//lockout IP or Country
		protected function custom_lockout($blocked_ip=false, $blocked_country=false, $country_code=false, $block_type=false)
		{
			global $wpdb;
			$wpdb->show_errors(false);
			$dbTable = tebravo_utility::dbprefix().'firewall_actions';
			
			$time_blocked = time();
			$time_to_unblock = '';
			
			if( $this->block_period != 'never' )
			{
				$time_to_unblock = $time_blocked + ( $this->block_period * 60 );
			}
			
			$wpdb->insert($dbTable, array(
					'ipaddress' => sanitize_text_field( $blocked_ip ),
					'blocked_country' => sanitize_text_field( $blocked_country ),
					'country_code' => sanitize_text_field( $country_code ),
					'block_type' => sanitize_text_field( $block_type ),
					'block_action' => 'block',
					'block_reason' => 'blocked_by_admin',
					'time_blocked' => $time_blocked,
					'time_to_unblock' => sanitize_text_field( $time_to_unblock ),
			));
		}
		
		//list online array
		protected function list_online( $groupby=false, $filterby=false, $sortby=false)
		{
			global $wpdb;
			$wpdb->show_errors(false);
			$dbTable = tebravo_utility::dbprefix().'traffic';
			
			$distinct_array = array('ipaddress', 'country_code', 'current_page');
			$sortby_array = array('start_time', 'current_sessions', 'attempts_404');
			
			if( !$groupby || !in_array($groupby, $distinct_array)){$groupby = 'ipaddress';}
			if( !$sortby || !in_array($sortby, $sortby_array)){$sortby = 'start_time';}
			
			$filterby = ($filterby!='all')?$filterby:'';
			
			//WHERE
			$where = '';
			if( $filterby != '' )
			{
				switch ($filterby)
				{
					case 'human':
						$where = 'and is_bot=\'false\'';
						break;
					case 'bot':
						$where = 'and is_bot=\'true\'';
						break;
					default: $where='';
				}
			}
			//DISTINCT
			$distinct = '*';
			$distinct_array = array('ipaddress', 'country_code', 'current_page');
			if( $groupby != '' && in_array($groupby, $distinct_array))
			{
				$distinct = 'distinct '.$groupby;
			}
			//echo $distinct;
			//ORDER BY
			$order_by = 'start_time';
			$sortby_array = array('start_time', 'current_sessions', 'attempts_404');
			if( $sortby != '' && in_array($sortby, $sortby_array))
			{
				$order_by = 'ORDER BY '.$sortby.' DESC';
			}
			
			//QUERY
			$query = "SELECT {$distinct} FROM {$dbTable} WHERE is_admin!='1' {$where} {$order_by}";
			$results = $wpdb->get_results( $query );
			
			$output['counter'] = '0';
			$output['list'] = '';
			$attempts_404 = '0';
			if( null!==$results )
			{
				$output['counter'] = $wpdb->get_var("SELECT count(*) FROM $dbTable {$dbTable} WHERE is_admin!='1' {$where} {$order_by}");
				$list = array();
				$i=0;
				foreach ($results as $row)
				{
					$i++;
					if( $distinct != '*') {
						$list[$i][$groupby] = esc_html( esc_js( $row->$groupby) );
						$list[$i]['404'] = $this->get_online_details($groupby, 'attempts_404');
						$list[$i]['current_sessions'] = $this->get_online_details($groupby, 'current_sessions');
						$list[$i]['past_sessions'] = $this->get_online_details($groupby, 'past_sessions');
					} else {
						$list = $row;
					}
					
				}
				$output['list'] = $list;
			}
			
			return $output;
		}
		
		//traffic tracker monitor
		public function tebravo_online_monitor()
		{
			global $wpdb;
			$wpdb->show_errors(false);
			if( empty( $_GET['_nonce'])
					|| false === wp_verify_nonce($_GET['_nonce'], 'tebravo_online_monitor'))
			{
				exit;
			}
			
			$groupby = (isset($_REQUEST['groupby'])?$_REQUEST['groupby']:'ipaddress');
			$filterby = (isset($_REQUEST['filterby'])?$_REQUEST['filterby']:'all');
			$sortby= (isset($_REQUEST['sortby'])?$_REQUEST['sortby']:'start_time');
			
			$data = $this->list_online($groupby, $filterby, $sortby);
			
			
			$ajax_url = add_query_arg( array(
					'action' => 'tebravo_online_table_update',
					'_nonce' => wp_create_nonce( 'tebravo_online_table_update' ),
					'groupby' => $groupby,
					'filterby' => $filterby,
					'sortby' => $sortby,
			), admin_url('admin-ajax.php'));
			
			
			?>
			<script>
			jQuery('#tebravo_online_now').html('<?php echo (int)$data['counter'];?>');
			//jQuery('#tebravo_online_table > tr :id[value="0"]').remove();
			jQuery("#tr0").hide(200);
			jQuery("#online_table").load('<?php echo $ajax_url;?>');
			</script>
			<?php 
		}
		
		//list online table for ajax
		public function tebravo_online_table_update()
		{
			if( empty( $_GET['_nonce'])
					|| false === wp_verify_nonce($_GET['_nonce'], 'tebravo_online_table_update'))
			{
				exit;
			}
			$groupby = (isset($_REQUEST['groupby'])?$_REQUEST['groupby']:'ipaddress');
			$filterby = (isset($_REQUEST['filterby'])?$_REQUEST['filterby']:'all');
			$sortby= (isset($_REQUEST['sortby'])?$_REQUEST['sortby']:'start_time');
			@ob_start();
			$list = $this->list_online_table($groupby, $filterby, $sortby);
			echo $list;
			$out = @ob_get_contents();
			@ob_end_clean();
			echo $out;
			
			@ob_flush();
			exit;
		}
		
		//item details for ajax right window
		private function get_online_details( $column, $target, $where=false )
		{
			global $wpdb;
			$wpdb->show_errors(false);
			$dbTable = tebravo_utility::dbprefix().'traffic';
			$columns_array = array('ipaddress', 'country_code', 'current_page', 'start_time', 'last_active');
			
			if( !in_array( $column, $columns_array)){return;}
			
			$dbWhere = '';
			if( $where ){$dbWhere = 'WHERE '.$where;}
			$results = $wpdb->get_row( "SELECT $target FROM $dbTable $dbWhere" );
			if( null!==$results )
			{
				return $results->$target;
			}
		}
		
		public function admins_online_update()
		{
			global $wpdb;
			$wpdb->show_errors(false);
			$helper = new tebravo_html();
			
			$ajax_url = add_query_arg( array(
					'action' => 'adminsonline_update',
					'_nonce' => wp_create_nonce('adminsonline_update')
			), admin_url('admin-ajax.php'));
			
			
			$admins_online_option = trim( esc_html( esc_js( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'admins_online' ) ) ) );
			if( $admins_online_option == 'checked'
					|| (isset( $_GET['action'] )
							&& $_GET['action'] == 'enable_adminsonline'
							&& ( isset( $_GET['_nonce'] )
									&& false !== wp_verify_nonce($_GET['_nonce'], $helper->init->security_hash.'enable-adminsonline'))
							))
			{
				if( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'admins_online') == 'no' )
				{
					echo __("Option Disabled", TEBRAVO_TRANS); exit;
				}
				?>
				<script>
				jQuery("#admins_online").load('<?php echo $ajax_url;?>');
				</script>
				<?php 
				
				$this->check_online();
			}
			
		}
		
		public function adminsonline_update()
		{
			global $wpdb;
			$wpdb->show_errors(false);
			if( !isset($_GET['_nonce'])
					|| false===wp_verify_nonce($_GET['_nonce'], 'adminsonline_update')){exit;}
			
			$dbTable = tebravo_utility::dbprefix().'traffic';
			$results = $wpdb->get_results("SELECT ipaddress,userid,country_code,browser,device,current_page FROM $dbTable WHERE is_admin='1' and userid>0");
			
			$output = "<i>".__("No Data, Try Refresh", TEBRAVO_TRANS)."</i>";
			if( null!=$results )
			{
				$output = "<table border=0 width=100% cellspacing=0>";
				foreach ( $results as $admin )
				{
					$ipaddress = trim( esc_html( esc_js( $admin->ipaddress)));
					$userid = trim( esc_html( esc_js( $admin->userid)));
					$country_code = trim( esc_html( esc_js( $admin->country_code)));
					$browser = trim( esc_html( esc_js( $admin->browser)));
					$device= trim( esc_html( esc_js( $admin->device)));
					$current_page= trim( esc_html( esc_js( $admin->current_page)));
					$flag = '<img src="'.plugins_url('assets/img/blank.png', TEBRAVO_PATH).'" class="tebravo_flag flag-'.strtolower($country_code).'" alt="'.$country_code.'" /> ';
					
					if( $userid > 0){
						$you = '';
						$user = wp_get_current_user();
						if( $userid == $user->ID ){$you = " [<span class='tebravo_breadcrumbs'>".__("You", TEBRAVO_TRANS)."</span>]";}
						$exp_page = explode("wp-admin", $current_page);
						$href = admin_url().$exp_page[1];
						#$href = str_replace("//", "/", $href);
						$user = get_user_by('ID', $userid);
						$output .= "<tr class='tebravo_underTD'><td><strong>".$user->display_name."</strong>$you <br />".$flag.$ipaddress."<br /><font class='smallfont'>".$browser." . ".$device."</font><br />";
						$output .= "<font class='smallfont' style='color:#76C4FA'><a href='".$href."' target=_blank>".tebravo_shorten_url($current_page)."</a></font>";
						$output .= "</td></tr>";
					}
				}
				
				$output .= "</table>";
			}
			
			echo $output;
			exit;
		}
		
		
	}
	//run
	new tebravo_traffic();
}