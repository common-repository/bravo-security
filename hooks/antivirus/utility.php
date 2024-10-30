<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}


if( !class_exists( 'tebravo_antivirus_utility' ) )
{
	class tebravo_antivirus_utility {
		
		public $userid;
		
		public function __construct()
		{
			
			$user = wp_get_current_user();
			
			if( $user && is_array( $user ) )
			{
				$this->userid = $user->ID;
			}
			
			if( $this->userid < 0 && !current_user_can( 'manage_options') ){
				wp_die(__("Please login in."));
			}
		}
		//implode to an array
		public function _implode($array, $glue = ', ') {
			$temp = array();
			foreach ($array as $value) {
				if (is_array($value)) {
					$temp[] = $this->_implode($value);
				} else {
					$temp[] = $value;
				}
			}
			return implode($glue, array_unique($temp));
		}
		
		//get content of file
		public function file_content( $file )
		{
			
			/*if( file_exists( $file )
			 && is_readable( dirname($file) )
			 && basename($file) != '.htaccess' )
			 {
			 */
			if( file_exists( $file )){
				$content = tebravo_files::read( $file );
			}
			
			return $content;
		}
		
		//create mini report for infected file
		public function create_report_mini( $file_infected , $t)
		{
			#$content = $this->file_content( $file_infected);
			
			if( filter_var( $file_infected, FILTER_VALIDATE_URL)) 
			{
				$file_infected = str_replace("http://", '', $file_infected);
			}
			$exp_1 = explode(":infected#tebravo#", $file_infected);
			$report = $exp_1[0];
			if( !empty( $report ) ):
			$exp_2 = explode(":", $report);
			$file_name = $exp_2[0];
			$report_2 = $exp_2[1];
			$exp_3 = explode(":", $report_2);
			$line_number = $exp_3[0];
			$exp_report = explode($line_number.':', $report);
			$file_report = $exp_report[1];
			
			$file_report = str_replace('High', '<span class=tebravo_scan_report_high>'.__("High", TEBRAVO_TRANS)."</span>", $file_report);
			$file_report = str_replace('SHELL', __('May be',TEBRAVO_TRANS).' <span class=tebravo_scan_report_shell>'.__("SHELL/BACKDOOR", TEBRAVO_TRANS)."</span>", $file_report);
			if( false !== strpos( $file_report , 'attack detected'))
			{
				$file_report = " <span class=tebravo_scan_report_shell>".$file_report."</span>";
			}
			if( false !== strpos( $file_report , 'blacklisted'))
			{
				$file_report = " <span class=tebravo_scan_report_blacklisted>".$file_report."</span>";
			}
			if( $t == 'file_name' ){ return $file_name;}
			else if( $t == 'line_number' ){ return $line_number;}
			else if( $t == 'file_report' ){ return $file_report;}
			endif;
		}
		
		//check if file in safe list or not
		public function check_safe_list( $file )
		{
			$file = str_replace("//", "/", $file);
			
			$safe_list = array();
			$safe_files = trim(esc_html( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'safe_files') ));
			if($safe_files != ''){
				$safe_list = explode(",", $safe_files);
			}
			
			if( in_array( $file , $safe_list) ) {return true;}
			else {
				return false;
			}
		}
		
		//print HTML5 sound mp3
		public function display_sound( $sound_file )
		{
			$sound = 'assets/sounds/'.$sound_file;
			?>
        				<div style="display:none;">
						<audio autoplay>
        				<source src="<?php echo plugins_url($sound, TEBRAVO_PATH);?>">
        				</audio>
        				</div>
        				<?php
		}
		
		//move infected file to quarantine
		public function move_to_quarantine( $file , $chmod=false)
		{
			//change file permission
			if($chmod)
			{
				tebravo_files::dochmod( $file , '0000');
			}
			
			//update quarantine
			$current_quarantine = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'quarantine') ) );
			
			$data = '';
			
			if( !empty( $current_quarantine ) ){
				$exp = explode( '#tebravo#', $current_quarantine );
				foreach ($exp as $newfile )
				{
					if( $file != $newfile)
					$data .= $newfile.'#tebravo#';
				}
			}
			$data .= $file.'#tebravo#';
			
			tebravo_utility::update_option( TEBRAVO_DBPREFIX.'quarantine' , sanitize_text_field( $data ) );
		}
		
		public function check_quarantine( $file )
		{
			$current_quarantine = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'quarantine') ) );
			if( !empty( $current_quarantine ) ){
				$exp = explode( '#tebravo#', $current_quarantine );
				foreach ($exp as $newfile )
				{
					if( $file === $newfile)
						return true;
				}
			}
			
			return false;
		}
		
		public function delete_process( $pid )
		{
			global $wpdb;
			
			$pid = trim( esc_html( $pid ) );
			
			$row = $wpdb->get_row( "SELECT pid FROM " .tebravo_utility::dbprefix()."scan_ps WHERE pid='$pid' Limit 1");
			if( null !== $row )
			{
				$wpdb->delete(tebravo_utility::dbprefix()."scan_ps", array("pid" => $pid ) );
			}
		}
		
		public function set_memory_limit()
		{
			$memory_limit = trim( esc_html( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'memory_limit') ) );
			if( $memory_limit != 'default')
			{
				@ini_set('memory_limit', $memory_limit);
			}
		}
		
		public function set_max_timeout()
		{
			$max_execution_time = trim( esc_html( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'max_execution_time') ) );
			if( $max_execution_time!= 'default')
			{
				@ini_set('max_execution_time', $max_execution_time);
			}
		}
		
		public function set_display_errors( $status )
		{
			if( $status == 'off' )
			{
				@ini_set('display_errors', 1);
			} else {
				@ini_set('display_errors', 0);
			}
			
		}
	}
	
}
?>