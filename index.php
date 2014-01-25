<!DOCTYPE html>
<html>
  <head>
    <title>NIFT</title>
    <link rel="stylesheet"  type="text/css" href="styles/main.css" />
    <!-- Leaflet CSS -->
    <link rel="stylesheet" href="scripts/dist/leaflet.css" />
    <!--[if lte IE 8]><link rel="stylesheet" href="dist/leaflet.ie.css" /><![endif]-->
  </head>
  <body>
    <!-- Leaflet JavaScript -->
    <script src="scripts/dist/leaflet.js"></script>
    <!-- Personal Utilities -->
    <script src="scripts/utility.js"></script>
    <!-- JQuery Library -->
    <script src="scripts/jquery-1.7.1.js"></script>
    <div id="content">
      <h1>NIFT</h1>
      <!-- Controls describes the left panel that contains all of the 
           chronological controls -->
      <div id="controls">
        <div id="histogram_buttons"></div>
        <!-- This Canvas is where the histogram will be drawn by the script -->
        <canvas id="histogram" width="280px" height="640px" style="float:right">
        </canvas>
        <!-- Allows the user to input any date - will change -->
        <input type="text" id="manual_date" size="12" />
        <button id="manualButton" onclick="manualButton()">
          Add
        </button>
        Lock Selections
        <input type="checkbox" id="lock" checked="checked" onclick="lockCheck()" />
      </div>
      <!-- Map describes the area where the leaflet map will be drawn -->
      <div id="map" style="height: 674px; width: 780px"></div>
      <!-- Keywords describes the area used to draw the tag cloud and content 
           controls -->
      <button id="resetBoxes" onclick="resetBoxes()" style="clear: both;">
          Reset Boxes
      </button>
      <div id="keywords" style="height: 636px; width: 150px;">
        <ul id="tag_list"></ul>
      </div>
      <script type="text/javascript" defer="defer">
        var lock = true;
        var filter = false;
        var filterDate = false;
        var filterTerms = false;
        var filterLocation = true;
        var filterDates = [];
        var filterKeys = [];
        var currentBounds;
        var filterLocations = []
        var locationArray = [];
        var dateData = [
          ["2011-10-11", 190698], ["2011-10-13", 203363], 
          ["2011-10-14", 231756], ["2011-10-15", 384175], 
          ["2011-10-16", 197376], ["2011-10-17", 196317], 
          ["2011-10-18", 188535], ["2011-10-26", 223406], 
          ["2011-11-15", 491890], ["2011-11-30", 206430]
        ];
        var keywordData = {};
        var topKeywords = [
          ["call", 6.268874123831775], ["free", 6.800927278037383], 
          ["occupy", 34.59086594626168], ["protesters", 32.66147050233645], 
          ["wall", 31.462178738317757], ["street", 29.364120911214954], 
          ["people", 29.073306074766354], ["park", 27.58473276869159], 
          ["nypd", 19.357586156542055], ["live", 16.85729410046729], 
          ["movement", 16.680965245327105], ["arrested", 13.853314836448597], 
          ["protest", 12.261755257009346], ["zuccotti", 12.18636828271028], 
          ["support", 11.942830023364486], ["obama", 11.831921728971961], 
          ["news", 11.546473422897197], ["cops", 11.245947721962617], 
          ["nyc", 10.929577978971963], ["world", 10.76909316588785], 
          ["mayor", 10.69140625], ["square", 10.561076226635514], 
          ["time", 10.342581775700936], ["today", 10.17136390186916], 
          ["protests", 10.142997955607475], ["eviction", 10.106198890186915], 
          ["media", 9.84375], ["city", 9.83046144859813], 
          ["back", 9.408805490654204], ["bloomberg", 9.220465829439254], 
          ["day", 8.431585864485982], ["times", 8.376898364485982], 
          ["good", 8.297678154205608], ["party", 8.066917348130842], 
          ["march", 7.928154205607476], ["press", 7.750292056074766], 
          ["make", 7.1617625584112155], ["arrests", 7.161251460280374], 
          ["america", 6.983133761682243], ["tonight", 6.900591413551402], 
          ["night", 6.61284316588785], ["peaceful", 6.579110689252337], 
          ["oakland", 6.510623539719626], ["can't", 6.497590537383177], 
          ["raid", 6.429358936915888], ["crowd", 6.4173481308411215], 
          ["global", 6.396904205607477], ["watch", 6.357805198598131], 
          ["big", 6.346305490654205], ["police", 35.0]
        ];
        var pointArray = [];

        // Create an overall map
        var map = new L.Map('map');
        // Overrides shift-drag behavior
        map.boxZoom._onMouseUp = function(e){
          //do the old things we /wanted/ to do.
          this._pane.removeChild(this._box);
          this._container.style.cursor = '';
          L.DomUtil.enableTextSelection();
          L.DomEvent.removeListener(document, 'mousemove', this._onMouseMove);
          L.DomEvent.removeListener(document, 'mouseup', this._onMouseUp);

          var layerPoint = this._map.mouseEventToLayerPoint(e),
            bounds = new L.LatLngBounds(
              this._map.layerPointToLatLng(this._startLayerPoint),
              this._map.layerPointToLatLng(layerPoint));

          var swPoint = bounds._southWest,
          nePoint = bounds._northEast,
          polygon_bounds = [swPoint,
                            new L.LatLng(swPoint.lat, nePoint.lng),
                            nePoint,
                            new L.LatLng(nePoint.lat, swPoint.lng),
                            swPoint];
          filterLocations.push(nePoint.lat, swPoint.lng, swPoint.lat, nePoint.lng);

          //draw a square in the selection
          newRectangle = new L.Polygon(polygon_bounds, {
            //rectangle_fill, rectangle_color
            fillColor: '#B4D5F1',
            color: '#0075D1',
            opacity: 1,
            weight: 2,
            clickable:false
          });
          map.addLayer(newRectangle);
          locationArray.push(newRectangle);
        };

        // Resets drawn boxes
        function resetBoxes() {
          for (var i = 0; i < locationArray.length; i++) {
            map.removeLayer(locationArray[i]);
          }
          locationArray = [];
        }

        // Set up cloudmade layer
        var cloudmadeUrl = 'http://{s}.tile.cloudmade.com/8f407551a2184211bfac03c7062ba5a8/997/256/{z}/{x}/{y}.png';
        var cloudmadeAttrib = 'Map data &copy; 2011 OpenStreetMap contributors, Imagery &copy; 2011 CloudMade';
        var cloudmade = new L.TileLayer(cloudmadeUrl, {
          maxZoom : 18,
          attribution : cloudmadeAttrib
        });

        // Set the default view on the map
        var default_view = new L.LatLng(38.0, -97.0);
        // geographical point (longitude and latitude)
        map.setView(default_view, 4).addLayer(cloudmade);
        var tiles = new L.TileLayer.Canvas();
        
        // These three lines create the initial setup for the application
        currentBounds = map.getBounds();
        reformTagCloud();
        buildCanvas(dateData, "histogram");

        //----------------------------------------------------------------------
        // Whenever the map is dragged, it retrieves the keywords and dates of 
        // the new bounds.
        //----------------------------------------------------------------------
        map.on('dragend', function(e) {
          currentBounds = map.getBounds();

          if (!lock) {
            redrawTags();
            redrawGraph();
          }
        });

        //----------------------------------------------------------------------
        // Whenever the map is zoomed in, it retrieves the keywords and dates 
        // of the new bounds.
        //----------------------------------------------------------------------
        map.on('zoomend', function() {
          currentBounds = map.getBounds();

          if (!lock) {
            redrawTags();
            redrawGraph();
          }
        });

        //----------------------------------------------------------------------
        // This changes the lock status of the view. If the view goes from 
        // checked to unchecked, it also refreshes the view.
        //----------------------------------------------------------------------
        function lockCheck() {
          var check = document.getElementById("lock");
          console.log("Clicked");

          if (check.checked) {
            lock = true;
          } else {
            lock = false;
            redrawGraph();
            redrawTags();
            resetMap();
          }
        }

        //----------------------------------------------------------------------
        // This method generates a call to the server to retrieve the top dates 
        // of the tweets that are bounded by the current restrictions. It does 
        // this by generating a url that can be parsed on the server side.
        //----------------------------------------------------------------------
        function redrawGraph() {
          var requestUrl = "scripts/data_retriever.php?0=1&";
          var breakpoint = 2;

          if(filterLocation) {
            requestUrl += "1=1&2=" + currentBounds.getNorthWest().lat + "&3=" + 
                          currentBounds.getNorthWest().lng + "&4=" + 
                          currentBounds.getSouthEast().lat + "&5=" + 
                          currentBounds.getSouthEast().lng + "&";
            breakpoint += 4;
            if (filterLocations.length > 0) {
              for (var i = 0; i < filterLocations.length; i++) {
                requestUrl += breakpoint + "=" + filterLocations[i] + "&";
                breakpoint++;
              }
            }

            if(filterTerms) {
              requestUrl += breakpoint + "=1&";
              breakpoint++;

              for( j = 0; j < filterKeys.length; j++) {
                requestUrl += breakpoint + "=" + filterKeys[j] + "&";
                breakpoint++;
              }
              
              requestUrl = requestUrl.substring(0, requestUrl.length - 1);
            } else {
              requestUrl += breakpoint + "=0";
            }
          } else if(filterTerms) {
            requestUrl += "1=0&2=1&";
            breakpoint++;

            for( j = 0; j < filterKeys.length; j++) {
              requestUrl += breakpoint + "=" + filterKeys[j] + "&";
              breakpoint++;
            }
            requestUrl = requestUrl.substring(0, requestUrl.length - 1);
          } else if(!filterLocation && !filterTerms) {
            requestUrl += "1=0&2=0";
          }
          request = new XMLHttpRequest();

          request.open("GET", requestUrl, true);
          request.send(null);

          request.onreadystatechange = function() {
            if(request.readyState == 4) {
              responseText = new String(request.response).split("\n");
              dateData = [];

              for( l = 0; l < responseText.length; l++) {
                dateData[l] = responseText[l].split(",");
              }

              // Get rid of the last element - it is empty
              dateData.pop();

              buildCanvas(dateData, "histogram");
            }
          };
        }

        //----------------------------------------------------------------------
        // Reset map calls data_retriever to get lat lng points to display on 
        // the map based on the filter keywords and filter dates.
        //----------------------------------------------------------------------
        function resetMap() {
          var requestUrl = "scripts/data_retriever.php?0=3&";
          var breakpoint = 2;

          if(filterTerms) {
            requestUrl += "1=1&";

            for( j = 0; j < filterKeys.length; j++) {
              requestUrl += breakpoint + "=" + filterKeys[j] + "&";
              breakpoint++;
            }

            if(filterDate) {
              requestUrl += breakpoint + "=1&";
              breakpoint++;

              for( j = 0; j < filterDates.length; j++) {
                requestUrl += breakpoint + "=" + filterDates[j] + "&";
                breakpoint++;
              }
              requestUrl = requestUrl.substring(0, requestUrl.length - 1);
            } else {
              requestUrl += breakpoint + "=0";
            }
          } else if(filterDate) {
            requestUrl += "1=0&2=1&";
            breakpoint++;

            for( j = 0; j < filterDates.length; j++) {
              requestUrl += breakpoint + "=" + filterDates[j] + "&";
              breakpoint++;
            }
            requestUrl = requestUrl.substring(0, requestUrl.length - 1);
          } else if(!filterDate && !filterTerms) {
            requestUrl += "1=0&2=0";
          }
          dateRequest = new XMLHttpRequest();

          dateRequest.open("GET", requestUrl, true);
          dateRequest.send(null);

          dateRequest.onreadystatechange = function() {
            if(dateRequest.readyState == 4) {
              tempPointData = dateRequest.response.split("\\n");
              tempPointData.pop();
              pointArray = [];

              for( l = 0; l < tempPointData.length; l++) {
                superTempData = tempPointData[l].split(",");
                pointArray.push(new L.LatLng(parseFloat(superTempData[0]), parseFloat(superTempData[1])));
              }

              var canvasTiles = $("canvas.leaflet-tile.leaflet-tile-loaded");

              // Get rid of the old tiles
              map.removeLayer(tiles);
              tiles = new L.TileLayer.Canvas({
                reuseTiles : false
              });

              //----------------------------------------------------------------
              // This is the method that is called whenever a tile is added to 
              // the map. It goes through all of the points in pointArray and 
              // draws them onto the map if it is contained within the tile.
              //----------------------------------------------------------------
              tiles.drawTile = function(canvas, tilePoint, zoom) {
                var tileContext = canvas.getContext('2d');
                tileContext.fillStyle = '#C11B17';
                var pointSize = 2 * Math.pow(map.getZoom(), (1.0 / 3.0));
                if(map.getZoom() < 5) {
                  pointSize = 1;
                }

                var prsed, xOffset, yOffset;
                prsed = canvas.style.cssText;
                prsed = prsed.substring(prsed.indexOf("translate3d(") + 
                        "translate3d(".length, prsed.indexOf(")"));
                prsed = prsed.split("px,");
                xOffset = parseInt(prsed[0], 10);
                yOffset = parseInt(prsed[1], 10);

                for( i = 0; i < pointArray.length; i++) {
                  mapPoint = pointArray[i];
                  mapPoint = map.latLngToLayerPoint(mapPoint);

                  tileContext.beginPath();

                  tileContext.fillRect(mapPoint.x - xOffset, 
                                       mapPoint.y - yOffset, pointSize, 
                                       pointSize);
                  if (mapPoint.x > xOffset && mapPoint.y > yOffset && 
                      mapPoint.x < (xOffset + 256) && 
                      mapPoint.y < (yOffset + 256)) {
                    tileContext.fill();
                  }
                }
              };

              map.addLayer(tiles);
            }
          };
        }

        //----------------------------------------------------------------------
        // Redraw tags creates a request to the server and the return value is 
        // a list of keywords combined with their frequencies. Based on these 
        // values, a new tag cloud is drawn on the right display pane. This 
        // function should be called every time the view changes or a new date 
        // is selected.
        //----------------------------------------------------------------------
        function redrawTags() {
          var requestUrl = "scripts/data_retriever.php?0=2&";
          var breakpoint = 2;

          if(filterLocation) {
            requestUrl += "1=1&2=" + currentBounds.getNorthWest().lat + "&3=" + 
                          currentBounds.getNorthWest().lng + "&4=" + 
                          currentBounds.getSouthEast().lat + "&5=" + 
                          currentBounds.getSouthEast().lng + "&";
            breakpoint += 4;
            if (filterLocations.length > 0) {
              for (var i = 0; i < filterLocations.length; i++) {
                requestUrl += breakpoint + "=" + filterLocations[i] + "&";
                breakpoint++;
              }
            }

            if(filterDate) {
              requestUrl += breakpoint + "=1&";
              breakpoint++;

              for( j = 0; j < filterDates.length; j++) {
                requestUrl += breakpoint + "=" + filterDates[j] + "&";
                breakpoint++;
              }
              requestUrl = requestUrl.substring(0, requestUrl.length - 1);
            } else {
              requestUrl += breakpoint + "=0";
            }
          } else if(filterDate) {
            requestUrl += "1=0&2=1&";
            breakpoint++;

            for( j = 0; j < filterDates.length; j++) {
              requestUrl += breakpoint + "=" + filterDates[j] + "&";
              breakpoint++;
            }
            requestUrl = requestUrl.substring(0, requestUrl.length - 1);
          } else if(!filterDate && !filterLocation) {
            requestUrl += "1=0&2=0";
          }
          tagRequest = new XMLHttpRequest();

          tagRequest.open("GET", requestUrl, true);
          tagRequest.send(null);

          tagRequest.onreadystatechange = function() {
            if(tagRequest.readyState == 4) {
              rawData = new String(tagRequest.response).split("\n");
              topKeywords = [];

              for( l = 0; l < rawData.length; l++) {
                topKeywords.push(rawData[l].split(","));
              }

              topKeywords.pop();

              reformTagCloud();
            }
          };
        }

        //----------------------------------------------------------------------
        // This function accepts the id of a button on the page and will change 
        // its state to being clicked if the button was unclicked and vice 
        // versa. Clicking a button adds that button's date to the list of 
        // buttons to be kept when the visualization is refereshed.
        //----------------------------------------------------------------------
        function dateButton(button_id) {
          // If the visualization is not currently filtering based on date, start.
          if(!filterDate) {
            filterDate = true;
          }

          button = document.getElementById(button_id);
          if($(button).attr("class") == "unclicked_button") {
            $(button).attr("class", "clicked_button");
            filterDates.push(button_id);
          } else if($(button).attr("class") == "clicked_button") {
            $(button).attr("class", "unclicked_button");
            removeByValue(filterDates, button_id);
          }

          if(filterDates.length === 0) {
            filterDate = false;
          }

          if (!lock) {
            resetMap();
            redrawTags();
          }
        }

        //----------------------------------------------------------------------
        // This function allows the user to manually enter dates to add to the 
        // selections. It is currently a very rudimentary method of adding more 
        // precise selections. It will almost certainly be replaced.
        //----------------------------------------------------------------------
        function manualButton() {
          var extraDate = $("#manual_date").val();

          if(!filterDate) {
            filterDate = true;
          }
          for( i = 0; i < filterDates.length; i++) {
            if(extraDate == filterDates[i]) {
              return;
            }
          }
          filterDates.push(extraDate);

          if (!lock) {
            resetMap();
            redrawTags();
          }
        }

        //----------------------------------------------------------------------
        // This function accepts two-dimensional data to draw a histogram on the 
        // provided canvas. It does some preprocessing of the data to 
        // establish some parameters for the low-level drawing of the graph.
        //----------------------------------------------------------------------
        function buildCanvas(data, canvas) {
          var histogramCanvas = document.getElementById(canvas);
          // Make sure the canvas exists
          if(histogramCanvas && histogramCanvas.getContext) {
            // Set up the context of the histogram
            var context = histogramCanvas.getContext("2d");
            context.clearRect(0, 0, canvas.width, canvas.height);

            // Find out the highest occuring date
            var maxVal = 0;
            for(j in data) {
              if(parseInt(data[j][1], 10) > maxVal) {
                maxVal = parseInt(data[j][1], 10);
              }
            }

            // Draw the bar chart
            drawHistogram(context, data, (parseFloat(histogramCanvas.getAttribute("height").substr(0, 3)) - 20) / data.length, 
                          parseInt(histogramCanvas.getAttribute("width").substr(0, 3), 10), 
                          maxVal);
          }
        }

        //----------------------------------------------------------------------
        // This function is the low-level aspect of buildCanvas. It takes a 
        // large number of parameters to create a very specific type of 
        // histogram complete with buttons that allow for adding the dates to 
        // the filter dates.
        //----------------------------------------------------------------------
        function drawHistogram(context, data, barHeight, chartWidth, dataMax) {
          startX = 10;
          context.clearRect(0, 0, chartWidth, barHeight * data.length + 20);
          $("#histogram_buttons").empty();
          mapQuickSort(data, 0, data.length - 1);
          context.lineWidth = "1.0";
          var startY = 10;

          document.getElementById("histogram_buttons").setAttribute("margin-top", startX);
          drawLine(context, startX, startY, chartWidth - 10, startY);
          drawLine(context, startX, startY, startX, barHeight * data.length);
          context.lineWidth = "0.0";

          for( i = 0; i < data.length; i++) {
            var name = data[i][0];
            var barWidth = (data[i][1] / dataMax) * ((chartWidth - startX) - 10);

            context.fillStyle = "#C11B17";
            drawRectangle(context, startX, i * barHeight + startY, barWidth, 
                          Math.ceil(barHeight - startY / data.length), true);
            var divButton = document.createElement("button");
            $(divButton).attr("class", "unclicked_button");
            $(divButton).attr("id", name);
            $(divButton).height(Math.floor(barHeight));
            $(divButton).attr("onclick", "dateButton(\"" + name + "\")");
            $(divButton).css("top", i * barHeight + startY + 'px')
            document.getElementById("histogram_buttons").appendChild(divButton);
            context.fillStyle = "#000000";
            context.fillText(name + " Tweets: " + data[i][1], startX + 10, 
                             i * barHeight + startY + (barHeight / 2));
          }

          // Check if the buttons are already being filtered. If so, make the 
          // button appear to be clicked.
          var buttons = $("#histogram_buttons").children().attr("class", "unclicked_button").each(function() {
            var child = $(this);
            for( i = 0; i < filterDates.length; i++) {
              if(filterDates[i] == child.attr("id")) {
                child.attr("class", "clicked_button");
              }
            }
          });
        }

        //----------------------------------------------------------------------
        // This function determines the behavior of the keywords that are 
        // clicked from the tag cloud. When one is clicked, if the keyword is 
        // already being filtered, it removes the keyword. If it is not being 
        // filtered, it is added to the keywords to be filtered. If this method 
        // causes the list of filter keywords to be empty, the program will 
        // stop filtering based on keyword.
        //----------------------------------------------------------------------
        function keywordClick(keyword) {
          var link = document.getElementById(keyword);

          if($(link).attr("class") == "unclickedKeyword") {
            $(link).attr("class", "clickedKeyword");
            if(!filterTerms) {
              filterTerms = true;
            }
            if(filterKeys.indexOf(keyword) == -1) {
              filterKeys.push(keyword);
            }
          } else if($(link).attr("class") == "clickedKeyword") {
            $(link).attr("class", "unclickedKeyword");
            removeByValue(filterKeys, keyword);
          }

          if(filterKeys.length === 0) {
            filterTerms = false;
          }

          if (!lock) {
            resetMap();
            redrawGraph();
          }
        }

        //----------------------------------------------------------------------
        // This adds a tag cloud to the div specified by #tag_cloud. It bases 
        // the tag cloud on the 2d array keywordData, with keywordData[i][0] 
        // being the value and keywordData[i][1] being the frequency of that 
        // value in the data.
        //----------------------------------------------------------------------
        function formTagCloud() {
          var tempIncrementer = 0;
          var max;
          var min;
          var cloudSize = 75;
          topKeywords = [];

          for(items in keywordData) {
            if(topKeywords.length < cloudSize) {
              topKeywords.push([items, keywordData[items]]);
            }
          }

          mapQuickSortByValue(topKeywords, 0, cloudSize - 1);

          for(items in keywordData) {
            if(tempIncrementer > cloudSize) {
              if(keywordData[items] > topKeywords[0][1]) {
                topKeywords.splice(0, 1);
                quickInsertByValue(topKeywords, [items, keywordData[items]]);
              }
            }
            tempIncrementer++;
          }
          max = topKeywords[cloudSize - 1][1];
          min = topKeywords[0][1];

          mapQuickSort(topKeywords, 0, cloudSize - 1);

          for(items in topKeywords) {
            var tag = document.createElement("li");
            $(tag).html("<a>" + topKeywords[items][0] + "</a>");
            $(tag).attr("id", topKeywords[items][0]);
            $(tag).attr("class", "unclickedKeyword");
            $(tag).css("font-size", 8 + 16 * (topKeywords[items][1] / (max - min)));
            $(tag).attr("onclick", "keywordClick(\"" + topKeywords[items][0] + "\")");
            document.getElementById("tag_list").appendChild(tag);
          }
        }

        //----------------------------------------------------------------------
        // This function accepts two-dimensional data and draws a tag cloud on 
        // the right pane based on it. The sizes of the keywords are dynamically
        // determined based on the relative frequencies of the keywords.
        //----------------------------------------------------------------------
        function reformTagCloud() {
          var tempIncrementer = 0;
          var max = topKeywords[topKeywords.length-1][1];
          var min = topKeywords[0][1];
          var cloudSize = topKeywords.length;

          mapQuickSort(topKeywords, 0, cloudSize - 1);

          $("#tag_list").empty();

          for(items in topKeywords) {
            var tag = document.createElement("li");
            $(tag).html("<a>" + topKeywords[items][0] + "</a>");
            $(tag).attr("id", topKeywords[items][0]);
            $(tag).attr("class", "unclickedKeyword");
            for( i = 0; i < filterKeys.length; i++) {
              if(filterKeys[i] == topKeywords[items][0]) {
                $(tag).attr("class", "clickedKeyword");
              }
            }
            $(tag).css("font-size", 10 + 16 * (topKeywords[items][1] / (max - min)));
            $(tag).attr("onclick", "keywordClick(\"" + topKeywords[items][0] + "\")");
            document.getElementById("tag_list").appendChild(tag);
          }

          for(items in filterKeys) {
            if (!document.getElementById(filterKeys[items])) {
              var tag = document.createElement('li');
              $(tag).html("<a>" + filterKeys[items] + "</a>");
              $(tag).attr("id", filterKeys[items]);
              $(tag).attr("class", "clickedKeyword");
              $(tag).css("font-size", 10);
              $(tag).attr("onclick", "keywordClick(\"" + filterKeys[items] + "\")");
              document.getElementById("tag_list").appendChild(tag);
            }
          }
        }
      </script>
      <footer id="foot">
        <p>
          More Info: <a href="http://dmml.asu.edu">Data Mining and Machine 
          Learning Lab at ASU</a>
        </p>
      </footer>
    </div>
  </body>
</html>
