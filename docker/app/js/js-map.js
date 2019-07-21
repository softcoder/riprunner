<script type="text/javascript"
    src="https://maps.googleapis.com/maps/api/js?key=${API_KEY}&alternatives=true"></script>
   <div style="width: 600px;">
     <div class="google-maps" id="map"></div> 
   </div>
   <script type="text/javascript"> 
	var directionsService = new google.maps.DirectionsService();
	var directionsDisplay = new google.maps.DirectionsRenderer();
	//${WATERSOURCE_LIST}
    var map = new google.maps.Map(document.getElementById('map'), {
		zoom: 13,
		center: new google.maps.LatLng(${CALLORIGIN}),
		draggable: true,
		scrollwheel: false,
		mapTypeId: google.maps.MapTypeId.ROADMAP,
		zoomControl: true,
		zoomControlOptions: {
			position: google.maps.ControlPosition.LEFT_TOP
			}
    });

	directionsDisplay.setMap(map);
	var request = {
		destination: "${DESTINATION}", 
		origin: "${FDLOCATION}",
		travelMode: google.maps.DirectionsTravelMode.DRIVING
	};

	var callOriginMarker = new google.maps.Marker({
		map: map,
		position: new google.maps.LatLng(${CALLORIGIN}), 
		title: "DESTINATION",
		clickable: true,
		icon: {
			path: google.maps.SymbolPath.CIRCLE,
			scale: 9,
			fillColor: "yellow",
			fillOpacity: 0.9,
			strokeWeight: 0.9
		},
	});
	//${WATERSOURCE_CODE}	
	directionsService.route(request, function(response, status) {
		if (status == google.maps.DirectionsStatus.OK) {
			directionsDisplay.setDirections(response);
		}
	//${KMLOVERLAY}
		marker.setMap(map);
	});
</script> 
   
