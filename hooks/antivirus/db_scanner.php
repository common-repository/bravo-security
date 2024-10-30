<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}
if( !class_exists( 'tebravo_db_scanner' ) )
{
	
    class tebravo_db_scanner extends tebravo_antivirus_utility{
    	public $ipaddress,
    	$no_update,
    	$BadCount,
    	$pid,
    	$all_rows;
    	
    	//constructor
    	public function __construct()
    	{
    		
    		$count_posts = wp_count_posts('post');
    		$count_pages = wp_count_posts('page');
    		
    		$posts = $count_posts->publish + $count_posts->draft;
    		$pages = $count_pages->publish + $count_pages->draft;
    		
    		$this->all_rows = $posts+$pages;
    		$this->posts_count = $posts;
    		$this->pages_count = $pages;
    	}
    	
    	public function scan( $nohtml=false )
    	{
    		global $wpdb;
    		
    		ob_start();
    		$AllCount = $this->all_rows;
    		$BadCount = 0;
    		$GoodCount = 0;
    		$infected_files = '';
    		
    		
    		$wpdb->update(tebravo_utility::dbprefix().'scan_ps', array(
    				'status'=>'running',
    				
    		), array (
    				'pid' => $this->pid
    		));
    		$return = 0;
    		if( isset($_GET['blog_id']) && !empty($_GET['blog_id']))
    		{
    			if( !tebravo_utility::blog_exists( (int)$_GET['blog_id'] ) )
    			{
    				tebravo_die(true, __("Blog does not exists!", TEBRAVO_TRANS), false, false );
    			} else {
    			    switch_to_blog( esc_html( esc_js( $_GET['blog_id'] ) ) );
    				$return = 1;
    			}
    		}
    		
    		
    		$postlist = get_posts();
    		$pagelist = get_pages();
    		
    		$posts_array = array_merge( $postlist, $pagelist);
    		if($nohtml){
    			echo "<table border=0 width=100% cellspacing=0>";
    		}
    		$i=1;
    		
    		foreach($posts_array as $post)
    		{
    		    if(preg_match('/(?:<[\s\n\r\t]*script[\r\s\n\t]+.*>|<[\s\n\r\t]*meta.*refresh)/i', $post->post_title))
    			{
    				if($nohtml){
    			    echo "<tr class='tebravo_underTD'><td width=60%><i>".$i."</i>. <span class='tebravo_errors_span'>".esc_html($post->post_title)."</span></td>
<td width=25%><font color=brown>".__("Suspect", TEBRAVO_TRANS)."!</font></td>
<td><a href='post.php?post=".$post->ID."&action=edit' target=_blank>".__("Edit", TEBRAVO_TRANS)."</a></td></tr>";
    				}
    				
    				$BadCount++;
    				$this->flush_buffers();
    				
    				$infected_files .= $post->ID.',';
    				
    			}
    			else
    			{
    				$GoodCount++;
    				if($nohtml){
    					$edit_url = str_replace("network/", "", admin_url("post.php?post=".$post->ID."&action=edit"));
    				echo "<tr class='tebravo_underTD'><td width=60%><i>".$i."</i>. ".esc_html($post->post_title)."</td>
<td width=25%><font color=green>".__("Clean", TEBRAVO_TRANS)."!</font></td>
<td><a href='$edit_url' target=_blank>".__("Edit", TEBRAVO_TRANS)."</a></td></tr>";
    				}
    				$this->flush_buffers();
    				
    			}
    			$i++;
    			
    			if($i==500){sleep(1);}
    		}
    		if($nohtml){
    		echo "</table>";
    		}
    		
    		$result = "There are <b>".$BadCount."</b> ".__("Suspected Posts or Pages", TEBRAVO_TRANS). " from <b>".$AllCount."</b>";
    		
    		$this->BadCount = $BadCount;
    		$wpdb->update(tebravo_utility::dbprefix().'scan_ps', array(
    				'status'=>'finished',
    				'infected'=>$BadCount,
    				'cheked_files'=>$GoodCount+$BadCount,
    				'p_percent'=>'100',
    				'infected_results' => $infected_files
    		), array (
    				'pid' => $this->pid
    		));
    		if($nohtml){
    		?>
        	<script>
        	jQuery(".tebravo_loading").hide();
			jQuery("#scanner_ajax_result").html('<?php echo $result;?>');
        	</script>
        	<?php 
    		}
    		
    		if( 1==$return ){
    			switch_to_blog( get_current_blog_id() );
    		}
    	}
    	
    	public function get_results( $pid )
    	{
    		global $wpdb;
    		
    		ob_start();
    		$row = $wpdb->get_row( "SELECT * FROM " .tebravo_utility::dbprefix()."scan_ps WHERE pid='$pid' Limit 1");
    		$result = '--';
    		if( null !== $row )
    		{
    			echo "<table border=0 width=100% cellspacing=0>";
    			
    			$BadCount = 0;
    			$GoodCount = 0;
    			$infected_files = '';
    			$AllCount = $this->all_rows;
    			$i=1;
    			
    			if( $row->infected_results != '')
    			{
    				$exp = explode(",", $row->infected_results);
    				foreach( $exp as $infected)
    				{
    					if( !empty( $infected ) ){
    						$edit_url = str_replace("network/", "", admin_url("post.php?post=".$infected."&action=edit"));
    					echo "<tr class='tebravo_underTD'><td width=60%><i>".$i."</i>. <span class='tebravo_errors_span'>".esc_html(get_the_title($infected))."</span></td>
<td width=25%><font color=brown>".__("Suspect", TEBRAVO_TRANS)."!</font></td>
<td><a href='".$edit_url."' target=_blank>".__("Edit", TEBRAVO_TRANS)."</a></td></tr>";
    					
    					$BadCount++;
    					$this->flush_buffers();
    					}
    				}
    			} else {
    				echo "<tr class='tebravo_underTD'><td>".__("Good News! No Infected Found.", TEBRAVO_TRANS)."</td></tr>";
    			}
    			
    			$result = "There are <b>".$BadCount."</b> ".__("Suspected Posts or Pages", TEBRAVO_TRANS). " from <b>".$AllCount."</b>";
    			
    			echo "</table>";
    		}
    		
    		?>
        	<script>
        	jQuery(".tebravo_loading").hide();
			jQuery("#scanner_ajax_result").html('<?php echo $result;?>');
        	</script>
        	<?php 
    	}
    	
    	protected function flush_buffers(){
    		ob_end_flush();
    		flush();
    		ob_start();
    	} 
    	
    }
}