<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}

if( !class_exists( 'tebravo_tour' ) )
{
	class tebravo_tour
	{
		public static $installed;
		
		public function __construct()
		{
			self::$installed = trim( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'installed') );
		}
		
		public static function dashboard()
		{
			if( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'installed')  != 'installed'){return;}
			if( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'tour')  == 'done'){return;}
			$helper = new tebravo_html();
			$message = __("Do you want to take tour?! It may help you to explore Bravo features.", TEBRAVO_TRANS);
			
			//$helper->popup_modal(true, $message);
			?>
			<script>
			
			var bravo_dashboard = new Anno({
  target: '.tebravo_title',
  position:'right',
  content: "<?php echo $message;?>",
  autoFocusLastButton:false,
  buttons: [
    {
      text: 'Start Tour',
      click: function(anno, evt){
        window.open('<?php echo $helper->init->admin_url;?>-settings', "_self")
      }
    },{
      text: 'No Thanks',
      className: 'anno-btn-low-importance',
      click: function(anno, evt){
        anno.hide();
        window.location.href="<?php echo $helper->init->admin_url;?>&action=end_tour";
      }
    }
  ]
})

jQuery(document).ready(function()
		{
	bravo_dashboard.show();
		});
			</script>
			<?php 
		}
		
		
		public static function settings()
		{
			if( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'installed')  != 'installed'){return;}
			if( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'tour')  == 'done'){return;}
			$helper = new tebravo_html();
			?>
		<script>

		var tebravo_tour1 = new Anno([{
			  target  : '.tebravo_underTD:eq(0)', 
			  position: 'top',
			  content : 'Hide/Show Bravo admin bar',
				  buttons: [
					  {
						text:'End Tour',
						click: function(anno, evt){
					        anno.hide();
					        window.location.href="<?php echo $helper->init->admin_url;?>&action=end_tour";
					      }
					  },
					  [AnnoButton.NextButton]
					  ]
			}, {
			  target  : '.tebravo_underTD:eq(1)',
			  position: 'top',
			  content : "Hide wordpress version from tags, scripts and stylesheets.",
			}, {
			  target  : '.tebravo_underTD:eq(8)',
			  position: 'bottom',
			  content : "Prevent hotlinking, e.g: no any websites can include your images in their pages"
			}, {
			  target  : '.tebravo_underTD:eq(9)',
			  position: 'top',
			  className: 'anno-width-200', // 150,175,200,250 (default 300)
			  content : 'Add some trusted domains to whitelist.'
			}, {
			  target  : '.tebravo_underTD:eq(12)',
			  arrowPosition: 'center-bottom',
			  content : 'Security hash for more secure side by side Wordpress nonce, It should be unique.'
			}, {
				  target  : '#backuphtaccess',
				  arrowPosition: 'left',
				  content : 'You should backup your htaccess or nginx.conf file before change hotlinking(images) settings.',
				  buttons: [
					  {
						text:'Continue',
						 click: function(anno, evt){
						        window.open('<?php echo $helper->init->admin_url;?>-wadmin', "_self")
						      }
					  },
					  {
							text:'End Tour',
							click: function(anno, evt){
						        anno.hide();
						        window.location.href="<?php echo $helper->init->admin_url;?>&action=end_tour";
						      }
						  }
					  ]
				}]);

jQuery(document).ready(function()
		{
	tebravo_tour1.show();
		});
