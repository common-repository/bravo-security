<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

//show // edit
add_action('show_user_profile', 'tebravo_2fa_profile');
add_action('edit_user_profile', 'tebravo_2fa_profile');
//update
add_action( 'personal_options_update' , 'tebravo_update_2fa_profile' );
add_action( 'edit_user_profile_update' , 'tebravo_update_2fa_profile' );

if( !function_exists( 'tebravo_2fa_profile' ) )
{
	function tebravo_2fa_profile()
	{
		$user_id = get_current_user_id();
		
		if($user_id > 0)
		
		$html = new tebravo_html();
		if(get_user_meta($user_id, TEBRAVO_DBPREFIX.'2fa_enabled', true) == 'checked')
		{
			$checked = "checked";
		} else {$checked = "";}
		
		$methods = array('email', 'mobile app');
		$fa_methods = '';
		foreach ($methods as $method)
		{
			$method_value = str_replace(" ", "_", $method);
			$fa_methods .= "<option value='".$method_value."' ";
			if(get_user_meta($user_id, TEBRAVO_DBPREFIX.'2fa_method', true) == $method_value)
			{
				$fa_methods .= "selected";
			}
			$fa_methods .= ">".ucfirst($method)."</option>";
		}
		
		$output[] = "<hr><A name='bravo_2fa'></A><h2>".__("Two Steps Login Settings", TEBRAVO_TRANS)."</h2><hr>";
		$output[] = "<input type='hidden' name='_nonce' value='".$html->init->create_nonce('2fa-profile-2fa')."'>";
		$output[] = "<table border=0 cellspacing=0 width=100% class='form-table' style='background:#E3E3E3;'>";
		//enabled
		$output[] = "<tr><td><th>".__("Two Factor Authentication", TEBRAVO_TRANS)."</th></td>";
		$output[] = "<td><input type='checkbox' value='checked' name='".TEBRAVO_DBPREFIX."2fa_enabled' $checked id='2fa_enabled'>";
		$output[] = "<label for='2fa_enabled'>".__("Enabled", TEBRAVO_TRANS)."</label></td></tr>";
		//method
		$output[] = "<tr><td><th>".__("Verification Method", TEBRAVO_TRANS)."</th></td>";
		$output[] = "<td><select name='".TEBRAVO_DBPREFIX."2fa_method'>".$fa_methods."</select><hr></td></tr>";
		//download app
		$output[] = "<tr><td><th>".__("Download Mobile App", TEBRAVO_TRANS)."</th></td><td><b><u>".__("Download Mobile App:", TEBRAVO_TRANS)."</u></b><br />";
		$output[] = "<b><u>".__("Andriod", TEBRAVO_TRANS)."</u></b>: ";
		$output[] = "<a href='https://play.google.com/store/apps/details?id=com.google.android.apps.authenticator2&hl=en' target=_blank>Google Authenticator</a>, ";
		$output[] = "<a href='https://play.google.com/store/apps/details?id=com.authy.authy&hl=en' target=_blank>Authy</a>, ";
		$output[] = "<a href='https://play.google.com/store/apps/details?id=org.fedorahosted.freeotp' target=_blank>FreeOTP</a> or ";
		$output[] = "<a href='https://play.google.com/store/apps/details?id=com.toopher.android&hl=en' target=_blank>Toopher</a><br />";
		$output[] = "<b><u>".__("IOS", TEBRAVO_TRANS)."</u></b>: ";
		$output[] = "<a href='https://itunes.apple.com/us/app/google-authenticator/id388497605?mt=8' target=_blank>Google Authenticator</a>, ";
		$output[] = "<a href='https://itunes.apple.com/us/app/authy/id494168017?mt=8' target=_blank>Authy</a>, ";
		$output[] = "<a href='https://itunes.apple.com/us/app/freeotp-authenticator/id872559395?mt=8' target=_blank>FreeOTP</a> or ";
		$output[] = "<a href='https://itunes.apple.com/us/app/toopher/id562592093?mt=8' target=_blank>Toopher</a>";
		$output[] = "</td></tr>";
		//QR
		$twofa = new tebravo_2fa();
		$user = wp_get_current_user();
		$name = get_bloginfo('name')."(".$user->user_login.")";
		$fa_secret_key = trim(esc_html(get_user_meta($user->ID, TEBRAVO_DBPREFIX.'2fa_secret_key', true)));
		if(!empty($fa_secret_key)){
			$secret_key = trim(esc_html(get_user_meta($user->ID, TEBRAVO_DBPREFIX.'2fa_secret_key', true)));
		} else {
			$secret_key = $twofa->createSecret();
		}
		$img = $twofa->getQRCodeGoogleUrl($name, $secret_key);
		
		$output[] = "<tr><td><th>".__("QR", TEBRAVO_TRANS)."</th></td><td><b><u>".__("Scan QR to verify app:", TEBRAVO_TRANS)."</u></b><br />";
		$output[] = "<img src='".$img."'><br />";
		$output[] = __("or use this secret key to verify app", TEBRAVO_TRANS)." <br />";
		$output[] = "<b>".__("Secret Key", TEBRAVO_TRANS)."</b>: <span style='background:#ccc'>".$secret_key."</span>";
		$output[] = "</td></tr>";
		$output[] = "</table></div>";
		$output[] = "<input type='hidden' name='".TEBRAVO_DBPREFIX."2fa_secret_key' value='".$secret_key."'>";
		
		echo implode("\n", $output);
	}
}

if( !function_exists( 'tebravo_update_2fa_profile' ) )
{
	function tebravo_update_2fa_profile()
	{
		
		$html = new tebravo_html();
		if(!empty($_POST['_nonce']) && false !== wp_verify_nonce($_POST['_nonce'], $html->init->security_hash.'2fa-profile-2fa'))
		{
			$user_id = get_current_user_id();
			
			if($user_id > 0)
			{
				//update option
				$fa_enabled = '';
				if( isset($_POST[TEBRAVO_DBPREFIX.'2fa_enabled']) )
				{
					$fa_enabled = trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'2fa_enabled']));
				}
				update_user_meta($user_id, TEBRAVO_DBPREFIX.'2fa_enabled', $fa_enabled);
				
				//update method
				$fa_method = '';
				if( isset($_POST[TEBRAVO_DBPREFIX.'2fa_method']) )
				{
					$fa_method = trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'2fa_method']));
				}
				update_user_meta($user_id, TEBRAVO_DBPREFIX.'2fa_method', $fa_method);
				
				//update secret key
				$fa_secret_key = '';
				if( isset($_POST[TEBRAVO_DBPREFIX.'2fa_secret_key']) )
				{
					$fa_secret_key = trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'2fa_secret_key']));
				}
				update_user_meta($user_id, TEBRAVO_DBPREFIX.'2fa_secret_key', $fa_secret_key);
			
			}
		}
	}
}

?>