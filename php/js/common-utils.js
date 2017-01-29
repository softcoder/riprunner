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
var appendGeoCoordinatesSpinner = null;

function handleGeoCoords(coords, form) {
	//debugger;
	
	// Check for valid coordinates and html form
	if(coords != null && form != null) {
		// Check for valid coordinates values
		if(coords[0] != null && coords[0] != 0 && coords[1] != 0) {
			// Append geo coords to html form action params
			form.action = form.action + '&lat=' + coords[0] + '&long=' + coords[1];
			
			if(appendGeoCoordinatesSpinner != null) {
				appendGeoCoordinatesSpinner.stop();
			}
			
			return true;
		}
	}
	return false;
}

function getGeoCoordinates_success(position) {
	//alert('GEO Found 1:' + position);
	//debugger;
	clearTimeout(getGeoCoordinatesTimeoutId);
	
	//alert('GEO Found:' + position);
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
	try {
		//debugger;
		clearTimeout(getGeoCoordinatesTimeoutId);
		//alert('GEO error: ' + err);
		
		// PERMISSION_DENIED
		//if(err.code != 1) {
		//alert('GEO error (' + err.code + '): ' + err.message);
		//}
		
		if(getGeoCoordinatesForm != null) {
			if(appendGeoCoordinatesSpinner != null) {
				appendGeoCoordinatesSpinner.stop();
			}
			
			getGeoCoordinatesForm.submit();
		}
	}
	catch(ex) {
		try {
			if(getGeoCoordinatesForm != null) {
				getGeoCoordinatesForm.submit();
			}
		}
		catch(ex2) {
			alert('Error submitting form: [' + ex.message + '] [' + ex2.message + ']');
		}
	}
};