</script>
		<?php 
		}
		
		
		public static function wpadmin()
		{
			if( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'installed')  != 'installed'){return;}
			if( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'tour')  == 'done'){return;}
			$helper = new tebravo_html();
			?>
		<script>

		var tebravo_tour2 = new Anno([
			 {
			  target  : '.tebravo_underTD:eq(6)',
			  position: 'bottom',
			  content : "All countries will be blocked from crossing to wp-admin except you're allowed countries, to disable this option, leave it blank/empty",
			}, {
			  target  : '.tebravo_underTD:eq(7)',
			  position: 'bottom',
			  content : "Like as allowed countries, only these IPs will be cross to wp-admin, to disable this option, leave it blank/empty"
			}, {
			  target  : '.tebravo_underTD:eq(1)',
			  position: 'top',
			  className: 'anno-width-200', // 150,175,200,250 (default 300)
			  content : 'Hide wp-login using this wizard.'
			}, {
			  target  : '.tebravo_underTD:eq(2)',
			  arrowPosition: 'top',
			  content : 'Hide wp-admin link, It will be show 404 page, to login you should use login link only.'
			}, {
				  target  : '.tebravo_underTD:eq(3)',
				  arrowPosition: 'top',
				  content : 'It is important option, If you forget your wp-admin open, you will be logged out automatically after (n) seconds.'
			}, {
				  target  : '.tebravo_underTD:eq(4)',
				  arrowPosition: 'bottom',
				  content : 'Two Step Login in, you have four options, choose one if them to protect your and clients log in.',
				  buttons: [
					  {
						text:'Continue',
						 click: function(anno, evt){
						        window.open('<?php echo $helper->init->admin_url;?>-wconfig', "_self")
						      }
					  },
					  {
							text:'End Tour',
							click: function(anno, evt){
						        anno.hide();
						        window.location.href="<?php echo $helper->init->admin_url;?>&action=end_tour";
						      }
						  }
					  ]
				}]);

jQuery(document).ready(function()
		{
	tebravo_tour2.show();
		});
</script>
		<?php 
		}
		
		
		public static function wpconfig()
		{
			if( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'installed')  != 'installed'){return;}
			if( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'tour')  == 'done'){return;}
			$helper = new tebravo_html();
			?>
		<script>

		var tebravo_tour3 = new Anno([
			 {
			  target  : '.tebravo_underTD:eq(0)',
			  position: 'bottom',
			  content : "it is very important to change wp database prefix, you can do it using this wizard.",
			}, {
			  target  : '.tebravo_underTD:eq(3)',
			  position: 'bottom',
			  content : "keep your Wordpress up to date"
			}, {
			  target  : '.tebravo_underTD:eq(6)',
			  position: 'top',
			  className: 'anno-width-200', // 150,175,200,250 (default 300)
			  content : 'Disable Wordpress debug, It is important to hide your wordpress errors.'
			}, {
				  target  : '.tebravo_underTD:eq(7)',
				  arrowPosition: 'bottom',
				  content : 'If you need more security, You should update your Wordpress salts.',
				  buttons: [
					  {
						text:'Continue',
						 click: function(anno, evt){
						        window.open('<?php echo $helper->init->admin_url;?>-bruteforce', "_self")
						      }
					  },
					  {
							text:'End Tour',
							click: function(anno, evt){
						        anno.hide();
						        window.location.href="<?php echo $helper->init->admin_url;?>&action=end_tour";
						      }
						  }
					  ]
				}]);

jQuery(document).ready(function()
		{
	tebravo_tour3.show();
		});
</script>
		<?php 
		}
		
		public static function bruteforce()
		{
			if( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'installed')  != 'installed'){return;}
			if( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'tour')  == 'done'){return;}
			$helper = new tebravo_html();
			?>
		<script>

		var tebravo_tour4 = new Anno([
			 {
			  target  : '.tebravo_underTD:eq(0)',
			  position: 'bottom',
			  content : "Enable/Disable Brute Force Protection.",
			}, {
			  target  : '.tebravo_underTD:eq(1)',
			  position: 'bottom',
			  content : "Choose login method, Username only or Email only or WP default method."
			}, {
				  target  : '.tebravo_underTD:eq(4)',
				  position: 'top',
				  className: 'anno-width-200', // 150,175,200,250 (default 300)
				  content : 'It is recommened to add your IP to whitelist.'
				}, {
					  target  : '.tebravo_underTD:eq(7)',
					  position: 'top',
					  className: 'anno-width-200', // 150,175,200,250 (default 300)
					  content : 'Choose some blacklist usernames to prevent it from log in.'
					}, {
						  target  : '.tebravo_underTD:eq(8)',
						  position: 'top',
						  className: 'anno-width-200', // 150,175,200,250 (default 300)
						  content : 'Choose some blacklist usernames to prevent it from register.'
						}, {
				  target  : '.tebravo_underTD:eq(9)',
				  arrowPosition: 'bottom',
				  content : 'Prevent some email provider, like e.g: mail.ru, most of hackers use this email hosting.',
				  buttons: [
					  {
						text:'Continue',
						 click: function(anno, evt){
						        window.open('<?php echo $helper->init->admin_url;?>-antivirus&p=settings', "_self")
						      }
					  },
					  {
							text:'End Tour',
							click: function(anno, evt){
						        anno.hide();
						        window.location.href="<?php echo $helper->init->admin_url;?>&action=end_tour";
						      }
						  }
					  ]
				}]);

