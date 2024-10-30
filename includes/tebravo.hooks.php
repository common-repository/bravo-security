<?php 

//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !class_exists( 'tebravo_hooks' ) )
{
	class tebravo_hooks {
		
		public $call_hook;
		public $cronjobs = "";
		
		//Instance
		protected static $_instance = null;
		
		//Setup onle one instance
		public static function init() {
			
			static $instance = null;
			
			if ( ! $instance ) {
				$instance = new tebravo_init;
			}
			
			return $instance;
			
		}
		
		public function __construct()
		{
			$this->include_files();
		}
		
		public function include_files()
		{
			//Include Hooks
			include TEBRAVO_DIR.'/hooks/tebravo.cronjobs.php';
			include TEBRAVO_DIR.'/hooks/tebravo.db.php';
			include TEBRAVO_DIR.'/hooks/tebravo.wconfig.php';
			include TEBRAVO_DIR.'/hooks/tebravo.wpadmin.php';
			include TEBRAVO_DIR.'/hooks/tebravo.bforce.php';
			include TEBRAVO_DIR.'/hooks/tebravo.antivirus.php';
				//Antivirus Modules
				include TEBRAVO_DIR.'/hooks/antivirus/utility.php';
				include TEBRAVO_DIR.'/hooks/antivirus/malware_scanner.php';
				include TEBRAVO_DIR.'/hooks/antivirus/phpmussel_scanner.php';
				include TEBRAVO_DIR.'/hooks/antivirus/googshavar_scanner.php';
				include TEBRAVO_DIR.'/hooks/antivirus/spamcheck_scanner.php';
				include TEBRAVO_DIR.'/hooks/antivirus/db_scanner.php';
				include TEBRAVO_DIR.'/hooks/antivirus/filechange_scanner.php';
			//continue to hooks
			include TEBRAVO_DIR.'/hooks/tebravo.mail.php';
			include TEBRAVO_DIR.'/hooks/tebravo.recaptcha.php';
			include TEBRAVO_DIR.'/hooks/tebravo.errorpages.php';
			include TEBRAVO_DIR.'/hooks/tebravo.firewall.php';
			include TEBRAVO_DIR.'/hooks/tebravo.traffic.php';
			include TEBRAVO_DIR.'/hooks/tebravo.logwatch.php';
			include TEBRAVO_DIR.'/hooks/tebravo.housekeeping.php';
			include TEBRAVO_DIR.'/hooks/tebravo.backups.php';
			
		}
	}
}
?>