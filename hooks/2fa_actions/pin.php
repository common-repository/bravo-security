<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}


//show // edit
add_action('show_user_profile', 'tebravo_pin_profile_form');
#add_action('edit_user_profile', 'tebravo_fb_profile_form');
//update
add_action( 'personal_options_update' , 'tebravo_update_pin_profile' );
#add_action( 'edit_user_profile_update' , 'tebravo_update_fb_profile' );

if(! function_exists( 'tebravo_pin_profile_form' ) )
{
    function tebravo_pin_profile_form()
    {
        
        $user = wp_get_current_user();
        
        if($user->ID > 0)
            
            $html = new tebravo_html();
            if(get_user_meta($user->ID, TEBRAVO_DBPREFIX.'pin_enabled', true) == 'checked')
            {
                $checked = "checked";
            } else {$checked = "";}
            
            $tebravo_login = new tebravo_login();
            
            $p1='';$p2='';$p3='';$p4='';
            if(get_user_meta($user->ID, TEBRAVO_DBPREFIX.'pin', true) != '')
            {
                $pin = get_user_meta($user->ID, TEBRAVO_DBPREFIX.'pin', true);
                $pin = tebravo_decodeString($pin, $html->init->security_hash);
                $exp = explode("-", $pin);
                if(is_array($exp)){
	                $p1 = $exp[0];
	                $p2 = $exp[1];
	                $p3 = $exp[2];
	                $p4 = $exp[3];
                }
            }
            
            $output[] = "<hr><A name='bravo_2fa'></A><h2>".__("Two Steps Login Settings", TEBRAVO_TRANS)."</h2><hr>";
            $output[] = "<input type='hidden' name='_nonce' value='".$html->init->create_nonce('2fa-profile-pin')."'>";
            $output[] = "<table border=0 cellspacing=0 width=100% class='form-table' style='background:#E3E3E3;'>";
            //enabled
            $output[] = "<tr><td><strong>".__("Pin Code Login Verification", TEBRAVO_TRANS)."</strong> <br />";
            $output[] = "<input type='checkbox' value='checked' name='".TEBRAVO_DBPREFIX."pin_enabled' $checked id='pin_enabled'>";
            $output[] = "<label for='pin_enabled'>".__("Enabled", TEBRAVO_TRANS)."</label></td></tr>";
            //method
            $output[] = "<tr><td><strong>".__("Choose your PIN", TEBRAVO_TRANS)."</strong><br />";
            $output[] = "<input id='tebravo_p1' class='tebravo_inputs' type='text' min='0' maxlength='1' name='".TEBRAVO_DBPREFIX."p1' value='{$p1}' onkeypress=\"return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57\" style='width:55px;'>";
            $output[] = "<input class='tebravo_inputs' type='text' min='0' maxlength='1' name='".TEBRAVO_DBPREFIX."p2' value='{$p2}' onkeypress=\"return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57\" style='width:55px;'>";
            $output[] = "<input class='tebravo_inputs' type='text' min='0' maxlength='1' name='".TEBRAVO_DBPREFIX."p3' value='{$p3}' onkeypress=\"return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57\" style='width:55px;'>";
            $output[] = "<input class='tebravo_inputs' type='text' min='0' maxlength='1' name='".TEBRAVO_DBPREFIX."p4' value='{$p4}' onkeypress=\"return (event.charCode == 8 || event.charCode == 0) ? null : event.charCode >= 48 && event.charCode <= 57\" style='width:55px;'>";
            $output[] = "</td>";

            
            $output[] = "</table></div>";
            if($tebravo_login->check_by_role($user) == true)
            {
                echo implode("\n", $output);
                ?>
                <script>
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
</script>
<style>
.tebravo_inputs{text-align: center;}
</style>
                <?php 
            }
    }
}


if( !function_exists( 'tebravo_update_pin_profile' ) )
{
    function tebravo_update_pin_profile()
    {
        $html = new tebravo_html();
        if(!empty($_POST['_nonce']) && false !== wp_verify_nonce($_POST['_nonce'], $html->init->security_hash.'2fa-profile-pin'))
        {
            $user = wp_get_current_user();
            
            if($user->ID > 0)
                
                $tebravo_login = new tebravo_login();
                if($tebravo_login->check_by_role($user) == true)
                {
                	//update option
                	$pin_enabled = '';
                	if( isset($_POST[TEBRAVO_DBPREFIX.'pin_enabled']) )
                	{
                		$pin_enabled = trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'pin_enabled']));
                	}
                	update_user_meta($user->ID, TEBRAVO_DBPREFIX.'pin_enabled', $pin_enabled);
                	
                	//update PIN
                	$pin = '';
                	if( isset($_POST[TEBRAVO_DBPREFIX.'p1'])
                			&& isset($_POST[TEBRAVO_DBPREFIX.'p2'])
                			&& isset($_POST[TEBRAVO_DBPREFIX.'p3'])
                			&& isset($_POST[TEBRAVO_DBPREFIX.'p4'])
                			)
                	{
                		$p1 = intval(trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'p1'])));
                		$p2 = intval(trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'p2'])));
                		$p3 = intval(trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'p3'])));
                		$p4 = intval(trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'p4'])));
                		
                		$pin = $p1.'-'.$p2.'-'.$p3.'-'.$p4;
                		$pin = tebravo_encodeString($pin, $html->init->security_hash);
                		
                	}
                	
                	update_user_meta($user->ID, TEBRAVO_DBPREFIX.'pin', $pin);
                	  
                }
        }
    }
}
?>