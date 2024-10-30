<?php 

//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

//Redirect with JS
if(!function_exists( 'tebravo_redirect_js'))
{
	/**
	 * becuase of the errors which got on some fastCGI setups, the redirect will be wiout headers
	 * @param $redirect_to the url to be redirect to
	 */
    function tebravo_redirect_js( $redirect_to , $safe=false)
    {
    	error_reporting( 0 );
    	@ini_set( 'display_errors', 0 );
    	
    	if($safe == false){
	    	if( wp_redirect( $redirect_to ) != true )
	        ?>
			<script>
			window.location.href="<?php echo strip_tags( $redirect_to );?>";
			</script>
			<?php
    	} else {
    		$location = wp_sanitize_redirect($redirect_to);
    		
    		$location = wp_validate_redirect($location, admin_url());
    		
    		if( wp_redirect( $location , 302) != true )
    		?>
			<script>
			window.location.href="<?php echo esc_url( $location );?>";
			</script>
			<?php
    	}
    }
}

if(! function_exists( 'tebravo_clear_wp_cookie' ) )
{
	/**
	 * clear all WP cookies for user using wp_clear_auth_cookie() or its source
	 */
	function tebravo_clear_wp_cookie()
	{
		if(! function_exists( 'wp_clear_auth_cookie' ) )
		{
			do_action( 'clear_auth_cookie' );
			
			setcookie( AUTH_COOKIE,        ' ', time() - YEAR_IN_SECONDS, ADMIN_COOKIE_PATH,   COOKIE_DOMAIN );
			setcookie( SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, ADMIN_COOKIE_PATH,   COOKIE_DOMAIN );
			setcookie( AUTH_COOKIE,        ' ', time() - YEAR_IN_SECONDS, PLUGINS_COOKIE_PATH, COOKIE_DOMAIN );
			setcookie( SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, PLUGINS_COOKIE_PATH, COOKIE_DOMAIN );
			setcookie( LOGGED_IN_COOKIE,   ' ', time() - YEAR_IN_SECONDS, COOKIEPATH,          COOKIE_DOMAIN );
			setcookie( LOGGED_IN_COOKIE,   ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH,      COOKIE_DOMAIN );
			
			// Old cookies
			setcookie( AUTH_COOKIE,        ' ', time() - YEAR_IN_SECONDS, COOKIEPATH,     COOKIE_DOMAIN );
			setcookie( AUTH_COOKIE,        ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN );
			setcookie( SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH,     COOKIE_DOMAIN );
			setcookie( SECURE_AUTH_COOKIE, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN );
			
			// Even older cookies
			setcookie( USER_COOKIE, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH,     COOKIE_DOMAIN );
			setcookie( PASS_COOKIE, ' ', time() - YEAR_IN_SECONDS, COOKIEPATH,     COOKIE_DOMAIN );
			setcookie( USER_COOKIE, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN );
			setcookie( PASS_COOKIE, ' ', time() - YEAR_IN_SECONDS, SITECOOKIEPATH, COOKIE_DOMAIN );
		} else {
			wp_clear_auth_cookie();
		}
	}
}

if(! function_exists( 'tebravo_home_root' ) ){
	/**
	 * Get WP home root for htaccess rewrite
	 * @return home root
	 */
	function tebravo_home_root( $site_url=false ) {
		
		if( !$site_url ){ $site_url = site_url(); }
		$home = parse_url( $site_url );
		
		if ( isset( $home['path'] ) ) {
			$home= trailingslashit( $home['path'] );
		} else { $home= '/'; }
		
		return $home;
		
	}
}

if(!function_exists('tebravo_selfURL')){
    /**
     * to get the current URL
     *
     * @return string
     */
    function tebravo_selfURL()
    {
    	if(isset($_SERVER["SERVER_NAME"]) ) {
	    	$s = empty($_SERVER["HTTPS"]) ? '' : ($_SERVER["HTTPS"] == "on") ? "s" : "";
	    	$protocol = tebravo_strleft(strtolower($_SERVER["SERVER_PROTOCOL"]), "/").$s;
	    	$port = ($_SERVER["SERVER_PORT"] == "80") ? "" : (":".$_SERVER["SERVER_PORT"]);
	    	return $protocol."://".$_SERVER['SERVER_NAME'].$port.$_SERVER['REQUEST_URI']; 
    	}
    }
}
if(!function_exists('tebravo_strleft')){
    /**
     * to fint and cut string
     *
     * @param 1st String $s1
     * @param 2nd String $s2
     * @return output
     */
    function tebravo_strleft($s1, $s2)
    {
        return substr($s1, 0, strpos($s1, $s2));
    }
}
if(!function_exists('tebravo_getDomainUrl')){
    /**
     * Get domain name from URL
     *
     * @param full link $url
     * @return string the domain name with ltd
     */
    function tebravo_getDomainUrl($url)
    {
        $domain= preg_replace(
            array(
                '~^https?\://~si' ,// strip protocol
                '~[/:#?;%&].*~',// strip port, path, query, anchor, etc
                '~\.$~',// trailing period
            ),
            '',$url);
            
            if(preg_match('#^www.(.*)#i',$domain))
            {
                $domain=preg_replace('#www.#i','',$domain);
            }
            return $domain;
    }
}

