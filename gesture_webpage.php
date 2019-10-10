<html>
<head>
<style>

    .container {  
        display: grid;  
        grid-gap: 0px;  
        grid-template-columns: repeat(auto-fit,  minmax(400px, 1fr));
        grid-template-rows: repeat(auto-fill, minmax(200px, 1fr));  
    }

    .container img {
      /* Just in case there are inline attributes */
      width: 100% !important;
      height: auto !important;
    }
    
    @media only screen and (max-width: 1440px) and (orientation : landscape) {
        .container {  
            grid-template-columns: repeat(auto-fit,  minmax(300px, 1fr));
            grid-template-rows: repeat(auto-fill, minmax(150px, 1fr));  
        }
    }
    
    @media only screen and (max-width: 800px) and (orientation : landscape) {
        .container {  
            grid-template-columns: repeat(auto-fit,  minmax(200px, 1fr));
            grid-template-rows: repeat(auto-fill, minmax(100px, 1fr));  
        }
    }
    
    .brightness {
        background-color: #6bff6b;
        display: inline-block;

    }

	body {
	  margin: 0;
	  padding: 0;
	}
    
    html,body {
    touch-action: none;
    }
    
    #overlay {
        background-color: black;
        width: 100%; 
        height: 100%;
        position: fixed; 
        top: 0; 
        left: 0;
        z-index: 999;
        display:none;
    }
    
    #Playing_Video {
        color: white;
        margin-top: 5vh;
        font-size: 10vh;
        text-align: center;
        overflow: hidden;
        white-space: nowrap;
    }
    
    #overlay img{
      position: fixed;
      top: 50%;
      left: 50%;
      margin-top: -175px;
      margin-left: -175px;
    }

</style>
    <script src="https://code.jquery.com/jquery-3.4.1.min.js" integrity="sha256-CSXorXvZcTkaix6Yvo6HppcZGetbYMGWSFlBw8HfCJo=" crossorigin="anonymous"></script>
</head>

<body bgcolor="black">

<div id="overlay">
    <div id="Playing_Video"></div>
    <img src="loading.gif">
</div>

<div class="container">  
</div>

<script>

var kodi_ip = "your kodi ip"
var kodi_port = "your kodi web port"
//Main video list
var videos  = {}
//Used to highlight pressed items (maybe go away?)
var clicked = null;
//Used to prevent double clicks (adds a 500 ms timer for sending to kodi)
var timeout_handler = null;
//Used for loading screen logic
var loading = false;
var downloaded_playlists = 0;
var yt_playlists;

