<?php 

if (!defined('ABSPATH')){exit; // Exit if get it directly!
}

if( !function_exists( '_tebravo_tasks_list' )){
    function _tebravo_tasks_list()
    {
        $tasks = array(
            "dbbackup" => "Database Backup",
            "dbscan" => "Database Scan",
            "filechange" => "File Change Detection",
            "traffictracker" => "Traffic Tracker Monitor Update",
        );
        
        return $tasks;
    }
}

if( !function_exists( '_tebravo_period_list' )){
    function _tebravo_period_list()
    {
        $periods = array(
            60 => "Every Minute",
            5*60 => "Every 5 Minutes",
            15*60 => "Every 15 Minutes",
            30*60 => "Twice per Hour",
            60*60 => "Once per Hour",
            6*60*60 => "4 Times per Day",
            8*60*60 => "3 Times per Day",
            12*60*60 => "Twice every Day",
            24*60*60 => "Once every Day",
            84*60*60 => "Twice every Week",
            7*24*60*60 => "Once per Week",
            30*24*60*60 => "Once per Month",
        );
        
        return $periods;
    }
}

if( !function_exists( '_tebravo_events_name' )){
	function _tebravo_events_name()
	{
		$periods = array(
				60 => "1min",
				5*60 => "5min",
				15*60 => "15min",
				30*60 => "30min",
				60*60 => "hourly",
				6*60*60 => "4day",
				8*60*60 => "3day",
				12*60*60 => "twicedaily",
				24*60*60 => "daily",
				84*60*60 => "twiceweekly",
				7*24*60*60 => "onceweekly",
				30*24*60*60 => "monthly",
		);
		
		return $periods;
	}
}

if(!function_exists( '_tebravo_event_details' ))
{
    function _tebravo_event_details( $event , $req)
    {
        $events = tebravo_utility::get_option( TEBRAVO_DBPREFIX.'cronjobs_events' , array() );
        
        if(! empty( $events[$event][$req] ) ){
            $output = esc_html( $events[$event][$req] );
            return $output;
        } 
    }
}

if(!function_exists( '_tebravo_delete_crons' ))
{
    function _tebravo_delete_crons($nextrun, $hook, $key)
    {
    	if( empty($nextrun) or empty( $hook ) ) {return;}
    	$html = new tebravo_html();
    	//check permissions
    	if( false === $html->init->get_access( 'manage_options' , true )) {wp_die( ); exit;}
        $crons = _get_cron_array();
        if ( isset( $crons[ $nextrun ][ $hook ][ $key ] ) ) {
            $args = $crons[ $nextrun ][ $hook][ $key]['args'];
            wp_unschedule_event( $nextrun, $hook, $args );
            return true;
        } 
        return false;
    }
}

if(!function_exists( '_tebravo_delete_schedule' ))
{
    function _tebravo_delete_schedule( $slug, $interval, $display ) {
    	if( empty($interval) or empty( $slug) ) {return;}
        $scheduls = tebravo_utility::get_option( TEBRAVO_DBPREFIX.'cronjobs_events', array() );
        unset( $scheduls[$slug] );
        tebravo_utility::update_option( TEBRAVO_DBPREFIX.'cronjobs_events', $scheduls);
        wp_clear_scheduled_hook( $display);
    }
}

if(!function_exists( '_tebravo_delete_schedule_email_only' ))
{
	function _tebravo_delete_schedule_email_only( $slug, $interval, $display ) {
		$scheduls = tebravo_utility::get_option( TEBRAVO_DBPREFIX.'cronjobs_events', array() );
		unset( $scheduls[$slug]['email'] );
		tebravo_utility::update_option( TEBRAVO_DBPREFIX.'cronjobs_events', $scheduls);
		wp_clear_scheduled_hook( $display);
	}
}
?>