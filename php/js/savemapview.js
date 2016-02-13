// ==============================================================
//	Copyright (C) 2016 Mark Vejvoda / Dennis Lloyd
//	Under GNU GPL v3.0
// ==============================================================

// Save google map view settings for re-use after refresh
function constructCookieName() {
var rtrvCid = $.getUrlVar('cid');
var cookieViewName="RiprunnerCookie_"+rtrvCid;
return cookieViewName;
}

function SaveView(mapObject) {
	try {
		var mapzoom = mapObject.getZoom();
		var mapcenter = mapObject.getCenter();
var maplat=mapcenter.lat();
var maplng=mapcenter.lng();
		var maptypeid = mapObject.getMapTypeId();
var cookiestring=maplat+"_"+maplng+"_"+mapzoom+"_"+maptypeid;
var exp = new Date();     //set new date object
exp.setTime(exp.getTime() + (1000*60*60*24));     //set it 24 hours
setViewCookie(constructCookieName(),cookiestring, exp);
		
		console.info('SUCCESS in SaveView cookie: ' + cookiestring);
	}
    catch(e) {
    	console.info('Error in SaveView msg: ' + e.message);	    	
    }
}

function LoadView(mapObject) {
	try {
var loadedstring=getViewCookie(constructCookieName());
	if(typeof loadedstring === 'undefined'){
			console.info('FAIL in LoadView cookie undefined');
	return false;
	}
	else {
			var mapzoom = mapObject.getZoom();
			var mapcenter = mapObject.getCenter();
			var maplat = mapcenter.lat();
			var maplng = mapcenter.lng();
			var maptypeid = mapObject.getMapTypeId();
			var cookiestring = maplat+"_"+maplng+"_"+mapzoom+"_"+maptypeid;
			
			console.info('In in LoadView current values: ' + cookiestring);
			
			var loadedstring = getViewCookie(constructCookieName());
		var splitstr = loadedstring.split("_");
		var latlng = new google.maps.LatLng(parseFloat(splitstr[0]), parseFloat(splitstr[1]));
			mapObject.setCenter(latlng);
			mapObject.setZoom(parseFloat(splitstr[2]));
			mapObject.setMapTypeId(splitstr[3])
		    console.info('SUCCESS in LoadView cookie: ' + loadedstring);
		}
	}
    catch(e) {
    	console.info('Error in LoadView msg: ' + e.message);	    	
	}
}

function setViewCookie(name, value, expires) {
document.cookie = name + "=" + escape(value) + "; path=/" + ((expires == null) ? "" : "; expires=" + expires.toGMTString());
}

function getViewCookie(c_name) {
	if (document.cookie.length > 0) {
c_start=document.cookie.indexOf(c_name + "=");
		if (c_start!=-1) { 
c_start=c_start + c_name.length+1; 
c_end=document.cookie.indexOf(";",c_start);
			if (c_end==-1) 
				c_end=document.cookie.length; 
return unescape(document.cookie.substring(c_start,c_end));
} 
}
}
//------------------------------------------------------------
$.extend({
  getUrlVars: function(){
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++) {
      hash = hashes[i].split('=');
      vars.push(hash[0]);
      vars[hash[0]] = hash[1];
    }
    return vars;
  },
  getUrlVar: function(name){
    return $.getUrlVars()[name];
  }
});
