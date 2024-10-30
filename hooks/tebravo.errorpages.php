<?php
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !class_exists( 'tebravo_errorpages' ) )
{
	
	class tebravo_errorpages
	{
		//options
		public $pages_path;
		public $html;
		public $template;
		public $template_file;
		public $assets_path;
		public $status;
		public $errorlog_status;
		
		//constructor
		public function __construct()
		{
			$template = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'errorpages_template' ) ) );
			if( empty( $template ) )
			{
				$template = 'default';
			}
			$this->template = $template;
			
			$this->pages_path = TEBRAVO_DIR.'/errorpages/';
			
			$tmp_file = $this->pages_path.'/templates/'.$this->template.'/template.html';
			$this->template_file = $tmp_file;
			
			$this->assets_path = plugins_url('/errorpages/templates/'.$this->template, TEBRAVO_PATH);
			
			$this->status = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'404_page') ) );
			$this->errorlog_status = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'error_pages_errorlog') ) );
			//actions
			add_action( 'init', array( $this, 'init' ) );
		}
		
		public function init()
		{
			if( $this->status == 'checked' )
			{
				add_action( 'template_redirect', array( $this, 'the_404' ) );
			}
		}
		//error pages shortcode
		public function the_404 ()
		{
			if( is_404() )
			{
				//error log
				if( $this->errorlog_status=='cheked' )
				{
					tebravo_errorlog::errorlog('404 Not Found!');
				}
				
				$content = $this->template( 404 );
				
				//define action
				do_action( 'tebravo_errorpages_404' );
				
				echo $content;
				
				exit;
			}
		}
		
		//error pages shortcode
		public function print_page ($error_code)
		{
			//error log
			if( $this->errorlog_status )
			{
				tebravo_errorlog::errorlog('403 Access Denied!');
			}
			
			$desc = $this->desc( (int)$error_code );
			
			$content = $this->template( $error_code, $desc);
			
			//define action
			do_action( 'tebravo_errorpages_'.$error_code );
			
			echo $content;
			
			exit;
			
		}
		
		public function template( $error_code=false, $error_desc=false )
		{
			//404 content
			$content = tebravo_files::read( $this->template_file );
			//define template
			if( !defined( 'tebravo_error_template' ) ){ define( 'tebravo_error_template', $error_code );}
			
			$title = '';
			if( !$error_code )
			{
				$error_code = 404;
				$title = __("Page Not Found", TEBRAVO_TRANS);
			}
			
			if( $error_code == '404' )
			{
				$desc = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'404_page_desc' ) ) );
			}
			
			if( empty( $desc ) )
			{
				$desc = $this->desc( $error_code );
			}
			
			if( !$error_desc )
			{
				$error_desc = $desc;
			}
			if( empty( $title ) )
			{
				$title = $this->desc( $error_code );
			}
			$code = $error_code;
			$footer = "<a href='".home_url()."'>".__("Home", TEBRAVO_TRANS)."</a>";
			
			$search_desc = '';
			$search_form = '';
			$search_included = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'errorpages_search' ) ) );
			
			if( $search_included == 'checked')
			{
				$search_desc = __("If you want", TEBRAVO_TRANS)." ...";
				$search_desc .= "<br />";
				$search_desc .= __("You can use our search form to find your requested page.", TEBRAVO_TRANS);
				
				#$search_form = get_search_form( false );
				$search_form = $this->searchbox( false );
			}
			
			$content = str_replace('{%title%}', $title, $content);
			$content = str_replace('{%error_number%}', $code, $content);
			$content = str_replace('{%error_desc%}', $error_desc, $content);
			$content = str_replace('{%footer_desc%}', $footer, $content);
			$content = str_replace('{%path%}', $this->assets_path, $content);
			
			
			$content = str_replace('{%search_desc%}', $search_desc, $content);
			$content = str_replace('{%searchbox%}', $search_form, $content);
			$content = str_replace('{%language%}', get_locale(), $content);
			
			//define action
			do_action( 'tebravo_errorpages_template' );
			
			return $content;
		}
		
		//print search form
		public function searchbox( $echo=true )
		{
			$output = '<form action="'.home_url().'" mehtod=get>'.PHP_EOL;
			$output .= '<input type="text" name="s" id="s" class="search_field" placeholder="'.__("Search for ...", TEBRAVO_TRANS).'">'.PHP_EOL;
			$output .= '<button type="submit" >'.__("Search", TEBRAVO_TRANS).'</button>'.PHP_EOL;
			$output .= '</form>'.PHP_EOL;
			
			if( true===$echo )
			{
				echo $output;
			}
			
			return $output;
		}
		//error pages description
		public static function desc( $errno )
		{
			switch ( $errno )
			{
				case 400:
					return __("Oops, The Page you requested was not found!");
					break;
				case 401:
					return __("Oops, The Page you requested was not found!");
					break;
				case 403:
					return __("Oops, Access Denied!");
					break;
				case 404:
					return __("Oops, The Page you requested was not found!");
					break;
				case 500:
					return __("Oops, Server error!");
					break;
				default:return __("Oops, The Page you requested was not found!");
			}
		}
		
		//dashboard
		public function dashboard()
		{
			$this->html = new tebravo_html();
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			$desc = "Setup your Wordpress error pages.";
			$this->html->header(__("Error Pages Dashboard", TEBRAVO_TRANS), $desc, 'errorpages.png');
			
			//Tabs Data
			$tabs["general"] = array("title"=>"Options",
					"href"=>$this->html->init->admin_url."-settings",
					"is_active"=> "");
			
			$tabs["wpconfig"] = array("title"=>"WP Config",
					"href"=>$this->html->init->admin_url."-wconfig",
					"is_active"=> '');
			
			$tabs["wpadmin"] = array("title"=>"WP Admin",
					"href"=>$this->html->init->admin_url."-wadmin",
					"is_active"=> '');
			
			$tabs["bruteforce"] = array("title"=>"Brute Force",
					"href"=>$this->html->init->admin_url."-bruteforce",
					"is_active"=> '');
			
			$tabs["antivirus"] = array("title"=>"Anti Virus",
					"href"=>$this->html->init->admin_url."-antivirus&p=settings",
					"is_active"=> '');
			
			$tabs["mail"] = array("title"=>"Email Settings",
					"href"=>$this->html->init->admin_url."-mail&p=settings",
					"is_active"=> '');
			
			$tabs["recaptcha"] = array("title"=>"reCAPTCHA",
					"href"=>$this->html->init->admin_url."-recaptcha",
					"is_active"=> '');
			
			$tabs["error_pages"] = array("title"=>"Error Pages",
					"href"=>$this->html->init->admin_url."-error_pages",
					"is_active"=> 'active');
			
			//Tabs HTML
			$this->html->tabs($tabs);
			$this->html->start_tab_content();
			
			$output[] = "<form action='".$this->html->init->admin_url."-error_pages' method=post>";
			$output[] = "<input type='hidden' name='_nonce' value='".$this->html->init->create_nonce('errorpages-settings')."'>";
			$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
			$output[] = "<table border='0' width=100% cellspacing=0>";
			
			//enable / disable 404_page tool
			$page_404 = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'404_page') ) );
			$page_404_yes = '';
			$page_404_no = '';
			if( $page_404== 'checked' ){$page_404_yes = 'checked';} else {$page_404_no = 'checked';}
			$page_404_help = __('When you enable this option, All Wordpress does not exist link will replaced with this tool template.', TEBRAVO_TRANS);
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("404 Page", TEBRAVO_TRANS)."</strong></td></tr>";
			$output[] = "<tr class='tebravo_underTD'><td width=80%>".$page_404_help."</td><td>";
			$output[] = "<input type='radio' name='page_404' value='checked' id='page_404_checked' $page_404_yes>";
			$output[] = "<label for='page_404_checked'><span></span>".__("Enable", TEBRAVO_TRANS)."</label>";
			$output[] = "<input type='radio' name='page_404' value='no' id='page_404_no' $page_404_no>";
			$output[] = "<label for='page_404_no'><span></span>".__("Disable", TEBRAVO_TRANS)."</label>";
			$output[] = "</td></tr>";
			
			//enable / disable errorpages_search tool
			$errorpages_search = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'errorpages_search') ) );
			$errorpages_search_yes = '';
			$errorpages_search_no = '';
			if( $errorpages_search == 'checked' ){$errorpages_search_yes = 'checked';} else {$errorpages_search_no = 'checked';}
			$errorpages_search_help = __('<i>Not Recommended</i>, If you want to hide your Wordpress, You should disable this option.', TEBRAVO_TRANS);
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Include Search Form", TEBRAVO_TRANS)."</strong></td></tr>";
			$output[] = "<tr class='tebravo_underTD'><td width=80%>".$errorpages_search_help."</td><td>";
			$output[] = "<input type='radio' name='errorpages_search' value='checked' id='errorpages_search_checked' $errorpages_search_yes>";
			$output[] = "<label for='errorpages_search_checked'><span></span>".__("Enable", TEBRAVO_TRANS)."</label>";
			$output[] = "<input type='radio' name='errorpages_search' value='no' id='errorpages_search_no' $errorpages_search_no>";
			$output[] = "<label for='errorpages_search_no'><span></span>".__("Disable", TEBRAVO_TRANS)."</label>";
			$output[] = "</td></tr>";
			
			//enable / disable error_pages_errorlog tool
			$error_pages_errorlog = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'error_pages_errorlog') ) );
			$error_pages_errorlog_yes = '';
			$error_pages_errorlog_no = '';
			if( $error_pages_errorlog == 'checked' ){$error_pages_errorlog_yes = 'checked';} else {$error_pages_errorlog_no = 'checked';}
			$error_pages_errorlog_help = __('If it is enabled, Every visitor details like country, device, IP, proxy and browser will be saved in the error log files.', TEBRAVO_TRANS);
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Save to Error Log", TEBRAVO_TRANS)."</strong></td></tr>";
			$output[] = "<tr class='tebravo_underTD'><td width=80%>".$error_pages_errorlog_help."</td><td>";
			$output[] = "<input type='radio' name='error_pages_errorlog' value='checked' id='error_pages_errorlog_checked' $error_pages_errorlog_yes>";
			$output[] = "<label for='error_pages_errorlog_checked'><span></span>".__("Enable", TEBRAVO_TRANS)."</label>";
			$output[] = "<input type='radio' name='error_pages_errorlog' value='no' id='error_pages_errorlog_no' $error_pages_errorlog_no>";
			$output[] = "<label for='error_pages_errorlog_no'><span></span>".__("Disable", TEBRAVO_TRANS)."</label>";
			$output[] = "</td></tr>";
			
			//template
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Error Pages Template", TEBRAVO_TRANS)."</strong></td></tr>";
			$output[] = "<tr class='tebravo_underTD'><td colspan=2>".__("Choose Template", TEBRAVO_TRANS).":<br />";
			$output[] = "<select name='errorpages_template'>";
			$output[] = $this->fetch_templates( $this->template );
			$output[] = "</select><br /><span class='tebravo_trail'>".__("Unlock more templates with the premium version", TEBRAVO_TRANS)."</span>";
			$output[] = "</td></tr>";
			
			//error description
			$page_404_desc = trim( esc_html( tebravo_utility::get_option( TEBRAVO_DBPREFIX.'404_page_desc' ) ) );
			$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Error Description", TEBRAVO_TRANS)."</strong></td></tr>";
			$output[] = "<tr class='tebravo_underTD'><td colspan=2>".__("Write Description", TEBRAVO_TRANS).":<br />";
			$output[] = "<textarea name='page_404_desc' style='width:250px; height:150px;'>".$page_404_desc."</textarea><br><font class='smallfont'>".__("No HTML!", TEBRAVO_TRANS);
			$output[] = "</td></tr>";
			
			$output[] = "<tr class='tebravo_underTD'><td colspan=2>";
			$output[] = $this->html->button(__("Save Settings", TEBRAVO_TRANS), 'submit');
			$output[] = "</td></tr>";
			
			$output[] = "</table>";
			$output[] = "</div>";
			$output[] = "</form>";
			
			if( !$_POST )
			{
				echo implode("\n", $output);
			} else {
				if( !empty($_POST['page_404'])
						&& !empty( $_POST['errorpages_search'] ) 
						&& !empty( $_POST['error_pages_errorlog'] ) 
						&& !empty( $_POST['errorpages_template'] )
						&& !empty( $_POST['_nonce'] )
						&& false !== wp_verify_nonce($_POST['_nonce'], $this->html->init->security_hash.'errorpages-settings'))
				{
					tebravo_utility::update_option( TEBRAVO_DBPREFIX.'404_page', trim( esc_html( $_POST['page_404'] ) ) );
					tebravo_utility::update_option( TEBRAVO_DBPREFIX.'errorpages_search', trim( esc_html( $_POST['errorpages_search'] ) ) );
					tebravo_utility::update_option( TEBRAVO_DBPREFIX.'errorpages_template', trim( esc_html( $_POST['errorpages_template'] ) ) );
					tebravo_utility::update_option( TEBRAVO_DBPREFIX.'404_page_desc', trim( esc_html( $_POST['page_404_desc'] ) ) );
					tebravo_utility::update_option( TEBRAVO_DBPREFIX.'error_pages_errorlog', trim( esc_html( $_POST['error_pages_errorlog'] ) ) );
					
					echo "Saving ...";
					tebravo_redirect_js($this->html->init->admin_url.'-error_pages&msg=01');
				} else {
					tebravo_redirect_js($this->html->init->admin_url.'-error_pages&err=02');
				}
			}
			
			$this->html->end_tab_content();
			$this->html->footer();
		}
		
		public function fetch_templates( $selected=false )
		{
			$dirs = tebravo_dirs::read( $this->pages_path.'/templates' );
			
			$options = '';
			foreach ( $dirs as $dir )
			{
				
				if( file_exists( $this->pages_path.'/templates/'.$dir.'/template.html')):
					$options .= "<option value='".$dir."' ";
					if( $selected == $dir ){ $options .= "selected"; }
					$options .= ">".ucfirst( $dir )."</option>";
				endif;
				
			}
			
			return $options;
		}
		
	}
	
	//run
	new tebravo_errorpages();
}

?>