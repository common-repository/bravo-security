<?php 
/**
 * The main class for all settings and actions
 * It will install/uninstall main/global settings/information
 * It will add global menu, action links and admin bar
 * 
 * Copyrights (C) 2017 Technoyer Solutions Ltd. <support@technoyer.com>
 */
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !class_exists( 'tebravo_init' ))
{
	class tebravo_init {
		//Instance
		protected static $_instance = null;
		
		//Version Info
		public $verion = "1.0";
		public $version_type = "free";
		
		//Direction Info
		public $path = "";
		public $dir = "";
		public $admin_url = "";
		public $adminbar_icon = "";
		public $menu_icon = "";
		public $docs_url = "";
		public $donate_url = "";
		
		//Hooks
		public $hooks = "";
		
		public $security_hash= "";
		public $backupfolder= "";
		public $backupdir= "";
		public $htaccess= "";
		public $nginx_docs= "";
		
		//dashboard templates
		public $dashboard_temps_dir;
		//Setup onle one instance
		public static function init() {
			
			static $instance = null;
			
			if ( ! $instance ) {
				$instance = new tebravo_init;
			}
			return $instance;
		}
		
		//Go Construct
		public function __construct()
		{
			//Version Info
			if(defined( 'TEBRAVO_VERSION' )){ $this->version = TEBRAVO_VERSION; }
			if(defined( 'TEBRAVO_VERSIONTYPE' )){ $this->version_type = TEBRAVO_VERSIONTYPE; }
			
			//Direction Info
			if(defined( 'TEBRAVO_PATH' )){ $this->path = TEBRAVO_PATH; } else { $this->path = __FILE__; }
			if(defined( 'TEBRAVO_DIR' )){ $this->dir = TEBRAVO_DIR; } else { $this->dir = dirname( __FILE__ ); }
			$this->admin_url = admin_url('admin.php?page=' . esc_js( TEBRAVO_SLUG ));
			if( is_multisite() )
			{
				$this->admin_url = network_admin_url('admin.php?page=' . esc_js( TEBRAVO_SLUG ));
			}
			$this->adminbar_icon = plugins_url( 'assets/img/bravo-16-16.png' , $this->path );
			$this->menu_icon = plugins_url( 'assets/img/bravo-16-16.png' , $this->path );
			
			//secure download file
			$this->download_file = $this->dir."/".$this->security_hash.".php";
			
			//Hooks
			$this->hooks = $this->get_hooks();

			//Backups
			$this->backupfolder = TEBRAVO_BACKUPFOLDER;
			$this->backupdir = $this->dir."/".$this->backupfolder;
			
			//Activate Plugin
			//register_activation_hook( TEBRAVO_PATH , array($this, 'activate') );
			
			//deActivate Plugin
			//register_deactivation_hook( TEBRAVO_PATH , array($this, 'de_activate'));
			
			//Uninstall Plugin
			//register_uninstall_hook( TEBRAVO_PATH , 'tebravo_byebye' );
			
			//add_action('plugins_loaded', array($this, 'install'));
			
			//Add Admin Bar
			if( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'adminbar' ) == 'checked' )
			{
				add_action( 'admin_bar_menu' , array( $this , 'adminbar' ) , 999);
				add_action( 'admin_bar_menu' , array( $this , 'adminbar_parent' ) , 999);
			}
			
			//Add Menu
			$ms_hook = is_multisite() ? 'network_' : '';
			if( is_admin() ){add_action( $ms_hook.'admin_menu' , array( $this , 'menu' ));}
			
			//URLS
			/*
			if( $this->version_type != 'PRO')
			{
				$this->docs_url = "http://bravo.technoyer.com/documentationt.html";
			} else {
				$this->docs_url = "http://bravo.technoyer.com/documentation.html";
			}
			*/
			$this->docs_url = "http://bravo.technoyer.com/wiki/index";
			//nginx docs
			$this->nginx_docs= "http://bravo.technoyer.com/wiki/nginx";
			
			//donate URL
			$this->donate_url = "http://bravo.technoyer.com";
			if( defined( 'TEBRAVO_DONATE_URL' ) )
			{
				$this->donate_url = TEBRAVO_DONATE_URL;
			}
			
			//Include CSS and JS files
			add_action ( 'admin_enqueue_scripts' , array ($this , 'include_scrips_and_css' ) );
			add_action ( 'wp_enqueue_scripts' , array ($this , 'include_scrips_and_css_frontend' ) );
			
			$this->security_hash = trim(tebravo_utility::get_option(TEBRAVO_DBPREFIX."security_hash"));
			
			//CRONJOBs Here
			//Add Actions for schedules
			if(function_exists( '_tebravo_tasks_list' )){
    			foreach (_tebravo_tasks_list() as $key=>$value)
    			{
    			    $action_hook = TEBRAVO_DBPREFIX.$key;
    			    if(function_exists( $action_hook.'_callback'))
    			    {
    			        add_action( $action_hook, $action_hook.'_callback' );
    			    }
    			}
			}
			
			//run auto update through filter
			$this->auto_updates();
			
			//run throw init, avoid headers problems
			add_action( 'init', array( $this, 'download_file_h' ) );
			add_action( 'init', array( $this, 'tebravo_setcookie' ));
			
			//dashboard templates
			$this->dashboard_temps_dir = TEBRAVO_DIR.'/assets/html/dashboard/';
			
			//hide WP version
			if( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'hidewpversion') == 'checked' )
			{
				add_filter('style_loader_src', array( $this, 'replace_version'));
				add_filter('script_loader_src', array( $this, 'replace_version'));
			}
			
			add_action('admin_head', array($this, 'installer'));
			add_action('admin_head', array($this, 'noti_admin_site_off'));
			
		}
		
		
		//site closed notifications
		public function noti_admin_site_off()
		{
			$development_mode= trim( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'development_mode'));
			$maintenance_mode= trim( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'maintenance_mode'));
			
			if( $development_mode=='checked'
					|| $maintenance_mode=='checked' )
			{
				$img_alert = plugins_url('/assets/img/050.png', TEBRAVO_PATH);
				?>
				<div class='tebravo_little_alert'><img src='<?php echo $img_alert;?>'>
				<?php echo __("Site Closed!", TEBRAVO_TRANS);?>
				</div>
				<?php
			}
		}
		
		public function installer()
		{
			
			if( isset($_GET['action']) && $_GET['action'] == 'setup-'.TEBRAVO_SLUG )
			{
				include_once TEBRAVO_DIR.'/includes/tebravo.installer.php';
				
				if( class_exists('tebravo_install_wizard') )
				{
					$helper = new tebravo_html();
					$helper->header(__("BRAVO Setup Wizard", TEBRAVO_TRANS), false, 'setup.png');
					$output[] = "<div style='min-height:110px;'>";
					$wizard = new tebravo_install_wizard();
					$html = '';
					
					$steps_array = array( "requirements", "constants", "permissions", "finish" );
					$installed_option = trim( tebravo_utility::get_bloginfo( TEBRAVO_DBPREFIX.'installed' ) );
					if( $installed_option !='installed' && $installed_option != 'no')
					{
						if( isset( $_GET['bravo-step']) && !empty( $_GET['bravo-step'] ) 
								&& !in_array($_GET['bravo-step'], $steps_array) )
						{
							tebravo_redirect_js( $helper->init->admin_url.'&action=setup-'.TEBRAVO_SLUG);
							exit;
						}
						
						if( isset( $_GET['bravo-step']) 
								&& $_GET['bravo-step'] != $installed_option 
								&& in_array( $installed_option, $steps_array))
						{
							tebravo_redirect_js( $helper->init->admin_url.'&action=setup-'.TEBRAVO_SLUG.'&bravo-step='.$installed_option);
							exit;
						}
					}
					
					if( empty( $_GET['bravo-step'] ) )
					{
						$output[] = $wizard->wizard( 1 );
						$html .= $wizard->welcome();
					} else if( isset( $_GET['bravo-step'] ) && $_GET['bravo-step'] == 'requirements' )
					{
						$output[] = $wizard->wizard( 2 );
						$html .= $wizard->requirements();
					} else if( isset( $_GET['bravo-step'] ) && $_GET['bravo-step'] == 'constants' )
					{
						$output[] = $wizard->wizard( 3 );
						$html .= $wizard->constants();
					} else if( isset( $_GET['bravo-step'] ) && $_GET['bravo-step'] == 'permissions' )
					{
						$output[] = $wizard->wizard( 4 );
						$html .= $wizard->permissions();
					} else if( isset( $_GET['bravo-step'] ) && $_GET['bravo-step'] == 'finish' )
					{
						$output[] = $wizard->wizard( 5 );
						$html .= $wizard->finish();
					} else {
						$output[] = $wizard->wizard( 1 );
					}
					
					//$output[] = "<br /><br /><br /><br /><center><h3>".__("BRAVO Setup Wizard", TEBRAVO_TRANS)."</h3></center>";
					$output[] = "</div>";
					
					echo implode("\n", $output);
					echo $html;
					$helper->footer();
					exit;
				}
			}
			
		}
		
		public function get_option( $option_name, $default=false )
		{
			if( function_exists( 'tebravo_utility::get_option' ) ){return tebravo_utility::get_option( $option_name, $default);}
			
			if( function_exists( 'is_multisite') && is_multisite() )
			{
				return get_site_option( $option_name, $default );
			}
			
			return get_option( $option_name, $default );
		}
		public function activate()
		{
			//set_transient(TEBRAVO_DBPREFIX.'redirect', 1, 60*60);
			do_action('bravo-security_activate');
		}
		
		public function de_activate()
		{
			do_action('bravo-security_deactivate');
		}
		
		public function install()
		{
			
			if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'security_hash' ) )
			{
				tebravo_install();
			}
		}
		
		
		//replace current WP version from scripts and styles URL
		public function replace_version($url) {
			return preg_replace_callback("/([&;\?]ver)=(.+?)(&|$)/", "tebravo_init::replace_version_callback", $url);
		}
		//callback for prev function
		public static function replace_version_callback($matches) {
			global $wp_version;
			return $matches[1] . '=' . ($wp_version === $matches[2] ? wp_hash($matches[2]) : $matches[2]) . $matches[3];
		}
		
		//automatic updates for plugins and themes
		public function auto_updates()
		{
			//add filter to auto update plugins
			if(trim(esc_html(tebravo_utility::get_option( TEBRAVO_DBPREFIX.'auto_updates_plugins' ))) == 'checked')
			{
				add_filter( 'auto_update_plugin', '__return_true' );
			}
			
			//add filter to auto update themes
			if(trim(esc_html(tebravo_utility::get_option( TEBRAVO_DBPREFIX.'auto_updates_themes' ))) == 'checked')
			{
				add_filter( 'auto_update_theme', '__return_true' );
			}
		}
		
		//get file permissions
		public function file_perms( $file )
		{
			if(strpos(@ini_get('disable_functions'), 'fileperms') === false){
				return substr(sprintf('%o', fileperms( $file )), -4);
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
		
		//using ini_set
		public function set_ini_value($option, $value)
		{
			if(strpos(@ini_get('disable_functions'), 'ini_set') === false){
				@ini_set($option, $value);
			}
		}
		
		//call htaccess class
		public function get_htaccess_class()
		{
			include 'tebravo.htaccess.php';
			$this->htaccess = new tebravo_htaccess();
		}
		
		//download file through headers
		public function download_file_h()
		{
			if( !empty( $_GET['tebravo_file'] ) ):
			$tebravo_file = trim( esc_html( esc_js( $_GET['tebravo_file'] ) ) );
			
			$file = tebravo_decodeString( $tebravo_file, $this->security_hash );
			
				if( file_exists( $file ) ):
				
					if( empty( $_GET['_download_nonce'] )
							|| false === wp_verify_nonce( $_GET['_download_nonce'], $this->security_hash.'download-file'))
				{
					return;
				}
					$quoted = basename($file);
					$size   = filesize($file);
					
					@header('Content-Description: File Transfer');
					@header('Content-Type: application/octet-stream');
					@header('Content-Disposition: attachment; filename=' . $quoted);
					@header('Content-Transfer-Encoding: binary');
					@header('Connection: Keep-Alive');
					@header('Expires: 0');
					@header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
					@header('Pragma: public');
					@header('Content-Length: ' . $size);
					
					@readfile($file);
				endif;
			endif;
		}
		
		//Include hooks
		public function get_hooks()
		{
			include_once $this->dir.'/includes/tebravo.hooks.php';
			return new tebravo_hooks();
		}
		//Call css and js
		public function include_scrips_and_css ()
		{
			//if(!$this->get_access('manage_options', true)){return;}
		    //global
		    if( !is_rtl() )
		    {
		        wp_enqueue_style (TEBRAVO_SLUG."_css", plugins_url('/assets/css/bravo.css', TEBRAVO_PATH));
		    } else {
		        wp_enqueue_style (TEBRAVO_SLUG."_css", plugins_url('/assets/css/bravo_rtl.css', TEBRAVO_PATH));
		    }
	        wp_enqueue_style (TEBRAVO_SLUG."_flags_css", plugins_url('/assets/css/flags.css', TEBRAVO_PATH));
	        wp_enqueue_script (TEBRAVO_SLUG."_bravo_scripts_js", plugins_url('/assets/js/scripts.js', TEBRAVO_PATH), '', '', true);
	        wp_enqueue_script (TEBRAVO_SLUG."_bravo_modal_js", plugins_url('/assets/js/modal.js', TEBRAVO_PATH), '', '', false);
	        //installer scripts
	        if( isset($_GET['action']) && $_GET['action'] == 'setup-'.TEBRAVO_SLUG )
	        {
	        	wp_enqueue_style (TEBRAVO_SLUG."_installer_css", plugins_url('/includes/install/style.css', TEBRAVO_PATH));
	        }
	        //tour
	        if( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'tour') != 'done' )
	        {
	        	wp_enqueue_style (TEBRAVO_SLUG."_tour_css", plugins_url('/assets/css/anno.css', TEBRAVO_PATH));
	        	wp_enqueue_script (TEBRAVO_SLUG."_tour_anno_js", plugins_url('/assets/js/anno.js', TEBRAVO_PATH), '', '', false);
	        	wp_enqueue_script (TEBRAVO_SLUG."_tour_scrollview_js", plugins_url('/assets/js/jquery.scrollintoview.min.js', TEBRAVO_PATH), '', '', false);
	        	
	        }
	       // wp_enqueue_script (TEBRAVO_SLUG."_bravo_installer_script_js", plugins_url('/includes/install/script.js', TEBRAVO_PATH), '', '', true);
	        
		}
		
		public function include_scrips_and_css_frontend()
		{
			wp_enqueue_style (TEBRAVO_SLUG."_css", plugins_url(TEBRAVO_SLUG.'/assets/css/frontend.css'));
		}
		
		//Menu
		public function menu()
		{
			if( true == current_user_can( 'manage_options' ) )
			{
				add_menu_page(__('Bravo Security Dashboard', TEBRAVO_TRANS),
						__('BRAVO Security', TEBRAVO_TRANS),
						'manage_options',
						TEBRAVO_SLUG,
						'',
						$this->menu_icon);
				
				//Dashboard
				add_submenu_page(TEBRAVO_SLUG,
						__('Bravo Security Dashboard',TEBRAVO_TRANS),
						__('Dashboard',TEBRAVO_TRANS),
						'manage_options',
						TEBRAVO_SLUG,
						array ($this,'dashboard'));
				
				//AntiVirus
				add_submenu_page(TEBRAVO_SLUG,
						__('AntiVirus',TEBRAVO_TRANS),
						__('Antivirus',TEBRAVO_TRANS),
						'manage_options',
						TEBRAVO_SLUG.'-antivirus',
						array ($this,'dashboard_antivirus'));
				
				//BruteForce
				add_submenu_page(TEBRAVO_SLUG,
						__('Brute Force Attack Protection',TEBRAVO_TRANS),
						__('Anti Brute Force',TEBRAVO_TRANS),
						'manage_options',
						TEBRAVO_SLUG.'-bruteforce',
						array ($this,'dashboard_bruteforce'));
				
				//FireWall
				add_submenu_page(TEBRAVO_SLUG,
						__('Firewall & Rules',TEBRAVO_TRANS),
						__('Firewall & Rules',TEBRAVO_TRANS),
						'manage_options',
						TEBRAVO_SLUG.'-firewall',
						array ($this,'dashboard_firewall'));
				
				//PHPSecurity
				/*add_submenu_page(TEBRAVO_SLUG,
						__('PHP Security',TEBRAVO_TRANS),
						__('PHP Security',TEBRAVO_TRANS),
						'manage_options',
						TEBRAVO_SLUG.'-phpsecurity',
						array ($this,'dashboard_php'));
				*/
				//HouseKeeping
				add_submenu_page(TEBRAVO_SLUG,
						__('Wordpress Housekeeping',TEBRAVO_TRANS),
						__('Housekeeping',TEBRAVO_TRANS),
						'manage_options',
						TEBRAVO_SLUG.'-housekeeping',
						array ($this,'dashboard_housekeeping'));
				
				//Backups
				add_submenu_page(TEBRAVO_SLUG,
						__('Database Backups',TEBRAVO_TRANS),
						__('Database Backups',TEBRAVO_TRANS),
						'manage_options',
						TEBRAVO_SLUG.'-backups',
						array ($this,'dashboard_backup'));
				
				//WPCONFIG
				add_submenu_page(TEBRAVO_SLUG,
						__('WPConfig Tweak Settings',TEBRAVO_TRANS),
						__('WPConfig Tweak',TEBRAVO_TRANS),
						'manage_options',
						TEBRAVO_SLUG.'-wconfig',
						array ($this,'dashboard_wpconfig'));
				
				//WPADMIN
				add_submenu_page(TEBRAVO_SLUG,
						__('WPAdmin Security',TEBRAVO_TRANS),
						__('WPAdmin Security',TEBRAVO_TRANS),
						'manage_options',
						TEBRAVO_SLUG.'-wadmin',
						array ($this,'dashboard_wpadmin'));
				
				//reCaptcha Settings
				add_submenu_page(TEBRAVO_SLUG,
						__('reCAPTCHA Settings',TEBRAVO_TRANS),
						__('reCAPTCHA',TEBRAVO_TRANS),
						'manage_options',
						TEBRAVO_SLUG.'-recaptcha',
						array ($this,'dashboard_recaptcha'));
				
				//Cronjobs
				add_submenu_page(TEBRAVO_SLUG,
						__('Bravo Security Cronjobs',TEBRAVO_TRANS),
						__('Cronjobs (Schedules)',TEBRAVO_TRANS),
						'manage_options',
						TEBRAVO_SLUG.'-cronjobs',
						array ($this,'dashboard_cronjobs'));
				
				//Traffic Tracker
				add_submenu_page(TEBRAVO_SLUG,
						__('Traffic Tracker',TEBRAVO_TRANS),
						__('Traffic Tracker',TEBRAVO_TRANS),
						'manage_options',
						TEBRAVO_SLUG.'-traffic',
						array ($this,'dashboard_traffic'));
				
				//Email Watching
				add_submenu_page(TEBRAVO_SLUG,
						__('Mail Watching',TEBRAVO_TRANS),
						__('Mail Watching',TEBRAVO_TRANS),
						'manage_options',
						TEBRAVO_SLUG.'-mail',
						array ($this,'dashboard_mail'));
				
				//Log
				add_submenu_page(TEBRAVO_SLUG,
						__('Log Watching',TEBRAVO_TRANS),
						__('Log Watching',TEBRAVO_TRANS),
						'manage_options',
						TEBRAVO_SLUG.'-logwatch',
						array ($this,'dashboard_logwatch'));
				//ErrorPages
				add_submenu_page(TEBRAVO_SLUG,
						__('Error Pages',TEBRAVO_TRANS),
						__('Error Pages',TEBRAVO_TRANS),
						'manage_options',
						TEBRAVO_SLUG.'-error_pages',
						array ($this,'dashboard_errorpages'));
				
				//Settings
				add_submenu_page(TEBRAVO_SLUG,
						__('Bravo Security Settings',TEBRAVO_TRANS),
						__('Settings',TEBRAVO_TRANS),
						'manage_options',
						TEBRAVO_SLUG.'-settings',
						array ($this,'settings'));
				
				do_action(TEBRAVO_DBPREFIX.'admin_menu');
				
			}
		}
		
		/**
		 * update last active for users
		 * @param WP $user
		 * @param string $username
		 * @param string $password
		 */
		public function authenticate( $user, $username=false, $password=false)
		{
			$user = wp_get_current_user();
			$user_id = $user->ID;
			$meta_key = TEBRAVO_DBPREFIX.'last_active';
			
			if( $user_id > 0 ){
				
				update_user_meta($user_id, $meta_key, time());
			}
		}
		
		/**
		 * The Plugin Main Dashboard
		 */
		public function dashboard()
		{
			
			$template = tebravo_dashboard::get_template();
		    $template_file = $this->dashboard_temps_dir.$template.'/index.php';
		    //echo $template_file;
		    if( file_exists( $template_file ) )
		    {
		    	include_once $template_file;
		    	
		    	//confirm class
		    	if( !class_exists( 'tebravo_dashboard_template' ) ){
		    		echo $this->print_errors(016);
		    		tebravo_errorlog::errorlog(016);
		    		exit;
		    	}
		    	//continue
		    	$dashboard = new tebravo_dashboard_template();
		    	$dashboard->enqueue();
		    	$dashboard->dashboard();
		    } else {
		    	echo $this->print_errors(016);
		    	tebravo_errorlog::errorlog(016);
		    }
		    
		    
		}
	
		//Hooks Dashboards
		public function dashboard_cronjobs(){ if(class_exists( 'tebravo_cronjobs' )){tebravo_tour::cronjobs();$cronjobs = new tebravo_cronjobs(); $cronjobs->dashboard();}}
		public function dashboard_wpconfig(){ if(class_exists( 'tebravo_wconfig' )){tebravo_tour::wpconfig(); $wconfig = new tebravo_wconfig(); $wconfig->dashboard();}}
		public function dashboard_wpadmin(){ if(class_exists( 'tebravo_wpadmin' )){tebravo_tour::wpadmin(); $wpadmin = new tebravo_wpadmin(); $wpadmin->dashboard();}}
		public function dashboard_bruteforce(){ if(class_exists( 'tebravo_bforce' )){tebravo_tour::bruteforce(); $bforce= new tebravo_bforce(); $bforce->dashboard();}}
		public function dashboard_antivirus(){ if(class_exists( 'tebravo_anitvirus' )){tebravo_tour::antivisurs_settings(); $antivirus= new tebravo_anitvirus(); $antivirus->dashboard();}}
		public function dashboard_mail(){ if(class_exists( 'tebravo_mail' )){$mail= new tebravo_mail(); $mail->dashboard();}}
		public function dashboard_recaptcha(){ if(class_exists( 'tebravo_recaptcha' )){tebravo_tour::recaptcha(); $recaptcha= new tebravo_recaptcha(); $recaptcha->dashboard();}}
		public function dashboard_errorpages(){ if(class_exists( 'tebravo_errorpages' )){$errorpages= new tebravo_errorpages(); $errorpages->dashboard();}}
		public function dashboard_firewall(){ if(class_exists( 'tebravo_firewall' )){tebravo_tour::firewall(); $firewall= new tebravo_firewall(); $firewall->dashboard();}}
		public function dashboard_traffic(){ if(class_exists( 'tebravo_traffic' )){$traffic= new tebravo_traffic(); $traffic->dashboard();}}
		public function dashboard_logwatch(){ if(class_exists( 'tebravo_logwatch' )){tebravo_tour::log();$logwatch= new tebravo_logwatch(); $logwatch->dashboard();}}
		public function dashboard_housekeeping(){ if(class_exists( 'tebravo_housekeeping' )){$housekeeping= new tebravo_housekeeping(); $housekeeping->dashboard();}}
		public function dashboard_backup(){ if(class_exists( 'tebravo_backups' )){tebravo_tour::backups();$backups= new tebravo_backups(); $backups->dashboard();}}
		
		/**
		 * General Settings Dashboard
		 */
		public function settings()
		{
			$this->html = new tebravo_html();
			$desc = "The BRAVO WP Ultimate Security Settings Section, Manage your choices and make the best settings to your Wordpress.";
			$this->html->header(__("General Settings", TEBRAVO_TRANS), $desc, "settings.png", false);
			//tour
			tebravo_tour::settings();
			
			if( empty($_GET['p'])){
				
				//Tabs Data
				$tabs["general"] = array("title"=>"Options",
						"href"=>$this->admin_url."-settings",
						"is_active"=> "active");
				
				$tabs["wpconfig"] = array("title"=>"WP Config",
						"href"=>$this->admin_url."-wconfig",
						"is_active"=> 'not');
				
				$tabs["wpadmin"] = array("title"=>"WP Admin",
						"href"=>$this->admin_url."-wadmin",
						"is_active"=> '');
				
				$tabs["bruteforce"] = array("title"=>"Brute Force",
						"href"=>$this->admin_url."-bruteforce",
						"is_active"=> '');
				
				$tabs["antivirus"] = array("title"=>"Anti Virus",
						"href"=>$this->admin_url."-antivirus&p=settings",
						"is_active"=> '');
				
				$tabs["mail"] = array("title"=>"Email Settings",
						"href"=>$this->admin_url."-mail&p=settings",
						"is_active"=> '');
				
				$tabs["recaptcha"] = array("title"=>"reCAPTCHA",
						"href"=>$this->admin_url."-recaptcha",
						"is_active"=> '');
				
				$tabs["error_pages"] = array("title"=>"Error Pages",
						"href"=>$this->html->init->admin_url."-error_pages",
						"is_active"=> '');

				//Tabs HTML
				$this->html->tabs($tabs);
				$this->html->start_tab_content();
				
				
				if($_POST)
				{
					//echo "BAZ"; exit;
					//check capability
					if(!$this->get_access('manage_options', true)){wp_die(__("Wrong Access", TEBRAVO_TRANS)); exit;}
					
					if(empty($_POST['_nonce']) || false == wp_verify_nonce($_POST['_nonce'], $this->security_hash.'mainsettings'))
					{
						$redirect_to = $this->admin_url."-settings&err=02";
						tebravo_redirect_js($redirect_to);
						exit;
					} else {
						////////////////////////
						// HTACCESS HOT LINIKING
						////////////////////////
						$this->get_htaccess_class();
						
						//(htaccess) update hotlinking rules
						$first_whitelist = strlen(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'hotlinks_while_list'));
						$last_whitelist = strlen(trim(esc_html($_POST[TEBRAVO_DBPREFIX.'hotlinks_while_list'])));
						
						if($_POST[TEBRAVO_DBPREFIX.'prevent_outside_images'] == 'checked'){
							if(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'prevent_outside_images') == 'checked'
									&& ($first_whitelist != $last_whitelist
											|| tebravo_utility::get_option(TEBRAVO_DBPREFIX.'hotlinks_while_list')
											!=(esc_html($_POST[TEBRAVO_DBPREFIX.'hotlinks_while_list'])) ))
							{
								$this->htaccess->update_prevent_image((esc_html($_POST[TEBRAVO_DBPREFIX.'hotlinks_while_list']))
										,(esc_html($_POST[TEBRAVO_DBPREFIX.'hot_linking_img'])));
							} else if(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'prevent_outside_images') != 'checked')
							{
								tebravo_utility::update_option(TEBRAVO_DBPREFIX.'hotlinks_while_list', (trim(esc_html($_POST[TEBRAVO_DBPREFIX.'hotlinks_while_list']))));
								tebravo_utility::update_option(TEBRAVO_DBPREFIX.'hot_linking_img', trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'hot_linking_img'])));
								$this->htaccess->do_prevent_images();
							}
						}
						if($_POST[TEBRAVO_DBPREFIX.'prevent_outside_images'] == 'checked'
								&& tebravo_utility::get_option(TEBRAVO_DBPREFIX.'prevent_outside_images') != 'checked')
						{
							tebravo_utility::update_option(TEBRAVO_DBPREFIX.'hotlinks_while_list', (trim(esc_html($_POST[TEBRAVO_DBPREFIX.'hotlinks_while_list']))));
							tebravo_utility::update_option(TEBRAVO_DBPREFIX.'hot_linking_img', trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'hot_linking_img'])));
							$this->htaccess->do_prevent_images();
						}
						if($_POST[TEBRAVO_DBPREFIX.'prevent_outside_images'] == 'checked'
								&& esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'hot_linking_img'))
								!=(esc_html($_POST[TEBRAVO_DBPREFIX.'hot_linking_img'])))
						{
							$this->htaccess->update_prevent_image(trim(esc_html($_POST[TEBRAVO_DBPREFIX.'hotlinks_while_list']))
									,trim(esc_html($_POST[TEBRAVO_DBPREFIX.'hot_linking_img'])));
						}
						if($_POST[TEBRAVO_DBPREFIX.'prevent_outside_images'] != 'checked'){
							$this->htaccess->do_prevent_images('delete');
						}
						
						$plugins_next_notify = time()+(3*60*60);
						$themes_next_notify = time()+(6*60*60);
						
						if( isset($_POST['errorlog_enabled']) )
						{
							tebravo_utility::update_option(TEBRAVO_DBPREFIX.'errorlog_enabled', trim(sanitize_text_field($_POST['errorlog_enabled'])));
						}
						if( isset($_POST[TEBRAVO_DBPREFIX.'security_hash']))
						{
							tebravo_utility::update_option(TEBRAVO_DBPREFIX.'security_hash', trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'security_hash'])));
						}
						if( isset($_POST[TEBRAVO_DBPREFIX.'adminbar']))
						{
							tebravo_utility::update_option(TEBRAVO_DBPREFIX.'adminbar', trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'adminbar'])));
						}
						if( isset($_POST[TEBRAVO_DBPREFIX.'hidewpversion']))
						{
							tebravo_utility::update_option(TEBRAVO_DBPREFIX.'hidewpversion', trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'hidewpversion'])));
						}
						if( isset($_POST[TEBRAVO_DBPREFIX.'remember_delete_unused_themes']))
						{
							tebravo_utility::update_option(TEBRAVO_DBPREFIX.'remember_delete_unused_themes', trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'remember_delete_unused_themes'])));
						}
						if( isset($_POST[TEBRAVO_DBPREFIX.'remember_delete_unused_plugins']))
						{
							tebravo_utility::update_option(TEBRAVO_DBPREFIX.'remember_delete_unused_plugins', trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'remember_delete_unused_plugins'])));
						}
						if( isset($_POST[TEBRAVO_DBPREFIX.'development_mode']))
						{
							tebravo_utility::update_option(TEBRAVO_DBPREFIX.'development_mode', trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'development_mode'])));
						}
						$dev_mode_user_rules_admin = '';
						if( isset($_POST[TEBRAVO_DBPREFIX.'dev_mode_user_rules_admin']))
						{
							$dev_mode_user_rules_admin = trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'dev_mode_user_rules_admin']));
						}
						$dev_mode_user_rules_author = '';
						if( isset($_POST[TEBRAVO_DBPREFIX.'dev_mode_user_rules_author']) ){
							$dev_mode_user_rules_author = trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'dev_mode_user_rules_author']));
						}
						$dev_mode_user_rules_editor = '';
						if( isset($_POST[TEBRAVO_DBPREFIX.'dev_mode_user_rules_editor']) ){
							$dev_mode_user_rules_editor = trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'dev_mode_user_rules_editor']));
						}
						tebravo_utility::update_option(TEBRAVO_DBPREFIX.'dev_mode_user_rules_editor', $dev_mode_user_rules_editor);
						tebravo_utility::update_option(TEBRAVO_DBPREFIX.'dev_mode_user_rules_admin', $dev_mode_user_rules_admin);
						tebravo_utility::update_option(TEBRAVO_DBPREFIX.'dev_mode_user_rules_author', $dev_mode_user_rules_author);
						if( isset($_POST[TEBRAVO_DBPREFIX.'maintenance_mode']))
						{
							tebravo_utility::update_option(TEBRAVO_DBPREFIX.'maintenance_mode', trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'maintenance_mode'])));
						}
						if( isset($_POST[TEBRAVO_DBPREFIX.'close_msg_head']))
						{
							tebravo_utility::update_option(TEBRAVO_DBPREFIX.'close_msg_head', trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'close_msg_head'])));
						}
						if( isset($_POST[TEBRAVO_DBPREFIX.'close_msg_body']))
						{
							tebravo_utility::update_option(TEBRAVO_DBPREFIX.'close_msg_body', trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'close_msg_body'])));
						}
						if( isset($_POST[TEBRAVO_DBPREFIX.'prevent_outside_images']))
						{
							tebravo_utility::update_option(TEBRAVO_DBPREFIX.'prevent_outside_images', trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'prevent_outside_images'])));
						}
						
						if( isset($_POST[TEBRAVO_DBPREFIX.'hotlinks_while_list']))
						{
							tebravo_utility::update_option(TEBRAVO_DBPREFIX.'hotlinks_while_list', (trim(esc_html($_POST[TEBRAVO_DBPREFIX.'hotlinks_while_list']))));
						}
						if( isset($_POST[TEBRAVO_DBPREFIX.'hot_linking_img']))
						{
							tebravo_utility::update_option(TEBRAVO_DBPREFIX.'hot_linking_img', trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'hot_linking_img'])));
						}
						
						if( isset($_POST[TEBRAVO_DBPREFIX.'memory_limit']))
						{
							tebravo_utility::update_option(TEBRAVO_DBPREFIX.'memory_limit', trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'memory_limit'])));
						}
						if( isset($_POST[TEBRAVO_DBPREFIX.'max_execution_time']))
						{
							tebravo_utility::update_option(TEBRAVO_DBPREFIX.'max_execution_time', trim(sanitize_text_field($_POST[TEBRAVO_DBPREFIX.'max_execution_time'])));
						}
						
						
						if((($_POST[TEBRAVO_DBPREFIX.'remember_delete_unused_plugins'])) == 'checked')
						{
							tebravo_utility::update_option(TEBRAVO_DBPREFIX.'plugins_next_notify', $plugins_next_notify);
						}
						
						if((($_POST[TEBRAVO_DBPREFIX.'remember_delete_unused_themes'])) == 'checked')
						{
							tebravo_utility::update_option(TEBRAVO_DBPREFIX.'themes_next_notify', $themes_next_notify);
						}
						
						$redirect_to = $this->admin_url."-settings&msg=01";
						
						echo __("Loading", TEBRAVO_TRANS)."...";
						tebravo_redirect_js($redirect_to);
					}
				} else {
				
					if( isset($_GET['msg']) )
					{
						if( tebravo_phpsettings::web_server() == 'nginx' )
						{
							$output[] = "<table border=0 width=100% cellspacing=0>";
							$output[] = "<tr style='border:solid 1px #83B5D2; background:#EAF4F9; color:#0E7DBC'><td style='padding:8px;'>";
							$output[] = __("The plugin detects that your server is running on NGINX", TEBRAVO_TRANS)."<br />";
							$output[] = __("Please follow the NGINX guide by click the following link:", TEBRAVO_TRANS)."<br />";
							$output[] = "<a href='".$this->html->init->nginx_docs."' target=blank>".__("NGINX Docs", TEBRAVO_TRANS)."</a>";
							$output[] = "</td></tr>";
							$output[] = "</table>";
						}
					}
					
				//Start Content
				$output[] = "<form action='".$this->admin_url."-settings' method=post>";
				$output[] = "<input type='hidden' name='_nonce' value='".$this->create_nonce('mainsettings')."'>";
				$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
				$output[] = "<table border='0' width=100% cellspacing=0>";
				
				//Admin bar settings
				$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Admin Bar (on/off)", TEBRAVO_TRANS)."</strong></td>";
				$output[] = "</td>";
				$output[] = "</tr>";
				if(tebravo_utility::get_option( TEBRAVO_DBPREFIX."adminbar" ) == 'checked' ){$adminbar_yes="checked"; $adminbar_no="";} else{$adminbar_no="checked"; $adminbar_yes="";}
				$help_adminbar = "If you choose to enable it, You will see the admin bar menu for the BRAVO plugin or you can disable it to hide the admin bar menu.";
				$output[] = "<tr class='tebravo_underTD'><td width=80%>".__("Enable admin bar menu", TEBRAVO_TRANS)." ".$this->html->open_window_help('adminbar',$help_adminbar)."</td>";
				$output[] = "<td><input type='radio' name='".TEBRAVO_DBPREFIX."adminbar' value='checked' id='adminbar_checked' $adminbar_yes><label for='adminbar_checked'><span></span>".__("Yes", TEBRAVO_TRANS)."</label> &nbsp;";
				$output[] = "<input type='radio' name='".TEBRAVO_DBPREFIX."adminbar' value='no' id='adminbar' $adminbar_no><label for='adminbar'><span></span>".__("No", TEBRAVO_TRANS)."</label> ";
				$output[] = "</td>";
				$output[] = "</tr>";
				
				//Hide Wordpress Version
				$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Recommended WP Tweak Options", TEBRAVO_TRANS)."</strong></td>";
				$output[] = "</td>";
				$output[] = "</tr>";
				if(tebravo_utility::get_option( TEBRAVO_DBPREFIX."hidewpversion" ) == 'checked' ){$hidewpversion_yes="checked"; $hidewpversion_no="";} else{$hidewpversion_no="checked"; $hidewpversion_yes="";}
				$help_hidewpversion = "When you choose to hide the Wordpress version, Plugin will remove all tags which refer to your Wordpress version from the page source.";
				$output[] = "<tr class='tebravo_underTD'><td width=80%>".__("Hide Your Wordpress Version?", TEBRAVO_TRANS)." ".$this->html->open_window_help('hidewpversion',$help_hidewpversion)."</td>";
				$output[] = "<td><input type='radio' name='".TEBRAVO_DBPREFIX."hidewpversion' value='checked' id='hidewpversion_checked' $hidewpversion_yes><label for='hidewpversion_checked'><span></span>".__("Yes", TEBRAVO_TRANS)."</label> &nbsp;";
				$output[] = "<input type='radio' name='".TEBRAVO_DBPREFIX."hidewpversion' value='no' id='hidewpversion' $hidewpversion_no><label for='hidewpversion'><span></span>".__("No", TEBRAVO_TRANS)."</label> ";
				$output[] = "</td>";
				$output[] = "</tr>";
				
				//Remember: unused themes
				if(tebravo_utility::get_option( TEBRAVO_DBPREFIX."remember_delete_unused_themes" ) == 'checked' ){$remember_delete_unused_themes_yes="checked"; $remember_delete_unused_themes_no="";} else{$remember_delete_unused_themes_no="checked"; $remember_delete_unused_themes_yes="";}
				$help_remember_delete_unused_themes = "It is an important reminder, if you enable it, It will remember you to delete old and unused themes. It is a good step to go forward to high level of security.";
				$output[] = "<tr class='tebravo_underTD'><td width=80%>".__("Notify me to delete the unused themes", TEBRAVO_TRANS)." ".$this->html->open_window_help('remember_delete_unused_themes',$help_remember_delete_unused_themes)."</td>";
				$output[] = "<td><input type='radio' name='".TEBRAVO_DBPREFIX."remember_delete_unused_themes' value='checked' id='remember_delete_unused_themes_checked' $remember_delete_unused_themes_yes><label for='remember_delete_unused_themes_checked'><span></span>".__("Yes", TEBRAVO_TRANS)."</label> &nbsp;";
				$output[] = "<input type='radio' name='".TEBRAVO_DBPREFIX."remember_delete_unused_themes' value='no' id='remember_delete_unused_themes' $remember_delete_unused_themes_no><label for='remember_delete_unused_themes'><span></span>".__("No", TEBRAVO_TRANS)."</label> ";
				$output[] = "</td>";
				$output[] = "</tr>";
				
				//Remember: unused plugins
				if(tebravo_utility::get_option( TEBRAVO_DBPREFIX."remember_delete_unused_plugins" ) == 'checked' ){$remember_delete_unused_plugins_yes="checked"; $remember_delete_unused_plugins_no="";} else{$remember_delete_unused_plugins_no="checked"; $remember_delete_unused_plugins_yes="";}
				$help_remember_delete_unused_plugins = "Just like the unused themes reminder. It is an important reminder, if you enable it, It will remember you to delete old and unused themes. It is a good step to go forward to high level of security.";
				$output[] = "<tr class='tebravo_underTD'><td width=80%>".__("Notify me to delete the unused plugins", TEBRAVO_TRANS)." ".$this->html->open_window_help('remember_delete_unused_plugins',$help_remember_delete_unused_plugins)."</td>";
				$output[] = "<td><input type='radio' name='".TEBRAVO_DBPREFIX."remember_delete_unused_plugins' value='checked' id='remember_delete_unused_plugins_checked' $remember_delete_unused_plugins_yes><label for='remember_delete_unused_plugins_checked'><span></span>".__("Yes", TEBRAVO_TRANS)."</label> &nbsp;";
				$output[] = "<input type='radio' name='".TEBRAVO_DBPREFIX."remember_delete_unused_plugins' value='no' id='remember_delete_unused_plugins' $remember_delete_unused_plugins_no><label for='remember_delete_unused_plugins'><span></span>".__("No", TEBRAVO_TRANS)."</label> ";
				$output[] = "</td>";
				$output[] = "</tr>";
				
				//Development Mode Settings
				$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Front-end Close/Open", TEBRAVO_TRANS)."</strong></td>";
				$output[] = "</td>";
				$output[] = "</tr>";
				if(tebravo_utility::get_option( TEBRAVO_DBPREFIX."development_mode" ) == 'checked' ){$development_mode_yes="checked"; $development_mode_no="";} else{$development_mode_no="checked"; $development_mode_yes="";}
				$help_development_mode = "Development mode lets you close front-end website for non administrator users, while the administrators can see the front-end as well.";
				$output[] = "<tr class='tebravo_underTD'><td width=80%>".__("Enable Development Mode", TEBRAVO_TRANS)." ".$this->html->open_window_help('development_mode',$help_development_mode)."</td>";
				$output[] = "<td><input type='radio' name='".TEBRAVO_DBPREFIX."development_mode' value='checked' id='development_mode_checked' $development_mode_yes><label for='development_mode_checked'><span></span>".__("Yes", TEBRAVO_TRANS)."</label> &nbsp;";
				$output[] = "<input type='radio' name='".TEBRAVO_DBPREFIX."development_mode' value='no' id='development_mode' $development_mode_no><label for='development_mode'><span></span>".__("No", TEBRAVO_TRANS)."</label> ";
				$output[] = "</td>";
				$output[] = "</tr>";
				
				
				$output[] = "<tr class='tebravo_underTD'><td width=80% colspan=2><div class='tebravo_displace'><u>".__("Who can access front-end while website is closed?", TEBRAVO_TRANS)."</u> <br />";
				
				if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'dev_mode_user_rules_admin'))) == 'checked'){$dev_for_admin = "checked";}else{$dev_for_admin="";}
				$output[] = "<input type='checkbox' name='".TEBRAVO_DBPREFIX."dev_mode_user_rules_admin' value='checked' id='".TEBRAVO_DBPREFIX."dev_mode_user_rules_admin' $dev_for_admin >";
				$output[] = "<label for='".TEBRAVO_DBPREFIX."dev_mode_user_rules_admin'>".__("Administrator", TEBRAVO_TRANS)."</label><br />";
				
				if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'dev_mode_user_rules_author'))) == 'checked'){$dev_for_auther= "checked";}else{$dev_for_auther="";}
				$output[] = "<input type='checkbox' name='".TEBRAVO_DBPREFIX."dev_mode_user_rules_author' value='checked' id='".TEBRAVO_DBPREFIX."dev_mode_user_rules_author' $dev_for_auther >";
				$output[] = "<label for='".TEBRAVO_DBPREFIX."dev_mode_user_rules_author'>".__("Author", TEBRAVO_TRANS)."</label><br />";
				
				if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'dev_mode_user_rules_editor'))) == 'checked'){$dev_for_editor= "checked";}else{$dev_for_editor="";}
				$output[] = "<input type='checkbox' name='".TEBRAVO_DBPREFIX."dev_mode_user_rules_editor' value='checked' id='".TEBRAVO_DBPREFIX."dev_mode_user_rules_editor' $dev_for_editor >";
				$output[] = "<label for='".TEBRAVO_DBPREFIX."dev_mode_user_rules_editor'>".__("Editor", TEBRAVO_TRANS)."</label><br />";
				$output[] = "</div></td>";
				$output[] = "</tr>";
				
				//Maintenance Mode Settings
				if(tebravo_utility::get_option( TEBRAVO_DBPREFIX."maintenance_mode" ) == 'checked' ){$maintenance_mode_yes="checked"; $maintenance_mode_no="";} else{$maintenance_mode_no="checked"; $maintenance_mode_yes="";}
				$help_maintenance_mode = "Maintenance mode will close the front-end website for all visitors, users and administrators.";
				$output[] = "<tr class='tebravo_underTD'><td width=80%>".__("Enable Maintenance Mode", TEBRAVO_TRANS)." ".$this->html->open_window_help('maintenance_mode',$help_maintenance_mode)."</td>";
				$output[] = "<td><input type='radio' name='".TEBRAVO_DBPREFIX."maintenance_mode' value='checked' id='maintenance_mode_checked' $maintenance_mode_yes><label for='maintenance_mode_checked'><span></span>".__("Yes", TEBRAVO_TRANS)."</label> &nbsp;";
				$output[] = "<input type='radio' name='".TEBRAVO_DBPREFIX."maintenance_mode' value='no' id='maintenance_mode' $maintenance_mode_no><label for='maintenance_mode'><span></span>".__("No", TEBRAVO_TRANS)."</label> ";
				$output[] = "</td>";
				$output[] = "</tr>";
				
				$output[] = "<tr class='tebravo_underTD'><td width=80% colspan=2><div class='tebravo_displace'>".__("Message H1", TEBRAVO_TRANS)." <sub><font class='smallfont'>".__("for both two modes", TEBRAVO_TRANS)."</font></sub><br />";
				$output[] = "<input type='text' style='width:250px;' name='".TEBRAVO_DBPREFIX."close_msg_head' value='".esc_html(tebravo_utility::get_option( TEBRAVO_DBPREFIX."close_msg_head" ))."' id='hot_linking_img'><br />";
				$output[] = "<font class='smallfont'>".__("Like e.g: Site Under Construction.", TEBRAVO_TRANS)."</font><br /><br />";
				$output[] = __("Message Body", TEBRAVO_TRANS)." <sub><font class='smallfont'>".__("for both two modes", TEBRAVO_TRANS)."</font></sub><br />";
				$output[] = "<textarea style='width:250px;height:80px;' name='".TEBRAVO_DBPREFIX."close_msg_body'>".esc_html(tebravo_utility::get_option( TEBRAVO_DBPREFIX."close_msg_body" ))."</textarea>";
				$output[] = "<br /><font class='smallfont'>".__("HTML NOT ALLOWED", TEBRAVO_TRANS)."<br />";
				$output[] = "<b>{%sitename%}</b>: for printing site name<br /><b>{%email%}</b>: for printing admin email";
				$output[] = "</font></div></td>";
				$output[] = "</tr>";
				
				//Prevent Images from showing outside (hotlinking)
				$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Hotlinking & iFrames", TEBRAVO_TRANS)."</strong></td>";
				$output[] = "</td>";
				$output[] = "</tr>";
				if(tebravo_utility::get_option( TEBRAVO_DBPREFIX."prevent_outside_images" ) == 'checked' ){$prevent_outside_images_yes="checked"; $prevent_outside_images_no="";} else{$prevent_outside_images_no="checked"; $prevent_outside_images_yes="";}
				$help_prevent_outside_images = "If it is enabled, The plugin will prevent all images from showing outside your website, It is good for saving your website resources like as bandwidth.";
				$output[] = "<tr class='tebravo_underTD'><td width=80%>".__("Prevent images from showing outside your website", TEBRAVO_TRANS)." ".$this->html->open_window_help('prevent_outside_images',$help_prevent_outside_images)."</td>";
				$output[] = "<td><input type='radio' name='".TEBRAVO_DBPREFIX."prevent_outside_images' value='checked' id='prevent_outside_images_checked' $prevent_outside_images_yes><label for='prevent_outside_images_checked'><span></span>".__("Yes", TEBRAVO_TRANS)."</label> &nbsp;";
				$output[] = "<input type='radio' name='".TEBRAVO_DBPREFIX."prevent_outside_images' value='no' id='prevent_outside_images' $prevent_outside_images_no><label for='prevent_outside_images'><span></span>".__("No", TEBRAVO_TRANS)."</label> ";
				$output[] = "</td>";
				$output[] = "</tr>";
				$help_hotlinks_while_list = "BRAVO lets you to allow some domains like as a search engine, social media and friends' sites to be whitelisted from preventing using your self hosted images.";
				$output[] = "<tr class='tebravo_underTD'><td width=80% colspan=2><div class='tebravo_displace'>".__("White List Domains", TEBRAVO_TRANS)." <sub><font class='smallfont'>".__("Highly Recommended", TEBRAVO_TRANS)."</font></sub> ".$this->html->open_window_help('hotlinks_while_list',$help_hotlinks_while_list)."<br />";
				$output[] = "<textarea style='width:250px;height:80px;' name='".TEBRAVO_DBPREFIX."hotlinks_while_list'>".esc_html(tebravo_utility::get_option( TEBRAVO_DBPREFIX."hotlinks_while_list" ))."</textarea>";
				$output[] = "<br /><font class='smallfont'>".__("Like e.g: google.com, bing.com, add one domain per line", TEBRAVO_TRANS)."</font><br /><br />";
				$output[] = __("Defualt image for prevented images (Hot Linking)", TEBRAVO_TRANS)."<br />";
				$output[] = "<input type='text' style='width:250px;' name='".TEBRAVO_DBPREFIX."hot_linking_img' value='".esc_html(tebravo_utility::get_option( TEBRAVO_DBPREFIX."hot_linking_img" ))."' id='hot_linking_img'><br />";
				$output[] = "<font class='smallfont'>It must be from outside, like e.g: http://i.imgur.com/a0YYDvt.jpg</font><br /><br />";
				$output[] = "</div></td>";
				$output[] = "</tr>";
				
				//Prevent links from showing outside (iframe)
				if(tebravo_utility::get_option( TEBRAVO_DBPREFIX."prevent_outside_iframe" ) == 'checked' ){$prevent_outside_iframe_yes="checked"; $prevent_outside_iframe_no="";} else{$prevent_outside_iframe_no="checked"; $prevent_outside_iframe_yes="";}
				$help_prevent_outside_iframe = "If your website uses a third-party advertising like Adsense, It will be a perfect way to be on the safe side from be banned.";
				$output[] = "<tr class='tebravo_underTD'><td width=80%>".__("Prevent your links to be embedded in a frame (<*iframe>)", TEBRAVO_TRANS)." ".$this->html->open_window_help('prevent_outside_iframe',$help_prevent_outside_iframe)." <span class='tebravo_trail'>".__("Premium", TEBRAVO_TRANS)."</span></td>";
				$output[] = "<td><input type='radio' name='".TEBRAVO_DBPREFIX."prevent_outside_iframe' value='checked' id='prevent_outside_iframe_checked' $prevent_outside_iframe_yes><label for='prevent_outside_iframe_checked'><span></span>".__("Yes", TEBRAVO_TRANS)."</label> &nbsp;";
				$output[] = "<input type='radio' name='".TEBRAVO_DBPREFIX."prevent_outside_iframe' value='no' id='prevent_outside_iframe' disabled><label for='prevent_outside_iframe'><span></span>".__("No", TEBRAVO_TRANS)."</label> ";
				$output[] = "</td>";
				$output[] = "</tr>";
				
				//Hotlinks and iframe white list and default pages
				$output[] = "<tr class='tebravo_underTD'><td width=80% colspan=2><div class='tebravo_displace'>".__("Defualt page for prevented iframe", TEBRAVO_TRANS)." <span class='tebravo_trail'>".__("Premium", TEBRAVO_TRANS)."</span><br />";
				$output[] = "<input type='text' style='width:250px;' name='".TEBRAVO_DBPREFIX."iframe_default_page' value='' id='iframe_default_page' disabled><br />";
				$output[] = "<font class='smallfont'>Leave it blank if you want to redirect visitor to the same orginal window.</font><br />";
				$output[] = "</div></td>";
				$output[] = "</tr>";
				
				//Security Hash
				$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Unique Security Code", TEBRAVO_TRANS)."</strong></td>";
				$output[] = "</td>";
				$output[] = "</tr>";
				$help_security_hash = "It is an extra way to secure the Wordpress nonce security codes, You should use new and your unique hash.";
				$output[] = "<tr class='tebravo_underTD'><td width=80% colspan=2>".__("Security Hash", TEBRAVO_TRANS)." <sub><font class='smallfont'>".__("Highly Recommended", TEBRAVO_TRANS)."</font></sub> ".$this->html->open_window_help('security_hash',$help_security_hash)."<br />";
				$output[] = "<input required type='text' name='".TEBRAVO_DBPREFIX."security_hash' value='".tebravo_utility::get_option( TEBRAVO_DBPREFIX."security_hash" )."' id='security_hash'>";
				$output[] = "</td>";
				$output[] = "</tr>";
				
				//Bravo Resources Limit
				$resources_memory_limit= "Sometimes the BRAVO plugin will need custom PHP settings to execute the big process like scan files and backup files.";
				$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Bravo Resources Limit", TEBRAVO_TRANS)." ".$this->html->open_window_help('memory_limit',$resources_memory_limit)."</strong></td>";
				$output[] = "</td>";
				$output[] = "</tr>";
				
				//errorlog_enabled
				$output[] = "<tr class='tebravo_underTD'><td width=80% colspan=2>".__("Maximum Memory Limit", TEBRAVO_TRANS)." <br />";
				$output[] = "<select name='".TEBRAVO_DBPREFIX."memory_limit'>";
				$arr_memory_limit = array("default", "128M", "256M", "512M", "1024M");
				for($i=0; $i<@count($arr_memory_limit); $i++)
				{
					$output[] = "<option value='".$arr_memory_limit[$i]."' ";
					if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'memory_limit'))) == $arr_memory_limit[$i]){
						$output[] = "selected";
					}
					$output[] = ">".$arr_memory_limit[$i]."</option>";
				}
				$output[] = "<select>";
				
				$output[] = "<tr class='tebravo_underTD'><td width=80% colspan=2>".__("Maximum Timeout", TEBRAVO_TRANS)." <br />";
				$output[] = "<select name='".TEBRAVO_DBPREFIX."max_execution_time'>";
				$arr_max_execution_time= array("default", 30*60, 60*60, 2*60*60, 3*60*60, 4*60*60);
				for($a=0; $a<@count($arr_max_execution_time); $a++)
				{
					$output[] = "<option value='".$arr_max_execution_time[$a]."' ";
					if((esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'max_execution_time'))) == $arr_max_execution_time[$a]){
						$output[] = "selected";
					}
					$output[] = ">".$arr_max_execution_time[$a]."</option>";
				}
				$output[] = "<select> ".__("seconds", TEBRAVO_TRANS);
				
				$output[] = "</td>";
				$output[] = "</tr>";
				
				//errorlog_enabled
				$errorlog_enabled_desc= "If the log is enabled, you will able to watch all stored errors and notices.";
				$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Log", TEBRAVO_TRANS)." </strong></td>";
				$output[] = "</td>";
				$output[] = "</tr>";
				$errorlog_enabled_yes = '';
				$errorlog_enabled_no = 'checked';
				if( esc_html(tebravo_utility::get_option(TEBRAVO_DBPREFIX.'errorlog_enabled')) =='checked')
				{
					$errorlog_enabled_no = '';
					$errorlog_enabled_yes = 'checked';
				}
				$output[] = "<tr class='tebravo_underTD'><td width=80%>".__("Enable/Disable Log", TEBRAVO_TRANS)." ".$this->html->open_window_help('errorlog_enabled',$errorlog_enabled_desc)."</td>";
				$output[] = "<td><input type='radio' name='errorlog_enabled' value='checked' id='errorlog_enabled_checked' $errorlog_enabled_yes><label for='errorlog_enabled_checked'><span></span>".__("Yes", TEBRAVO_TRANS)."</label> &nbsp;";
				$output[] = "<input type='radio' name='errorlog_enabled' value='no' id='errorlog_enabled' $errorlog_enabled_no><label for='errorlog_enabled'><span></span>".__("No", TEBRAVO_TRANS)."</label> ";
				$output[] = "</td>";
				$output[] = "</tr>";
				
				//max_execution_time
				$backup_rules_btn_name = __("Backup .htaccess File", TEBRAVO_TRANS);
				if( tebravo_phpsettings::web_server() == 'nginx' )
				{
					$backup_rules_btn_name = __("Backup nginx.conf File", TEBRAVO_TRANS);
				}
				$output[] = "</table>";
				$output[] = $this->html->button(__("Save Settings", TEBRAVO_TRANS), "submit");
				$output[] = $this->html->button($backup_rules_btn_name, "button", "backuphtaccess", false, "download.png");
				$output[] = "</div></form>";
				
				echo implode("\n", $output);
				
				}
				$this->html->end_tab_content();
				
				//$db=new tebravo_db();
				#$db->backup();
				
				#var_dump(wp_get_themes());
				
			} else if(isset($_GET['p']) && $_GET['p'] == 'download_htacfile')
			{
				//check capability
				if(! current_user_can('manage_options')){wp_die(__("Wrong Access", TEBRAVO_TRANS)); exit;}
				
				#tebravo_redirect_js(plugins_url('includes/download_htacfile.php', $this->path));
				#add_action( 'wp_headers', 'tebravo_download_file' );
				
				$htaccess_path= ABSPATH.".htaccess";
				$filename = '.htaccess';
				if( tebravo_phpsettings::web_server() == 'nginx' )
				{
					$htaccess_path= ABSPATH."nginx.conf";
					$filename = 'nginx.conf';
				}
				if(file_exists($htaccess_path)){
					$data = file_get_contents($htaccess_path, 'nginx.conf');
					echo '<div class="tebravo_block_blank" style="width:100%"><div class="tebravo_code"><pre data-lang="scss">'.$data.'</pre></div>';
					echo '<input style="width:100%" type="text" disabled value="'.$this->backupdir."/htaccess/".$filename."_".$this->security_hash."_".@date('dMY').'">';
					echo $this->html->button(__("Copy to that path", TEBRAVO_TRANS), "button", "backuphtaccesscopy", false, "save.png");
					echo $this->html->button(__("Back", TEBRAVO_TRANS), "button", "back", false, "arrow_left.png");
					echo '&nbsp;<span id=tebravo_results><img src="'.plugins_url('assets/img/loading.gif', $this->path).'"></span></div>';
					
					
					echo '<script>';
					echo 'var tebravo_backup_htaccess_url="'.$this->create_nonce_ajax_url('tebravo_backup_htaccess_file', 'tebravo_backup_htaccess_file').'";';
					echo 'jQuery("#back").click(function(){';
					echo 'window.location.href= "'.$this->admin_url."-settings".'"';
					echo '});';
					echo '</script>';
				} else {
					tebravo_redirect_js($this->admin_url."-settings&err=04");
				}
			}
			
			$this->html->footer();
			
		}
		
		//Admin bar menu
		public function adminbar( $wp_admin_bar )
		{
			$args = array(
			"id" => "tebravo_adminbar",
			"title" => "<a href='".$this->admin_url."'><img src='".$this->adminbar_icon."' style='cursor:pointer'></a> ",
			"parent" => false,
			);
			
			if( true == current_user_can( 'manage_options' ))
			{
				$wp_admin_bar->add_node( $args );
			}
		}
		
		//Admin bar sub-menu
		public function adminbar_parent( $wp_admin_bar )
		{
			$args = array();
			
			if( $this->version_type!='PRO' )
			{
				array_push($args, array(
						"id" => "tebravo_go_pro",
						"title" => "<strong style='color:#2296D8'>".__( 'Upgrade to Pro' , TEBRAVO_TRANS)."</strong>",
						"href" => tebravo_create_donate_url('admin_bar'),
						"parent" => "tebravo_adminbar"
				));
			}
			
			array_push($args, array(
				"id" => "tebravo_adminbar_dashboard",
				"title" => __( 'Dashboard' , TEBRAVO_TRANS ),
				"href" => $this->admin_url,
				"parent" => "tebravo_adminbar"
			));
			
			array_push($args, array(
				"id" => "tebravo_adminbar_antivirus",
				"title" => __( 'Start Scan' , TEBRAVO_TRANS),
				"href" => $this->admin_url."-antivirus&p=scan",
				"parent" => "tebravo_adminbar"
			));
			
			array_push($args, array(
				"id" => "tebravo_adminbar_firewall",
				"title" => __( 'Firewall & Rules' , TEBRAVO_TRANS),
				"href" => $this->admin_url."-firewall",
				"parent" => "tebravo_adminbar"
			));
			
			array_push($args, array(
				"id" => "tebravo_adminbar_traffic",
				"title" => __( 'Traffic Tracker' , TEBRAVO_TRANS),
				"href" => $this->admin_url."-traffic",
				"parent" => "tebravo_adminbar"
			));
			
			
			ksort($args);
			if( true == current_user_can( 'manage_options' ) )
			{
				$this->get_adminbar_submenu( $wp_admin_bar , $args );
			}
		}
		
		//Loop to register sub menus for admin bar
		public function get_adminbar_submenu ( $wp_admin_bar , $args )
		{
				for($a=0;$a<sizeOf($args);$a++)
				{
					$wp_admin_bar->add_node($args[$a]);
				}
		}
		
		//All plugins counter
		public function plugins_count(){ return count(get_plugins());}
		//All active plugins counter
		public function active_plugins_count(){ return count(tebravo_utility::get_option('active_plugins'));}
		//All inactive plugins counter
		public function inactive_plugins_count()
		{
			$all_plugins = (int) $this->plugins_count();
			$active_plugins = (int) $this->active_plugins_count();
			
			$inactive_plugins = floor( $all_plugins - $active_plugins );
			return $inactive_plugins;
		}
		
		//All themes count
		public function themes_count(){ return count(wp_get_themes());}
		//In-active themes
		public function inactive_themes_count(){ return floor($this->themes_count() - 1);}
		
		//cookie
		public function tebravo_setcookie( )
		{
			if( isset($_REQUEST['tebravo_cookie_name'] )
					&& isset($_REQUEST['tebravo_cookie_value']  )
							&& isset($_REQUEST['tebravo_cookie_period'] )
									&& $_REQUEST['tebravo_hash'] == $this->security_hash):
			setcookie($_REQUEST['tebravo_cookie_name'], $_REQUEST['tebravo_cookie_value'], $_REQUEST['tebravo_cookie_period']);
			endif;
		}
		
		//create nonce
		public function create_nonce( $nonce )
		{
		    $new_nonce = $this->security_hash.$nonce;
		    return wp_create_nonce( $new_nonce);
		}
		
		//creat ajax nonce
		public function create_nonce_ajax_url( $nonce, $callback )
		{
			$URL = add_query_arg (
			array (
					'action' => $callback,
					'_wpnonce' => wp_create_nonce ( $this->security_hash.$nonce)
			)
			, admin_url('admin-ajax.php') );
			
			return $URL;
		}
		
		//create hash
		public static function create_hash( $length ) {
		    $alphabet = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ1234567890';
		    $hash = array(); //remember to declare $pass as an array
		    $alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
		    for ($i = 0; $i < $length; $i++) {
		        $n = rand(0, $alphaLength);
		        $hash[] = $alphabet[$n];
		    }
		    return implode($hash); //turn the array into a string
		}
		
		//create prefix
		public static function create_prefix( $length ) {
			$alphabet = 'abcdefghijklmnopqrstuvwxyzAXYBCDLMNOPQRSTEFGHIJKUVWZ0123654789';
			$hash = array(); //remember to declare $pass as an array
			$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
			for ($i = 0; $i < $length; $i++) {
				$n = rand(0, $alphaLength);
				$hash[] = $alphabet[$n];
			}
			return implode($hash); //turn the array into a string
		}
		
		//create salt if it was empty from API
		public static function create_salt( $length ) {
			$alphabet = 'abcdefghijklmnopqrstuvwxyzAXYBCDLMNOPQRSTEFGHIJKUVWZ0123654789!@#$%^&*';
			$hash = array(); //remember to declare $pass as an array
			$alphaLength = strlen($alphabet) - 1; //put the length -1 in cache
			for ($i = 0; $i < $length; $i++) {
				$n = rand(0, $alphaLength);
				$hash[] = $alphabet[$n];
			}
			return implode($hash); //turn the array into a string
		}
		
		//setup admin permissions
		public function get_access( $permission, $is_admin=false )
		{
			if( $permission && $permission == 'manage_options' )
			{
				if( function_exists( 'is_multisite' ) && is_multisite() )
				{
					$permission = 'manage_network_options';
				}
			}
			$counter = 0;
			
			if( !$is_admin){
				if( current_user_can( $permission ) === true )
				{
					$counter = 1;
				}
			} else {
				if( current_user_can( $permission ) === true 
						&& is_admin())
				{
					$counter = 2;
				}
			}
			
			if( $counter > 0 )
			{
				return true;
			}
			
			return false;
		}
		//errors
		public function errors( $error_number )
		{
		    $errors = array(
		        "01" => __("Sorry! Page not found.", TEBRAVO_TRANS),
		        "02" => __("Actively Refused! this always happens when security level comes lower than normal!", TEBRAVO_TRANS),
		        "03" => __("Access Denied! No thing to do.", TEBRAVO_TRANS),
		        "04" => __("File not found.", TEBRAVO_TRANS),
		        "05" => __("Failed to rewrite .htaccess file.", TEBRAVO_TRANS),
		        "06" => __("Failed to find /tmp directory.", TEBRAVO_TRANS),
		        "07" => __("Can not complete your request.", TEBRAVO_TRANS),
		        "08" => __("wp-config.php does not exists in path <i>".ABSPATH."wp-config.php", TEBRAVO_TRANS),
		        "09" => __("BRAVO can not backup the wp-config.php, Please do it manually!", TEBRAVO_TRANS),
		        "010" => __("BRAVO can not backup your wordpress database, Please do it manually!", TEBRAVO_TRANS),
		        "011" => __("Failed to change the Wordpress database prefix because of missed important data!", TEBRAVO_TRANS),
		        "012" => __("Can not scan empty directory!", TEBRAVO_TRANS),
		        "013" => __("File does not exists or removed!", TEBRAVO_TRANS),
		        "014" => __("Scan Module Missed!", TEBRAVO_TRANS),
		        "015" => __("Start over file not found!", TEBRAVO_TRANS),
		        "016" => __("Dashboard template file does not exits", TEBRAVO_TRANS),
		    );
		    
		    if(!in_array( $error_number , $errors ))
		    foreach($errors as $key => $value)
		    {
		        if($error_number == $key)
		        {
		            $error_is = $value;
		        }
		    }
		    if(!empty($error_is))
		    	return $error_is;
		}
		
		//print erros
		public function print_errors( $error_number )
		{
		    $error_number = esc_html( $error_number );
		      
		    $the_error = new WP_Error( 'broke', __($this->errors( $error_number )) );
		    if(is_wp_error ( $the_error ) )
		    {
		        $error = $the_error->get_error_message();
		    }
		    
		    $output[] = "";
		    $output[] = $error;
		    $output[] = "";
		    
		    return implode("\n", $output);
		}
		
		//print message
		public function print_message( $msg_number )
		{
		    $msgs = array
		    (
		      "01" => __("Settings Saved Successfully.", TEBRAVO_TRANS),   
		      "02" => __("New Schedule Event Added Successfully.", TEBRAVO_TRANS),   
		      "03" => __("Cronjob Updated Successfully.", TEBRAVO_TRANS),   
		      "04" => __("Wordpress Database Prefix Changed Successfully.", TEBRAVO_TRANS),   
		      "05" => __("Your action on file have been done.", TEBRAVO_TRANS),   
		      "06" => __("Antivirus process delete successfully.", TEBRAVO_TRANS),   
		      "07" => __("Database successfully optimized.", TEBRAVO_TRANS),   
		      "08" => __("Cleaning Done.", TEBRAVO_TRANS),   
		      "09" => __("Backing up Done.", TEBRAVO_TRANS),   
		    );
		    
		    if(!in_array( $msg_number, $msgs))
		    foreach($msgs as $key => $value)
		    {
		        if($msg_number== $key)
		        {
		            $message_is = $value;
		        }
		    }
		    if(!empty($message_is)){
		    	$output[] = $message_is;
		    
		   		return implode("\n", $output);
		    }
		    
		}
		
		//Admin Notices Error
		public function admin_notice_error( ) {
			global $tebravo_admin_notice_error;
			
			$class = 'notice notice-error';
			$message = __( $tebravo_admin_notice_error, TEBRAVO_TRANS );
			
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}
		
		//get the time by ago/since format
		public function since_ago( $old_date, $new_date ) {
		    return $this->interval_sec( $new_date - $old_date );
		}
		
		public function interval_sec( $t ) {
		    // array of time period chunks
		    $chunks = array(
		        /* translators: 1: The number of years in an interval of time. */
		        array( 60 * 60 * 24 * 365, _n_noop( '%s year', '%s years', TEBRAVO_TRANS ) ),
		        /* translators: 1: The number of months in an interval of time. */
		        array( 60 * 60 * 24 * 30, _n_noop( '%s month', '%s months', TEBRAVO_TRANS ) ),
		        /* translators: 1: The number of weeks in an interval of time. */
		        array( 60 * 60 * 24 * 7, _n_noop( '%s week', '%s weeks', TEBRAVO_TRANS ) ),
		        /* translators: 1: The number of days in an interval of time. */
		        array( 60 * 60 * 24, _n_noop( '%s day', '%s days', TEBRAVO_TRANS ) ),
		        /* translators: 1: The number of hours in an interval of time. */
		        array( 60 * 60, _n_noop( '%s hour', '%s hours', TEBRAVO_TRANS ) ),
		        /* translators: 1: The number of minutes in an interval of time. */
		        array( 60, _n_noop( '%s minute', '%s minutes', TEBRAVO_TRANS ) ),
		        /* translators: 1: The number of seconds in an interval of time. */
		        array( 1, _n_noop( '%s second', '%s seconds', TEBRAVO_TRANS ) ),
		    );
		    
		    if ( $t <= 0 ) {
		        return __( 'now', TEBRAVO_TRANS );
		    }
		    
		    // we only want to output two chunks of time here, eg:
		    // x years, xx months
		    // x days, xx hours
		    // so there's only two bits of calculation below:
		    
		    // step one: the first chunk
		    for ( $i = 0, $j = count( $chunks ); $i < $j; $i++ ) {
		        $seconds = $chunks[ $i ][0];
		        $name = $chunks[ $i ][1];
		        
		        // finding the biggest chunk (if the chunk fits, break)
		        if ( ( $count = floor( $t / $seconds ) ) != 0 ) {
		            break;
		        }
		    }
		    
		    // set output var
		    $output = sprintf( translate_nooped_plural( $name, $count, TEBRAVO_TRANS ), $count );
		    
		    // step two: the second chunk
		    if ( $i + 1 < $j ) {
		        $seconds2 = $chunks[ $i + 1 ][0];
		        $name2 = $chunks[ $i + 1 ][1];
		        
		        if ( ( $count2 = floor( ( $t - ( $seconds * $count ) ) / $seconds2 ) ) != 0 ) {
		            // add to output var
		            $output .= ' ' . sprintf( translate_nooped_plural( $name2, $count2, TEBRAVO_TRANS ), $count2 );
		        }
		    }
		    
		    return $output;
		}
		
	}
}


?>