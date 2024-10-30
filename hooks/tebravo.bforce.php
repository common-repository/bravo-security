<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !class_exists( 'tebravo_bforce' ) )
{
	class tebravo_bforce{
        
    	public 
	    	$status,
	    	$login_by,
	    	$enforce_strongpasswords,
	    	$max_login_attemps_ip,
	    	$max_login_attemps_user,
	    	$max_forgot_attemps,
	    	$time_before_unblock,
	    	$blocked_usernames_login,
	    	$blocked_usernames_register,
	    	$blocked_email_hosts,
	    	$blocked_countries,
	    	$blocked_countries_expect_ips,
	    	$moderate_new_members,
	    	$min_username,
	    	$max_username,
	    	$strong_pwd_string = '$\S*(?=\S{8,})(?=\S*[a-z])(?=\S*[A-Z])(?=\S*[\d])(?=\S*[\W])\S*$',
	    	$block_users_string,
	    	$html;
    	
	    	
	    //constructor
	    public function __construct()
	    {
	    	if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'bruteforce_protection'))) == 'checked')
	    	{
	    		$this->status = 'enabled';
	    	} else { $this->status = 'disabled';}
	    	
	    	$this->login_by = trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'login_by')));
	    	$this->enforce_strongpasswords= trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'enforce_strongpasswords')));
	    	$this->max_login_attemps_ip= intval(trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'max_login_attemps_ip'))));
	    	$this->max_login_attemps_user= intval(trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'max_login_attemps_user'))));
	    	$this->max_forgot_attemps= intval(trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'max_forgot_attemps'))));
	    	$this->time_before_unblock= intval(trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'time_before_unblock'))));
	    	$this->blocked_usernames_login= trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'blocked_usernames_login')));
	    	$this->blocked_usernames_register= trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'blocked_usernames_register')));
	    	$this->blocked_email_hosts= trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'blocked_email_hosts')));
	    	$this->blocked_countries= trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'blocked_countries')));
	    	$this->blocked_countries_expect_ips= trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'blocked_countries_expect_ips')));
	    	$this->moderate_new_members= trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'moderate_new_members')));
	    	$this->min_username= intval(trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'min_username'))));
	    	$this->max_username= intval(trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'max_username'))));
	    	
	    	if($this->status == 'enabled')
	    	{
	    		add_action('init', array($this , 'init'));
	    	}
	    	
	    	add_action( 'wp_login', array($this, 'last_login'), 10, 2 );
	    }
	    
	    //hook init
	    public function init()
	    {
	    	//login by filters
	    	if($this->login_by == 'email')
	    	{
	    		add_filter(  'gettext',  array($this, 'replace_login_text') );
	    		add_filter(  'ngettext',  array($this, 'replace_login_text') );
	    		remove_filter( 'authenticate', 'wp_authenticate_username_password', 20 );
	    	} else if($this->login_by == 'username')
	    	{
	    		add_filter(  'gettext',  array($this, 'replace_login_text') );
	    		add_filter(  'ngettext',  array($this, 'replace_login_text') );
	    		remove_filter( 'authenticate', 'wp_authenticate_email_password', 20 );
	    	}
	    	
	    	//strong passwords
	    	if($this->enforce_strongpasswords == 'checked')
	    	{
	    		add_action('user_profile_update_errors', array( $this , 'update_profile'), 0, 3);
	    	}
	    	
	    	//wp_login
	    	add_filter('login_message', array($this, 'register_session'), 10);
	    	add_filter( 'authenticate', array( $this, 'authenticate' ), 10, 3);
	    	add_filter( 'authenticate', array( $this, 'authenticate_rules' ), 10, 4);
	    	
	    	//register
	    	
	    	
	    	//moderate users
	    	if ($this->moderate_new_members == 'checked')
	    	{
	    		add_filter( 'user_row_actions', array( $this, 'user_table_tools' ), 10, 2 );
	    		add_filter( 'manage_users_columns', array( $this, 'add_column' ) );
	    		add_filter( 'manage_users_custom_column', array( $this, 'status_column' ), 10, 3 );
	    		add_action( 'user_register', array( $this, 'user_register' ), 10 );
	    		add_action( 'load-users.php', array( $this, 'update_user_status' ) );
	    		add_action( 'init', array( $this, 'check_inline_approval' ) );
	    		add_action( 'admin_init', array( $this, 'check_inline_approval' ) );
	    		add_action('login_head', array( $this, 'login_head' ) );
	    	}
	    	
	    	//set redirect to
	    	if(isset( $_REQUEST['redirect_to'] ) && !empty( $_REQUEST['redirect_to'] ))
	    	{
	    		add_action('authenticate', array( $this,'authenticate_redirect_to' ) );
	    	}
	    }
	    
	    public function login_head()
	    {
	    	if( isset($_REQUEST['msg']) && $_REQUEST['msg'] == 'not_approved' )
	    	{
	    		//$this->print_auth_error(__('<strong>ERROR</strong>: Your account still waits admin approval.', TEBRAVO_TRANS));
	    		?>
	    		<script>
				alert('<?php echo __("Your account still waits admin approval.", TEBRAVO_TRANS);?>');
	    		</script>
	    		<?php 
	    	}
	    }
	    public function authenticate_redirect_to()
	    {
	    	@setcookie('redirect_to', $_REQUEST['redirect_to'], time()+3600);
	    }
	    
	    //update last login
	    public function last_login( $user_login, $user)
	    {
	    	$browser = tebravo_agent::getBrowser();
	    	
	    	$user_id = $user->ID;
	    	$meta_key = TEBRAVO_DBPREFIX.'last_login';
	    	$meta_value_array = array(
	    			"time" => time(),
	    			"ip" => tebravo_agent::user_ip(),
	    			"country" => tebravo_agent::ip2country(),
	    			"browser" => $browser['name'],
	    			"device" => tebravo_agent::device(),
	    	);
	    	$meta_value = json_encode( $meta_value_array ) ;
	    	update_user_meta($user_id, $meta_key, $meta_value);
	    }
	    
	    //get out the disapproved users by admin while they are connected
	    public function check_inline_approval( $user )
	    {
	    	if( !$user )
	    	{
	    		$user = wp_get_current_user();
	    	}
	    	
	    	if( $this->user_status( $user->ID ) != 'approved' )
	    	{
	    		tebravo_clear_wp_cookie();
	    		$link = add_query_arg(array("msg"=>'not_approved'), wp_login_url());
	    		tebravo_redirect_js( $link );
	    		exit;
	    	}
	    }
	    
	    //update user status
	    public function update_user_status ()
	    {
	    	$helper = new tebravo_html();
	    	if(isset($_GET['action'])
	    			&& isset($_GET['user'])
	    			&& (isset($_GET['_nonce'])
	    					&& false !== wp_verify_nonce($_GET['_nonce'], $helper->init->security_hash.$_GET['action'].'-user-'.$_GET['user'])))
	    	{
	    		$actions_accepted = array(
	    				'approved',
	    				'denied',
	    				'pending'
	    		);
	    		$user_id = trim($_GET['user']);
	    		$user_id = (int) $_GET['user'];
	    		
	    		$action = trim( esc_html($_GET['action']));
	    		$action = sanitize_text_field( $action );
	    		
	    		$user_status = $this->user_status( $user_id , true);
	    		
	    		if( in_array($action, $actions_accepted)){
		    		if( count($user_status) == 0)
		    		{
		    			add_user_meta( $user_id, TEBRAVO_DBPREFIX.'user_status', $action);
		    		} else {
		    			update_user_meta( $user_id, TEBRAVO_DBPREFIX.'user_status', $action);
		    		}
	    		}
	    		
	    		tebravo_redirect_js(admin_url('users.php'));
	    	}
	    }
	    //add 'pending' status to new user
	    public function user_register( $user_id )
	    {
	    	add_user_meta( $user_id , TEBRAVO_DBPREFIX.'user_status', 'pending');
	    }
	    
	    //tools to moderate new members at users list
	    public function user_table_tools($actions, $user)
	    {
	    	$helper = new tebravo_html();
	    	
	    	$user_status = $this->user_status( $user->ID );
	    	
	    	$approve_btn = add_query_arg( array(
	    			'action' => 'approved',
	    			'user' => $user->ID,
	    			'_nonce' => $helper->init->create_nonce('approved-user-'.$user->ID)
	    	));
	    	
	    	$dis_approve_btn = add_query_arg( array(
	    			'action' => 'denied',
	    			'user' => $user->ID,
	    			'_nonce' => $helper->init->create_nonce('denied-user-'.$user->ID)
	    	));
	    	
	    	if($user->ID > 1):
		    	if( !empty($user_status) )
		    	{
		    		if( $user_status == 'approved' )
		    		{
		    			$actions[] = "<a href='".$dis_approve_btn."'>".__("Disapprove", TEBRAVO_TRANS)."</a>";
		    		} else {
		    			$actions[] = "<a href='".$approve_btn."'>".__("Approve", TEBRAVO_TRANS)."</a>";
		    		}
		    	} else {
		    		$actions[] = "<a href='".$dis_approve_btn."'>".__("Disapprove", TEBRAVO_TRANS)."</a>";
		    	}
	    	endif;
	    	
	    	return $actions;
	    }
	    
	    //new column to users list
	    public function add_column( $columns ) {
	    	$the_columns['tebravo_manage_users'] = __( 'Status', TEBRAVO_TRANS);
	    	
	    	$newcol = array_slice( $columns, 0, -1 );
	    	$newcol = array_merge( $newcol, $the_columns );
	    	$columns = array_merge( $newcol, array_slice( $columns, 1 ) );
	    	
	    	return $columns;
	    }
	    
	    //status column at users list when the moderate new users activated
	    public function status_column( $val, $column_name, $user_id ) {
	    	
	    			$status = $this->user_status( $user_id );
	    			if ( $status == 'approved' ) {
	    				$status_ = "<font color=green>".__( 'Approved', TEBRAVO_TRANS)."</font>";
	    			} else if ( $status == 'denied' ) {
	    				$status_ = "<font color=brown>".__( 'Denied', TEBRAVO_TRANS)."</font>";
	    			} else if ( $status == 'pending' ) {
	    				$status_ = "<font color=#C97109>".__( 'Pending', TEBRAVO_TRANS)."</font>";
	    			} else if ( $status == '' )
	    			{
	    				$status_ = "<font color=green>".__( 'Approved', TEBRAVO_TRANS)."</font>";
	    			}
	    			return $status_;
	    			
	    	return $val;
	    }
	    
	    protected function user_status( $userid , $array=false)
	    {
	    	if(! $array )
	    	{
	    		$user_status = get_user_meta( $userid, TEBRAVO_DBPREFIX.'user_status' , true);
	    	} else {
	    		$user_status = get_user_meta( $userid, TEBRAVO_DBPREFIX.'user_status' );
	    	}
	    	
	    	if( empty ( $user_status ) )
	    	{
	    		$status = 'approved';
	    	} else {
	    		$status = $user_status;
	    	}
	    	
	    	return $status;
	    }
	    
	    //while register is running
	    //check block usernames
	    //check minimum username
	    //check maximum username
	    //check blocked countries
	    //check blocked email providers
	    public function new_member_rules($errors)
	    {
	    	$banned_user_logins = '';
	    	$blocked_countries = '';
	    	$blocked_countries_expect_ips = '';
	    	$blocked_email_hosts= '';
	    	
	    	if($this->blocked_usernames_register != '')
	    	{
	    		$banned_user_logins = @explode(',', $this->blocked_usernames_register);
	    	}
	    	
	    	if($this->blocked_countries != '')
	    	{
	    		$blocked_countries= @explode(',', $this->blocked_countries);
	    	}
	    	
	    	if($this->blocked_countries_expect_ips != '')
	    	{
	    		$blocked_countries_expect_ips = @explode(',', $this->blocked_countries_expect_ips);
	    	}
	    	
	    	if($this->blocked_email_hosts != '')
	    	{
	    		$blocked_email_hosts= @explode(',', $this->blocked_email_hosts);
	    	}
	    	
	    	if($_POST)
	    	{
	    		if(!empty($_POST['user_login']))
	    		{
	    			$user_login = trim(sanitize_text_field($_POST['user_login']));
	    			$user_login_str = strlen($user_login);
	    			$user_country = tebravo_agent::ip2country();
	    			
	    			//check block usernames
	    			if(is_array( $banned_user_logins ))
	    			{
	    				if(in_array($user_login, $banned_user_logins))
	    				{
	    					$errors->add('user_login_banned', __("<strong>ERROR</strong>: This login name not allowed.", TEBRAVO_TRANS));
	    				}
	    			}
	    			
	    			//check username length
	    			if($this->min_username!= '' && $this->min_username > 0):
		    			if($user_login_str < $this->min_username)
		    			{
		    				$errors->add('user_login_minlength', __("<strong>ERROR</strong>: Username is too short.", TEBRAVO_TRANS));
		    			}
	    			endif;
	    			
	    			if($this->max_username != '' && $this->max_username > 0):
		    			if($user_login_str > $this->max_username)
		    			{
		    				$errors->add('user_login_maxlength', __("<strong>ERROR</strong>: Username is too long.", TEBRAVO_TRANS));
		    			}
	    			endif;
	    			
	    			//check blocked countires
	    			if(is_array($blocked_countries))
	    			{
	    				$blocked_countries = array_map('strtoupper', $blocked_countries);
	    				if(in_array($user_country, $blocked_countries))
	    				{
	    					if(is_array($blocked_countries_expect_ips) && $blocked_countries_expect_ips != ''
	    							&& in_array(tebravo_agent::user_ip(), $blocked_countries_expect_ips))
	    					{ //do nothing
	    					} else {
	    						$errors->add('user_login_country_block', __("<strong>ERROR</strong>: Register closed at your country.", TEBRAVO_TRANS));
	    					}
	    				}
	    			}
	    			
	    			//check blocked email providers
	    			if(is_array( $blocked_email_hosts ))
	    			{
	    				$blocked_email_hosts = array_map('strtolower', $blocked_email_hosts);
	    				$email = strtolower(trim(sanitize_email($_POST['user_email'])));
	    				$email_exp = @explode('@', $email);
	    				
	    				if(in_array($email_exp[1], $blocked_email_hosts))
	    				{
	    					$errors->add('user_login_email_blocked', __("<strong>ERROR</strong>: Try another email address.", TEBRAVO_TRANS));
	    				}
	    			}
	    		}
	    	}
	    	
	    	return $errors;
	    }
	    //register sessions
	    public function register_session()
	    {
	    	global $wpdb;
	    	//session_start();
	    	$wpdb->show_errors(false);
	    	$wpdb->hide_errors(); //hide database errors
	    	
	    	if(isset($_SESSION) && !empty($_SESSION['times']))
	    	{
	    		$_SESSION['times'] = $_SESSION['times'] + 1;
	    	} else {
	    		$_SESSION['times'] = 1;
	    	}
	    	
	    	$_SESSION['ipaddress'] = tebravo_agent::user_ip();
	    	
	    	//set redirect to
	    	if(isset( $_REQUEST['redirect_to'] ) && !empty( $_REQUEST['redirect_to'] ))
	    	{
	    		add_action('init', array( $this,'authenticate_redirect_to' ) );
	    	}
	    	
	    }
	    
	    public function authenticate_rules( $user , $username=false, $password=false, $errors=false)
	    {
	    	$blocked_names = '';
	    	$blocked_email_hosts = trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'blocked_email_hosts')));
	    	
	    	if($this->blocked_email_hosts != '')
	    	{
	    		$blocked_email_hosts= @explode(',', $this->blocked_email_hosts);
	    	}
	    	
	    	if($_POST)
	    	{
	    			
	    			
	    			//user status
	    			if($this->moderate_new_members == 'checked')
	    			{
	    				if(filter_var($username, FILTER_VALIDATE_EMAIL))
	    				{
	    					$db_colmn = 'email';
	    				} else {
	    					$db_colmn = 'login';
	    				}
	    				
	    				$user = get_user_by($db_colmn, $username);
	    				$user = wp_get_current_user();
	    				if(is_array($user)):
	    				
	    				$user_status = $this->user_status( $user->ID );
	    				#echo $user->ID; exit;
	    				if($user_status != 'approved')
	    				{
	    					$this->print_auth_error(__('<strong>ERROR</strong>: Your account still waits admin approval.', TEBRAVO_TRANS));
	    					
	    					tebravo_clear_wp_cookie();
	    					?><a href="<?php echo $this->wp_login_url();?>"><?php echo __("Try again?!", TEBRAVO_TRANS);?></a>
	    					<?php
	    					
	    					exit;
	    				}
	    				endif;
	    			}
	    		
	    	}
	    }
	    
	    
	    public function print_auth_error( $error )
	    {
	    	//HTML //login header
	    	if( !function_exists( 'login_header' ) ){ include_once( TEBRAVO_DIR.'/includes/function.login-header.php' ); }
	    	login_header();
	    	wp_enqueue_script( 'jquery' );
	    	wp_enqueue_script( 'tebravo_easytimer', plugins_url(TEBRAVO_SLUG.'/assets/js/easytimer.min.js'), false );
	    	wp_print_scripts();
	    	
	    	
	    	?>
	    	
	    	<div id='login_error'><?php echo $error;?> <span id="countdownTebravo"><span class="values"></span></span></div>
	    	<?php
	    	
	    }
	    //block on wrong authenticate if there many times errors
	    public function authenticate( $user, $username=false, $password=false)
	    {
	    	@session_start();
	    	if($_POST):
	    	$user_login='';
	    	if( isset($_POST[TEBRAVO_DBPREFIX.'auth']))
	    	{
	    		$user = get_user_by('ID', (int)esc_html($_POST[TEBRAVO_DBPREFIX.'auth']));
	    		if( !empty( $user ) )
	    		{
	    			$user_login = $user->user_login;
	    		}
	    	}
		    	if( isset($_POST['log']) )
		    	{
		    		$user_login = trim( sanitize_text_field( $_POST['log'] ) );
		    	}
		    	if(! $username )
		    	{
		    		$_SESSION['log'] = $user_login;
		    	} else {
		    		$_SESSION['log'] = $username;
		    	}
		    	
		    	if($this->check_locked() == true)
		    	{
		    		$this->lock_wp_login();
		    		exit;
		    	}
		    	
		    	//lock IP
		    	if( $this->max_login_attemps_ip > 0):
		    	if(isset($_SESSION) && !empty( $_SESSION['times'] )
		    			&& ($_SESSION['times'] >= $this->max_login_attemps_ip))
		    	{
		    		$this->lock_session(tebravo_agent::user_ip());
		    	}
		    	endif;
		    	
		    	//lock USER
		    	if( $this->max_login_attemps_user> 0):
		    	if(isset($_SESSION) && !empty( $_SESSION['times'] )
		    			&& ($_SESSION['times'] >= $this->max_login_attemps_user))
		    	{
		    		$this->lock_session(tebravo_agent::user_ip(),$username);
		    	}
		    	endif;
	    	endif;
	    	#unset($_SESSION['times']);
	    	
	    }
	    
	    protected function is_whitelist( $ip )
	    {
	    	$whitelisted_ips = trim( esc_html( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'bforce_whitelist_ips')) );
	    	$wlist_haystack = explode(",", $whitelisted_ips);
	    	
	    	if( filter_var($ip, FILTER_VALIDATE_IP) && in_array( $ip, $wlist_haystack))
	    	{
	    		return true;
	    	}
	    	
	    	return false;
	    }
	    public function lock_session($ip, $user_login=false)
	    {
	    	global $wpdb;
	    	$wpdb->show_errors(false);
	    	//default values
	    	$user_id = '';
	    	$user_email = '';
	    	$username = '';
	    	
	    	if( $this->is_whitelist( $ip ) ){return;}
	    	
	    	//define db_column if user_login not empty
	    	if($user_login):
		    	if(filter_var($user_login, FILTER_VALIDATE_EMAIL))
		    	{
		    		$db_colmn = 'email';
		    	} else {
		    		$db_colmn = 'user_login';
		    	}
	    	endif;
	    	
	    	//block an ipaddress
	    	$q_ip_var = $wpdb->get_var("SELECT COUNT(*) FROM " .tebravo_utility::dbprefix()."attemps
										WHERE ipaddress='$ip'"); 
	    	if($q_ip_var == 0)
	    	{
	    		$wpdb->insert(tebravo_utility::dbprefix().'attemps', array(
	    				'ipaddress'=>$ip
	    		));
	    	}
	    	
	    	//block an username
	    	if($user_login)
	    	{
	    		$user = get_user_by($db_colmn, $user_login);
	    		
	    		if(is_array($user))
	    		{
	    			$user_id = $user->ID;
	    			$user_email = $user->email;
	    			$username = $user->user_login;
	    		}
	    		$q_user_var = $wpdb->get_var("SELECT COUNT(*) FROM " .tebravo_utility::dbprefix()."attemps
	    				WHERE ".$db_colmn."='$user_login'"); 
	    		
	    		if($q_user_var == 0)
	    		{
	    			//insert new
	    			$wpdb->insert(tebravo_utility::dbprefix().'attemps', array(
	    					'ipaddress'=>$ip,
	    					'userid'=>$user_id,
	    					'email'=>$user_email,
	    					'user_login'=>$user_login,
	    			));
	    		} else {
	    			//or update
	    			$wpdb->update(tebravo_utility::dbprefix().'attemps', array(
	    					'userid'=>$user_id,
	    					'email'=>$user_email,
	    					'user_login'=>$user_login,
	    			), array('ipaddress'=>$ip, $db_colmn=>$user_login));
	    		}
	    	}
	    	
	    	//add time and block period
	    	$wpdb->update(tebravo_utility::dbprefix().'attemps', array(
	    			'time_blocked'=>time(),
	    			'time_to_unblock'=>(time()+($this->time_before_unblock*60))
	    	), array('ipaddress'=>$ip));
	    	
	    }
	    
	    //locked message
	    public function lock_wp_login()
	    {
	    	global $wpdb;
	    	$wpdb->show_errors(false);
	    	$helper = new tebravo_html();
	    	$timer = date('M d, Y h:i:s', (time()+(15*60)));
	    	$time_to_unblock = strtotime(microtime($timer));
	    	
	    	$q = "SELECT time_to_unblock FROM " .tebravo_utility::dbprefix()."attemps
					WHERE ipaddress='".tebravo_agent::user_ip()."' order by id DESC Limit 1";
	    	$result = $wpdb->get_row( $q );
	    	if($result->time_to_unblock != '')
	    	{
	    		$timer = date('M d, Y h:i:s', $result->time_to_unblock);
	    		$time_to_unblock = $result->time_to_unblock;
	    	}
	    	
	    	//HTML //login header
	    	if( !function_exists( 'login_header' ) ){ include_once( 'includes/function.login-header.php' ); }
	    	login_header();
	    	wp_enqueue_script( 'jquery' );
	    	wp_enqueue_script( 'tebravo_easytimer', plugins_url(TEBRAVO_SLUG.'/assets/js/easytimer.min.js'), false );
	    	wp_print_scripts();
	    	
	    	if($timer != '')
	    	{
	    		$this->js_timer_countDown($time_to_unblock, 'countdownTebravo');
	    	}
	    	?>
	    	
	    	<div id='login_error'><strong><?php echo __("Security Alert");?>: </strong><?php echo __("Your are locked! please try again after ", TEBRAVO_TRANS);?> <span id="countdownTebravo"><span class="values"></span></span></div>
	    	<?php
	    	
	    	//unset sessions
	    	@session_start();
	    	unset($_SESSION['times']);
	    	
	    	//try to unblock
	    	$this->try_unblock( $time_to_unblock );
	    	exit;
	    }
	    
	    //unblock
	    public function try_unblock( $time_to_unblock )
	    {
	    	global $wpdb;
	    	$wpdb->show_errors(false);
	    	$query = "DELETE FROM ".tebravo_utility::dbprefix()."attemps WHERE
						ipaddress='".tebravo_agent::user_ip()."' and time_to_unblock < '".time()."'";
	    	$wpdb->query( $query );
	    	
	    	if($this->check_locked() == false)
	    		tebravo_redirect_js($this->wp_login_url(), true);
	    	
	    }
	    
	    //counDown timer JS
	    public function js_timer_countDown( $timer , $divID)
	    {
	    	$redirect_to = $this->wp_login_url();
	    	?>
	    	<script>
	    	var timer = new Timer();
	    	timer.start({countdown: true, startValues: {seconds: <?php echo $timer - time();?>}});
	    	jQuery('#<?php echo $divID;?> .values').html(timer.getTimeValues().toString());
	    	timer.addEventListener('secondsUpdated', function (e) {
	    		jQuery('#<?php echo $divID;?> .values').html(timer.getTimeValues().toString());
	    	});
	    	timer.addEventListener('targetAchieved', function (e) {
	    		jQuery('#<?php echo $divID;?> .values').html('<?php echo __("Try Now!", TEBRAVO_TRANS);?>');
	    	});
	    	     
</script>
	    	<?php 	
	    }
	    
	    //WP login URL
	    public function wp_login_url()
	    {
	    	$then_redirect_to = '';
	    	
	    	if(isset($_COOKIE) && !empty($_COOKIE['redirect_to']))
	    	{
	    		$then_redirect_to = $_COOKIE['redirect_to']	;
	    	}
	    	
	    	$redirect_to = esc_url( wp_login_url( $then_redirect_to ) );
	    	
	    	return $redirect_to;
	    }
	    
	    //check if this user locked out
	    public function check_locked(  )
	    {
	    	global $wpdb;
	    	$wpdb->show_errors(false);
	    	$user_login = trim( sanitize_text_field( $_SESSION['log'] ) );
	    	$db_colmn = 'username';
	    	$locked = false;
	    	$q_user_count = 0;
	    	$ip = tebravo_agent::user_ip();
	    	if(!empty($user_login)):
	    	
		    	if(filter_var($user_login, FILTER_VALIDATE_EMAIL))
		    	{
		    		$db_colmn = 'email';	
		    	} else {
		    		$db_colmn = 'username';	
		    	}
		    	
		    	$user = get_user_by( $db_colmn, $user_login);
		    	if(is_array( $user ))
		    	{
		    		$user_id = $user->ID;
		    		//mysql query
		    		$q_user_count = $wpdb->get_var("SELECT COUNT(*) FROM " .tebravo_utility::dbprefix()."attemps
										WHERE {$db_colmn}='{$user_login}' and userid='{$user_id}'");
		    		
		    	}
	    	
	    	endif;
	    	
	    	$q_addr_count= $wpdb->get_var("SELECT COUNT(*) FROM " .tebravo_utility::dbprefix()."attemps
	    			WHERE ipaddress='{$ip}'");
	    	
	    	$this_device_count = $q_user_count + $q_addr_count;
	    	
	    	if($this_device_count > 0)
	    	{
	    		$locked = true;
	    	} else {
	    		$locked = false;
	    	}
	    	
	    	return $locked;
	    }
	    
	    //replace email or username in textdomain
	    public function replace_login_text( $translated ) {
	    	
	    	if($this->login_by == 'email'){$new_text = 'Email Address';}
	    	else if($this->login_by == 'username'){$new_text = 'Username';}
	    	
	    	$translated = str_ireplace(  'Username or Email Address',  $new_text ,  $translated );
	    	return $translated;
	    }
	    
	    //validate srtrong password
	    public function validate_pwd( $pwd )
	    {
	    	if (!preg_match_all( $this->strong_pwd_string , $pwd ) )
	    	{
	    		return false;
	    	} else {
	    		return true;
	    	}
	    }
	    
	    //update profile
	    public function update_profile($errors, $update, $user)
	    {
	    	return $this->validate_profile($errors,$user);
	    }
	    
	    public function validate_profile($errors, $user)
	    {
	    	$is_pwd_ok = true;
	    	$pwd = ( isset( $_POST['pass1'] ) && trim( $_POST['pass1'] ) ) ? sanitize_text_field( $_POST['pass1'] ) : false;
	    	
	    	//if errors already exist
	    	if ( ( false === $pwd) || ( is_wp_error( $errors ) && $errors->get_error_data( 'pass' ) ) ) {
	    		return $errors;
	    	}
	    	
	    	//check password
	    	if(false === $this->validate_pwd( $pwd ))
	    	{
	    		$is_pwd_ok = false;
	    	}
	    	
	    	if(!$is_pwd_ok && is_wp_error($errors))
	    	{
	    		$errors->add('pass',__("<strong>Security Alert</strong>: We can not continue saving your weak password, Please choose strong one.", TEBRAVO_TRANS));
	    	}
	    	
	    	return $errors;
	    }
	    
	    //dashboard
	    public function dashboard()
	    {
	    	$this->html = new tebravo_html();
	    	//Tabs Data
	    	$tabs["general"] = array("title"=>"Options",
	    	"href"=>$this->html->init->admin_url."-settings",
	    	"is_active"=> '');
	    	
	    	$tabs["wpconfig"] = array("title"=>"WP Config",
	    			"href"=>$this->html->init->admin_url."-wconfig",
	    			"is_active"=> '');
	    	
	    	$tabs["wpadmin"] = array("title"=>"WP Admin",
	    			"href"=>$this->html->init->admin_url."-wadmin",
	    			"is_active"=> '');
	    	
	    	$tabs["bruteforce"] = array("title"=>"Brute Force",
	    			"href"=>$this->html->init->admin_url."-bruteforce",
	    			"is_active"=> 'active');
	    	
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
	    	
	    	$desc = "The complete security for your and users' passwords by activating these brute force protection options.";
	    	$this->html->header(__("Brute Force Protection", TEBRAVO_TRANS), $desc, "bruteforce.png", false);
	    	
	    	//Tabs HTML
	    	$this->html->tabs($tabs);
	    	$this->html->start_tab_content();
	    	
	    	//Start Content
	    	$output[] = "<form action='".$this->html->init->admin_url."-bruteforce' method=post>";
	    	$output[] = "<input type='hidden' name='_nonce' value='".$this->html->init->create_nonce('bruteforce-settings')."'>";
	    	$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
	    	$output[] = "<table border='0' width=100% cellspacing=0>";
	    	
	    	//enable/disable brute force
	    	$bruteforce_protection_no='';
	    	$bruteforce_protection_yes='';
	    	if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX."bruteforce_protection"))) == 'checked'){$bruteforce_protection_yes='checked';}else{$bruteforce_protection_no='checked';}
	    	$help_enablebforce = 'If you disabled this option, this means all brute force protection options will be disabled automatically.';
	    	$output[] = "<tr class='tebravo_underTD'><td width=60%>".__("Enable Protection", TEBRAVO_TRANS)." ".$this->html->open_window_help('enablebforce',$help_enablebforce)."</td>";
	    	$output[] = "<td>";
	    	$output[] = "<input type='radio' name='".TEBRAVO_DBPREFIX."bruteforce_protection' value='checked' id='bruteforce_protection_checked' $bruteforce_protection_yes><label for='bruteforce_protection_checked'><span></span>".__("Yes", TEBRAVO_TRANS)."</label> &nbsp;";
	    	$output[] = "<input type='radio' name='".TEBRAVO_DBPREFIX."bruteforce_protection' value='no' id='bruteforce_protection_no' $bruteforce_protection_no><label for='bruteforce_protection_no'><span></span>".__("No", TEBRAVO_TRANS)."</label> &nbsp;";
	    	$output[] = "</td>";
	    	$output[] = "</tr>";
	    	
	    	//login by
	    	$help_loginby = 'It is a perfect way to create strong defense, the plugin gives you ability to choose the login way, but the recommended way is: email address.';
	    	$login_by_array = array('username' => __("Username", TEBRAVO_TRANS), 'email' => __("Email Address", TEBRAVO_TRANS), '' => __("Username OR Email", TEBRAVO_TRANS));
	    	$login_by_list = '';
	    	foreach ($login_by_array as $key => $value)
	    	{
	    		$login_by_list .= "<option value='".$key."' ";
	    		if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX."login_by"))) == $key){$login_by_list .= "selected";}
	    		$login_by_list .= ">".$value."</option>";
	    	}
	    	$output[] = "<tr class='tebravo_underTD'><td width=60%>".__("Available login method(s)", TEBRAVO_TRANS)." ".$this->html->open_window_help('loginby',$help_loginby)."</td>";
	    	$output[] = "<td><select name='".TEBRAVO_DBPREFIX."login_by'>";
	    	$output[] = $login_by_list;
	    	$output[] = "</select>";
	    	$output[] = "</td>";
	    	$output[] = "</tr>";
	    	
	    	//enforce strong passwords
	    	$enforce_strongpasswords_no='';
	    	$enforce_strongpasswords_yes='';
	    	if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX."enforce_strongpasswords"))) == 'checked'){$enforce_strongpasswords_yes='checked';}else{$enforce_strongpasswords_no='checked';}
	    	$help_enforce_strongpasswords= 'It is recommended to enable this option, to enforce all users to use strong passwords while they are updating their passwords at the next times.';
	    	$output[] = "<tr class='tebravo_underTD'><td width=60%>".__("Enforce Strong Passwords", TEBRAVO_TRANS)." ".$this->html->open_window_help('enforce_strongpasswords',$help_enforce_strongpasswords)."</td>";
	    	$output[] = "<td>";
	    	$output[] = "<input type='radio' name='".TEBRAVO_DBPREFIX."enforce_strongpasswords' value='checked' id='enforce_strongpasswords_checked' $enforce_strongpasswords_yes><label for='enforce_strongpasswords_checked'><span></span>".__("Yes", TEBRAVO_TRANS)."</label> &nbsp;";
	    	$output[] = "<input type='radio' name='".TEBRAVO_DBPREFIX."enforce_strongpasswords' value='no' id='enforce_strongpasswords_no' $enforce_strongpasswords_no><label for='enforce_strongpasswords_no'><span></span>".__("No", TEBRAVO_TRANS)."</label> &nbsp;";
	    	$output[] = "</td>";
	    	$output[] = "</tr>";
	    	
	    	//max login attemps IP
	    	$max_login_attemps_ip= trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'max_login_attemps_ip')));
	    	$help_max_login_attemps_ip= 'Available attempts to login with wrong details per IP address before blocking.';
	    	$output[] = "<tr class='tebravo_underTD'><td width=60%>".__("Maximum Login Attempts Per IP", TEBRAVO_TRANS)." ".$this->html->open_window_help('max_login_attemps_ip',$help_max_login_attemps_ip)."</td>";
	    	$output[] = "<td>";
	    	$output[] = "<input type='text' name='".TEBRAVO_DBPREFIX."max_login_attemps_ip' pattern='[0-9]{1,5}' value='$max_login_attemps_ip' id='max_login_attemps_ip'>";
	    	$output[] = "</td>";
	    	$output[] = "</tr>";
	    	//bforce_whitelist_ips
	    	$bforce_whitelist_ips= trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'bforce_whitelist_ips')));
	    	$help_bforce_whitelist_ips= 'Add some IPs to white list.';
	    	$output[] = "<tr class='tebravo_underTD'><td width=60%>".__("White Listed IPs", TEBRAVO_TRANS)." ".$this->html->open_window_help('bforce_whitelist_ips',$help_bforce_whitelist_ips)."</td>";
	    	$output[] = "<td>";
	    	$output[] = "<input type='text' name='".TEBRAVO_DBPREFIX."bforce_whitelist_ips' value='$bforce_whitelist_ips' id='bforce_whitelist_ips'>";
	    	$output[] = "<br /><font class='smallfont'>use comma, to sperate IPs, Like: 10.20.30.40,101.102.103.104</font>";
	    	$output[] = "</td>";
	    	$output[] = "</tr>";
	    	//max login attemps USER
	    	$max_login_attemps_user= trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'max_login_attemps_user')));
	    	$help_max_login_attemps_user= 'Available attempts to login with wrong details per USER before blocking.';
	    	$output[] = "<tr class='tebravo_underTD'><td width=60%>".__("Maximum Login Attempts Per USER", TEBRAVO_TRANS)." ".$this->html->open_window_help('max_login_attemps_user',$help_max_login_attemps_user)."</td>";
	    	$output[] = "<td>";
	    	$output[] = "<input type='text' name='".TEBRAVO_DBPREFIX."max_login_attemps_user' pattern='[0-9]{1,5}' value='$max_login_attemps_user' id='max_login_attemps_user'>";
	    	$output[] = "</td>";
	    	$output[] = "</tr>";
	    	
	    	//time befor unblock
	    	$time_before_unblock= trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'time_before_unblock')));
	    	$help_time_before_unblock= 'The period which you give to the blocked user before the system allows him again to login.';
	    	$output[] = "<tr class='tebravo_underTD'><td width=60%>".__("Time Before Unblock", TEBRAVO_TRANS)." ".$this->html->open_window_help('time_before_unblock',$help_time_before_unblock)."</td>";
	    	$output[] = "<td>";
	    	$output[] = "<input type='text' name='".TEBRAVO_DBPREFIX."time_before_unblock' pattern='[0-9]{1,5}' value='$time_before_unblock' id='time_before_unblock'> ".__("minutes", TEBRAVO_TRANS);
	    	$output[] = "</td>";
	    	$output[] = "</tr>";
	    	
	    	//blocked usernames login
	    	$blocked_usernames_login= trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'blocked_usernames_login')));
	    	$help_blocked_usernames_login= 'Add some usernames to block it from using to login.';
	    	$help_blocked_usernames_login .= __("Use comma, to separate between it. like e.g: admin,control", TEBRAVO_TRANS);
	    	$output[] = "<tr class='tebravo_underTD'><td width=60%>".__("Disallowed usernames for login", TEBRAVO_TRANS)." ".$this->html->open_window_help('blocked_usernames_login',$help_blocked_usernames_login)." <span class='tebravo_trail'>".__("Premium", TEBRAVO_TRANS)."</span></td>";
	    	$output[] = "<td>";
	    	$output[] = "<input type='text' name='".TEBRAVO_DBPREFIX."blocked_usernames_login' value='' id='blocked_usernames_login' disabled> <br><font class='smallfont'>".__("Use comma, to separate between it. like e.g: admin,control", TEBRAVO_TRANS)."</font>";
	    	$output[] = "</td>";
	    	$output[] = "</tr>";
	    	
	    	//blocked usernames register
	    	$blocked_usernames_register= trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'blocked_usernames_register')));
	    	$help_blocked_usernames_register= 'Add some usernames to block it from using to register new membersihp.';
	    	$help_blocked_usernames_register .= __("Use comma, to separate between it. like e.g: admin,control", TEBRAVO_TRANS);
	    	$output[] = "<tr class='tebravo_underTD'><td width=60%>".__("Disallowed usernames for register", TEBRAVO_TRANS)." ".$this->html->open_window_help('blocked_usernames_register',$help_blocked_usernames_register)." <span class='tebravo_trail'>".__("Premium", TEBRAVO_TRANS)."</span></td>";
	    	$output[] = "<td>";
	    	$output[] = "<input type='text' name='".TEBRAVO_DBPREFIX."blocked_usernames_register' value='' id='blocked_usernames_register' disabled> <br><font class='smallfont'>".__("Use comma, to separate between it. like e.g: admin,control", TEBRAVO_TRANS)."</font>";
	    	$output[] = "</td>";
	    	$output[] = "</tr>";
	    	
	    	//blocked email providers
	    	$blocked_email_hosts= trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'blocked_email_hosts')));
	    	$help_blocked_email_hosts= 'Add some email hosts/providers to block it from using to register or login.';
	    	$help_blocked_email_hosts .= __("Use comma, to separate between it without @. like e.g: admin,control", TEBRAVO_TRANS);
	    	$output[] = "<tr class='tebravo_underTD'><td width=60%>".__("Disallowed email hosts/providers", TEBRAVO_TRANS)." ".$this->html->open_window_help('blocked_email_hosts',$help_blocked_email_hosts)." <span class='tebravo_trail'>".__("Premium", TEBRAVO_TRANS)."</span></td>";
	    	$output[] = "<td>";
	    	$output[] = "<input type='text' name='".TEBRAVO_DBPREFIX."blocked_email_hosts' value='' id='blocked_email_hosts' disabled> <br><font class='smallfont'>".__("Use comma, to separate between it without @. like e.g: hotmail.com,mail.ru,yahoo.com", TEBRAVO_TRANS)."</font>";
	    	$output[] = "</td>";
	    	$output[] = "</tr>";
	    	
	    	//blocked countries
	    	$blocked_countries= trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'blocked_countries')));
	    	$blocked_countries_expect_ips= trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'blocked_countries_expect_ips')));
	    	$help_blocked_countries= 'Add some countries to prevent its visitors from register new membership.';
	    	$help_blocked_countries .= __("Use comma, to separate between it. like e.g: US,UK,EG", TEBRAVO_TRANS);
	    	$output[] = "<tr class='tebravo_underTD'><td width=60%>".__("Disallowed countires (for register)", TEBRAVO_TRANS)." ".$this->html->open_window_help('blocked_countries',$help_blocked_countries)." <span class='tebravo_trail'>".__("Premium", TEBRAVO_TRANS)."</span></td>";
	    	$output[] = "<td>";
	    	$output[] = "<input type='text' name='".TEBRAVO_DBPREFIX."blocked_countries' value='' id='blocked_countries' disabled> <br><font class='smallfont'>".__("Use comma, to separate between it. like e.g:  US,UK,EG", TEBRAVO_TRANS)."</font><br />";
	    	$output[] = __("Except These IP Addresses", TEBRAVO_TRANS).": <br />";
	    	$output[] = "<input type='text' name='".TEBRAVO_DBPREFIX."blocked_countries_expect_ips' value='' id='blocked_countries_expect_ips' disabled> <br><font class='smallfont'>".__("Use comma, to separate between it. like e.g:  127.0.0.1,10.11.12.13", TEBRAVO_TRANS)."</font><br />";
	    	$output[] = "</td>";
	    	$output[] = "</tr>";
	    	
	    	//moderate new members
	    	$moderate_new_members_no='';
	    	$moderate_new_members_yes='';
	    	if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX."moderate_new_members"))) == 'checked'){$moderate_new_members_yes='checked';}else{$moderate_new_members_no='checked';}
	    	$help_moderate_new_members= 'If you enable this option, this means all new users will be not able to login before administrator approves their accounts.';
	    	$output[] = "<tr class='tebravo_underTD'><td width=60%>".__("Moderate New Members (new registered users)", TEBRAVO_TRANS)." ".$this->html->open_window_help('moderate_new_members',$help_moderate_new_members)."</td>";
	    	$output[] = "<td>";
	    	$output[] = "<input type='radio' name='".TEBRAVO_DBPREFIX."moderate_new_members' value='checked' id='moderate_new_members_checked' $moderate_new_members_yes><label for='moderate_new_members_checked'><span></span>".__("Yes", TEBRAVO_TRANS)."</label> &nbsp;";
	    	$output[] = "<input type='radio' name='".TEBRAVO_DBPREFIX."moderate_new_members' value='no' id='moderate_new_members_no' $moderate_new_members_no><label for='moderate_new_members_no'><span></span>".__("No", TEBRAVO_TRANS)."</label> &nbsp;";
	    	$output[] = "</td>";
	    	$output[] = "</tr>";
	    	
	    	//minimum username
	    	$min_username= trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'min_username')));
	    	$help_min_username= 'Set the minimum for the new registered username chars , new user can not complete registration before reaching at least the minimum for his username chars .';
	    	$output[] = "<tr class='tebravo_underTD'><td width=60%>".__("Minimum Chars for Username (Min Limit) (new users)", TEBRAVO_TRANS)." ".$this->html->open_window_help('min_username',$help_min_username)."</td>";
	    	$output[] = "<td>";
	    	$output[] = "<input type='text' name='".TEBRAVO_DBPREFIX."min_username' pattern='[0-9]{1,5}' value='$min_username' id='min_username'>";
	    	$output[] = "</td>";
	    	$output[] = "</tr>";
	    	
	    	//maximum username
	    	$max_username= trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'max_username')));
	    	$help_max_username= 'Set the maximum for the new registered username chars , new user can not complete registration if his username more than the maximum limit.';
	    	$output[] = "<tr class='tebravo_underTD'><td width=60%>".__("Minimum Chars for Username (Max Limit) (new users)", TEBRAVO_TRANS)." ".$this->html->open_window_help('max_username',$help_max_username)."</td>";
	    	$output[] = "<td>";
	    	$output[] = "<input type='text' name='".TEBRAVO_DBPREFIX."max_username' pattern='[0-9]{1,5}' value='$max_username' id='max_username'>";
	    	$output[] = "</td>";
	    	$output[] = "</tr>";
	    	
	    	$output[] = "</table>";
	    	$output[] = $this->html->button(__("Save", TEBRAVO_TRANS), "submit");
	    	$output[] = "</div></form>";
	    	
	    	if(! $_POST)
	    	{
	    		echo implode("\n", $output);
	    	} else {
	    		if(isset($_POST['_nonce'])
	    				&& false !== wp_verify_nonce($_POST['_nonce'], $this->html->init->security_hash.'bruteforce-settings'))
	    		{
	    			tebravo_utility::update_option( TEBRAVO_DBPREFIX.'bruteforce_protection' , trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'bruteforce_protection'])));	
	    			tebravo_utility::update_option( TEBRAVO_DBPREFIX.'login_by' , trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'login_by'])));	
	    			tebravo_utility::update_option( TEBRAVO_DBPREFIX.'enforce_strongpasswords' , trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'enforce_strongpasswords'])));	
	    			tebravo_utility::update_option( TEBRAVO_DBPREFIX.'max_login_attemps_ip' , intval(trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'max_login_attemps_ip']))));	
	    			tebravo_utility::update_option( TEBRAVO_DBPREFIX.'bforce_whitelist_ips' , (trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'bforce_whitelist_ips']))));	
	    			tebravo_utility::update_option( TEBRAVO_DBPREFIX.'max_login_attemps_user' , intval(trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'max_login_attemps_user']))));	
	    			#tebravo_utility::update_option( TEBRAVO_DBPREFIX.'max_forgot_attemps' , trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'max_forgot_attemps'])));	
	    			tebravo_utility::update_option( TEBRAVO_DBPREFIX.'time_before_unblock' , intval(trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'time_before_unblock']))));	
	    			tebravo_utility::update_option( TEBRAVO_DBPREFIX.'moderate_new_members' , trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'moderate_new_members'])));	
	    			tebravo_utility::update_option( TEBRAVO_DBPREFIX.'min_username' , intval(trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'min_username']))));	
	    			tebravo_utility::update_option( TEBRAVO_DBPREFIX.'max_username' , intval(trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'max_username']))));
	    			
	    			echo __("Loading", TEBRAVO_TRANS)."...";
	    			$url = $this->html->init->admin_url.'-bruteforce&msg=01';
	    		} else {
	    			$url = $this->html->init->admin_url.'-bruteforce&err=02';
	    		}
	    		
	    		tebravo_redirect_js( $url );
	    	}
	    	$this->html->end_tab_content();
	    	$this->html->footer();
	    }
	    
    }
    //run
	new tebravo_bforce();
}
?>