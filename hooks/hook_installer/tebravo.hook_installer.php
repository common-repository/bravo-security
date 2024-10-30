<?php
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}
if( !function_exists( 'tebravo_utility::is_option' ) )
{
	include_once TEBRAVO_DIR.'/includes/tebravo.base.php';
}
if( !class_exists( 'tebravo_hooks_install' ) )
{

    class tebravo_hooks_install{
//install antivirus
        public static function install_antivirus()
        {
        	global $wpdb;
        	
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'antimalware' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'antimalware' , 'checked' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'phpmussel' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'phpmussel' , 'checked' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'googshaver' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'googshaver' , 'checked' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'googshaver_api' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'googshaver_api' , '' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'domainspam' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'domainspam' , 'checked' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'dbscanner' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'dbscanner' , 'checked' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'scan_attachments' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'scan_attachments' , 'both' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'attachments_infected_action' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'attachments_infected_action' , 'autodelete' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'scan_new_plugins' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'scan_new_plugins' , 'all' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'new_plugins_infected_action' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'new_plugins_infected_action' , 'quarantine' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'scan_new_themes' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'scan_new_themes' , 'all' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'new_themes_infected_action' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'new_themes_infected_action' , 'quarantine' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'filechange' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'filechange' , 'checked' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'scan_fchange_new' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'scan_fchange_new' , 'never' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'scan_fchange_altered' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'scan_fchange_altered' , 'never' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'fchange_infected_action' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'fchange_infected_action' , 'nothing' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'safe_files' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'safe_files' , '' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'quarantine' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'quarantine' , '' ); }
        	
        	$charset_collate = $wpdb -> get_charset_collate();
        	
        	$sql = "CREATE TABLE ".$wpdb->prefix.TEBRAVO_DBPREFIX."scan_ps (
        	id mediumint(11) NOT NULL AUTO_INCREMENT,
        	pid char(75) NOT NULL,
        	status char(25) NOT NULL,
        	start_at char(25) NOT NULL,
        	end_at char(25) NOT NULL,
			infected longtext,
        	total_files char(25) NOT NULL,
        	cheked_files char(25) NOT NULL,
        	infected_files char(25) NOT NULL,
        	start_by char(25) NOT NULL,
        	scan_type char(25) NOT NULL,
        	infected_results longtext,
        	p_percent int(11) NOT NULL,
        	PRIMARY KEY  (id)
        	) $charset_collate;";
        	
        	require_once ABSPATH .'wp-admin/includes/upgrade.php';
        	dbDelta($sql);
        }
        
        
        //uninstall antivirus
        public static function uninstall_antivirus()
        {
        	global $wpdb;
        	
        	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix.TEBRAVO_DBPREFIX."scan_ps");
        	
        }
        
        //install bforce
        public static function install_bforce()
        {
        	global $wpdb;
        	
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'bruteforce_protection' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'bruteforce_protection' , 'no' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'login_by' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'login_by' , 'email_username' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'enforce_strongpasswords' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'enforce_strongpasswords' , 'no' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'max_login_attemps_ip' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'max_login_attemps_ip' , '3' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'max_login_attemps_user' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'max_login_attemps_user' , '3' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'bforce_whitelist_ips' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'bforce_whitelist_ips' , tebravo_agent::user_ip() ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'max_forgot_attemps' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'max_forgot_attemps' , '3' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'time_before_unblock' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'time_before_unblock' , '15' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'blocked_usernames_login' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'blocked_usernames_login' , 'admin,control,cpanel,administrator,author,editor' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'blocked_usernames_register' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'blocked_usernames_register' , 'admin,control,cpanel,administrator,author,editor' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'blocked_email_hosts' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'blocked_email_hosts' , 'mail.ru' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'blocked_countries' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'blocked_countries' , '' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'blocked_countries_expect_ips' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'blocked_countries_expect_ips' , '' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'moderate_new_members' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'moderate_new_members' , '' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'min_username' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'min_username' , '3' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'max_username' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'max_username' , '30' ); }
        	
        	$charset_collate = $wpdb -> get_charset_collate();
        	
        	$sql = "CREATE TABLE ".$wpdb->prefix.TEBRAVO_DBPREFIX."attemps (
        	id bigint(20) NOT NULL AUTO_INCREMENT,
        	ipaddress char(25) NOT NULL,
        	userid char(25) NOT NULL,
        	email char(25) NOT NULL,
        	user_login char(25) NOT NULL,
        	time_blocked char(25) NOT NULL,
        	time_to_unblock char(25) NOT NULL,
        	PRIMARY KEY  (id)
        	) $charset_collate;";
        	
        	require_once ABSPATH .'wp-admin/includes/upgrade.php';
        	dbDelta($sql);
        }
        
        //uninstall bforce
        public static function uninstall_bforce()
        {
        	global $wpdb;
        	
        	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix.TEBRAVO_DBPREFIX."attemps");
        	
        }
        
        //Install cronjobs
        public static function install_cronjobs()
        {
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'cronjobs_events' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'cronjobs_events' , '' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'cronjobs_enabled' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'cronjobs_enabled' , 'checked' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'cronjobs_emails_enabled' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'cronjobs_emails_enabled' , 'checked' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'cronjobs_emails' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'cronjobs_emails' , tebravo_utility::get_option( 'admin_email' ) ); }
        	
        	do_action( 'tebravo_cronjobs_install_hook' );
        }
        
        //install errorpages
        public static function install_errorpages()
        {
        	//pages settings
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'404_page' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'404_page' , 'no' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'errorpages_search' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'errorpages_search' , 'no' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'errorpages_template' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'errorpages_template' , 'default' ); }
        	//pages descriptions
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'404_page_desc' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'404_page_desc' , tebravo_errorpages::desc( 404 )); }
        	//error log settings
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'error_pages_errorlog' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'error_pages_errorlog' , 'no'); }
        	
        }
        
        //install firewall
        public static function install_firewall()
        {
        	global $wpdb;
        	
        	$charset_collate = $wpdb -> get_charset_collate();
        	
        	$sql = "CREATE TABLE ".$wpdb->prefix.TEBRAVO_DBPREFIX."firewall_actions (
        	id bigint(20) NOT NULL AUTO_INCREMENT,
        	ipaddress char(25) NOT NULL,
        	country_code char(25) NOT NULL,
        	blocked_country char(25) NOT NULL,
        	block_type char(25) NOT NULL,
        	block_action char(25) NOT NULL,
        	block_reason mediumtext,
        	time_blocked char(25) NOT NULL,
        	time_to_unblock char(25) NOT NULL,
        	PRIMARY KEY  (id)
        	) $charset_collate;";
        	
        	require_once ABSPATH .'wp-admin/includes/upgrade.php';
        	dbDelta($sql);
        	
        	$firewall_404_whitefiles = '/favicon.ico'.PHP_EOL;
        	$firewall_404_whitefiles .= '/robots.txt'.PHP_EOL;
        	$firewall_404_whiteext = 'png'.PHP_EOL;
        	$firewall_404_whiteext .= 'jpeg'.PHP_EOL;
        	$firewall_404_whiteext .= 'jpg'.PHP_EOL;
        	$firewall_404_whiteext .= 'gif'.PHP_EOL;
        	$firewall_404_whiteext .= 'css'.PHP_EOL;
        	$firewall_whiteips = '127.0.0.1'.PHP_EOL;
        	$firewall_whiteips .= '78.46.102.242'.PHP_EOL;
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'firewall_profile' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'firewall_profile' , 'disabled' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'firewall_block_screen' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'firewall_block_screen' , '404' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'firewall_block_message' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'firewall_block_message' , '' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'firewall_block_period' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'firewall_block_period' , '15' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'firewall_blocked_ips' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'firewall_blocked_ips' , '' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'firewall_blocked_countries' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'firewall_blocked_countries' , '' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'firewall_php_security' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'firewall_php_security' , 'no' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'firewall_404_whitefiles' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'firewall_404_whitefiles' , $firewall_404_whitefiles ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'firewall_404_whiteext' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'firewall_404_whiteext' , $firewall_404_whiteext ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'firewall_whiteips' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'firewall_whiteips' , '' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'firewall_whitecountries' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'firewall_whitecountries' , '' ); }
        	
        }
        
        //uninstall firewall
        public static function uninstall_firewall()
        {
        	global $wpdb;
        	
        	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix.TEBRAVO_DBPREFIX."firewall_actions");
        	
        }
        
        //install logwatch
        public static function install_logwatch()
        {
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'errorlog_enabled' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'errorlog_enabled' , 'checked' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'errorlog_checkproxy' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'errorlog_checkproxy' , 'no' ); }
        }
        
        //install mail
        public static function install_mail()
        {
        	$email = tebravo_utility::get_option( 'admin_email' );
        	//$email = 'technoyer@gmail.com';
        	$email = trim( esc_html( $email ) );
        	
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'maillog_filename' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'maillog_filename' , '' ); }
        	tebravo_mail::create_filename();
        	
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'mailwatching' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'mailwatching' , 'no' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'email_firewall_blocked_human' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'email_firewall_blocked_human' , 'checked' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'email_firewall_blocked_bot' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'email_firewall_blocked_bot' , 'checked' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'email' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'email' , $email); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'cc' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'cc' , $email); }
        }
        
        //install recaptcha
        public static function install_recaptcha()
        {
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'recaptcha_comment' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'recaptcha_comment' , 'no' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'recaptcha_login' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'recaptcha_login' , 'no' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'recaptcha_register' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'recaptcha_register' , 'no' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'recaptcha_resetpw' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'recaptcha_resetpw' , 'no' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'recaptcha_site_key' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'recaptcha_site_key' , '' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'recaptcha_secret_key' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'recaptcha_secret_key' , '' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'recaptcha_theme' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'recaptcha_theme' , 'light' ); }
        }
        
        //install traffic
        public static function install_traffic()
        {
        	global $wpdb;
        
        	
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'uniquevisits' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'uniquevisits' , '0' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'pageviews' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'pageviews' , '0' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'admins_online' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'admins_online' , 'no' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'traffic_enabled' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'traffic_enabled' , 'no' ); }
        	
        	$charset_collate = $wpdb -> get_charset_collate();
        	
        	$sql = "CREATE TABLE ".$wpdb->prefix.TEBRAVO_DBPREFIX."traffic (
        	id bigint(20) NOT NULL AUTO_INCREMENT,
        	ipaddress char(25) NOT NULL,
        	userid char(25) NOT NULL,
        	country char(25) NOT NULL,
        	country_code char(25) NOT NULL,
        	attempts_404 char(25) NOT NULL,
        	attempts_badreqs char(25) NOT NULL,
        	is_admin char(25) NOT NULL,
        	is_bot char(25) NOT NULL,
        	device char(25) NOT NULL,
        	browser char(25) NOT NULL,
        	user_isp tinytext,
        	past_sessions int(9) NOT NULL,
        	current_sessions int(9) NOT NULL,
        	cookie_key char(25) NOT NULL,
        	start_time char(25) NOT NULL,
        	last_active char(25) NOT NULL,
        	http_referer tinytext,
        	current_page tinytext,
        	PRIMARY KEY  (id)
        	) $charset_collate;";
        	
        	require_once ABSPATH .'wp-admin/includes/upgrade.php';
        	dbDelta($sql);
        	
        }
        
        //uninstall traffic
        public static function uninstall_traffic()
        {
        	global $wpdb;
        	
        	$wpdb->query("DROP TABLE IF EXISTS ".$wpdb->prefix.TEBRAVO_DBPREFIX."traffic");
        }
        
        //Install wconfig
        public static function install_wconfig()
        {
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'wp_prefix_enabled' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'wp_prefix_enabled' , 'no' );}
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'wp_prefix' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'wp_prefix' , tebravo_init::create_hash(5));}
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'disable_editor' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'disable_editor' , 'no');}
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'wp_config_notify_perms' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'wp_config_notify_perms' , 'checked');}
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'auto_updates_wp' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'auto_updates_wp' , 'checked');}
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'auto_updates_themes' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'auto_updates_themes' , 'no');}
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'auto_updates_plugins' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'auto_updates_plugins' , 'checked');}
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'disable_wp_errors_debug' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'disable_wp_errors_debug' , 'checked');}
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'auth_keys_last_update' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'auth_keys_last_update' , '');}
        	
        	do_action( 'tebravo_wconfig_install_hook' );
        }
        
        //Install Hook
        public static function install_wpadmin()
        {
        	$auth_methods = json_encode(array( 'mobileapp', 'email' , 'backup_codes' ));
        	$roles_2fa = json_encode(array( 'Administrator', 'Editor' , 'Author', 'Contributor', 'Subscriber', 'Customer', 'Shop Manager' ));
        	
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'hide_wplogin' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'hide_wplogin' , 'no' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'wplogin_slug' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'wplogin_slug' , 'login' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'wpregister_slug' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'wpregister_slug' , 'register' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'hide_wpadmin' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'hide_wpadmin' , 'no' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'wpadmin_slug' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'wpadmin_slug' , 'admin' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'wpadmin_keys_login' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'wpadmin_keys_login' , 'no' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'idle_logout' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'idle_logout' , 'no' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'idle_logout_duration' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'idle_logout_duration' , '120' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'wpadmin_block_proxy' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'wpadmin_block_proxy' , 'no' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'wpadmin_wl_countries' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'wpadmin_wl_countries' , '' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'wpadmin_wl_ips' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'wpadmin_wl_ips' , '' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'two_step_login' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'two_step_login' , 'no' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'two_step_login_default' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'two_step_login_default' , '2fa' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'two_step_facebook_app' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'two_step_facebook_app' , '' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'two_step_facebook_app_secret' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'two_step_facebook_app_secret' , '' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'two_step_pin_code' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'two_step_pin_code' , '' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'two_step_question' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'two_step_question' , '' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'two_step_answer' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'two_step_answer' , '' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'two_step_auth_login_enabled' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'two_step_auth_login_enabled' , '' ); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'two_step_auth_method' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'two_step_auth_method' , $auth_methods); }
        	if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'roles_2fa' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'roles_2fa' , $roles_2fa); }
        	
        	global $wpdb;
        	
        	$charset_collate = $wpdb -> get_charset_collate();
        	
        	do_action( 'tebravo_wpadmin_install_hook' );
        }
    }
}