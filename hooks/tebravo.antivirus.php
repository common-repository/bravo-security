<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !class_exists( 'tebravo_anitvirus' ) )
{
	class tebravo_anitvirus{
        public $html,
        		$memory_limit,
        		$max_execution_time,
        		$admin_notices_activate_plugin,
        		$max_file_size,
        		$is_infected;
        
        //constructor
        public function __construct()
        {
        	//options
        	$this->memory_limit = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'memory_limit' ) ) );
        	$this->max_execution_time= trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'max_execution_time' ) ) );
        	$this->max_file_size = 1000*1000; //bytes
        	if( defined('TEBRAVO_MAX_SCAN_FILESIZE') ){$this->max_file_size = TEBRAVO_MAX_SCAN_FILESIZE;}
        	//actions
        	add_action('admin_init', array( $this, 'actions' ) );
        }
        
        //set custom PHP settings
        public function set_php_settings()
        {
        	$php = new tebravo_phpsettings();
        	$php->set_php_settings();
        }
        //WP actions
        public function actions()
        {
        	add_action('wp_ajax_ajax_load_dir', array( $this, 'ajax_load_dir' ) );
        	add_action('wp_ajax_scan_files', array( $this, 'scan_files' ) );
        	add_action('wp_ajax_scan_files_stop', array( $this, 'scan_files_stop' ) );
        	add_action('wp_ajax_scan_files_resume', array( $this, 'scan_files_resume' ) );
        	add_action('wp_ajax_scan_files_startover', array( $this, 'scan_files_startover' ) );
        	add_action('wp_ajax_scan_files_infected_results', array( $this, 'scan_files_infected_results' ) );
        	add_action('wp_ajax_spamlist_checker', array( $this, 'spamlist_checker' ) );
        	add_action('wp_ajax_dbscan_checker', array( $this, 'dbscan_checker' ) );
        	add_action('wp_ajax_filechange_checker', array( $this, 'filechange_checker' ) );
        	add_action('media_buttons', array( $this, 'media_button' ) );
        	
        	//add_filter('wp_handle_upload_prefilter', array( $this, 'scan_attachment' ));
        	//add_filter('wp_handle_sideload_prefilter', array( $this, 'scan_attachment' ));
        	    	
        }
        
        
        //add button beside the media button to check safe browsing
        public function media_button()
        {
        	global $post;
        	
        	if( is_admin() ){
        		$this->html = new tebravo_html();
        	
        		$icon = "<img src='".plugins_url( '/assets/img/multimedia-44-16.png', TEBRAVO_PATH )."'>";
        		echo '<a target=_blank href="'.$this->html->init->admin_url.'-antivirus&p=scan_3&id='.$post->ID.'" class="button">'.$icon.' Check Safe Browsing</a>';
        	}
        }
        //hook dashboard /HTML
        public function dashboard()
        {
        	$scan_types = array('scan_1','scan_2','scan_3','scan_4','scan_5','scan_6');
        	
        	if(empty($_GET['p']))
        	{
        		$this->dashboard_antivirus();
        	} else if( $_GET['p'] == 'settings')
        	{
        		$this->dashborad_settings();
        	} else if( $_GET['p'] == 'quarantine')
        	{
        		$this->dashborad_quarantine();
        	} else if( $_GET['p'] == 'safefiles')
        	{
        		$this->dashborad_safefiles();
        	} else if( $_GET['p'] == 'scan')
        	{
        		$this->dashboard_scanner();
        	} else if( $_GET['p'] == 'start_scan')
        	{
        		$this->dashboard_start_scan();
        	} else if( $_GET['p'] == 'safebrowsing')
        	{
        		$this->dashboard_safebrowsing_scan();
        	} else if( $_GET['p'] == 'delete')
        	{
        		$this->delete();
        	}else if(  substr(trim( esc_html( $_GET['p'] ) ),0,5) == 'scan_' && in_array($_GET['p'], $scan_types))
        	{
        		$this->dashboard_scanner_start();
        	} else {
        		wp_die( TEBRAVO_NO_ACCESS_MSG ) ;
        	}
        	
        }
        
        //safe files list and control
        public function dashborad_safefiles()
        {
        	$this->html = new tebravo_html();
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
        	
        	$safe_files = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'safe_files') ) );
        	$safe_files_html = __("No files in the list", TEBRAVO_TRANS);
        	$flush_nonce= $this->html->init->create_nonce( 'flush-safefiles' );
        	$unsafe_nonce= $this->html->init->create_nonce( 'unsafe-safefiles' );
        	
        	$desc = __("Safe list. Full control on files in the list.", TEBRAVO_TRANS);
        	$extra = "<a href='".$this->html->init->admin_url."-antivirus&p=safefiles&action=flush&nonce=$flush_nonce' class='tebravo_curved'>".__("Flush")."</a>";
        	$this->html->header(__("Antivirus - Safe List", TEBRAVO_TRANS), $desc, 'antivirus.png', $extra);
        	
        	$this->html->print_loading();
        	
        	$utility = new tebravo_antivirus_utility();
        	
        	$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
        	
        	//actions on safe files
        	if( !empty( $_GET['action'] ) )
        	{
        		//flush safe files
        		if( $_GET['action'] == 'flush' )
        		{
        			if( !empty( $_GET['nonce'] )
        					&& wp_verify_nonce( $_GET['nonce'], $this->html->init->security_hash.'flush-safefiles') )
        			{
        				tebravo_utility::update_option( TEBRAVO_DBPREFIX.'safe_files', '' );
        				echo __("Redirecting ...", TEBRAVO_TRANS);
        				tebravo_redirect_js( $this->html->init->admin_url.'-antivirus&p=safefiles&msg=05');
        				exit;
        			}
        		} 
        		//remove from list
        		else if ( $_GET['action'] == 'unsafe' )
        		{
        			if( !empty( $_GET['nonce'] )
        					&& wp_verify_nonce( $_GET['nonce'], $this->html->init->security_hash.'unsafe-safefiles') )
        			{
        				if( !empty( $safe_files ) )
        				{
        					if( !empty( $_GET['f'] ) )
        					{
        					    $f_hash = tebravo_decodeString( esc_html($_GET['f']) , $this->html->init->security_hash );
        						$exp_safe = explode( ',', $safe_files);
        						if( is_array( $exp_safe ) )
        						{
        							$newFiles = '';
        							
        							foreach ( $exp_safe as $file )
        							{
        								if( $f_hash === $file)
        								{
        									unset( $file );
        								}
        								if( !empty( $file ) ){
        									$newFiles .= $file.',';
        								}
        							}
        							
        							tebravo_utility::update_option( TEBRAVO_DBPREFIX.'safe_files', $newFiles );
        							/*if(($key = array_search($f_hash, $exp_safe)) !== false) {
        								unset($exp_safe[$key]);
        							}*/
        							echo __("Redirecting ...", TEBRAVO_TRANS);
        						}
        					} else {
        						tebravo_redirect_js( $this->html->init->admin_url.'-antivirus&p=safefiles&err=02');
        						exit;
        					}
        				}
        				tebravo_redirect_js( $this->html->init->admin_url.'-antivirus&p=safefiles&msg=05');
        				exit;
        			}
        		}
        	}
        	
        	//safe files list
        	if( ! empty( $safe_files ) )
        	{
        		$exp = explode( ',', $safe_files);
        		if( is_array( $exp) && count( $exp ) > 0)
        		{
        			$safe_files_html = "<table border=0 width=100% cellspacing=0>";
        			$nonce = $this->html->init->create_nonce( 'take-action-infected-files' );
        			$back = true;
        			
        			foreach ( $exp as $file )
        			{
        				if( !empty( $file ) ):
        				$file = str_replace('//', '/', $file);
        				$f_hash = tebravo_encodeString( $file , $this->html->init->security_hash );
        				$actions = "<a onclick=\"return confirm('".__("Sure! Delete File From Server?!", TEBRAVO_TRANS)."')\" href='".$this->html->init->admin_url."-antivirus&p=start_scan&pid=safefiles&new_action=delete&_nonce=".$nonce."&f=".$f_hash."&back=$back'>".__("Delete File", TEBRAVO_TRANS)."</a>";
        				
        				if(!$utility->check_safe_list( $file )){
        					$actions .= " | <a href='".$this->html->init->admin_url."-antivirus&p=start_scan&pid=safefiles&new_action=safe&_nonce=".$nonce."&f=".$f_hash."&back=$back'>".__("Mark as Safe", TEBRAVO_TRANS)."</a>";
        				}
        				
        				if( !file_exists( $file ) )
        				{
        					$actions = "<i><font color=green>".__("Removed!", TEBRAVO_TRANS)."</font></i>";
        				}
        				
        				$actions .= " | <a href='".$this->html->init->admin_url."-antivirus&p=safefiles&action=unsafe&nonce=".$unsafe_nonce."&f=".$f_hash."'>".__("Remove from list", TEBRAVO_TRANS)."</a>";
        				
        				
        				$safe_files_html .= "<tr class='tebravo_underTD'><td width=80%><strong>".basename( $file )."</strong><br />".$file;
        				$safe_files_html .= "</td><td>". $actions ."</td></tr>";
        				endif;
        			}
        			$safe_files_html .= "</table>";
        		}
        	}
        	
        	$output[] = $safe_files_html;
        	$output[] = "</div>";
        	
        	echo implode("\n", $output);
        	$this->html->footer();
        }
        
        //the quarantine dashboard and control
        public function dashborad_quarantine()
        {
        	$this->html = new tebravo_html();
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
        	
        	$quarantine = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'quarantine') ) );
        	$quarantine_html = __("No files stored in the quarantine", TEBRAVO_TRANS);
        	$flush_nonce= $this->html->init->create_nonce( 'flush-qurantine' );
        	
        	$desc = __("Quarantine is the safe place to store suspected files.", TEBRAVO_TRANS);
        	$extra = "<a href='".$this->html->init->admin_url."-antivirus&p=quarantine&action=flush&nonce=$flush_nonce' class='tebravo_curved'>".__("Flush")."</a>";
        	$this->html->header(__("Antivirus - Quarantine", TEBRAVO_TRANS), $desc, 'antivirus.png', $extra);
        	
        	$this->html->print_loading();
        	
        	$utility = new tebravo_antivirus_utility();
        	
        	$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
        	
        	//flush quarantine
        	if( !empty( $_GET['action'] ) )
        	{
        		if( $_GET['action'] == 'flush' )
        		{
        			if( !empty( $_GET['nonce'] ) 
        					&& wp_verify_nonce( $_GET['nonce'], $this->html->init->security_hash.'flush-qurantine') )
        			{
        				tebravo_utility::update_option( TEBRAVO_DBPREFIX.'quarantine', '' );
        				tebravo_redirect_js( $this->html->init->admin_url.'-antivirus&p=quarantine&msg=05');
        				exit;
        			}
        		}
        	}
        	
        	//quarantine list
        	if( ! empty( $quarantine ) )
        	{
        		$exp = explode( '#tebravo#', $quarantine );
        		if( is_array( $exp) && count( $exp ) > 0)
        		{
        			$quarantine_html = "<table border=0 width=100% cellspacing=0>";
        			$nonce = $this->html->init->create_nonce( 'take-action-infected-files' );
        			$back = true;
        			
        			foreach ( $exp as $file )
        			{
        				if( !empty( $file ) ):
        				$file = str_replace('//', '/', $file);
        				$f_hash = tebravo_encodeString( $file , $this->html->init->security_hash );
        				$actions = "<a onclick=\"return confirm('".__("Sure! Delete File From Server?!", TEBRAVO_TRANS)."')\" href='".$this->html->init->admin_url."-antivirus&p=start_scan&pid=quarantine&new_action=delete&_nonce=".$nonce."&f=".$f_hash."&back=$back'>".__("Delete File", TEBRAVO_TRANS)."</a>";
        				
        				if(!$utility->check_safe_list( $file )){
        					$actions .= " | <a href='".$this->html->init->admin_url."-antivirus&p=start_scan&pid=quarantine&new_action=safe&_nonce=".$nonce."&f=".$f_hash."&back=$back'>".__("Mark as Safe", TEBRAVO_TRANS)."</a>";
        				}
        				
        				if( !file_exists( $file ) )
        				{
        					$actions = "<i><font color=green>".__("Removed!", TEBRAVO_TRANS)."</font></i>";
        				}
        				
        				$quarantine_html .= "<tr class='tebravo_underTD'><td width=80%><strong>".basename( $file )."</strong><br />".$file;
        				$quarantine_html .= "</td><td>". $actions ."</td></tr>";
        				endif;
        			}
        			$quarantine_html .= "</table>";
        		}
        	}
        	
        	$output[] = $quarantine_html;
        	$output[] = "</div>";
        	
        	echo implode("\n", $output);
        	$this->html->footer();
        }
        
        //delete antivirus process
        public function delete()
        {
        	global $wpdb;
        	
        	
        	$this->html = new tebravo_html();
        	
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
        	
        	if( empty( $_GET['_nonce']) 
        			|| false === wp_verify_nonce($_GET['_nonce'], $this->html->init->security_hash.'delete_pid_antivirus')
        			|| empty( $_GET['pid'] ) )
        	{
				tebravo_redirect_js($this->html->init->admin_url.'-antivirus&err=02');
				exit;
        	}
        	
        	$pid = trim( esc_html( $_GET['pid'] ) );
        	
        	$row = $wpdb->get_row("SELECT pid FROM " .tebravo_utility::dbprefix()."scan_ps WHERE pid='$pid' Limit 1");
        	if( null === $row )
        	{
        		tebravo_redirect_js($this->html->init->admin_url.'-antivirus&err=02');
        		exit;
        	}
        	
        	//delete PID files
        	$tmp_dir = TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/tmp/';
        	$pid_files[] = $tmp_dir.'/'.$pid.'.txt';
        	$pid_files[] = $tmp_dir.'/'.$pid.'_startover.txt';
        	$pid_files[] = $tmp_dir.'/'.$pid.'_results.txt';
        	$pid_files[] = $tmp_dir.'/'.$pid.'_infected.txt';
        	
        	if( is_array($pid_files)):
	        	foreach ( $pid_files as $file )
	        	{
	        		if( file_exists( $file ) )
	        		{
	        			tebravo_files::remove( $file );
	        		}
	        	}
        	endif;
        	
        	//delete process from DB
        	$wpdb->delete(tebravo_utility::dbprefix()."scan_ps", array( "pid" => $pid ) );
        	tebravo_redirect_js($this->html->init->admin_url.'-antivirus&msg=06');
        	exit;
        }
        
        //antivirus dashboard
        public function dashboard_antivirus()
        {
        	$this->html = new tebravo_html();
        	
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
        	
        	$desc = __("All processes and tools are available here!", TEBRAVO_TRANS);
        	$extra = "<a href='".$this->html->init->admin_url."-antivirus&p=scan' class='tebravo_curved'>".__("Start Scan", TEBRAVO_TRANS)."</a>";
        	$this->html->header(__("Antivirus - Dashboard", TEBRAVO_TRANS), $desc, 'antivirus.png', $extra);
        	
        	$this->html->print_loading();
        	
        	$del_nonce = $this->html->init->create_nonce('delete_pid_antivirus');
        	$delete_confirm = __("Are you sure?! Delete this process!", TEBRAVO_TRANS);
        	
        	//quarantine counter
        	$quarantine = 0;
        	$quarantine_list = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'quarantine' ) ) );
        	if( $quarantine_list != '' )
        	{
        		$exp_q = explode( '#tebravo#' , $quarantine_list );
        		foreach ($exp_q as $quarantinefile)
        		{
        			if( !empty( $quarantinefile ) ){$quarantine++;}
        		}
        		$quarantine = (int)$exp_q;
        	}
        	
        	//safe files counter
        	$safe = 0;
        	$safe_list = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'safe_files' ) ) );
        	if( $safe_list != '' )
        	{
        		$exp_s = explode( ',' , $safe_list );
        		
        		foreach ($exp_s as $safefile)
        		{
        			if( !empty( $safefile ) ){$safe++;}
        		}
        		$safe = (int)$safe;
        	}
        	
        	$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
        	$output[] = $this->html->button_small("Quarantine [".$quarantine."]", "button", "quarantine");
        	$output[] = $this->html->button_small("Safe Files [".$safe."]", "button", "safe");
        	$output[] = $this->html->button_small("Settings", "button", "settings");
        	$output[] = "<table border=0 width=100% cellspacing=2>";
        	//running processes
        	$query_run = $this->processes_list('running');
        	$output[] = "<tr class='tebravo_headTD'><td colspan=6><strong>".__("In Progress (Running Now)", TEBRAVO_TRANS)."</strong>";
        	$output[] = "[ ".count($query_run)." ]</td></tr>";
        	$output[] = "<tr class='tebravo_headTD'>";
        	$output[] = "<td width=30%><strong>".__("PID", TEBRAVO_TRANS)."</strong></td>";
        	$output[] = "<td width=20%><strong>".__("Owner", TEBRAVO_TRANS)."</strong></td>";
        	$output[] = "<td width=20%><strong>".__("Module (Type)", TEBRAVO_TRANS)."</strong></td>";
        	$output[] = "<td width=15%><strong>".__("Affected", TEBRAVO_TRANS)."</strong></td>";
        	$output[] = "<td width=10%><strong>".__("Total Files", TEBRAVO_TRANS)."</strong></td>";
        	$output[] = "<td width=5%><strong>".__("Delete", TEBRAVO_TRANS)."</strong></td>";
        	$output[] = "</tr>";
        	
        	$running = '';
        	$owner = '';
        	if( count( $query_run ) > 0)
        	{
        		foreach ( $query_run as $run )
        		{
        			if( $run->start_by == 'cronjob' )
        			{
        				$owner = __("Cronjob", TEBRAVO_TRANS);
        			} else {
        				$owner_id = intval( $run->start_by );
        				if( $owner_id > 0){
        					$owner_details = get_user_by( 'id', $owner_id );
        					$owner = $owner_details->display_name;
        				}
        			}
        			
        			$no_update = '';
        			$scan_module = $this->handle_scan_type($run->scan_type);
        			
        			//check handing
        			if( !is_array( $scan_module ) ){wp_die(TEBRAVO_NO_ACCESS_MSG); exit;}
        			
        			//fetching link
        			if( $run->scan_type == 'malware' || $run->scan_type == 'phpmussel' )
        			{
        				$link = $this->html->init->admin_url."-antivirus&p=start_scan&pid=".$run->pid."&no_update=1";
        			} else if( $run->scan_type == 'googshavar')
        			{
        				$link = $this->html->init->admin_url."-antivirus&p=safebrowsing&pid=".$run->pid."&no_update=1";
        			} else {
        				$link = $this->html->init->admin_url."-antivirus&p=".$scan_module[$run->scan_type]."&pid=".$run->pid."&no_update=1";
        			}
        			
        			//infected files counter
        			$infected_files = $run->infected_files;
        			if( $run->infected_files > 0)
        			{
        				$infected_files = ($run->infected_files - 1);
        			}
        			
        			//delete link
        			$img = "<img style='cursor:pointer' src='".plugins_url('/assets/img/delete.png', TEBRAVO_PATH)."'>";
        			$delete_href = $this->html->init->admin_url."-antivirus&p=delete&pid=".$run->pid."&_nonce=".$del_nonce;
        			$delete = "<a href='$delete_href' onclick=\"return confirm('$delete_confirm')\">".$img."</a>";
        			
        			$running .= "<tr class='tebravo_underTD'>";
        			$running .= "<td width=30%><a href='$link'>".$run->pid."</a><br />";
        			$running .= "<i>".__("Process", TEBRAVO_TRANS).": ".$run->p_percent."%<i><br />";
        			$running .= "<i>".__("Since", TEBRAVO_TRANS).": ".tebravo_ago($run->start_at)." ".__('ago', TEBRAVO_TRANS)."<i>";
        			$running .= "</td>";
        			$running .= "<td width=20%>".($owner != ''? $owner : '--')."</td>";
        			$running .= "<td width=20%>".strtoupper($run->scan_type)."</td>";
        			$running .= "<td width=15%>".(int)$infected_files."</td>";
        			$running .= "<td width=10%>".(int)$run->total_files."</td>";
        			$running .= "<td width=5%>".$delete."</td>";
        			$running .= "</tr>";
        		}
        	} else {
        		$output[] = "<tr class='tebravo_underTD'><td colspan=5><i>";
        		$output[] = __("Nothing!", TEBRAVO_TRANS)."</i></td></tr>";
        	}
        	$output[] = $running;
        	
        	//finished processes
        	$query_finished= $this->processes_list('finished');
        	$output[] = "<tr class='tebravo_headTD'><td colspan=6><strong>".__("Finished (What Are Done)", TEBRAVO_TRANS)."</strong>";
        	$output[] = "[ ".count($query_finished)." ]</td></tr>";
        	$output[] = "<tr class='tebravo_headTD'>";
        	$output[] = "<td width=30%><strong>".__("PID", TEBRAVO_TRANS)."</strong></td>";
        	$output[] = "<td width=20%><strong>".__("Owner", TEBRAVO_TRANS)."</strong></td>";
        	$output[] = "<td width=20%><strong>".__("Module (Type)", TEBRAVO_TRANS)."</strong></td>";
        	$output[] = "<td width=15%><strong>".__("Affected", TEBRAVO_TRANS)."</strong></td>";
        	$output[] = "<td width=10%><strong>".__("Total Files", TEBRAVO_TRANS)."</strong></td>";
        	$output[] = "<td width=5%><strong>".__("Delete", TEBRAVO_TRANS)."</strong></td>";
        	$output[] = "</tr>";
        	
        	$finished= '';
        	if( count( $query_finished ) > 0)
        	{
        		foreach ( $query_finished as $finish )
        		{
        			if( $finish->start_by == 'cronjob' )
        			{
        				$owner = __("Cronjob", TEBRAVO_TRANS);
        			} else {
        				$owner_id = intval( $finish->start_by );
        				if( $owner_id > 0){
        					$owner_details = get_user_by( 'id', $owner_id );
        					$owner = $owner_details->display_name;
        				}
        			}
        			
        			$no_update = '';
        			$scan_module = $this->handle_scan_type($finish->scan_type);
        			//check handing
        			if( !is_array( $scan_module ) ){wp_die(TEBRAVO_NO_ACCESS_MSG); exit;}
        			
        			//set percent of scan process
        			if( $finish->status == 'finished' )
        			{
        				$percent = "100";
        			} else {$percent = $finish->p_percent;}
        			
        			//fetching link
        			if( $finish->scan_type == 'malware' || $finish->scan_type == 'phpmussel' )
        			{
        				$link = $this->html->init->admin_url."-antivirus&p=start_scan&pid=".$finish->pid."&no_update=1";
        			} else if( $finish->scan_type == 'googshavar')
        			{
        				$link = $this->html->init->admin_url."-antivirus&p=safebrowsing&pid=".$finish->pid."&no_update=1";
        			} else {
        				$link = $this->html->init->admin_url."-antivirus&p=".$scan_module[$finish->scan_type]."&pid=".$finish->pid."&no_update=1";
        			}
        			
        			//delete link
        			$img = "<img style='cursor:pointer' src='".plugins_url('/assets/img/delete.png', TEBRAVO_PATH)."'>";
        			$delete_href = $this->html->init->admin_url."-antivirus&p=delete&pid=".$finish->pid."&_nonce=".$del_nonce;
        			$delete = "<a href='$delete_href' onclick=\"return confirm('$delete_confirm')\">".$img."</a>";
        			
        			//infected files counter
        			$infected_files= $finish->infected_files;
        			if( $finish->infected_files > 0)
        			{
        				$infected_files= ($finish->infected_files - 1);
        			}
        			
        			$finished .= "<tr class='tebravo_underTD'>";
        			$finished .= "<td width=30%><a href='$link'>".$finish->pid."</a><br />";
        			$finished .= "<i>".__("Process", TEBRAVO_TRANS).": ".$percent."%<i><br />";
        			$finished .= "<i>".__("Since", TEBRAVO_TRANS).": ".tebravo_ago($finish->start_at)." ".__('ago', TEBRAVO_TRANS)."<i>";
        			$finished .= "</td>";
        			$finished .= "<td width=20%>".($owner != ''? $owner : '--')."</td>";
        			$finished .= "<td width=20%>".strtoupper($finish->scan_type)."</td>";
        			$finished .= "<td width=15%>".(int)$infected_files."</td>";
        			$finished .= "<td width=10%>".(int)$finish->total_files."</td>";
        			$finished .= "<td width=5%>".$delete."</td>";
        			$finished.= "</tr>";
        		}
        	} else {
        		$output[] = "<tr class='tebravo_underTD'><td colspan=5><i>";
        		$output[] = __("Nothing!", TEBRAVO_TRANS)."</i></td></tr>";
        	}
        	$output[] = $finished;
        	
        	$output[] = "</table>";
        	$output[] = "</div>";
        	
        	echo implode("\n", $output);
        	
        	?>
        	<script>
			jQuery("#quarantine").click(function()
					{
						window.location.href='<?php echo $this->html->init->admin_url.'-antivirus&p=quarantine';?>';
					});
			jQuery("#safe").click(function()
					{
						window.location.href='<?php echo $this->html->init->admin_url.'-antivirus&p=safefiles';?>';
					});
			jQuery("#settings").click(function()
					{
						window.location.href='<?php echo $this->html->init->admin_url.'-antivirus&p=settings';?>';
					});
        	</script>
        	<?php 
        	
        	$this->html->footer();
        }
        
        public function processes_list( $status=false, $scan_type=false, $start_by=false )
        {
        	global $wpdb;
        	
        	$q[] = '';
        	if( $status )
        	{
        		$status = trim( esc_html( $status ) );
        		$q[] = 'status=\''.$status.'\'';
        	}
        	
        	if( $scan_type )
        	{
        		$scan_type= trim( esc_html( $scan_type ) );
        		$q[] = 'scan_type=\''.$scan_type.'\'';
        	}
        	
        	if( $start_by )
        	{
        		$start_by = trim( esc_html( $start_by ) );
        		$q[] = 'start_by=\''.$start_by.'\'';
        	}
        	
        	$where = '';
        	if( count($q) > 0)
        	{
        		$where = implode('and ', $q);
        		$where = trim( $where );
        		$where = "WHERE ".substr( $where, 3 );
        	}
        	
        	$query = $wpdb->get_results("SELECT * FROM 
					" .tebravo_utility::dbprefix()."scan_ps 
        			$where 
					ORDER BY id DESC");
        	return $query;
        	
        }
        
        //stop scan process
        public function scan_files_stop( )
        {
        	global $wpdb;
        	
        	$this->html = new tebravo_html();
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( ); exit;}
        	
        	//check nonce
        	if( empty( $_GET['_nonce'] ) 
        			|| false === wp_verify_nonce($_GET['_nonce'], 'scan_files_stop')){
        		exit;
        	}
        	
        	//update status
        	if( !empty( $_GET['pid'] ) ):
        	$pid = trim( $_GET['pid'] );
        	$pid = esc_html( $pid );
        	$status = 'stopped';
        	
        	$row = $wpdb->get_row("SELECT status,pid FROM ".
        			tebravo_utility::dbprefix()."scan_ps
        			WHERE pid='$pid' Limit 1");
        	
        	if( null !== $row )
        	{
        		if( $row->status != $status)
        		{
        			$wpdb->update(tebravo_utility::dbprefix()."scan_ps", array( "status" => $status ), array( "pid" => $pid));
        			?>
        			<script>
					jQuery(".tebravo_scan_process").html("<font color=green><strong><?php echo __("Stopped!", TEBRAVO_TRANS);?><strong></font>");
					jQuery("#stop").hide();
					jQuery("#resume").show();
					jQuery(".tebravo_loading").hide();
        			</script>
        			<?php 
        		}
        	}
        	endif;
        	
        	exit;
        }
        
        //resume scan process
        public function scan_files_resume( )
        {
        	global $wpdb;
        	
        	$this->html = new tebravo_html();
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( ); exit;}
        	
        	ob_start();
        	//check nonce
        	if( empty( $_GET['_nonce'] )
        	|| false === wp_verify_nonce($_GET['_nonce'], 'scan_files_resume')){
        		exit;
        	}
        	
        	//update status
        	if( !empty( $_GET['pid'] ) ):
        	$pid = trim( $_GET['pid'] );
        	$pid = esc_html( $pid );
        	$status = 'running';
        	
        	$row = $wpdb->get_row("SELECT status,pid FROM ".
        			tebravo_utility::dbprefix()."scan_ps
        			WHERE pid='$pid' Limit 1");
        	
        	if( null !== $row )
        	{
        		if( $row->status != $status)
        		{
        			$wpdb->update(tebravo_utility::dbprefix()."scan_ps", 
        					array( "status" => $status), 
        					array( "pid" => $pid));
        			?>
        			<script>
					jQuery(".tebravo_scan_process").html("<font color=green><strong><?php echo __("Resume Scan Process ...", TEBRAVO_TRANS);?><strong></font>");
					jQuery("#stop").show();
					jQuery("#resume").hide();
					jQuery(".tebravo_loading").hide();
        			</script>
        			<?php 
        		}
        	}
        	endif;
        	
        	$out = @ob_get_contents();
        	@ob_end_clean();
        	echo $out;
        	@ob_flush();
        	
        	exit;
        }
        
        //start over scan process
        public function scan_files_startover( )
        {
        	global $wpdb;
        	
        	$this->html = new tebravo_html();
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( ); exit;}
        	
        	ob_start();
        	//check nonce
        	if( empty( $_GET['_nonce'] )
        	|| false === wp_verify_nonce($_GET['_nonce'], 'scan_files_startover')){
        		exit;
        	}
        	
        	//update status
        	if( !empty( $_GET['pid'] ) ):
        	$pid = trim( $_GET['pid'] );
        	$pid = esc_html( $pid );
        	
        	$tmp_dir = TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/tmp/';
        	$file_process = $tmp_dir.$pid.'.txt';
        	$file_startover = $tmp_dir.$pid.'_startover.txt';
        	$file_data = tebravo_files::read($file_startover);
        	
        	tebravo_files::write($file_process, $file_data);
        	
        	$status = 'running';
        	
        	
        			$wpdb->update(tebravo_utility::dbprefix()."scan_ps", 
        					array( "status" => $status, "cheked_files" => "0", "infected_files" => "0", "infected_results" => "")
        					, array( "pid" => $pid));
        			?>
        			<script>
					jQuery(".tebravo_scan_process").html("<font color=green><strong><?php echo __("Start Over ...", TEBRAVO_TRANS);?><strong></font>");
					jQuery("#stop").show();
					jQuery("#resume").hide();
					jQuery(".tebravo_loading").hide();
        			</script>
        			<?php 
        		
        	endif;
        	
        	$out = @ob_get_contents();
        	@ob_end_clean();
        	echo $out;
        	@ob_flush();
        	exit;
        }
        
        
        //safe browsing scan
        public function dashboard_safebrowsing_scan ()
        {
        	global $wpdb;
        	
        	$this->html = new tebravo_html();
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
        	$this->get_scanner_html_details( 'scan_3' );
        	
        	
        	$this->html->print_loading();
        	
        	if( ! empty( $_GET['pid'] ) )
        	{
        		$pid = trim( esc_html( $_GET['pid'] ) );
        		
        		$pid_file = TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/tmp/'.$pid.'.txt';
        		
        		if( file_exists( $pid_file ) )
        		{
        			$antivirus = new tebravo_googshavar_scanner();
        			
        			$data = tebravo_files::read( $pid_file );
        			
        			if( $data != '')
        			{
        				$exp = explode( '#tebravo#' , $data );
        				$url_to_scan = $exp[0];
        				
        				if(filter_var( $url_to_scan , FILTER_VALIDATE_URL ) )
        				{
        					$result = $antivirus->scan( $url_to_scan );
        					
        					$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
        					$infected_files = '';
        					$infected = '';
        					if( $result == '204' )
        					{
        						$output[] = "<p align=center><img src='".plugins_url( '/assets/img/11-128.png', TEBRAVO_PATH)."'></p>";
        						$output[] = "<h3><font color=green><center>".__("SAFE", TEBRAVO_TRANS)."</center></font></h3>";
        						$output[] = "<hr><strong>".__("URL", TEBRAVO_TRANS)."</strong>: ".$url_to_scan."<br />";
        						$output[] = "<hr><strong>".__("Result", TEBRAVO_TRANS)."</strong>: ".__("SAFE", TEBRAVO_TRANS)."<br />";
        						
        					} else {
        						$output[] = "<p align=center><img src='".plugins_url( '/assets/img/dialog-error.png', TEBRAVO_PATH)."'></p>";
        						$output[] = "<h3><font color=red><center>".__("NOT SAFE", TEBRAVO_TRANS)."</center></font></h3>";
        						$output[] = "<hr><strong>".__("URL", TEBRAVO_TRANS)."</strong>: ".$url_to_scan."<br />";
        						$output[] = "<hr><strong>".__("Result", TEBRAVO_TRANS)."</strong>: ".__("NOT SAFE", TEBRAVO_TRANS)."<br />";
        						$infected_files = $url_to_scan;
        						$infected = 1;
        					}
        					$output[] = "</div>";
        					
        					$wpdb->update(tebravo_utility::dbprefix().'scan_ps', array(
        							'status'=>'finished',
        							'infected'=>sanitize_text_field($infected),
        							'infected_files'=>sanitize_text_field($infected),
        							'cheked_files'=>1,
        							'p_percent'=>'100',
        							'infected_results' => sanitize_text_field($infected_files)
        					), array (
        							'pid' => $pid
        					));
        					
        					echo implode("\n", $output);
        				} else {
        					tebravo_redirect_js( $this->html->init->admin_url.'-antivirus&p=scan_3&err=12' , true );
        					exit;
        				}
        			} else {
        				tebravo_redirect_js( $this->html->init->admin_url.'-antivirus&p=scan_3&err=03' , true );
        				exit;
        			}
        		} else {
        			tebravo_redirect_js( $this->html->init->admin_url.'-antivirus&p=scan_3&err=04' , true );
        			exit;
        		}
        	} else {
        		tebravo_redirect_js( $this->html->init->admin_url.'-antivirus&p=scan_3&err=02' , true );
        		exit;
        	}
        	
        	$this->html->footer();
        }
        //load infected results
        public function scan_files_infected_results( )
        {
        	global $wpdb;
        	
        	$this->html = new tebravo_html();
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( ); exit;}
        	
        	ob_start();
        	//check nonce
        	if( empty( $_GET['_nonce'] )
        	|| false === wp_verify_nonce($_GET['_nonce'], 'scan_files_infected_results')){
        		exit;
        	}
        	
        	//update status
        	if( !empty( $_GET['pid'] ) ):
        	$pid = trim( $_GET['pid'] );
        	$pid = esc_html( $pid );
        	
        	$row = $wpdb->get_row("SELECT infected_results,pid,scan_type FROM ".
        			tebravo_utility::dbprefix()."scan_ps
        			WHERE pid='$pid' Limit 1");
        			$scanType = trim( esc_html( $row->scan_type) ) ;
        			
        			//load scanner class
        			$class_name = 'tebravo_'.$scanType.'_scanner';
        			try {
        				$antivirus = new $class_name();
        			}
        			catch(Exception $e) {
        				echo 'Message: ' .$e->getMessage();
        			}
        			
        			$antivirus->pid = $pid;
        			
        	$infected_results = $row->infected_results;
        	$infected_exp = explode(",", $infected_results);
        	
        	$new_data_infected_screen = '';
        	
        	if( is_array( $infected_exp ) ){
        	
	        	foreach ($infected_exp as $infected_result)
	        	{
	        		if($infected_result != '')
	        		{
	        			$new_data_infected_screen .=
	        			$antivirus->create_report_mini($infected_result, 'file_name')."
						<b>".__('REPORT', TEBRAVO_TRANS)."</b>: ".$antivirus->create_report_mini($infected_result, 'file_report')."<hr>";
	        		}
	        	}
        	
        	}
        	$new_data_infected_screen =str_replace("//", "/", $new_data_infected_screen);
        	echo $new_data_infected_screen;
        	endif;
        	
        	$out = @ob_get_contents();
        	@ob_end_clean();
        	echo $out;
        	@ob_flush();
        	exit;
        }
        //read dir to scan
        public function read_dir( $dir=false )
        {
        	$this->html = new tebravo_html();
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die(  ); exit;}
        	ob_start();
        	if(!$dir)
        	{
        		$dir = ABSPATH;
        	}
        	
        	$ajax_url = add_query_arg(array(
        			'action' => 'ajax_load_dir',
        			'_nonce' => wp_create_nonce('tebravo_load_dir')
        	), admin_url('admin-ajax.php') );
        	
        	$files = tebravo_dirs::read( $dir ) ;
        	
        	$output = '<table border=0 width=100% cellspacing=0>';
        	$output .= "<tr class='tebravo_underTD'><td colspan=2>".$this->breadcrumbs($dir)."</td></tr>";
        	$output .= "<tr class='tebravo_headTD'><td colspan=2>";
        	$output .= "<input checked type='checkbox' id='tebravo_checkall'><label for='tebravo_checkall'>".__("Select/Unselect All", TEBRAVO_TRANS)."</label></td></tr>";
        	$js = '';
        	$id = '';
        	
        	if( is_array( $files ) ):
        	
        	foreach ($files as $file)
        	{
        		$icon_path = TEBRAVO_DIR.'/assets/img/icons/'.tebravo_files::extension($file).'.png';
        		$icon = plugins_url('assets/img/icons/'.tebravo_files::extension($file).'.png', TEBRAVO_PATH);
        		$x_icon = plugins_url('assets/img/icons/x.png', TEBRAVO_PATH);
        		$folder_icon = plugins_url('assets/img/icons/folder.png', TEBRAVO_PATH);
        		
        			if( file_exists( $icon_path) )
        			{
        				$file_icon = $icon;
        			} else {
        				$file_icon = $x_icon;
        			}
        			
        		
        		if(is_dir( $dir.'/'.$file )){
        			$id = tebravo_encodeString( $dir."/".$file , $this->html->init->security_hash);
        			$output .= "<tr class='tebravo_headTD'><td width=18 style='margin:0 auto'>";
        			$output .= "<img src='". $folder_icon."'></td>";
        			$output .= "<td><input checked type='checkbox' name='files[]' value='".$dir."/".$file."' class='tebravo_checkclass'>".$file;
					$output .= " <span class='tebravoFixInline' id='$id'>".__("Open", TEBRAVO_TRANS)."</span> ";
					$output .= "</td>";
        			$output .= "</tr>";
        			
        			
        			$js .= "jQuery('#$id').click(function(){".PHP_EOL;
        			$js .= "jQuery('.tebravo_loading').show();".PHP_EOL;
        			$js .= "jQuery('#dirlist').load('".$ajax_url."&id=".$id."&f=".$file."');".PHP_EOL;
        			$js .= "});".PHP_EOL;
        		} else {
        			$output .= "<tr class='tebravo_underTD'><td width=18 style='margin:0 auto'>";
        			$output .= "<img src='". $file_icon."'></td>";
        			$output .= "<td><input checked type='checkbox' name='files[]' value='".$dir."/".$file."' class='tebravo_checkclass'>".$file."</td>";
        			$output .= "</tr>";
        		}
        	}
        	
        	endif;
        	
        	$output .= "</table>";
        	
        	$output .= "<script>".PHP_EOL;
        	$output .= $js;
        	$output .= "</script>";
        	
        	return $output;
        }
        
        public function read_dir_zoom( $dir )
        {
        	        
        	if( is_dir( $dir ) ):
        		$files = tebravo_dirs::read( $dir ) ;
        	endif;
        	
        	$output = '';
        	
        	if( is_array( $files ) ):
	        	foreach ($files as $file)
	        	{
	        		if( is_dir( $dir.'/'.$file ) )
	        		{
	        			$output .= $this->read_dir_zoom( $dir.'/'.$file );
	        		}
	        		
	        		$output .= $dir.'/'.$file.'#tebravo#';
	        	}
        	endif;
        	
        	return $output;
        }
        
        //ajax load dir contents
        public function ajax_load_dir()
        {
        	$this->html = new tebravo_html();
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
        	
        	ob_start();
        	$this->html = new tebravo_html();
        	
        	$ajax_url = add_query_arg(array(
        			'action' => 'ajax_load_dir',
        			'_nonce' => wp_create_nonce('tebravo_load_dir')
        	), admin_url('admin-ajax.php') );
        	
        	$back_id = '';
        	$js = '';
        	$back_dir = '';
        	
        	if(!empty($_GET['_nonce'])
        			&& false !== wp_verify_nonce($_GET['_nonce'], 'tebravo_load_dir'))
        	{
        		if( !empty( $_GET['id'] ) )
        		{
        		    $dir = tebravo_decodeString( esc_html($_GET['id']), $this->html->init->security_hash);
        			if( is_dir( $dir ))
        				/*
        				if($dir != ABSPATH && !empty($_GET['f']))
        				{
        					$back_dir = str_replace($_GET['f'], '', $dir);
        					$back_id = tebravo_encodeString($back_dir, $this->html->init->security_hash);
        					
        					echo "<span id='$back_id' style='cursor:pointer'>&larr; ".__("Back", TEBRAVO_TRANS)."</span>";
        					$js .= "jQuery('#$back_id').click(function(){".PHP_EOL;
        					$js .= "jQuery('.tebravo_loading').show();".PHP_EOL;
        					$js .= "jQuery('#dirlist').load('".$ajax_url."&id=".$back_id."&f=".basename(dirname($dir))."');".PHP_EOL;
        					$js .= "});".PHP_EOL;
        				}
        				*/
        				echo $this->read_dir( $dir );
        				
        				echo "<script>".$js."</script>";
        		}
        	} 
        	?>
        	<script>
			jQuery(".tebravo_loading").hide();
        	
        	jQuery(document).ready(function () {
        		jQuery("#tebravo_checkall").click(function () {
        			jQuery(".tebravo_checkclass").prop('checked', jQuery(this).prop('checked'));
        	    });
        	});

        	</script>
        
        	<?php 
        	$out = @ob_get_contents();
        	@ob_end_clean();
        	echo $out;
        	@ob_flush();
        	exit;
        }
        
        //directories breadcrumbs
        public function breadcrumbs( $dir )
        {
        	$this->html = new tebravo_html();
        	
        	if( !$dir)
        	{
        		$dir = ABSPATH;
        	}
        	//clean string
        	$dir = str_replace('//', '/', $dir);
        	
        	$ajax_url = add_query_arg(array(
        			'action' => 'ajax_load_dir',
        			'_nonce' => wp_create_nonce('tebravo_load_dir')
        	), admin_url('admin-ajax.php') );
        	
        	if( false !== strpos( $dir , '/' ) ):
        		$exp = explode("/", $dir);
        	endif;
        	
        	$output = '';
        	$js = '';
        	
        	if( is_array( $exp ) ):
	        	foreach ($exp as $folder => $file)
	        	{
	        		$id = $this->create_path_url($folder, $dir) . $file;
	        		$id = tebravo_encodeString( $id , $this->html->init->security_hash);
	        		
	        		if( $file != '')
	        		{
	        			if( $folder != '1'){
			        		$output .= "<span class='tebravo_breadcrumbs' id='$id'>".$file."</span> / ";
			        		$js .= "jQuery('#$id').click(function(){".PHP_EOL;
			        		$js .= "jQuery('.tebravo_loading').show();".PHP_EOL;
			        		$js .= "jQuery('#dirlist').load('".$ajax_url."&id=".$id."&f=".basename(dirname($dir))."');".PHP_EOL;
			        		$js .= "});".PHP_EOL;
	        			} else {
	        				$output .= "<span>".$file."</span> / ";
	        			}
	        		}
	        	}
        	endif;
        	
        	$output = $output . '<script>'. $js .'</script>';
        	return rtrim($output, '/');
        }
        
        //create breadcrumbs url
        protected function create_path_url( $n,$dir )
        {
        	if( false !== strpos( $dir , '/' ) ):
        		$exp = explode('/', $dir);
        	endif;
        	
        	$output = '';
        	
        	if( is_array( $exp ) ):
	        	for($i=0; $i<$n; $i++)
	        	{
	        		$output .= $exp[$i].'/';
	        	}
        	endif;
        	
        	return $output;
        }
        
        public function scan_files( $noprint=false , $pid=false)
        {
        	$this->html = new tebravo_html();
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
        	
        	//PHP settings
        	$this->set_php_settings();
        	
        	//no print activated or not
        	if( !$noprint ){
	        	if( empty( $_GET['_nonce'])
	        			|| false === wp_verify_nonce($_GET['_nonce'], 'scan_files'))
	        	{
	        		wp_die();
	        		exit;
	        	}
        	}
        	
        	//set PID
        	if( !$pid )
        	{
        		$pid = trim( esc_html( $_GET['pid'] ) );
        	}
        	
        	//start scan
        		if( !empty( $pid ) )
        		{
        			global $wpdb;	
        			
        			
        			$tmp_dir = TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/tmp/';
        			$file = $tmp_dir.$pid.'.txt';
        			
        			$file_results = $tmp_dir.$pid.'_results.txt';
        			$file_infected = $tmp_dir.$pid.'_infected.txt';
        			
        			$percent = '0';
        			//create file for result
        			if(!file_exists($file_results)){
        				tebravo_files::write($file_results, '');
        			}
        			
        			//create file for infected
        			if(!file_exists($file_infected)){
        				tebravo_files::write($file_infected, '');
        			}
        			
        			ob_start();
        			if(! file_exists( $file ))
        			{
        				//no print activated or not
        				if( !$noprint ){
	        				?>
	        				<script>
							jQuery(".tebravo_scan_process").html("<font color=red><?php echo __("PID file does not exists", TEBRAVO_TRANS)."<br><i>".$file."</i>";?></font>");
	        				</script>
	        				<?php 
        				}
        				exit;
        			}
        			
        			//create load infected ajax URL
        			$ajax_load_infected_url = add_query_arg( array(
        			'action' => 'scan_files_infected_results',
        			'_nonce' => wp_create_nonce('scan_files_infected_results')
        			), admin_url('admin-ajax.php'));
        			
        			//set to running
        			
        			$row = $wpdb->get_row("SELECT total_files,scan_type,start_at,end_at,status,cheked_files,infected_files FROM ".
        					tebravo_utility::dbprefix()."scan_ps
        					WHERE pid='$pid' Limit 1");
        			
        					if($row->status == 'prepare')
        					{
        						$wpdb->update(tebravo_utility::dbprefix().'scan_ps',
        								array('status' => 'running'), array('pid' => $pid));
        					}
        					
        					if($row->status == 'running')
        					{
        						//no print activated or not
        						if( !$noprint ){
	        						?>
			        				<script>
									jQuery("#resume").hide();
									jQuery("#stop").show();
			        				</script>
			        				<?php 
        						}
        					} else {
        						//no print activated or not
        						if( !$noprint ){
	        						?>
			        				<script>
									jQuery("#resume").show();
									jQuery("#stop").hide();
			        				</script>
			        				<?php 
        						}
        					}
        					//check process if completed
        					if($row->cheked_files >= ($row->total_files))
        					{
        						$wpdb->update(tebravo_utility::dbprefix().'scan_ps',
        								array('end_at' => sanitize_text_field(time()), "status" => "finished"), array('pid' => $pid));
        						$percent = "100";
        						$loading_img = plugins_url('assets/img/loading.gif', TEBRAVO_PATH);
        						
        						if($row->infected_files == 0){
        							$infected_files_count = 0;
        						} else {
        							$infected_files_count = intval(floor($row->infected_files-1));
        						}
        						
        						//no print activated or not
        						if( !$noprint ){
	        						?>
			        				<script>
									jQuery(".tebravo_scan_process").html("<font color=green><?php echo __("Done.", TEBRAVO_TRANS);?></font>");
									jQuery("#checked").html("<?php echo $row->cheked_files;?>");
									jQuery("#infected").html("<?php echo $infected_files_count;?>");
									jQuery("#scan_progress").html("<?php echo $percent;?>%");
									jQuery("#scan_progress").css("width", "<?php echo $percent;?>%");
									jQuery("#start_at").html("<?php echo tebravo_ago($row->start_at);?>");
									jQuery("#infected_results").load("<?php echo $ajax_load_infected_url.'&pid='.$pid;?>");
									//jQuery("#infected_results").html("<br><br><center><img src='<?php echo $loading_img;?>'></center>");
									jQuery("#take_action").show();
									jQuery("#stop").hide();
									jQuery("#resume").hide();
			        				</script>
			        				<?php 
        						}
        						exit;
        					}
        					
        					//check status
        					if($row->status != 'running')
        					{
        						exit;
        					}
        					//check current action
        					if( !empty($_GET['tebravo_action']) && $_GET['tebravo_action'] == 'hold')
        					{
        						exit;
        					}
        					
        					if(null !== $row)
        					{
        						$scanType = trim( esc_html( $row->scan_type ) );
        						
        						//load scanner class
        						$class_name = 'tebravo_'.$scanType.'_scanner';
        						try {
        								$antivirus = new $class_name();
        						}
        						catch(Exception $e) {
        							//no print activated or not
        							if( !$noprint ){
        								echo 'Message: ' .$e->getMessage();
        							}
        						}
        							
        						
        							$antivirus->pid = $pid;
        							
        							$file_tmp = $file;
        							$data = tebravo_files::read($file_tmp);
        							
        							$exp = explode('#tebravo#', $data);
        							
        							if( is_array( $exp ) )
        							{
        								$new_data = '';
        								
        								$i=1;
        								$checked=1;
        								$infected=0;
        								$clean=0;
        								$new_data_infected = '';
        								$new_data_infected_screen = '';
        								$infected_report = '';
        								$infected_found = false;
        								#$pid_filenames = array($pid.'.txt', $pid.'_infected.txt', $pid.'_results.txt');
        								foreach ($exp as $file_to_scan)
        								{
        									$status = '';
        										if( file_exists($file_to_scan) && filesize($file_to_scan) >= $this->max_file_size )
        										{
        										//	$this->html->popup_modal(true, __("File skipped because of maximum size!", TEBRAVO_TRANS), false, true);
        											$new_data = $file_to_scan.':skipped#tebravo#';
        											$status = '<font color=red>'.__("Skipped", TEBRAVO_TRANS).'!</font>';
        										} else
        										if( $antivirus->scan( $file_to_scan ) != 1)
	        									{
	        										$new_data = $file_to_scan.':clean#tebravo#';
	        										$status = '<font color=green>'.__("Clear", TEBRAVO_TRANS).'</font>';
	        										$clean++;
	        									} else {
	        										$new_data = $file_to_scan.':infected#tebravo#';
	        										$new_data_infected = $file_to_scan.':'.$antivirus->line_number.':'.$antivirus->file_report.':infected#tebravo#';
	        										$infected_report = $antivirus->file_report;
	        										$this->is_infected=1;
	        										$infected_found = true;
	        										if( filter_var( $file_infected , FILTER_VALIDATE_URL ) ){
	        											$file_infected = str_replace("http://", "", $file_infected);
	        										} 
	        										tebravo_files::write($file_infected, $new_data_infected, true);
	        										tebravo_files::write($file_results, $new_data, true);
	        										$this->update_infected_result($new_data_infected, $pid);
	        										//outpout print audio tag to display alert sound
	        										$antivirus->display_sound( 'Checkout-Scanner-Beep.mp3' );
	        										$status = '<font color=#c3c3c3>!'.__("Suspicious", TEBRAVO_TRANS).'!</font>';
	        									}
	        									
	        									//no print activated or not
	        									if( !$noprint ){
	        										?>
        										<script>
        										setTimeout(function(){
													jQuery(".tebravo_scan_process").html(" <strong><?php echo $status;?></strong>: <?php echo str_replace("//", "/", $file_to_scan);?>");
        										},<?php echo (95+$i);?>);
        										</script>
        										<?php
        									}
        									
	        									$file_results_current_data = file_get_contents($file_results);
	        									if(false === strpos($file_results_current_data, $new_data)){
	        										tebravo_files::write($file_results, $new_data, true);
	        									}
	        									
        									
        									
        									if(false === strpos( $data, '#tebravo#'))
        									{
        										$new_data_orginal_file = str_replace($file_to_scan, '', $data);
        									} else {
        										$new_data_orginal_file = str_replace($file_to_scan.'#tebravo#', '', $data);
        									}
        									
        									
        									$results_content = file_get_contents( $file_results );
        									$infected_content = file_get_contents( $file_infected );
        									
        									$exp_results = explode("#tebravo#", $results_content);
        									$exp_infected = explode("#tebravo#", $infected_content);
        									
        									$the_cheked_files = sizeof($exp_results);
        									$the_infected_files = sizeof($exp_infected);
        									
        									if($i++ == $antivirus->max_files_to_scan){break;}
        								}
        								//write to tmp files
        								
        								
        								tebravo_files::write($file_tmp, $new_data_orginal_file);
        								
        								/*$row_checked = 0;
        								$core_checked = 0;
        								if( is_numeric($row->cheked_files)){$row_checked = $row->cheked_files;}
        								if( is_numeric($checked)){$core_checked= $checked;}
        								$total_checked = $row_checked+$core_checked;*/
        								//update results in DB
        								//updated checked & infected files
        								$wpdb->update(tebravo_utility::dbprefix()."scan_ps", 
        										array(
        										'cheked_files'=>sanitize_text_field((int)$row->cheked_files+(int)$checked),
        										'p_percent' => sanitize_text_field($percent)
        												
        								), 
        										array(
        									'pid' => $pid			
        								));
        								
        								//get data from DB to calc percent
        								$row_updated = $wpdb->get_row("SELECT cheked_files,infected_files,infected_results FROM ".
        										tebravo_utility::dbprefix()."scan_ps
        										WHERE pid='$pid' Limit 1");
        										
        										
        								//calc percent
        										$row_checked_after_updated = 0;
        										$row_total_files= 0;
        										if( is_numeric($row_updated->cheked_files)){$row_checked_after_updated = $row_updated->cheked_files;}
        										if( is_numeric($row->total_files)){$row_total_files = $row->total_files;}
        										
        										$percent_p = $row_checked_after_updated / $row_total_files;
        										$percent = floor ( ((int)$row_updated->cheked_files/(int)$row->total_files) * 100);
        										if( $percent > 100){ $percent = 100;}
        									
        										$wpdb->update(tebravo_utility::dbprefix()."scan_ps",
        												array(
        														'p_percent' => sanitize_text_field($percent)
        														
        												),
        												array(
        														'pid' => $pid
        												));
        								//handling infected files to screen
        								
        										if($row->infected_files == 0){
        											$infected_files_count = 0;
        										} else {
        											$infected_files_count = intval(floor($row_updated->infected_files-1));
        										}
        								//no print activated or not
        								if( !$noprint ){
	        								?>
	        								<script>
											jQuery("#checked").html("<?php echo $row_updated->cheked_files;?>");
											jQuery("#infected").html("<?php echo $infected_files_count;?>");
											jQuery("#scan_progress").html("<?php echo $percent;?>%");
											jQuery("#scan_progress").css("width", "<?php echo $percent;?>%");
											jQuery("#start_at").html("<?php echo tebravo_ago($row->start_at);?>");
											jQuery("#infected_results").load("<?php echo $ajax_load_infected_url.'&pid='.$pid;?>");
					        				</script>
	        								<?php 
        								}
        								
        								////////// TODO
        								/// use ini_set
        							} else {
        								//no print activated or not
        								if( !$noprint ){
	        								?>
					        				<script>
											jQuery(".tebravo_scan_process").html("<font color=red><?php echo __("PID file exit during error!", TEBRAVO_TRANS)."<br><i>".$file."</i>";?></font>");
					        				</script>
					        				<?php 
        								}
				        				exit;
        							}
        						
        					}
        					
        					
        			$out = @ob_get_contents();
        			@ob_end_clean();
        			if( !$noprint ){
        				echo $out;
        			}
        			@ob_flush();
        		}
        	
        	
        	exit;
        }
        
        //update infected results
        protected function update_infected_result( $file, $pid)
        {
        	global $wpdb;
        	
        	$this->html = new tebravo_html();
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( ); exit;}
        	
        	$update_results = true;
        	if(!empty($file)):
        	$row = $wpdb->get_row("SELECT infected_results,infected_files FROM ".
        			tebravo_utility::dbprefix()."scan_ps
        			WHERE pid='$pid' Limit 1");
        	
        			
        			if( $row->infected_results != '')
        			{
        				$infected_results = explode(",", $row->infected_results);
        				if( in_array($file, $infected_results) )
        					$update_results = false;
        			}
        			
        			if($update_results)
        			{
        				$wpdb->update(tebravo_utility::dbprefix()."scan_ps",
        						array(
        								'infected_results' => $row->infected_results.','.$file,
        						),
        						array(
        								'pid' => $pid
        						));
        				
        				$row_updated = $wpdb->get_row("SELECT infected_results FROM ".
        						tebravo_utility::dbprefix()."scan_ps
        						WHERE pid='$pid' Limit 1");
        				
        				
        						if( $row_updated->infected_results != '')
        						{
        							$infected_files = count( explode(",", $row_updated->infected_results) );
        						}
        						
        						$wpdb->update(tebravo_utility::dbprefix()."scan_ps",
        								array(
        										'infected_files' => sanitize_text_field($infected_files)
        								),
        								array(
        										'pid' => $pid
        								));
        				//break;
        			}
        	endif;
        }
        
        public function get_scanner_html_details( $scan_module )
        {
        	global $desc;
        	
        	$this->html = new tebravo_html();
        	
        	switch ($scan_module)
        	{
        		case 'scan_1':
        			$desc = "Scan for web malicious codes like e.g: shell files.";
        			$this->html->header(__("Antivirus - Malware Scanner", TEBRAVO_TRANS), $desc, 'scanner/malware_scanner.png');
        			break;
        			
        		case 'scan_2':
        			$desc = "Scan for viruses and malware files like e.g: uploaded files from infected computer.";
        			$this->html->header(__("Antivirus - PHPMussel Scanner", TEBRAVO_TRANS), $desc, 'scanner/phpmussel_scanner.png');
        			break;
        			
        		case 'scan_3':
        			$desc = "Scan for pages which marked by browsers as malware or phishing pages.";
        			$this->html->header(__("Antivirus - Google Safe Browsing Scanner", TEBRAVO_TRANS), $desc, 'scanner/google_shavar_scanner.png');
        			break;
        			
        		case 'scan_4':
        			$desc = "Check if your domain or URLs marked as spam.";
        			$this->html->header(__("Antivirus - Spam Listing Scanner", TEBRAVO_TRANS), $desc, 'scanner/spam_scanner.png');
        			break;
        			
        		case 'scan_5':
        			$desc = "Scan database to check if it contains any XSS codes via any SQL Injection bugs at your Wordpress.";
        			$this->html->header(__("Antivirus - Database Scanner", TEBRAVO_TRANS), $desc, 'scanner/db_scanner.png');
        			break;
        			
        		case 'scan_6':
        			$desc = "Scan directories and files to check if there are any changes (new, altered or deleted files).";
        			$this->html->header(__("Antivirus - File Change Scanner", TEBRAVO_TRANS), $desc, 'scanner/filechange_scanner.png');
        			break;
        	}
        }
        //malware scanner dashboard
        public function dashboard_scanner_start()
        {
        	$this->html = new tebravo_html();
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
        	
        	//retrieve scan module and details
        	$scan_module = trim( esc_html( $_GET['p'] ) );
        	$this->get_scanner_html_details( $scan_module );
        	
        	
        	$this->html->print_loading();
        	
        	if( $scan_module == 'scan_2') {tebravo_die(true,__("Sorry, PHPMussel for premium only.", TEBRAVO_TRANS),false,true); exit; } 
        	
        	if($_POST)
        	{
        		if( !empty($_POST['_nonce'])
        				&& false !== wp_verify_nonce($_POST['_nonce'], $this->html->init->security_hash.'antivirus-scan-malware'))
        		{
        			global $wpdb;
        			
        			$user = wp_get_current_user();
        			
        			if( !empty($_POST['files']) )
        			{
        				
        				$data = '';
        				//Malware and phpMussel
        				if( $scan_module == 'scan_1' || $scan_module == 'scan_2' ){
        				    foreach ($_POST['files'] as $file)
        				    {
        				        $file = esc_html( $file );
	        					if($file != ''):
		        					if( is_dir( $file ) )
		        					{
		        						$data .= $this->read_dir_zoom( $file );
		        					}
		        					
		        					$data .= $file.'#tebravo#';
		        				endif;
	        				}
        				}
        				
        				//Google Safe Brwosing
        				else if( $scan_module == 'scan_3' )
        				{
        					$data .= $this->fetch_google_shavar();
        				}
        				#var_dump($data); exit;
        				$total_files = 0;
        				$exp = explode( '#tebravo#' , $data);
        				if( is_array( $exp ) ){
        					$total_files = count( $exp ) - 1;
        				}
        				
        				$scan_type = $this->handle_scan_type($scan_module);
        				//check scan module
        				if( !is_array( $scan_type) )
        				{
        					$redirect = $this->html->init->admin_url.'-antivirus&p='.$scan_module.'&err=014';
        					exit;
        				}
        				
        				//create PID
        				$pid = md5( $scan_module.time().rand(1,50) );
        				
        				#$this->insert_new_process($pid , $scan_type[$scan_module] , false, $data);
        				//create process in database
        				$wpdb->show_errors = false;
        				$wpdb->insert(tebravo_utility::dbprefix().'scan_ps', array(
        						'pid'=>$pid,
        						'status'=>'prepare',
        						'start_at'=>time(),
        						'total_files'=>$total_files,
        						'start_by'=>$user->ID,
        						'scan_type'=>$scan_type[$scan_module],
        						'p_percent'=>'0',
        				));
	        			
        				//create process files
	        			$temp_path = TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/tmp/'.$pid.'.txt';
	        			$temp_path_startover= TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/tmp/'.$pid.'_startover.txt';
	        			tebravo_files::write($temp_path, $data);
	        			tebravo_files::write($temp_path_startover, $data);
	        			
	        			if( $scan_module == 'scan_1' || $scan_module == 'scan_2' ){
	        				$redirect = $this->html->init->admin_url.'-antivirus&p=start_scan&pid='.$pid;
	        			} else if( $scan_module == 'scan_3' ){
	        				$redirect = $this->html->init->admin_url.'-antivirus&p=safebrowsing&pid='.$pid;
	        			} else if( $scan_module == 'scan_4' ){
	        				$redirect = $this->html->init->admin_url.'-antivirus&p=spamcheck&pid='.$pid;
	        			}
        			} else {
        				$redirect = $this->html->init->admin_url.'-antivirus&p='.$scan_module.'&err=012';	
        			}
        		} else {
        			$redirect = $this->html->init->admin_url.'-antivirus&p='.$scan_module.'&err=02';	
        		}
        		echo __("Redirecting ...", TEBRAVO_TRANS);
        		tebravo_redirect_js( $redirect );
        		$this->html->footer();
        		exit;
        	}
        	
        	$output[] = "<form action='".$this->html->init->admin_url."-antivirus&p=".$scan_module."' method=post>";
        	$output[] = "<input type='hidden' name='_nonce' value='".$this->html->init->create_nonce('antivirus-scan-malware')."'>";
        	$output[] = "<div class='tebravo_block_blank' style='width:100%;'>";
        	$output[] = "<table border=0 width=100% cellspacing=0>";
        	$output[] = "<tr class='tebravo_headTD'><td><strong>".$this->retrieve_table_title( $scan_module )."</strong></td></tr>";
        	$output[] = "<tr><td style='border:solid 1px #D5DADB;' id='dirlist'>";
        	
        	//print files tree for malware and phpmussel scanners
        	$tr_hidden_style = '';
        	$scan_type = $this->handle_scan_type_options($scan_module);
        	//check scan module
        	if( !is_array( $scan_type) )
        	{
        		$redirect = $this->html->init->admin_url.'-antivirus&p='.$scan_module.'&err=014';
        		exit;
        	}
        	if( trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.$scan_type[$scan_module]) ) ) != 'checked')
        	{
        		$output[] = $this->module_disabled_dashboard($scan_type[$scan_module]);
        		$tr_hidden_style = 'display:none';
        	} else {
	        	if( $scan_module == 'scan_1' || $scan_module == 'scan_2' )
	        	{
	        		$output[] = $this->read_dir();
	        	} else if( $scan_module == 'scan_3' ){
	        		$output[] = $this->google_options_list();
	        	} else if( $scan_module == 'scan_4' ){
	        		$output[] = $this->spamcheck_scanner();
	        		$tr_hidden_style = 'display:none;';
	        	} else if( $scan_module == 'scan_5' ){
	        		$output[] = $this->db_scanner();
	        		$tr_hidden_style = 'display:none;';
	        	} else if( $scan_module == 'scan_6' ){
	        		$output[] = $this->filechange_scanner();
	        		$tr_hidden_style = 'display:none;';
	        	}
        	}
        	$output[] = "</td></tr>";
        	$output[] = "<tr class='tebravo_underTD' id='last_tr' style='$tr_hidden_style'><td>".$this->html->button_small(__("Start Scan", TEBRAVO_TRANS), 'submit', 'start_scan')."</td></tr>";
        	$output[] = "</table></div></form>";
        	
        	$output[] = "<div id='tebravo_results'></div>";
        	ob_start();
        	echo implode("\n", $output);
        	
        	$out = @ob_get_contents();
        	@ob_end_clean();
        	echo $out;
        	@ob_flush();
        	
        	?>
        	<script>
        	jQuery(document).ready(function () {
        		jQuery("#tebravo_checkall").click(function () {
        			jQuery(".tebravo_checkclass").prop('checked', jQuery(this).prop('checked'));
        	    });
        	});

        	</script>
        	<?php 
        	
        	$this->html->footer();
        }
        
        //insert new scan process
        public function insert_new_process( $pid , $scan_type , $dir=false, $data=false)
        {
        	global $wpdb;
        	
        	$user = wp_get_current_user();
        	
        	if( !$dir ){$dir = ABSPATH;}
        	
        	$tmp_dir = TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/tmp/';
        	$pid_file = $tmp_dir.'/'.$pid.'.txt';
        	$pid_file_startover = $tmp_dir.'/'.$pid.'_startover.txt';
        	
        	if( !$data )
        	{
        		$data = $this->read_dir_zoom( $dir );
        	}
        	
        	if( !file_exists( $pid_file ) )
        	{
        		tebravo_files::write( $pid_file , $data );
        	}
        	
        	if( !file_exists( $pid_file_startover) )
        	{
        		tebravo_files::write( $pid_file_startover, $data );
        	}
        	
        	$exp = explode('#tebravo#', $data);
        	$total_files = 0;
        	if( is_array( $exp ) )
        	{
        		$total_files = count( $exp );
        	}
        	
        	$wpdb->show_errors = false;
        	$wpdb->insert(tebravo_utility::dbprefix().'scan_ps', array(
        			'pid'=>sanitize_text_field($pid),
        			'status'=>'prepare',
        			'start_at'=>time(),
        			'total_files'=>sanitize_text_field($total_files),
        			'start_by'=>sanitize_text_field($user->ID),
        			'scan_type'=>sanitize_text_field($scan_type),
        			'p_percent'=>'0',
        	));
        }
        
        //retrieve table title regarding scan module
        protected function retrieve_table_title( $module )
        {
        	if( $module == 'scan_1' || $module == 'scan_2' )
        	{
        		return __("Select Directories or files", TEBRAVO_TRANS);
        	} else if ( $module == 'scan_3')
        	{
        		return __("Choose an option", TEBRAVO_TRANS);
        	} else if ( $module == 'scan_4' )
        	{
        		return __("Spam Checker", TEBRAVO_TRANS);
        	} else if( $module == 'scan_5' )
        	{
        		return __("Database Checker", TEBRAVO_TRANS);
        	} else if( $module == 'scan_6' )
        	{
        		return __("File Change Checker", TEBRAVO_TRANS);
        	}
        		
        }
        
        //fetcing google safe browsing
        protected function fetch_google_shavar()
        {
        	$this->html = new tebravo_html();
        	$output = '';
        	$url = '';
        	
        	//
        	if(! empty( $_POST['url']) )
        	{
        		$url = trim( esc_url( $_POST[ 'url' ] ) );
        	}
        	
        	if( !empty( $_POST['files'] ) )
        	{
        		$requested = trim( esc_html( $_POST['files'] ) );
        		
        		if( $requested == 'main_url' )
        		{
        			$url = (get_bloginfo( 'url' ));
        			$output .= strtok($url, "#")."#tebravo#";
        		} else if( $requested == 'all_posts' )
        		{
        			$posts = get_posts( array( 'post_status' => 'publish', 'post_type' => 'post' ) );
        			
        			foreach ($posts as $post)
        			{
        				$url = (get_permalink($post->ID)); 
        				$output .= strtok($url, "#")."#tebravo#";
        			}
        		} else if( $requested == 'all_pages' )
        		{
        			$pages = get_posts( array( 'post_status' => 'publish', 'post_type' => 'page' ) );
        			
        			foreach ($pages as $page)
        			{
        				$url = (get_permalink($page->ID));
        				$output .= strtok($url, "#")."#tebravo#";
        			}
        		} else if( $requested == 'another_url' 
        				|| ( empty( $requested ) && filter_var($url, FILTER_VALIDATE_URL))){
        					$output .= strtok($url, "#")."#tebravo#";
        		}
        	}
        	
        	return $output;
        }
        
        //print google safe browsing options list
        public function google_options_list()
        {
        	global $wpdb;
        	
        	ob_start();
        	
        	//post or page URL
        	$p_url = '';
        	$main_url_check = 'checked';
        	$custom_url_check = '';
        	if( !empty( $_GET['id'] ) )
        	{
        		$p_url = get_permalink( trim( esc_html( $_GET['id'] ) ) );
        		$custom_url_check = "checked";
        		$main_url_check = 'checked';
        	}
        	
        	$all_posts_count = wp_count_posts('post');
        	$all_pages_count = wp_count_posts('page');
        	
        	$output[] = "<table border=0 width=100% cellspacing=2>";
        	//main url
        	$output[] = "<tr class='tebravo_underTD'><td><input type='radio' name='files' $main_url_check id='request_1' value='main_url'>";
        	$output[] = "<label for='request_1'><span></span>".__("Mail URL", TEBRAVO_TRANS)."</label><br />&nbsp;&nbsp;&nbsp;
<span class='smallfont'>".get_bloginfo( 'url' )."</span></td><tr>";
        	
        	//another URL
        	$output[] = "<tr class='tebravo_underTD'><td><input type='radio' name='files' $custom_url_check id='request_4' value='another_url'>";
        	$output[] = "<label for='request_4'><span></span>".__("Another URL", TEBRAVO_TRANS)."</label><br />&nbsp;&nbsp;&nbsp;
<input type='text' name='url' placeholder='http://' style='width:350px' value='$p_url'></td><tr>";
        	$output[] = "</table>";
        	
        	return implode("\n", $output);
        }
        
        //hanlde scan type
        //convert scan_n to scan module name
        //convert scan module name to scan_n
        protected function handle_scan_type( $scan_type )
        {
        	if( substr($scan_type,0,5) == 'scan_')
        	{
        		$scan_module = array(
        				"scan_1" => "malware"	,
        				"scan_2" => "phpmussel"	,
        				"scan_3" => "googshavar"	,
        				"scan_4" => "spamcheck"	,
        				"scan_5" => "dbscanner"	,
        				"scan_6" => "filechange"	,
        		);
        	} else {
        		$scan_module= array(
	        			"malware" => "scan_1"	,
	        			"phpmussel" => "scan_2"	,
	        			"googshavar" => "scan_3"	,
	        			"spamcheck" => "scan_4"	,
	        			"dbscanner" => "scan_5"	,
	        			"filechange" => "scan_6"	,
	        	);
        	}
        	
        	return $scan_module;
        }
        
        //hanlde scan type for plugin options
        //convert scan_n to scan module name
        protected function handle_scan_type_options( $scan_type )
        {
        	if( substr($scan_type,0,5) == 'scan_')
        	{
        		$scan_module = array(
        				"scan_1" => "antimalware"	,
        				"scan_2" => "phpmussel"	,
        				"scan_3" => "googshaver"	,
        				"scan_4" => "domainspam"	,
        				"scan_5" => "dbscanner"	,
        				"scan_6" => "filechange"	,
        		);
        	}
        	
        	return $scan_module;
        }
        
        //spam list checker
        protected function spamcheck_scanner()
        {
        	global $wpdb;
        	
        	$this->html = new tebravo_html();
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
        	
        	ob_start();
        	
        	$user = wp_get_current_user();
        	
        	$antivirus = new tebravo_spamcheck_scanner();
        	
        	$pid = md5( time().rand(1,50) );
        	$wpdb->show_errors = false;
        	$wpdb->insert(tebravo_utility::dbprefix().'scan_ps', array(
        			'pid'=>sanitize_text_field($pid),
        			'status'=>'prepare',
        			'start_at'=>time(),
        			'total_files'=>sanitize_text_field(count( $antivirus->servers_list() )),
        			'start_by'=>sanitize_text_field($user->ID),
        			'scan_type'=>'spamcheck',
        			'p_percent'=>'0',
        	));
        	$antivirus->pid = $pid;
        	
        	$ajax_url = add_query_arg(
        			array(
        					'action' => 'spamlist_checker',
        					'_nonce' => wp_create_nonce( 'tebravo_spamlist_checker' )
        			)
        			, admin_url('admin-ajax.php'));
        	
        	
        	$output = "<table border=0 width=100% cellspacing=0>";
        	$output .= "<tr class='tebravo_headTD'><td width=20%>".__("Domain Name", TEBRAVO_TRANS)."</td><td> ".$antivirus->domain."</td></tr>";
        	$output .= "<tr class='tebravo_headTD'><td width=20%>".__("IP Address", TEBRAVO_TRANS)."</td><td> ".$antivirus->ipaddress."</td></tr>";
        	$output .= "<tr class='tebravo_headTD'><td width=20%>".__("Result", TEBRAVO_TRANS)."</td><td id='scanner_ajax_result'> <i>".__("Loading ...", TEBRAVO_TRANS)."</i></td></tr>";
        	$output .= "</table>";
        	$output .= "<div id='scanner_ajax' style='padding:4px'><i>".__("Loading ...", TEBRAVO_TRANS)."</i></div>".PHP_EOL;
        	$output .= "<script>".PHP_EOL;
        	$output .= 'jQuery(".tebravo_loading").show();'.PHP_EOL;
        	$output .= 'jQuery("#scanner_ajax").load("'.$ajax_url.'&pid='.$pid.'");'.PHP_EOL;
        	$output .= '</script>'.PHP_EOL;
        	
        	
        	return $output;
        }
        
        //Database checker
        protected function db_scanner(  )
        {
        	global $wpdb;
        	
        	$this->html = new tebravo_html();
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
        	
        	ob_start();
        	
        	$user = wp_get_current_user();
        	
        	$this->html = new tebravo_html();
        	$antivirus = new tebravo_db_scanner();
        	
        	if( !empty( $_GET['pid'] ) )
        	{
        		$pid = trim( esc_html( $_GET['pid'] ) );
        		$no_update = 1;
        	} else {
	        	$pid = md5( time().rand(1,50) );
	        	$wpdb->show_errors = false;
	        	$wpdb->insert(tebravo_utility::dbprefix().'scan_ps', array(
	        			'pid'=>sanitize_text_field($pid),
	        			'status'=>'prepare',
	        			'start_at'=>time(),
	        			'total_files'=>sanitize_text_field( $antivirus->all_rows ),
	        			'start_by'=>sanitize_text_field($user->ID),
	        			'scan_type'=>'dbscanner',
	        			'p_percent'=>'0',
	        	));
	        	$no_update = 0;
        	}
        	$antivirus->pid = $pid;
        	
        	$ajax_url = add_query_arg(
        			array(
        					'action' => 'dbscan_checker',
        					'_nonce' => wp_create_nonce( 'tebravo_dbscan_checker' ),
        			    'blog_id' => isset($_GET['blog_id'])?esc_html($_GET['blog_id']):'',
        			)
        			, admin_url('admin-ajax.php'));
        	
        	$posts = $antivirus->posts_count;
        	$pages = $antivirus->pages_count;
        	
        	$pid_url_href = $this->html->init->admin_url.'-antivirus&p=scan_5&pid='.$pid;
        	$pid_url = "<a href='".$pid_url_href."' target=_blank>".$pid."</a>";
        	
        	$output = "<table border=0 width=100% cellspacing=0>";
        	$output .= "<tr class='tebravo_headTD'><td width=20%>".__("Posts", TEBRAVO_TRANS)."</td><td> ".$posts."</td></tr>";
        	$output .= "<tr class='tebravo_headTD'><td width=20%>".__("Pages", TEBRAVO_TRANS)."</td><td> ".$pages."</td></tr>";
        	$output .= "<tr class='tebravo_headTD'><td width=20%>".__("Result", TEBRAVO_TRANS)."</td><td id='scanner_ajax_result'> <i>".__("Loading ...", TEBRAVO_TRANS)."</i></td></tr>";
        	$output .= "<tr class='tebravo_headTD'><td width=20%>".__("PID", TEBRAVO_TRANS)."</td><td id='scanner_ajax_result'> <i>".$pid_url."</i></td></tr>";
        	$output .= "</table>";
        	$output .= "<div id='scanner_ajax' style='padding:4px'><i>".__("Loading ...", TEBRAVO_TRANS)."</i></div>".PHP_EOL;
        	$output .= "<script>".PHP_EOL;
        	$output .= 'jQuery(".tebravo_loading").show();'.PHP_EOL;
        	$output .= 'jQuery("#scanner_ajax").load("'.$ajax_url.'&pid='.$pid.'&no_update='.$no_update.'");'.PHP_EOL;
        	$output .= '</script>'.PHP_EOL;
        	
        	
        	return $output;
        }
        
        //Filechange checker
        protected function filechange_scanner()
        {
        	global $wpdb;
        	
        	$this->html = new tebravo_html();
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
        	
        	ob_start();
        	$this->html = new tebravo_html();
        	
        	$user = wp_get_current_user();
        	
        	$antivirus = new tebravo_filechange_scanner();
        	
        	
        	if( empty( $_GET['pid'] ) )
        	{
        		$pid = md5( time() );
        		
	        	$wpdb->show_errors = false;
	        	$wpdb->insert(tebravo_utility::dbprefix().'scan_ps', array(
	        			'pid'=>sanitize_text_field($pid),
	        			'status'=>'prepare',
	        			'start_at'=>time(),
	        			'total_files'=>sanitize_text_field(count( $antivirus->all_files )),
	        			'start_by'=>sanitize_text_field($user->ID),
	        			'scan_type'=>'filechange',
	        			'p_percent'=>'0',
	        	));
	        	$antivirus->no_update = false;
        	} else {
        		$pid = trim( esc_html( $_GET['pid'] ) );
        		$antivirus->no_update = true;
        	}
        	
        	$antivirus->pid = $pid;
        	
        	$ajax_url = add_query_arg(
        			array(
        					'action' => 'filechange_checker',
        					'_nonce' => wp_create_nonce( 'tebravo_filechange_checker' )
        			)
        			, admin_url('admin-ajax.php'));
        	
        	//PID link
        	$pid_link = $this->html->init->admin_url.'-antivirus&p=scan_6&pid='.$pid;
        	$pid_url = "<a href='".$pid_link."'>".$pid."</a>";
        	
        	$output = "<table border=0 width=100% cellspacing=0>";
        	$output .= "<tr class='tebravo_headTD'><td width=20%>".__("Directory", TEBRAVO_TRANS)."</td><td> ".ABSPATH."</td></tr>";
        	$output .= "<tr class='tebravo_headTD'><td width=20%>".__("Result", TEBRAVO_TRANS)."</td><td id='scanner_ajax_result'> <i>".__("Loading ...", TEBRAVO_TRANS)."</i></td></tr>";
        	$output .= "<tr class='tebravo_headTD'><td width=20%>".__("PID", TEBRAVO_TRANS)."</td><td id='scanner_ajax_result'> $pid_url</td></tr>";
        	$output .= "</table>";
        	$output .= "<div id='scanner_ajax' style='padding:4px'><i>".__("Loading ...", TEBRAVO_TRANS)."</i></div>".PHP_EOL;
        	$output .= "<script>".PHP_EOL;
        	$output .= 'jQuery(".tebravo_loading").show();'.PHP_EOL;
        	$output .= 'jQuery("#scanner_ajax").load("'.$ajax_url.'&pid='.$pid.'&no_update='.$antivirus->no_update.'");'.PHP_EOL;
        	$output .= '</script>'.PHP_EOL;
        	
        	
        	return $output;
        }
        
        //spam checker for ajax
        public function spamlist_checker()
        {
        	$this->html = new tebravo_html();
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( ); exit;}
        	
        	if( !empty( $_GET['_nonce'] ) 
        			&& false !== wp_verify_nonce($_GET['_nonce'], 'tebravo_spamlist_checker')
        			&& !empty( $_GET['pid'] ) )
			{
        		$antivirus = new tebravo_spamcheck_scanner();
        		$antivirus->pid = trim( esc_html( $_GET['pid'] ) );
        		$antivirus->scan();
				
			}
			
			exit;
        }
        
        //database checker for ajax
        public function dbscan_checker()
        {
        	$this->html = new tebravo_html();
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die(  ); exit;}
        	
        	if( !empty( $_GET['_nonce'] )
        			&& false !== wp_verify_nonce($_GET['_nonce'], 'tebravo_dbscan_checker')
        			&& !empty( $_GET['pid'] ) )
        	{
        		$antivirus = new tebravo_db_scanner();
        		$antivirus->pid = trim( esc_html( $_GET['pid'] ) );
        		$antivirus->no_update = 0;
        		
        		if( !empty( $_GET['no_update'] ) )
        		{
        			$antivirus->no_update = trim( esc_html( $_GET['no_update'] ) );
        		}
        		
        		if( $antivirus->no_update == 1 )
        		{
        			$antivirus->get_results( $antivirus->pid );
        			
        		} else {
        			$antivirus->scan( true );
        		}
        		
        	}
        	
        	exit;
        }
        
        //file change checker for ajax
        public function filechange_checker()
        {
        	global $wpdb;
        	
        	$this->html = new tebravo_html();
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
        	
        	if( !empty( $_GET['_nonce'] )
        			&& false !== wp_verify_nonce($_GET['_nonce'], 'tebravo_filechange_checker')
        			&& !empty( $_GET['pid'] ) )
        	{
        		$antivirus = new tebravo_filechange_scanner();
        		$antivirus->pid = trim( esc_html( $_GET['pid'] ) );
        		
        		//before scan // choose no_update choice
        		if( !empty( $_GET['no_update'] ) )
        		{
        			$antivirus->no_update = trim( esc_html( $_GET['no_update'] ) );
        		}
        		//start real scan
        		$antivirus->scan( ABSPATH );
        		
        		
        		$query = $wpdb->get_row( "SELECT * FROM ".tebravo_utility::dbprefix()."scan_ps WHERE pid = '".$antivirus->pid."'" );
        		
        				if( $query->status == 'finished' )
        				{
        						$exp = explode('[altered]', $query->infected_results );
        						$altered_exp = explode('[added]', $exp[1]);
        						$altered = json_decode($altered_exp[0], true);
        						
        						$added_exp = explode('[deleted]', $altered_exp[1]);
        						$added = json_decode($added_exp[0], true);
        					
        						$deleted = json_decode($added_exp[1], true);
        						
        						$output[] = "<table border=0 width=100% cellspacing=0>";
        						$output[] = "<tr class='tebravo_headTD'>";
        						$output[] = "<td width=60%><strong>".__("File", TEBRAVO_TRANS)."</strong></td>";
        						$output[] = "<td width=10%><strong>".__("Size", TEBRAVO_TRANS)."</strong></td>";
        						$output[] = "<td width=10%><strong>".__("Past Modify", TEBRAVO_TRANS)."</strong></td>";
        						$output[] = "<td width=10%><strong>".__("Last Modify", TEBRAVO_TRANS)."</strong></td>";
        						$output[] = "<td width=10%><strong>".__("Scan", TEBRAVO_TRANS)."</strong></td>";
        						$output[] = "</tr>";
        						$output[] = "<tr class=''><td colspan=5><strong><u>".__("New Files", TEBRAVO_TRANS)."</u></strong></td></tr>";
        						if( count( $added ) > 0)
        						{
        							$output[] = $antivirus->fetch_results($added, $antivirus->oldStoredData, $antivirus->newStoredData, 'new');
        						} else {
        							$output[] = "<tr class='tebravo_underTD'><td colspan=5>".__("Clear", TEBRAVO_TRANS)."</td></tr>";
        						}
        						$output[] = "<tr class=''><td colspan=5><strong><u>".__("Changed Files", TEBRAVO_TRANS)."</u></strong></td></tr>";
        						if( count( $altered ) > 0)
        						{
        							$output[] = $antivirus->fetch_results($altered, $antivirus->oldStoredData, $antivirus->newStoredData, 'altered');
        						} else {
        							$output[] = "<tr class='tebravo_underTD'><td colspan=5>".__("Clear", TEBRAVO_TRANS)."</td></tr>";
        						}
        						$output[] = "<tr class=''><td colspan=5><strong><u>".__("Deleted Files", TEBRAVO_TRANS)."</u></strong></td></tr>";
        						if( count( $deleted ) > 0)
        						{
        							$output[] = $antivirus->fetch_results($deleted, $antivirus->oldStoredData, $antivirus->newStoredData, 'deleted');
        						} else {
        							$output[] = "<tr class='tebravo_underTD'><td colspan=5>".__("Clear", TEBRAVO_TRANS)."</td></tr>";
        						}
        						
        						$output[] = "</table>";
        						
        						if( $antivirus->no_update != 1)
        						{
        							$result = floor( $antivirus->report_countfiles );
        						} else {
        							$result = floor( $query->infected_files );
        						}
        						echo implode("\n", $output);
        					
        				} else {
        					$result = __("Checking ...", TEBRAVO_TRANS);
        					echo __("Checking ...", TEBRAVO_TRANS)." <img src='".plugins_url('/assets/img/loading.gif', TEBRAVO_PATH)."'>";	
        				}
        				
        						?>
					        	<script>
					        	jQuery(".tebravo_loading").hide();
								jQuery("#scanner_ajax_result").html('<?php echo $result;?>');
					        	</script>
					        	<?php
        	}
        	
        	
        	exit;
        }
        
        //start scan dashboard
        public function dashboard_start_scan()
        {
        	global $wpdb;
        	
        	ob_start();
        	
        	$this->html = new tebravo_html();
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
        	
        	//check PID
        	if( empty ($_GET['pid'] ) )
        	{
        		tebravo_redirect_js($this->html->init->admin_url.'-antivirus&p=scan');
        		exit;
        	}
        	
        	$pid = trim( esc_html( $_GET['pid'] ) );
        	$row = $wpdb->get_row("SELECT total_files,scan_type,start_at,status FROM ".
        			tebravo_utility::dbprefix()."scan_ps
					WHERE pid='$pid' Limit 1");

        			//display actions on scan progress
        			if( !empty($_GET['new_action']))
        			{
        				$new_action = trim( esc_html( $_GET['new_action'] ) );
        				
        				if( $new_action == 'stop' && $row->status != 'stopped')
        				{
        					$wpdb->update(tebravo_utility::dbprefix()."scan_ps",
        							array( "status" => 'stopped')
        							, array( "pid" => $pid));
        					
        					tebravo_redirect_js($this->html->init->admin_url.'-antivirus&p=start_scan&pid='.$pid);
        					exit;
        				} else if( $new_action == 'resume' && $row->status != 'running')
        				{
        					$wpdb->update(tebravo_utility::dbprefix()."scan_ps",
        							array( "status" => 'running')
        							, array( "pid" => $pid));
        					
        					tebravo_redirect_js($this->html->init->admin_url.'-antivirus&p=start_scan&pid='.$pid);
        					exit;
        				} else if( $new_action == 'startover')
        				{
        					$tmp_dir = TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/tmp/';
        					$file_process = $tmp_dir.$pid.'.txt';
        					$file_startover = $tmp_dir.$pid.'_startover.txt';
        					$file_results = $tmp_dir.$pid.'_results.txt';
        					$file_infected = $tmp_dir.$pid.'_infected.txt';
        					//check if file exists
        					if( !file_exists( $file_startover ) )
        					{
        						tebravo_redirect_js($this->html->init->admin_url.'-antivirus&p=start_scan&pid='.$pid.'&err=015');
        						exit;
        					}
        					
        					$file_data = tebravo_files::read($file_startover);
        					
        					//write new data
        					tebravo_files::write($file_process, $file_data);
        					tebravo_files::write($file_results, '');
        					tebravo_files::write($file_infected, '');
        					
        					$wpdb->update(tebravo_utility::dbprefix()."scan_ps",
        							array( "status" => 'running', "cheked_files" => "0", "infected_files" => "0", "infected_results" => "")
        							, array( "pid" => $pid));
        					
        					tebravo_redirect_js($this->html->init->admin_url.'-antivirus&p=start_scan&pid='.$pid);
        					exit;
        				} else if( $new_action == 'take_action')
        				{
        					$this->take_action_dashboard( $pid );
        					exit;
        				} else if( $new_action == 'delete')
        				{
        					if( !empty($_GET['_nonce'])
        							&& false !== wp_verify_nonce($_GET['_nonce'], $this->html->init->security_hash.'take-action-infected-files'))
        					{
        						$file = $f_hash = tebravo_decodeString(esc_html($_GET['f']), $this->html->init->security_hash);
        						if( file_exists( $file ) )
        						{
        							@unlink( $file );
        							$backurl = $this->html->init->admin_url.'-antivirus&p=start_scan&pid='.$pid.'&new_action=take_action&msg=05';
        							if( !empty( $_GET['back']) )
        							{
        								if( $pid == 'safefiles' )
        								{
        									$backurl = $this->html->init->admin_url.'-antivirus&p=safefiles&msg=05';
        								} else if( $pid == 'quarantine'){
        									$backurl = $this->html->init->admin_url.'-antivirus&p=quarantine&msg=05';
        								}
        								
        							}
        							tebravo_redirect_js( $backurl );
        							
        						} else {
        							tebravo_redirect_js($this->html->init->admin_url.'-antivirus&p=start_scan&pid='.$pid.'&err=013');
        							
        						}
        					} else {
        						tebravo_redirect_js($this->html->init->admin_url.'-antivirus&p=start_scan&pid='.$pid.'&err=02');
        						
        					}
        					
        					exit;
        				}  else if( $new_action == 'safe')
        				{
        					if( !empty($_GET['_nonce'])
        							&& false !== wp_verify_nonce($_GET['_nonce'], $this->html->init->security_hash.'take-action-infected-files'))
        					{
        						$file = tebravo_decodeString($_GET['f'], $this->html->init->security_hash);
        						$f_hash[] = $file;
        						if( file_exists( $file ) )
        						{
        							$db_list = trim( esc_html( tebravo_utility::get_option (TEBRAVO_DBPREFIX.'safe_files')));
        							if( !empty($db_list) )
        							{
        								$files_list = ( $db_list );
        							} else {
        								$files_list = '';
        							}
        							
        							$new_safe_list = $files_list.','.$file;
        							//update safe list
        							tebravo_utility::update_option(TEBRAVO_DBPREFIX.'safe_files' , sanitize_text_field( $new_safe_list ));
        							
        							$backurl = $this->html->init->admin_url.'-antivirus&p=start_scan&pid='.$pid.'&new_action=take_action&msg=05';
        							if( !empty( $_GET['back']) )
        							{
        								if( $pid == 'quarantine'):
	        								if( tebravo_files::file_perms( $file ) != '0644' )
	        								{
	        									tebravo_files::dochmod($file, '0644');
	        								}
        								endif;
        								$backurl = $this->html->init->admin_url.'-antivirus&p=quarantine&msg=05';
        							}
        							tebravo_redirect_js($backurl);
        							
        						} else {
        							tebravo_redirect_js($this->html->init->admin_url.'-antivirus&p=start_scan&pid='.$pid.'&err=013');
        							
        						}
        					} else {
        						tebravo_redirect_js($this->html->init->admin_url.'-antivirus&p=start_scan&pid='.$pid.'&err=02');
        						
        					}
        					
        					exit;
        				}
        				
        				
        			}
        			
        			//start scan progress
        			if(null !== $row)
        			{
        				$total_files = $row->total_files;	
        				$scan_type = $row->scan_type;	
        				
        				$started_at = tebravo_ago($row->start_at);
        				
        				//Ajax Scan URL
        				$ajax_scan = add_query_arg(array(
								'action' => 'scan_files',
        						'_nonce' => wp_create_nonce('scan_files')
        				), admin_url('admin-ajax.php'));

        				//Ajax Stop URL
        				$ajax_stop_url = add_query_arg(array(
        				'action' => 'scan_files_stop',
        				'_nonce' => wp_create_nonce('scan_files_stop')
        				), admin_url('admin-ajax.php'));
        				
        				//Ajax Resume URL
        				$ajax_resume_url= add_query_arg(array(
        				'action' => 'scan_files_resume',
        				'_nonce' => wp_create_nonce('scan_files_resume')
        				), admin_url('admin-ajax.php'));
        				
        				//Ajax Start Over URL
        				$ajax_start_over_url= add_query_arg(array(
        				'action' => 'scan_files_startover',
        				'_nonce' => wp_create_nonce('scan_files_startover')
        				), admin_url('admin-ajax.php'));
        				
        				$checked_files = '';
        				$infected_files = '';
        				
        				$scanType = esc_html(trim($row->scan_type));
        				$scan_type = $this->handle_scan_type($scanType);
        				
        				//check if is valid scan type
        				if( !is_array( $scan_type ) )
        				{
        					tebravo_redirect_js($this->html->init->admin_url.'-antivirus&p=scan&err=014');
        					wp_die(); exit;
        				}
        				
        				$scan_module = trim( esc_html( $scan_type[$row->scan_type] ) );
        				$this->get_scanner_html_details( $scan_module );
        				
        				//load scanner class
        				$class_name = 'tebravo_'.$scanType.'_scanner';
        				try {
        					$antivirus = new $class_name();
        				}
        				catch(Exception $e) {
        					echo 'Message: ' .$e->getMessage();
        				}
        				
        				$this->html->print_loading();
        				
        				$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
        				$output[] = "<table border=0 width=100% cellspacing=0>";
        				
        				if($row->status != 'stopped')
        				{
        					$scan_status = '<font color=blue>'.__("Preparing ...", TEBRAVO_TRANS).'</font>';
        				} else {
        					$scan_status = '<font color=green><strong>'.__("Stopped!", TEBRAVO_TRANS).'</strong></font>';
        				}
        				
        				$output[] = "<tr class='tebravo_underTD'><td colspan=2><strong>".__("Total Files:", TEBRAVO_TRANS)."</strong> ".$total_files."</td></tr>";
        				$output[] = "<tr class='tebravo_underTD'><td colspan=2><strong>".__("Started:", TEBRAVO_TRANS)."</strong> <span id='start_at'>".$started_at."</span> ".__("ago", TEBRAVO_TRANS)."</td></tr>";
        				$output[] = "<tr><td colspan=2><div class='tebravo_scan_process'> $scan_status </div></td></tr>";
        				$output[] = "<tr><td colspan=2><div class='tebravo_scan_bar'><div class='progress' id='scan_progress'>0%</div></div></td></tr>";
        				$output[] = "<tr><td colspan=2><hr><h3>".__("Scan Results", TEBRAVO_TRANS)."</h3></td></tr>";
        				$output[] = "<tr class='tebravo_underTD'><td width=25%><strong>".__("Checked:", TEBRAVO_TRANS)."</strong> <span id='checked'>".$checked_files."</span></td>";
        				$output[] = "<td><strong>".__("Affected:", TEBRAVO_TRANS)."</strong> <span id='infected'>".floor($infected_files)."</span></td></tr>";
        				
        				$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Affected Files List", TEBRAVO_TRANS)."</strong></td></tr>";
        				$output[] = "<tr class='tebravo_underTD'><td colspan=2><div class='tebravo_scan_results' id='infected_results'></div></td></tr>";
        				$output[] = "<tr class='tebravo_underTD'><td colspan=2>";
        				
        				$output[] = $this->html->button_small_alert(__("Stop", TEBRAVO_TRANS), "button", "stop");
        				$output[] = $this->html->button_small_info(__("Resume", TEBRAVO_TRANS), "button", "resume");
        				
        				$output[] = $this->html->button_small(__("Start Over", TEBRAVO_TRANS), "button", "start_over");
        				$output[] = $this->html->button_small(__("Take Action", TEBRAVO_TRANS), "button", "take_action", false, "computer_settings-16.png");
        				$output[] = "</td></tr>";
        				$output[] = "</table></div>";
        				
        				$output[] = "<div id='tebravo_results'></div>";
        				$output[] = "<div id='tebravo_change_status'></div>";
        				
        				echo implode("\n", $output);
        				
        				echo "<script>";
        				echo "jQuery('#take_action').hide();";
        				echo "jQuery('#take_action').click(function(){";
        				echo "var tebravo_action_now='hold';";
        				echo "jQuery('.tebravo_loading').show();";
        				echo "window.location.href= '".$this->html->init->admin_url."-antivirus&p=start_scan&pid=$pid&new_action=take_action';";
        				echo "});";
        				
        				echo "jQuery('#stop').click(function(){";
        				echo "var tebravo_action_now='hold';";
        				echo "jQuery('.tebravo_loading').show();";
        				echo "window.location.href= '".$this->html->init->admin_url."-antivirus&p=start_scan&pid=$pid&new_action=stop';";
        				#echo "jQuery('#tebravo_change_status').load('".$ajax_stop_url."&pid=".$pid."');";
        				echo "});";
        				echo "jQuery('#resume').click(function(){";
        				echo "var tebravo_action_now='hold';";
        				echo "jQuery('.tebravo_loading').show();";
        				echo "window.location.href= '".$this->html->init->admin_url."-antivirus&p=start_scan&pid=$pid&new_action=resume';";
        				#echo "jQuery('#tebravo_change_status').load('".$ajax_resume_url."&pid=".$pid."');";
        				echo "});";
        				echo "jQuery('#start_over').click(function(){";
        				echo "var tebravo_action_now='hold';";
        				echo "jQuery('.tebravo_loading').show();";
        				echo "window.location.href= '".$this->html->init->admin_url."-antivirus&p=start_scan&pid=$pid&new_action=startover';";
        				#echo "jQuery('#tebravo_change_status').load('".$ajax_start_over_url."&pid=".$pid."');";
        				echo "});";
        				
        				echo "var time_to_load_tebravo=".$antivirus->timer_ajax.";";
        				echo "if(tebravo_action_now === undefined){";
        				echo "var tebravo_action_now='go';";
        				echo "}";
        				
        				echo "jQuery('.tebravo_block_blank').ready(function(){";
        				echo "setInterval(function(){";
        				echo "jQuery('#tebravo_results').load('".$ajax_scan."&pid=".$pid."&tebravo_action='+tebravo_action_now+'');";
        				echo "},time_to_load_tebravo);";
        				echo "});";
        				echo "</script>";
        				
        				$out = @ob_get_contents();
        				@ob_end_clean();
        				echo $out;
        				@ob_flush();
        				
        				$this->html->footer();
        				
        			} else {
        				
        				tebravo_redirect_js($this->html->init->admin_url.'-antivirus&p=scan');
        				exit;
        			}
        }
        
        //take action on infected files //dashboard
        protected function take_action_dashboard( $pid )
        {
        	global $wpdb;
        	
        	$this->html = new tebravo_html();
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
        	
        	$pid = esc_html( $pid );
        	$exit = false;
        	
        	$row = $wpdb->get_row("SELECT infected_results,scan_type FROM ".
        			tebravo_utility::dbprefix()."scan_ps 
					WHERE pid='$pid' Limit 1");
        	
        			if( null !== $row )
        			{
        				$desc = __("Affected Results", TEBRAVO_TRANS);
        				$desc .= "<br /><b>".__("PID", TEBRAVO_TRANS).":</b> ".$pid;
        				$scan_module = $this->handle_scan_type( $row->scan_type );
        				$this->get_scanner_html_details( $scan_module[$row->scan_type]);
        				$this->html->print_loading();
        				
        				$scanType = trim( esc_html( $row->scan_type ) );
        				//load scanner class
        				$class_name = 'tebravo_'.$scanType.'_scanner';
        				try {
        					$antivirus = new $class_name();
        				}
        				catch(Exception $e) {
        					echo 'Message: ' .$e->getMessage();
        				}
        				
        					$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
        					$output[] = "<table border=0 width=100% cellspacing=0>";
        					
        					$status = '';
        					$nonce = $this->html->init->create_nonce('take-action-infected-files');
        					
        					if($row->infected_results != ''){
        					$infected_results = explode(",", $row->infected_results);
        					if( is_array( $infected_results ) ){
	        					foreach ( $infected_results as $file )
	        					{
	        						if( !empty($file) ){
		        						$file_name = $antivirus->create_report_mini($file, 'file_name');
		        						$file_report = $antivirus->create_report_mini($file, 'file_report');
		        						
		        						$file_name = str_replace("//", "/", $file_name);
		        						$f_hash = tebravo_encodeString($file_name, $this->html->init->security_hash);
		        						
		        						if( !file_exists( $file_name ))
		        						{
		        							$status = __("Removed", TEBRAVO_TRANS);
		        						} else {$status = '';}
		        						
		        						$actions = "<a href='".$this->html->init->admin_url."-antivirus&p=start_scan&pid=".$pid."&new_action=delete&_nonce=".$nonce."&f=".$f_hash."'>".__("Remove", TEBRAVO_TRANS)."</a>";
		        						
		        						if(!$antivirus->check_safe_list( $file_name )){
		        							$actions .= " | <a href='".$this->html->init->admin_url."-antivirus&p=start_scan&pid=".$pid."&new_action=safe&_nonce=".$nonce."&f=".$f_hash."'>".__("Mark as Safe", TEBRAVO_TRANS)."</a>";
		        						}
		        						
		        						$output[] = "<tr class='tebravo_underTD'><td><strong>".basename( $file_name )."</strong><br />";
		        						$output[] = "<span class='smallfont'>".$file_name."</span><br /><u>".__('Report', TEBRAVO_TRANS)."</u>: ".$file_report."</td>";
		        						$output[] = "<td width=7%><i><font color=green>".$status."</font></i></td>";
		        						$output[] = "<td width=18%>".$actions."</td>";
	        						}
	        					}
        					}
        					} else {
        						$output[] = "<tr class='tebravo_underTD'><td> ** ".__("No problems found.", TEBRAVO_TRANS)."</td></tr>";
        					}
        					$output[] = "</table></div>";
        					
        					echo implode("\n", $output);
        					
        					$this->html->footer();
        				
        			} else {
        				$exit = true;
        			}
        			
        			//die
        			if( $exit )
        			{
        				tebravo_redirect_js($this->html->init->admin_url.'-antivirus&p=scan');
        				die();
        			}
        }
        	
        //scanners list dashboard
        public function dashboard_scanner()
        {
        	$this->html = new tebravo_html();
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
        	
        	$desc = __("Choose the scanner you need to use. You can scan multiple times with different scanners.", TEBRAVO_TRANS);
        	$this->html->header(__("Antivirus - Scan Files", TEBRAVO_TRANS), $desc, 'antivirus.png');
        	
        	$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
        	$output[] = "<table border=0 width=100% cellspacing=0>";
        	$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Choose Scanner")."</strong></td></tr>";
        	$scanners = 0;
        	//Malware Scanner
        	if( ( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'antimalware') ) ) == 'checked' ){
        		$scanners = 1;
	        	$output[] = "<tr class='tebravo_underTD'><td width=68 style='margin:0 auto;'><img src='".plugins_url('assets/img/scanner/malware_scanner.png', TEBRAVO_PATH)."'></td>";
	        	$output[] = "<td><strong><a href='".$this->html->init->admin_url."-antivirus&p=scan_1'>".__("Malware Scanner")."</a></strong><br />";
	        	$output[] = __("Scan for web malicious codes like e.g: shell files.", TEBRAVO_TRANS);
	        	$output[] = "</td><tr>";
        	}
        	//PHPMussel Scanner
        	if( ( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'phpmussel') ) ) == 'checked' ){
        		$scanners = 1;
	        	$output[] = "<tr class='tebravo_underTD'><td width=68 style='margin:0 auto;'><img src='".plugins_url('assets/img/scanner/phpmussel_scanner.png', TEBRAVO_PATH)."'></td>";
	        	$output[] = "<td><strong>".__("PHPMussel Scanner")."</strong> <span class='tebravo_trail'>".__("Premium", TEBRAVO_TRANS)."</span><br />";
	        	$output[] = __("Scan for viruses and malware files like e.g: uploaded files from affected computer.", TEBRAVO_TRANS);
	        	$output[] = "</td><tr>";
        	}
        	//Google Shavar Scanner
        	if( ( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'googshaver') ) ) == 'checked' ){
        		$scanners = 1;
	        	$output[] = "<tr class='tebravo_underTD'><td width=68 style='margin:0 auto;'><img src='".plugins_url('assets/img/scanner/google_shavar_scanner.png', TEBRAVO_PATH)."'></td>";
	        	$output[] = "<td><strong><a href='".$this->html->init->admin_url."-antivirus&p=scan_3'>".__("Google Safe Browsing Scanner")."</a></strong><br />";
	        	$output[] = __("Scan for pages which marked by browsers as malware or phishing pages.", TEBRAVO_TRANS);
	        	$output[] = "</td><tr>";
        	}
        	//Spamlist Scanner
        	if( ( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'domainspam') ) ) == 'checked' ){
        		$scanners = 1;
	        	$output[] = "<tr class='tebravo_underTD'><td width=68 style='margin:0 auto;'><img src='".plugins_url('assets/img/scanner/spam_scanner.png', TEBRAVO_PATH)."'></td>";
	        	$output[] = "<td><strong><a href='".$this->html->init->admin_url."-antivirus&p=scan_4'>".__("Spam Listing Scanner")."</a></strong><br />";
	        	$output[] = __("Check if your domain or URLs marked as spam.", TEBRAVO_TRANS);
	        	$output[] = "</td><tr>";
        	}
        	//DB Scanner
        	if( ( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'dbscanner') ) ) == 'checked' ){
        		$scanners = 1;
	        	$output[] = "<tr class='tebravo_underTD'><td width=68 style='margin:0 auto;'><img src='".plugins_url('assets/img/scanner/db_scanner.png', TEBRAVO_PATH)."'></td>";
	        	if( function_exists('is_multisite') && is_multisite())
	        	{
	        		$output[] = "<td><strong>".__("Database Scanner")."</strong><br />";
	        		$sites = tebravo_utility::get_sites();
	        		if( is_array( $sites ) )
	        		{
	        			$scan = '';
	        			foreach ( $sites as $site )
	        			{
	        				$scan .= " &nbsp;&nbsp; -".__("Network Site", TEBRAVO_TRANS)."[".$site->blog_id."] <a href='".$this->html->init->admin_url."-antivirus&p=scan_5&blog_id=".$site->blog_id."'>";
	        				$blog_details = tebravo_utility::get_bloginfo( 'blogname' );
	        				
	        				$scan .= $blog_details."</a><br />"; 
	        			}
	        		}
	        		$output[] = $scan."<br />";
	        	} else {
	        		$output[] = "<td><strong><a href='".$this->html->init->admin_url."-antivirus&p=scan_5'>".__("Database Scanner")."</a></strong><br />";
	        	}
	        	$output[] = __("Scan database to check if it contains any XSS codes via any SQL Injection bugs at your Wordpress.", TEBRAVO_TRANS);
	        	$output[] = "</td><tr>";
        	}
        	//Filechange Scanner
        	if( ( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'filechange') ) ) == 'checked' ){
        		$scanners = 1;
	        	$output[] = "<tr class='tebravo_underTD'><td width=68 style='margin:0 auto;'><img src='".plugins_url('assets/img/scanner/filechange_scanner.png', TEBRAVO_PATH)."'></td>";
	        	$output[] = "<td><strong><a href='".$this->html->init->admin_url."-antivirus&p=scan_6'>".__("File Change Scanner")."</a></strong><br />";
	        	$output[] = __("Scan directories and files to check if there are any changes (new, altered or deleted files).", TEBRAVO_TRANS);
	        	$output[] = "</td><tr>";
        	}
        	
        	if( $scanners == 0 )
        	{
        		$output[] = "<tr class='tebravo_underTD'><td colspan=2>";
        		$output[] = __("Enable some scan modules from <i>settings > antivirus</i>.", TEBRAVO_TRANS);
        		$output[] = "</td><tr>";
        	}
        	$output[] = "</table></div>";
        	
        	$output[] = "<div id='tebravo_results'></div>";
        	
        	echo implode("\n", $output);
        	
        	$this->html->footer();
        }
        
        //settings HTML dashboard
        public function dashborad_settings()
        {
        	$this->html = new tebravo_html();
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
        	
        	$antimalware_yes = '';
        	$phpmussel_yes = '';
        	$googshaver_yes = '';
        	$domainspam_yes = '';
        	$dbscanner_yes = '';
        	$filechange_yes = '';
        	
        	//save settings
        	if( $_POST)
        	{
        		if(!empty($_POST['_nonce'])
        				&& false !== wp_verify_nonce($_POST['_nonce'] , $this->html->init->security_hash.'antivirus-settings'))
        		{
        			if(isset($_POST['antimalware'])){$antimalware_yes = trim(sanitize_text_field($_POST['antimalware']));}
        			if(isset($_POST['googshaver'])){$googshaver_yes = trim(sanitize_text_field($_POST['googshaver']));}
        			if(isset($_POST['googshaver_api'])){$googshaver_api= trim(sanitize_text_field($_POST['googshaver_api']));}
        			if(isset($_POST['domainspam'])){$domainspam_yes = trim(sanitize_text_field($_POST['domainspam']));}
        			if(isset($_POST['dbscanner'])){$dbscanner_yes = trim(sanitize_text_field($_POST['dbscanner']));}
        			if(isset($_POST['filechange'])){$filechange_yes = trim(sanitize_text_field($_POST['filechange']));}
        			if(isset($_POST['scan_attachments'])){$scan_attachments= trim(sanitize_text_field($_POST['scan_attachments']));}
        			
        			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'antimalware', $antimalware_yes);
        			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'googshaver', $googshaver_yes);
        			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'googshaver_api', $googshaver_api);
        			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'domainspam', $domainspam_yes);
        			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'dbscanner', $dbscanner_yes);
        			//tebravo_utility::update_option(TEBRAVO_DBPREFIX.'attachments_infected_action', trim(sanitize_text_field($_POST['attachments_infected_action'])));
        			//tebravo_utility::update_option(TEBRAVO_DBPREFIX.'new_plugins_infected_action', trim(sanitize_text_field($_POST['new_plugins_infected_action'])));
        			//tebravo_utility::update_option(TEBRAVO_DBPREFIX.'new_themes_infected_action', trim(sanitize_text_field($_POST['new_themes_infected_action'])));
        			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'filechange', $filechange_yes);
        			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'scan_fchange_new', trim(sanitize_text_field($_POST['scan_fchange_new'])));
        			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'scan_fchange_altered', trim(sanitize_text_field($_POST['scan_fchange_altered'])));
        			tebravo_utility::update_option(TEBRAVO_DBPREFIX.'fchange_infected_action', trim(sanitize_text_field($_POST['fchange_infected_action'])));
        			
        			tebravo_redirect_js($this->html->init->admin_url.'-antivirus&p=settings&msg=01');
        		} else {
        			tebravo_redirect_js($this->html->init->admin_url.'-antivirus&p=settings&err=02');
        		}
        		
        		exit;
        	}
        	
        	//start dashboard for settings
        	//HTML
        	
        	if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'antimalware'))) == 'checked'){$antimalware_yes = 'checked';}
        	if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'phpmussel'))) == 'checked'){$phpmussel_yes = 'checked';}
        	if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'googshaver'))) == 'checked'){$googshaver_yes = 'checked';}
        	if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'domainspam'))) == 'checked'){$domainspam_yes = 'checked';}
        	if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'dbscanner'))) == 'checked'){$dbscanner_yes = 'checked';}
        	if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'filechange'))) == 'checked'){$filechange_yes = 'checked';}
        	
        	
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
        			"is_active"=> 'active');
        	
        	$tabs["mail"] = array("title"=>"Email Settings",
        			"href"=>$this->html->init->admin_url."-mail&p=settings",
        			"is_active"=> '');
        	
        	$tabs["recaptcha"] = array("title"=>"reCAPTCHA",
        			"href"=>$this->html->init->admin_url."-recaptcha",
        			"is_active"=> '');
        	
        	$tabs["error_pages"] = array("title"=>"Error Pages",
        			"href"=>$this->html->init->admin_url."-error_pages",
        			"is_active"=> '');
        	
        	//Tabs HTML
        	$desc = "Choose the best for your website, This section lets you to optimize the antivirus auto-run.";
        	$this->html->header(__("Antivirus Settings", TEBRAVO_TRANS), $desc, 'antivirus.png');
        	
        	$this->html->tabs($tabs);
        	$this->html->start_tab_content();
        	
        	$googshaver_api = trim( esc_html( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'googshaver_api') ) );
        	
        	$output[] = "<form action='".$this->html->init->admin_url."-antivirus&p=settings' method=post>";
        	$output[] = "<input type='hidden' name='_nonce' value='".$this->html->init->create_nonce('antivirus-settings')."'>";
        	$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
        	$output[] = "<table border=0 width=100% cellspacing=0>";
        	//disable / enable scanner
        	$output[] = "<tr class='tebravo_headTD'><td><strong>".__("Enable/Disable Scanners", TEBRAVO_TRANS)."</strong></td></tr>";
        	$output[] = "<tr class='tebravo_underTD'><td>";
        	$help_antimalware = "Scan website directories and files for viruses and malware.";
        	$output[] = "<input type='checkbox' name='antimalware' value='checked' id='antimalware' $antimalware_yes><label for='antimalware'>".__("Anti Malware Scanner", TEBRAVO_TRANS)."</label> ".$this->html->open_window_help('antimalware',$help_antimalware)."</td></tr>";
        	$output[] = "<tr class='tebravo_underTD'><td>";
        	$help_phpmussel = "PHPMussel is an open source software, it is good, perfect and recommended to enable it.";
        	$output[] = "<input type='checkbox' name='phpmussel' value='checked' id='phpmussel' disabled><label for='phpmussel'>".__("PHPMussel Scanner", TEBRAVO_TRANS)."</label> ".$this->html->open_window_help('phpmussel',$help_phpmussel)."  <span class='tebravo_trail'>".__("Premium", TEBRAVO_TRANS)."</span></td></tr>";
        	$output[] = "<tr class='tebravo_underTD'><td>";
        	$help_googshaver = "Scan website data to prevent phishing entry. It is anti-phishing scanner tool.";
        	$output[] = "<input type='checkbox' name='googshaver' value='checked' id='googshaver' $googshaver_yes><label for='googshaver'>".__("Google Safe Browsing Scanner", TEBRAVO_TRANS)."</label> ".$this->html->open_window_help('googshaver',$help_googshaver);
        	$output[] = "<br /><br />&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;".__('Google API Key', TEBRAVO_TRANS).": 
