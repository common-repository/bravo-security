<?php 
/**
 * CLASS: BRAVO.BAD_REQUESTS
 * The bad requests and query filter lists for Wordpress.
 * @since 1.0
 * Copyrights (C) 2017 Technoyer Solutions Ltd. <support@technoyer.com>
 */

//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if(!class_exists( 'tebravo_query_filter' ))
{
    class tebravo_query_filter
	{
		public static $bad_queries_uri = array(
	        'eval\(', 
	        'UNION(.*)SELECT', 
	        '\(null\)', 
	        'base64_', 
	        '\/localhost', 
	        '\%2Flocalhost', 
	        '\/pingserver', 
	        '\/config\.', 
	        '\/wwwroot', 
	        '\/makefile', 
	        'crossdomain\.', 
	        'proc\/self\/environ', 
	        'etc\/passwd', 
	        '\/https\:', 
	        '\/http\:', 
	        '\/ftp\:', 
	        '\/cgi\/', 
	        '\.cgi', 
	        '\.exe', 
	        '\.sql', 
	        '\.ini', 
	        '\.dll', 
	        '\.asp', 
	        '\.jsp', 
	        '\/\.bash', 
	        '\/\.git', 
	        '\/\.svn', 
	        '\/\.tar', 
	        ' ', 
	        '\<', 
	        '\>', 
	        '\/\=', 
	        '\.\.\.', 
	        '\+\+\+', 
	        '\/&&', 
	        '\/Nt\.', 
	        '\;Nt\.', 
	        '\=Nt\.', 
	        '\,Nt\.', 
	        '\.exec\(', 
	        '\)\.html\(', 
	        '\{x\.html\(', 
	        '\(function\(', 
	        '\.php\([0-9]+\)', 
	        '(benchmark|sleep)(\s|%20)*\('
	    );
	    
	    public static $bad_queries = array(
	        '\.\.\/', 
	        '127\.0\.0\.1', 
	        'localhost', 
	        'loopback', 
	        '\%0A', 
	        '\%0D', 
	        '\%00', 
	        '\%2e\%2e', 
	        'input_file', 
	        'execute', 
	        'mosconfig', 
	        'path\=\.', 
	        'mod\=\.', 
	        'wp-config\.php'
	    );
	    
	    public static $bad_user_agents = array(
	        'acapbot', 
	        'binlar', 
	        'casper', 
	        'cmswor', 
	        'diavol', 
	        'dotbot', 
	        'finder', 
	        'flicky', 
	        'morfeus', 
	        'nutch', 
	        'planet', 
	        'purebot', 
	        'pycurl', 
	        'semalt', 
	        'skygrid', 
	        'snoopy', 
	        'sucker', 
	        'turnit', 
	        'vikspi', 
	        'zmeu'
	        );
    }
}