//Format percentage // taking care of 0
if(!function_exists('tebravo_format_percentage'))
{
    function tebravo_format_percentage( $current, $total ) 
    {
        return ( $total > 0 ? round( ( $current / $total ) * 100, 2 ) : 0 ) . '%';
    }
}

if(!function_exists( 'tebravo_ago' ) )
{
	function tebravo_ago($tm,$rcs = 0) {
		
		$cur_tm = time(); $dif = $cur_tm-$tm;
		$pds = array(__("seconds", TEBRAVO_TRANS)
				,__("minutes", TEBRAVO_TRANS)
				,__("hours", TEBRAVO_TRANS)
				,__("days", TEBRAVO_TRANS)
				,__("weeks", TEBRAVO_TRANS)
				,__("months", TEBRAVO_TRANS)
				,__("years", TEBRAVO_TRANS)
				,'decade');
		$lngh = array(1,60,3600,86400,604800,2630880,31570560,315705600);
		for($v = sizeof($lngh)-1; ($v >= 0)&&(($no = $dif/$lngh[$v])<=1); $v--); if($v < 0) $v = 0; $_tm = $cur_tm-($dif%$lngh[$v]);
		
		$no = floor($no); if($no <> 1) $pds[$v] .=''; $x=sprintf("%d %s ",$no,$pds[$v]);
		if(($rcs == 1)&&($v >= 1)&&(($cur_tm-$_tm) > 0)) $x .= tebravo_ago($_tm);
		return $x;
	}
}

if( !function_exists( 'tebravo_encodeString' ) ) {
	function tebravo_encodeString($str, $secret)
	{
		$encyrpt = new Encriptor($secret);
		return $encyrpt -> encrypt($str);
	}
}

if( !function_exists( 'tebravo_decodeString' ) ) {
	function tebravo_decodeString($str, $secret)
	{
		$encyrpt = new Encriptor($secret);
		return $encyrpt -> decrypt($str);
	}
}

if(!function_exists( 'tebravo_GetRealIP' )){
	function tebravo_GetRealIP()
	{
		if( isset( $_SERVER['REMOTE_ADDR'] ) )
		{
			if (!empty($_SERVER['HTTP_CLIENT_IP']))   
			{
				$ip=$_SERVER['HTTP_CLIENT_IP'];
			}
			elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR']))   
			{
				$ip=$_SERVER['HTTP_X_FORWARDED_FOR'];
			}
			else
			{
				$ip=$_SERVER['REMOTE_ADDR'];
			}
			
			return $ip;
		}
	}
}

if( !function_exists( 'tebravo_format_no' ) )
{
	function tebravo_format_no($numberf){
		
		$mx=floor(strlen($numberf)/3);
		$sx2=floor(strlen($numberf)%3);
		$sx="";
		$ix=0;
		$vx=0;
		$zx=0;
		$len=strlen($numberf);
		if($sx2 >0 && $len >3){
			$sx.=substr($numberf,0,$sx2).",";
			$mx--;
			if($mx==0){
				$sx.=substr($numberf,1,3);
				$zx=1;
				
			}
			
			$ix=1;
			$vx=1;
		}
		while($mx >0 && $len>3  ){
			$zx=1;
			if($vx==0 && $mx==1)
				$sx.=substr($numberf,$sx2,3);
				else
					$sx.=substr($numberf,$sx2,3).",";
					$mx--;
					if($vx==0){
						$ix++;
						$sx2=$ix*3;
					}
					else
					{
						$sx2=$ix*3;
						$ix++;
					}
					if($mx==0)
						$sx.=substr($numberf,$sx2,3);
		}
		
		
		if($zx==0)
			return $numberf;
			else
				return $sx;
	}
}

if( !function_exists( 'tebravo_ConvertBytes' ) ){
	function tebravo_ConvertBytes($number)
	{
		$len = strlen($number);
		if($len < 4)
		{
			return sprintf("%d b", $number);
		}
		if($len >= 4 && $len <=6)
		{
			return sprintf("%0.2f Kb", $number/1024);
		}
		if($len >= 7 && $len <=9)
		{
			return sprintf("%0.2f Mb", $number/1024/1024);
		}
		
		return sprintf("%0.2f Gb", $number/1024/1024/1024);
		
	}
}
?>