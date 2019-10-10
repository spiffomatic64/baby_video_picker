<html>
<head>
<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=0">
<style>

    .container {  
        display: grid;  
        grid-gap: 0px;  
        grid-template-columns: repeat(auto-fit,  minmax(200px, 1fr));
        grid-template-rows: repeat(auto-fill, minmax(100px, 1fr));  
    }
    
     @media only screen and (-webkit-max-device-pixel-ratio: 2.0) {
        .container {  
            grid-template-columns: repeat(auto-fit,  minmax(300px, 1fr));
            grid-template-rows: repeat(auto-fill, minmax(150px, 1fr));  
        }
    }

    .container img {
      /* Just in case there are inline attributes */
      width: 100% !important;
      height: auto !important;
    }
    
   
    
    .brightness {
        background-color: #6bff6b;
        display: inline-block;

    }

	body {
	  margin: 0;
	  padding: 0;
      overscroll-behavior-y: contain;
	}
    
    html,body {
    touch-action: manipulation;
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
    <div class="wrapper">
        <div id="overlay">
            <div id="Playing_Video"></div>
            <img src="loading.gif">
        </div>

        <div class="container">  
        </div>

        <script>
            var kodi_ip = "your kodi ip"
            var kodi_port = "your kodi web port"
            //alert("screen.width: "+ screen.width);
            //alert("window.devicePixelRatio: "+ window.devicePixelRatio);
        
            var hash = location.hash.substr(1) ;

            //Main video list
            var videos  = {}
            //Used to highlight pressed items (maybe go away?)
            var clicked = false;
            //Used to prevent double clicks (adds a 500 ms timer for sending to kodi)
            var timeout_handler = null;
            //Used for loading screen logic
            var loading = false;
            var downloaded_playlists = 0;
            var yt_playlists;
            var fading_element = null;
            var fullscreen = false;
        
            /* Get the documentElement (<html>) to display the page in fullscreen */
            var elem = document.documentElement;
            
            
            function toggleFullscreen() {
                if (!fullscreen) {
                    openFullscreen();
                } else {
                    closeFullscreen();
                }
            }

            /* View in fullscreen */
            function openFullscreen() {
              fullscreen = true;
              if (elem.requestFullscreen) {
                elem.requestFullscreen();
              } else if (elem.mozRequestFullScreen) { /* Firefox */
                elem.mozRequestFullScreen();
              } else if (elem.webkitRequestFullscreen) { /* Chrome, Safari and Opera */
                elem.webkitRequestFullscreen();
              } else if (elem.msRequestFullscreen) { /* IE/Edge */
                elem.msRequestFullscreen();
              }
            }
            
            /* Close fullscreen */
            function closeFullscreen() {
              fullscreen = false;
              if (document.exitFullscreen) {
                document.exitFullscreen();
              } else if (document.mozCancelFullScreen) { /* Firefox */
                document.mozCancelFullScreen();
              } else if (document.webkitExitFullscreen) { /* Chrome, Safari and Opera */
                document.webkitExitFullscreen();
              } else if (document.msExitFullscreen) { /* IE/Edge */
                document.msExitFullscreen();
              }
            }
            

            

            function htmlEntities(str) {
                return str.replace(/['"]+/g, '');
            }

            window.oncontextmenu = function(event) {
                 event.preventDefault();
                 event.stopPropagation();
                 return false;
            };  
            
            function showScreen(error=null) {
                
                if (error != null) {
                    alert("Error: " + error);
                }
                
                console.log("showing screen");
                fading_element.css({ opacity: 1.0 });
                fading_element.show();
                $("#overlay").hide(); 
                loading = false;
                clicked = false;
            }

            function click_handler(youtubeUrl,title) {
                wait = 0;
                
                if(hash == "debug") {
                    wait = 1000;
                }
                
                if (!loading) {
                    if(clicked) {
                        clicked = false;
                        fading_element.stop(true,false);
                        fading_element.css({ opacity: 1.0 });
                    } else {
                        clicked = true;
                        fading_element = $("#"+youtubeUrl);
                        fading_element.fadeOut( 500, function() {
                            loading = true;
                            $("#overlay").show();

                            clearTimeout(timeout_handler);
                            if(hash != "debug") {
                                check_kodi_idle();
                            }
                            console.log("Waiting: " + wait);
                            timeout_handler = setTimeout(function(){
                                if(hash == "debug") {
                                    showScreen();
                                } else {
                                    sendToKodi(youtubeUrl,title);
                                }
                            }, wait);
                        });
                    }
                }
            }


            function sendToKodi(youtubeUrl,title) {
                kodiUrl = "http://" + kodi_ip + ":" + kodi_port + "/?youtube=" + youtubeUrl;
                $.ajax({
                  url: kodiUrl,
                  error: function(data, textStatus, xhr){
                    showScreen("Error sending video: " + youtubeUrl + " : " + textStatus );
                  },
                  success: function(data, textStatus, xhr){
                    console.log( "Sent video: " + title + " : " + textStatus );
                  },
                  timeout: 500	
                });
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
                              showScreen();
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
                    $(".container").append("<div class='brightness'><img id='" + 
                        items[i][0] + "' src='" + 
                        items[i][3] + "' onclick='click_handler(\"" + 
                        items[i][0] + "\",\"" + 
                        items[i][1] + "\")'></div>");
                }
            }

            function getVideos(playlistId,t) {
                #https://developers.google.com/youtube/v3/getting-started
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
                $(".container").append("<img src='full.png' onclick='toggleFullscreen()'>");
                
                #https://www.youtube.com/playlist?list=PLitSwcTzcIt603Jwfy78fYNHgjewRKJBe
                # in the example url above the playlist id is PLitSwcTzcIt603Jwfy78fYNHgjewRKJBe
                yt_playlists = ["add your playlist id here", "another playlist"];
                
                download_playlists(yt_playlists);
            });
            
        </script>
    </div>
</body>
</html>
