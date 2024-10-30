<?php 

//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !class_exists( 'tebravo_html' ))
{
	class tebravo_html{
	
	public $init;
	public $path;
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
	
	//Go Construct
	public function __construct()
	{
		$this->init = new tebravo_init();
		$this->path = TEBRAVO_PATH;
		
		add_action('admin_head', array( $this,'check_install' ) );
	}

	//Print HTML Header for the plugin
	public function header($title , $desc = false , $icon = false, $extra = false)
	{
		//check dashboard directory if exists
		if( !is_dir( $this->init->dashboard_temps_dir ) ){
			$message = __("Dashboard files not found!", TEBRAVO_TRANS);
			$message .= "<br /><i>".$this->init->dashboard_temps_dir."</i>";
			tebravo_die( true,$message ,false,false); 
		}
		
		//define pages
		if( !defined('TEBRAVO_CURRENT_PAGE_TITLE')){define('TEBRAVO_CURRENT_PAGE_TITLE', $title);}
		if( !isset($_GET['page']) && !empty( $_GET['page'] ) )
		{
			if( !defined('TEBRAVO_CURRENT_PAGE')){define('TEBRAVO_CURRENT_PAGE', esc_html($_GET['page']));}
		}
		if( !isset($_GET['p']) && !empty( $_GET['p'] ) )
		{
			if( !defined('TEBRAVO_CURRENT_P')){define('TEBRAVO_CURRENT_PAGE_P', esc_html($_GET['p']));}
		}
		if( !isset($_GET['action']) && !empty( $_GET['action'] ) )
		{
			if( !defined('TEBRAVO_CURRENT_ACTION')){define('TEBRAVO_CURRENT_PAGE_ACTION', esc_html($_GET['action']));}
		}
		
		//continue
		if( !$icon ) {$icon = "default_icon.png";}
		$icon_path = plugins_url('assets/img/'.$icon, $this->init->path);
		?>
		<div class="tebravo_container">
		<div class="tebravo_icon" style="background: url('<?php echo $icon_path;?>');background-repeat: no-repeat;background-position: center;"></div>
		<div class="tebravo_title"><?php echo $title;?></div>
		<div class="tebravo_extra"><?php echo $extra;?></div>
		<hr>
		<?php
		if($desc)
		{
			?>
			<div class="tebravo_desc"><?php echo $desc;?></div>
			<?php	
		}
		
		if(isset($_GET['err']))
		{
			echo "<br /><div class='tebravo_error'><div class='close_x' id='tebravo_close_error'><center><span title='Close Alert'>x</span></center></div><p>".$this->init->print_errors( esc_attr( $_GET['err'] ) )."</p></div>";	
		}
		
		if(isset($_GET['msg']))
		{
			echo "<br /><div class='tebravo_msg'><div class='close_x' id='tebravo_close_msg'><center><span title='Close Notification'>x</span></center></div><p>".$this->init->print_message( esc_attr( $_GET['msg'] ) )."</p></div>";
		}
		
		echo "<p>";
		
		
	}
	
	public function check_install()
	{
		//check if installed
		if( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'installed') != 'installed' )
		{
			if( !isset( $_GET['action'] )
					|| (isset( $_GET['action']) && $_GET['action'] !='setup-'.TEBRAVO_SLUG))
			{
				$not_installed_message = "<div class='tebravo_block_blank' style='width:100%'>".__("BRAVO is not in the best practice mode! Please start configuration now.", TEBRAVO_TRANS). "<br />";
				$not_installed_message .= $this->button_small(__("Start Configuration", TEBRAVO_TRANS), "button", "bravo-start-confi", "computer-16.png")."</div>";
				
				$this->print_noti( $not_installed_message );
				
				?>
				<script>
				jQuery("#bravo-start-confi").click(function()
						{
							window.location.href = "<?php echo $this->init->admin_url;?>&action=setup-bravo-security&bravo-step=";
						});
				</script>
				<?php 
			}
		}
	}
	//Print HTML Footer for the plugin
	public function footer()
	{
		?>
		</p>
		</div>
		<div class="tebravo_footer" id="bravo_footer">Powered By <a href="http://<?php echo TEBRAVO_SLUG;?>.technoyer.com" target=_blank>BRAVO WP Ultimate Security</a> V<?php echo TEBRAVO_VERSION?>
		<?php 
		if(defined('TEBRAVO_VERSIONTYPE') && TEBRAVO_VERSIONTYPE != 'PRO')
		{
			$img_src = plugins_url('/assets/img/gopro.png', TEBRAVO_PATH);
			
			?>
			<div class="gopro"><center><img src="<?php echo $img_src;?>"><br /><a href="<?php echo tebravo_create_donate_url('footer');?>" target=_blank><?php echo __("Buy Premium", TEBRAVO_TRANS);?></a></center></div>
			<?php	
		}
		
		?>
		</div>
		<script>
		jQuery("#tebravo_close_error").click(function( $ )
		{
			jQuery(".tebravo_error").hide(200);
		});
		setTimeout(function()
				{
			jQuery(".tebravo_error").hide(200);
				},10000);
		jQuery("#tebravo_close_msg").click(function( $ )
		{
			jQuery(".tebravo_msg").hide(200);
		});
		setTimeout(function()
				{
			jQuery(".tebravo_msg").hide(200);
				},10000);
		</script>
		<?php 
		
		
	}
	
	//Print HTML TABS for the plugin
	//Tabs must be an array
	public function tabs( $tabs)
	{
		if(is_array($tabs))
		{
			$output[] = '<ul class="tebravo_tabs">';
			foreach ($tabs as $key => $value)
			{
				
				$output[] = '<li class="'.$tabs[$key]["is_active"].'" onClick="window.location.href=\''.$tabs[$key]["href"].'\'"><a href="'.$tabs[$key]["href"].'">'.$tabs[$key]["title"].'</a></li>';
			}
			$output[] = '</ul>';
			
			do_action( 'tebravo_tabs' );
			
			echo implode("\n", $output);
		}
	}
	
	//Print Single TAB
	public function print_tab ($title, $href, $active=false)
	{
		if($active){$css_class = "active";}
		$output = "<li class=\"$css_class\" onclick=\"window.location.href='$href'\"><a href=\" $href\">$title</a></li>";
		
		return $output;
	}
	
	//Print TAB Content Header
	public function start_tab_content()
	{
		$output[] = '<div class="tebravo_clr"></div>';
		$output[] = '<section class="block">';
		$output[] = '<article class="tebravo_article">';
		$output[] = '<p>';
		echo implode("\n", $output);
	}
	
	//Print TAB Content Footer
	public function end_tab_content()
	{
		$output[] = '</p>';
		$output[] = '</article>';
		$output[] = '</section>';
		echo implode("\n", $output);
	}
	
	//HTML Button Standard
	public function button($value, $type="", $id="", $name="", $icon="")
	{
		if(!empty($icon))
		{
			$value = "<img src='".plugins_url("assets/img/".$icon, $this->init->path)."'> ".$value;	
		}
		$output[] = '<button class="button_button tebravo_curved" type="'.$type.'" name="'.$name.'" id="'.$id.'">'.$value.'</button>';
		return implode("\n", $output);
	}
	
	//HTML Alert Button Standard
	public function button_alert($value, $type="", $id="", $name="", $icon="")
	{
		if(!empty($icon))
		{
			$value = "<img src='".plugins_url("assets/img/".$icon, $this->init->path)."'> ".$value;
		}
		$output[] = '<button class="button_alert tebravo_curved" type="'.$type.'" name="'.$name.'" id="'.$id.'">'.$value.'</button>';
		return implode("\n", $output);
	}
	
	//HTML Info Button Standard
	public function button_info($value, $type="", $id="", $name="", $icon="")
	{
		if(!empty($icon))
		{
			$value = "<img src='".plugins_url("assets/img/".$icon, $this->init->path)."'> ".$value;
		}
		$output[] = '<button class="button_info tebravo_curved" type="'.$type.'" name="'.$name.'" id="'.$id.'">'.$value.'</button>';
		return implode("\n", $output);
	}
	
	//HTML Success Button Standard
	public function button_success($value, $type="", $id="", $name="", $icon="")
	{
		if(!empty($icon))
		{
			$value = "<img src='".plugins_url("assets/img/".$icon, $this->init->path)."'> ".$value;
		}
		$output[] = '<button class="button_success tebravo_curved" type="'.$type.'" name="'.$name.'" id="'.$id.'">'.$value.'</button>';
		return implode("\n", $output);
	}
	
	//HTML Button Small
	public function button_small($value, $type="", $id="", $name="", $icon="")
	{
		if(!empty($icon))
		{
			$value = "<img src='".plugins_url("assets/img/".$icon, $this->init->path)."'> ".$value;
		}
		$output[] = '<button class="button_small tebravo_curved" type="'.$type.'" name="'.$name.'" id="'.$id.'">'.$value.'</button>';
		return implode("\n", $output);
	}
	
	//HTML Alert Button Small
	public function button_small_alert($value, $type="", $id="", $name="", $icon="")
	{
		if(!empty($icon))
		{
			$value = "<img src='".plugins_url("assets/img/".$icon, $this->init->path)."'> ".$value;
		}
		$output[] = '<button class="button_small_alert tebravo_curved" type="'.$type.'" name="'.$name.'" id="'.$id.'">'.$value.'</button>';
		return implode("\n", $output);
	}
	
	//HTML Info Button Small
	public function button_small_info($value, $type="", $id="", $name="", $icon="")
	{
		if(!empty($icon))
		{
			$value = "<img src='".plugins_url("assets/img/".$icon, $this->init->path)."'> ".$value;
		}
		$output[] = '<button class="button_small_info tebravo_curved" type="'.$type.'" name="'.$name.'" id="'.$id.'">'.$value.'</button>';
		return implode("\n", $output);
	}
	
	//HTML Success Button Small
	public function button_small_success($value, $type="", $id="", $name="", $icon="")
	{
		if(!empty($icon))
		{
			$value = "<img src='".plugins_url("assets/img/".$icon, $this->init->path)."'> ".$value;
		}
		$output[] = '<button class="button_small_success tebravo_curved" type="'.$type.'" name="'.$name.'" id="'.$id.'">'.$value.'</button>';
		return implode("\n", $output);
	}
	
	//Popup Modal
	public function popup_modal($echo=true, $message, $id=false, $store_errorlog=false)
	{
		if( !$id ){$id = 'tebravo_modal';}
		
		$output[] = '<div id="'.$id.'" class="tebravo_modal">';
		$output[] = '<div class="tebravo_modal-content">';
		$output[] = '<span class="tebravo_close">&times;</span>';
		$output[] = '<p>'.$message.'</p>';
		$output[] = '</div>';
		$output[] = '</div>';
		
		$js = '<script>';
		$js .= 'jQuery(".tebravo_close").click(function()
		{
			jQuery(".tebravo_modal").css("display", "none");
		});';
		$js .= '</script>';
		
		$output[] = $js;
		
		//errorlog
		if( $store_errorlog==true )
		{
			tebravo_errorlog::errorlog('eh');
		}
		
		if( $echo==true ){echo implode("\n", $output);} else {implode("\n", $output);}
	}
	//Javascript POPUP Window Open
	public function open_window_help($id, $message, $width="", $height="")
	{
		$id = $this->init->create_nonce($id);
		$message = sanitize_textarea_field($message);
		$message = "<center><img src='".plugins_url("assets/img/help-32.png", $this->init->path)."'></center><br>".$message;

		if(empty($width)){$width = "370";}
		if(empty($height)){$height= "190";}
		
		$output[] = '&nbsp;<img style="cursor:pointer;" onclick="myFunction'.$id.'()" src="'.plugins_url("assets/img/help.png", $this->init->path).'" title="'.__("Help", TEBRAVO_TRANS).'">';
		$output[] = '<script>';
		$output[] = 'function myFunction'.$id.'() {';
		$output[] = 'var myWindow'.$id.' = window.open("", "MsgWindow", "resizable=yes,top=450,left=480,width='.$width.',height='.$height.'");';
		$output[] = 'myWindow'.$id.'.document.write("<p>'.$message.'</p>");';
		$output[] = '}';
		$output[] = 'function closeWin'.$id.'() {';
		$output[] = 'myWindow'.$id.'.close();';
		$output[] = '}';
		$output[] = '</script>';
		
		return implode("\n", $output);
	}
	
	//print loading
	public function print_loading( $text=false )
	{
		if(! $text)
		{
			$text = __("Loading, Please wait ...");
		}
		
		?>
        	<div class='tebravo_loading'>
        	<p><img src='<?php echo plugins_url('assets/img/loading.gif', TEBRAVO_PATH);?>'><br /><?php echo $text;?></p>
        	</div>
        <?php
    }
	

	//Print Notification Bar
	public function print_noti( $message )
	{
		?>
		<div class="tebravo_notifis_admin">
<input type="checkbox" id="toggleTop" name="toggleTop" value="toggleTop" checked="checked">
<div id="topbar"><?php echo "<center><img src='".plugins_url('assets/img/bravo-16-16.png', $this->init->path)."'></center> ".$message;?>
 <label for="toggleTop" id="hideTop" title="Close">x</label>

</div>
</div>
		<?php 	
	}
	
	public function plugin_login()
	{
		$desc = __("Please insert BRAVO password.");
		$this->header(__("Self Protection", TEBRAVO_TRANS), $desc, "security.png", false);
		
		$output[] = "<form action='".$this->init->admin_url."-wadmin&p=login' method=post>";
		$output[] = "<input type='hidden' name='_nonce' value='".$this->init->create_nonce('self-protection')."'>";
		$output[] = "<input type='hidden' name='url' value='".tebravo_selfURL()."'>";
		$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
		$output[] = "<table border='0' width=100% cellspacing=0>";
		$output[] = "<tr class='tebravo_underTD'><td>";
		$output[] = __("Password", TEBRAVO_TRANS).":<br /><input type='password' name='pw'></td></tr>";
		$output[] = "<tr class='tebravo_underTD'><td>";
		$output[] = $this->button(__("Login", TEBRAVO_TRANS), 'submit');
		$output[] = "</td></tr>";
		$output[] = "</table></div></form>";
		echo implode("\n", $output);
		$this->footer();
		
		exit;
	}
		
}
}
?>