<input style='height:20px; width:270px; font-size:0.9em; color:#373737' type='text' name='googshaver_api' value='$googshaver_api'>
&nbsp;&nbsp;<a href='https://console.developers.google.com' target=_blank>".__("Create API", TEBRAVO_TRANS)."</td></tr>";
        	$output[] = "<tr class='tebravo_underTD'><td>";
        	$help_domainspam = "Check if your domain blacklisted as a spammer or not.";
        	$output[] = "<input type='checkbox' name='domainspam' value='checked' id='domainspam' $domainspam_yes><label for='domainspam'>".__("Domain Spam Black List Scanner", TEBRAVO_TRANS)."</label> ".$this->html->open_window_help('domainspam',$help_domainspam)."</td></tr>";
        	$output[] = "<tr class='tebravo_underTD'><td>";
        	$help_dbscanner = "Scan database to clear XSS or SQL Injection codes if exist.";
        	$output[] = "<input type='checkbox' name='dbscanner' value='checked' id='dbscanner' $dbscanner_yes><label for='dbscanner'>".__("Database Scanner", TEBRAVO_TRANS)."</label> ".$this->html->open_window_help('dbscanner',$help_dbscanner)."</td></tr>";
        	
        	//scan options
        	$output[] = "<tr class='tebravo_headTD'><td><strong>".__("Scanner Options", TEBRAVO_TRANS)."</strong></td></tr>";
        	//scan attachments
        	$attachments_options_ar = array(
        			'never' => __("Never", TEBRAVO_TRANS),
        			'malware' => __("Scan for malware", TEBRAVO_TRANS),
        			//'phpmussel' => __("Scan with PHPMussel", TEBRAVO_TRANS),
        			//'both' => __("Both scanners", TEBRAVO_TRANS),
        	);
        	$attachments_options = '';
        	foreach ($attachments_options_ar as $key_attach => $value_attach)
        	{
        		$attachments_options .= "<option value='".$key_attach."' ";
        		if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'scan_attachments'))) == $key_attach){$attachments_options .= "selected";}
        		$attachments_options .= ">".$value_attach."</option>";
        	}
        	$output[] = "<tr class='tebravo_underTD'><td>".__("Scan attachments while being uploaded?", TEBRAVO_TRANS)."  <span class='tebravo_trail'>".__("Premium", TEBRAVO_TRANS)."</span><br />";
        	$output[] = "<select name='scan_attachments' disabled>";
        	$output[] = $attachments_options;
        	$output[] = "</select>";
        	$output[] = "</td></tr>";
        	
        	//infected attachments action
        	/*$attachments_options_infected_ar = array(
        			'nothing' => __("Nothing", TEBRAVO_TRANS),
        			'autodelete' => __("Auto Delete", TEBRAVO_TRANS),
        			'quarantine' => __("Move to Quarantine", TEBRAVO_TRANS),
        	);
        	$attachments_options_infected= '';
        	foreach ($attachments_options_infected_ar as $key_attach_infected => $value_attach_infected)
        	{
        		$attachments_options_infected.= "<option value='".$key_attach_infected."' ";
        		if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'attachments_infected_action'))) == $key_attach_infected){$attachments_options_infected.= "selected";}
        		$attachments_options_infected.= ">".$value_attach_infected."</option>";
        	}
        	$output[] = "<tr class='tebravo_underTD'><td> &rarr; ".__("Infected Attachments Action:", TEBRAVO_TRANS)."<br />";
        	$output[] = "<select name='attachments_infected_action'>";
        	$output[] = $attachments_options_infected;
        	$output[] = "</select>";
        	$output[] = "</td></tr>";
        	*/
        	//scan new_plugins
        	$new_plugins_options_ar = array(
        			'never' => __("Never", TEBRAVO_TRANS),
        			'malware' => __("Scan for malware", TEBRAVO_TRANS),
        			'phpmussel' => __("Scan with PHPMussel", TEBRAVO_TRANS),
        			'both' => __("Both scanners", TEBRAVO_TRANS),
        	);
        	$new_plugins_options = '';
        	foreach ($new_plugins_options_ar as $key_plugin => $value_plugin)
        	{
        		$new_plugins_options .= "<option value='".$key_plugin."' ";
        		if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'scan_new_plugins'))) == $key_plugin){$new_plugins_options .= "selected";}
        		$new_plugins_options .= ">".$value_plugin."</option>";
        	}
        	$output[] = "<tr class='tebravo_underTD'><td>".__("Scan New Plugins Before Using It?", TEBRAVO_TRANS)."  <span class='tebravo_trail'>".__("Premium", TEBRAVO_TRANS)."</span><br />";
        	$output[] = "<select name='scan_new_plugins' disabled>";
        	$output[] = $new_plugins_options;
        	$output[] = "</select>";
        	$output[] = "</td></tr>";
        	
        	//infected new_plugins action
        	/*$new_plugins_options_infected_ar = array(
        			'nothing' => __("Nothing", TEBRAVO_TRANS),
        			'autodelete' => __("Auto Delete", TEBRAVO_TRANS),
        			'quarantine' => __("Move to Quarantine", TEBRAVO_TRANS),
        	);
        	$new_plugins_options_infected= '';
        	foreach ($new_plugins_options_infected_ar as $key_plugin_infected => $value_plugin_infected)
        	{
        		$new_plugins_options_infected.= "<option value='".$key_plugin_infected."' ";
        		if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'new_plugins_infected_action'))) == $key_plugin_infected){$new_plugins_options_infected.= "selected";}
        		$new_plugins_options_infected.= ">".$value_plugin_infected."</option>";
        	}
        	$output[] = "<tr class='tebravo_underTD'><td> &rarr; ".__("Infected Plugins Action:", TEBRAVO_TRANS)."<br />";
        	$output[] = "<select name='new_plugins_infected_action'>";
        	$output[] = $new_plugins_options_infected;
        	$output[] = "</select>";
        	$output[] = "</td></tr>";
        	*/
        	//scan new_themes
        	$new_themes_options_ar = array(
        			'never' => __("Never", TEBRAVO_TRANS),
        			'malware' => __("Scan for malware", TEBRAVO_TRANS),
        			'phpmussel' => __("Scan with PHPMussel", TEBRAVO_TRANS),
        			'both' => __("Both scanners", TEBRAVO_TRANS),
        	);
        	$new_themes_options = '';
        	foreach ($new_themes_options_ar as $key_plugin => $value_plugin)
        	{
        		$new_themes_options .= "<option value='".$key_plugin."' ";
        		if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'scan_new_themes'))) == $key_plugin){$new_themes_options .= "selected";}
        		$new_themes_options .= ">".$value_plugin."</option>";
        	}
        	$output[] = "<tr class='tebravo_underTD'><td>".__("Scan New Themes Before  Using It?", TEBRAVO_TRANS)." <span class='tebravo_trail'>".__("Premium", TEBRAVO_TRANS)."</span><br />";
        	$output[] = "<select name='scan_new_themes' disabled>";
        	$output[] = $new_themes_options;
        	$output[] = "</select>";
        	$output[] = "</td></tr>";
        	
        	//infected new_themes action
        	/*$new_themes_options_infected_ar = array(
        			'nothing' => __("Nothing", TEBRAVO_TRANS),
        			'autodelete' => __("Auto Delete", TEBRAVO_TRANS),
        			'quarantine' => __("Move to Quarantine", TEBRAVO_TRANS),
        	);
        	$new_themes_options_infected= '';
        	foreach ($new_themes_options_infected_ar as $key_themes_infected => $value_themes_infected)
        	{
        		$new_themes_options_infected.= "<option value='".$key_themes_infected."' ";
        		if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'new_themes_infected_action'))) == $key_themes_infected){$new_themes_options_infected.= "selected";}
        		$new_themes_options_infected.= ">".$value_themes_infected."</option>";
        	}
        	$output[] = "<tr class='tebravo_underTD'><td> &rarr; ".__("Infected Themes Action:", TEBRAVO_TRANS)."<br />";
        	$output[] = "<select name='new_themes_infected_action'>";
        	$output[] = $new_themes_options_infected;
        	$output[] = "</select>";
        	$output[] = "</td></tr>";
        	*/
        	//file change detection
        	$output[] = "<tr class='tebravo_headTD'><td><strong>".__("File Change Detection", TEBRAVO_TRANS)."</strong></td></tr>";
        	$output[] = "<tr class='tebravo_underTD'><td>";
        	$output[] = "<input type='checkbox' name='filechange' value='checked' id='filechange' $filechange_yes><label for='filechange'>".__("Enabled", TEBRAVO_TRANS)."</label>";
        	$output[] = "</td></tr>";
        	
        	//file change options
        	$output[] = "<tr class='tebravo_headTD'><td><strong>".__("File Change Options", TEBRAVO_TRANS)."</strong></td></tr>";
        	//new files
        	$fchange_new_options_ar = array(
        			'never' => __("Never", TEBRAVO_TRANS),
        			'malware' => __("Scan for malware", TEBRAVO_TRANS),
        			'phpmussel' => __("Scan with PHPMussel", TEBRAVO_TRANS),
        			'both' => __("Both scanners", TEBRAVO_TRANS),
        	);
        	$fchange_new_options = '';
        	foreach ($fchange_new_options_ar as $key_fchange_new => $value_fchange_new)
        	{
        		$fchange_new_options .= "<option value='".$key_fchange_new."' ";
        		if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'scan_fchange_new'))) == $key_fchange_new){$fchange_new_options .= "selected";}
        		$fchange_new_options .= ">".$value_fchange_new."</option>";
        	}
        	$output[] = "<tr class='tebravo_underTD'><td>".__("Scan New Files automatically?", TEBRAVO_TRANS)."<br />";
        	$output[] = "<select name='scan_fchange_new'>";
        	$output[] = $fchange_new_options;
        	$output[] = "</select>";
        	$output[] = "</td></tr>";
        	//altered files
        	$fchange_altered_options_ar = array(
        			'never' => __("Never", TEBRAVO_TRANS),
        			'malware' => __("Scan for malware", TEBRAVO_TRANS),
        			'phpmussel' => __("Scan with PHPMussel", TEBRAVO_TRANS),
        			'both' => __("Both scanners", TEBRAVO_TRANS),
        	);
        	$fchange_altered_options = '';
        	foreach ($fchange_altered_options_ar as $key_fchange_altered => $value_fchange_altered)
        	{
        		$fchange_altered_options .= "<option value='".$key_fchange_altered."' ";
        		if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'scan_fchange_altered'))) == $key_fchange_altered){$fchange_altered_options .= "selected";}
        		$fchange_altered_options .= ">".$value_fchange_altered."</option>";
        	}
        	$output[] = "<tr class='tebravo_underTD'><td>".__("Scan Altered Files Automatically?", TEBRAVO_TRANS)."<br />";
        	$output[] = "<select name='scan_fchange_altered'>";
        	$output[] = $fchange_altered_options;
        	$output[] = "</select>";
        	$output[] = "</td></tr>";
        	//infected files action
        	$files_options_infected_ar = array(
        			'nothing' => __("Nothing", TEBRAVO_TRANS),
        			'autodelete' => __("Auto Delete", TEBRAVO_TRANS),
        			'quarantine' => __("Move to Quarantine", TEBRAVO_TRANS),
        	);
        	$files_options_infected= '';
        	foreach ($files_options_infected_ar as $key_files_infected => $value_files_infected)
        	{
        		$files_options_infected.= "<option value='".$key_files_infected."' ";
        		if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'fchange_infected_action'))) == $key_files_infected){$files_options_infected.= "selected";}
        		$files_options_infected.= ">".$value_files_infected."</option>";
        	}
        	$output[] = "<tr class='tebravo_underTD'><td> &rarr; ".__("Affected Files Action:", TEBRAVO_TRANS)."<br />";
        	$output[] = "<select name='fchange_infected_action'>";
        	$output[] = $files_options_infected;
        	$output[] = "</select>";
        	$output[] = "</td></tr>";
        	
        	$output[] = "</table>";
        	$output[] = $this->html->button(__("Save", TEBRAVO_TRANS), "submit");
        	$output[] = "</div>";
        	$output[] = "</form>";
        	$output[] = "<div id='tebravo_results'></div>";
        	
        	echo implode("\n", $output);
        	$this->html->end_tab_content();
        	$this->html->footer();
        }
        
        //if scan module disabled //print this dashboard
        public function module_disabled_dashboard( $module_name )
        {
        	$this->html = new tebravo_html();
        	//check permissions
        	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( ); exit;}
        	
        	ob_start();
        	
        	$module_name = str_replace('domainspam', 'Spam Check', $module_name);
        	$module_name = str_replace('dbscanner', 'DB Scanner', $module_name);
        	
        	$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
        	$output[] = "<h3>".ucfirst(esc_html( $module_name ))." disabled!</h3><hr>";
        	$output[] = "If you want to enable it, please go to <i>plugin settings > antivirus > enable ".ucfirst(esc_html( $module_name ))."</i>";
        	$output[] = "</div>";
        	
        	return implode("\n", $output);
        }
        
    }
    //run
    new tebravo_anitvirus();
}