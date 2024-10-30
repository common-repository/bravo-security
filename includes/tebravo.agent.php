<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if(! class_exists( 'tebravo_agent' ) )
{
	include 'AgentSrc/tebravo.Crawlers.php';
    class tebravo_agent extends tebravo_crawlers
    {
    	
    	public static function constants( $option )
    	{
    		$results = '';
    		if( $option == 'userAgent')
    		{
    			if( isset( $_SERVER['HTTP_USER_AGENT'] ) && !empty( $_SERVER['HTTP_USER_AGENT'] ) )
    			{
    				$results = $_SERVER['HTTP_USER_AGENT'];
    			}
    		}
    		
    		return $results;
    	}
    	
    	public static function complieRegex( $patterns)
    	{
    		return implode('|', $patterns);
    	}
    	
    	//get user ip address
        public static function user_ip()
        {
        	return tebravo_GetRealIP();
        }
        
        //check if user uses proxy
        public static function is_proxy()
        {
        	if ( $GLOBALS['pagenow'] === 'wp-login.php' 
        			|| $GLOBALS['pagenow'] === tebravo_utility::get_option( TEBRAVO_DBPREFIX.'wplogin_slug' ) ) {
        			$for_login = true;	
        			}
        	$sockport = false;
        	$proxyports = array(
        			80,
        			8080,
        			6588,
        			8000,
        			3128,
        			3127,
        			3124,
        			1080,
        			553,
        			554
        	);
        	
        	for ($i = 0; $i <= count($proxyports); $i++) {
        		
        		if(@fsockopen($_SERVER['REMOTE_ADDR'],$proxyports[$i],$errstr,$errno,0.5))
        		{
        			$sockport=true;
        		}
        	}
        	
        	$proxy_headers = array(
        			'HTTP_VIA',
        			'HTTP_X_FORWARDED_FOR',
        			'HTTP_FORWARDED_FOR',
        			'HTTP_X_FORWARDED',
        			'HTTP_FORWARDED',
        			'HTTP_CLIENT_IP',
        			'HTTP_FORWARDED_FOR_IP',
        			'VIA',
        			'X_FORWARDED_FOR',
        			'FORWARDED_FOR',
        			'X_FORWARDED',
        			'FORWARDED',
        			'CLIENT_IP',
        			'FORWARDED_FOR_IP',
        			'HTTP_PROXY_CONNECTION'
        	);
        	
        	foreach($proxy_headers as $x){
        		if (isset($_SERVER[$x])
        				|| $sockport == true)
        		{
        			return true;
        		}
        		return false;
        	}
        	
        	
        }
        
        //get user browser information
        public static function getBrowser()
        {
        	$u_agent = self::constants( 'userAgent' );
        	//echo $u_agent;
        	$bname = 'Unknown';
        	$platform = 'Unknown';
        	$version= "";
        	
        	//First get the platform?
        	if (preg_match('/linux/i', $u_agent)) {
        		$platform = 'linux';
        	}
        	elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
        		$platform = 'mac';
        	}
        	elseif (preg_match('/windows|win32/i', $u_agent)) {
        		$platform = 'windows';
        	}
        	$ub='';
        	// Next get the name of the useragent yes seperately and for good reason
        	if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent))
        	{
        		$bname = 'Internet Explorer';
        		$ub = "MSIE";
        	}
        	elseif(preg_match('/Firefox/i',$u_agent))
        	{
        		$bname = 'Mozilla Firefox';
        		$ub = "Firefox";
        	}
        	elseif(preg_match('/Chrome/i',$u_agent))
        	{
        		$bname = 'Google Chrome';
        		$ub = "Chrome";
        	}
        	elseif(preg_match('/Safari/i',$u_agent))
        	{
        		$bname = 'Apple Safari';
        		$ub = "Safari";
        	}
        	elseif(preg_match('/OPR/i',$u_agent))
        	{
        		$bname = 'Opera';
        		$ub = "OPR";
        	}
        	elseif(preg_match('/Netscape/i',$u_agent))
        	{
        		$bname = 'Netscape';
        		$ub = "Netscape";
        	}
        	
        	// finally get the correct version number
        	$known = array('Version', $ub, 'other');
        	$pattern = '#(?<browser>' . join('|', $known) .
        	')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
        	if (!preg_match_all($pattern, $u_agent, $matches)) {
        		// we have no matching number just continue
        	}
        	
        	// see how many we have
        	$i = count($matches['browser']);
        	if ($i != 1) {
        		//we will have two since we are not using 'other' argument yet
        		//see if version is before or after the name
        		if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
        			$version= $matches['version'][0];
        		}
        		else {
        			$varsion = '';
        			if( isset( $matches['version'][1] ))
        			{
        				$version= $matches['version'][1];
        			}
        			
        		}
        	}
        	else {
        		$version= $matches['version'][0];
        	}
        	
        	// check if we have a number
        	if ($version==null || $version=="") {$version="?";}
        	
        	include_once TEBRAVO_DIR.'/includes/tebravo.browser.php';
        	if( class_exists( 'tebravo_Browser' ) )
        	{
        		$browser = new tebravo_Browser();
        		$bname = $browser->getBrowser();
        		$version = $browser->getVersion();
        	}
        	
        	return array(
        			'userAgent' => $u_agent,
        			'name'      => $bname,
        			'version'   => $version,
        			'platform'  => $platform,
        			'pattern'    => $pattern
        	);
        }
        
        //get user device type using Mobile_Detect class
        //https://github.com/serbanghita/Mobile-Detect
        public static function device()
        {
        	require_once TEBRAVO_DIR.'/includes/tebravo.mobile.php';
        	
        	if( class_exists( 'Mobile_Detect' ) ){
        		$detect = new Mobile_Detect;
        		$deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'Tablet' : 'Mobile') : 'Computer');
        	
        		return __($deviceType, TEBRAVO_TRANS);
        	}
        }
        
        //get user country name or code using GEOip class
        //https://github.com/maxmind/geoip-api-php
        public static function ip2country( $ip=false, $result="country_code")
        {
        	if(!$ip){
        		$ip = self::user_ip();
        	}
        	
        	if( !$ip || empty( $ip ) ){return ;}
        	//include class file
        	if(! (function_exists('tebravo_geoip_open') && function_exists('tebravo_geoip_country_code_by_addr') && function_exists('tebravo_geoip_country_code_by_addr_v6'))){
        		require_once TEBRAVO_DIR.'/includes/tebravo.geoip.php';
        	}
        	
        	if(function_exists( 'tebravo_geoip_open' ) ){
	        	$gi = tebravo_geoip_open(TEBRAVO_GEOPATH,GEOIP_STANDARD);
	        	if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
	        		if($result == 'country_name'){
	        			$country = tebravo_geoip_country_name_by_addr_v6($gi, $ip);
	        		} else{
	        			$country = tebravo_geoip_country_code_by_addr_v6($gi, $ip);
	        		}
	        	} else {
	        		if($result == 'country_name'){
	        			$country = tebravo_geoip_country_name_by_addr($gi, $ip);
	        		} else {
	        			$country = tebravo_geoip_country_code_by_addr($gi, $ip);
	        		}
	        	}
	        	tebravo_geoip_close($gi);
	        	$res = $country ? $country : '';
	        	return $res;
        	}
        }
        
        //get user ISP
        //Internet Provider Name
        public static function ISP( $ip=false )
        {
        	if( !$ip )
        	{
        		$ip = self::user_ip();
        	}
        	if( !$ip || empty( $ip ) || !filter_var($ip, FILTER_VALIDATE_IP)){return ;}
        	
        	return gethostbyaddr( $ip );
        }
        
        //check if bot
        public static function is_bot( $userAgent=false )
        {
        	$agent = $userAgent ?$userAgent: self::constants( 'userAgent' );
        	
        	//$result = preg_match('/'.self::constants( 'Crawlers' ).'/i', trim($agent), $matches);
        	return (bool) (
        			isset($agent)
        			&& preg_match('/'.implode("|", self::$listCrawlers).'/i',$agent)
        			);
        	//return (bool) $result;
        }
    }
}
?>