<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !class_exists( 'tebravo_errorlog'))
{
	class tebravo_errorlog
	{
		public static $path;
		public static $dir;
		public static $current_perma;
		public static $current_perma_file;
		public static $status;
		public static $check_proxy		;
		
		public function __construct()
		{
			self::$path= TEBRAVO_DIR."/".TEBRAVO_BACKUPFOLDER."/log/log.txt";
			self::$dir = TEBRAVO_DIR."/".TEBRAVO_BACKUPFOLDER."/log";
			self::$status = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'errorlog_enabled' ) ) );
			self::$check_proxy = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'errorlog_checkproxy' ) ) );
		}
		//create the error log file
		public static function create_file()
		{
			if( !file_exists( self::$path ) )
			{
				//check if directory is writable
				if(! tebravo_dirs::writable(self::$dir))
				{
					self::$current_perma = tebravo_files::file_perms(self::$dir); //get permissions
					tebravo_files::dochmod(self::$dir, 0777); //set writable permissions
				}
				
				//create file
				if( false !== ( $fp = !@fopen( self::$path , 'w' ) ) )
				{
					@flock( self::$path,  LOCK_EX );
					
					//WP safe encoding
					mbstring_binary_safe_encoding();
					$data = '';
					@fwrite( $fp , $data );
					
					//reset encoding
					reset_mbstring_encoding();
					
					@flock( self::$path, LOCK_UN );
					
					@fclose( $fp );
				}
				
				//back to previous permissions if exists
				if(!empty(self::$current_perma))
				{
					$available_read_permas_dir = array(
						'0555',
						'0711',
						'0511',
						'0755',
						'0444',
						'0440',
						'0400',
					);
					
					if(@in_array(self::$current_perma, $available_read_permas_dir))
					{
						$new_perma_dir = self::$current_perma;
					} else {
						$new_perma_dir= '0755';
					}
					
					tebravo_files::dochmod(self::$dir, $new_perma_dir);
				}
			}
		}
		
		//write to error log file
		public static function errorlog( $error, $user_details=true )
		{
			self::$path= TEBRAVO_DIR."/".TEBRAVO_BACKUPFOLDER."/log/log.txt";
			self::$dir = TEBRAVO_DIR."/".TEBRAVO_BACKUPFOLDER."/log";
			self::$status = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'errorlog_enabled' ) ) );
			
			//check status
			if( self::$status != 'checked' ){ return; }
			
			//create file if not exists
			if( !file_exists( self::$path) )
			{
				self::create_file();
				return;
			}
			
			//retrieve current errors (data)
			$current_data = tebravo_files::read(self::$path);
			
			//get IP, Country, Device and Browser
			
			$browser = tebravo_agent::getBrowser();
			$device = tebravo_agent::device();
			$user_data = '#IP:'.tebravo_agent::user_ip();
			$user_data .= '#Country:'.tebravo_agent::ip2country(false,'country_name');
			$user_data .= '#Device:'.$device;
			$user_data .= '#Browser:'.$browser['name'];
			$user_data .= '#OS:'.$browser['platform'];
			if( !$user_details ){$user_data = '';}
			$new_data = $current_data.'['.date('d-m-Y H:i').']#'.$error.$user_data.PHP_EOL;
			
			//check if directory is writable
			if(! is_writable(self::$dir))
			{
				self::$current_perma = tebravo_files::file_perms(self::$dir); //get permissions
				tebravo_files::dochmod(self::$dir, 0777); //set writable permissions
			}
			
			//check if file is writable
			if(! is_writable(self::$path))
			{
				self::$current_perma_file = tebravo_files::file_perms(self::$path); //get permissions
				tebravo_files::dochmod(self::$path, 0666); //set writable permissions
			}
			
			//write error log
			tebravo_files::write(self::$path, $new_data);
			
			//back to previous permissions if exists for FILE
			if(!empty(self::$current_perma_file))
			{
				$available_read_permas_file= array(
						'0444',
						'0600',
						'0640',
						'0644',
						'0400',
				);
				
				if(@in_array(self::$current_perma_file, $available_read_permas_file))
				{
					$new_perma_file= self::$current_perma_file;
				} else {
					$new_perma_file= '0644';
				}
				
				tebravo_files::dochmod(self::$path, $new_perma_file);
			}
			
			//back to previous permissions if exists for DIRECTORY
			if(!empty(self::$current_perma))
			{
				$available_read_permas_dir = array(
						'0555',
						'0711',
						'0511',
						'0755',
						'0444',
						'0440',
						'0400',
				);
				
				if(@in_array(self::$current_perma, $available_read_permas_dir))
				{
					$new_perma_dir = self::$current_perma;
				} else {
					$new_perma_dir= '0755';
				}
				
				tebravo_files::dochmod(self::$dir, $new_perma_dir);
			}
		}
	}
}
?>