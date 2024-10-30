function bravo_idle(){
	if( typeof(bravo) != 'undefined' ){
		var timer = parseInt(bravo.bravo_idle_timer);
		var action = parseInt(bravo.bravo_idle_actions);
		jQuery(document).idleTimer(timer);
		jQuery(document).bind("idle.idleTimer", function(){
			switch(parseInt(action)){
			case 1:
	            tebravo_idle_logout_in_admin();
	          break;
			case 2:
	            tebravo_idle_logout_in_frontend();
	          break;
			default:
				/*no thing to do*/
			}
		});
	}
}

function tebravo_idle_logout_in_frontend(type){
	
  jQuery.ajax({  
    type: 'POST',
    url: bravo.ajaxurl,
    data: {action: 'tebravo_idle_action_frontend'},
    error: function(MLHttpRequest, textStatus, errorThrown){ console.log(errorThrown); },
    success: function(response){ 
    jQuery(".tebravo_darkenBG").css({"height": jQuery(document).height()});	
    jQuery("#tebravo_loader").slideDown();
    jQuery('.tebravo_darkenBG').show(200);
    tebravo_progress();
    
    setTimeout(function()
    	{
    	window.location.href=response;
    	}	
    ,10000)
    }
  });
  return null;
}

function tebravo_idle_logout_in_admin(type){
	  jQuery.ajax({  
	    type: 'POST',
	    url: bravo.ajaxurl,
	    data: {action: 'tebravo_idle_logout_in_admin'},
	    error: function(MLHttpRequest, textStatus, errorThrown){ console.log(errorThrown); },
	    success: function(response){ console.log(response); }
	  });
	  return null;
	}

function tebravo_progress() {
	var timeleft = 10;
	var downloadTimer = setInterval(function(){
	  document.getElementById("tebravo_progressBar").value = 10 - --timeleft;
	  if(timeleft <= 0)
	    clearInterval(downloadTimer);
	},1000);
};

