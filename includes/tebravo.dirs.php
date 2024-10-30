<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !class_exists( 'tebravo_dirs' ) )
{
	class tebravo_dirs
	{
		public $default_read_length = 1024;
		public $default_perms = array();
		public static $default_read_funcs = array('opendir', 'glob');
	
		//contstructor
		public function __construct()
		{
		    $this->default_perms = array (0755, 0777);
		}
		
		//read dir 
		public static function read( $dir )
		{
			if(! is_dir( $dir))
			{
				trigger_error("<strong>Error</strong>: Directory <i>{$dir}</i> not found!");
			}
			
			//use opendir()
			if(in_array( 'opendir' , self::$default_read_funcs) )
			{
				if( false !== ( $fp = opendir( $dir ) ) ):
					$files = array();
					while ( false !== ( $file= readdir( $fp ) ) )
					{
						if ( in_array( basename( $file ), array( '.', '..' ) ) ) {
							continue;
						}
						
						$files[] = "$file";
					}
					
					closedir( $fp );
					sort( $files );
					
					return $files;
				endif;
			}
			
			//use glob()
			if(in_array( 'glob' , self::$default_read_funcs) )
			{
				$scan_visible = glob( "$dir/*" );
				$scan_hidden = glob( "$dir/.*" );
				
				if ( $scan_visible !== false|| $scan_hidden !== false) {
					if ( false === $scan_visible) {
						$scan_visible= array();
					}
					if ( false === $scan_hidden) {
						$scan_hidden= array();
					}
					
					$files = array_merge( $scan_visible, $scan_hidden);
					
					foreach ( $files as $index => $file ) {
						if ( in_array( basename( $file ), array( '.', '..' ) ) ) {
							unset( $files[$index] );
						}
					}
					
					sort( $files );
					
					return $files;
				}
			}
		}
		//create dir
		public static function create( $dir )
		{
		    $dir = rtrim( $dir , '/');
		    
		    if(is_dir ( $dir ))
		    {
		        $this->indexing( $dir );
		        return true;
		    }
		    
		    if( !file_exists( $dir ) )
		    {
		    	if(strpos(@ini_get('disable_functions'), 'mkdir') === false){
		    		
		    		$perms = tebravo_files::file_perms( rtrim( $dir , '/' ) );
		    		if( !is_int($perms) )
		    		{
		    			$perms = '0755';
		    			
		    			$umask = umask( 0 );
		    			$result = @mkdir( $dir, $perms, true );
		    			umask( $umask );
		    			
		    			if ($result)
		    			{
		    				return false;
		    				$this->indexing( $dir );
		    			} else {
		    				return false;
		    				// error
		    			}
		    		}
		    	} else {
		    		return false;
		    		//error
		    	}
		    } else {
		    	return false;
		    	// error
		    }
		}
		
		//protect directories with index.php
		public static function indexing( $dir )
		{
			$dir = rtrim( $dir , '/');
			$file = $dir .'/index.php';
			
			if( !file_exists( $file ) )
			{
				tebravo_files::write( $dir.'/index.php' , '<?php //'.TEBRAVO_PLUGINNAME);
			}
		}
		
		//check if writable
		public static function writable( $dir )
		{
			$dir = rtrim( $dir , '/' );
			
			if(! is_writable( $dir ))
			{
				$test_file = TEBRAVO_SLUG."_".time().".txt";
				$results = tebravo_files::write( $dir.'/'.$test_file , 'Tested File By Bravo');
				if( $results == true)
				{
					tebravo_files::remove( $dir.'/'.$test_file );
					return true;
				} else {
					return false;
				}
			} else {
				return true;
			}
		}
		
		//get dir size
		public static function dir_size ($directory)
		{
			$size = 0;
			foreach (glob(rtrim($directory, '/').'/*', GLOB_NOSORT) as $each) {
				$size += is_file($each) ? filesize($each) : self::dir_size($each);
			}
			return $size;
		}
		
		public static function dir_file_count($directory, $zoom_in_dirs=false, $include_types=false)
		{
			if( is_dir( $directory ) )
			{
				$files = self::read( $directory );
				
				$count=0;
				foreach( $files as $file )
				{
					if( $zoom_in_dirs ){self::dir_file_count( $files.'/'.$file );
					if( is_file( $file ) && $file!='.' && $file!='..')
					{
						if( !$include_types ){$count++;}
					
						if( $include_types && is_array( $include_types ))
						{
							if( in_array(tebravo_files::extension( $file ), $include_types) )
							{
								$count++;
							}
						}
						
						if( $include_types && is_array( $include_types ))
						{
							if( in_array(tebravo_files::extension( $file ), $include_types ) )
							{
								$count--;
							}
						}
					}
				}
				
				return $count;
			}
			
		}
	}
	}
}
?>