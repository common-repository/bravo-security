<?php 

//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if(!class_exists( 'tebravo_htaccess' )){
class tebravo_htaccess{
    
    public $tmpdir="";
    public $backupfolder="";
    public $backupdir="";
    public $init="";
    public $siteurl="";
    public $wp_siteurl="";
    public $prevent_images_start="";
    public $prevent_images_end="";
    public $wplogin_start="";
    public $wplogin_end="";
    public $wpadmin_start="";
    public $wpadmin_end="";
    public $home_root="";
    
    private 
    	$wplogin_slug,
    	$wpadmin_slug,
    	$wpregister_slug;
    
    public function __construct()
    {
        $this->init = new tebravo_init();
        
        $this->backupfolder = TEBRAVO_BACKUPFOLDER;
        $this->backupdir = $this->init->dir."/".$this->backupfolder;
        $this->tmpdir= $this->backupdir."/tmp";
        $this->home_root= tebravo_home_root();
        
        $this->wplogin_slug= trim(esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'wplogin_slug' ) ) );
        $this->wpadmin_slug= trim(esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'wpadmin_slug' ) ) );
        $this->wpregister_slug= trim(esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'wpregister_slug' ) ) );
        
        $this->wp_siteurl = get_home_url();
        $this->siteurl = str_replace("www.", "", tebravo_getDomainUrl(tebravo_selfURL()));
        
        $this->prevent_images_start = "# BRAVO Security BEGIN Prevent Images";
        $this->prevent_images_end = "# BRAVO Security END Prevent Images";
        
        $this->wplogin_start = "# BRAVO Security BEGIN WPLogin";
        $this->wplogin_end = "# BRAVO Security END WPLogin";
        
        $this->wpadmin_start = "# BRAVO Security BEGIN WPAdmin";
        $this->wpadmin_end = "# BRAVO Security END WPAdmin";
        
    }
    
    //Update hotlinking while list
    public function update_prevent_image( $whilelist, $img)
    {
    	$newCode = $this->prevent_images_code($whilelist, $img);
    	echo $newCode; exit;
    	$this->replace_update($this->prevent_images_start, $this->prevent_images_end, $newCode);
    }
    
    //Update write htaccess file for hotlinking
    public function do_prevent_images( $action=false )
    {
    	//take backup to /tmp
    	$file = ABSPATH.".htaccess";
    	if( tebravo_phpsettings::web_server() == 'nginx' )
    	{
    		$file = ABSPATH."nginx.conf";
    	}
    	$this->take_copy( $file );
    	
    	//start writting
    	if(empty($action))
        {
        	$newData = $this->delete_rule($this->prevent_images_start, $this->prevent_images_end, '');
            $this->prevent_images();
        } else {
        	if($action == 'delete')
        	{
        		$string = @file_get_contents($file);
        		
        		$newData = $this->delete_rule($this->prevent_images_start, $this->prevent_images_end, $string);
        		#echo $newData; exit;
        		tebravo_store_free_data($file, ABSPATH, $newData, 'w');
        	}
        }
    }
    
    protected function prevent_images()
    {
    	global $tebravo_admin_notice_error;
    	$file = ABSPATH.".htaccess";
    	$filename = ".htaccess";
    	if( tebravo_phpsettings::web_server() == 'nginx' )
    	{
    		$file = ABSPATH."nginx.conf";
    		$filename= "nginx.conf";
    	}
        #if(!file_exists($file)){$file = "../".$file;}
        
       
        $dir= dirname($file);
        $while_list = tebravo_utility::get_option(TEBRAVO_DBPREFIX.'hotlinks_while_list');
        $img = tebravo_utility::get_option(TEBRAVO_DBPREFIX.'hot_linking_img');
        
        tebravo_store_backup_file($file, $filename, $dir, 'r+', $this->prevent_images_code($while_list, $img));
        if(!file_exists($dir.'/'.$filename))
        {
        	$redirect_to = $this->init->admin_url."-settings&err=05";
        	tebravo_redirect_js($redirect_to);
        }
    }
    
    public function take_copy( $file=false )
    {
    	global $tebravo_admin_notice_error;
        
    	$file_path = ABSPATH.".htaccess";
    	$filename = ".htaccess_".$this->init->security_hash."_".@date('dmy_h_i');
    	if( tebravo_phpsettings::web_server() == 'nginx' )
    	{
    		$file_path= ABSPATH."nginx.conf";
    		$filename = "nginx.conf_".$this->init->security_hash."_".@date('dmy_h_i');
    	}
    	
    	if( !$file ) {$file = $file_path;}
       # if(!file_exists($file)){$file = "../".$file;}
        
        
        $dir= $this->tmpdir;
        
        tebravo_store_backup_file($file, $filename, $dir, 'w');
        
        if(!file_exists($dir.'/'.$filename))
        {
        	$redirect_to = $this->init->admin_url."-settings&err=06";
        	tebravo_redirect_js($redirect_to);
        	exit;
        }
    }
    
    //Delete one rule from htaccess
    protected function delete_rule($beginning, $end, $string) {
    	
    	$beginningPos = strpos($string, $beginning);
    	$endPos = strpos($string, $end);
    	if ($beginningPos !== false && $endPos !== false) {
    		
	    	$textToDelete = substr($string, $beginningPos, ($endPos + strlen($end)) - $beginningPos);
	    	
	    	return str_replace($textToDelete, '', $string);
    	} else {
    		return $string;
    	}
    }
    
    //Prevent hotlinking rule
    protected function prevent_images_code($whilelist, $img)
    {
    	$web_server = tebravo_phpsettings::web_server();
    	
    	$output[] = '';
    	$output[] = $this->prevent_images_start;
       
        if( $web_server == 'nginx' )
        {
        	$output[] = 'location ~* \.(gif|png|jpe?g)$ {';
        	$output[] = 'expires 7d;';
        	$output[] = 'add_header Pragma public;';
        	$output[] = 'add_header Cache-Control "public, must-revalidate, proxy-revalidate";';
        	$rules = 'valid_referers none blocked ';
        } else 
        {
	        $output[] = "<IfModule mod_rewrite.c>";
	        $output[] = "RewriteEngine On";
	        $output[] = "RewriteCond %{HTTP_REFERER} !^$";
	        $output[] = "RewriteCond %{REQUEST_FILENAME} -f";
	        $output[] = "RewriteCond %{REQUEST_FILENAME} \.(gif|jpe?g?|png)$ [NC]";
	        
	        $MU_rules = '';
	        if( function_exists( 'is_multisite') && is_multisite() )
	        {
	        	if ( function_exists( 'wp_get_sites' ) ) {
	        		$sites = wp_get_sites();
	        		
	        		foreach ( $sites as $site ) {
	        			$blog_id = $site['blog_id'];
	        			$blog_details = get_blog_details( $blog_id );
	        			$blog_url = $blog_details->siteurl;
	        			
	        			$MU_rules .= "RewriteCond %{HTTP_REFERER} !^".$this->correct_url($blog_url,false)." [NC]".PHP_EOL;
	        			$MU_rules .= "RewriteCond %{HTTP_REFERER} !^".$this->correct_url($blog_url,true)." [NC]".PHP_EOL;
	        		}
	        	} 
	        } else {
	        	$MU_rules .= "RewriteCond %{HTTP_REFERER} !^".$this->correct_url($this->wp_siteurl,false)." [NC]".PHP_EOL;
	        	$MU_rules .= "RewriteCond %{HTTP_REFERER} !^".$this->correct_url($this->wp_siteurl,true)." [NC]".PHP_EOL;
	        }
	        $output[] = $MU_rules;
	        $rules = '#White List Domains'.PHP_EOL;
        }
        #$output[] = "RewriteCond %{HTTP_REFERER} !^http://(www.)?arabeka.com(/)?.*$ [NC]";
        
        $whitelisted = $whilelist;
        if($whitelisted != '')
        {
        	$n_w_listed = nl2br($whitelisted);
        	$exp = @explode("\n", $n_w_listed);
        	
        	
        	$i=3;
        	$rule_n = '';
        	$rule_n_1= '';
        	foreach($exp as $domain)
        	{
        		$i++;
        		$domain = strip_tags($domain);
        		if(!empty($domain))
        		{
        			if( $web_server == 'nginx' )
        			{
        				$exp_domain = explode(".", $domain);
        				$rules .= '~.'.$exp_domain[0].'. ';
        			} else {
        				$rules .= "RewriteCond %{HTTP_REFERER} !^http://(www.)?".trim(strip_tags($domain))."(/)?.*$ [NC]".PHP_EOL;
        			}
        		}
        	}
        	
        }
        
        $hot_linking_img_def = $img;
        if(!empty($hot_linking_img_def)){$defaultimg = $hot_linking_img_def;}
        else{$defaultimg = "http://i.imgur.com/a0YYDvt.jpg";}
        
        if( $web_server == 'nginx' )
        {
        	$this_domain = tebravo_getDomainUrl(home_url());
        	$exp_this_domain = explode(".", $this_domain);
        	if( filter_var($this_domain, FILTER_VALIDATE_IP))
        	{
        		$rules .= $this_domain.' server_names ~($host);'.PHP_EOL;
        	} else {
        		$rules .= '~.'.$exp_this_domain[0].'. server_names ~($host);'.PHP_EOL;
        	}
        	
        	$output[] = $rules;
        	$output[] = 'if ($invalid_referer) {';
        	$output[] = 'rewrite (.*) '.$defaultimg.' redirect;';
        	$output[] = '}';
        	$output[] = '}';
        } else {
        	$output[] = $rules;
        	$output[] = "RewriteRule \.(jpg|jpeg|png|gif)$ $defaultimg [NC,R,L]";
        	$output[] = "</IfModule>";
        }
       
        $output[] = $this->prevent_images_end;
        $output[] = ' ';
      	
        return implode("\n", $output);
    }
    
    //replace old date with new data or blank area and update file
    //write using tebravo_files class
    public function replace_update($start, $end, $new_updated_data)
    {
    	$file = ABSPATH.".htaccess";
    	if( tebravo_phpsettings::web_server() == 'nginx' )
    	{
    		$file = ABSPATH.'nginx.conf';
    	}
    	
    	//check if exists
    	if( !file_exists( $file ) ){tebravo_files::write($file, '');}
    	
    	//Backup htaccess
    	$this->take_copy();
    	
    	//check if writable
    	if( !is_writable( $file ) )
    	{
    		//error log
    		tebravo_errorlog::errorlog('.htaccess file is not writable.');
    		//die
    		$message = __("File is not writable!", TEBRAVO_TRANS);
    		$message .= "<br /><i>".$file."</i>";
    		tebravo_die(true, $message, false, false);
    	}
    	//continue
    	$string = tebravo_files::read($file);
    	
    	$clearedData = $this->delete_rule($start, $end, $string);
    	$newData = $new_updated_data.$clearedData;
    	#echo $newData; exit;
    	#tebravo_store_free_data($file, ABSPATH, $newData, 'w');
    	tebravo_files::write($file, $newData);
    }
    
    //wplogin htaccess rules
    public function wplogin_rules()
    {
    	if( tebravo_phpsettings::web_server() != 'nginx'){
    		$rules[] = '';
    		$rules[] = $this->wplogin_start;
    		$rules[] = '<IfModule mod_rewrite.c>';
    		$MU_rules = '';
    		$MU_rules .= "RewriteRule ^({$this->home_root})?{$this->wplogin_slug}/?$ {$this->home_root}wp-login.php [QSA,L]".PHP_EOL;
    			if( $this->wpregister_slug!='' )
    			{
    				$MU_rules .= "RewriteRule ^({$this->home_root})?{$this->wpregister_slug}/?$ {$this->home_root}wp-login.php?action=register [QSA,L]".PHP_EOL;
    			}
    		
    		
    		$rules[] = $MU_rules;
    		$rules[] = '</IfModule>';
    		$rules[] = $this->wplogin_end;
    		$rules[] = '';
    	} else {
    		$rules[] = '';
    		$rules[] = $this->wplogin_start;
    		$MU_rules = '';
    	
    			$MU_rules .= "rewrite ^({$this->home_root})?{$this->wplogin_slug}/?$ {$this->home_root}wp-login.php?\$query_string break;";
	    		if( $this->wpregister_slug!='' )
	    		{
	    			$MU_rules .= "rewrite ^({$this->home_root})?{$this->wpregister_slug}/?$ {$this->home_root}wp-login.php?action=register break;";
	    		}
    		
    		$rules[] = $MU_rules;
    		$rules[] = $this->wplogin_end;
    		$rules[] = '';
    	}
    	
    	return implode(PHP_EOL, $rules);
    }
    
    //wpadmin htaccess rules
    public function wpadmin_rules()
    {
    	if( tebravo_phpsettings::web_server() != 'nginx'){
    		$rules[] = '';
    		$rules[] = $this->wpadmin_start;
    		$rules[] = "RewriteRule ^({$this->home_root})?{$this->wpadmin_slug}/?$ {$this->home_root}wp-admin [QSA,L]";
    		$rules[] = $this->wpadmin_end;
    		$rules[] = '';
    	} else {
    		$rules[] = '';
    		$rules[] = $this->wpadmin_start;
    		$rules[] = "rewrite ^({$this->home_root})?{$this->wpadmin_slug}/?$ {$this->home_root}wp-admin\$query_string break;";
    		$rules[] = $this->wpadmin_end;
    		$rules[] = '';
    	}
    	
    	return implode(PHP_EOL, $rules);
    }
    
    //Correct the WP URL to set http(s) and/or www
    public function correct_url($url, $www=false)
    {
    	if(strpos($url, "http") === true){
    		$https_str = substr($url,0,5);
    		
    		if($https_str == "https")
    		{
    			$http_str = "https://";
    		} else {
    			$http_str = "http://";
    		}
    		
    	} else {
    		$http_str = "http://";
    	}
    	
    	$string = str_replace("www.", "", $url);
    	$string = str_replace($http_str, "", $string);
    	
    	if($www)
    	{
    		$urlOutput = $http_str."www.".$string;
    	} else {
    		$urlOutput = $http_str.$string;
    	}
    	
    	return $urlOutput;
    	
    }
    
}
}
?>