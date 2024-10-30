<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !function_exists( 'tebravo_utility::is_option' ) )
{
	include_once TEBRAVO_DIR.'/includes/tebravo.base.php';
}

if( !function_exists( 'tebravo_install' ) )
{
function tebravo_install()
        {
	
        	global $wpdb;
        	//Update version anyway
            tebravo_utility::update_option( TEBRAVO_DBPREFIX.'verion' , TEBRAVO_VERSION );
            
           
            $security_hash = tebravo_create_hash(8);
            
            //Add General Settings
            $hotlinks_while_listed = "google.com\nyahoo.com\nbing.com\nfacebook.com\nfbcdn.net\ntwitter.com";
            if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'security_hash' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'security_hash' , tebravo_create_hash(8) );}
            if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'adminbar' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'adminbar' , 'checked' );}
            if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'hidewpversion' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'hidewpversion' , 'checked' );}
            if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'remember_delete_unused_themes' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'remember_delete_unused_themes' , '' );}
            if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'remember_delete_unused_plugins' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'remember_delete_unused_plugins' , '' );}
            if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'themes_next_notify' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'themes_next_notify' , '' );}
            if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'plugins_next_notify' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'plugins_next_notify' , '' );}
            if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'development_mode' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'development_mode' , '' );}
            if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'dev_mode_user_rules_admin' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'dev_mode_user_rules_admin' , 'checked' );}
            if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'dev_mode_user_rules_author' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'dev_mode_user_rules_author' , '' );}
            if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'dev_mode_user_rules_editor' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'dev_mode_user_rules_editor' , '' );}
            if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'maintenance_mode' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'maintenance_mode' , '' );}
            if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'close_msg_head' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'close_msg_head' , 'Site is temporarily unavailable.' );}
            if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'close_msg_body' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'close_msg_body' , 'We apologize for any inconvenience.' );}
            if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'prevent_outside_images' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'prevent_outside_images' , '' );}
            if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'prevent_outside_iframe' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'prevent_outside_iframe' , '' );}
            if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'hotlinks_while_list' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'hotlinks_while_list' , $hotlinks_while_listed);}
            if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'hot_linking_img' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'hot_linking_img' , 'http://i.imgur.com/a0YYDvt.jpg');}
            if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'iframe_default_page' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'hot_linking_img' , '');}
            if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'memory_limit' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'memory_limit' , 'default');}
            if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'max_execution_time' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'max_execution_time' , 'default');}
            if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'protect_plugin' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'protect_plugin' , 'no');}
            if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'password' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'password' , '');}
            if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'admins' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'admins' , '');}
            if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'installed' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'installed' , 'no');}
            
           
            //Add Hooks Options
            if( class_exists( 'tebravo_hooks_install' ) )
            {
            	tebravo_hooks_install::install_antivirus();
            	tebravo_hooks_install::install_bforce();
            	tebravo_hooks_install::install_cronjobs();
            	tebravo_hooks_install::install_errorpages();
            	tebravo_hooks_install::install_firewall();
            	tebravo_hooks_install::install_logwatch();
            	tebravo_hooks_install::install_mail();
            	tebravo_hooks_install::install_recaptcha();
            	tebravo_hooks_install::install_traffic();
            	tebravo_hooks_install::install_wconfig();
            	tebravo_hooks_install::install_wpadmin();
            }
            
            do_action('tebravo_activate');
        }
   
}

