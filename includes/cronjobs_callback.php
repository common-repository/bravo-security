<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

/**
 * Database Backup callback
 */
if( !function_exists( '_tebravo_dbbackup_callback' ) ){
	function _tebravo_dbbackup_callback()
	{
		
			$db = new tebravo_db();
			$db->backup( 'scheduled' );
			
			$cronjobs = new tebravo_cronjobs();
			$task = 'dbbackup';
			$email = _tebravo_event_details($task, 'email');
			
			if( !empty( $email ) )
			{
				$message = __("DB Backup Done!", TEBRAVO_TRANS);
				$message.= "<br />";
				$message.= "<br />";
				$message.= __("Your database backup is ready.", TEBRAVO_TRANS);
				$message.= "<br />";
				$message.= @date('d-m-Y h:i a');
				
				$subject = __("DB Backup Done!", TEBRAVO_TRANS);
				
				tebravo_mail($email,$subject, $message);
			}
		
	}
}

/**
 * Files backup callback
 */
if( !function_exists( '_tebravo_filesbackup_callback' ) ){
	function _tebravo_filesbackup_callback()
	{
		if( class_exists( 'tebravo_backups' ) )
		{
			$backup = new tebravo_backups();
			$backup->backup_files( false, 'scheduled/files' );
			
			$cronjobs = new tebravo_cronjobs();
			$task = 'filesbackup';
			$email = _tebravo_event_details($task, 'email');
			
			if( !empty( $email ) )
			{
				$message = __("Files Backup Done!", TEBRAVO_TRANS);
				$message.= "<br />";
				$message.= "<br />";
				$message.= __("Your wordpress files backup is ready.", TEBRAVO_TRANS);
				$message.= "<br />";
				$message.= __("Please check.", TEBRAVO_TRANS);
				
				
				$subject = __("Files Backup Done!", TEBRAVO_TRANS);
				
				tebravo_mail( $email, $subject, $message);
			}
		}
	}
}

/**
 * Malware Scan callback
 */
if( !function_exists( '_tebravo_malwarescan_callback' ) ){
	function _tebravo_malwarescan_callback()
	{
		global $wpdb;
		
		$helper = new tebravo_html();
		$antivirus = new tebravo_anitvirus();
		$tmp_dir = TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/tmp/';
		$cronjobs = new tebravo_cronjobs();
		$task = 'malwarescan';
		$nextrun = $cronjobs->get_cron_task( $task , 'time' );
		$sendemail = _tebravo_event_details( $task , 'email' );
		$time = time();
		
		$current_run_time = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'malware_cronjob_prev_run' ) ) );
		$current_pid = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'malware_cronjob_pid' ) ) );
		
		if( empty( $current_run_time ) )
		{
			$current_run_time = (time()-60);
		}
		
		$pid = md5( $current_run_time );
		$pid_file = $tmp_dir.'/'.$pid.'.txt';
		
		if( $current_run_time <= $time )
		{
			if( $current_pid == ''){
				tebravo_utility::update_option( TEBRAVO_DBPREFIX.'malware_cronjob_prev_run' , sanitize_text_field( $current_run_time ) );
				tebravo_utility::update_option( TEBRAVO_DBPREFIX.'malware_cronjob_pid' , sanitize_text_field( $pid ) );
			}
			
			if( !file_exists( $pid_file ) )
			{
				$antivirus->insert_new_process($pid, 'malware');
				$new_task = 'tebravo_malware_tmp';
				$cronjobs->add_schedule_event($new_task, 60, $new_task);
			}
				$antivirus->scan_files( true, $pid );
				
				$query = $wpdb->get_row( "SELECT percent FROM ".tebravo_utility::dbprefix()."scan_ps WHERE pid='$pid' Limit 1");
				if($query->percent == 100)
				{
					tebravo_utility::update_option( TEBRAVO_DBPREFIX.'malware_cronjob_prev_run' , sanitize_text_field( $nextrun ) );
				}
			
			#wp_mail('hackdid@gmail.com', "Scan Process Started", "Ameeeeeeeel Eeeeeeeeh");
		}
		
		#wp_mail('hackdid@gmail.com', "Scan Process 2 Started", "Ameeeeeeeel Eeeeeeeeh");
	}
}

if( !function_exists( 'tebravo_malware_tmp' ) )
{
	function tebravo_malware_tmp()
	{
		global $wpdb;
		$pid = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'malware_cronjob_pid' ) ) );
		$cronjobs = new tebravo_cronjobs();
		$task = 'malwarescan';
		$nextrun = $cronjobs->get_cron_task( $task , 'time' );
		$sendemail = _tebravo_event_details( $task , 'email' );
		$antivirus = new tebravo_anitvirus();
		
		if( $pid != ''){
			$antivirus->scan_files( true, $pid );
		
			$query = $wpdb->get_row( "SELECT percent FROM ".tebravo_utility::dbprefix()."scan_ps WHERE pid='$pid' Limit 1");
			if($query->percent == 100)
			{
				tebravo_utility::update_option( TEBRAVO_DBPREFIX.'malware_cronjob_prev_run' , sanitize_text_field( $nextrun ) );
			}
		}
	}
}