function getGeoCoordinates_timeout() {
	//debugger;
	clearTimeout(getGeoCoordinatesTimeoutId);
	//alert('Generic timeout.');
	if(getGeoCoordinatesForm != null) {
		
		if(appendGeoCoordinatesSpinner != null) {
			appendGeoCoordinatesSpinner.stop();
		}
		
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
			timeout: 10000,
			maximumAge: 0
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

function appendGeoCoordinates(form) {
	//debugger;
	appendGeoCoordinatesSpinner = loadingSpinner();
	var coords = getGeoCoordinates(form);
	if(coords != null) {
		if(coords[0] == null) {
			if(appendGeoCoordinatesSpinner != null) {
				appendGeoCoordinatesSpinner.stop();
			}
			return false;
		}
		handleGeoCoords(coords,form);
		return true;
	}
	return true;
}

function confirmAppendGeoCoordinates(confirm_msg,form) {
	//debugger;
	if(confirm(confirm_msg)) {
		return appendGeoCoordinates(form);
	}
	return false;
}

function openURLHidden(url) {
	//
	//window.open(url,'_blank', 'toolbar=no,status=no,menubar=no,scrollbars=no,resizable=no,left=10000, top=10000, width=10, height=10, visible=none', '');
	//window.open(url,'_blank', 'toolbar=no,status=no,menubar=no,scrollbars=no,resizable=no, visible=none', '');
	//window.open(url,'_blank', '', '');
	
	// Visible for debugging
	//window.open(url,'_blank', 'width=400, height=100, visible=none', '');
	window.open(url,'_blank', 'toolbar=no,status=no,menubar=no,scrollbars=no,resizable=no,left=-1, top=-1, width=10, height=10, visible=none', '');
}

var openAjaxUrlSpinner = null;
function openAjaxUrl(url_path,hidden,max_retries,retry_freq,current_attempt) {
	//debugger;

	if(typeof current_attempt === 'undefined') {
		openAjaxUrlSpinner = loadingSpinner();
		current_attempt = 1;
	}
	else {
		current_attempt++;
	}
	
	if(typeof max_retries != 'undefined' && 
			typeof current_attempt != 'undefined' &&
		current_attempt > max_retries) {
		if(openAjaxUrlSpinner != null) {
			openAjaxUrlSpinner.stop();
		}
		return false;
	}

	//alert('current_attempt = ' + current_attempt);

	/*
	var w = null;
	if(typeof hidden === 'undefined' || hidden == false) {
	   w = window.open();
	}
	else {
       w= window.open('','_blank', 'toolbar=no,status=no,menubar=no,scrollbars=no,resizable=no,left=-1, top=-1, width=1, height=1, visible=none', '');
    }
	$("body", w.document).load(url_path, function() {
	    // do your stuff
		
	});
	*/
	
	
	$.ajax({
      url: url_path,
      data: $(this).serialize(),
      success: function(result) {
    	  //debugger;
    	  
  		  if(openAjaxUrlSpinner != null) {
			  openAjaxUrlSpinner.stop();
		  }
    	  
    	  var w = null;
    	  if(typeof hidden === 'undefined' || hidden == false) {
    		  w = window.open();
    	  }
    	  else {
    		  //w = window.open('','_blank', 'width=400, height=100, visible=none', '');
    		  w= window.open('','_blank', 'toolbar=no,status=no,menubar=no,scrollbars=no,resizable=no,left=-1, top=-1, width=1, height=1, visible=none', '');
    	  }
    	  
    	  // popup blockers result in null
    	  if(w != null) {
	    	  w.document.write(result);
	    	  //$(w.document.body).html(result);
	    	  //$(w.document.documentElement).outerHTML(result);
	    	  //w.document.documentElement.outerHTML = result;
    	  }
    	  else {
    		  console.info('In openAjaxUrl success, BUT ERROR!!! cannot popup page likely blocked from popup blocker!.');

    		  var error_div="<div class='container_center' id='error_msg'><div id='error_msg_close'><h3>X</h3></div><h3>CANNOT Open popups as you have a popup blocker for this website!</h3></div></center>";
    		  $(document.body).prepend(error_div);
    		  $(document).on('click','.error_msg_close',function(){
    			  $(this).parent().remove();
    		  });
    	  }
      },
      error: function(XMLHttpRequest, textStatus, errorThrown) {
    	  //debugger;
    	  
    	  if(typeof retry_freq === 'undefined') {
    		  retry_freq = 30000;
    	  }
    	  setTimeout(function() {
    		  openAjaxUrl(url_path,hidden,max_retries,retry_freq,current_attempt);
    		}, retry_freq);	        		
      }
    });

    return false;	
}

var submitAjaxFormSpinner = null;
function submitAjaxForm(formName,max_retries,retry_freq,current_attempt) {
	$(document).ready(function() {
	    $('#' + formName).submit(function() {
	    	//debugger;
	    	if(typeof current_attempt === 'undefined') {
	    		submitAjaxFormSpinner = loadingSpinner();
	    		current_attempt = 1;
	    	}
	    	else {
	    		current_attempt++;
	    	}
	    	
	    	if(typeof max_retries != 'undefined' && 
	    			typeof current_attempt != 'undefined' &&
	    		current_attempt >= max_retries) {
	    		
    		    if(submitAjaxFormSpinner != null) {
    		    	submitAjaxFormSpinner.stop();
	    		}
	    		
	    		return false;
	    	}

	    	//alert('current_attempt = ' + current_attempt);
	    	
	        $.ajax({
	            type: 'POST',
	            url: $(this).attr('action'),
	            data: $(this).serialize(),
	            cache: false,
	            success: function(result) {
	                //debugger;
	            	
	    		    if(submitAjaxFormSpinner != null) {
	    		    	submitAjaxFormSpinner.stop();
		    		}
	            	
	                $('body').html(result);
	            },
	        	error: function(XMLHttpRequest, textStatus, errorThrown) {
	        		//debugger;
	        		
	          	  if(typeof retry_freq === 'undefined') {
	        		  retry_freq = 30000;
	        	  }
	        		
        		  setTimeout(function() {
	        				$('#' + formName).submit();
	        			}, retry_freq);	        		
	        	}
	        })
	        return false;
	    });
	});
}

function loadingSpinner(parent,spinnerColorValue) {
    // Turn on spinner
    var spinnerColor = spinnerColorValue;
    if(typeof spinnerColor == 'undefined') {
    	spinnerColor = '#FFFF00';
    }
	
    var opts = {
	  lines: 13, // The number of lines to draw
	  length: 20, // The length of each line
	  width: 10, // The line thickness
	  radius: 30, // The radius of the inner circle
	  corners: 1, // Corner roundness (0..1)
	  rotate: 0, // The rotation offset
	  direction: 1, // 1: clockwise, -1: counterclockwise
	  color: spinnerColor, // #rgb or #rrggbb or array of colors
	  speed: 1, // Rounds per second
	  trail: 60, // Afterglow percentage
	  shadow: false, // Whether to render a shadow
	  hwaccel: false, // Whether to use hardware acceleration
	  className: 'spinner', // The CSS class to assign to the spinner
	  zIndex: 2e9, // The z-index (defaults to 2000000000)
	  top: '50%', // Top position relative to parent
	  left: '50%' // Left position relative to parent
	};
    //var target = document.getElementById('foo');
    //var spinner = new Spinner(opts).spin(target);
    
    var target = parent;
    if(typeof target == 'undefined') {
    	target = document.body;
    }
    
    return new Spinner(opts).spin(target);
}

//Note: This example requires that you consent to location sharing when
//prompted by your browser. If you see the error "The Geolocation service
//failed.", it means you probably did not give permission for the browser to
//locate you.

function getGEOLocationCoords(callback_fn) {

// Try HTML5 geolocation.
if (navigator.geolocation) {
 navigator.geolocation.getCurrentPosition(function(position) {
   var pos = {
     lat: position.coords.latitude,
     lng: position.coords.longitude
   };

   //infoWindow.setPosition(pos);
   //infoWindow.setContent('Location found.');
   //map.setCenter(pos);
   callback_fn(true,pos);
 }, function() {
   handleLocationError(true, callback_fn);
 });
} 
else {
 // Browser doesn't support Geolocation
 handleLocationError(false, callback_fn);
}
}

function handleLocationError(browserHasGeolocation, callback_fn) {
//infoWindow.setPosition(pos);
//infoWindow.setContent(browserHasGeolocation ?
//                     'Error: The Geolocation service failed.' :
//                     'Error: Your browser doesn\'t support geolocation.');
	
	callback_fn(false,browserHasGeolocation);
}