jQuery(document).ready(function()
		{
	tebravo_tour4.show();
		});
</script>
		<?php 
		}
		
		public static function antivisurs_settings()
		{
			if( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'installed')  != 'installed'){return;}
			if( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'tour')  == 'done'){return;}
			$helper = new tebravo_html();
			?>
		<script>

		var tebravo_tour5 = new Anno([
			 {
			  target  : '.tebravo_headTD:eq(0)',
			  position: 'bottom',
			  content : "Enable/Disable antivirus tools.",
			}, {
			  target  : '.tebravo_underTD:eq(2)',
			  position: 'bottom',
			  content : "before using safe browsing scanner, you should get an API key from google."
			}, {
				  target  : '.tebravo_underTD:eq(5)',
				  position: 'top',
				  className: 'anno-width-200', // 150,175,200,250 (default 300)
				  content : 'Scan attachments, It is very important if you have writers or editors.'
				}, {
				  target  : '.tebravo_headTD:eq(2)',
				  arrowPosition: 'bottom',
				  content : 'Enable/Disable filechange scanner, You should add cron job event from Bravo menu > cronjobs.',
				  buttons: [
					  {
						text:'Continue',
						 click: function(anno, evt){
						        window.open('<?php echo $helper->init->admin_url;?>-recaptcha', "_self")
						      }
					  },
					  {
							text:'End Tour',
							click: function(anno, evt){
						        anno.hide();
						        window.location.href="<?php echo $helper->init->admin_url;?>&action=end_tour";
						      }
						  }
					  ]
				}]);

jQuery(document).ready(function()
		{
	tebravo_tour5.show();
		});
</script>
		<?php 
		}
		
		public static function recaptcha()
		{
			if( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'installed')  != 'installed'){return;}
			if( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'tour')  == 'done'){return;}
			$helper = new tebravo_html();
			?>
		<script>

		var tebravo_tour6 = new Anno([
			 {
			  target  : '.tebravo_underTD:eq(4)',
			  position: 'top',
			  content : "Before using reCaptcha tools, you should get your site key, get it from google console.",
			}, {
				  target  : '.tebravo_underTD:eq(5)',
				  arrowPosition: 'bottom',
				  content : 'Before using reCaptcha tools, you should get site secret key, get it from google console.',
				  buttons: [
					  {
						text:'Continue',
						 click: function(anno, evt){
						        window.open('<?php echo $helper->init->admin_url;?>-firewall', "_self")
						      }
					  },
					  {
							text:'End Tour',
							click: function(anno, evt){
						        anno.hide();
						        window.location.href="<?php echo $helper->init->admin_url;?>&action=end_tour";
						      }
						  }
					  ]
				}]);

jQuery(document).ready(function()
		{
	tebravo_tour6.show();
		});
