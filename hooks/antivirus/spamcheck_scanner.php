<?php 
//Exit if get it directly!
if(!defined('ABSPATH')){exit;}
if( !class_exists( 'tebravo_spamcheck_scanner' ) )
{
	
    class tebravo_spamcheck_scanner extends tebravo_antivirus_utility{
    	public $ipaddress,
    	$servers = array(),
    	$pid,
    	$domain;
    	
    	//constructor
    	public function __construct()
    	{
    		$this->servers = $this->servers_list();
    		
    		$url = get_bloginfo( 'url' );
    		$parse = parse_url($url);
    		
    		$this->domain = $parse['host'];
    		
    		$this->ipaddress = @gethostbyname( $this->domain );
    		
    		if( !filter_var($this->ipaddress, FILTER_VALIDATE_IP) )
    		{
    			trigger_error('<strong>Error:</strong> Can not get ipaddress using this function <u>gethostbyname</u>');
    		}
    	}
    	
    	public function scan( )
    	{
    		global $wpdb;
    		
    		ob_start();
    		$AllCount = count( $this->servers );
    		$BadCount = 0;
    		$GoodCount = 0;
    		$infected_files = '';
    		
    		$reverse_ip = implode(".", array_reverse(explode(".", $this->ipaddress)));
    		
    		$wpdb->update(tebravo_utility::dbprefix().'scan_ps', array(
    				'status'=>'running',
    				
    		), array (
    				'pid' => $this->pid
    		));
    		
    		?>
    		<script>
    		jQuery("#start_scan").hide();
    		</script>
    		<?php 
    		echo "<table border=0 width=100% cellspacing=0>";
    		$i=1;
    		foreach($this->servers as $host)
    		{
    			if(checkdnsrr($reverse_ip.".".$host.".", "A"))
    			{
    				echo "<tr class='tebravo_underTD'><td width=60%><i>".$i."</i>. <span class='tebravo_errors_span'>".$reverse_ip.'.'.$host."</span></td><td><font color=brown>".__("Listed", TEBRAVO_TRANS)."!</font></td></tr>";
    				
    				$BadCount++;
    				$this->flush_buffers();
    				
    				$infected_files .= $reverse_ip.'.'.$host.',';
    				
    			}
    			else
    			{
    				$GoodCount++;
    				echo "<tr class='tebravo_underTD'><td width=60%><i>".$i."</i>. ".$reverse_ip.'.'.$host."</td><td><font color=green>".__("Not Listed", TEBRAVO_TRANS)."!</font></td></tr>";
    				$this->flush_buffers();
    				
    			}
    			$i++;
    		}
    		echo "</table>";
    		
    		$result = __("This ip has", TEBRAVO_TRANS)." <b>".$BadCount."</b> ".__("bad listings of", TEBRAVO_TRANS). " <b>".$AllCount."</b>";
    		
    		$wpdb->update(tebravo_utility::dbprefix().'scan_ps', array(
    				'status'=>'finished',
    				'scan_type'=>'spamcheck',
    				'infected'=>$BadCount,
    				'infected_files'=>$BadCount,
    				'cheked_files'=>$GoodCount+$BadCount,
    				'p_percent'=>'100',
    				'infected_results' => $infected_files
    		), array (
    				'pid' => $this->pid
    		));
    		
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
    	
    	public function servers_list()
    	{
    		$list=array(
    				"access.redhawk.org",
    				"b.barracudacentral.org",
    				"bl.emailbasura.org",
    				"bl.spamcannibal.org",
    				"bl.spamcop.net",
    				"bl.technovision.dk",
    				"blackholes.five-ten-sg.com",
    				"blackholes.wirehub.net",
    				"blacklist.sci.kun.nl",
    				"block.dnsbl.sorbs.net",
    				"blocked.hilli.dk",
    				"bogons.cymru.com",
    				"cart00ney.surriel.com",
    				"cbl.abuseat.org",
    				"dev.null.dk",
    				"dialup.blacklist.jippg.org",
    				"dialups.mail-abuse.org",
    				"dialups.visi.com",
    				"dnsbl.ahbl.org",
    				"dnsbl.antispam.or.id",
    				"dnsbl.cyberlogic.net",
    				"dnsbl.kempt.net",
    				"dnsbl.njabl.org",
    				"dnsbl.sorbs.net",
    				"dnsbl-1.uceprotect.net",
    				"dnsbl-2.uceprotect.net",
    				"dnsbl-3.uceprotect.net",
    				"duinv.aupads.org",
    				"dul.dnsbl.sorbs.net",
    				"dul.ru",
    				"escalations.dnsbl.sorbs.net",
    				"hil.habeas.com",
    				"http.dnsbl.sorbs.net",
    				"intruders.docs.uu.se",
    				"ips.backscatterer.org",
    				"korea.services.net",
    				"mail-abuse.blacklist.jippg.org",
    				"misc.dnsbl.sorbs.net",
    				"msgid.bl.gweep.ca",
    				"new.dnsbl.sorbs.net",
    				"no-more-funn.moensted.dk",
    				"old.dnsbl.sorbs.net",
    				"pbl.spamhaus.org",
    				"proxy.bl.gweep.ca",
    				"psbl.surriel.com",
    				"pss.spambusters.org.ar",
    				"rbl.schulte.org",
    				"rbl.snark.net",
    				"recent.dnsbl.sorbs.net",
    				"relays.bl.gweep.ca",
    				"relays.bl.kundenserver.de",
    				"relays.mail-abuse.org",
    				"relays.nether.net",
    				"rsbl.aupads.org",
    				"sbl.spamhaus.org",
    				"smtp.dnsbl.sorbs.net",
    				"socks.dnsbl.sorbs.net",
    				"spam.dnsbl.sorbs.net",
    				"spam.olsentech.net",
    				"spamguard.leadmon.net",
    				"spamsources.fabel.dk",
    				"tor.ahbl.org",
    				"web.dnsbl.sorbs.net",
    				"whois.rfc-ignorant.org",
    				"xbl.spamhaus.org",
    				"zen.spamhaus.org",
    				"zombie.dnsbl.sorbs.net",
    				"bl.tiopan.com",
    				"dnsbl.abuse.ch",
    				"tor.dnsbl.sectoor.de",
    				"ubl.unsubscore.com",
    				"cblless.anti-spam.org.cn",
    				"dnsbl.tornevall.org",
    				"dnsbl.anticaptcha.net",
    				"dnsbl.dronebl.org"
    		);
    		
    		return $list;
    	}
    }
}