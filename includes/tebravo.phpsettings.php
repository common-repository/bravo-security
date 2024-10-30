<?php 

//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !class_exists( 'tebravo_phpsettings' ) )
{
    class tebravo_phpsettings{
        
    	public $memory_limit,
    	$max_execution_time;
        //constructor
        public function __counstruct()
        {
        	//options
        	$this->memory_limit = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'memory_limit' ) ) );
        	$this->max_execution_time= trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'max_execution_time' ) ) );
        }
        
        //set custom PHP settings
        public function set_php_settings()
        {
        	//memory limit
        	if($this->memory_limit != 'default'
        			|| $this->memory_limit != '')
        	{
        		if( is_numeric( $this->memory_limit ) )
        			ini_set('memory_limit', $this->memory_limit);
        	}
        	
        	//max execution time
        	if($this->max_execution_time != 'default'
        			|| $this->max_execution_time != '')
        	{
        		if( is_numeric( $this->max_execution_time) )
        			ini_set('max_execution_time', $this->max_execution_time);
        	}
        }
        //check if function disabled
        public static function is_disabled( $function )
        {
            if(strpos(ini_get('disable_functions'), $function) !== false){
                return true;
            } else {
                return false;
            }
        }
        //get SAPI
        public static function get_php_handler()
        {
        	$handler = php_sapi_name();
        	$handler = trim($handler);
        	
        	if(substr($handler,0,3)
        			|| substr($handler,-3)
        			== 'cgi')
        	{
        		return 'CGI';
        	} else if(substr($handler,0,6) == 'apache')
        	{
        		return 'Apache';
        	} else {
        		return $handler;
        	}
        }
        //get server software
        public static function web_server()
        {
        	$handler = php_sapi_name();
        	
        	if( isset( $_SERVER['SERVER_SOFTWARE'] ) ){$software= strtolower( $_SERVER['SERVER_SOFTWARE'] );} 
        	else{ $software = $handler;}
        	
        	$server = $software;
        	//return $software.'<br>'.$handler;
        	//litespeed
        	if( $software=='litespeed' 
        			|| false!==strpos($software, 'litespeed')){$server = 'litespeed';}
        	//nginx
        	else if( $software=='nginx'
        			|| (false!==strpos($software, 'nginx')
        			&& $handler == 'fpm-fcgi')){$server = 'nginx';}
        	//thttpd
        	else if( $software=='thttpd'
        			|| false!==strpos($software, 'thttpd')){$server = 'thttpd';}
        	//IIS
        	else if( $software=='microsoft-iis'
        			|| false!==strpos($software, 'microsoft-iis')
        			|| false!==strpos($software, 'iis')){$server = 'iis';}
        	//apache
        	else if( $software=='apache'
        			|| false!==strpos($software, 'apache')){$server = 'apache';}
        
        		//	return $server;
        	if($server=='' && (defined('TEBRAVO_REWRITE_AS') and TEBRAVO_REWRITE_AS != '')){
        		return TEBRAVO_REWRITE_AS;
        	} else {
        		return ($server);
        	}
        }
        
        //if is nginx
        public static function is_nginx()
        {
        	if( self::web_server() == 'nginx' ) {return true;}
        	
        	return false;
        }
    }
}
?>