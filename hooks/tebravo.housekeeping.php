<?php 
/**
 * Hook: BRAVO.HOUSEKEEPING
 * Cleaner Module for Wordpress.
 * @since 1.0
 * Copyrights (C) 2017 Technoyer Solutions Ltd. <support@technoyer.com>
 */

//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if(!class_exists( 'tebravo_housekeeping' ))
{
    class tebravo_housekeeping
	{
    	private $tmp_path;
    	
    	public function __construct()
    	{
    		$this->tmp_path = TEBRAVO_DIR.'/'.TEBRAVO_BACKUPFOLDER.'/tmp';
    	}
	  //check unwanted files
	    private function check_filenames()
	    {
	    	$file_str = array( "license", "readme", "read_me", "debug.log" );
	    	$matching_files = $this->compare_files( ABSPATH , $file_str);
	    	$exp = explode('#tebravo#', $matching_files);
	    	$exp = array_map( 'trim', $exp);
	    	
	    	return $exp;
	    	
	    }
	    //delete unwanted files
	    private function delete_files()
	    {
	    	$this->html = new tebravo_html();
	    	
	    	if( empty( $_POST['_nonce'])
	    			|| false === wp_verify_nonce($_POST['_nonce'], $this->html->init->security_hash.'delete_files_housekeeping'))
	    	{
	    		exit;
	    	}
	    	
	    	$result = 0;
	    	$errors = 0;
	    	if( isset($_POST['files']) && is_array( $_POST['files'] ) )
	    	{
	    		$counter = count( esc_html( $_POST['files'] ) );
	    		
	    		if( $counter > 0 )
	    		{
	    			foreach ( $_POST['files'] as $file )
	    			{
	    				$file = esc_html( $file );
	    				if( file_exists( $file ) && is_file( $file )) { tebravo_files::remove( $file ); $result = 1;}
	    				
	    				//check if still exists
	    				if( file_exists( $file ) ){$errors++;}
	    			}
	    		}
	    	}
	    	
	    	if( $errors > 0 ){tebravo_die(true, __("Can not remove all or some files!", TEBRAVO_TRANS), false, true);}
	    	
	    	$redirect_to = $this->html->init->admin_url.'-housekeeping&err=07';
	    	if( $result > 0 ){
	    		$redirect_to = $this->html->init->admin_url.'-housekeeping&msg=05';
	    	}
	    	
	    	echo __("Loading", TEBRAVO_TRANS)."...";
	    	$this->html->footer();
	    	tebravo_redirect_js( $redirect_to );
	    }
	    //show files
	    private function show_files()
	    {
	    	$this->html = new tebravo_html();
	    	//check permissions
	    	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
	    		    	
	    	if( empty( $_GET['_nonce'])
	    			|| false===wp_verify_nonce($_GET['_nonce'],$this->html->init->security_hash.'delete-housekeeping')){exit;}
	    			
	    	$output[] = "<form action='".$this->html->init->admin_url."-housekeeping&action=delfiles' method=post>";
	    	$output[] = "<input type='hidden' name='_nonce' value='".$this->html->init->create_nonce('delete_files_housekeeping')."'>";
	    	
	    	$list = $this->check_filenames();
	    	$countFiles = ( count( $list ) - 1);
	    	
	    	$files = "<div class='tebravo_block_blank' style='width:100%'><table border=0 width=100% cellspacing=0>";
	    	$files .= "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Clean Up Files", TEBRAVO_TRANS)."</strong> (".__("Files", TEBRAVO_TRANS).": ".$countFiles.")</td></tr>";
	    	$files .= "<tr class=tebravo_headTD><td>";
	    	$files .= "<input checked type='checkbox' id='tebravo_checkall'></td>";
	    	$files .= "<td><label for='tebravo_checkall'>".__("Select/Unselect All", TEBRAVO_TRANS)."</label></td></tr>";
	    	
	    	if( is_array( $list ) && null!==$list )
	    	{
	    		foreach ( $list as $file )
	    		{
	    			if ($file!=='' && file_exists( $file ) && is_file( $file ) ):
		    			$files .= "<tr class='tebravo_underTD'><td width=20>";
		    			$files .= "<input type='checkbox' name='files[]' class='tebravo_checkclass' value='".$file."' checked></td>";
		    			$files .= "<td><strong>".basename( $file )."</strong><br /><i>".$file."</i></td>";
		    			$files .= "</tr>";
	    			endif;
	    		}
	    	} else {
	    		$files .= "<tr><td colspan=2>".__("No Files Found!", TEBRAVO_TRANS)."</td></tr>";
	    	}
	    	$files .= "</table>";
	    	
	    	$output[] = $files;
	    	$output[] = $this->html->button(__("Delete Selected", TEBRAVO_TRANS));
	    	$output[] = "</div></form>";
	    	
	    	echo implode("\n", $output);
	    	?>
	    	<script>
        	
        	jQuery(document).ready(function () {
        		jQuery("#tebravo_checkall").click(function () {
        			jQuery(".tebravo_checkclass").prop('checked', jQuery(this).prop('checked'));
        	    });
        	});

        	</script>
        
        	<?php 
	    	
	    }
	    //compare files in all dirs
	    private function compare_files( $dir , $file_str)
	    {
	    	//check permissions
	    	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
	    	
	    	if( is_dir( $dir ) ):
	    	$files = tebravo_dirs::read( $dir ) ;
	    	endif;
	    	
	    	$output = '';
	    	
	    	if( is_array( $files ) ):
	    	foreach ($files as $file)
	    	{
	    		if( is_dir( $dir.'/'.$file ) )
	    		{
	    			$output .= $this->compare_files( $dir.'/'.$file, $file_str);
	    		}
	    		if( !empty( $file ) )
	    		if( preg_match('/'.implode("|", $file_str).'/i', basename( $file ) ) )
	    		{
	    			if( tebravo_files::extension( $file ) != 'php' )
	    			{
	    				$output .= $dir.'/'.$file.'#tebravo#';
	    			}
	    		}
	    	}
	    	endif;
	    	
	    	return $output;
	    }
	    
	    private function tmp_counter()
	    {
	    	//check permissions
	    	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
	    	
	    	if( is_dir( $this->tmp_path ) ):
	    	$dirs = tebravo_dirs::read( $this->tmp_path );
	    	$i=0;
	    	foreach ($dirs as $file)
	    	{
	    		if( $file!='.'
	    				&& $file!='..'
	    				&& $file!='.htaccess'
	    				&& $file!='index.php'
	    				&& file_exists( $this->tmp_path.'/'.$file ))
	    		{
	    			
	    			$i++;
	    		}
	    		
	    	}
	    	return $i;
	    	endif;
	    }
	    
	    private function delete_tmp()
	    {
	    	//check permissions
	    	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
	    	
	    	
	    	if( is_dir( $this->tmp_path ) ):
	    	$dirs = tebravo_dirs::read( $this->tmp_path );
	    	$errors = 0;
	    	foreach ($dirs as $file)
	    	{
	    		if( $file!='.'
	    				&& $file!='..'
	    				&& $file!='.htaccess'
	    				&& $file!='index.php'
	    				&& file_exists( $this->tmp_path.'/'.$file ))
	    		{ 
	    		
	    			tebravo_files::remove( $this->tmp_path.'/'.$file );
	    			
	    			//check if still exists
	    			if( file_exists( $this->tmp_path.'/'.$file ) )
	    			{
	    				$errors++;
	    			}
	    		}
	    			
	    	}
	    	
	    	if( $errors > 0 ){tebravo_die(true, __("Can not remove all or some files!", TEBRAVO_TRANS), false, true);}
	    	
	    	endif;
	    }
	    //unwanted DB rows :counter
	    private function counter( $target )
	    {
	    	$counter = 0;
	    	global $wpdb;
	    	
	    	//check permissions
	    	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
	    	
	    	
	    	switch ($target)
	    	{
	    		case 'tmp': $counter = ( ( $this->tmp_counter() ) ); break;
	    		case 'files': $counter = ( count( $this->check_filenames() ) - 1); break;
	    		case 'revision': $counter = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_type = 'revision'"); break;
	    		case 'autodraft': $counter = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status = 'auto-draft'"); break;
	    		case 'draft': $counter = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts WHERE post_status = 'draft'"); break;
	    		case 'postmeta': $counter = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->postmeta pm LEFT JOIN $wpdb->posts wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL"); break;
	    		case 'moderated': $counter = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved = '0'"); break;
	    		case 'trash': $counter = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved = 'trash'"); break;
	    		case 'spam': $counter = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->comments WHERE comment_approved = 'spam'"); break;
	    		case 'commentmeta': $counter = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->commentmeta WHERE comment_id NOT IN (SELECT comment_id FROM $wpdb->comments)"); break;
	    		case 'relationships': $counter = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->term_relationships WHERE term_taxonomy_id=1 AND object_id NOT IN (SELECT id FROM $wpdb->posts)"); break;
	    		case 'feed': $counter = $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->options WHERE option_name LIKE '_site_transient_browser_%' OR option_name LIKE '_site_transient_timeout_browser_%' OR option_name LIKE '_transient_feed_%' OR option_name LIKE '_transient_timeout_feed_%'"); break;
	    	}
	    	
	    	return (int)$counter;
	    }
	    //delete unwanted DB rows
	    private function delete( $target )
	    {
	    	global $wpdb;
	    	$helper = new tebravo_html();
	    	
	    	if( empty( $_GET['_nonce'])
	    			|| false===wp_verify_nonce($_GET['_nonce'],$helper->init->security_hash.'delete-housekeeping')){exit;}
	    	
	    	//check permissions
	    	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
	    	
	    	switch ($target)
	    	{
	    		case 'tmp': $this->delete_tmp(); break;
	    		case 'revision': $wpdb->get_var("DELETE FROM $wpdb->posts WHERE post_type = 'revision'"); break;
	    		case 'autodraft': $wpdb->get_var("DELETE FROM $wpdb->posts WHERE post_status = 'auto-draft'"); break;
	    		case 'draft': $wpdb->get_var("DELETE FROM $wpdb->posts WHERE post_status = 'draft'"); break;
	    		case 'postmeta': $wpdb->get_var("DELETE pm FROM $wpdb->postmeta pm LEFT JOIN $wpdb->posts wp ON wp.ID = pm.post_id WHERE wp.ID IS NULL"); break;
	    		case 'moderated': $wpdb->get_var("DELETE FROM $wpdb->comments WHERE comment_approved = '0'"); break;
	    		case 'trash': $wpdb->get_var("DELETE FROM $wpdb->comments WHERE comment_approved = 'trash'"); break;
	    		case 'spam': $wpdb->get_var("DELETE FROM $wpdb->comments WHERE comment_approved = 'spam'"); break;
	    		case 'commentmeta': $wpdb->get_var("DELETE FROM $wpdb->commentmeta WHERE comment_id NOT IN (SELECT comment_id FROM $wpdb->comments)"); break;
	    		case 'relationships': $wpdb->get_var("DELETE FROM $wpdb->term_relationships WHERE term_taxonomy_id=1 AND object_id NOT IN (SELECT id FROM $wpdb->posts)"); break;
	    		case 'feed': $wpdb->get_var("DELETE FROM $wpdb->options WHERE option_name LIKE '_site_transient_browser_%' OR option_name LIKE '_site_transient_timeout_browser_%' OR option_name LIKE '_transient_feed_%' OR option_name LIKE '_transient_timeout_feed_%'"); break;
	    	}
	    }
		//optimize DB
		private function optimize()
		{
			global $wpdb;
			
			//check permissions
			if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
			
			
			$query = 'SHOW TABLE STATUS FROM `'.DB_NAME.'`';
			$result = $wpdb->get_results($query);
			foreach($result as $row){
				$query_opt = 'OPTIMIZE TABLE '.$row->Name;
				$wpdb->query($query_opt);
			}
		
		}
	    //dashboard //HTML
	    public function dashboard()
	    {
	    	$this->html = new tebravo_html();
	    	
	    	$desc = __("Clean your Wordpress.", TEBRAVO_TRANS);
	    	$extra = "<a href='".$this->html->init->admin_url."-housekeeping&action=optimize' class='tebravo_curved'>".__("Optimize DB", TEBRAVO_TRANS)."</a>";
	    	$this->html->header(__("Housekeeping (Wordpress Cleaner Module)", TEBRAVO_TRANS), $desc, 'housekeeping.png', $extra);
	    	$output[] = "<div class='tebravo_block_blank' style='width:100%'>";
	    	$js = '<script>';
	    	//check permissions
	    	if( false === $this->html->init->get_access( 'manage_options' , true )) {wp_die( TEBRAVO_NO_ACCESS_MSG ); exit;}
	    	//show files
	    	if( !empty( $_GET['action']) && $_GET['action'] == 'showfiles' )
	    	{
	    		$this->show_files();
	    		$output[] = "</div>";
	    		echo implode("\n", $output);
	    		$this->html->footer();
	    		exit;
	    	}
	    	//delete files
	    	if( !empty( $_GET['action']) && $_GET['action'] == 'delfiles' )
	    	{
	    		$this->delete_files();
	    		echo __("Loading", TEBRAVO_TRANS)."...";
	    		$this->html->footer();
	    		exit;
	    	}
	    	//optimize DB
	    	if( !empty( $_GET['action']) && $_GET['action'] == 'optimize' )
	    	{
	    		$this->optimize();
	    		echo __("Loading", TEBRAVO_TRANS)."...";
	    		$this->html->footer();
	    		$redirect_to = $this->html->init->admin_url.'-housekeeping&msg=07';
	    		tebravo_redirect_js( $redirect_to );
	    		exit;
	    	}
	    	//Delete From DB
	    	if( !empty( $_GET['action']) && $_GET['action'] == 'clean' )
	    	{
	    		
	    		$t_array = array ("tmp", "revision", "draft", "auto-draft", "postmeta", "moderated", "trash", "spam", "commentmeta", "relationships", "feed");
	    		
	    		if( isset( $_GET['t']) && in_array( $_GET['t'], $t_array) )
	    		{
	    			$t = esc_html( esc_js ( $_GET['t'] ) );
	    			$this->delete( $t );
	    			$redirect_to = $this->html->init->admin_url.'-housekeeping&msg=08';
	    		} else {
	    			$redirect_to = $this->html->init->admin_url.'-housekeeping&msg=02';
	    		}
	    		
	    		echo __("Loading", TEBRAVO_TRANS)."...";
	    		$this->html->footer();
	    		
	    		tebravo_redirect_js( $redirect_to );
	    		exit;
	    	}
	    	
	    	$nonce = $this->html->init->create_nonce('delete-housekeeping');
	    	
	    	$output[] = "<table border=0 width=100% cellspacing=0>";
	    	//files
	    	$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Directories", TEBRAVO_TRANS)."</strong></td></tr>";
	    	$output[] = "<tr class='tebravo_underTD'><td width=30%><strong>".__("Files", TEBRAVO_TRANS)."</strong></td><td>";
	    	$output[] = $this->html->button_small(__("Show", TEBRAVO_TRANS)." (".$this->counter('files').")", "button", "files");
	    	$output[] = "</td></tr>";
	    	$js .= "jQuery('#files').click(function(){";
	    	$js .= "window.location.href='".$this->html->init->admin_url."-housekeeping&action=showfiles&_nonce=".$nonce."';";
	    	$js .= "});";
	    	//tmp
	    	$output[] = "<tr class='tebravo_underTD'><td width=30%><strong>".__("/tmp", TEBRAVO_TRANS)."</strong></td><td>";
	    	$output[] = $this->html->button_small(__("Delete", TEBRAVO_TRANS)." (".$this->counter('tmp').")", "button", "tmp");
	    	$output[] = "</td></tr>";
	    	$js .= "jQuery('#tmp').click(function(){";
	    	$js .= "window.location.href='".$this->html->init->admin_url."-housekeeping&action=clean&t=tmp&_nonce=".$nonce."';";
	    	$js .= "});";
	    	//revision
	    	$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Posts", TEBRAVO_TRANS)."</strong></td></tr>";
	    	$output[] = "<tr class='tebravo_underTD'><td width=30%><strong>".__("Posts (Revision)", TEBRAVO_TRANS)."</strong></td><td>";
	    	$output[] = $this->html->button_small(__("Delete", TEBRAVO_TRANS)." (".$this->counter('revision').")", "button", "revision");
	    	$output[] = "</td></tr>";
	    	$js .= "jQuery('#revision').click(function(){";
	    	$js .= "window.location.href='".$this->html->init->admin_url."-housekeeping&action=clean&t=revision&_nonce=".$nonce."';";
	    	$js .= "});";
	    	//draft
	    	$output[] = "<tr class='tebravo_underTD'><td width=30%><strong>".__("Posts (Draft)", TEBRAVO_TRANS)."</strong></td><td>";
	    	$output[] = $this->html->button_small(__("Delete", TEBRAVO_TRANS)." (".$this->counter('draft').")", "button", "draft");
	    	$output[] = "</td></tr>";
	    	$js .= "jQuery('#draft').click(function(){";
	    	$js .= "window.location.href='".$this->html->init->admin_url."-housekeeping&action=clean&t=draft&_nonce=".$nonce."';";
	    	$js .= "});";
	    	//auto draft
	    	$output[] = "<tr class='tebravo_underTD'><td width=30%><strong>".__("Posts (Auto Draft)", TEBRAVO_TRANS)."</strong></td><td>";
	    	$output[] = $this->html->button_small(__("Delete", TEBRAVO_TRANS)." (".$this->counter('auto-draft').")", "button", "autodraft");
	    	$output[] = "</td></tr>";
	    	$js .= "jQuery('#autodraft').click(function(){";
	    	$js .= "window.location.href='".$this->html->init->admin_url."-housekeeping&action=clean&t=auto-draft&_nonce=".$nonce."';";
	    	$js .= "});";
	    	//postmeta
	    	$output[] = "<tr class='tebravo_underTD'><td width=30%><strong>".__("Post Meta", TEBRAVO_TRANS)."</strong></td><td>";
	    	$output[] = $this->html->button_small(__("Delete", TEBRAVO_TRANS)." (".$this->counter('postmeta').")", "button", "postmeta");
	    	$output[] = "</td></tr>";
	    	$js .= "jQuery('#postmeta').click(function(){";
	    	$js .= "window.location.href='".$this->html->init->admin_url."-housekeeping&action=clean&t=postmeta&_nonce=".$nonce."';";
	    	$js .= "});";
	    	//moderated
	    	$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Comments", TEBRAVO_TRANS)."</strong></td></tr>";
	    	$output[] = "<tr class='tebravo_underTD'><td width=30%><strong>".__("Moderated Comments", TEBRAVO_TRANS)."</strong></td><td>";
	    	$output[] = $this->html->button_small(__("Delete", TEBRAVO_TRANS)." (".$this->counter('moderated').")", "button", "moderated");
	    	$output[] = "</td></tr>";
	    	$js .= "jQuery('#moderated').click(function(){";
	    	$js .= "window.location.href='".$this->html->init->admin_url."-housekeeping&action=clean&t=moderated&_nonce=".$nonce."';";
	    	$js .= "});";
	    	//trash
	    	$output[] = "<tr class='tebravo_underTD'><td width=30%><strong>".__("Trash Comments", TEBRAVO_TRANS)."</strong></td><td>";
	    	$output[] = $this->html->button_small(__("Delete", TEBRAVO_TRANS)." (".$this->counter('trash').")", "button", "trash");
	    	$output[] = "</td></tr>";
	    	$js .= "jQuery('#trash').click(function(){";
	    	$js .= "window.location.href='".$this->html->init->admin_url."-housekeeping&action=clean&t=trash&_nonce=".$nonce."';";
	    	$js .= "});";
	    	//spam
	    	$output[] = "<tr class='tebravo_underTD'><td width=30%><strong>".__("Spam Comments", TEBRAVO_TRANS)."</strong></td><td>";
	    	$output[] = $this->html->button_small(__("Delete", TEBRAVO_TRANS)." (".$this->counter('spam').")", "button", "spam");
	    	$output[] = "</td></tr>";
	    	$js .= "jQuery('#spam').click(function(){";
	    	$js .= "window.location.href='".$this->html->init->admin_url."-housekeeping&action=clean&t=spam&_nonce=".$nonce."';";
	    	$js .= "});";
	    	//commentmeta
	    	$output[] = "<tr class='tebravo_underTD'><td width=30%><strong>".__("Comment Meta", TEBRAVO_TRANS)."</strong></td><td>";
	    	$output[] = $this->html->button_small(__("Delete", TEBRAVO_TRANS)." (".$this->counter('commentmeta').")", "button", "commentmeta");
	    	$output[] = "</td></tr>";
	    	$js .= "jQuery('#commentmeta').click(function(){";
	    	$js .= "window.location.href='".$this->html->init->admin_url."-housekeeping&action=clean&t=commentmeta&_nonce=".$nonce."';";
	    	$js .= "});";
	    	//relationships
	    	$output[] = "<tr class='tebravo_headTD'><td colspan=2><strong>".__("Others", TEBRAVO_TRANS)."</strong></td></tr>";
	    	$output[] = "<tr class='tebravo_underTD'><td width=30%><strong>".__("Relationships", TEBRAVO_TRANS)."</strong></td><td>";
	    	$output[] = $this->html->button_small(__("Delete", TEBRAVO_TRANS)." (".$this->counter('relationships').")", "button", "relationships");
	    	$output[] = "</td></tr>";
	    	$js .= "jQuery('#relationships').click(function(){";
	    	$js .= "window.location.href='".$this->html->init->admin_url."-housekeeping&action=clean&t=relationships&_nonce=".$nonce."';";
	    	$js .= "});";
	    	//feed
	    	$output[] = "<tr class='tebravo_underTD'><td width=30%><strong>".__("Dashboard Transient Feed", TEBRAVO_TRANS)."</strong></td><td>";
	    	$output[] = $this->html->button_small(__("Delete", TEBRAVO_TRANS)." (".$this->counter('feed').")", "button", "feed");
	    	$output[] = "</td></tr>";
	    	$js .= "jQuery('#feed').click(function(){";
	    	$js .= "window.location.href='".$this->html->init->admin_url."-housekeeping&action=clean&t=feed&_nonce=".$nonce."';";
	    	$js .= "});";
	    	$output[] = "</table>";
	    	$output[] = "</div>";
	    	
	    	
	    	$js .= "</script>";
	    	echo implode("\n", $output);
	    	echo $js;
	    	$this->html->footer();
	    }
	}
}

?>