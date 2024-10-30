<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !class_exists( 'tebravo_files' ) )
{
	class tebravo_files
	{
		public static $default_read_length=1024;
		public static $orginal_perms;
		public static $default_perms = array (0644, 0664, 0666);
		
		//contstructor
		public function __construct()
		{
		}
		
		//get file permissions
		public static function file_perms( $file )
		{
			if(strpos(@ini_get('disable_functions'), 'fileperms') === false){
				return substr(sprintf('%o', @fileperms( $file )), -4);
			}
		}
		
		//chmod files and dirs
		public static function dochmod( $file , $perms)
		{
			if(strpos(@ini_get('disable_functions'), 'chmod') === false){
				@chmod($file, $perms);
				return true;
			} else {return false;}
		}
		
		//check writable
		public static function writable( $file )
		{
			if(strpos(@ini_get('disable_functions'), 'writable') === false){
				if(is_writable($file)){return true;} else {return false;}
			}
		}
		
		//get file contents
		public static function read( $file )
		{
			if( !file_exists( $file ) ) {sprintf( __( '%s is not readable or non exists.', TEBRAVO_TRANS), $file );}
			if( filesize( $file ) == 0 ){sprintf( __( '%s is not readable or non exists.', TEBRAVO_TRANS), $file );}
			if( !is_readable( $file ) ){sprintf( __( '%s is not readable or non exists.', TEBRAVO_TRANS), $file );}
			//file permissions and acceptable list
			$perms_array= self::$default_perms;
			if(! isset($file_perms)){
				$file_perms = $orginal_perms = self::file_perms( $file );
			}
			/*
			//check writable
			if( !in_array($file_perms, $perms_array) )
			{
				if(tebravo_init::get_php_handler() == 'CGI')
				{
					self::dochmod( $file , 0644 );
				} else {
					self::dochmod( $file , 0664 );
					if(!self::writable($file))
					{
						self::dochmod( $file, 0666 );
					}
				}
			}
			*/
			$data = '';
			//try fopen
			if( strpos(@ini_get('disable_functions'), 'fopen') === false && false !== ( $fp = @fopen( $file, 'rb' ) ) )
			{
				//lock file
				@flock($fp, LOCK_SH);
				
				while ( !feof ( $fp ) )
				{
					$data .= @fread( $fp, self::$default_read_length );
				}
				
				@flock( $fp, LOCK_UN );
				@fclose($fp);
			} else if(strpos(@ini_get('disable_functions'), 'file_get_contents') === false){
					$data .= @file_get_contents( $file );
			}else {
				sprintf( __( '%s is not readable or non exists.', TEBRAVO_TRANS), $file );
			}
			
			/*
			//reset the previous chmod;
			if($orginal_perms != self::file_perms( $file )
					&& is_int( $orginal_perms ))
			{
				self::dochmod( $file , $orginal_perms);
			}
			*/
			return $data; 
		}
		
		//write to files
		public static function write( $file , $data, $append = false)
		{
			$dir = dirname( $file );
			//create dir if not exists
			if(!is_dir( $dir ))
			{
				tebravo_dirs::create( $dir );
			}
			
			//file permissions and acceptable list
			$perms_array = self::$default_perms;
			
			$file_perms = self::$orginal_perms = self::file_perms( $file );
			
			
			//check writable
			if( !in_array($file_perms, $perms_array) )
			{
				if(tebravo_init::get_php_handler() == 'CGI')
				{
					self::dochmod( $file , 0644 );
				} else {
					self::dochmod( $file , 0664 );
					if(!self::$writable($file))
					{
						self::dochmod( $file, 0666 );
					}
				}
			}
			
			//set roles
			if( $append ){$role = 'ab';} else{$role = 'wb';}
			
			if( false !== ( $fp = !@fopen( $file , $role ) ) )
			{
				@flock( $file ,  LOCK_EX );
				
				//WP safe encoding
				mbstring_binary_safe_encoding();
				
				$length = strlen( $data );
				$writen = @fwrite( $fp , $data );
				
				//reset encoding
				reset_mbstring_encoding();
				
				@flock( $file , LOCK_UN );
				
				@fclose( $fp );
				
				if($length == $writen)
				{
					$result = true;
				} else {$result = false;}
			} else {$result = false;}
			
			if( !$result 
					&&strpos(@ini_get('disable_functions'), 'file_put_contents') === false)
			{
				//set role
				if($append){$mode = FILE_APPEND;} else {$mode = 0;}
				
				//WP safe encoding
				mbstring_binary_safe_encoding();
				
				$length = @strlen( $data );
				if(!$length){$length = @sizeof($data);}
				$writen = @file_put_contents( $file , $data , $mode );
				
				//reset encoding
				reset_mbstring_encoding();
				
				if( $length == $writen )
				{
					$result = true;
				}
					
			}
			
			//reset the previous chmod;
			if($file_perms != self::file_perms( $file )
					&& is_int( self::$orginal_perms))
			{
				if( is_file( $file ))
				self::dochmod( $file , $file_perms);
			}
			
			
			if($result)
			{
				return true;
			} else {
				//error
				return false;
			}
			
				
		}
		
		//delete file
		public static function remove( $file )
		{
			if( file_exists( $file ) )
			{
				@unlink( $file );
				return true;
			} else {
				return false;
			}
			
		}
		
		//get extension
		public static function extension( $file )
		{
			return pathinfo($file, PATHINFO_EXTENSION);
		}
	}
	
	
}
?>