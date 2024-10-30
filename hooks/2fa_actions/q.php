<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}


//show // edit
add_action('show_user_profile', 'tebravo_q_profile_form');
#add_action('edit_user_profile', 'tebravo_fb_profile_form');
//update
add_action( 'personal_options_update' , 'tebravo_update_q_profile' );
#add_action( 'edit_user_profile_update' , 'tebravo_update_fb_profile' );

if(! function_exists( 'tebravo_q_profile_form' ) )
{
    function tebravo_q_profile_form()
    {
        
        $user = wp_get_current_user();
        
        if($user->ID > 0)
            
            $html = new tebravo_html();
            if(get_user_meta($user->ID, TEBRAVO_DBPREFIX.'q_enabled', true) == 'checked')
            {
                $checked = "checked";
            } else {$checked = "";}
            
            $tebravo_login = new tebravo_login();
            
            $q = ''; $a = '';
            if(get_user_meta($user->ID, TEBRAVO_DBPREFIX.'q', true) != '')
            {
                $q = get_user_meta($user->ID, TEBRAVO_DBPREFIX.'q', true);
                $q = tebravo_decodeString($q, $html->init->security_hash);
                
                $a = get_user_meta($user->ID, TEBRAVO_DBPREFIX.'a', true);
                $a = tebravo_decodeString($a, $html->init->security_hash);
            }
            
            $questions = tebravo_questions_list();
            if(is_array( $questions ))
            {
            	$options = '';
            	foreach ($questions as $key => $value)
            	{
            		$options .= "<option value='".esc_html($key)."' ";
            		if( $q == $key){ $options .= "selected";}
            		$options .= ">".esc_html($value)."</option>";
            	}
            }
            
            $output[] = "<hr><A name='bravo_2fa'></A><h2>".__("Two Steps Login Settings", TEBRAVO_TRANS)."</h2><hr>";
            $output[] = "<input type='hidden' name='_nonce' value='".$html->init->create_nonce('2fa-profile-q')."'>";
            $output[] = "<table border=0 cellspacing=0 width=100% class='form-table' style='background:#E3E3E3;'>";
            //enabled
            $output[] = "<tr><td><strong>".__("Security Questions Login Verification", TEBRAVO_TRANS)."</strong> <br />";
            $output[] = "<input type='checkbox' value='checked' name='".TEBRAVO_DBPREFIX."q_enabled' $checked id='q_enabled'>";
            $output[] = "<label for='q_enabled'>".__("Enabled", TEBRAVO_TRANS)."</label></td></tr>";
            //question
            $output[] = "<tr><td><strong>".__("Choose your most difficult question", TEBRAVO_TRANS)."</strong><br />";
            $output[] = "<select name='".TEBRAVO_DBPREFIX."q'>";
            $output[] = $options;
            $output[] = "</select>";
            $output[] = "</td></tr>";
            //answer
            $output[] = "<tr><td><strong>".__("Write a good answer", TEBRAVO_TRANS)."</strong><br />";
            $output[] = "<input type='text' name='".TEBRAVO_DBPREFIX."a' value='{$a}' style='width:35%'>";
            $output[] = "</td></tr>";

            
            $output[] = "</table></div>";
            if($tebravo_login->check_by_role($user) == true)
            {
                echo implode("\n", $output);
                
            }
    }
}


if( !function_exists( 'tebravo_update_q_profile' ) )
{
    function tebravo_update_q_profile()
    {
        $html = new tebravo_html();
        if(!empty($_POST['_nonce']) && false !== wp_verify_nonce($_POST['_nonce'], $html->init->security_hash.'2fa-profile-q'))
        {
            $user = wp_get_current_user();
            
            if($user->ID > 0)
                
                $tebravo_login = new tebravo_login();
                if($tebravo_login->check_by_role($user) == true)
                {
                	//update option
                	$q_enabled = '';
                	if( isset($_POST[TEBRAVO_DBPREFIX.'q_enabled']) )
                	{
                		$q_enabled = trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'q_enabled']));
                	}
                	update_user_meta($user->ID, TEBRAVO_DBPREFIX.'q_enabled', $q_enabled);
                	
                	//update question
                	$q = '';
                	if( isset($_POST[TEBRAVO_DBPREFIX.'q']))
                	{
                		$q = (trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'q'])));
                		$q = tebravo_encodeString($q, $html->init->security_hash);
                	}
                	update_user_meta($user->ID, TEBRAVO_DBPREFIX.'q', $q);
                	
                	//update answer
                	$a = '';
                	if( isset($_POST[TEBRAVO_DBPREFIX.'q']))
                	{
                		$a = (trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'a'])));
                		$a = tebravo_encodeString($a, $html->init->security_hash);
                	}
                	update_user_meta($user->ID, TEBRAVO_DBPREFIX.'a', $a);
                }
        }
    }
}
?>