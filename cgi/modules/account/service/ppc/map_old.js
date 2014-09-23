<style>
    #location_map_wrap {line-height:1.1;color:#333}
    #get_direction {color:#dc582a;text-decoration:none;margin-left:14px;}
    #map_direction {font-size:18px;}
    #map_address {display:inline-block;padding-right:14px;}
    #location_copy {font-size:49px;margin-bottom:5px;}
    #map_headline {color:#39757e;font-size:18px;font-weight:bold;}
</style>
<script src="//maps.googleapis.com/maps/api/js?libraries=places&key=AIzaSyAE-0lbc0-T16JHDQCFPu5bsARpQWYWUas&sensor=true" type="text/javascript"  charset="utf-8"></script>
<script type="text/javascript">
    if (typeof jQuery == 'undefined') {
        document.write(unescape("%3Cscript src='https://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js' type='text/javascript'%3E%3C/script%3E"));
    }
</script>
<script type="text/javascript">
    var centerFinder = {

        // defaults
        zoomLevel: 9,
        cf: null,
        locations: [],
        map: null,

        init: function (options) {
            options = options || {};
            cf = this;

            if (options.zoomLevel) {
                cf.zoomLevel = options.zoomLevel;
            }

            cf.events();

            cf.search()
        },

        displayResults: function (xml) {
            var ci = {};

            //
            $(xml).find('ns1\\:CenterInfo, CenterInfo').each(function(){
                ci = {
                    centerName: $(this).find("ns1\\:CenterName, CenterName").text(),
                    PhoneNumber: $(this).find("ns1\\:PhoneNumber,PhoneNumber").text(),
                    Street1: $(this).find("ns1\\:Street1,Street1").text(),
                    Street2: $(this).find("ns1\\:Street2,Street2").text(),
                    City: $(this).find("ns1\\:City,City").text(),
                    State: $(this).find("ns1\\:State,State").text(),
                    PostalCode: $(this).find("ns1\\:PostalCode,PostalCode").text(),
                    Country: $(this).find("ns1\\:Country,Country").text(),
                    CenterID: $(this).find("ns1\\:CenterID,CenterID").text()
                };
                return true;
            });
            if(ci.CenterID == 0){
                //window.location.href= '@@TrafficSourceLabel' + '?centerid=nf';
            }
            liveballData('center_city',ci.City);
            var address = ci.Street1 + ' ' + ci.Street2 + ', ' + ci.City + ', ' + ci.State + ' ' + ci.PostalCode + ' ' + ci.Country;

            // find the address and render the map
            var geocoder = new google.maps.Geocoder();

            geocoder.geocode( { 'address': address}, function(results, status) {
                if (status == google.maps.GeocoderStatus.OK) {
                    var latlng = results[0].geometry.location;
                    cf.renderMap(latlng, ci);
                }
            });
            //fill in data for map
            $('#location_copy').html(ci.centerName);
            $('#map_address').html(address);
            $('#centerid').val(ci.CenterID);
            $('#centeraddress').val(address);
            liveballData('center_name',ci.centerName);
        },

        renderMap: function (pos, ci) {
            var mapOptions = {
                zoom: cf.zoomLevel,
                center: pos,
                mapTypeId: google.maps.MapTypeId.ROADMAP
            }

            map = new google.maps.Map(document.getElementById("map-canvas"), mapOptions);

            var infowindow = new google.maps.InfoWindow();

            // create the marker and stick it on the map
            var markerHtml = cf.buildMarkerHtml(ci);
            var marker = new google.maps.Marker({
                position: pos,
                map: map,
                animation: google.maps.Animation.DROP
            });

            // bind the infowindow to a click on the marker
            google.maps.event.addListener(marker, 'click', function () {
                infowindow.setContent(markerHtml);
                infowindow.open(map, this);
            });
        },

        buildMarkerHtml: function (o) {
            var html = '';
            html += '<h3>' + o.centerName + '</h3>';
            html += '<div>' + o.Street1 + ' ' + o.Street2 + '<br>';
            html += o.City + ', ' + o.State + ' ' + o.PostalCode + '</div>';
            return html;
        },

        search: function () {
            var searchRequest = '/Outside/Scriptlet.ashx' +
                '?lb3id=' + _lbapi_lb3id + '&scrid=3' + '&rct=application/xml';
            $.ajax({
                type: "GET",
                url: searchRequest,
                async: false,
                dataType: (jQuery.support.leadingWhitespace ? "xml" : "text" ),
                success: function (xml) {
                    centerFinder.displayResults(xml);
                }
            });

            return false;
        },

        events: function () {
            var self = this;

            // add any event bindings here and they'll be automatically initialized

        }
    };

    $(document).ready(function(){
        centerFinder.init({
            zoomLevel: 9
        });
    });

</script>

<div id="location_map_wrap">
    <div id="map_headline">Club Nearest You:</div>
    <div id="location_copy"></div>
    <div id="map_direction"><span id="map_address"></span></div>
    <div id="map_widget_container" class="clear_wrap">
        <div class="map_widget_col_470" id="treatment_listings"></div>
        <div class="map_widget_col_450" id="map-canvas" style="width: 460px; height: 380px;"></div>
    </div>
</div>