//uninstall
if( !function_exists( 'tebravo_byebye' ) )
{
	function tebravo_byebye()
        {
        	global $wpdb;
        	
        	//delete options
        	$wpdb->query("DELETE FROM $wpdb->options WHERE
        			option_name LIKE '%".TEBRAVO_DBPREFIX."%';");
        	
        	//delete sitemeta for network
        	if( function_exists('is_multisite') && is_multisite() )
        	{
        		$wpdb->query("DELETE FROM $wpdb->sitemeta WHERE
        				meta_key LIKE '%".TEBRAVO_DBPREFIX."%';");
        	}
        	//delete usermeta
        	$wpdb->query("DELETE FROM $wpdb->usermeta WHERE
        			meta_key LIKE '%".TEBRAVO_DBPREFIX."%';");
        	
        	//Remove Bravo DB Tables
        	if( class_exists( 'tebravo_hooks_install' ) )
        	{
        		tebravo_hooks_install::uninstall_antivirus();
        		tebravo_hooks_install::uninstall_bforce();
        		tebravo_hooks_install::uninstall_firewall();
        		tebravo_hooks_install::uninstall_traffic();
        	}
        	
        	//clear scheduled cronjobs
        	if(function_exists( '_tebravo_tasks_list' )){
        		foreach (_tebravo_tasks_list() as $key=>$value)
        		{
        			$action_hook = TEBRAVO_DBPREFIX.$key;
        			if( wp_next_scheduled( $action_hook) )
        				@wp_clear_scheduled_hook($action_hook);
        		}
        	}
        	
        }
}

if( !function_exists( 'tebravo_create_hash' ) )
{
	function tebravo_create_hash( $length ) {
        	$alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
        	$hash = array(); //remember to declare $pass as an array
        	$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
        	for ($i = 0; $i < $length; $i++) {
        		$n = rand(0, $alphaLength);
        		$hash[] = $alphabet[$n];
        	}
        	return implode($hash); //turn the array into a string
        }
}


if( !class_exists( 'tebravo_install_wizard' ) )
{
	class tebravo_install_wizard
	{
		
		public function __construct()
		{
			
			
		}
		public function tabs( $tabs=array())
		{
			$output[] = '<div class="checkout-wrap">';
			$output[] = '<ul class="checkout-bar">';
			foreach ($tabs as $tab)
			{
				$active = '';
				if( $tab['active'] == 'active')
				{
					$active = 'active';
				}
				$visited = '';
				if( $tab['visited'] == 'visited')
				{
					$visited = 'visited';
				}
				$first = '';
				if( $tab['first'] == 'first')
				{
					$first= 'first';
				}
				$prev = '';
				if( $tab['previous'] == 'previous')
				{
					$prev= 'previous';
				}
				
				$title = $tab['title'];
				$output[] = '<li class="'.$prev.' '.$active.' '.$visited.' '.$first.'"><a href="#">'.$title.'</a></li>';
			}
			
			$output[] = ' </ul></div>';
			
			return implode("\n", $output);
			
			
		}
		
		public function wizard( $step=false )
		{
			$helper = new tebravo_html();
			
			if( !$step ) {$step = 1;}
			
			$active_1 = ''; $active_2 = ''; $active_3 = ''; $active_4 = ''; $active_5 = ''; 
			$next_2 = ''; $next_3 = ''; $next_4 = ''; $next_5 = ''; 
			$visited_1 = ''; $visited_2 = ''; $visited_3 = ''; $visited_4 = ''; 
			$prev_1 = ''; $prev_2 = ''; $prev_3 = ''; $prev_4 = ''; 
			
			switch ($step){
				case 1: $active_1 = "active"; break;
				case 2: $active_2 = "active"; break;
				case 3: $active_3 = "active";break;
				case 4: $active_4 = "active";break;
				case 5: $active_5 = "active";break;
			}
			
			if( $step+1 == $step ):
			switch ($step+1){
				case 2: $next_2 = "next"; break;
				case 3: $next_3 = "next";break;
				case 4: $next_4 = "next";break;
				case 5: $next_5 = "next";break;
			}
			endif;
			
			
			if( $step == 2 ){$prev_1 = 'previous'; $visited_1='visited';}
			else if( $step == 3 ){$prev_1 = 'previous'; $visited_1='visited';$prev_2 = 'previous'; $visited_2='visited';}
			else if( $step == 4 ){$prev_1 = 'previous'; $visited_1='visited';$prev_2 = 'previous'; $visited_2='visited';$prev_3 = 'previous'; $visited_3='visited';}
			else if( $step == 5 ){$prev_1 = 'previous'; $visited_1='visited';$prev_2 = 'previous'; $visited_2='visited';$prev_3 = 'previous'; $visited_3='visited';$prev_4 = 'previous'; $visited_4='visited';}
			
			$tabs['1'] = array( "active" => $active_1,
					"first" => "first", 
					"visited" => $visited_1,
					"previous" => $prev_1,
					"next" => "",
					"title" => __("Welcome", TEBRAVO_TRANS));
			
			$tabs['2'] = array( "active" => $active_2,
					"first" => "",
					"visited" => $visited_2,
					"previous" => $prev_2,
					"next" => $next_2,
					"title" => __("Requirements", TEBRAVO_TRANS));
			
			$tabs['3'] = array( "active" => $active_3,
					"first" => "",
					"visited" => $visited_3,
					"previous" => $prev_3,
					"next" => $next_3,
					"title" => __("Constants", TEBRAVO_TRANS));
			
			$tabs['4'] = array( "active" => $active_4,
					"first" => "",
					"visited" => $visited_4,
					"previous" => $prev_4,
					"next" => $next_4,
					"title" => __("Permissions", TEBRAVO_TRANS));
			
			$tabs['5'] = array( "active" => $active_5,
					"first" => "",
					"visited" => "",
					"previous" => "",
					"next" => $next_5,
					"last" => "last",
					"title" => __("Finish", TEBRAVO_TRANS));
			
			return  $this->tabs( $tabs );
			
		}
		
		public function welcome()
		{
			$helper = new tebravo_html();
			$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
			$output[] = "<h1>".__("Bravo Configuration", TEBRAVO_TRANS)."</h1><hr>";
			$output[] = __("This wizard will help you to get the best configuration and settings.", TEBRAVO_TRANS)."<br />";
			$output[] = __("Step by step we are testing your hosting to get it compatible with Bravo.", TEBRAVO_TRANS)."<br /><br /><br />";
			$output[] = $helper->button(__("Start", TEBRAVO_TRANS), 'button', 'bravo_start_conf');
			$output[] = "</div>";
			
			$js = '<script>'.PHP_EOL;
			$js .= 'jQuery("#bravo_start_conf").click(function(){'.PHP_EOL;
			$js .= 'window.location.href="'.$helper->init->admin_url.'&action=setup-'.TEBRAVO_SLUG.'&bravo-step=requirements";'.PHP_EOL;
			$js .= '});'.PHP_EOL;
			$js .= '</script>';
			
			$output[] = $js;
			
			//set new value
			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'installed', 'welcome');
			
			return implode("\n", $output);
		}
		
		public function requirements()
		{
			$helper = new tebravo_html();
			$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
			$output[] = "<h1>".__("Bravo Requirements", TEBRAVO_TRANS)."</h1><hr>";
			$output[] = "<strong>".__("Please be sure that your hosting support these requirements", TEBRAVO_TRANS)."</strong>:<br /><br />";
			$output[] = "<table border=0 width=100% cellspacing=0>";
			//php version
			$php_status = "<img src='".plugins_url('/assets/img/blocked.png', TEBRAVO_PATH)."'>";
			if( version_compare(phpversion(), '5.4', '>=') ){$php_status = "<img src='".plugins_url('/assets/img/ok.png', TEBRAVO_PATH)."'>";}
			$output[] = "<tr class='tebravo_underTD'><td width=30%>".__("PHP Version", TEBRAVO_TRANS)."</td>";
			$output[] = "<td width=20%>".phpversion()."</td><td>".$php_status."</td></tr>";
			//chmod
			$chmod_status = "<img src='".plugins_url('/assets/img/blocked.png', TEBRAVO_PATH)."'>";
			$chmod_text = __("Disabled", TEBRAVO_TRANS); 
			if( !tebravo_phpsettings::is_disabled('chmod') ){$chmod_text = __("Enabled", TEBRAVO_TRANS); $chmod_status = "<img src='".plugins_url('/assets/img/ok.png', TEBRAVO_PATH)."'>";}
			$output[] = "<tr class='tebravo_underTD'><td width=30%>".__("PHP Function: chmod()", TEBRAVO_TRANS)."</td>";
			$output[] = "<td width=20%>".$chmod_text."</td><td>".$chmod_status."</td></tr>";
			
			//fopen
			$fopen_status = "<img src='".plugins_url('/assets/img/blocked.png', TEBRAVO_PATH)."'>";
			$fopen_text = __("Disabled", TEBRAVO_TRANS);
			if( !tebravo_phpsettings::is_disabled('fopen') ){$fopen_text = __("Enabled", TEBRAVO_TRANS); $fopen_status = "<img src='".plugins_url('/assets/img/ok.png', TEBRAVO_PATH)."'>";}
			$output[] = "<tr class='tebravo_underTD'><td width=30%>".__("PHP Function: fopen()", TEBRAVO_TRANS)."</td>";
			$output[] = "<td width=20%>".$fopen_text."</td><td>".$fopen_status."</td></tr>";
			
			//fread
			$fread_status = "<img src='".plugins_url('/assets/img/blocked.png', TEBRAVO_PATH)."'>";
			$fread_text = __("Disabled", TEBRAVO_TRANS);
			if( !tebravo_phpsettings::is_disabled('fread') ){$fread_text = __("Enabled", TEBRAVO_TRANS); $fread_status = "<img src='".plugins_url('/assets/img/ok.png', TEBRAVO_PATH)."'>";}
			$output[] = "<tr class='tebravo_underTD'><td width=30%>".__("PHP Function: fread()", TEBRAVO_TRANS)."</td>";
			$output[] = "<td width=20%>".$fread_text."</td><td>".$fread_status."</td></tr>";
			
			//file_get_contents
			$file_get_contents_status = "<img src='".plugins_url('/assets/img/blocked.png', TEBRAVO_PATH)."'>";
			$file_get_contents_text = __("Disabled", TEBRAVO_TRANS);
			if( !tebravo_phpsettings::is_disabled('file_get_contents') ){$file_get_contents_text = __("Enabled", TEBRAVO_TRANS); $file_get_contents_status = "<img src='".plugins_url('/assets/img/ok.png', TEBRAVO_PATH)."'>";}
			$output[] = "<tr class='tebravo_underTD'><td width=30%>".__("PHP Function: file_get_contents()", TEBRAVO_TRANS)."</td>";
			$output[] = "<td width=20%>".$file_get_contents_text."</td><td>".$file_get_contents_status."</td></tr>";
			
			//fputs
			$fputs_status = "<img src='".plugins_url('/assets/img/blocked.png', TEBRAVO_PATH)."'>";
			$fputs_text = __("Disabled", TEBRAVO_TRANS);
			if( !tebravo_phpsettings::is_disabled('fputs') ){$fputs_text = __("Enabled", TEBRAVO_TRANS); $fputs_status = "<img src='".plugins_url('/assets/img/ok.png', TEBRAVO_PATH)."'>";}
			$output[] = "<tr class='tebravo_underTD'><td width=30%>".__("PHP Function: fputs()", TEBRAVO_TRANS)."</td>";
			$output[] = "<td width=20%>".$fputs_text."</td><td>".$fputs_status."</td></tr>";
			
			//unlink
			$unlink_status = "<img src='".plugins_url('/assets/img/blocked.png', TEBRAVO_PATH)."'>";
			$unlink_text = __("Disabled", TEBRAVO_TRANS);
			if( !tebravo_phpsettings::is_disabled('unlink') ){$unlink_text = __("Enabled", TEBRAVO_TRANS); $unlink_status = "<img src='".plugins_url('/assets/img/ok.png', TEBRAVO_PATH)."'>";}
			$output[] = "<tr class='tebravo_underTD'><td width=30%>".__("PHP Function: unlink()", TEBRAVO_TRANS)."</td>";
			$output[] = "<td width=20%>".$unlink_text."</td><td>".$unlink_status."</td></tr>";
			
			//filesize
			$filesize_status = "<img src='".plugins_url('/assets/img/blocked.png', TEBRAVO_PATH)."'>";
			$filesize_text = __("Disabled", TEBRAVO_TRANS);
			if( !tebravo_phpsettings::is_disabled('filesize') ){$filesize_text = __("Enabled", TEBRAVO_TRANS); $filesize_status = "<img src='".plugins_url('/assets/img/ok.png', TEBRAVO_PATH)."'>";}
			$output[] = "<tr class='tebravo_underTD'><td width=30%>".__("PHP Function: filesize()", TEBRAVO_TRANS)."</td>";
			$output[] = "<td width=20%>".$filesize_text."</td><td>".$filesize_status."</td></tr>";
			
			//allow_url_fopen
			$allow_url_fopen_status = "<img src='".plugins_url('/assets/img/blocked.png', TEBRAVO_PATH)."'>";
			$allow_url_fopen_text = __("Off", TEBRAVO_TRANS);
			if( ini_get('allow_url_fopen')==1){$allow_url_fopen_text = __("On", TEBRAVO_TRANS); $allow_url_fopen_status = "<img src='".plugins_url('/assets/img/ok.png', TEBRAVO_PATH)."'>";}
			$output[] = "<tr class='tebravo_underTD'><td width=30%>".__("PHP.ini: allow_url_fopen", TEBRAVO_TRANS)."</td>";
			$output[] = "<td width=20%>".$allow_url_fopen_text."</td><td>".$allow_url_fopen_status."</td></tr>";
			
			if( tebravo_phpsettings::web_server() == 'nginx' )
			{
				$nginx_conf_file = ABSPATH."nginx.conf";
				$nginx_conf_status = "<img src='".plugins_url('/assets/img/blocked.png', TEBRAVO_PATH)."'>";
				$nginx_conf_text = __("Not Exists", TEBRAVO_TRANS);
				if( file_exists($nginx_conf_file)){$nginx_conf_text = __("Exists", TEBRAVO_TRANS); $nginx_conf_status = "<img src='".plugins_url('/assets/img/ok.png', TEBRAVO_PATH)."'>";}
				$output[] = "<tr class='tebravo_underTD'><td width=30%>".__("nginx.conf", TEBRAVO_TRANS)."<br />";
				$output[] = "<i>".$nginx_conf_file."</td>";
				$output[] = "<td width=20%>".$nginx_conf_text."</td><td>".$nginx_conf_status."</td></tr>";
				
			}
			
			if( tebravo_phpsettings::web_server() == 'nginx' )
			{
				$output[] = "<tr class='tebravo_underTD'><td colspan=3><strong>".__("NGINX Configuration", TEBRAVO_TRANS)."</td></tr>";
				$output[] = "<tr class='tebravo_underTD'><td colspan=3>".__("Add this line in the main nginx.conf or default.conf in your server using SSH", TEBRAVO_TRANS)."<br />";
				$output[] = "<pre>include ".ABSPATH."nginx.conf;</pre></td></tr>";
			}
			$output[] = "</table>";
			
			$output[] = "<br />";
			$output[] = $helper->button(__("Previous", TEBRAVO_TRANS), 'button', 'bravo_start_prev');
			$output[] = $helper->button(__("Next", TEBRAVO_TRANS), 'button', 'bravo_start_next');
			
			$output[] = "<br /><hr><i>* ".__("If you got an error, Please fix it, then reload the page.", TEBRAVO_TRANS)."</i>";
			$output[] = "</div>";
			
			$js = '<script>'.PHP_EOL;
			$js .= 'jQuery("#bravo_start_next").click(function(){'.PHP_EOL;
			$js .= 'window.location.href="'.$helper->init->admin_url.'&action=setup-'.TEBRAVO_SLUG.'&bravo-step=constants";'.PHP_EOL;
			$js .= '});'.PHP_EOL;
			$js .= 'jQuery("#bravo_start_prev").click(function(){'.PHP_EOL;
			$js .= 'window.location.href="'.$helper->init->admin_url.'&action=setup-'.TEBRAVO_SLUG.'&bravo-step=";'.PHP_EOL;
			$js .= '});'.PHP_EOL;
			$js .= '</script>';
			
			$output[] = $js;
			
			//set new value
			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'installed', 'requirements');
			
			return implode("\n", $output);
		}
		
		
		public function constants()
		{
			$helper = new tebravo_html();
			$true = "<img src='".plugins_url('/assets/img/ok.png', TEBRAVO_PATH)."'>";
			$false = "<img src='".plugins_url('/assets/img/shield_error.png', TEBRAVO_PATH)."'>";
			
			$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
			$output[] = "<h1>".__("Bravo Constants", TEBRAVO_TRANS)."</h1><hr>";
			$output[] = "<strong>".__("We are setting Bravo on the best practice mode for you.", TEBRAVO_TRANS)."</strong><br /><br />";
			$output[] = "<table border=0 width=100% cellspacing=0>";
			//Hide Wordpress Version
			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'hidewpversion', 'checked');
			$output[] = "<tr class='tebravo_underTD'><td width=30%>".__("Hide Wordpress Version", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".$true."</td></tr>";
			//Security HASH
			$security_hash = tebravo_utility::get_option(TEBRAVO_DBPREFIX.'security_hash');
			if( empty( $security_hash ))
			{
				tebravo_utility::update_option(TEBRAVO_DBPREFIX.'security_hash', tebravo_create_hash(8));
			}
			$output[] = "<tr class='tebravo_underTD'><td width=30%>".__("Security HASH", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".$true."</td></tr>";
			//Brute Force Protection
			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'bruteforce_protection', 'checked');
			$output[] = "<tr class='tebravo_underTD'><td width=30%>".__("Brute Force Protection", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".$true."</td></tr>";
			//Enforce Strong Passwords
			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'enforce_strongpasswords', 'checked');
			$output[] = "<tr class='tebravo_underTD'><td width=30%>".__("Enforce Strong Passwords", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".$true."</td></tr>";
			
			//Brute Force Whitelist
			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'bforce_whitelist_ips', tebravo_agent::user_ip());
			$output[] = "<tr class='tebravo_underTD'><td width=30%>".__("Brute Force Whitelist", TEBRAVO_TRANS)."<br />";
			$output[] = "</td>";
			$output[] = "<td>".$true." <i>".tebravo_agent::user_ip()."</i></td></tr>";
			//Firewall Whitelist
			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'firewall_whiteips', tebravo_agent::user_ip());
			$output[] = "<tr class='tebravo_underTD'><td width=30%>".__("Firewall Whitelist", TEBRAVO_TRANS)."<br />";
			$output[] = "</td>";
			$output[] = "<td>".$true." <i>".tebravo_agent::user_ip()."</i></td></tr>";
			//Firewall Profile
			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'firewall_profile', 'high');
			$output[] = "<tr class='tebravo_underTD'><td width=30%>".__("Firewall Profile", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>$true ".__("High", TEBRAVO_TRANS)."</td></tr>";
			//PHP Security
			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'firewall_php_security', 'checked');
			$output[] = "<tr class='tebravo_underTD'><td width=30%>".__("PHP Security", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>$true ".__("High", TEBRAVO_TRANS)."</td></tr>";
			//Error Pages
			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'page_404', 'checked');
			$output[] = "<tr class='tebravo_underTD'><td width=30%>".__("Error Pages", TEBRAVO_TRANS)."</td>";
			$output[] = "<td>".$true."</td></tr>";
			//Filechange
			
			$output[] = "<tr class='tebravo_underTD'><td width=30%>".__("Filechange First Scan", TEBRAVO_TRANS)."</td>";
			if( function_exists( '_tebravo_filechange_callback' ) )
			{
				_tebravo_filechange_callback();
				$output[] = "<td>".$true."</td></tr>";
			} else {$output[] = "<td>".$false."</td></tr>";}
			
			
			$output[] = "</table>";
			
			$output[] = "<br />";
			$output[] = $helper->button(__("Previous", TEBRAVO_TRANS), 'button', 'bravo_start_prev');
			$output[] = $helper->button(__("Next", TEBRAVO_TRANS), 'button', 'bravo_start_next');
			
			$output[] = "<br /><hr><i>* ".__("If you got an error, Please fix it, then reload the page.", TEBRAVO_TRANS)."</i>";
			$output[] = "</div>";
			
			$js = '<script>'.PHP_EOL;
			$js .= 'jQuery("#bravo_start_next").click(function(){'.PHP_EOL;
			$js .= 'window.location.href="'.$helper->init->admin_url.'&action=setup-'.TEBRAVO_SLUG.'&bravo-step=permissions";'.PHP_EOL;
			$js .= '});'.PHP_EOL;
			$js .= 'jQuery("#bravo_start_prev").click(function(){'.PHP_EOL;
			$js .= 'window.location.href="'.$helper->init->admin_url.'&action=setup-'.TEBRAVO_SLUG.'&bravo-step=requirements";'.PHP_EOL;
			$js .= '});'.PHP_EOL;
			$js .= '</script>';
			
			$output[] = $js;
			
			//set new value
			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'installed', 'constants');
			
			return implode("\n", $output);
		}
		
		public function permissions()
		{
			$helper = new tebravo_html();
			$true = "<img src='".plugins_url('/assets/img/ok.png', TEBRAVO_PATH)."'>";
			$false = "<img src='".plugins_url('/assets/img/shield_error.png', TEBRAVO_PATH)."'>";
			
			$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
			$output[] = "<h1>".__("Permissions We Need", TEBRAVO_TRANS)."</h1><hr>";
			$output[] = "<strong>".__("Some files and directories should be writable and it will be protected.", TEBRAVO_TRANS)."</strong><br /><br />";
			$output[] = "<table border=0 width=100% cellspacing=0>";
			
			//daily
			$daily_path = TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/daily';
			$output[] = "<tr class='tebravo_underTD'><td width=60%>".$daily_path."</td>";
			$output[] = "<td>".(is_writeable($daily_path)?$true:$false)."</td></tr>";
			
			//log dir
			$log_dir_path = TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/log';
			$output[] = "<tr class='tebravo_underTD'><td width=60%>".$log_dir_path."</td>";
			$output[] = "<td>".(is_writeable($log_dir_path)?$true:$false)."</td></tr>";
			
			//log file
			$phplog_file_path = TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/log/php_log.txt';
			$output[] = "<tr class='tebravo_underTD'><td width=60%>".$phplog_file_path."</td>";
			$output[] = "<td>".(is_writeable($phplog_file_path)?$true:$false)."</td></tr>";
			
			//phplog file
			$log_file_path = TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/log/log.txt';
			$output[] = "<tr class='tebravo_underTD'><td width=60%>".$log_file_path."</td>";
			$output[] = "<td>".(is_writeable($log_file_path)?$true:$false)."</td></tr>";
			
			//manually
			$manually_path = TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/manually';
			$output[] = "<tr class='tebravo_underTD'><td width=60%>".$manually_path."</td>";
			$output[] = "<td>".(is_writeable($manually_path)?$true:$false)."</td></tr>";
			
			//manually files
			$manuallyfiles_path = TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/manually/files';
			$output[] = "<tr class='tebravo_underTD'><td width=60%>".$manuallyfiles_path."</td>";
			$output[] = "<td>".(is_writeable($manuallyfiles_path)?$true:$false)."</td></tr>";
			
			//scheduled
			$scheduled_path = TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/scheduled';
			$output[] = "<tr class='tebravo_underTD'><td width=30%>".$scheduled_path."</td>";
			$output[] = "<td>".(is_writeable($scheduled_path)?$true:$false)."</td></tr>";
			
			if( tebravo_phpsettings::web_server() == 'nginx' )
			{
				//sessions
				$sessions_path = TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/sessions';
				$output[] = "<tr class='tebravo_underTD'><td width=60%>".$sessions_path."</td>";
				$output[] = "<td>".(is_writeable($sessions_path)?$true:$false)."</td></tr>";
				
			}
			
			//filechange
			$filechange_path = TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/filechange.txt';
			$output[] = "<tr class='tebravo_underTD'><td width=60%>".$filechange_path."</td>";
			$output[] = "<td>".(is_writeable($filechange_path)?$true:$false)."</td></tr>";
			
			$output[] = "</table>";
			
			$output[] = "<br />";
			$output[] = $helper->button(__("Previous", TEBRAVO_TRANS), 'button', 'bravo_start_prev');
			$output[] = $helper->button(__("Next", TEBRAVO_TRANS), 'button', 'bravo_start_next');
			
			$output[] = "<br /><hr><i>* ".__("If you got an error, Please fix it, then reload the page.", TEBRAVO_TRANS)."</i>";
			$output[] = "</div>";
			
			$js = '<script>'.PHP_EOL;
			$js .= 'jQuery("#bravo_start_next").click(function(){'.PHP_EOL;
			$js .= 'window.location.href="'.$helper->init->admin_url.'&action=setup-'.TEBRAVO_SLUG.'&bravo-step=finish";'.PHP_EOL;
			$js .= '});'.PHP_EOL;
			$js .= 'jQuery("#bravo_start_prev").click(function(){'.PHP_EOL;
			$js .= 'window.location.href="'.$helper->init->admin_url.'&action=setup-'.TEBRAVO_SLUG.'&bravo-step=constants";'.PHP_EOL;
			$js .= '});'.PHP_EOL;
			$js .= '</script>';
			
			$output[] = $js;
			
			//set new value
			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'installed', 'permissions');
			
			return implode("\n", $output);
		}
		
		
		public function finish()
		{
			$helper = new tebravo_html();
			$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
			
			$output[] = "<center><img src='".plugins_url('/assets/img/11-128.png', TEBRAVO_PATH)."'>";
			$output[] = "<h1>".__("Congratulations", TEBRAVO_TRANS)."</h1><br /><br />";
			$output[] = __("If you have no errors, or you did repair all errors were happening while the wizard was running ...", TEBRAVO_TRANS)."<br />";
			$output[] = __("We can say: your plugin is ready!", TEBRAVO_TRANS)."<br /><br />";
			$output[] = $helper->button(__("Go to Dashboard", TEBRAVO_TRANS), 'button', 'bravo_dashboard');
			$output[] = "</center>";
			
			$output[] = "</div>";
			
			$js = '<script>'.PHP_EOL;
			$js .= 'jQuery("#bravo_dashboard").click(function(){'.PHP_EOL;
			$js .= 'window.location.href="'.$helper->init->admin_url.'";'.PHP_EOL;
			$js .= '});'.PHP_EOL;
			$js .= '</script>';
			
			$output[] = $js;
			
			//set new value
			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'installed', 'installed');
			
			return implode("\n", $output);
		}
	}
}
?>