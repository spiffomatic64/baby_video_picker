<?php

header("Access-Control-Allow-Origin: *");

function kodi_curl($postfields) {
    $kodi_ip = '127.0.0.1';
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'http://anonymous@'.kodi_ip.'/jsonrpc');
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postfields);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip, deflate');

    $headers = array();
    $headers[] = 'Accept: application/json, text/javascript, */*; q=0.01';
    $headers[] = 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/76.0.3809.100 Safari/537.36';
    $headers[] = 'Content-Type: application/json';
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

    $result = curl_exec($ch);
    if (curl_errno($ch)) {
            echo 'Error:' . curl_error($ch);
    }
    curl_close($ch);

    return $result;
}

if (isset($_GET['youtube'])) {

        kodi_curl("{\"jsonrpc\": \"2.0\", \"method\": \"Player.Stop\", \"params\":{\"playerid\":1}, \"id\" : 1}");

        kodi_curl("{\"jsonrpc\": \"2.0\", \"method\": \"Playlist.Clear\", \"params\":{\"playlistid\":1}, \"id\": 1}");
        kodi_curl("{\"jsonrpc\": \"2.0\", \"method\": \"Playlist.Clear\", \"params\":{\"playlistid\":0}, \"id\": 1}");

        kodi_curl("[{\"jsonrpc\": \"2.0\", \"method\": \"Playlist.Add\", \"params\":{\"playlistid\":1, \"item\" :{ \"file\" : \"plugin://plugin.video.youtube/play/?video_id=".$_GET['youtube']."\" }}, \"id\" :1}]");

        kodi_curl("{\"jsonrpc\": \"2.0\", \"method\": \"Player.Open\", \"params\":{\"item\":{\"playlistid\":1, \"position\" : 0}}, \"id\": 1}");

}

if (isset($_GET['stop'])) {
        kodi_curl("{\"jsonrpc\": \"2.0\", \"method\": \"Player.Stop\", \"params\":{\"playerid\":1}, \"id\" : 1}");
        kodi_curl("{\"jsonrpc\": \"2.0\", \"method\": \"Playlist.Clear\", \"params\":{\"playlistid\":1}, \"id\": 1}");
        kodi_curl("{\"jsonrpc\": \"2.0\", \"method\": \"Playlist.Clear\", \"params\":{\"playlistid\":0}, \"id\": 1}");
        kodi_curl("{\"jsonrpc\": \"2.0\", \"method\": \"Player.Stop\", \"params\":{\"playerid\":1}, \"id\" : 1}");
}

if (isset($_GET['status'])) {
        $stuff = kodi_curl("{\"jsonrpc\": \"2.0\", \"method\": \"Player.GetItem\", \"params\":{\"playerid\":1}, \"id\" : 1}");
    // playing
    // {"id":1,"jsonrpc":"2.0","result":{"item":{"label":"Horses for Kids - Horse Song Nursery Rhymes by Blippi","type":"unknown"}}}

    //not playing
    // {"id":1,"jsonrpc":"2.0","result":{"item":{"label":"","type":"unknown"}}}
    //$stuff = kodi_curl("{\"jsonrpc\": \"2.0\", \"method\": \"Addons.GetAddonDetails\", \"params\":{\"addonid\":\"plugin.video.youtube\"}, \"id\" : 1}");
    echo $stuff;
}

if (isset($_GET['time'])) {
        $stuff = kodi_curl("{\"jsonrpc\": \"2.0\", \"method\": \"Player.GetProperties\", \"params\":{\"playerid\":1, \"properties\": [\"position\", \"time\", \"totaltime\"]}, \"id\" : 1}");
    // playing
    // {"id":1,"jsonrpc":"2.0","result":{"item":{"label":"Horses for Kids - Horse Song Nursery Rhymes by Blippi","type":"unknown"}}}

    //not playing
    // {"id":1,"jsonrpc":"2.0","result":{"item":{"label":"","type":"unknown"}}}
    //$stuff = kodi_curl("{\"jsonrpc\": \"2.0\", \"method\": \"Addons.GetAddonDetails\", \"params\":{\"addonid\":\"plugin.video.youtube\"}, \"id\" : 1}");
    echo $stuff;
}

?>
