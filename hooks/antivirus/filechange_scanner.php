<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}
if( !class_exists( 'tebravo_filechange_scanner' ) )
{
	
    class tebravo_filechange_scanner extends tebravo_antivirus_utility{
    	public $ipaddress,
    	$pid,
    	$file_to_store,
    	$file,
    	$all_files,
    	$dir,
    	$dirs = array(),
    	$report = array(),
    	$report_altered= array(),
    	$report_added = array(),
    	$report_deleted = array(),
    	$report_countfiles,
    	$no_update,
    	$oldStoredData,
    	$newStoredData;
    	
    	//constructor
    	public function __construct()
    	{
    		$this->dir = getcwd();
    		
    		$this->file = 'filechange.txt';
    		$this->file_to_store = TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/'.$this->file;
    		
    		if( !is_dir( $this->dir ) )
    		{
    			trigger_error('<strong>Error:</strong> '.esc_html( $this->dir ).' is not directory!');
    		}
    	}
    	
    	//do scan
    	public function doScan ( $dir = false){
    		
    		$memory_limit = trim( esc_html( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'memory_limit') ) );
    		if( $memory_limit != 'default')
    		{
    			@ini_set('memory_limit', $memory_limit);
    		}
    		
    		if(empty($dir)){
    			$dir = getcwd();
    		}
    		
    		$dirs = array();
    		
    		$handle = opendir($dir);
    		
    		if( ! $handle )
    			return $dirs;
    			
    			while ( false !== ( $file = readdir( $handle ) ) )
    			{
    				if( "." == $file || ".." == $file )
    					continue;
    					
    					$full_file_name = $dir . DIRECTORY_SEPARATOR . $file;
    					$full_dir_file_name = $full_file_name;
    					
    					if( false === strpos($full_file_name, $this->file) ):
    					
    					if( 'dir' === filetype( $full_dir_file_name ) )
    					{
    						// We are on a directory lets go one deeper
    						$dirs = array_merge( (array) $dirs, (array) $this->doScan( $full_file_name ) );
    					} else {
    						$dirs[utf8_encode($full_file_name)] = array();
    						
    						$dirs[utf8_encode($full_file_name)]["size"] = filesize( $full_dir_file_name );
    						$dirs[utf8_encode($full_file_name)]["modified"] = filemtime( $full_dir_file_name );
    						
    					}
    					
    					endif;
    					
    			}
    			
    			closedir( $handle );
    			
    			return $dirs;
    	}
    	
    	protected function handleData ($method = "get", $data = "")
    	{
    		
    		//check if writable
    		$do_chmod = 0;
    		if( !is_writable($this->file_to_store) )
    		{
    			tebravo_files::dochmod($this->file_to_store, '0666');
    			$do_chmod = 1;
    		}
    		
    		//check new perma
    		if( $do_chmod == 1 && tebravo_files::file_perms( $this->file_to_store) != '0666')
    		{
    			$helper = new tebravo_html();
    			$message = __("Can not write to file", TEBRAVO_TRANS)."<br /><i>".$this->file_to_store."</i>";
    			?>
    			<script>jQuery('.tebravo_loading').hide();</script>
    			<?php 
    			tebravo_die(true, $message, false, false);
    		}
    		
    		if("get" == $method)
    		{
    			if(!file_exists($this->file_to_store)){return false;}
    			try{
	    			$json = file_get_contents($this->file_to_store);
	    			//mb_convert_encoding($json,'UTF-8','UTF-8');
	    			$data = json_decode($json, true);
    			}catch (Exception $e)
    			{
    				tebravo_errorlog::errorlog($e->Message());
    			}
    		}
    		
    		if(!empty($data)){
    			try{
    				file_put_contents($this->file_to_store, json_encode($data));
    			}catch (Exception $e)
    			{
    				tebravo_errorlog::errorlog($e->Message());
    			}
    		}
    		
    		return $data;
    	}
    	//comapring arrays to get difference
    	protected function array_compare( $array1, $array2 )
    	{
    		$diff = false;
    			
    		if( file_exists($this->file_to_store) && (filesize($this->file_to_store) >= 1 || empty( $this->file_to_store)) )
    		{
    			ini_set('display_errors', 'off');
    		}
    		
    		foreach( $array1 as $key => $value )
    		{
    			if( ! array_key_exists( $key, $array2 ) )
    			{
    				$diff[0][$key] = $value;
    			} elseif ( is_array( $value ) )
    			{
    				if ( ! is_array( $array2[$key] ) )
    				{
    					$diff[0][$key] = $value;
    					$diff[1][$key] = $array2[$key];
    				} else
    				{
    					$new = $this->array_compare( $value, $array2[$key] );
    					
    					if ( $new !== false )
    					{
    						if ( isset( $new[0] ) )
    							$diff[0][$key] = $new[0];
    							
    							if ( isset( $new[1] ) )
    								$diff[1][$key] = $new[1];
    					}
    				}
    			} elseif ( $array2[$key] !== $value )
    			{
    				$diff[0][$key] = $value;
    				$diff[1][$key] = $array2[$key];
    			}
    		}
    		
    		foreach ( $array2 as $key => $value )
    		{
    			if ( ! array_key_exists( $key, $array1 ) )
    				$diff[1][$key] = $value;
    		}
    		
    		return $diff;
    	}
    	
    	//scan path
    	public function scan ( $path )
    	{
    		$dirs = $this->doScan( $path );
    		
    		$oldStoredData = $this->handleData("get", $dirs);
    		$newStoredData = $this->handleData("put", $dirs);
    		//echo var_dump($dirs); exit;
    		$files_added = @array_diff_assoc($newStoredData, $oldStoredData);
    		$files_deleted = @array_diff_assoc($oldStoredData, $newStoredData);
    		
    		$compNewData = @array_diff_key($newStoredData, $files_added);
    		$compOldData = @array_diff_key($oldStoredData, $files_deleted);
    		
    		$getDif = $this->array_compare($compNewData, $compOldData);
    		
    		$changed = @count($getDif);
    		$added = @count($files_added);
    		$deleted = @count($files_deleted);
    		
    		if(!max($changed, $added, $deleted)) return ;
    		
    		$this->filter_alert($files_added, $files_deleted, $getDif, $oldStoredData, $newStoredData);
    	}
    	
    	protected function filter_alert ($added, $deleted, $altered, $oldData, $newData)
    	{
    		global $wpdb;
    		
    		if( $this->no_update != 1 ){
	    		$wpdb->update(tebravo_utility::dbprefix().'scan_ps', array(
	    				'status'=>'running',
	    				
	    		), array (
	    				'pid' => $this->pid
	    		));
    		}
    		
    		$this->oldStoredData = $oldData;
    		$this->newStoredData = $newData;
    		
    		//Altered Files
    		if(count( $altered[0] ) >= 1){
    			/*$report .= "<h3>Files Changed (Altered)</h3>";
    			echo '<pre>';
    			print_r($altered);
    			echo '</pre>';
    			foreach ($altered[0] as $key => $value)
    			{
    				$report .= "<strong>".$key."</strong><hr>";
    				
    				$oldSize = ( $oldData[$key]['size'] );
    				$newSize = ( $newData[$key]['size'] );
    				
    				$report .= "Size was <i>".$oldSize."</i> , changed to <i>".$newSize."</i><br />";
    				
    				$oldDate = date('d-M-y h:i:s a', $oldData[$key]['modified']);
    				$newDate = date('d-M-y h:i:s a', $newData[$key]['modified']);
    				
    				$report .= "Past modify date <i>".$oldDate."</i> , Last modify date <i>".$newDate."</i><hr>";
    			}*/
    			$this->report_altered[] = $altered[0];
    		}
    		
    		//New Files
    		if(count( $added ) >= 1)
    		{
    			/*$report .= "<h3>New Files</h3>";
    			echo '<pre>';
    			print_r($added);
    			echo '</pre>';
    			
    			foreach ($added as $key => $value)
    			{
    				$report .= "<strong>".$key."</strong><hr>";
    				
    				$Size = ( $newData[$key]['size'] );
    				
    				$report .= "Size <i>".$Size."</i><br />";
    				
    				$Date = date('d-M-y h:i:s a', $newData[$key]['modified']);
    				
    				$report .= "Modify date <i>".$Date."</i><hr>";
    			}*/
    			$this->report_added[] =  $added;
    		}
    		
    		//Deleted Files
    		if(count( $deleted ) >= 1)
    		{
    			/*$report .= "<h3>Deleted Files</h3>";
    			echo '<pre>';
    			print_r($deleted);
    			echo '</pre>';
    			
    			foreach ($deleted as $key => $value)
    			{
    				$report .= "<strong>".$key."</strong><hr>";
    				
    				$Size = ( $oldData[$key]['size'] );
    				
    				$report .= "Size <i>".$Size."</i><br />";
    				
    				$Date = date('d-M-y h:i:s a', $oldData[$key]['modified']);
    				
    				$report .= "Modify date <i>".$Date."</i><hr>";
    			}
    			*/
    			$this->report_deleted[] = $deleted;
    		}
    		
    		
    		$this->report_countfiles = count( $altered[0] ) + count( $added ) + count( $deleted );
    		
    		$infected_results = '[altered]'.json_encode($altered[0]);
    		$infected_results .= '[added]'.json_encode($added);
    		$infected_results .= '[deleted]'.json_encode($deleted);
    		
    		if( $this->no_update != 1 ){
	    		$wpdb->update(tebravo_utility::dbprefix().'scan_ps', array(
	    				'status'=>'finished',
	    				'infected_results'=>$infected_results,
	    				'infected_files'=>$this->report_countfiles,
	    				
	    		), array (
	    				'pid' => $this->pid
	    		));
    		}
    		
    		
    	}
    	
    	public function fetch_results( $str=array() , $oldData, $newData , $type)
    	{
    		$helper = new tebravo_html();
    		$output = '';
    		$oldsize = '--';
    		$newsize = '--';
    		$olddate = '--';
    		$newdate = '--';
    		$scan_results = '--';
    		$is_scan_malware= false;
    		$is_scan_phpmussel= false;
    		$scan_output = 0;
    		$infected_action = '';
    		$fchange_infected_action = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'fchange_infected_action' ) ) );
    		
    		global $wpdb;
    		
    		$query = $wpdb->get_row( "SELECT start_by FROM ".tebravo_utility::dbprefix()."scan_ps WHERE pid='".$this->pid."' Limit 1");
    		
    		
    		//set antivirus options
    		if( is_array( $str ) && count( $str ) > 0)
    		{
    			//New files scanner options
    			if( $type == 'new' )
    			{
    				$new_scan_option = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'scan_fchange_new' ) ) );
    				if( $new_scan_option != 'never' )
    				{
    					$class_name_malware = 'tebravo_malware_scanner';
    					$class_name_phpmussel = 'tebravo_phpmussel_scanner';
    					
    					//malware
    					if( $new_scan_option == 'malware')
    					{
    						if( class_exists( $class_name_malware) )
    						{
    							$scan_malware = new $class_name_malware();
    							$is_scan_malware= true;
    						}
    					} // phpMussel 
    					else if( $new_scan_option == 'phpmussel')
    					{
    						if( class_exists( $class_name_phpmussel) )
    						{
    							$scan_phpmussel= new $class_name_phpmussel();
    							$is_scan_phpmussel= true;
    						}
    					} //BOTH Scanners
    					else if( $new_scan_option == 'both')
    					{
    						if( class_exists( $class_name_phpmussel) )
    						{
    							$scan_malware = new $class_name_malware();
    							$is_scan_malware= true;
    							
    							$scan_phpmussel= new $class_name_phpmussel();
    							$is_scan_phpmussel= true;
    						}
    					}
    					
    				}
    			} // Altered files scanner options
    			else if( $type == 'altered' )
    			{
    				$altered_scan_option = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'scan_fchange_altered' ) ) );
    				if( $altered_scan_option != 'never' )
    				{
    					$class_name_malware = 'tebravo_malware_scanner';
    					$class_name_phpmussel = 'tebravo_phpmussel_scanner';
    					
    					//malware
    					if( $altered_scan_option == 'malware')
    					{
    						if( class_exists( $class_name_malware) )
    						{
    							$scan_malware = new $class_name_malware();
    							$is_scan_malware= true;
    						}
    					} // phpMussel 
    					else if( $altered_scan_option == 'phpmussel')
    					{
    						if( class_exists( $class_name_phpmussel) )
    						{
    							$scan_phpmussel= new $class_name_phpmussel();
    							$is_scan_phpmussel= true;
    						}
    					} //BOTH Scanners
    					else if( $altered_scan_option == 'both')
    					{
    						if( class_exists( $class_name_phpmussel) )
    						{
    							$scan_malware = new $class_name_malware();
    							$is_scan_malware= true;
    							
    							$scan_phpmussel= new $class_name_phpmussel();
    							$is_scan_phpmussel= true;
    						}
    					}
    					
    				}
    			}
    		}
    		$scan_output = 0;
    		$scan_results = '';
    		//fetching output and files details
    		foreach ($str as $key => $value)
    		{
    			$output .= "<tr class='tebravo_underTD'><td width=60%>".$key."</td>";
    			$file_to_scan = tebravo_encodeString($key, $helper->init->security_hash);
    			if( !empty($oldData[$key]['size']) ){
    				$oldsize = $oldData[$key]['size'];
    			}
    			
    			if( !empty($newData[$key]['size']) ){
    				$newsize = $newData[$key]['size'];
    			}
    			
    			$output .= "<td width=10%>".$oldsize." / ".$newsize."</td>";
    			
    			if( !empty($oldData[$key]['modified']) ){
    				$olddate= date('d-M-y h:i:s a', $oldData[$key]['modified']);
    			}
    			
    			if( !empty($newData[$key]['modified']) ){
    				$newdate= date('d-M-y h:i:s a', $newData[$key]['modified']);
    			}
    			$the_oldDate = $olddate;
    			$the_newDate = $newdate;
    			
    			if( $this->no_update != 1){
	    			//scan for malware
	    			if( $is_scan_malware )
	    			{
	    				if( $scan_malware->scan( $key ) != 1 )
	    				{
	    					$scan_output = 0;
	    					$scan_results = __("Clean", TEBRAVO_TRANS);
	    					$scan_results = "<font color=green>".$scan_results."</font>";
	    				} else {
	    					if( $fchange_infected_action == 'quarantine' )
	    					{
	    						$scan_malware->move_to_quarantine( $key, false );
	    						$infected_action = __("Moved to quarantine", TEBRAVO_TRANS);
	    					}
	    					$scan_output++;
	    					$scan_results = __("Infected", TEBRAVO_TRANS);
	    					$scan_results = "<font color=red>".$scan_results."</font>";
	    				}
	    			}
	    			
	    			//scan with phpMussel
	    			if( $is_scan_phpmussel )
	    			{
	    				if( $scan_phpmussel->scan( $key ) != 1 )
	    				{
	    					$scan_output = 0;
	    					$scan_results = __("Clean", TEBRAVO_TRANS);
	    					$scan_results = "<font color=green>".$scan_results."</font>";
	    				} else {
	    					if( $fchange_infected_action == 'quarantine' )
	    					{
	    						$scan_phpmussel->move_to_quarantine( $key, true );
	    						$infected_action = __("Moved to quarantine", TEBRAVO_TRANS);
	    					}
	    					$scan_output++;
	    					$scan_results = __("Infected", TEBRAVO_TRANS);
	    					$scan_results = "<font color=red>".$scan_results."</font>";
	    				}
	    			}
	    			
	    			//setup final result
	    			if( $is_scan_malware || $is_scan_phpmussel )
	    			{
		    			if( $scan_output != 0)
		    			{
		    				if( $fchange_infected_action == 'autodelete' )
		    				{
		    					if( tebravo_files::remove( $key ) )
		    					{
		    						$infected_action = __("Removed", TEBRAVO_TRANS);
		    					}
		    				}
		    			}
	    			}
    			}
    			
    			
    			else if( $this->no_update == 1){
    				$actions = 0;
    				if( !file_exists( $key ) ){$scan_results = "<font color=brown>".__("Removed", TEBRAVO_TRANS)."</font>";$actions = 1;}
    				else if($this->check_quarantine( $key ) ){$scan_results = "<font color=blue>".__("Moved to quarantine", TEBRAVO_TRANS)."</font>";$actions = 1;}
    				else{
    					$scan_results = __("Clean", TEBRAVO_TRANS);
    					$scan_results = "<font color=green>".$scan_results."</font>";
    				}
    			}
    			
    			if( $scan_output > 0 )
    			{
    				$scan_results = "<font color=red>".__("Infected", TEBRAVO_TRANS)."</font>";
    			}
    			
    			$output .= "<td width=10%>".$the_oldDate."</td>";
    			$output .= "<td width=10%>".$the_newDate."</td>";
    			$output .= "<td width=10%>".$scan_results." <i>".$infected_action."</i></td>";
    			$output .= "</tr>";
    		}
    		
    		return $output;
    	}
    }
    
    
}