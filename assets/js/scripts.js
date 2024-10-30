jQuery("#backuphtaccess").click(function( $ )
{
	var tebravo_backup_htaccess_url_ = window.location.href;
	window.location.href= tebravo_backup_htaccess_url_+"&p=download_htacfile";
});

jQuery("#backuphtaccesscopy").click(function( $ )
{
	jQuery("#tebravo_results").show();
	jQuery("#tebravo_results").load(tebravo_backup_htaccess_url);
});


jQuery(document).ready(function(){
	if( typeof bravo_idle!='undefined')
		{
		bravo_idle();
		}
	
 });