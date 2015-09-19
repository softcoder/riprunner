<script type="text/javascript"
    src="https://maps.googleapis.com/maps/api/js?key=${API_KEY}&alternatives=true"></script>
   <div style="width: 600px;">
     <div class="google-maps" id="map"></div> 
   </div>
   <script type="text/javascript"> 
	var directionsService = new google.maps.DirectionsService();
	var directionsDisplay = new google.maps.DirectionsRenderer();
	var waterSource = [
      ['Community Park - 8000L', 53.963694, -122.567766, 1],
	  ['Forman Flats - 10,000 L', 53.946556, -122.673760, 2],
	  ['Shell-Glen Hall 1 - 15,000 L', 53.965286, -122.592838, 3]
    ];
    var map = new google.maps.Map(document.getElementById('map'), {
		zoom: 13,
		center: new google.maps.LatLng($(CALLORIGIN)),
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
		position: new google.maps.LatLng($(CALLORIGIN)), 
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

    var infowindow = new google.maps.InfoWindow();
    var marker, i;
    for (i = 0; i < waterSource.length; i++) {  
      marker = new google.maps.Marker({
        position: new google.maps.LatLng(waterSource[i][1], waterSource[i][2]),
   			icon: {
				path: google.maps.SymbolPath.CIRCLE,
				scale: 9,
				fillColor: "blue",
				fillOpacity: 0.9,
				strokeWeight: 0.9
			},
		map: map
      });
      google.maps.event.addListener(marker, 'click', (function(marker, i) {
        return function() {
          infowindow.setContent(waterSource[i][0]);
          infowindow.open(map, marker);
        }
      })(marker, i));
	}
	
	directionsService.route(request, function(response, status) {
		if (status == google.maps.DirectionsStatus.OK) {
			directionsDisplay.setDirections(response);
		}
	var boundaryLayer = new google.maps.KmlLayer({
		url: 'http://rr3.sgvfr.com/kml/boundaries.kml',
		preserveViewport: true,
		supressInfoWindows: true
	});
	//boundaryLayer.setMap(map);
	marker.setMap(map);
	});
</script> 
   
