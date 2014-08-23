function submitenter(myfield,e,call_event) {
	var keycode;
	if (window.event) 
		keycode = window.event.keyCode;
	else if (e) 
		keycode = e.which;
	else 
		return true;
	
	if (keycode == 13) {
		if(typeof call_event === 'undefined') {
			myfield.form.submit();
		}
		else {
			call_event();
		}
	
		return false;
	}
	else
		return true;
}

function enterMovesFocus(myfield,e,focusField) {
	var keycode;
	if (window.event) 
		keycode = window.event.keyCode;
	else if (e) 
		keycode = e.which;
	else 
		return;
	
	if (keycode == 13) {
		focusField.focus();
	}
}