</script>
		<?php 
		}
		
		public static function firewall()
		{
			if( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'installed')  != 'installed'){return;}
			if( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'tour')  == 'done'){return;}
			$helper = new tebravo_html();
			?>
		<script>

		var tebravo_tour7 = new Anno([
			 {
			  target  : '.tebravo_underTD:eq(0)',
			  position: 'top',
			  content : "Choose your firewall security level, we call it 'profiles', set it to high for best security.",
			}, {
				  target  : '.tebravo_underTD:eq(1)',
				  position: 'top',
				  content : "If you enable it, Security level depends on Firewall profile level.",
				}, {
					  target  : '.tebravo_underTD:eq(7)',
					  position: 'top',
					  content : "Do not forget to put your IP in the whitelist.",
					}, {
				  target  : '.tebravo_tabs li:eq(2)',
				  position: 'right',
				  arrowPosition: 'left',
				  content : 'After you save your firewall profile, you can see all rules here.',
				  buttons: [
					  {
						text:'Continue',
						 click: function(anno, evt){
						        window.open('<?php echo $helper->init->admin_url;?>-logwatch', "_self")
						      }
					  },
					  {
							text:'End Tour',
							click: function(anno, evt){
						        anno.hide();
						        window.location.href="<?php echo $helper->init->admin_url;?>&action=end_tour";
						      }
						  }
					  ]
				}]);

jQuery(document).ready(function()
		{
	tebravo_tour7.show();
		});
</script>
		<?php 
		}
		
		public static function log()
		{
			if( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'installed')  != 'installed'){return;}
			if( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'tour')  == 'done'){return;}
			$helper = new tebravo_html();
			?>
		<script>

		var tebravo_tour8 = new Anno([
			 {
			  target  : '.tebravo_tabs li:eq(0)',
			  position: 'top',
			  content : "Watch Bravo activity.",
			}, {
				  target  : '.tebravo_tabs li:eq(1)',
				  position: 'right',
				  arrowPosition: 'left',
				  content : 'Watch PHP errors , If you enable PHP security from Firewall.',
				  buttons: [
					  {
						text:'Continue',
						 click: function(anno, evt){
						        window.open('<?php echo $helper->init->admin_url;?>-backups', "_self")
						      }
					  },
					  {
							text:'End Tour',
							click: function(anno, evt){
						        anno.hide();
						        window.location.href="<?php echo $helper->init->admin_url;?>&action=end_tour";
						      }
						  }
					  ]
				}]);

jQuery(document).ready(function()
		{
	tebravo_tour8.show();
		});
</script>
		<?php 
		}
		
		public static function backups()
		{
			if( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'installed')  != 'installed'){return;}
			if( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'tour')  == 'done'){return;}
			$helper = new tebravo_html();
			?>
		<script>

		var tebravo_tour8 = new Anno([
			 {
				  target  : '#dbbackup',
				  position: 'right',
				  arrowPosition: 'left',
				  content : 'Create new DB backup.',
				  buttons: [
					  {
						text:'Continue',
						 click: function(anno, evt){
						        window.open('<?php echo $helper->init->admin_url;?>-cronjobs', "_self")
						      }
					  },
					  {
							text:'End Tour',
							click: function(anno, evt){
						        anno.hide();
						        window.location.href="<?php echo $helper->init->admin_url;?>&action=end_tour";
						      }
						  }
					  ]
				}]);

jQuery(document).ready(function()
		{
	tebravo_tour8.show();
		});
</script>
		<?php 
		}
		
		public static function cronjobs()
		{
			if( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'installed')  != 'installed'){return;}
			if( tebravo_utility::get_option(TEBRAVO_DBPREFIX.'tour')  == 'done'){return;}
			$helper = new tebravo_html();
			?>
		<script>

		var tebravo_tour8 = new Anno([
			 {
				  target  : '.button_small',
				  position: 'top',
				  content : 'Choose schedule events then click save.',
				  buttons: [
					  
					  {
							text:'End Tour',
							click: function(anno, evt){
						        anno.hide();
						        window.location.href="<?php echo $helper->init->admin_url;?>&action=end_tour";
						      }
						  }
					  ]
				}]);

jQuery(document).ready(function()
		{
	tebravo_tour8.show();
		});
</script>
		<?php 
		}
	}
}
?>