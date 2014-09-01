// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================

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




//Note: This example requires that you consent to location sharing when
//prompted by your browser. If you see a blank space instead of the map, this
//is probably because you have denied permission for location sharing.
var isGeoCoordsFound = false;
var getGeoCoordinatesForm = null;
var getGeoCoordinatesWatchId = null;
var getGeoCoordinatesTimeoutId = null;

function handleGeoCoords(coords, form) {
	//debugger;
	if(coords != null && form != null) {
		if(coords[0] != null && coords[0] != 0 && coords[1] != 0) {
			form.action = form.action + '&lat=' + coords[0] + '&long=' + coords[1];
			return true;
		}
	}
	return false;
}

function getGeoCoordinates_success(position) {
	//debugger;
	clearTimeout(getGeoCoordinatesTimeoutId);
	
	if(isGeoCoordsFound == false) {
		isGeoCoordsFound = true;
		var lat_lng = [ position.coords.latitude,position.coords.longitude ];
		
		//alert('GEO Found:' + lat_lng);
		
		if(handleGeoCoords(lat_lng,getGeoCoordinatesForm)) {
			getGeoCoordinatesForm.submit();
		}
	}
};
function getGeoCoordinates_error(err) {
	//debugger;
	clearTimeout(getGeoCoordinatesTimeoutId);
	alert(err);
};

function getGeoCoordinates_timeout() {
	//debugger;
	clearTimeout(getGeoCoordinatesTimeoutId);
	//alert('Generic timeout.');
	if(getGeoCoordinatesForm != null) {
		getGeoCoordinatesForm.submit();
	}
};

function watchGeocodePosition(position){
	//debugger;
	//alert('GEO Watch');
	clearTimeout(getGeoCoordinatesTimeoutId);
	navigator.geolocation.clearWatch(getGeoCoordinatesWatchId);
	getGeoCoordinates_success(position);
};

function getGeoCoordinates(form) {
	getGeoCoordinatesForm = form;
	
	var options = {
			enableHighAccuracy: true,
			timeout: 5000,
			maximumAge: 3600000
	};
	
	// Try HTML5 geolocation
	//debugger;
	if(navigator.geolocation) {
		getGeoCoordinatesTimeoutId = setTimeout("getGeoCoordinates_timeout()", 8000);
		navigator.geolocation.getCurrentPosition(getGeoCoordinates_success,getGeoCoordinates_error,options);
		getGeoCoordinatesWatchId = navigator.geolocation.watchPosition(watchGeocodePosition);
		//debugger;
		return [ null, null ];
	} 
	else {
		// Browser doesn't support Geolocation
		//debugger;
		handleNoGeolocation(false);
		return null;
	}
}

function handleNoGeolocation(errorFlag) {
	//debugger;
	if (errorFlag) {
	 var content = 'Error: The Geolocation service failed.';
	} 
	else {
	 var content = 'Error: Your browser doesn\'t support geolocation.';
	}
}

//google.maps.event.addDomListener(window, 'load', initialize);

function confirmAppendGeoCoordinates(confirm_msg,form) {
	//debugger;
	if(confirm(confirm_msg)) {
		var coords = getGeoCoordinates(form);
		if(coords != null) {
			if(coords[0] == null) {
				return false;
			}
			handleGeoCoords(coords,form);
			return true;
		}
		return true;
	}
	return false;
}
