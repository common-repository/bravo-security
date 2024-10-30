<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

//get blog info
if( !function_exists( 'tebravo_blog_info' ) )
{
	function tebravo_blog_info( $blog_id=false, $request=false )
	{
		//if we have a blog ID
		if( $blog_id && is_numeric( $blog_id ) )
		{
			return get_blog_details( array( 'blog_id' => $blog_id ) );
		}
		//multisite is on
		if( function_exists( 'is_multisite') && is_multisite() )
		{
			$current_blog_id = get_current_blog_id();
			return get_blog_details( array( 'blog_id' => $current_blog_id ), $request);
		}
		//default
		return get_bloginfo($request);
	}
}

//die when error found
if( !function_exists( 'tebravo_die' ) )
{
	function tebravo_die( $echo=true, $message=false, $include_header=false, $include_footer=false )
	{
		$html = new tebravo_html();
		
		//call wp_die when needed
		if( !$message ){wp_die(); exit;}
		//print header
		if( $include_header )
		{
			$html->header(__("Error", TEBRAVO_TRANS), __("Something wrong happened!", TEBRAVO_TRANS), 'dialog-error.png');
		}
		
		$ajax_url = add_query_arg(array(
				'action' => 'tebravo_contact_support',
				'_nonce' => wp_create_nonce('contact-support')
		), admin_url('admin-ajax.php'));
		
		$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
		$output[] = "<table border=0 width=100% cellspacing=0>";
		$output[] = "<tr class='tebravo_errorTD'><td width=16><img src='".plugins_url('/assets/img/blocked.png', TEBRAVO_PATH)."'></td>";
		$output[] = "<td>".$message."</td></tr>";
		$output[] = "<tr class='tebravo_headTD' id='contact_support_btn'><td colspan=2>".$html->button_small(__("Contact Support",TEBRAVO_TRANS), "button", "contact-support-btn")."</td></tr>";
		
		//contact support
		$domain = tebravo_getDomainUrl(tebravo_selfURL());
		$admin_email = get_option('admin_email');
		$report = addslashes($message)."<hr>";
		$title = 'BRAVO -Client Support Request';
		$p = '';
		if( defined('TEBRAVO_CURRENT_PAGE_TITLE') ) 
		{
			$report .= "<strong>Page Title:</strong> ".TEBRAVO_CURRENT_PAGE_TITLE."<br />";
		}
		if( defined('TEBRAVO_CURRENT_PAGE') )
		{
			$report .= "<strong>Page:</strong> ".TEBRAVO_CURRENT_PAGE."<br />";
		}
		if( defined('TEBRAVO_CURRENT_PAGE_P') )
		{
			$report .= "<strong>Sub Page:</strong> ".TEBRAVO_CURRENT_PAGE_P."<br />";
		}
		if( defined('TEBRAVO_CURRENT_PAGE_ACTION') )
		{
			$report .= "<strong>Action -Sub Page:</strong> ".TEBRAVO_CURRENT_PAGE_ACTION."<br />";
		}
		
		if( isset($_GET['page'])){$p .= "<input type='hidden' name='page' value='".esc_html( $_GET['page'] )."'>";}
		if( isset($_GET['p'])){$p .= "<input type='hidden' name='p' value='".esc_html( $_GET['p'] )."'>";}
		if( isset($_GET['action'])){$p .= "<input type='hidden' name='p' value='".esc_html( $_GET['action'] )."'>";}
		
		$report .= "<strong>Domain:</strong>".$domain."<br />";
		$report .= "<strong>Admin E-Mail:</strong>".$admin_email;
		$output[] = "<tr class='tebravo_headTD' id='contact_support_form' style='display:none;'>";
		$output[] = "<td colspan=2>";
		$output[] = "<form action='".$ajax_url."' method=post id='contactsupportform'>";
		$output[] = "<u>".__("Domain", TEBRAVO_TRANS).":</u> <i>".$domain."</i><br />";
		$output[] = "<u>".__("Your E-Mail", TEBRAVO_TRANS).":</u> <i>".$admin_email."</i><hr><br />";
		$output[] = "<strong>".__("Your Custom Message", TEBRAVO_TRANS).":</strong><br />";
		$output[] = "<textarea style='width:350px; height:75px' name='message'></textarea><br />";
		$output[] = "<input type='checkbox' name='report' value='yes' checked id='report'>";
		$output[] = "<input type='hidden' name='title' value='".$title."'>";
		$output[] = "<input type='hidden' name='report' value='".$report."'>".$p;
		$output[] = "<label for='report'>".__("Include Report", TEBRAVO_TRANS)."</label><br />";
		$output[] = $html->button_small(__("Send", "submit", "contact-support-submit"));
		$output[] = "<span id='tebravo_send_loading' style='display:none;'><br /><img src='".plugins_url('assets/img/loading.gif', TEBRAVO_PATH)."'></span>";
		$output[] = "</form>";
		$output[] = "<div id='res'></div></td></tr>";
		
		$output[] = "</table></div>";
		
		$js = "<script>".PHP_EOL;
		$js .= "jQuery('#contact-support-btn').click(function(){".PHP_EOL;
		$js .= "jQuery('#contact_support_form').show();".PHP_EOL;
		$js .= "jQuery('#contact-support-btn').hide();".PHP_EOL;
		$js .= "});".PHP_EOL;
		$js .= "jQuery( \"#contactsupportform\" ).submit(function( event ) {
	// Stop form from submitting normally
		jQuery(\".tebravo_loading\").show(); // Show Loading Box and Darken Background
		var url = jQuery( \"#contactsupportform\" ).attr (\"action\");
jQuery('#tebravo_send_loading').show();
		jQuery.ajax({
	        url: url,
	        type: 'post',
	        dataType: 'html',
	        data: jQuery('form#contactsupportform').serialize(),
	        success: function(data) {
			jQuery( \"#res\" ).html( data );
	                 }
	    });

		
		event.preventDefault(); 
	});";
		$js .= "</script>";
		
		if( $echo ){ echo implode("\n", $output); echo $js;} else {return implode("\n", $output).$js;}
		//print footer
		if( $include_footer )
		{
			$html->footer();
		}
		
		exit;
	}
}

//create donate URL
if( !function_exists( 'tebravo_create_donate_url' ) )
{
	function tebravo_create_donate_url( $position=false )
	{
		return add_query_arg(
				array(
						'utm_source' => tebravo_getDomainUrl(tebravo_selfURL()),
						'utm_medium' => 'bravo_trial',
						'utm_content' => $position,
				), TEBRAVO_DONATE_URL);
	}
}

//creat plugin action links
if( !function_exists( 'tebravo_plugin_action_links' ) )
{
	function tebravo_plugin_action_links( $links )
	{
		$tebravo_version_type= 'free';
		if( defined('TEBRAVO_VERSIONTYPE')){$tebravo_version_type= TEBRAVO_VERSIONTYPE;}
		
		if( $tebravo_version_type!= 'PRO')
		{
			$tebravo_docs_url = "http://bravo.technoyer.com/wiki/index";
		} else {
			$tebravo_docs_url = "http://bravo.technoyer.com/wiki/index";
		}
		
		$links[] = '<a href="' . esc_url( $tebravo_docs_url) . '">' . __( 'Docs', TEBRAVO_TRANS ) . '</a>';
		if( $tebravo_version_type != 'PRO' ){
			$links[] = '<a target="_blank" href="'.tebravo_create_donate_url('action_links').'"><strong style="color: #2296D8; display: inline;">' . __( 'Upgrade To Pro', TEBRAVO_TRANS ) . '<strong></a>';
		}
		
		return $links;
	}
}


if( !class_exists('tebravo_utility'))
{
	class tebravo_utility
	{
		/**
		 * Get the master blog table prefix
		 * @return string
		 */
		public static function dbprefix()
		{
			global $wpdb;
			
			$blog_id = 1;
			if( defined( 'BLOG_ID_CURRENT_SITE' ) )
			{
				$blog_id = BLOG_ID_CURRENT_SITE;
			}
			$main_blog_prefix = $wpdb->get_blog_prefix( $blog_id );
			
			//return only main blog prefix
			return $main_blog_prefix.TEBRAVO_DBPREFIX;
		}
		public static function get_sites()
		{
			if ( function_exists( 'get_sites' ) && class_exists( 'WP_Site_Query' ) ) {
				$sites = get_sites();
				if( is_array( $sites) ) 
				{
					return $sites;
				}
			}
		}
		/**
		 * get wordpress option
		 * @param string $option_name
		 * @param string $option_value
		 * @param string $default
		 * @return mixed|boolean
		 */
		public static function get_option( $option_name, $default=false)
		{
			if( function_exists('is_multisite') && is_multisite() )
			{
				return get_site_option($option_name, $default);
			}
			
			return get_option($option_name, $default);
		}
		/**
		 * add new option to wordpress
		 * @param string $option_name
		 * @param string $option_value
		 * @return boolean
		 */
		public static function add_option( $option_name, $option_value='' )
		{
			if( function_exists('is_multisite') && is_multisite() )
			{
				return add_site_option($option_name, $option_value);
			}
			
			return add_option($option_name, $option_value);
		}
		/**
		 * update an exisiting option
		 * @param string $option_name
		 * @param string $option_value
		 * @return boolean
		 */
		public static function update_option( $option_name, $option_value='')
		{
			if( function_exists('is_multisite') && is_multisite() )
			{
				return update_site_option($option_name, $option_value);
			}
			
			return update_option($option_name, $option_value);
		}
		/**
		 * delete wordpress option
		 * @param string $option_name
		 * @return boolean
		 */
		public static function delete_option( $option_name )
		{
			if( function_exists('is_multisite') && is_multisite() )
			{
				return delete_site_option($option_name);
			}
			
			return delete_option($option_name);
		}
		/**
		 * check if option exists
		 * @param string $option_name
		 * @return boolean
		 */
		public static function is_option( $option_name )
		{
			if( self::get_option( $option_name ) )
			{
				return true;
			}
			return false;
		}
		/**
		 * get wordpress blog info
		 * @param string $show
		 * @return string
		 */
		public static function get_bloginfo( $show )
		{
			if( function_exists('is_multisite') && is_multisite() )
			{
				$blog_id = get_current_blog_id();
				$current_blog_details = get_blog_details( array( 'blog_id' => $blog_id ) );
				
				if( $show == 'name' ) {$show = 'blogname';}
				
				return $current_blog_details->$show;
					
			}
			
			if( $show == 'siteurl' ) {$show = 'wpurl';}
			return get_bloginfo( $show );
		}
		
		public static function get_securityhash()
		{
			$hash = self::get_option( TEBRAVO_DBPREFIX.'security_hash');
			if( !empty( $hash ) )
			{
				return esc_html( $hash );
			} else {
				$new_hash = tebravo_create_hash( 8 );
				self::update_option( TEBRAVO_DBPREFIX.'security_hash', $new_hash);
				return $new_hash;
			}
			
		}
		
		/**
		 * Checks if a blog exists and is not marked as deleted.
		 *
		 * @param  int $blog_id
		 * @param  int $site_id
		 * @return bool
		 */
		public static function blog_exists( $blog_id, $site_id = 0 ) {
			
			global $wpdb;
			static $cache = array ();
			
			$site_id = (int) $site_id;
			
			if ( 0 === $site_id )
				$site_id = get_current_site()->id;
				
				if ( empty ( $cache ) or empty ( $cache[ $site_id ] ) ) {
					
					if ( wp_is_large_network() ) // we do not test large sites.
						return TRUE;
						
						$query = "SELECT blog_id FROM $wpdb->blogs
						WHERE site_id = $site_id AND deleted = 0";
						
						$result = $wpdb->get_col( $query );
						
						// Make sure the array is always filled with something.
						if ( empty ( $result ) )
							$cache[ $site_id ] = array ( 'do not check again' );
							else
								$cache[ $site_id ] = $result;
				}
				
				return in_array( $blog_id, $cache[ $site_id ] );
		}
		
	}
}
?>