<?php 
/**
 * Plugin Name: BRAVO WP Ultimate Security
 * Plugin URI: http://bravo-security.technoyer.com
 * Description: Advanced firewall with multiple profiles, Anti virus, Anti malwares, Anti spam, Brute Force attack protection, Perfect wp-admin security, Database and Files backups, Files change detection and more.
 * Author: Technoyer Solutions Ltd.
 * Author URI: http://technoyer.com
 * Version: 1.1
 * Network: true
 * License: GPL V3
 * Text Domain: bravo-security
 */
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

//Include Config
include 'bravo-config.php';
//installer
include 'includes/tebravo.installer.php';

//Installer
include_once 'hooks/hook_installer/tebravo.hook_installer.php';
// Run Installation When Plugin is active by admin
register_activation_hook (__FILE__, 'tebravo_install');

// Uninstall when admin need to delete the plugin
register_uninstall_hook(__FILE__, 'tebravo_byebye');


//Include Files
include 'includes/functions.php';
include 'includes/tebravo.base.php';
include 'includes/tebravo.core.php';
include 'includes/tebravo.tour.php';
include 'includes/tebravo.selfprotect.php';
include 'includes/tebravo.html.php';
include 'includes/tebravo.view.php';
include 'includes/tebravo.files.php';
include 'includes/tebravo.dirs.php';
include 'includes/tebravo.2fa.php';
include 'includes/tebravo.phpsettings.php';
include 'includes/cronjobs_functions.php';
include 'includes/cronjobs_callback.php';
include 'includes/tebravo.Encriptor.php';
include 'includes/tebravo.agent.php';
include 'includes/tebravo.errorlog.php';
include 'includes/tebravo.dashboard.php';
include 'includes/AgentSrc/tebravo.countries.php';
include 'includes/tebravo.init.php';

if( !function_exists( 'tebravo_trans_load' ) )
{
function tebravo_trans_load() {
    $plugin_rel_path = basename( dirname( __FILE__ ) ) . '/langs'; /* Relative to WP_PLUGIN_DIR */
    load_plugin_textdomain( TEBRAVO_SLUG, false, $plugin_rel_path );
}
add_action('plugins_loaded', 'tebravo_trans_load');
}
//Run Plugin
tebravo_init::init();

//plugin action links
if( !function_exists( 'tebravo_action_links' ) )
{
	function tebravo_action_links()
	{
		
		$basename = plugin_basename( __FILE__ );
		$prefix = is_network_admin() ? 'network_admin_' : '';
		add_filter(
				"{$prefix}plugin_action_links_$basename",
				'tebravo_plugin_action_links',
				10, // priority
				4   // parameters
		);
	}
	tebravo_action_links();
}
#remove_filter( 'authenticate', 'wp_authenticate_username_password', 20 );
?>