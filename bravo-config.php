<?php 
/**
 * Bravo Config File
 * @since 1.0
 * Copyrights (C) 2017 Technoyer Solutions Ltd. <support@technoyer.com>
 */

//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

//Defination
if(!defined( 'TEBRAVO_SLUG' )){define( 'TEBRAVO_SLUG' , 'bravo-security' );}
if(!defined( 'TEBRAVO_PLUGINNAME' )){define( 'TEBRAVO_PLUGINNAME' , 'BRAVO WP Ultimate Security' );}
if(!defined( 'TEBRAVO_VERSION' )){define( 'TEBRAVO_VERSION' , '1.1' );}
if(!defined( 'TEBRAVO_PATH' )){define( 'TEBRAVO_PATH' , __FILE__ );}
if(!defined( 'TEBRAVO_DIR' )){define( 'TEBRAVO_DIR' , dirname(__FILE__) );}
if(!defined( 'TEBRAVO_PLUGINPREFIX' )){define( 'TEBRAVO_PLUGINPREFIX' , 'TEBRAVO' );}
if(!defined( 'TEBRAVO_TRANS' )){define( 'TEBRAVO_TRANS' , 'TEBRAVO' );}
if(!defined( 'TEBRAVO_VERSIONTYPE' )){define( 'TEBRAVO_VERSIONTYPE' , '' );}
if(!defined( 'TEBRAVO_SUPPORT_EMAIL' )){define( 'TEBRAVO_SUPPORT_EMAIL' , 'support@technoyer.com' );}
if(!defined( 'TEBRAVO_DBPREFIX' )){define( 'TEBRAVO_DBPREFIX' , '_tebravo_' );}
if(!defined( 'TEBRAVO_BACKUPFOLDER' )){
	//Define the backups folder in directory:
	//wp-content/plugins/bravo-security/backups_folder_name
	define( 'TEBRAVO_BACKUPFOLDER' , '_tebravo_backups' );
}
if(!defined( 'TEBRAVO_REWRITE_AS' )){
	//Define default server software
	//it is important for htaccess file
	define( 'TEBRAVO_REWRITE_AS', 'apache' );
}

if(!defined( 'TEBRAVO_NO_ACCESS_MSG' )){
	//Define not access message
	define( 'TEBRAVO_NO_ACCESS_MSG', __("You have no permissions to access this page!", TEBRAVO_TRANS) );
}

// Set The Technoyer Product Info
if(!defined( 'TEBRAVO_PRODUCTTYPE' )){define ('TEBRAVO_PRODUCTTYPE', 'wordpress-plugin');}

//path to GEOip
if(!defined( 'TEBRAVO_GEOPATH' )){define ('TEBRAVO_GEOPATH', TEBRAVO_DIR."/includes/Geo/GeoIP.dat");}
//dashboard template
if(!defined( 'TEBRAVO_DASHBOARD_TEMPLATE' )){define ('TEBRAVO_DASHBOARD_TEMPLATE', "default");}
//traffic tracker update
if(!defined( 'TEBRAVO_LIVEUPDATE_TIME' )){define ('TEBRAVO_LIVEUPDATE_TIME', "5000");} // 1000 = 1 sec
//antivirus
if(!defined( 'TEBRAVO_MAX_SCAN_FILESIZE' )){define ('TEBRAVO_MAX_SCAN_FILESIZE', 800*1000);} // bytes
//donate URL
if(!defined( 'TEBRAVO_DONATE_URL' )){define ('TEBRAVO_DONATE_URL', "http://bravo.technoyer.com");} // 1000 = 1 sec
//session save path
if(!defined( 'TEBRAVO_SESSION_SAVE_PATH_ENABLED' )){define ('TEBRAVO_SESSION_SAVE_PATH_ENABLED', false);}
if(!defined( 'TEBRAVO_SESSION_SAVE_PATH' )){define ('TEBRAVO_SESSION_SAVE_PATH', TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/sessions/');}

//change default session save path of server if needed
if( defined( 'TEBRAVO_SESSION_SAVE_PATH_ENABLED' )
		&& true==TEBRAVO_SESSION_SAVE_PATH_ENABLED )
{
	if( defined( 'TEBRAVO_SESSION_SAVE_PATH' ) && @is_dir( TEBRAVO_SESSION_SAVE_PATH ) )
	{
		session_save_path( TEBRAVO_SESSION_SAVE_PATH );
		ini_set('session.gc_probability', 1);
	}
}

//self defense or self protection options
if(!defined( 'TEBRAVO_SELFP' )){define ('TEBRAVO_SELFP', false);}
?>