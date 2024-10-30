// Get the modal
var tebravo_modal = document.getElementsByClassName('tebravo_modal');

// Get the button that opens the modal
var tebravo_btn = document.getElementsByClassName("tebravo_modal_btn");

// Get the <span> element that closes the modal
var tebravo_span = document.getElementsByClassName("tebravo_close")[0];

// When the user clicks on the button, open the modal 
function tebravo_open_modal()
{
	//tebravo_modal.style.display = "block";
	jQuery(".tebravo_modal").css("display", "block");
}
