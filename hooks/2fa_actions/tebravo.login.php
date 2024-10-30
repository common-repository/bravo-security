<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if(!class_exists( 'tebravo_login' ) )
{
	class tebravo_login{
        
    	private $login_nonce;
    	public 
	    	$userid,
	    	$token,
	    	$time,
	    	$login_nonce_name,
	    	$userdata,
	    	$user_roles,
	    	$twofa,
	    	$twofa_default,
	    	$html;
    	
    	//constructor
        public function __construct()
        {
        	//options
        	$this->time = strtotime( date('d-m-Y h') );
        	$this->login_nonce_name = trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'security_hash'))).'loggin';
        	$this->twofa= trim(esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'two_step_login' ) ) );
        	$this->twofa_default= trim(esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'two_step_login_default' ) ) );
        	
        	//actions
        	
        	if($this->twofa == 'checked')
        	{
        		$this->add_actions();
        		#add_action( 'init' , array( $this , 'init' ) );
        	}
        }
        
        //add actions
        public function add_actions()
        {
        	add_action( 'set_logged_in_cookie',     array( $this, 'set_logged_in_cookie' ) );
        	add_action( 'wp_login',                 array( $this, 're_login' ), 10, 2 );
        	add_action( 'login_form_bravo_fa',  array( $this, 'login_form_bravo_fa' ) );
        	add_action( 'login_form_new_2fa',  array( $this, 'login_form_new_2fa' ) );
        }
        
        //hook init
        public function init()
        {
        	
        	$user = wp_get_current_user();
        	
        	if($this->user_use_2fa( $user ) == false)
        	{
        		remove_action('set_logged_in_cookie', array( $this , 'set_logged_in_cookie' ) );
        		remove_action('wp_login', array( $this , 're_login' ) );
        		remove_action('login_form_bravo_fa', array( $this , 'login_form_bravo_fa' ) );
        		remove_action('login_form_new_2fa', array( $this , 'login_form_new_2fa' ) );
        	} else{
        		$this->add_actions();
        	}
        	
        	do_action('bravo_2fa_init');
			
        }
        
        //remove action if needed
        public function remove_actions()
        {
        	$user = wp_get_current_user();
        	
        	if($this->twofa == 'checked'
        			|| $this->user_use_2fa( $user ) != true
        			|| $this->check_by_role( $user ) != true)
        	{
        		remove_action('set_logged_in_cookie', array( $this , 'set_logged_in_cookie' ) );
        		remove_action('wp_login', array( $this , 're_login' ) );
        	}
        	
        	do_action('bravo_2fa_init');
        }
        
        //check if user uses two steps for login or not
        protected function user_use_2fa( $user )
        {
        	if($this->twofa_default == '2fa')
        	{
        		$meta_enabled = get_user_meta($user->ID , TEBRAVO_DBPREFIX.'2fa_enabled', true);
        		$meta_enabled= trim( esc_html( $meta_enabled) );
        	} else if($this->twofa_default == 'fb'){
        		$meta_enabled = get_user_meta($user->ID , TEBRAVO_DBPREFIX.'fb_enabled', true);
        		$meta_enabled= trim( esc_html( $meta_enabled) );
        	} else if($this->twofa_default == 'pin'){
        		$meta_enabled = get_user_meta($user->ID , TEBRAVO_DBPREFIX.'pin_enabled', true);
        		$meta_enabled= trim( esc_html( $meta_enabled) );
        	} else if($this->twofa_default == 'q'){
        		$meta_enabled = get_user_meta($user->ID , TEBRAVO_DBPREFIX.'q_enabled', true);
        		$meta_enabled= trim( esc_html( $meta_enabled) );
        	}
        	
        	if($meta_enabled == 'checked')
        	{
        			return true;
        	} else {
        		return false;
        	}
        }
        
        //create login nonce
        public function create_nonce( $user_id )
        {
        	$nonce = array();
        	$nonce['key'] = wp_hash($this->login_nonce_name.$user_id.mt_rand().microtime(), 'bravo_nonce');
        	$nonce['expire'] = time()+3600;
        	
        	if( !update_user_meta($user_id, TEBRAVO_DBPREFIX.'2fa_nonce', $nonce) ){return false;}
        	return $nonce;
        }
        
        //verify login nonce
        public function verify_nonce( $user_id, $nonce )
        {
        	$login_nonce = get_user_meta( $user_id, TEBRAVO_DBPREFIX.'2fa_nonce' , true );
        	if( !$login_nonce ){return false;} 
        	
        	if( $login_nonce['key'] !== $nonce
        			|| time() > $login_nonce['expire'])
        	{
        		$this->delete_login_nonce( $user_id );
        		return false;
        	}
        	
        	return true;
        }
        
        //delete nonce
        public function delete_login_nonce( $user_id )
        {
        	if( $user_id > 0 )
        	{
        		delete_user_meta($user_id, TEBRAVO_DBPREFIX.'2fa_nonce');
        	}
        	
        }
        
        //check by role
        public function check_by_role( $user )
        {
        	if( !$user )
        	{
        		$user = wp_get_current_user();
        	}
        	
        	$userdata = get_userdata($user->ID);
        	
        	$available_roles = trim(sanitize_text_field(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'roles_2fa')));
        	;
        	if(empty($available_roles))
        	{
        		$run = true;
        	} else {
        		$roles = json_decode($available_roles, true);
        		
        		if(is_array( $roles) ){
        			$user_roles = array_map('strtolower', $userdata->roles);
	        		
        			$results_roles = array_intersect($roles, $user_roles);
	        		
	        		if(count( $results_roles ) > 0)
	        		{
	        			$run = true;
	        		} else {
	        			$run = false;
	        		}
        		} else {
        			$run = false;
        		}
        	}
        	
        	return $run;
        }
        
        //store login token in $this->token
        public function set_logged_in_cookie( $cookie )
        {
        	$cookie = wp_parse_auth_cookie( $cookie, 'logged_in' );
        	if( !empty( $cookie['token'] ) ){$this->token = $cookie['token'];}
        	else{$this->token = '';}
        }
        
        //re login //handle login user with 2fa
        public function re_login( $user_login , $user )
        {
        	if( !$user )
        	{
        		$user = wp_get_current_user();
        	}
        	
        	if($this->user_use_2fa($user) != true
        			|| $this->check_by_role($user) != true)
        	{
        		//remove_action('set_logged_in_cookie', array( $this , 'set_logged_in_cookie' ) );
        		remove_action('wp_login', array( $this , 're_login' ) );
        		remove_action('login_form_bravo_fa', array( $this , 'login_form_bravo_fa' ) );
        		remove_action('login_form_new_2fa', array( $this , 'login_form_new_2fa' ) );
        		
        		$redirect_to = isset( $_REQUEST['redirect_to'] ) ? esc_url($_REQUEST['redirect_to']): $_SERVER['REQUEST_URI'];
        		tebravo_redirect_js($redirect_to, true);
        		return ;
        	}
        	
        	if( $this->token )
        	{
        		$sessions = WP_Session_Tokens::get_instance( $user->ID);
        		$sessions->destroy( $this->token );
        	}
        	
        	//clear auth cookie
        	wp_clear_auth_cookie();
        	
        	//go HTML
        	$this->show_2fa_box( $user );
        	exit;
        }
        
        //show the two steps login box
        public function show_2fa_box( $user )
        {
        	if( !$user )
        	{
        		$user = wp_get_current_user();
        	}
        	
        	$redirect_to = isset( $_REQUEST['redirect_to'] ) ? esc_url($_REQUEST['redirect_to']): $_SERVER['REQUEST_URI'];
        	
        	$login_nonce = $this->create_nonce( $user->ID );
        	
        	if(!$login_nonce ){return;}
        	
        	$this->print_loginbox($user, $login_nonce['key'] ,false , $redirect_to);
        }
        
        //print login box for 2fa
        public function print_loginbox($user, $login_nonce, $err=false, $redirect_to=false)
        {
        	if( !$user  || ! is_a( $user, 'WP_User' ) )
        	{
        		$user = wp_get_current_user();
        	}
        	$this->userid = $user->ID;
        	
        	//set remember me
        	$rememberme= 0;
        	if( isset( $_REQUEST['rememberme'])
        			&& $_REQUEST['rememberme'])
        	{
        		$rememberme= 1;
        	}
        	
        	//interim login
        	$interim_login = isset($_REQUEST['interim-login']);
        	
        	//HTML //login header
        	if( !function_exists( 'login_header' ) ){ include_once( TEBRAVO_DIR.'/includes/function.login-header.php' ); }
        	login_header();
        	wp_enqueue_script( 'jquery' );
        	wp_enqueue_style (TEBRAVO_SLUG."_flags_css", plugins_url(TEBRAVO_SLUG.'/assets/css/flags.css'));
        	wp_print_scripts();
        	wp_print_styles();
        	
        	if($err)
        	{
        		$login_error = "<strong>".__("Error", TEBRAVO_TRANS)."</strong>: ".esc_html( $err );
        		$output[] = "<div id='login_error'>".$login_error."<br /></div>";
        	}
        	$output[] = "<form action='".$this->login_url()."' method=post id='loginform' autocomplete='off' name='bravo_2fa'>";
        	$output[] = "<input type='hidden' name='_tebravo_nonce' value='".$login_nonce."'>";
        	if( isset($_POST['log']) )
        	{
        		$output[] = "<input type='hidden' name='log' value='".sanitize_text_field($_POST['log'])."'>";
        	}
        	$output[] = "<input type='hidden' name='_tebravo_auth' value='".$this->userid."'>";
        	$output[] = "<input type='hidden' name='_tebravo_rememberme' value='".$rememberme."'>";
        	if($interim_login)
        	{
        		$output[] = "<input type='hidden' name='interim-login' value='1'>";
        	} else {
        		$output[] = "<input type='hidden' name='redirect_to' value='".$redirect_to."'>";
        	}
        	
        	if($this->twofa_default == '2fa')
        	{
        		$output[] = $this->two_fa_form( $user , $err);
        	}else if($this->twofa_default == 'fb')
        	{
        		include TEBRAVO_DIR.'/hooks/2fa_actions/fb.php';
        		$output[] = $this->two_fb_form( $user , $err);
        	}else if($this->twofa_default == 'pin')
        	{
        		$output[] = $this->two_pin_form( $user , $err);
        	}else if($this->twofa_default == 'q')
        	{
        		$output[] = $this->two_questions_form( $user , $err);
        	}
        	
        	$output[] = "<center><input name='wp-submit' id='wp-submit' class='button button-primary button-large' value='".__("Continue", TEBRAVO_TRANS)."' type='submit'></center>";
        	$output[] = "</form>";
        	
        	if($this->twofa_default == 'fb')
        	{
        		$output[] = '<script>jQuery("#wp-submit").hide();</script>';
        	}
        	
        	
        	echo implode("\n", $output);
        	
        	$country_name = tebravo_agent::ip2country(false,'country_name');
        	$country_code= tebravo_agent::ip2country();
        	echo '<img src="'.plugins_url('assets/img/blank.png', TEBRAVO_PATH).'" class="tebravo_flag flag-'.strtolower($country_code).'" alt="'.$country_name.'" /> '.tebravo_GetRealIP();
        	
        }
        
        //the questions box to continue log in
        public function two_questions_form( $user , $err=false)
        {
        	$questions = tebravo_questions_list();
        	if(is_array( $questions ))
        	{
        		$options = '';
        		foreach ($questions as $key => $value)
        		{
        			$options .= "<input type='radio' name='".TEBRAVO_DBPREFIX."q' value='".esc_html($key)."' id='".esc_html($key)."'>";
        			$options .= "<label for='".esc_html($key)."'>".esc_html($value)."</label><br />";
        		}
        		
        		$output = "<p align=center>";
        		$output .= "<img src='".plugins_url('assets/img/Gnome-Security-Low-32.png', TEBRAVO_PATH)."'></p>";
        		$output .= "<p><strong>".__("Choose your securtiy question!", TEBRAVO_TRANS)."</strong><br />";
        		$output .= $options;
        		$output .= "<br /><hr><br />";
        		$output .= __("Write your exact answer:", TEBRAVO_TRANS)."<br />";
        		$output .= "<input type='text' name='".TEBRAVO_DBPREFIX."a' autocomplete=off>";
        		$output .="</p>";
        		
        		return $output;
        	}
        }
        
        //the PIN code box to continue log in
        public function two_pin_form( $user , $err=false)
        {
        	$output = "<p align=center>";
        	$output .= "<img src='".plugins_url('assets/img/keyhole-32.png', TEBRAVO_PATH)."'><br />";
        	$output .= __("Insert you PIN code to login", TEBRAVO_TRANS)."<br /><br />";
        	$output .= "<input type='text' id='tebravo_p1' class='tebravo_inputs' min='0' maxlength='1' name='".TEBRAVO_DBPREFIX."p1' onkeypress=\"return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57\" style='width:45px;'>";
        	$output .= "<input type='text' class='tebravo_inputs' min='0' maxlength='1' name='".TEBRAVO_DBPREFIX."p2' onkeypress=\"return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57\" style='width:45px;'>";
        	$output .= "<input type='text' class='tebravo_inputs' min='0' maxlength='1' name='".TEBRAVO_DBPREFIX."p3' onkeypress=\"return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57\" style='width:45px;'>";
        	$output .= "<input type='text' class='tebravo_inputs' min='0' maxlength='1' name='".TEBRAVO_DBPREFIX."p4' onkeypress=\"return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57\" style='width:45px;'>";
        	$output .="</p>";
        	$output .= '<script>
jQuery(".tebravo_inputs").keyup(function () {
    if (this.value.length == this.maxLength) {
      var next = jQuery(this).next(".tebravo_inputs");
      if (next.length)
          jQuery(this).next(".tebravo_inputs").focus();
      else
          jQuery(this).blur();
    }
});
jQuery(".tebravo_inputs").focus(function() { jQuery(this).select(); } );
jQuery(document).ready(function(){
	jQuery("#tebravo_p1").focus();
});
</script>
<style>
.tebravo_inputs{text-align: center;}
</style>
';
        	
        	return $output;
        }
        
        //the Faceboo login verification box to continue log in
        public function two_fb_form( $user , $err=false, $title=false)
        {
        	$this->html = new tebravo_html();
        	
        	$output = "<p>";
        	if(!$title){
        		$output .= __("Verify your login with your Facebook account", TEBRAVO_TRANS)."<br />";
        	} else {
        		$output .= $title;
        	}
        	$output .= "<br />";
        	$output .= "<div id=\"fb-root\"></div>
<script>(function(d, s, id) {
  var js, fjs = d.getElementsByTagName(s)[0];
  if (d.getElementById(id)) return;
  js = d.createElement(s); js.id = id;
  js.src = \"//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.9&appId=".TEBRAVO_APPID."\";
  fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));</script>";
        	
        	$output .= "<script>
  // This is called with the results from from FB.getLoginStatus().
  function statusChangeCallback(response) {
    console.log('statusChangeCallback');
    console.log(response);
 
    if (response.status === 'connected') {
      // Logged into your app and Facebook.
      LoginApi();
    } else {
      // The person is not logged into your app or we are unable to tell.
      document.getElementById('status').innerHTML = 'Please log ' +
        'into this app.';
    }
  }


  function checkLoginState() {
    FB.getLoginStatus(function(response) {
      statusChangeCallback(response);
    });
  }

  window.fbAsyncInit = function() {
  FB.init({
    appId      : '".TEBRAVO_APPID."',
    cookie     : true,  
    xfbml      : true,  
    version    : 'v2.8'
  });


  FB.getLoginStatus(function(response) {
    statusChangeCallback(response);
  });

  };

  (function(d, s, id) {
    var js, fjs = d.getElementsByTagName(s)[0];
    if (d.getElementById(id)) return;
    js = d.createElement(s); js.id = id;
    js.src = \"//connect.facebook.net/en_US/sdk.js\";
    fjs.parentNode.insertBefore(js, fjs);
  }(document, 'script', 'facebook-jssdk'));

  
  function LoginApi() {
    console.log('Welcome!  Fetching your information.... ');
    FB.api('/me', function(response) {
		document.cookie = '".TEBRAVO_DBPREFIX."fbid='+ response.id +'';
jQuery('#wp-submit').show();
jQuery('#tebravo_status').show();
jQuery('#fb_btn').hide();
jQuery('#tebravo_fbid').val(response.id);
jQuery('#tebravo_fbimg').show();
jQuery('#tebravo_fbimg').attr('src','http://graph.facebook.com/'+response.id+'/picture?type=square');
    });
  }
</script>

<div id='fb_btn' class=\"fb-login-button\" data-max-rows=\"1\" data-size=\"large\" data-button-type=\"continue_with\" 
data-show-faces=\"false\" data-auto-logout-link=\"false\" data-use-continue-as=\"false\"></div>
<div id=\"status\">
<div id='tebravo_status' style='display:none;'>
<img src='' id='tebravo_fbimg' style='display:none;'><br />
Facebook ID:<br />
<input type='text' name='".TEBRAVO_DBPREFIX."fbid' id='tebravo_fbid' value='' readonly>
</div></div>";
        	$output .= "</p>";        	
        	
        	return $output;
        }
        
        //the 2-factor authentication box to continue log in
        public function two_fa_form( $user , $err=false)
        {
        	$output = "<p>";
        	$output .= "<label for='".TEBRAVO_DBPREFIX."2fa'>".__("Authentication Code", TEBRAVO_DBPREFIX)."<br />";
        	$output .= "<input type='tel' class='input' name='".TEBRAVO_DBPREFIX."2fa' id='".TEBRAVO_DBPREFIX."2fa' pattern=\"[0-9]*\"><br />";
        	$output .= "</p>";
        	$two_fa_method = trim(esc_html(get_user_meta($user->ID, TEBRAVO_DBPREFIX.'2fa_method', true)));
        	if($two_fa_method == 'mobile_app'){
        		$output .= "<font style='font-size:0.8em;color:#c3c3c3'>".__("Example: Mobile App", TEBRAVO_DBPREFIX)."</font><br />";
        		$output .= "<img src='".plugins_url('assets/img/auth_example.png', TEBRAVO_PATH)."'>";
        	} else if($two_fa_method == 'email')
        	{
        		$output .= __("Check your email .. inbox or junk", TEBRAVO_TRANS);
        		$this->send_email_auth( $user, $err );
        		
        	}
        	
        	return $output;
        }
        
        //send email with auth code
        protected function send_email_auth( $user , $error=false)
        {
        	if(! $user  || ! is_a( $user, 'WP_User' ))
        	{
        		$user = wp_get_current_user();
        	}
        	
        	if(!$error
        			&& get_user_meta($user->ID, TEBRAVO_DBPREFIX.'2fa_fresh_email_code', true) == ''){
	        	$email = $user->user_email;
	        	$username = $user->user_login;
	        	$sitename = tebravo_utility::get_bloginfo( 'name' );
	        	
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
	        	
	        	add_filter( 'wp_mail_content_type' , function(){
	        		return "text/html";
	        	});
	        		
	        	wp_mail($email, $subject, $message);
        	}
        }
        
        //login box for validation
        public function login_form_bravo_fa()
        {
        	global $interim_login;
        	wp_enqueue_style (TEBRAVO_SLUG."_flags_css", plugins_url(TEBRAVO_SLUG.'/assets/css/flags.css'));
        	
        	if( !isset( $_POST['_tebravo_auth']) && !isset( $_POST['_tebravo_nonce'] ) ){return;}
        	if( $_POST['_tebravo_auth'] > 0)
        	{
        		$user = get_userdata( esc_html($_POST['_tebravo_auth']) );
        		
        		if( !$user ){return; }
        		
	        	$err = '';
	        	if( empty($_POST['_tebravo_auth'])){$err = 1;}
	        	if( empty($_POST['_tebravo_nonce'])){$err = 1;}
	        	//verify nonce
	        	if(!$this->verify_nonce($user->ID, esc_html($_POST['_tebravo_nonce']))){$err = 1;}
	        	
	        	if($err == 1){tebravo_redirect_js(tebravo_utility::get_bloginfo('siteurl'), true); exit;}
	        	
	        	
	        	//redirect_to
	        	if( empty( $_REQUEST['redirect_to'] )){ $_REQUEST['redirect_to'] = '';}
	        	
	        	//check and validate
	        	
	        	if($this->twofa_default == '2fa')
	        	{
	        		$this->validate_2fa( $user );
	        	} else if($this->twofa_default == 'fb')
	        	{
	        		$this->fb_login( $user );
	        	} else if($this->twofa_default == 'pin')
	        	{
	        		$this->validate_pin( $user );
	        	} else if($this->twofa_default == 'q')
	        	{
	        		$this->validate_q( $user );
	        	}
	        	
	        	//delete user meta
	        	if( isset( $_POST[TEBRAVO_DBPREFIX.'2fa'] )){
	        		delete_user_meta($user->ID, TEBRAVO_DBPREFIX.'2fa_fresh_email_code', trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'2fa'])));
	        	}
	        	
	        	$interim_login = isset($_REQUEST['interim-login']);
	        	
	        	//set remember me
	        	$rememberme= 0;
	        	if( isset( $_REQUEST['rememberme'])
	        			&& $_REQUEST['rememberme'])
	        	{
	        		$rememberme= 1;
	        	}
	        	
	        	wp_set_auth_cookie($user->ID, $rememberme);
	        	
	        	// check interim login
	        	if( $interim_login ):
		        	$customize_login = isset( $_REQUEST['customize-login'] );
		        	if ( $customize_login ) { wp_enqueue_script( 'customize-base' ); }
		        	$message = '<p class="message">' . __('You have logged in successfully.') . '</p>';
		        	$interim_login = 'success';
		        	login_header( '', $message );
		        	?> </div> <?php 
		        	do_action( 'login_footer' );
		        	if ( $customize_login ) :
		        		?> <script type="text/javascript">setTimeout( function(){ new wp.customize.Messenger({ url: '<?php echo wp_customize_url(); ?>', channel: 'login' }).send('login') }, 1000 );</script> <?php 
		        	endif;
		        	?> </body></html><?php 
		        	exit;
		        endif;
		        
		        $redirect_to = apply_filters( 'login_redirect', esc_url($_REQUEST['redirect_to']), esc_url($_REQUEST['redirect_to']), $user );
		        if( $redirect_to == admin_url() ){$redirect_to = admin_url( 'index.php' );}
		        wp_safe_redirect( $redirect_to );
		        exit;
        	}
	        
        }
        
        //validate two factor from login box
        protected function validate_2fa( $user )
        {
        	
        	$twofa_c = new tebravo_2fa();
        	
        	$secret_key = get_user_meta( $user->ID, TEBRAVO_DBPREFIX.'2fa_secret_key', true);
        	
        	$two_fa_method = trim(esc_html(get_user_meta($user->ID, TEBRAVO_DBPREFIX.'2fa_method', true)));
        	if($two_fa_method == 'mobile_app'){
        		if($twofa_c->verifyCode($secret_key, trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'2fa']))) !== true)
        		{
        			$error = __("Sorry! Wrong Code", TEBRAVO_TRANS);
        			#echo $twofa_c->getCode($secret_key);
        			$login_nonce = $this->create_nonce( $user->ID );
        			$this->print_loginbox($user, $login_nonce['key'], $error, esc_url($_REQUEST['redirect_to']));
        			exit;
        		}
        	} else {
        		if($this->veridy_email_code($user, trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'2fa']))) !== true)
        		{
        			$error = __("Sorry! Wrong Code, try login again.", TEBRAVO_TRANS);
        			#echo $twofa_c->getCode($secret_key);
        			$login_nonce = $this->create_nonce( $user->ID );
        			$this->print_loginbox($user, $login_nonce['key'], $error, esc_url($_REQUEST['redirect_to']));
        			exit;
        		}
        	}
        }
        
        //verify 2fa from user meta
        protected function veridy_email_code( $user , $code)
        {
        	if( !$user )
        	{
        		$user = wp_get_current_user();
        	}
        	
        	$code_in_meta = get_user_meta($user->ID, TEBRAVO_DBPREFIX.'2fa_fresh_email_code', true);
        	$code_in_meta = trim(esc_html( $code_in_meta ));
        	
        	if( $code == $code_in_meta) {return true;}
        	else{ return false;}
        }
        
        //handle login URL
        public function login_url( $action=false )
        {
        	//standard wp login url
        	$login_url = wp_login_url();
        	$login_url = set_url_scheme( $login_url , 'login-post' );
        	if(! $action )
        	{
        		$login_url = add_query_arg( 'action' , 'bravo_fa' , $login_url );
        	} else {
        		$login_url = add_query_arg( 'action' , $action , $login_url );
        	}
        	
        	//wpe
        	if( isset( $_GET['wpe-login'] )
        			&&
        			!preg_match( '/[&?]wpe-login=/', $login_url ))
        	{
        	    $login_url = add_query_arg( 'wpe-login' , esc_html( $_GET['wpe-login'] ) , $login_url );
        	}
        	
        	return $login_url;
        }
        
        //validate Facebook login
        public function fb_login( $user )
        {
        	if( !$user )
        	{
        		$user = wp_get_current_user();
        	}
        	
        	$html = new tebravo_html();
        	$posted_fbid = '';
        	if( isset($_POST[TEBRAVO_DBPREFIX.'fbid'] ))
        	{
        		$posted_fbid = trim( esc_html( esc_js( $_POST[TEBRAVO_DBPREFIX.'fbid'] ) ) );
        	}
        	
        	$meta_fbid = get_user_meta($user->ID , TEBRAVO_DBPREFIX.'fbid', true);
        	$meta_fbid = trim( esc_html( $meta_fbid ) );
        	$meta_fbid = tebravo_decodeString($meta_fbid, $html->init->security_hash);
        	
        	if($meta_fbid != $posted_fbid)
        	{
        		$error = __("Sorry! Your Facebook ID does not match the stored Facebook ID in our database.", TEBRAVO_TRANS);
        		$login_nonce = $this->create_nonce( $user->ID );
        		$this->print_loginbox($user, $login_nonce['key'], $error, esc_url($_REQUEST['redirect_to']));
        		exit;
        	}
        }
        
        //validate PIN code login
        public function validate_pin( $user )
        {
        	if( !$user )
        	{
        		$user = wp_get_current_user();
        	}
        	
        	$html = new tebravo_html();
        	
        	$posted_p1 = '';
        	$posted_p2 = '';
        	$posted_p3 = '';
        	$posted_p4 = '';
        	if( isset($_POST[TEBRAVO_DBPREFIX.'p1'])
        	 	&&isset($_POST[TEBRAVO_DBPREFIX.'p2'])
        	 	&&isset($_POST[TEBRAVO_DBPREFIX.'p3'])
        	 	&&isset($_POST[TEBRAVO_DBPREFIX.'p4'])
        			)
        	{
        		$posted_p1 = trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'p1']));
        		$posted_p2 = trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'p2']));
        		$posted_p3 = trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'p3']));
        		$posted_p4 = trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'p4']));
        	}
        	
        	$posted_pin = $posted_p1.'-'.$posted_p2.'-'.$posted_p3.'-'.$posted_p4;
        	
        	$meta_pin = get_user_meta($user->ID , TEBRAVO_DBPREFIX.'pin', true);
        	$meta_pin = trim( esc_html( $meta_pin) );
        	$meta_pin= tebravo_decodeString($meta_pin, $html->init->security_hash);
        	
        	if($meta_pin != $posted_pin)
        	{
        		$error = __("Sorry! The posted PIN code does not match the stored PIN code in our database.", TEBRAVO_TRANS);
        		$login_nonce = $this->create_nonce( $user->ID );
        		$this->print_loginbox($user, $login_nonce['key'], $error, esc_url($_REQUEST['redirect_to']));
        		exit;
        	}
        }
        
        //validate security questions and answer
        public function validate_q( $user )
        {
        	if( !$user )
        	{
        		$user = wp_get_current_user();
        	}
        	
        	$html = new tebravo_html();
        	
        	$posted_a = ''; $posted_q = '';
        	if( isset($_POST[TEBRAVO_DBPREFIX.'q']) )
        	{
        		$posted_q = trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'q']));
        	}
        	if( isset($_POST[TEBRAVO_DBPREFIX.'a']) )
        	{
        		$posted_a = trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'a']));
        	}
        	
        	$meta_q = get_user_meta($user->ID , TEBRAVO_DBPREFIX.'q', true);
        	$meta_q = trim( esc_html( $meta_q) );
        	$meta_q = tebravo_decodeString($meta_q, $html->init->security_hash);
        	
        	$meta_a = get_user_meta($user->ID , TEBRAVO_DBPREFIX.'a', true);
        	$meta_a = trim( esc_html( $meta_a) );
        	$meta_a = tebravo_decodeString($meta_a, $html->init->security_hash);
        	
        	if($meta_q != $posted_q)
        	{
        		$error = __("Sorry! The question or the answer or both are wrong.", TEBRAVO_TRANS);
        		$login_nonce = $this->create_nonce( $user->ID );
        		$this->print_loginbox($user, $login_nonce['key'], $error, esc_url($_REQUEST['redirect_to']));
        		exit;
        	}
        	
        	if($meta_a != $posted_a)
        	{
        		$error = __("Sorry! The question or the answer or both are wrong.", TEBRAVO_TRANS);
        		$login_nonce = $this->create_nonce( $user->ID );
        		$this->print_loginbox($user, $login_nonce['key'], $error, esc_url($_REQUEST['redirect_to']));
        		exit;
        	}
        }
    }
    
    new tebravo_login();
}
?>