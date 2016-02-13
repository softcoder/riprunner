function getGEOLocationCoords_callback_fn(geoAccess,param2) {
	//alert('In getGEOLocationCoords_callback_fn' + geoAccess);
	//alert(param2);
	console.info('In getGEOLocationCoords_callback_fn: access = ' + geoAccess);
	if(geoAccess && typeof callout_geo_dest != 'undefined') {
        //var geo_tag = document.getElementById('geo_tag');
        //if(typeof geo_tag != 'undefined') {
        	//geo_tag.textContent = 'Your GEO Position: ' + param2.lat + ", " + param2.lng;
        	$( '#geo_tag' ).html( 'Position: ' + param2.lat + ", " + param2.lng );
        	//$( '#geo_tag' ).html( 'Your GEO Position: ' + param2.lat + ", " + param2.lng + " dest: " + callout_geo_dest);
        	console.info('You: ' + param2.lat + ", " + param2.lng + " dest: " + callout_geo_dest);
        	
        	var origin = new google.maps.LatLng( param2.lat, param2.lng );
        	var destination = callout_geo_dest; // using string

        	var directionsService = new google.maps.DirectionsService();
        	var request = {
        	    origin: origin, // LatLng|string
        	    destination: destination, // LatLng|string
        	    travelMode: google.maps.DirectionsTravelMode.DRIVING
        	};

        	directionsService.route( request, function( response, status ) {
        	    if ( status === 'OK' ) {
        	        var point = response.routes[ 0 ].legs[ 0 ];
        	        $( '#geo_tag' ).html( 'ETA: ' + point.duration.text + ' (' + point.distance.text + ')' );
        	        console.info('ETA: ' + point.duration.text + ' (' + point.distance.text + ')');
        	    }
        	} );        	
        //}
	}
}

function getGEOLocationCoords_initialize() {
	getGEOLocationCoords(getGEOLocationCoords_callback_fn);
}
// https://maps.googleapis.com/maps/api/js?v=3.exp&key={{ callout_details_vm.firehall.WEBSITE.WEBSITE_GOOGLE_MAP_API_KEY }}&alternatives=true&callback=map_initialize    
