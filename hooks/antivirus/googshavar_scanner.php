<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}
if( !class_exists( 'tebravo_googshavar_scanner' ) )
{
    class tebravo_googshavar_scanner extends tebravo_antivirus_utility{
        
    	public $pid;
    	public $file;
    	public $file_report;
    	public $line_number;
    	public $file_content_data;
    	public $max_size;
    	public $phpMusselOptions = array();
    	public $scan_log;
    	public $timer_ajax;
    	public $max_files_to_scan;
    	public $skipped_list = array();
    	public $api_key;
    	
    	public function __construct()
    	{
    		//scan_log
    		$this->max_size = 1048576; //in bytes
    		
    		
    		$this->timer_ajax = '900'; //in seconds
    		$this->max_files_to_scan= '3'; //digit
    		
    		$this->api_key = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'googshaver_api' ) ) );
    	}
    	
    	
    	public function scan( $file )
    	{
    		$flag = false;
    		
    		//check API
    		if( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'googshaver_api') ) == '' )
    		{
    			$message = __("Google safe browsing API is missing, Please update", TEBRAVO_TRANS);
    			$message .= " <i>".__("settings", TEBRAVO_TRANS).">".__("antivirus", TEBRAVO_TRANS)."</i>";
    			tebravo_die(true, $message, false, true);
    		}
    		
    		if( filter_var($file, FILTER_VALIDATE_URL) )
    		{
    			$url = urlencode( $file );
    			
    			if(! $this->check_safe_list( $file)){
    				$url_api = "https://sb-ssl.google.com/safebrowsing/api/lookup?client=wpantivirus&key=".$this->api_key."&appver=1.3.7&pver=3.1&url=".$file;
    				$response = wp_remote_get( $url_api );
    				
    				$headers = wp_remote_retrieve_response_code( $response );
    				
    				
    			} else {
    				$headers = 200;
    			}
    			
    			return $headers;
    			
    		}
    		
    		
    		return $flag;
    	}
        
    }
}
?>