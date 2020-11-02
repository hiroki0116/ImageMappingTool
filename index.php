<?php
require_once("flickr.php");


// Consumer Key
$app_key = 'appkey' ;
// Consumer Secret
$app_secret = 'appsecret' ;
//Create an instance
$flickr = new phpFlickr( $app_key , $app_secret ) ;



//Configure options
$option = array(

    "text" => "Tower",//default search key
    # How many img data
    "per_page" =>15,
    # media type
    "media" => 'photos',
    # data sort
    "sort" => 'relevance',
    # No UI display
    "safe_search" => 1,
    "extras" => 'url_q,url_c' ,
);



foreach( array( 'text' , 'per_page' , 'media', 'sort', 'safe_search' , 'extras' ) as $val )
    {
        if( isset( $_GET[ $val ] ) && $_GET[ $val ] != '' )
        {
            $option[ $val ] = $_GET[ $val ];
        }
    }

//Execute photos_search method and store the result
$result = $flickr->photos_search( $option ) ;

//Transform the file formart of result into JSON
$json = json_encode( $result );
//Convert json into an object
$obj = json_decode( $json ) ;



//Prepare an empty array to store geolocation and photos within the loop below
$geoLocation = array();
$photos = array();
foreach( $obj->photo as $photo ){
        if( !isset($photo->url_q) || !isset($photo->width_q) || !isset($photo->height_q) )
        {
            continue ;
        }

        $photoID = $photo->id;
        $t_src = $photo->url_q ;        // Thumnail url
        $t_width = $photo->width_q ;    // width of thumnail
        $t_height = $photo->height_q ;    // height of thumnail
        $o_src = ( isset($photo->url_c) ) ? $photo->url_c : $photo->url_q ;        // image url


        //Store the result of method "photos.get.getLocation"
        $geo = $flickr->photos_geo_getLocation($photoID);
        array_push($geoLocation,$geo);
        array_push($photos,$t_src);
    }


//Store only location object extracted from JSON
$location = array();
foreach($geoLocation as $column){
    array_push($location, $column['location']);
}


$jsonLocation = json_encode($location);//This is the data of geolocation
$jsonPhoto = json_encode($photos);//This is the data of photos
$jsonLocation = mb_convert_encoding($jsonLocation, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');
$jsonPhoto = mb_convert_encoding($jsonPhoto, 'UTF8', 'ASCII,JIS,UTF-8,EUC-JP,SJIS-WIN');


?>
<!DOCTYPE html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ImageMap</title>
    <style>

        #googlemap{
        height: 600px;
        width: 100%;
        }
    </style>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.3/css/bootstrap.min.css" integrity="sha384-MCw98/SFnGE8fJT3GXwEOngsV7Zt27NXFoaoApmYm81iuXoPkFOJwJ8ERdknLPMO" crossorigin="anonymous">

</head>
<body class="text-black-50 text-center">

<div class="mt-5">
    <h1>Image Mapping Tool</h1>
</div>
<hr class="w-50 my-3">

            <div id="googlemap"></div>
            <h2>Display photos published on Flickr</h2>

        <div class="mt-3">
            <form class="form-group">
                <p style="font-size:.9em; font-weight:700;"><label for="text">Enter Keyword</label></p>
                <p><input id="text" name="text" placeholder = "ex.) Tower"></p>
                <p><button class="btn btn-secondary">Search</button></p>
            </form>
            <h3>Address</h3>
            <p><textarea rows="8" id = "address"></textarea></p>
        </div>

    </div>
</div>




<!-- Javascript -->
<script>


//Import Php JSON data
var phpJson = <?php echo $jsonLocation; ?>;
var jsonPhoto = <?php echo $jsonPhoto; ?>;

//Add img src to the json
for (var i = 0; i < phpJson.length; i++){
    if(phpJson[i] == null){
        continue;
    }
    phpJson[i]["context"] = jsonPhoto[i];
};

//Elimate elements which causes False
var filteredJson = phpJson.filter(Boolean);


var markerData = [];
for (var i = 0; i < filteredJson.length; i++){
    markerData.push(
        {
         lat : Number(filteredJson[i]["latitude"]),
         lng : Number(filteredJson[i]["longitude"]),
         src : filteredJson[i]["context"]
         });
};

var map;
var marker = [];
var infoWindow = [];
var geocoder;

function initMap() {
    // Create a google map
    var mapLatLng = new google.maps.LatLng({lat: markerData[0]['lat'], lng: markerData[0]['lng']}); // pass the geoLocation of flickr to GoogleMaps method
    geocoder = new google.maps.Geocoder();
    map = new google.maps.Map(document.getElementById('googlemap'), { // Insert the map into html body
    center: mapLatLng, // define a center pin by using the return value of geolocation
    zoom: 3
    });


    for (var i = 0; i < markerData.length; i++) {
        markerLatLng = new google.maps.LatLng({lat: markerData[i]['lat'], lng: markerData[i]['lng']}); // Create data of longitude and latitude

        marker[i] = new google.maps.Marker({ // Add other markers
        position: markerLatLng,
            map: map
    });

    infoWindow[i] = new google.maps.InfoWindow({ //Add speech bubbles
        content: '<div class="googlemap">' + "<img src=" + markerData[i]["src"] + ">" + '</div>'//Contents of speech bubbles

    });

    markerEvent(i); //Add click event for the speech bubbles
    addressEvent(markerLatLng,i);
        }
}



//Speech Bubbles event
function markerEvent(i) {
    marker[i].addListener('click', function() {
    infoWindow[i].open(map, marker[i]);
    });
}
//Get JSON data and traslate geolocation into human-readable address
function addressEvent(markerLatLng,i){
    marker[i].addListener('click', function() {
        geocoder.geocode({ location: markerLatLng }, (results, status) => {
            document.getElementById('address').innerHTML = results[0].formatted_address;
        });
    });
}


</script>
<script src="https://maps.googleapis.com/maps/api/js?language=en&region=JP&key=key&callback=initMap" async defer></script>
<footer class="py-3 m-0 ">
  <p><script>document.write(new Date().getFullYear());</script> Hiroki, All Rights Reserved.</p>
</footer>
</body>
</html>
