<?php 
/**
 * Hook: BRAVO.CRONJOBS
 * The cronjob (schedules) system.
 * It is based on the wordpress cronjobs system.
 * @since 1.0
 * Copyrights (C) 2017 Technoyer Solutions Ltd. <support@technoyer.com>
 */

//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !class_exists( 'tebravo_cronjobs' ) )
{
	class tebravo_cronjobs {
		//options
		public $task;
		public $init;
		public $events=array();
		
		public $hookname="cronjobs";
		public $cronjob_status="";
		
		
		public function __construct()
		{
			//WP CRON status
			if(defined('DISABLE_WP_CRON'))
			{
				if(DISABLE_WP_CRON == true){$this->cronjob_status = "off";}else{$this->cronjob_status = "on";}
			} else {
				$this->cronjob_status = "on";
			}
			
			$this->events = tebravo_utility::get_option( TEBRAVO_DBPREFIX.'cronjobs_events', array() );
			
			//schedule
			
			//Actions
			if ($this->cronjob_status ) 
			{
				add_action( 'init', array( $this, 'init') );
				add_action( 'admin_init', array( $this, 'init') );
			}
			
			add_filter('cron_schedules', array( $this, 'schedules_interval'));
		}
		
		public function init()
		{
			//Add Filter to WP Cronjobs
			if( isset( $this->events ) 
					&& !empty( $this->events )
					&& is_array( $this->events ))
			{
				add_filter( 'cron_schedules',    array( $this, 'filter_schedule_events' ) );
			}
		}
		
		public function schedules_interval($schedules){
			if(!isset($schedules["1min"])){
				$schedules["1min"] = array(
						'interval' => 1*60,
						'display' => __('Once every minute'));
			}
			if(!isset($schedules["5min"])){
				$schedules["5min"] = array(
						'interval' => 5*60,
						'display' => __('Once every 5 minutes'));
			}
			if(!isset($schedules["30min"])){
				$schedules["30min"] = array(
						'interval' => 30*60,
						'display' => __('Once every 30 minutes'));
			}
			if(!isset($schedules["hourly"])){
				$schedules["hourly"] = array(
						'interval' => 60*60,
						'display' => __('Once every hour'));
			}
			if(!isset($schedules["daily"])){
				$schedules["daily"] = array(
						'interval' => 24*60*60,
						'display' => __('Once every day'));
			}
			if(!isset($schedules["twicedaily"])){
				$schedules["twicedaily"] = array(
						'interval' => 12*60*60,
						'display' => __('Twice Daily'));
			}
			if(!isset($schedules["twiceweekly"])){
				$schedules["twiceweekly"] = array(
						'interval' => 84*60*60,
						'display' => __('Twice every week'));
			}
			if(!isset($schedules["onceweekly"])){
				$schedules["onceweekly"] = array(
						'interval' => 7*24*60*60,
						'display' => __('Once every week'));
			}
			
			return $schedules;
		}
		
		
		//filyer to schedule events
		public function filter_schedule_events( $_tebravo_scheds ) {
			if( !empty( $this->events ) && is_array( $this->events))
			{
				return array_merge( $this->events, $_tebravo_scheds );
			}
		}
		
		//run cronjobs
		public function run_cronjob_events()
		{
			if( is_array( _tebravo_tasks_list() ) ):
			foreach(_tebravo_tasks_list() as $key => $value)
			{
				if ( !wp_next_scheduled( $key) ) {
					wp_schedule_event( time(), '5seconds', $key);
				}
			}
			endif;
		}
		
		//Cronjobs Dashboard
		public function dashboard()
		{
			$this->html = new tebravo_html();
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( ); exit;}
			//Save Schedule
			if($_POST)
			{
				$this->save();
				exit;
			}
			
			//Get List
			$list = true;
			#var_dump(_get_cron_array());
			//Show Dashboard // New Schedule
			if($this->cronjob_status == 'on'){
				$conjob_icon_status="<font color='green'>ON</font>";
			} else {
				$conjob_icon_status="<font color='brown'>OFF</font>";
			}
			$cronjobstatus = __("WP Cronjob Status", TEBRAVO_TRANS).": ".$conjob_icon_status;
			
			$desc = "Cronjobs, or Schedules.. The most useful management section in the plugin, You should use it carefully and wisely.<br>";
			$desc .= "Do not waste your time with the non-useful tasks, Schedule only the important tasks.";
			$this->html->header(__("Cronjobs", TEBRAVO_TRANS), $desc, false, $cronjobstatus);
			
			//tebravo_utility::update_option( TEBRAVO_DBPREFIX.'cronjobs_events', 'BBB');
			//Content
			
			
			if( !$_POST)
			{
				echo $this->list_schedule();
			} else {
				$this->save();
			}
			
			
			$this->html->footer();
			
		}
		
		//List Events/Schedules
		public function list_schedule()
		{
			$this->html = new tebravo_html();
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( ); exit;}
			
			$output[] = '<form action="'.$this->html->init->admin_url.'-cronjobs" method=post>';
			$output[] = '<input type="hidden" name="_nonce" value="'.$this->html->init->create_nonce('save-cronjobs').'">';
			$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
			$output[] = '<table border="0" width="100%" id="table1" cellspacing="0" cellpadding="0" class="wp-list-table widefat">';
			$output[] = '<thead>';
			$output[] = '<tr class="manage-column column-cb check-column">';
			$output[] = '<td height="30"><strong>'.__("Task", TEBRAVO_TRANS).'</strong></td>';
			$output[] = '<td height="30" width="35%"><strong>'.__("Next Run", TEBRAVO_TRANS).'</strong></td>';
			$output[] = '<td height="30" width="15%"><center><strong>'.__("Action", TEBRAVO_TRANS).'</strong></center></td>';
			$output[] = '<td height="30" width="7%"></td>';
			$output[] = '</tr>';
			$output[] = '</thead>';
			$output[] = '<tbody>';
			
			$icon_noti_help = "<img src='".plugins_url("assets/img/email_noti_small.png", $this->html->init->path)."'>";
			$icon_delete = "<img src='".plugins_url("assets/img/delete.png", $this->html->init->path)."' title='".__('Delete', TEBRAVO_TRANS)."'>";
			
			$list_schedules = tebravo_utility::get_option(TEBRAVO_DBPREFIX.'cronjobs_events', array());
			$wp_crons = get_option('cron', array());
			
			echo "<pre>";
			
			echo "</pre>";
			$i=0;
			foreach (_tebravo_tasks_list() as $key => $value)
			{
				//echo $key."<br />";
				$hook = TEBRAVO_DBPREFIX.$key;
				
				$status_interval = '<font color=brown>'.__("Disabled", TEBRAVO_TRANS).'</font>'; 
				$status_nexrun = '<font color=brown>'.__("Disabled", TEBRAVO_TRANS).'</font>';
				$tools = '';
				$min_1='';
				$min_5='';
				$min_30='';
				$hr_1='';
				$d_1='';
				$d_2='';
				$w_1='';
				$w_2='';
				$email_tools='';
				$email='';
				$disabled = 'selected';
				//var_dump($list_schedules);
				if( is_array( $list_schedules) )
				{
					$hook_key = $this->get_cron_task($hook, 'key');
					$interval = $this->get_cron_task($hook, 'interval');
					$mtime = $this->get_cron_task($hook, 'time');
					
					$email = _tebravo_event_details($key, 'email');
					if( $interval!='' )
					{
						$status_interval = $this->get_period_by_interval($interval);
					}
					$nextRun = '--';
					
					if(!empty($mtime)){
						//$i++;
						$nextRun = get_date_from_gmt( date( 'Y-m-d H:i:s', $mtime) );
					}
					if( $interval!='' )
					{
						$status_nexrun= $nextRun.'<br><font color=green>'.$this->html->init->since_ago(time(), $mtime);
					}
					
					switch ($interval)
					{
						case (1*60): $min_1 = 'selected'; break;
						case (5*60): $min_5 = 'selected'; break;
						case (30*60): $min_30 = 'selected'; break;
						case (60*60): $hr_1 = 'selected'; break;
						case (24*60*60): $d_1 = 'selected'; break;
						case (12*60*60): $d_2 = 'selected'; break;
						case (84*60*60): $w_2 = 'selected'; break;
						case (7*24*60*60): $w_1 = 'selected'; break;
					}
				}
				
				$tools .= "<select name='".$key."'>";
				$tools .= "<option value='disabled' $disabled>".__("Disabled", TEBRAVO_TRANS)."</option>";
				$tools .= "<option value='".(1*60)."' $min_1>".__("Once every minute", TEBRAVO_TRANS)."</option>";
				$tools .= "<option value='".(5*60)."' $min_5>".__("Once every 5 minutes", TEBRAVO_TRANS)."</option>";
				$tools .= "<option value='".(30*60)."' $min_30>".__("Once every 30 minutes", TEBRAVO_TRANS)."</option>";
				$tools .= "<option value='".(60*60)."' $hr_1>".__("Once every hour", TEBRAVO_TRANS)."</option>";
				$tools .= "<option value='".(24*60*60)."' $d_1>".__("Once every day", TEBRAVO_TRANS)."</option>";
				$tools .= "<option value='".(12*60*60)."' $d_2>".__("Twice Daily", TEBRAVO_TRANS)."</option>";
				$tools .= "<option value='".(84*60*60)."' $w_2>".__("Twice every week", TEBRAVO_TRANS)."</option>";
				$tools .= "<option value='".(7*24*60*60)."' $w_1>".__("Once every week", TEBRAVO_TRANS)."</option>";
				$tools .= "</select>";
				
				$admin_email = tebravo_utility::get_option('admin_email');
				$email_tools .= "<input type='checkbox' name='noti_".$key."' value='$admin_email' id='$key' ";
				if( !empty( $email ) && $email==$admin_email){$email_tools .= "checked";}
				$email_tools .= "><label for='$key'>".__("Email Notifications", TEBRAVO_TRANS)."</label>";
				
				$icon_noti = "<img src='".plugins_url("assets/img/email_noti.png", $this->html->init->path)."' title='$email'>";
				
				$output[] = '<tr class="tebravo_underTD">';
				$output[] = '<td height="30"><b>'.__($value, TEBRAVO_TRANS).'</b>';
				
				$output[] = "<br />".$email_tools;
				$output[] = '</td>';
				$output[] = '<td height="30" width="35%">'.$status_nexrun.'</font></td>';
				#$output[] = '<td height="30" width="25%">'.$rec.'</td>';
				$output[] = '<td height="30" width="15%">';
				$output[] = '<center>'.$tools.'</center>';
				$output[] = '</td>';
				$output[] = '<td height="30" width="7%"><center>'.($email != ''? $icon_noti : '').'</center></td>';
				$output[] = '</tr>';
			}
			
			
			$output[] = '</tbody>';
			$output[] = '<tr><td colspan=2></td>';
			$output[] = '<td width="15%">'.$this->html->button_small(__("Save and Update", TEBRAVO_TRANS), "submit").'</td>';
			$output[] = '</tr>';
			$output[] = '</table></form></div>';
			$output[] = '<p>'.$icon_noti_help.' '.__("It means that email notification enabled.", TEBRAVO_TRANS).'</p>';
			
			return implode("\n", $output);
			
			
		}
		
		public function save()
		{
			$helper = new tebravo_html();
			if( !$_POST
					|| !isset($_POST['_nonce'])
					|| false===wp_verify_nonce($_POST['_nonce'], $helper->init->security_hash.'save-cronjobs'))
			{
				wp_die(TEBRAVO_NO_ACCESS_MSG);
			}
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			$events_name = _tebravo_events_name();
			$admin_email = tebravo_utility::get_option('admin_email');
			foreach (_tebravo_tasks_list() as $key => $value)
			{
				if( isset($key ) && !empty($key) && isset( $_POST[$key] ) )
				{
					$recurrence = '';
					$emails = '';
					if( $_POST[$key] != 'disabled' )
					{
						$recurrence = sanitize_text_field($events_name[$_POST[$key]]);
					}
					
					$hook = TEBRAVO_DBPREFIX.$key;
					
					$hook_key = $this->get_cron_task($hook, 'key');
					if( $_POST[$key] != 'disabled' )
					{
						if (! wp_next_scheduled ( $hook ) && !empty( $recurrence )) {
							wp_schedule_event(time(), $recurrence, $hook );
							if( isset($_POST['noti_'.$key] ) && !empty($_POST['noti_'.$key]))
							{
								$emails = $admin_email;
							}
							$this->add_schedule_event($key, sanitize_text_field($_POST[$key]), $hook, $emails);
						} else {
							$hook_key = $this->get_cron_task($hook, 'key');
							$mtime = $this->get_cron_task($hook, 'time');
							$interval = $this->get_cron_task($hook, 'interval');
							$email = _tebravo_event_details($key, 'email');
							//update email again
							if( !isset($_POST['noti_'.$key]) || $email!=$_POST['noti_'.$key])
							{
								$emails = '';
								if( isset($_POST['noti_'.$key]) && $_POST['noti_'.$key]== $admin_email ){$emails = $_POST['noti_'.$key];}
								//$emails = ($_POST['noti_'.$key]!='')?$admin_email:'';
								//delete cron
								_tebravo_delete_schedule(sanitize_text_field($_POST[$key]), $recurrence, $hook);
								$this->delete_cronjob($mtime, $hook, $hook_key, $interval, $hook);
								//re-add it again
								wp_schedule_event($mtime, $recurrence, $hook );
								$this->add_schedule_event($key, sanitize_text_field($_POST[$key]), $hook, $emails);
							}
							//update recurrence
							if( $_POST[$key]!=$interval )
							{
								$emails = !empty($_POST['noti_'.$key])?$email:'';
								//delete cron
								_tebravo_delete_schedule(sanitize_text_field($_POST[$key]), $recurrence, $hook);
								$this->delete_cronjob(time(), $hook, $hook_key, $interval, $hook);
								//re-add it again
								wp_schedule_event(time(), $recurrence, $hook );
								$this->add_schedule_event($key, sanitize_text_field($_POST[$key]), $hook, $emails);
							}
							/*if( empty($_POST['noti_'.$key]) )
							{
								$mtime = $this->get_cron_task($hook, 'time');
								_tebravo_delete_schedule_email_only($_POST[$key], $recurrence, $hook);
								wp_schedule_event($mtime, $recurrence, $hook );
								$this->add_schedule_event($key, $_POST[$key], $hook, $emails);
							} else {
								$mtime = $this->get_cron_task($hook, 'time');
								_tebravo_delete_schedule_email_only($_POST[$key], $recurrence, $hook);
								wp_schedule_event($mtime, $recurrence, $hook );
								
								$this->add_schedule_event($key, $_POST[$key], $hook, $email);
							}*/
							//END update email again
						}
					} else {
						$hook_key = $this->get_cron_task($hook, 'key');
						$interval = $this->get_cron_task($hook, 'interval');
						$mtime = $this->get_cron_task($hook, 'time');
						wp_clear_scheduled_hook($hook);
						$this->delete_cronjob($mtime, $hook, $hook_key, $interval, $hook);
					}
				}
			}
			
			tebravo_redirect_js($helper->init->admin_url.'-cronjobs&msg=03');
		}
		public function get_cron_task( $task , $req)
		{
			$this->html = new tebravo_html();
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( ); exit;}
			$crons = get_option('cron', array());
			$events = array();

			if (! empty( $crons ) && is_array( $crons )) {
				
			foreach ( $crons as $time => $cron ) {
				
				if( isset( $cron ) && is_array( $cron ) )
				{
					
					foreach ( $cron as $hook => $vs ) {
					
						
							if( isset( $vs ) && is_array( $vs ) )
							{
								
								foreach ( $vs as $mkey => $data ) {
									
									if($task == $hook){
										
										if($req == 'interval'){
											$output = isset($data['interval'])?$data['interval']:null;
										} else if ($req == 'time')
										{
											$output =esc_html( $time );
										} else if ($req == 'key')
										{
											$output =esc_html( $mkey );
										} else if ($req == 'schedule')
										{
											$output =$data['schedule'];
										} else if ($req == 'email')
										{
											$output =$data['email'];
										}else {
											$output = "";
										}
									}
								}
							}
						
					}
				}
			}

			}
			if(!empty($output)):
				return $output;
			endif;
			
		}
		
		public function get_period_by_interval( $interval )
		{
			$this->html = new tebravo_html();
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( ); exit;}
			$list = _tebravo_period_list();
			if( is_array( $list ) ){
				foreach ($list as $key => $value)
				{
					if($interval == $key)
					{
						$output = $value;
					}
				}
			}
			
			if(!empty($output)){
				return $output;
			}
			return '';
		}
		
		//add schedule event
		public function add_schedule_event( $name, $interval, $display , $emails = false) {
			$this->html = new tebravo_html();
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( ); exit;}
			//check capability
			if(! current_user_can('manage_options')){wp_die(); exit;}
			
			if( !tebravo_utility::is_option( 'cronjobs_events' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'cronjobs_events' , '' ); }
			
			$_tebravo_old_scheds = tebravo_utility::get_option( TEBRAVO_DBPREFIX.'cronjobs_events', array() );
			/*$_tebravo_old_scheds[ (string)$name ]['interval'] = esc_html( $interval );
			$_tebravo_old_scheds[ (string)$name ]['display'] = esc_html( $display );
			if( !$emails ){$emails = '';}
			if($emails){
				$_tebravo_old_scheds[ (string)$name ]['email'] = esc_html( $emails );
			}*/
			
			$new_schedule = array( $name => array( 
						'interval' => esc_html( $interval ),
						'display' => esc_html( $display ),
						'email' => esc_html( $emails),
			)
			);
			
			if( is_array( $_tebravo_old_scheds ) && $_tebravo_old_scheds!='')
			{
				$new_value = array_merge( $_tebravo_old_scheds, $new_schedule );
			} else {
				$new_value = $new_schedule;
			}
			//var_dump($new_value) ; exit;
			
			if( is_array($_tebravo_old_scheds) && in_array($name, $_tebravo_old_scheds))
			{
				tebravo_die(true, __("You can not duplicate events or hooks.", TEBRAVO_TRANS), false, true);
			}
			
			tebravo_utility::update_option( TEBRAVO_DBPREFIX.'cronjobs_events', $new_value);
		}
		
		//delete schedlue event
		public function delete_schedule_event( $name ) {
			$this->html = new tebravo_html();
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( ); exit;}
			//check capability
			if(! current_user_can('manage_options')){wp_die(); exit;}
			
			$_tebravo_scheds = tebravo_utility::get_option( TEBRAVO_DBPREFIX.'cronjobs_events', array() );
			unset( $_tebravo_scheds[ $name ] );
			tebravo_utility::update_option( TEBRAVO_DBPREFIX.'cronjobs_events', $_tebravo_scheds );
		}
		
		
		//Add New Schedule
		public function add_new_schedule()
		{
			$this->html = new tebravo_html();
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die(TEBRAVO_NO_ACCESS_MSG); exit;}
			
			$nonce = @esc_html($_POST['_nonce']);
			$period= @sanitize_text_field($_POST['period']);
			$task= @sanitize_text_field($_POST['task']);
			$sendemail = '';
			if( isset($_POST['sendemail']) )
			{
				$sendemail= @sanitize_email($_POST['sendemail']);
			}
			$adminemail = '';
			if( isset($_POST['adminemail']) )
			{
				$adminemail= @sanitize_email($_POST['adminemail']);
			}
			
			#echo $nonce;
			if(empty($nonce) ||
					wp_verify_nonce($nonce, $this->html->init->security_hash.'save-new-schedule') == false
					|| empty($period)
					|| empty($task)){
				#@setcookie("site_error", $this->html->init->errors("02"), time()+200);
				
				$redirect_to = $this->html->init->admin_url."-".$this->hookname."&err=02";
				tebravo_redirect_js($redirect_to);
			} else {
				$period_x = @explode("#", $period);
				$task_x = @explode("#", $task);
				$interval = $period_x[0];
				$slug = $period_x[1];
				$display = str_replace('_', ' ', $task_x[1]);
				
				if(!empty($sendemail) && $sendemail == 'checked' && !empty($adminemail))
				{
					$emails = $adminemail;	
				} else {
					$emails = "";
				}
				$this->add_schedule_event($task_x[0], $interval, $display, $emails);
				
				$redirect_to = $this->html->init->admin_url."-".$this->hookname."&msg=02";
				#wp_redirect( $redirect_to );
				tebravo_redirect_js($redirect_to);
			}
			exit;
		}
		
		//Save Schedule
		public function save_schedule($interval , $slug , $display) {
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( ); exit;}
			global $schedules, $interval, $slug, $display;
			$schedules[$slug] = array(
					'interval' => $interval,
					'display'  => esc_html__( $display),
			);
			
			return $schedules;
		}
		
		public function add_new_cron($next_run, $schedule, $action_hook, $args=false)
		{
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( ); exit;}
			$next_run = time()+$next_run;
			if ( ! wp_next_scheduled( $schedule) ) {
				wp_schedule_event( esc_html( $next_run ), esc_html( $schedule ), esc_html( $action_hook ), esc_html( $args ) );
			}
			
		}
		
		public function delete_cronjob($nextrun, $hook, $key, $interval, $displayname)
		{
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( ); exit;}
			
			if( empty($nextrun) or empty( $hook ) ) {return;}
			
			_tebravo_delete_crons(esc_html( $nextrun ), esc_html( TEBRAVO_DBPREFIX.$hook ), esc_html( $key ));
			_tebravo_delete_schedule(esc_html( $hook ), esc_html( $interval ), esc_html( $displayname ));
		}
		//Install Hook
		public function install_hook()
		{
			if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'cronjobs_events' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'cronjobs_events' , '' ); }
			if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'cronjobs_enabled' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'cronjobs_enabled' , 'checked' ); }
			if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'cronjobs_emails_enabled' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'cronjobs_emails_enabled' , 'checked' ); }
			if( !tebravo_utility::is_option( TEBRAVO_DBPREFIX.'cronjobs_emails' )){ tebravo_utility::add_option( TEBRAVO_DBPREFIX.'cronjobs_emails' , tebravo_utility::get_option( 'admin_email' ) ); }
			
			do_action( 'tebravo_cronjobs_install_hook' );
		}

	}
	//run
	new tebravo_cronjobs();
}


?>