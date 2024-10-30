<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if(! class_exists( 'tebravo_db' ) )
{
    class tebravo_db{
        //protected: options
        protected $backupdir = "";
        protected $tmpdir = "";
        protected $manualdir = "";
        protected $dailydir = "";
        protected $weeklydir = "";
        protected $monthlydir = "";
        protected $scheduleddir = "";
        protected $method = "";
        protected$backup_where= "";
        protected $dso_chmod= "0777";
        public $error= "";
        
        //public options
        public $backupname = "";
        public $is_backuped= "";
        
        //main bravo init
        public $init="";
        
        //constuctor
        public function __construct()
        {
            $this->init = tebravo_init::init();
            $this->backupdir = $this->init->backupdir;
            $this->tmpdir = $this->backupdir."/tmp/";
            $this->manualdir = $this->backupdir."/manually/";
            $this->dailydir= $this->backupdir."/daily/";
            $this->weeklydir = $this->backupdir."/weekly/";
            $this->monthlydir = $this->backupdir."/monthly/";
            $this->scheduleddir = $this->backupdir."/scheduled/";
            $this->backupname= TEBRAVO_DBPREFIX."_WPDB_".@date('d-m-Y-h-i-a').".sql";

        }
        
        //go take a copy of databse
        public function backup( $method = false , $filename = false)
        {
        	$this->method = $method;
        	if( !$method ){$this->method = "manual";}
        	
        	switch ($this->method)
        	{
        		case 'manual': $this->backup_where = $this->manualdir; break;
        		case 'month': $this->backup_where = $this->monthlydir; break;
        		case 'week': $this->backup_where = $this->weeklydir; break;
        		case 'day': $this->backup_where = $this->dailydir; break;
        		case 'scheduled': $this->backup_where = $this->scheduleddir; break;
        		default: $this->backup_where = $this->tmpdir; break;
        	}
        	
        	if($filename){$this->backupname = $filename;}
        	
        	if(! is_writable($this->backup_where)){
        		ob_start();
        		try {
        			chmod($this->backup_where, $this->dso_chmod);
        		} catch (Exception $e){
        			$this->error = $e->getMessage().PHP_EOL;
        		}
        		ob_flush();
        		$this->is_backuped = false;
        	} else {
        		$this->dump();
        		$this->is_backuped = true;
        	}
        	//if permessions changed by BRAVO, return it to read only
        	if(!empty($_COOKIE['dirperms_changed'])){
        		if(is_dir($this->backupdir.'/'.$_COOKIE['dirperms_changed'].'/')){
        			@chmod($this->backupdir.'/'.$_COOKIE['dirperms_changed'].'/', 0400);
        		}
        		
        		@setcookie("dirperms_changed","");
        	}
        	
        	//store action
        	do_action( 'tebravo_dbbackup' );
        }
        
        //protect: dump db then store it into a file
        protected function dump()
        {
            global $wpdb;
            $wpdb->show_errors(false);
            // Get a list of the tables
            $tables = $wpdb->get_results('SHOW TABLES');
            
            $upload_dir = $this->backup_where;
            $file_path = $upload_dir.$this->backupname;
            $file = fopen($file_path, 'w');
            $slug = 'Tables_in_'.DB_NAME;
            
            $file_header = '##############################################'.PHP_EOL;
            $file_header.= '#Wordpress Database Backup '.@date('d M Y h:i:s A').PHP_EOL;
            $file_header.= '#Dumped By BRAVO WP Ultimate Security'.PHP_EOL;
            $file_header.= '##############################################'.PHP_EOL;
            fwrite($file, $file_header);
            #var_dump($tables); exit;
            foreach ($tables as $table)
            {
            	$table_name = $table->$slug;
                $schema = $wpdb->get_row('SHOW CREATE TABLE ' . $table_name, ARRAY_A);
                fwrite($file, $schema['Create Table'] . ';' . PHP_EOL);
                
                $rows = $wpdb->get_results('SELECT * FROM ' . $table_name, ARRAY_A);
                
                if( $rows )
                {
                    fwrite($file, 'INSERT INTO ' . $table_name . ' VALUES ');
                    
                    $total_rows = count($rows);
                    $counter = 1;
                    foreach ($rows as $row => $fields)
                    {
                        $line = '';
                        foreach ($fields as $key => $value)
                        {
                            $value = addslashes($value);
                            $line .= '"' . $value . '",';
                        }
                        
                        $line = '(' . rtrim($line, ',') . ')';
                        
                        if ($counter != $total_rows)
                        {
                            $line .= ',' . PHP_EOL;
                        }
                        
                        fwrite($file, $line);
                        
                        $counter++;
                    }
                    
                    fwrite($file, '; ' . PHP_EOL);
                }
            }
            
            fclose($file);
        }
        
    }
}
?>