<?php
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !class_exists( 'tebravo_mail' ) )
{
    class tebravo_mail{
        
    	public $log_path,
    	$filename,
    	$html
    	;
    	
    	//constuctor
        public function __construct()
        {
        	$filename_from_options = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'maillog_filename' ) ) );
        	$mailwatching = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'mailwatching' ) ) );
        	
        	if( empty( $filename_from_options) )
        	{
        		$this->create_filename();
        	}
        	
        	$this->filename = $filename_from_options;
        	
        	$this->log_path = TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/log/'.$this->filename;
        	
        	//update log via add_action to phpmailer_init
        	if( $mailwatching == 'checked' ){
        		add_action( 'phpmailer_init' , array( $this, 'update_log' ) );
        	}
        	
        }
        
        //create log file name and path
        public static function create_filename( $filename=false, $logpath=false )
        {
        	$hash = tebravo_init::create_hash( 16 );
        	if( !$filename ){$filename = $hash.'.txt';}
        	
        	if( !$logpath ){$logpath = TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/log/'.$filename;}
        	//update database
        	tebravo_utility::update_option( TEBRAVO_DBPREFIX.'maillog_filename' , sanitize_text_field( $filename) );
        	
        	//create file
        	if( !file_exists( $logpath ) ){tebravo_files::write( $logpath, '' );}
        }
        
        //save email log
        public function update_log( $phpmailer )
        {        	
        	$this->html = new tebravo_html();
        	
        	if( $phpmailer->From != '' ){
	        	//check if mail wacting tool is enabled
	        	$mailwatching = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'mailwatching' ) ) );
	        	if( $mailwatching == 'checked' ){
	        	
		        	$line = "[from]".$phpmailer->From;
		        	$line .= "[subject]".$phpmailer->Subject;
		        	$line .= "[time]".time();
		        	
		        	if( !file_exists( $this->log_path ) )
		        	{
		        		tebravo_files::write( $this->log_path , '', true );
		        	}
		        	
		        	if( file_exists( $this->log_path ) ){
		        		$line_encode = tebravo_encodeString( $line , $this->html->init->security_hash );
		        		$line_encode = $line_encode.'#tebravo#';
		        		
		        		$oldData = tebravo_files::read( $this->log_path );
		        		$newData = $line_encode.$oldData;
		        		tebravo_files::write( $this->log_path , $newData);
		        	}
	        	}
        	}
        }
        
        //dashboard
        public function dashboard()
        {
        	$this->html = new tebravo_html();
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
        	
        	if( empty( $_GET['p'] ) )
        	{
        		$this->main_dashboard();
        	} else if ( $_GET['p'] == 'settings' )
        	{
        		$this->dashboard_settings();
        	}
        	
        }
        
        //main dashboard
        public function main_dashboard()
        {
        	//flush log
        	//empty log file
        	if( !empty( $_GET['action'] ) 
        			&& $_GET['action'] == 'flush' 
        			&& !empty( $_GET['_nonce'])
        			&& false !== wp_verify_nonce( $_GET['_nonce'], $this->html->init->security_hash.'flush-mail-log'))
        	{
        		if( file_exists( $this->log_path) )
        		{
        			tebravo_files::write( $this->log_path , '');
        		}
        		
        		tebravo_redirect_js($this->html->init->admin_url.'-mail&msg=05');
        		exit;
        	}
        	
        	if( !empty( $_GET['action'] )
        			&& $_GET['action'] == 'createfile'
        			&& !empty( $_GET['_nonce'])
        			&& false !== wp_verify_nonce( $_GET['_nonce'], $this->html->init->security_hash.'create_maillog_file'))
        	{
        		if( !file_exists( $this->log_path) )
        		{
        			tebravo_files::write( $this->log_path , '');
        		}
        		
        		tebravo_redirect_js($this->html->init->admin_url.'-mail&msg=05');
        		exit;
        	}
        	
        	//main dashboard
        	//HTML
        	$desc = "It is a perfect tool to control your outbound WP emails.";
        	$extra = "<a href='".$this->html->init->admin_url."-mail&p=settings' class='tebravo_curved'>".__("Settings")."</a>";
        	$this->html->header(__("E-Mail Dashboard", TEBRAVO_TRANS), $desc, 'mail.png', $extra);
        	
        	$this->html->print_loading();
        	
        	$output[] = "<div class='tebravo_block_blank' style=' min-width:100%; '>";
        	$output[] = $this->html->button_small(__("Empty Log", TEBRAVO_TRANS), 'button', 'flush');
        	$output[] = "<div style='max-height:500px; overflow-y:scroll;'>";
        	$js = '';
        	if( file_exists( $this->log_path ) )
        	{
        		$data = tebravo_files::read( $this->log_path );
        		$data = trim( $data );
        		$exp = explode('#tebravo#', $data);
        		$count = floor(count($exp) -1);
        		if( $count < 1 )
        		{
        			$count = 0;
        		}
        		$output[] = "<table border=0 width=100% cellspacing=2>";
        		$output[] = "<tr class=tebravo_headTD><td width=30%><strong>".__("From", TEBRAVO_TRANS)."</strong></td>";
        		$output[] = "<td width=55%><strong>".__("Subject", TEBRAVO_TRANS)."</strong></td>";
        		$output[] = "<td width=25%><strong>".__("Date/Time", TEBRAVO_TRANS)."</strong></td>";
        		$output[] = "</tr>";
        		$output[] = "<tr class='tebravo_headTD'><td colspan=3>".__("Count", TEBRAVO_TRANS).": ".$count."</td></tr>";
        		
        		if( empty( $data ) )
        		{
        			$output[] = "<tr><td colspan=3>".__("Email log is empty!", TEBRAVO_TRANS)."</td></tr>";
        		} else {
        			
        			if( !empty( $exp ) ):
        			$content = '';
        			$js = '';
        			$i=1;
        			foreach ( $exp as $line )
        			{
        				if( !empty( $line ) ){
        					//decode line
	        				$msg = tebravo_decodeString( $line , $this->html->init->security_hash );
	        				$msg = trim( $msg );
	        				
	        				if( !empty( $msg ) ):
		        				//convert decoded line to message parameters
		        				$exp_from = explode( '[from]' , $msg);
		        				$exp_subject = explode( '[subject]', $exp_from[1] );
		        				$exp_date = explode( '[time]' , $exp_subject[1] );
		        				
		        				//msg parameters
		        				$from = $exp_subject[0];
		        				$subject= $exp_date[0];
		        				
		        				//explode time from msg
		        				$time = '';
		        				if( isset($exp_date[1]) ){
		        					$time = $exp_date[1];
		        				}
		        				
		        				//set date in date format
		        				$date = __("Unkown", TEBRAVO_TRANS);
		        				if( isset( $time ) )
		        				{
		        					$date = @date( 'd-m-Y', $time)." <font color=brown>".@date( 'h:i A', $time)."</font>";
		        				}
		        				
		        				$imgID = ++$i.$time.md5($subject);
		        				$img = "<img id='".$imgID."' style='cursor:pointer' src='".plugins_url('/assets/img/delete.png', TEBRAVO_PATH)."'>";
		        				$delete = $img;
		        				$content .= "<tr class=tebravo_underTD><td width=30%>{$from}</strong></td>";
		        				$content .= "<td width=55%>{$subject}</td>";
		        				$content .= "<td width=25%>{$date}</td>";
		        				$content .= "</tr>";
	        				endif;
        				}
        				
        			}
        			endif;
        			
        			$output[] = $content;
		        	//pagination
		        	$pages = '';
		        	$total = floor( count( $exp ) / 10 );
		        	// only bother with the rest if we have more than 1 page!
		        	if ( $total > 1 )  {
		        		// get the current page
		        		$current_page = 1;
		        		if ( isset($_GET['paged']) )
		        		    $current_page = (int)esc_html( esc_js( $_GET['paged'] ) );
		        			// structure of "format" depends on whether we're using pretty permalinks
		        			if( tebravo_utility::get_option('permalink_structure') ) {
		        				$format = '&paged=%#%';
		        			} else {
		        				$format = 'page/%#%/';
		        			}
		        			$pages .= paginate_links(array(
		        					'base'     => get_pagenum_link(1) . '%_%',
		        					'format'   => $format,
		        					'current'  => $current_page,
		        					'total'    => $total,
		        					'mid_size' => 4,
		        					'type'     => ''
		        			));
		        	}
		        	
		        	//$output[] = "<tr><td colspan=3>{$pages}</td></tr>";
        		}
        		
        		$output[] = "</table>";
        	
        	} else {
        		
        		$output[] = "<strong>Error:</strong> File not found!<br /><i>".$this->log_path."</i><br />";
        		$output[] = $this->html->button_small(__("Create New File", TEBRAVO_TRANS), "button", "createFile");
        		$js = "jQuery('#flush').hide();";
        		$js .= "jQuery('#createFile').click(function(){";
        		$js .= "window.location.href = '".$this->html->init->admin_url."-mail&action=createfile&_nonce=".$this->html->init->create_nonce('create_maillog_file')."';";
        		$js .= "});";
        	}
        	
        	$output[] = "</div>";
        	$output[] = "</div>";
        	$output[] = "<div id='tebravo_results'></div>";
        	
        	echo implode("\n", $output);
        	
        	$flush_url = $this->html->init->admin_url."-mail&action=flush&_nonce=".$this->html->init->create_nonce('flush-mail-log');
        	echo "<script>";
        	echo "jQuery('#flush').click(function(){";
        	echo "window.location.href='".$flush_url."';";
        	echo "});";
        	echo $js;
        	echo "</script>";
        	$this->html->footer();
        	
        }
        
        //settings dashboard and update
        public function dashboard_settings()
        {
        	$this->html = new tebravo_html();
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
        	
        	
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
        			"is_active"=> 'active');
        	
        	$tabs["recaptcha"] = array("title"=>"reCAPTCHA",
        			"href"=>$this->html->init->admin_url."-recaptcha",
        			"is_active"=> '');
        	
        	$tabs["error_pages"] = array("title"=>"Error Pages",
        			"href"=>$this->html->init->admin_url."-error_pages",
        			"is_active"=> '');
        	
        	//Tabs HTML
        	$desc = "E-Mail settings, choose your good options to control your WP emails.";
        	$extra = "<a href='".$this->html->init->admin_url."-mail' class='tebravo_curved'>".__("Watch")."</a>";
        	$this->html->header(__("E-Mail Settings Dashboard", TEBRAVO_TRANS), $desc, 'mail.png', $extra);
        	
        	$this->html->tabs($tabs);
        	$this->html->start_tab_content();
        	
        	$output[] = "<form action='".$this->html->init->admin_url."-mail&p=settings' method=post>";
        	$output[] = "<input type='hidden' name='_nonce' value='".$this->html->init->create_nonce('mailwatching-settings')."'>";
        	$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
        	$output[] = "<table border='0' width=100% cellspacing=0>";
        	
        	//enable / disable mailwatching tool
        	$mailwatching_tool = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'mailwatching') ) );
        	$mailwatch_yes = '';
        	$mailwatch_no = '';
        	if( $mailwatching_tool == 'checked' ){$mailwatch_yes = 'checked';} else {$mailwatch_no = 'checked';}
        	$mail_watching_help = __('This tool designed for watching outbound email messages in Wordpress.', TEBRAVO_TRANS);
        	$mail_watching_help .= "<br />";
        	$mail_watching_help .= __('It can help if someone using backdoor in your blog to send spam emails.', TEBRAVO_TRANS);
        	$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Mail Watching Tool", TEBRAVO_TRANS)."</strong></td></tr>";
        	$output[] = "<tr class='tebravo_underTD'><td width=80%>".$mail_watching_help."</td><td>";
        	$output[] = "<input type='radio' name='mailwatching' value='checked' id='checked' $mailwatch_yes>";
        	$output[] = "<label for='checked'><span></span>".__("Enable", TEBRAVO_TRANS)."</label>";
        	$output[] = "<input type='radio' name='mailwatching' value='no' id='no' $mailwatch_no>";
        	$output[] = "<label for='no'><span></span>".__("Disable", TEBRAVO_TRANS)."</label>";
        	$output[] = "</td></tr>";
        	
        	//enable / disable firewall notification tool
        	$firewall_human = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'email_firewall_blocked_human') ) );
        	$firewall_bot = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'email_firewall_blocked_bot') ) );
        	$firewall_human_yes = '';
        	$firewall_bot_yes = '';
        	$firewall_human_no = '';
        	$firewall_bot_no = '';
        	if( $firewall_human == 'checked' ){$firewall_human_yes = 'checked';} else {$firewall_human_no = 'checked';}
        	if( $firewall_bot == 'checked' ){$firewall_bot_yes = 'checked';} else {$firewall_bot_no = 'checked';}
        	
        	$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Firewall Notifications", TEBRAVO_TRANS)."</strong></td></tr>";
        	$output[] = "<tr class='tebravo_underTD'><td width=80%>".__("If human blocked", TEBRAVO_TRANS)."</td><td>";
        	$output[] = "<input type='radio' name='email_firewall_blocked_human' value='checked' id='human_checked' $firewall_human_yes>";
        	$output[] = "<label for='human_checked'><span></span>".__("Enable", TEBRAVO_TRANS)."</label>";
        	$output[] = "<input type='radio' name='email_firewall_blocked_human' value='no' id='human_no' $firewall_human_no>";
        	$output[] = "<label for='human_no'><span></span>".__("Disable", TEBRAVO_TRANS)."</label>";
        	$output[] = "</td></tr>";
        	$output[] = "<tr class='tebravo_underTD'><td width=80%>".__("If bot blocked", TEBRAVO_TRANS)."</td><td>";
        	$output[] = "<input type='radio' name='email_firewall_blocked_bot' value='checked' id='bot_checked' $firewall_bot_yes>";
        	$output[] = "<label for='bot_checked'><span></span>".__("Enable", TEBRAVO_TRANS)."</label>";
        	$output[] = "<input type='radio' name='email_firewall_blocked_bot' value='no' id='bot_no' $firewall_bot_no>";
        	$output[] = "<label for='bot_no'><span></span>".__("Disable", TEBRAVO_TRANS)."</label>";
        	$output[] = "</td></tr>";
        	
        	//email settings
        	$tebravo_email = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'email' ) ) );
        	$tebravo_cc = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'cc' ) ) );
        	$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Emails to receive notifications and reports", TEBRAVO_TRANS)."</strong></td></tr>";
        	$output[] = "<tr class='tebravo_underTD'><td colspan=2>".__("Email Address", TEBRAVO_TRANS).":<br />";
        	$output[] = "<input type='email' name='email' value='".$tebravo_email."' pattern='[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,3}$'>";
        	$output[] = "</td></tr>";
        	
        	$output[] = "<tr class='tebravo_underTD'><td colspan=2>".__("CC", TEBRAVO_TRANS).":<br />";
        	$output[] = "<input type='email' name='cc' value='".$tebravo_cc."' pattern='[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,3}$'>";
        	$output[] = "</td></tr>";
        	
        	$output[] = "<tr class='tebravo_underTD'><td colspan=2>";
        	$output[] = $this->html->button(__("Save Settings", TEBRAVO_TRANS), 'submit');
        	$output[] = "</td></tr>";
        	
        	$output[] = "</table></div>";
        	$output[] = "</form>";
        	
        	if( !$_POST ){
        		echo implode("\n", $output);
        	} else {
        		if( empty( $_POST['_nonce'])
        				|| false === wp_verify_nonce( $_POST['_nonce'], $this->html->init->security_hash.'mailwatching-settings' ) )
        		{
        			tebravo_redirect_js($this->html->init->admin_url.'-mail&p=settings&err=02');
        			exit;
        		}
        		
        		if( !empty( $_POST['mailwatching']) 
        				&& !empty( $_POST['email_firewall_blocked_human'])
        				&& !empty( $_POST['email_firewall_blocked_bot']))
        		{
        			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'mailwatching' , trim( esc_html( $_POST['mailwatching'] ) ) );
        			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'email_firewall_blocked_human' , trim( esc_html( $_POST['email_firewall_blocked_human'] ) ) );
        			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'email_firewall_blocked_bot' , trim( esc_html( $_POST['email_firewall_blocked_bot'] ) ) );
        			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'email' , trim( esc_html( $_POST['email'] ) ) );
        			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'cc' , trim( esc_html( $_POST['cc'] ) ) );
        			
        			echo "Saving ...";
        			tebravo_redirect_js($this->html->init->admin_url.'-mail&p=settings&msg=01');
        		} else {
        			tebravo_redirect_js($this->html->init->admin_url.'-mail&p=settings&err=02');
        			exit;
        		}
        	}
        	
        	$this->html->end_tab_content();
        		
        	$this->html->footer();
        }
        
       
    }
}
?>