<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !class_exists( 'tebravo_recaptcha' ) )
{
	class tebravo_recaptcha
	{
		//options
		private 
			$site_key,
			$secret_key;
		
		public 
			$theme,
			$api_url,
			$js_url,
			$settings=array(),
			$language,
			$ip;
		
		//constructor
		public function __construct()
		{
			//site language
			$lang = get_locale();
			$exp_lang = explode('_', $lang);
			$this->language = $exp_lang[0];
			
			//api options
			//$this->ip = tebravo_agent::user_ip();
			$this->api_url = 'https://www.google.com/recaptcha/api/siteverify';
			$this->js_url = 'https://www.google.com/recaptcha/api.js?hl='.$this->language;
			
			//reCAPTACHA options
			$this->site_key = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'recaptcha_site_key' ) ) );
			$this->secret_key = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'recaptcha_secret_key' ) ) );
			$this->theme = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'recaptcha_theme' ) ) );
			
			//settings
			$this->settings['comments'] = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'recaptcha_comment' ) ) );
			$this->settings['login'] = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'recaptcha_login' ) ) );
			$this->settings['register'] = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'recaptcha_register' ) ) );
			$this->settings['resetpw'] = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'recaptcha_resetpw' ) ) );
			
			//actions and filters
			add_action( 'init', array( $this, 'actions' ) );
		}
		
		//add actions and filters
		public function actions()
		{
			//reCAPATCHA for comments
			if( $this->settings['comments'] == 'checked' )
			{
				$user = wp_get_current_user();
				
				if( $user->ID == 0 ):
					if ( version_compare( $GLOBALS['wp_version'], '4.2', '>=' ) ) {
						add_filter( 'comment_form_submit_button', array( $this, 'comment_form_submit_button' ) );
					} else {
						add_filter( 'comment_form_field_comment', array( $this, 'comment_form_field_comment' ) );
					}
					add_filter( 'preprocess_comment', array( $this, 'preprocess_comment' ) );
				endif;
			}
			
			//reCAPATCHA for login
			if( $this->settings['login'] == 'checked' )
			{
				add_action( 'login_form', array( $this, 'wplogin_form' ) );
				add_filter( 'wp_authenticate_user', array( $this, 'wp_authenticate_user' ) );
			}
			
			//reCAPATCHA for register
			if( $this->settings['register'] == 'checked' )
			{
				add_action( 'register_form', array( $this, 'wplogin_form' ) );
				add_filter( 'registration_errors', array( $this, 'registration_errors' ) );
			}
			
			
		}
		
		//validate reCAPTCHA for login
		public function wp_authenticate_user( $user )
		{
			if( is_wp_error( $user ) 
					|| defined( 'XMLRPC_REQUEST' ) )
			{
				return $user;
			}
			
			$results = $this->validate();
			#var_export($_POST);
			if( $results !== 1 )
			{
				return new WP_Error('recaptcha_error', $this->error( $results ) );
			}
			
			return $user;
		}
		
		//validate reCAPATCHA for register form
		public function registration_errors( $errors )
		{
			$results = $this->validate();
			
			if( $results !== 1 )
			{
				$errors->add( 'recaptcha_error', $this->error( $results ) );
			}
			
			return $errors;
		}
		
		//validate reCAPTCHA for reset password
		public function lostpassword_post( $errors )
		{
			
			$results = $this->validate();
			#var_export($_POST);
			if( $results !== 1 )
			{
				$errors->add('recaptcha_error', $this->error( $results ) );
			}
			
			return $errors;
		}
		
		//validate reCAPTCHA for login
		public function preprocess_comment( $comment_data )
		{
			
			$results = $this->validate();
			#var_export($_POST);
			if( $results !== 1 )
			{
				wp_die( $this->error( $results ) );
			}
			
			//define action
			do_action( 'tebravo_preprocess_comment' );
			return $comment_data;
		}
		
		//reCAPTCHA for login form
		public function wplogin_form() {
			
			$css_style = "transform:scale(0.90);-webkit-transform:scale(0.90);transform-origin:0 0;-webkit-transform-origin:0 0;";
			
			$this->recaptcha_field( true, true, 0, 0, 10, 0, $css_style);
		}
		
		//reCAPTCHA for comments WP v -4.2
		public function comment_form_field_comment( $comment_field )
		{
			$comment_field .= $this->recaptcha_field( false ,0,0,5);
			
			return $comment_field;
			
		}
		
		//reCAPTCHA for comments WP v +4.2
		public function comment_form_submit_button( $submit_button )
		{
			$submit_button = $this->recaptcha_field( false ,0,0,15) . $submit_button;
			
			return $submit_button;
		}
		
		//recaptcha field
		public function recaptcha_field( $print=true, $noscript=true, $top=0, $bottom=0, $right=0, $left=0, $css_style=false)
		{
			//margins as integer
			$top = absint( $top );
			$bottom = absint( $bottom );
			$right = absint( $right );
			$left = absint( $left );
			
			//the field
			$field = '';
			$field .= '<div ';
			$field .= 'data-theme="'. $this->theme. '" ';
			$field .= 'style="margin-top:'. $top. 'px; margin-right:'.$right.'px; margin-bottom:'.$bottom.'px; margin-left:'.$left.'px; ';
			
			if( $this->language == 'en')
			{
				if( $css_style )
				{
					$field .= $css_style;
				}
			}
			
			$field .= '"';
			$field .= 'class="g-recaptcha" data-sitekey="'.$this->site_key.'"></div><br />';
			$field .= '<script src="'.$this->js_url.'" async defer></script>';
			
			if ( true === $noscript ) {
				$field .= '<noscript>'.PHP_EOL;
				$field .= '<div style="width: 302px; height: 352px;">'.PHP_EOL;
				$field .= '<div style="width: 302px; height: 352px; position: relative;">'.PHP_EOL;
				$field .= '<div style="width: 302px; height: 352px; position: absolute;">'.PHP_EOL;
				$field .= '<iframe src="https://www.google.com/recaptcha/api/fallback?k=' . esc_attr( $this->site_key ) . '" frameborder="0" scrolling="no" style="width: 302px; height:352px; border-style: none;"></iframe>'.PHP_EOL;
				$field .= '</div>'.PHP_EOL;
				$field .= '<div style="width: 250px; height: 80px; position: absolute; border-style: none; bottom: 21px; left: 25px; margin: 0px; padding: 0px; right: 25px;">'.PHP_EOL;
				$field .= '<textarea id="g-recaptcha-response" name="g-recaptcha-response" class="g-recaptcha-response" style="width: 250px; height: 80px; border: 1px solid #c1c1c1; margin: 0px; padding: 0px; resize: none;" value=""></textarea>'.PHP_EOL;
				$field .= '</div>'.PHP_EOL;
				$field .= '</div>'.PHP_EOL;
				$field .= '</div>'.PHP_EOL;
				$field .= '</noscript>'.PHP_EOL;
			}
			
			//check reCAPATCHA settings at first
			if( $this->site_key !='' && $this->secret_key !='')
			{	
				if( true === $print )
				{
					echo $field;
				}
				
				return $field;
			}
		}
		
		//validate reCAPATCHA
		public function validate()
		{
			$output = '';
			//define action
			do_action( 'tebravo_recaptcha_validate' );
			//validate
			if( isset( $_POST['g-recaptcha-response'] )
					&& !empty( $_POST['g-recaptcha-response'] ) )
			{
				$posted_data = array(
								'secret' => $this->secret_key,
								'response' => esc_attr( $_POST['g-recaptcha-response'] ),
								'remoteip' => $this->ip
						);
				
				//get response
				$url = add_query_arg( $posted_data , $this->api_url );
				$result = wp_remote_get( $url );
				
				if( is_wp_error( $result ) )
				{
					return -1;
				} else {
					
					$code_status = json_decode( $result['body'] );
					
					if( isset( $code_status->success ) )
					{
						//successfully verified
						return 1;
					} else {
						return 0;
					}
				}
			} else {
				return -2;
			}
			
			#return $output;
		}
		
		//define reCAPTCHA errors
		public function error( $errno=-2 )
		{
			switch ($errno)
			{
				case 0:
					return __("You must confirm that you are a human.", TEBRAVO_TRANS );
					break;
				case -1:
					return __("We can not confirm that you are a human.", TEBRAVO_TRANS );
					break;
				case -2:
					return __("We did not receive confirmation that you are a human.", TEBRAVO_TRANS );
					break;
			}
		}
		
		//dashboard HTML and settings
		public function dashboard()
		{
			
			$this->html = new tebravo_html();
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			$desc = "reCAPTCHA is important to save your host resources and your Wordpress safe from spam.";
			$this->html->header(__("reCAPTCHA Dashboard", TEBRAVO_TRANS), $desc, 'recaptcha.png');
			
			//Tabs Data
			$tabs["general"] = array("title"=>"Options",
					"href"=>$this->html->init->admin_url."-settings",
					"is_active"=> "");
			
			$tabs["wpconfig"] = array("title"=>"WP Config",
					"href"=>$this->html->init->admin_url."-wconfig",
					"is_active"=> '');
			
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
					"is_active"=> 'active');
			
			$tabs["error_pages"] = array("title"=>"Error Pages",
					"href"=>$this->html->init->admin_url."-error_pages",
					"is_active"=> '');
			
			//Tabs HTML
			$this->html->tabs($tabs);
			$this->html->start_tab_content();
			
			$output[] = "<form action='".$this->html->init->admin_url."-recaptcha' method=post>";
			$output[] = "<input type='hidden' name='_nonce' value='".$this->html->init->create_nonce('recpatcha-settings')."'>";
			$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
			$output[] = "<table border='0' width=100% cellspacing=0>";
			
			//enable / disable recaptcha_comment tool
			$recaptcha_comment = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'recaptcha_comment') ) );
			$recaptcha_comment_yes = '';
			$recaptcha_comment_no = '';
			if( $recaptcha_comment == 'checked' ){$recaptcha_comment_yes = 'checked';} else {$recaptcha_comment_no = 'checked';}
			$recaptcha_comment_help = __('reCAPTCHA field will appear for guests while they are posting new comments.', TEBRAVO_TRANS);
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Posting New Comments", TEBRAVO_TRANS)."</strong></td></tr>";
			$output[] = "<tr class='tebravo_underTD'><td width=80%>".$recaptcha_comment_help."</td><td>";
			$output[] = "<input type='radio' name='recaptcha_comment' value='checked' id='recaptcha_comment_checked' $recaptcha_comment_yes>";
			$output[] = "<label for='recaptcha_comment_checked'><span></span>".__("Enable", TEBRAVO_TRANS)."</label>";
			$output[] = "<input type='radio' name='recaptcha_comment' value='no' id='recaptcha_comment_no' $recaptcha_comment_no>";
			$output[] = "<label for='recaptcha_comment_no'><span></span>".__("Disable", TEBRAVO_TRANS)."</label>";
			$output[] = "</td></tr>";
			
			//enable / disable recaptcha_login tool
			$recaptcha_login = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'recaptcha_login') ) );
			$recaptcha_login_yes = '';
			$recaptcha_login_no = '';
			if( $recaptcha_login == 'checked' ){$recaptcha_login_yes = 'checked';} else {$recaptcha_login_no = 'checked';}
			$recaptcha_login_help = __('reCAPTCHA field will appear for guests while they are trying to login.', TEBRAVO_TRANS);
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Members Login", TEBRAVO_TRANS)."</strong></td></tr>";
			$output[] = "<tr class='tebravo_underTD'><td width=80%>".$recaptcha_login_help."</td><td>";
			$output[] = "<input type='radio' name='recaptcha_login' value='checked' id='recaptcha_login_checked' $recaptcha_login_yes>";
			$output[] = "<label for='recaptcha_login_checked'><span></span>".__("Enable", TEBRAVO_TRANS)."</label>";
			$output[] = "<input type='radio' name='recaptcha_login' value='no' id='recaptcha_login_no' $recaptcha_login_no>";
			$output[] = "<label for='recaptcha_login_no'><span></span>".__("Disable", TEBRAVO_TRANS)."</label>";
			$output[] = "</td></tr>";
			
			//enable / disable recaptcha_register tool
			$recaptcha_register = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'recaptcha_register') ) );
			$recaptcha_register_yes = '';
			$recaptcha_register_no = '';
			if( $recaptcha_register == 'checked' ){$recaptcha_register_yes = 'checked';} else {$recaptcha_register_no = 'checked';}
			$recaptcha_register_help = __('reCAPTCHA field will appear for guests while they are trying to register new accounts.', TEBRAVO_TRANS);
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("New Accounts (Register)", TEBRAVO_TRANS)."</strong></td></tr>";
			$output[] = "<tr class='tebravo_underTD'><td width=80%>".$recaptcha_register_help."</td><td>";
			$output[] = "<input type='radio' name='recaptcha_register' value='checked' id='recaptcha_register_checked' $recaptcha_register_yes>";
			$output[] = "<label for='recaptcha_register_checked'><span></span>".__("Enable", TEBRAVO_TRANS)."</label>";
			$output[] = "<input type='radio' name='recaptcha_register' value='no' id='recaptcha_register_no' $recaptcha_register_no>";
			$output[] = "<label for='recaptcha_register_no'><span></span>".__("Disable", TEBRAVO_TRANS)."</label>";
			$output[] = "</td></tr>";
			
			//enable / disable recaptcha_resetpw tool
			$recaptcha_resetpw = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'recaptcha_resetpw') ) );
			$recaptcha_resetpw_yes = '';
			$recaptcha_resetpw_no = '';
			if( $recaptcha_resetpw == 'checked' ){$recaptcha_resetpw_yes = 'checked';} else {$recaptcha_resetpw_no = 'checked';}
			$recaptcha_resetpw_help = __('reCAPTCHA field will appear for guests while they are trying to get new passwords.', TEBRAVO_TRANS);
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Reset Passwords", TEBRAVO_TRANS)."</strong>  <span class='tebravo_trail'>".__("Premium", TEBRAVO_TRANS)."</span></td></tr>";
			$output[] = "<tr class='tebravo_underTD'><td width=80%>".$recaptcha_resetpw_help."</td><td>";
			$output[] = "<input type='radio' name='recaptcha_resetpw' value='checked' id='recaptcha_resetpw_checked' disabled>";
			$output[] = "<label for='recaptcha_resetpw_checked'><span></span>".__("Enable", TEBRAVO_TRANS)."</label>";
			$output[] = "<input type='radio' name='recaptcha_resetpw' value='no' id='recaptcha_resetpw_no'  disabled>";
			$output[] = "<label for='recaptcha_resetpw_no'><span></span>".__("Disable", TEBRAVO_TRANS)."</label>";
			$output[] = "</td></tr>";
			
			//reCAPTCHA options
			$recaptcha_site_key = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'recaptcha_site_key') ) );
			$recaptcha_secret_key = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'recaptcha_secret_key') ) );
			$recaptcha_theme = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'recaptcha_theme') ) );
			$create_key = "<a href='https://www.google.com/recaptcha/admin#list' target=_blank>".__("Create Key and Secret", TEBRAVO_TRANS)."</a>";
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("API Settings (from google)", TEBRAVO_TRANS)."</strong> [$create_key]</td></tr>";
			//site key
			$output[] = "<tr class='tebravo_underTD'><td colspan=2>".__("Site Key", TEBRAVO_TRANS);
			$output[] = ":<br /><input type='text' style='width:35%' name='recaptcha_site_key' value='".$recaptcha_site_key."'>";
			$output[] = "</td></tr>";
			//secret key
			$output[] = "<tr class='tebravo_underTD'><td colspan=2>".__("Secret Key", TEBRAVO_TRANS);
			$output[] = ":<br /><input type='text' style='width:35%' name='recaptcha_secret_key' value='".$recaptcha_secret_key."'>";
			$output[] = "</td></tr>";
			//theme
			$output[] = "<tr class='tebravo_underTD'><td colspan=2>".__("Theme Style", TEBRAVO_TRANS);
			$output[] = ":<br />";
			$output[] = "<select name='recaptcha_theme'>";
				//light
				$theme_style = "<option value='light'";
				if( $recaptcha_theme == 'light' ) {$theme_style .= "selected";}
				$theme_style .= ">".__("Light", TEBRAVO_TRANS)."</option>";
				//dark
				$theme_style .= "<option value='dark'";
				if( $recaptcha_theme == 'dark' ) {$theme_style .= "selected";}
				$theme_style .= ">".__("Dark", TEBRAVO_TRANS)."</option>";
			$output[] = $theme_style;
			$output[] = "</select>";
			$output[] = "</td></tr>";
						
			$output[] = "<tr class='tebravo_underTD'><td colspan=2>";
			$output[] = $this->html->button(__("Save Settings", TEBRAVO_TRANS), 'submit');
			$output[] = "</td></tr>";
			
			$output[] = "</table></div>";
			$output[] = "</form>";
			
			if( !$_POST )
			{
				echo implode("\n", $output);
			} else {
				//save settings
				if( !empty( $_POST['recaptcha_comment'] ) 
						&& !empty( $_POST['recaptcha_login'] ) 
						&& !empty( $_POST['recaptcha_register'] )
						&& !empty( $_POST['_nonce'])
						&& false !== wp_verify_nonce($_POST['_nonce'], $this->html->init->security_hash.'recpatcha-settings'))
				{
					$recaptcha_comment = trim( esc_html( $_POST['recaptcha_comment'] ) );
					$recaptcha_login = trim( esc_html( $_POST['recaptcha_login'] ) );
					$recaptcha_register = trim( esc_html( $_POST['recaptcha_register'] ) );
					$recaptcha_site_key= trim( esc_html( $_POST['recaptcha_site_key'] ) );
					$recaptcha_secret_key = trim( esc_html( $_POST['recaptcha_secret_key'] ) );
					$recaptcha_theme = trim( esc_html( $_POST['recaptcha_theme'] ) );
					
					tebravo_utility::update_option( TEBRAVO_DBPREFIX.'recaptcha_comment' , $recaptcha_comment );
					tebravo_utility::update_option( TEBRAVO_DBPREFIX.'recaptcha_login' , $recaptcha_login );
					tebravo_utility::update_option( TEBRAVO_DBPREFIX.'recaptcha_register' , $recaptcha_register );
					tebravo_utility::update_option( TEBRAVO_DBPREFIX.'recaptcha_site_key' , $recaptcha_site_key );
					tebravo_utility::update_option( TEBRAVO_DBPREFIX.'recaptcha_secret_key' , $recaptcha_secret_key );
					tebravo_utility::update_option( TEBRAVO_DBPREFIX.'recaptcha_theme' , $recaptcha_theme );
					
					echo "Saving ...";
					tebravo_redirect_js($this->html->init->admin_url.'-recaptcha&msg=01');
					
				} else {
					tebravo_redirect_js($this->html->init->admin_url.'-recaptcha&err=02');
					exit;
				}
			}
			$this->html->end_tab_content();
			$this->html->footer();
		}
		
	}
	//run
	new tebravo_recaptcha();
}
?>