<?php 
/**
 * Hook: BRAVO.LOGWATCH
 * Log Watch from error log hook for Wordpress.
 * @since 1.0
 * Copyrights (C) 2017 Technoyer Solutions Ltd. <support@technoyer.com>
 */

//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if(!class_exists( 'tebravo_logwatch' ))
{
    class tebravo_logwatch
	{
		public 
		$min_size,
		$max_size,
		$log,
		$phplog,
		$dir;
		
		public function __construct()
		{
			$this->min_size = 1;
			$this->max_size = (10 * ( 1000 * 1000 ));
			$this->log = TEBRAVO_DIR."/".TEBRAVO_BACKUPFOLDER."/log/log.txt";
			$this->phplog = TEBRAVO_DIR."/".TEBRAVO_BACKUPFOLDER."/log/php_log.txt";
			$this->dir = TEBRAVO_DIR."/".TEBRAVO_BACKUPFOLDER."/log";
		}
		public function dashboard()
		{
			$this->html = new tebravo_html();
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			$tab_active = 'active';
			$tabphp_active = '';
			$current_log = '';
			if( !empty( $_GET['p']) && $_GET['p'] == 'phplog' )
			{
				$current_log = 'phplog';
				$tabphp_active = 'active';
				$tab_active = '';
			}
			
			if( !empty( $_GET['p']) && $_GET['p'] == 'flush' )
			{
				$for = '';
				if( isset( $_GET['for'] ) ){$for = trim( esc_html( esc_js( $_GET['for']) ) );}
				
				$this->flush( $for );
				tebravo_redirect_js( $this->html->init->admin_url.'-logwatch&p='.$for );
				exit;
			}
			//Tabs Data
			$tabs["tebravo_log"] = array("title"=>"Bravo Log",
					"href"=>$this->html->init->admin_url."-logwatch",
					"is_active"=> $tab_active);
			
			$tabs["php_log"] = array("title"=>"PHP Log",
					"href"=>$this->html->init->admin_url."-logwatch&p=phplog",
					"is_active"=> $tabphp_active);
			
			
			//Tabs HTML
			$desc = "Watch your Wordpress blog and what is happening in the background.";
			$extra = "<a href='".$this->html->init->admin_url."-logwatch&p=flush&for=".$current_log."' class='tebravo_curved'>".__("Flush", TEBRAVO_TRANS)."</a>";
			$this->html->header(__("Log Watch", TEBRAVO_TRANS), $desc, 'log.png', $extra);
			
			$this->html->tabs($tabs);
			$this->html->start_tab_content();
			$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
			//$output[] = $this->log;
			if( !empty( $_GET['p']) && $_GET['p'] == 'phplog' )
			{
				$output[] = $this->read_phplog();
			} else {
				$output[] = $this->read_log();
			}
			
			$output[] = "</div>";
			echo implode("\n", $output);
			
			
			?>
			<script>
			jQuery("#emptylog").click(function()
					{
						window.location.href="<?php echo $this->html->init->admin_url.'-logwatch&p=flush&for='.$current_log;?>";
					});

			</script>
			<?php 
			$this->html->end_tab_content();
			$this->html->footer();
		}
		
		protected function flush( $logfile=false )
		{
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			
			if( !$logfile ){ $logfile='log'; }
			
			switch ($logfile)
			{
				case 'log': $file = $this->log; break;
				case 'phplog': $file = $this->phplog; break;
			}
			
			if( isset( $file ) && file_exists( $file ) )
			{
				tebravo_files::write( $file, '');
			}
			
		}
		protected function read_phplog()
		{
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			if( $this->logsize( $this->phplog ) > $this->max_size )
			{
				return "<font color=brown>".__("Log File is too large!", TEBRAVO_TRANS)."</font>";
			}
			
			if( $this->logsize( $this->phplog ) < $this->min_size )
			{
				return __("Log File is empty!", TEBRAVO_TRANS);
			}
			
			//$log = __("Log File is empty!", TEBRAVO_TRANS);
			if( $this->logsize( $this->phplog ) > $this-> min_size &&
					$this->logsize( $this->phplog ) < $this->max_size )
			{
				$data = $this->read( $this->phplog );
				//var_dump($data);
				$exp = explode(PHP_EOL, $data);
				
				if( $exp!='' )
				{
					$log = "<div style='max-height:500px; overflow-y:scroll'><table border=0 width=100% cellspacing=0>";
					$i=0;
					foreach ( $exp as $line )
					{
						if( !empty( $line ) )
						{
								
							$log_data = $line;
							
							$log .= "<tr class=tebravo_underTD><td>".$log_data."</td></tr>";
							
							$i++;
							
						}
					}
					$log .= "</table></div>";
					$log .= "<hr><table border=0 width=100%><tr><td>".__("Lines", TEBRAVO_TRANS).": $i</td><td width=12%>";
					$log .= $this->html->button_small_info( __("Empty Log", TEBRAVO_TRANS), "button", "emptylog")."</td></tr></table>";
				}
			}
			
			return $log;
		}
		
		protected function read_log()
		{
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			if( $this->logsize( $this->log ) > $this->max_size )
			{
				return "<font color=brown>".__("Log File is too large!", TEBRAVO_TRANS)."</font>";
			}
			
			if( $this->logsize( $this->log ) < $this->min_size )
			{
				return __("Log File is empty!", TEBRAVO_TRANS);
			}
			//$log = $this->logsize( $this->log );
			//$log = __("Log File is empty!", TEBRAVO_TRANS);
			if( $this->logsize( $this->log ) > $this-> min_size &&
					$this->logsize( $this->log ) < $this->max_size )
			{
				$data = $this->read( $this->log );
				$exp = explode(PHP_EOL, $data);
				
				if( $exp!='' )
				{
					$log = "<div style='max-height:500px; overflow-y:scroll'><table border=0 width=100% cellspacing=0>";
					$i=0;
					foreach ( $exp as $line )
					{
						if( !empty( $line ) )
						{
							$exp_data = explode("#", $line);
							$date = $exp_data[0];
							if( !empty( $date ) )
							{
								$pattern = '/(\w+)-(\d+)-(\d+) (\w+):(\d+)/i';
								
								$log_data = $line;
								$log_data = str_replace("#", " | ", $log_data);
								$log_data = str_replace($date, "", $log_data);
								$log_data = preg_replace($pattern, "", $log_data);
								$log_data = str_replace("[", "", $log_data);
								$log_data = str_replace("]", "", $log_data);
								$date= str_replace("[", "", $date);
								$date= str_replace("]", "", $date);
								
								$log .= "<tr class=tebravo_underTD><td><b>".$date."</b>: ".$log_data."</td></tr>";
								
								$i++;
							}
						}
					}
					$log .= "</table></div>";
					$log .= "<hr><table border=0 width=100%><tr><td>".__("Lines", TEBRAVO_TRANS).": $i</td><td width=12%>";
					$log .= $this->html->button_small_info( __("Empty Log", TEBRAVO_TRANS), "button", "emptylog")."</td></tr></table>";
				}
			}
			
			return $log;
		}
		
		public function logsize( $file )
		{
			if( file_exists( $file ) )
			{
				return filesize( $file );
			}
		}
		
		public function read( $file )
		{
			if( file_exists( $file ) && is_file( $file ) )
			{
				return tebravo_files::read( $file );
			}
		}
		
	}
	
	//run
	new tebravo_logwatch();
}