function htmlEntities(str) {
    return str.replace(/['"]+/g, '');
}

window.oncontextmenu = function(event) {
     event.preventDefault();
     event.stopPropagation();
     return false;
};

window.addEventListener('load', function() {
	var start = {};
	var end = {};
	var tracking = false;
	var thresholdTime = 5000;
	var thresholdDistance = 200;
	
	gestureStart = function(e) {
		e.stopPropagation(); 
        e.preventDefault();
		if (e.touches.length>1) {
			tracking = false;
			return;
		} else {
			tracking = true;
			/* Hack - would normally use e.timeStamp but it's whack in Fx/Android */
			start.t = new Date().getTime();
			start.x = e.targetTouches[0].clientX;
			start.y = e.targetTouches[0].clientY;
            if (clicked != null)
            {
                clicked.style.opacity = "1.0";
            }
            temp = document.elementFromPoint(start.x, start.y);
            if (temp.id != "overlay")
            {
                clicked = document.elementFromPoint(start.x, start.y);
                clicked.style.opacity = "0.5";
            }
		}
		console.dir(start);
	};
    
	gestureMove = function(e) {
		if (tracking) {
			e.preventDefault();
			end.x = e.targetTouches[0].clientX;
			end.y = e.targetTouches[0].clientY;
		}
	}
    
	gestureEnd = function(e) {
		tracking = false;
		var now = new Date().getTime();
		var deltaTime = now - start.t;
		var deltaX = end.x - start.x;
		var deltaY = end.y - start.y;
        var scroll_amount = window.screen.availHeight * 1.2;
		/* work out what the movement was */
		if (deltaTime > thresholdTime) {
			/* gesture too slow */
			return;
		} else {
			if ((deltaX > thresholdDistance)&&(Math.abs(deltaY) < thresholdDistance)) {
				console.log("Scroll right");
                clicked.style.opacity = "1.0";
			} else if ((-deltaX > thresholdDistance)&&(Math.abs(deltaY) < thresholdDistance)) {
				console.log("Scroll left");
                clicked.style.opacity = "1.0";
			} else if ((deltaY > thresholdDistance)&&(Math.abs(deltaX) < thresholdDistance)) {
                console.log("Scroll up: " + scroll_amount);
                $('html, body').animate({scrollTop: $(window).scrollTop() - scroll_amount}, 500);
                clicked.style.opacity = "1.0";
			} else if ((-deltaY > thresholdDistance)&&(Math.abs(deltaX) < thresholdDistance)) {
                console.log("Scroll down: " + scroll_amount);
				$('html, body').animate({scrollTop: $(window).scrollTop() + scroll_amount}, 500);
                clicked.style.opacity = "1.0";
			} else { 
                clicked.onclick();
            }
		}
	}

	document.addEventListener('touchstart', gestureStart, false);
	document.addEventListener('touchmove', gestureMove, false);
	document.addEventListener('touchend', gestureEnd, false);

}, false);

function sendToKodi(youtubeUrl,title) {
    if (!loading) {
        loading = true;
        $("#overlay").show();
        wait = 0;
        clearTimeout(timeout_handler);
 
        check_kodi_idle();
        console.log("Waiting: " + wait);
        timeout_handler = setTimeout(function(){ 
            kodiUrl = "http://" + kodi_ip + ":" + kodi_port + "/?youtube=" + youtubeUrl;
            $.ajax({
              url: kodiUrl,
              error: function(data, textStatus, xhr){
                alert( "Error sending video: " + youtubeUrl + " : " + textStatus );
                console.log("showing screen");
                $("#overlay").hide(); 
                loading = false;
              },
              success: function(data, textStatus, xhr){
                console.log( "Sent video: " + title + " : " + textStatus );
              },
              timeout: 500	
            });
                   
            
        }, wait);
    }
}

//check_kodi_idle -> check_kodi_time -> get_kodi_status -> show screen
//             \_/                \_/                  /
//                                   \________________/

function check_kodi_idle() {
    console.log("check_kodi_idle");
    kodiUrl = "http://" + kodi_ip + ":" + kodi_port + "/?time";
    $.ajax({
          url: kodiUrl,
          error: function(data, textStatus, xhr){
            alert( "Error: " + textStatus );
          },
          success: function(data, textStatus, xhr){
              json_data = JSON.parse(data);
              console.log(data);
            if(json_data.result.time.hours == 0 &&
                json_data.result.time.milliseconds == 0 &&
                json_data.result.time.minutes == 0 &&
                json_data.result.time.seconds == 0) {
                check_kodi_time();
            } else {
                setTimeout(check_kodi_idle,200);
            }
          },
          timeout: 500	
        });
}

function get_kodi_status() {
    console.log("check_kodi_status");
    status_url = "http://" + kodi_ip + ":" + kodi_port + "/?status";
    $.ajax({
          url: status_url,
          error: function(data, textStatus, xhr){
            alert( "Error: " + textStatus );
          },
          success: function(data, textStatus, xhr){
              json_data = JSON.parse(data);
              console.log(data);
              if(json_data.result.item.label == "") {
                  setTimeout(check_kodi_time,200);
                  
              } else {
                  console.log("showing screen");
                  $("#overlay").hide(); 
                  loading = false;
              }   
          },
          timeout: 500	
        });
}

function check_kodi_time() {
    console.log("check_kodi_time");
    kodiUrl = "http://" + kodi_ip + ":" + kodi_port + "/?time";
    
    $.ajax({
          url: kodiUrl,
          error: function(data, textStatus, xhr){
            alert( "Error: " + textStatus );
          },
          success: function(data, textStatus, xhr){
              json_data = JSON.parse(data);
              console.log(data);
              if(json_data.result.time.hours == 0 &&
                  json_data.result.time.milliseconds == 0 &&
                  json_data.result.time.minutes == 0 &&
                  json_data.result.time.seconds == 0) {
                  setTimeout(check_kodi_time,200);
              } else {
                  get_kodi_status();
              }
          },
          timeout: 500	
        });
}



function stopKodi() {
	kodiUrl = "http://" + kodi_ip + ":" + kodi_port + "/?stop";
	
	$.ajax({
	  url: kodiUrl,
	  error: function(data, textStatus, xhr){
	    alert( "error stopping: "+ textStatus );
      },
      success: function(data, textStatus, xhr){
	    console.log( "sent stop: "+ textStatus );
      },
	  timeout: 500	
	});
}

function show_sorted_videos()
{
    var items = Object.keys(videos).map(function(key) {
      return [key, videos[key][0], videos[key][1], videos[key][2]];
    });
    
    // Sort the array based on the second element
    items.sort(function(first, second) {
      return first[2] - second[2];
    });
    console.log(items);
    for (i = 0; i < items.length; i++) { 
        $(".container").append("<div class='brightness'><img src='" + items[i][3] + "' onclick='sendToKodi(\"" + items[i][0] + "\",\""+items[i][1]+"\")'></div>");
    }
}


function getVideos(playlistId,t) {
    //https://developers.google.com/youtube/v3/getting-started
    var APIKey = "your google dev API key",
        baseURL = "https://www.googleapis.com/youtube/v3/";
    var url = baseURL + "playlistItems?part=snippet&maxResults=50&playlistId=" + playlistId + "&key=" + APIKey;
    if (t != undefined) {
        url = url + "&pageToken=" + t
    }
    $.ajax({
        type: 'GET',
        url: url,
        dataType: 'json',
        success: function (data) {
            
            for (i = 0; i < data.items.length; i++) { 
                videoId = data.items[i].snippet.resourceId.videoId;
                title = htmlEntities(data.items[i].snippet.title);
                position = data.items[i].snippet.position;
                try {
                  image = data.items[i].snippet.thumbnails.high.url;
                  videos[videoId] = [title,position,image];
                }
                catch(error) {
                  console.error(error);
                  console.error(data.items[i].snippet);
                }
            }
            
            if (data.nextPageToken != undefined) {
                getVideos(playlistId, data.nextPageToken);
            } else {
                downloaded_playlists++;
                if (downloaded_playlists == yt_playlists.length) {
                    show_sorted_videos();
                }
            }
        }
    });
};

function download_playlists(playlists) {
    
    for (i = 0; i < playlists.length; i++) { 
        console.log("Downloading: " + playlists[i]);
        getVideos(playlists[i]);
    }
}


$(document).ready(function() {
		
	$(".container").append("<img src='stop.png' onclick='stopKodi()'>");
    
    //https://www.youtube.com/playlist?list=PLitSwcTzcIt603Jwfy78fYNHgjewRKJBe
    // in the example url above the playlist id is PLitSwcTzcIt603Jwfy78fYNHgjewRKJBe
    yt_playlists = ["add your playlist id here", "another playlist"];
    
    download_playlists(yt_playlists);
});
</script>