/**
 * phpMussle Scan callback
 */
if( !function_exists( '_tebravo_phpmusslescan_callback' ) ){
	function _tebravo_phpmusslescan_callback()
	{
		//
	}
}

/**
 * Google Safe Browsing callback
 */
if( !function_exists( '_tebravo_gsbscan_callback' ) ){
	function _tebravo_gsbscan_callback()
	{
		//
	}
}

/**
 * Spam Black List scan callback
 */
if( !function_exists( '_tebravo_spamscan_callback' ) ){
	function _tebravo_spamscan_callback()
	{
		//
	}
}

/**
 * Database scan callback
 */
if( !function_exists( '_tebravo_dbscan_callback' ) ){
	function _tebravo_dbscan_callback()
	{
		global $wpdb;
		
		$antivirus = new tebravo_db_scanner();
		$pid = md5( time() );
		
		$wpdb->show_errors = false;
		
		$row = $wpdb->get_row( "SELECT pid FROM " .tebravo_utility::dbprefix()."scan_ps WHERE pid='$pid' Limit 1");
		if( null === $row )
		{
			$wpdb->insert(tebravo_utility::dbprefix().'scan_ps', array(
					'pid'=>$pid,
					'status'=>'prepare',
					'start_at'=>time(),
					'total_files'=>$antivirus->all_rows,
					'start_by'=>'cronjob',
					'scan_type'=>'dbscanner',
					'p_percent'=>'0',
			));
		}
		
		$antivirus->pid = $pid;
		$antivirus->scan( true );
		
		
		$cronjobs = new tebravo_cronjobs();
		$task = 'dbscan';
		$email = _tebravo_event_details($task, 'email');
		
		if( !empty( $email ) )
		{
			$message = __("DB Scanner started!", TEBRAVO_TRANS);
			$message.= "<br />";
			$message.= "<br />";
			$message .= __("<b>Bad Results</b>", TEBRAVO_TRANS).": ".$antivirus->BadCount;
			$message .= "<br />";
			$message.= __("Please check antivirus dashboard!", TEBRAVO_TRANS);
			
			
			$subject = __("DB Scanner Report", TEBRAVO_TRANS);
			
			tebravo_mail( tebravo_utility::get_option( 'admin_email' ) , $subject, $message);
		}
	}
}

/**
 * Filechange Detection scan callback
 */
if( !function_exists( '_tebravo_filechange_callback' ) ){
	function _tebravo_filechange_callback()
	{
		global $wpdb;
		
		$user = wp_get_current_user();
		
		$dir = ABSPATH;
		$antivirus = new tebravo_filechange_scanner();
		
		$pid = md5( time() );
		
		$wpdb->show_errors = false;
		$wpdb->insert(tebravo_utility::dbprefix().'scan_ps', array(
				'pid'=>$pid,
				'status'=>'prepare',
				'start_at'=>time(),
				'total_files'=>count( $antivirus->all_files ),
				'start_by'=>'cronjob',
				'scan_type'=>'filechange',
				'p_percent'=>'0',
		));
		$antivirus->pid = $pid;
		$antivirus->no_update = false;
		
		$antivirus->scan( $dir );
		
		$utility = new tebravo_antivirus_utility();
		if( $antivirus->report_countfiles == 0)
		{
			$utility->delete_process( $pid );
		} else {
			$cronjobs = new tebravo_cronjobs();
			$task = 'filechange';
			$email = _tebravo_event_details($task, 'email');
			
			if( !empty( $email ) )
			{
				$message = __("Your website have some changes while the <i>Filechange</i> scanner being running.", TEBRAVO_TRANS);
				$message.= "<br />";
				$message.= __("Please check antivirus dashboard!", TEBRAVO_TRANS);
				
				
				$subject = __("File Change Scanner Report", TEBRAVO_TRANS);
				
				tebravo_mail( tebravo_utility::get_option( 'admin_email' ) , $subject, $message);
			}
		}
	}
}

/**
 * Deprecated Detection scan callback
 */
if( !function_exists( '_tebravo_deprecated_callback' ) ){
	function _tebravo_deprecated_callback()
	{
		//
	}
}

/**
 * Traffic Tracker scan callback
 */
if( !function_exists( '_tebravo_traffictracker_callback' ) ){
	function _tebravo_traffictracker_callback()
	{
		global $wpdb;
		
		$wpdb->show_errors( false );
		
		$dead_time = ( time() - (5*60) );
		$max_queries = 150;
		
		$query = "DELETE FROM ".tebravo_utility::dbprefix()."traffic WHERE
						last_active < '$dead_time'";
		$wpdb->query( $query );
		/*
		$wpdb->delete(tebravo_utility::dbprefix().'traffic', array(
				"last_action" => $dead_time
		), ">=");
		
		*/
	}
}
?>