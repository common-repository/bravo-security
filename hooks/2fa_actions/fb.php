<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if(! defined('TEBRAVO_APPID')){
    define('TEBRAVO_APPID', trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'two_step_facebook_app'))));
}

if(! defined('TEBRAVO_APPSECRET')){
    define('TEBRAVO_APPSECRET', trim(esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'two_step_facebook_app_secret'))));
}

//show // edit
add_action('show_user_profile', 'tebravo_fb_profile_form');
#add_action('edit_user_profile', 'tebravo_fb_profile_form');
//update
add_action( 'personal_options_update' , 'tebravo_update_fb_profile' );
#add_action( 'edit_user_profile_update' , 'tebravo_update_fb_profile' );

if(! function_exists( 'tebravo_fb_profile_form' ) )
{
	function tebravo_fb_profile_form()
	{
		
		$user = wp_get_current_user();
		
		if($user->ID > 0)
		
		$html = new tebravo_html();
		if(get_user_meta($user->ID, TEBRAVO_DBPREFIX.'fb_enabled', true) == 'checked')
		{
			$checked = "checked";
		} else {$checked = "";}
		
		$tebravo_login = new tebravo_login();
		
		$output[] = "<hr><A name='bravo_2fa'></A><h2>".__("Two Steps Login Settings", TEBRAVO_TRANS)."</h2><hr>";
		$output[] = "<input type='hidden' name='_nonce' value='".$html->init->create_nonce('2fa-profile-fb')."'>";
		$output[] = "<table border=0 cellspacing=0 width=100% class='form-table' style='background:#E3E3E3;'>";
		//enabled
		$output[] = "<tr><td><th>".__("Facebook Login Verification", TEBRAVO_TRANS)."</th></td>";
		$output[] = "<td><input type='checkbox' value='checked' name='".TEBRAVO_DBPREFIX."fb_enabled' $checked id='fb_enabled'>";
		$output[] = "<label for='fb_enabled'>".__("Enabled", TEBRAVO_TRANS)."</label></td></tr>";
		//method
		$output[] = "<tr><td><th>".__("Login", TEBRAVO_TRANS)."</th></td>";
		$output[] = "<td>";
		$output[] = $tebravo_login->two_fb_form($user);
		$output[] = "</td></tr>";
		
		$output[] = "</table></div>";
		if($tebravo_login->check_by_role($user) == true)
		{
			echo implode("\n", $output);
		}
	}
}


if( !function_exists( 'tebravo_update_fb_profile' ) )
{
	function tebravo_update_fb_profile()
	{		
		$html = new tebravo_html();
		if(!empty($_POST['_nonce']) && false !== wp_verify_nonce($_POST['_nonce'], $html->init->security_hash.'2fa-profile-fb'))
		{
			$user = wp_get_current_user();
			
			if($user->ID > 0)
			
			$tebravo_login = new tebravo_login();
			if($tebravo_login->check_by_role($user) == true)
			{
				//update option
				$fb_enabled = '';
				if( isset($_POST[TEBRAVO_DBPREFIX.'fb_enabled']) )
				{
					$fb_enabled = trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'fb_enabled']));
				}
				update_user_meta($user->ID, TEBRAVO_DBPREFIX.'fb_enabled', $fb_enabled);
				
				//update FB ID
				$fbid = '';
				if( isset($_POST[TEBRAVO_DBPREFIX.'fbid']) )
				{
					$fbid = trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'fbid']));
					$fbid = tebravo_encodeString($fbid, $html->init->security_hash);
				}
				update_user_meta($user->ID, TEBRAVO_DBPREFIX.'fbid', $fbid);
				
			}
		}
	}
}
?>