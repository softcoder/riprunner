<script type="text/javascript">
    var directionDisplay;
    var directionsService = new google.maps.DirectionsService();
    var map;

    function initialize() {
        directionsDisplay = new google.maps.DirectionsRenderer();
        var DESTINATION = new google.maps.LatLng(-40.321, 175.54);
        var myOptions = {
            zoom: 20,
            mapTypeId: google.maps.MapTypeId.ROADMAP,
            center: DESTINATION
        }
        map = new google.maps.Map(document.getElementById("map_canvas"), myOptions);
        directionsDisplay.setMap(map);
        calcRoute();
    }

    function calcRoute() {

        var waypts = [];


stop = new google.maps.LatLng(-39.419, 175.57)
        waypts.push({
            location:stop,
            stopover:true});


        start  = new google.maps.LatLng(-40.321, 175.54);
        end = new google.maps.LatLng(-38.942, 175.76);
        var request = {
            origin: start,
            destination: "${DESTINATION}",
            waypoints: waypts,
            optimizeWaypoints: true,
            travelMode: google.maps.DirectionsTravelMode.WALKING
        };
        directionsService.route(request, function(response, status) {
            if (status == google.maps.DirectionsStatus.OK) {
                directionsDisplay.setDirections(response);
                var route = response.routes[0];

            }
        });
    }
</script>