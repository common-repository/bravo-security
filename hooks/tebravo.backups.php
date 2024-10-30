<?php 
/**
 * Hook: BRAVO.BACKUPS
 * DB & Files Backup for Wordpress.
 * @since 1.0
 * Copyrights (C) 2017 Technoyer Solutions Ltd. <support@technoyer.com>
 */

//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if(!class_exists( 'tebravo_backups' ))
{
	class tebravo_backups
	{
		public $init,$backupdir,$files_backup_name;
		public function __construct()
		{
			$this->init = tebravo_init::init();
			$this->backupdir = $this->init->backupdir;
			$this->files_backup_name = TEBRAVO_DBPREFIX.date('d-m-Y').'_'.date('H-i').'.zip';
			
		}
		//dasboard //HTML
		public function dashboard()
		{
			$this->html = new tebravo_html();
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			$desc = __("Database Backups.", TEBRAVO_TRANS);
			$this->html->header(__("Backups", TEBRAVO_TRANS), $desc, 'backup.png');
			$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
			
			//delete backup
			if( !empty( $_GET['action']) && $_GET['action'] == 'del' )
			{
				if( !empty( $_GET['file'] ) )
				{
					$file = trim( esc_html( esc_js( $_GET['file'] ) ) );
					$file = tebravo_decodeString($file, $this->html->init->security_hash);
					$this->delete_backup( $file, esc_html($_GET['p']) );
				}
			}
			//backing up database
			if( !empty( $_GET['action']) && $_GET['action'] == 'dbbackup' )
			{
				if( empty( $_GET['_nonce'])
						|| false === wp_verify_nonce($_GET['_nonce'], $this->html->init->security_hash.'db-backup'))
				{
					wp_die( __("Access Denied!", TEBRAVO_TRANS)); exit;
				}
					$db = new tebravo_db();
					$db->backup();
					if( !$db->is_backuped )
					{
						tebravo_die(true, __("Backup Failed!", TEBRAVO_TRANS), false, true);
					}
					echo __("Loading", TEBRAVO_TRANS)."...";
					tebravo_redirect_js( $this->html->init->admin_url.'-backups&msg=09');
				
			}
			
			//backing up files
			if( !empty( $_GET['action']) && $_GET['action'] == 'filesbackup' )
			{
				if( empty( $_GET['_nonce'])
						|| false === wp_verify_nonce($_GET['_nonce'], $this->html->init->security_hash.'files-backup'))
				{
					wp_die( __("Access Denied!", TEBRAVO_TRANS)); exit;
				}
				echo __("Loading", TEBRAVO_TRANS)."...";
					//$this->backup_files();
					$this->zip_files();
					tebravo_redirect_js( $this->html->init->admin_url.'-backups&msg=09');
				
			}
			$db_backup_href = $this->html->init->admin_url.'-backups&action=dbbackup&_nonce='.$this->html->init->create_nonce('db-backup');
			$files_backup_href = $this->html->init->admin_url.'-backups&action=filesbackup&_nonce='.$this->html->init->create_nonce('files-backup');
			
			//$this->backup_files();
			
			$output[] = $this->html->button_small(__("Create new backup", TEBRAVO_TRANS), "button", "dbbackup");
			//$output[] = $this->html->button_small(__("Backing up Files", TEBRAVO_TRANS), "button", "filesbackup");
			
			$db_listbackups = $this->db_listbackups();
		//	$files_listbackups = $this->files_listbackups();
			
			$output[] = "<div class='tebravo_clr'></div><div class='tebravo_blocks' style='width:49%'>";
			$output[] = "".$db_listbackups."</div>";
			$output[] = "<div class='tebravo_blocks' style='width:49%'>".$this->stats()."</div>";
			$output[] = "</div>";
			$this->html->print_loading(__("Loading, Please wait...", TEBRAVO_TRANS));
			echo implode("\n", $output);
			?>
			<script>
			jQuery("#dbbackup").click(function()
					{
				jQuery(".tebravo_loading").show();
						window.location.href='<?php echo $db_backup_href;?>';
					});
			jQuery("#filesbackup").click(function()
					{
				jQuery(".tebravo_loading").show();
						window.location.href='<?php echo $files_backup_href;?>';
					});
			</script>
			<?php 
			$this->html->footer();
		}
		
		public function stats()
		{
			$path_1 = $this->backupdir."/manually";
			$path_2 = $this->backupdir."/scheduled";
			
			$read_1 = tebravo_dirs::read($path_1);
			$count_1 = 0;
			if( null!=$read_1 )
			{
				foreach ( $read_1 as $file_1 )
				{
					if( tebravo_files::extension($file_1) == 'sql'){$count_1++;}
				}
			}
			
			$read_2 = tebravo_dirs::read($path_2);
			$count_2 = 0;
			if( null!=$read_2 )
			{
				foreach ( $read_2 as $file_2 )
				{
					if( tebravo_files::extension($file_2) == 'sql'){$count_2++;}
				}
			}
			
			$allfiles = $count_1+$count_2;
			
			$manually= $this->database_backups( 'manually' );
			$scheduled= $this->database_backups( 'scheduled' );
			
			$manually_size = tebravo_dirs::dir_size( $path_1 );
			$scheduled_size = tebravo_dirs::dir_size( $path_2 );
			
			$allsize = $manually_size+$scheduled_size;
			
			$output[] = "<table border=0 width=100% cellspacing=0>";
			$output[] = "<tr><td colspan=2><h3>".__("Stats", TEBRAVO_TRANS)."</h3></td></tr>";
			$output[] = "<tr class='tebravo_underTD'><td width=15%><strong>".__("All Files", TEBRAVO_TRANS)."</strong></td><td>".$allfiles."</td></tr>";
			$output[] = "<tr class='tebravo_underTD'><td width=15%><strong>".__("Used Space", TEBRAVO_TRANS)."</strong></td><td>".tebravo_ConvertBytes($allsize)."</td></tr>";
			$output[] = "</table>";
			
			return implode("\n", $output);
		}
		
		private function db_listbackups()
		{
			$this->html = new tebravo_html();
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			$manually= $this->database_backups( 'manually' );
			$scheduled= $this->database_backups( 'scheduled' );
			
			$nonce_delete = $this->html->init->create_nonce( 'delete-backups' );
			$download_link = $this->html->init->admin_url.'-backups&_download_nonce='.$this->html->init->create_nonce('download-file').'&tebravo_file=';
			
			$output[] = "<table border=0 width=100% cellspacing=0>";
			//Manually
			$output[] = "<tr><td colspan=2><h3>".__("Database Backups", TEBRAVO_TRANS)."</h3></td></tr>";
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Manually", TEBRAVO_TRANS)."</strong></td></tr>";
			$manually_list = "<tr><td colspan=2>&nbsp;&nbsp;&nbsp;".__("No Files!", TEBRAVO_TRANS)."</td></tr>";
			if( null!= $manually )
			{
				$manually_list = '';
				foreach ($manually as $file)
				{
					$file_path = $this->backupdir.'/manually/'.$file;
					if( file_exists( $file_path ) ){
						$filetime = __("Since", TEBRAVO_TRANS)." ".tebravo_ago( filemtime( $file_path ) );
						$filesize = ( filesize( $file_path ) / 1000 );
						$file_to_delete = tebravo_encodeString($file, $this->html->init->security_hash);
						$del_link = $this->html->init->admin_url."-backups&action=del&file=".$file_to_delete."&p=manually&_nonce=".$nonce_delete;
						$tools = "<a onclick=\"return confirm('".__("Are you sure?!", TEBRAVO_TRANS)."')\" href='".$del_link."'>".__("Delete", TEBRAVO_TRANS)."</a>";
						$tools .= "&nbsp; . &nbsp;<a href='".$download_link.tebravo_encodeString($this->backupdir.'/manually/'.$file, $this->html->init->security_hash)."'>".__("Download", TEBRAVO_TRANS)."</a>";
						$manually_list .= "<tr class='tebravo_underTD'><td width=70%>".basename( $file)."<br /><font class='smallfont'>".ceil($filesize)." ".__("KB", TEBRAVO_TRANS)."</font></td>";
						$manually_list .= "<td>".$tools."<br /><font class='smallfont'>".$filetime."</font></td></tr>";
					}
				}
			}
			$output[] = $manually_list;
			//Scheduled
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Scheduled", TEBRAVO_TRANS)."</strong></td></tr>";
			$scheduled_list = "<tr><td colspan=2>&nbsp;&nbsp;&nbsp;".__("No Files!", TEBRAVO_TRANS)."</td></tr>";
			if( null!= $scheduled)
			{
				$scheduled_list= '';
				foreach ($scheduled as $file )
				{
					$file_path = $this->backupdir.'/scheduled/'.$file;
					if( file_exists( $file_path ) ){
						$filetime = __("Since", TEBRAVO_TRANS)." ".tebravo_ago( filemtime( $file_path ) );
						$filesize = ( filesize( $file_path ) / 1000 );
						$file_to_delete = tebravo_encodeString($file, $this->html->init->security_hash);
						$del_link = $this->html->init->admin_url."-backups&action=del&file=".$file_to_delete."&p=scheduled&_nonce=".$nonce_delete;
						$tools = "<a onclick=\"return confirm('".__("Are you sure?!", TEBRAVO_TRANS)."')\" href='".$del_link."'>".__("Delete", TEBRAVO_TRANS)."</a>";
						$tools .= "&nbsp; . &nbsp;<a href='".$download_link.tebravo_encodeString($this->backupdir.'/scheduled/'.$file, $this->html->init->security_hash)."'>".__("Download", TEBRAVO_TRANS)."</a>";
						$scheduled_list .= "<tr class='tebravo_underTD'><td width=70%>".basename( $file )."<br /><font class='smallfont'>".ceil($filesize)." ".__("KB", TEBRAVO_TRANS)."</font></td>";
						$scheduled_list .= "<td>".$tools."<br /><font class='smallfont'>".$filetime."</font></td></tr>";
					}
				}
			}
			$output[] = $scheduled_list;
			
			$output[] = "</table>";
			
			return implode("\n", $output);
		}
		
		private function files_listbackups()
		{
			$this->html = new tebravo_html();
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			$manually= $this->files_backups( 'manually' );
			$scheduled= $this->files_backups( 'scheduled' );
			
			$nonce_delete = $this->html->init->create_nonce( 'delete-backups' );
			$download_link = $this->html->init->admin_url.'-backups&_download_nonce='.$this->html->init->create_nonce('download-file').'&tebravo_file=';
			
			$output[] = "<table border=0 width=100% cellspacing=0>";
			//Manually
			$output[] = "<tr><td colspan=2><h3>".__("Files Backups", TEBRAVO_TRANS)."</h3></td></tr>";
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Manually", TEBRAVO_TRANS)."</strong></td></tr>";
			$manually_list = "<tr><td colspan=2>&nbsp;&nbsp;&nbsp;".__("No Files!", TEBRAVO_TRANS)."</td></tr>";
			if( null!= $manually )
			{
				$manually_list = '';
				foreach ($manually as $file)
				{
					$file_path = $this->backupdir.'/manually/files/'.$file;
					if( file_exists( $file_path ) ){
						$filetime = __("Since", TEBRAVO_TRANS)." ".tebravo_ago( filemtime( $file_path ) );
						$filesize = ( filesize( $file_path ) / 1000 );
						$file_to_delete = tebravo_encodeString($file, $this->html->init->security_hash);
						$del_link = $this->html->init->admin_url."-backups&action=del&file=".$file_to_delete."&p=manually/files&_nonce=".$nonce_delete;
						$tools = "<a onclick=\"return confirm('".__("Are you sure?!", TEBRAVO_TRANS)."')\" href='".$del_link."'>".__("Delete", TEBRAVO_TRANS)."</a>";
						$tools .= "&nbsp; . &nbsp;<a href='".$download_link.tebravo_encodeString($this->backupdir.'/manually/files/'.$file, $this->html->init->security_hash)."'>".__("Download", TEBRAVO_TRANS)."</a>";
						$manually_list .= "<tr class='tebravo_underTD'><td width=70%>".basename( $file)."<br /><font class='smallfont'>".ceil($filesize)." ".__("KB", TEBRAVO_TRANS)."</font></td>";
						$manually_list .= "<td>".$tools."<br /><font class='smallfont'>".$filetime."</font></td></tr>";
					}
				}
			}
			$output[] = $manually_list;
			//Scheduled
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Scheduled", TEBRAVO_TRANS)."</strong></td></tr>";
			$scheduled_list = "<tr><td colspan=2>&nbsp;&nbsp;&nbsp;".__("No Files!", TEBRAVO_TRANS)."</td></tr>";
			if( null!= $scheduled)
			{
				$scheduled_list= '';
				foreach ($scheduled as $file )
				{
					$file_path = $this->backupdir.'/scheduled/files/'.$file;
					if( file_exists( $file_path ) ){
						$filetime = __("Since", TEBRAVO_TRANS)." ".tebravo_ago( filemtime( $file_path ) );
						$filesize = ( filesize( $file_path ) / 1000 );
						$file_to_delete = tebravo_encodeString($file, $this->html->init->security_hash);
						$del_link = $this->html->init->admin_url."-backups&action=del&file=".$file_to_delete."&p=scheduled/files&_nonce=".$nonce_delete;
						$tools = "<a onclick=\"return confirm('".__("Are you sure?!", TEBRAVO_TRANS)."')\" href='".$del_link."'>".__("Delete", TEBRAVO_TRANS)."</a>";
						$tools .= "&nbsp; . &nbsp;<a href='".$download_link.tebravo_encodeString($this->backupdir.'/scheduled/files/'.$file, $this->html->init->security_hash)."'>".__("Download", TEBRAVO_TRANS)."</a>";
						$scheduled_list .= "<tr class='tebravo_underTD'><td width=70%>".basename( $file )."<br /><font class='smallfont'>".ceil($filesize)." ".__("KB", TEBRAVO_TRANS)."</font></td>";
						$scheduled_list .= "<td>".$tools."<br /><font class='smallfont'>".$filetime."</font></td></tr>";
					}
				}
			}
			$output[] = $scheduled_list;
			
			$output[] = "</table>";
			
			return implode("\n", $output);
		}
		
		private function delete_backup( $file, $folder=false )
		{
			$this->html = new tebravo_html();
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			$path = $this->backupdir.'/'.$folder.'/'.$file;
			$path = str_replace("//", "/", $path);
			
			if( empty( $_GET['_nonce'])
					|| false === wp_verify_nonce( $_GET['_nonce'], $this->html->init->security_hash.'delete-backups'))
			{
				wp_die(__("Access Denied!", TEBRAVO_TRANS) ) ; exit;
			}
			
			if( file_exists( $path ) )
			{
				tebravo_files::remove( $path );
				
				$redirect_to = $this->html->init->admin_url.'-backups&msg=05';
			} else {
				$redirect_to = $this->html->init->admin_url.'-backups&err=04';
			}
			
			tebravo_redirect_js( $redirect_to );
			
		}
		
		private function database_backups( $folder=false )
		{
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			if( !$folder ){$folder = "manually";}
			
			$path = $this->backupdir.'/'.$folder;
			
			if( !is_dir( $path ) ){return;}
			
			$dirs = tebravo_dirs::read( $path );
			
			$results = array();
			foreach ( $dirs as $file )
			{
				if( is_file( $path.'/'.$file ) 
						&& file_exists( $path.'/'.$file )
						&& false !== strpos( basename( $file ), TEBRAVO_DBPREFIX )
						&& strtolower(tebravo_files::extension( basename( $file )) == 'sql' ) )
				{
					$results[] = $file;
				}
			}
			
			return $results;
		}
		
		private function files_backups( $folder=false )
		{
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			if( !$folder ){$folder = "manually";}
			
			$path = $this->backupdir.'/'.$folder.'/files/';
			$path = utf8_encode($path);
			
			if( !is_dir( $path ) ){return;}
			
			$dirs = tebravo_dirs::read( $path );
			
			$results = array();
			foreach ( $dirs as $file )
			{
				$file = utf8_encode($file);
				if( is_dir( $file ) )
				{
					$results[] = $this->files_backups($dirs.$file);
				}
				if( is_file( $path.'/'.$file )
						&& file_exists( $path.'/'.$file )
						&& false !== strpos( basename( $file ), TEBRAVO_DBPREFIX )
						&& strtolower(tebravo_files::extension( basename( $file )) == 'gz' ) )
				{
					$results[] = $file;
				}
			}
			
			return $results;
		}
		
		public function backup_files( $dir=false, $path=false)
		{
			$php = new tebravo_phpsettings();
			$php->set_php_settings();
			//@ini_set('memory_limit', '168');
			if( !$dir ){ $dir = ABSPATH;}
			if( !$path ){ $path = 'manually/files';}
			
			//@mb_internal_encoding('UTF-8');
		//	@ini_set('default_charset', 'UTF-8');
			
			$backup_path = $this->backupdir.'/'.$path.'/'.$this->files_backup_name;
			$backup_path= str_replace("//", "/", $backup_path);
			
			$result = false;
			
			
			
			$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($dir)
			);
			$filterIterator = new CallbackFilterIterator($iterator , function ($file) {
				//return (strpos($file, "vendor/") === false);
				$file = mb_convert_encoding($file, 'UTF-8','CP850');
				if( is_file( $file ))
				return ( filesize($file) < 500000);
			});
			
				$filterIterator = new CallbackFilterIterator($iterator , function ($file) {
					$file = mb_convert_encoding($file, 'UTF-8','CP850');
					if( is_file( $file ))
						return ( strlen($file) < 163);
				});
				
					$filterIterator = new CallbackFilterIterator($iterator , function ($file) {
						$file = mb_convert_encoding($file, 'UTF-8','CP850');
						if( is_file( $file ))
							return ( tebravo_files::extension($file)!='DS_Store');
					});
						$filterIterator = new CallbackFilterIterator($iterator , function ($file) {
							$file = mb_convert_encoding($file, 'UTF-8','CP850');
							if( is_file( $file ))
								return ( tebravo_files::extension($file)!='tar');
						});
							$filterIterator = new CallbackFilterIterator($iterator , function ($file) {
								$file = mb_convert_encoding($file, 'UTF-8','CP850');
								if( is_file( $file ))
									return ( tebravo_files::extension($file)!='sql');
							});
								$filterIterator = new CallbackFilterIterator($iterator , function ($file) {
									$file = mb_convert_encoding($file, 'UTF-8','CP850');
									if( is_file( $file ))
										return ( tebravo_files::extension($file)!='gz');
								});
						try {
							$phar = new PharData($backup_path);
							
							$phar->buildFromIterator($filterIterator, $dir);
							$phar->compress(Phar::GZ);
							@unlink(realpath($backup_path));
				
						} catch (\PharException $e) {
							tebravo_die(true,$e->getMessage(),false,true);
							exit;
						}
			return $result;
		}
		
		public function addfiles( $dir=false, $path=false )
		{
			$php = new tebravo_phpsettings();
			$php->set_php_settings();
			
			if( !$dir ){ $dir = ABSPATH;}
			if( !$path ){ $path = 'manually/files';}
			
			$backup_path = $this->backupdir.'/'.$path.'/'.$this->files_backup_name;
			$backup_path= str_replace("//", "/", $backup_path);
			
			$zip = new ZipArchive();
			$zip->open($backup_path, ZipArchive::CREATE);
			
			if( is_dir( $dir ) ):
			$files = tebravo_dirs::read( $dir ) ;
			endif;
			
			
			if( is_array( $files ) ):
			foreach ($files as $file)
			{
				if( is_dir( $dir.'/'.$file ) )
				{
					$this->addfiles( $dir.'/'.$file );
				}
				if (file_exists($dir.'/'.$file) && is_file($dir.'/'.$file))
					$zip->addFile($dir.'/'.$file,$dir.'/'.$file);
			}
			endif;
			
			$zip->close();
		}
		
		public function zip_files($dir=false, $path=false)
		{
			$php = new tebravo_phpsettings();
			$php->set_php_settings();
			//@ini_set('memory_limit', '168');
			
			
			$files = $this->addfiles( $dir ) ;
			
				
		}
		
		
		
	}
	
}
