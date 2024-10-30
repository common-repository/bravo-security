<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

$message_body = '';
$close_option = esc_html(tebravo_utility::get_option( TEBRAVO_DBPREFIX."close_msg_body" ));
if(!empty($close_option))
{
    $message_body = nl2br(esc_html(tebravo_utility::get_option( TEBRAVO_DBPREFIX."close_msg_body" )));
    $message_body = str_replace('{%sitename%}',tebravo_utility::get_bloginfo('name'), $message_body);
    $message_body = str_replace('{%email%}',tebravo_utility::get_option('admin_email'), $message_body);
}
?>
<style>
body { 
  background: url(<?php  echo plugins_url("assets/img/bg.jpg", TEBRAVO_PATH);?>) no-repeat center center fixed; 
  -webkit-background-size: cover;
  -moz-background-size: cover;
  -o-background-size: cover;
  background-size: cover;
}
h1{
  color:#fff;
}
p{
  color:#fff;
}
</style>
<body>
<article>
<center>
<br />
<img src="<?php  echo plugins_url("assets/img/maintenance.png", TEBRAVO_PATH);?>">
<h1><?php echo esc_html(get_option( TEBRAVO_DBPREFIX."close_msg_head" ));?></h1>
<p><?php echo ($message_body);?></p>
</center>
</article>
</body>
</html>