<?php

// Copyright (c) 2016 Scott Zeid.
// Released under the X11 License:  <https://tldrlegal.com/l/x11>

function stream_url($url, $format = null) {
 // handle the case where no format is given
 if (empty($format))
  $format = "best";
 
 // URL cleanup
 if (strpos($url, "://") === false)
  $url = "http://$url";
 
 // format cleanup
 if (preg_match("/^https?:\/\/([^.]+?\.)?twitch\.tv/", strtolower($url)) &&
     $format != "best" && $format != "worst")
  $format = ucfirst(strtolower($format));
 
 // get stream URL
 $success = false;
 $sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
 if (socket_connect($sock, "127.0.0.1", 26298)) {
  // get URL via ytdl-urld
  $stream_url = "";
  if (socket_write($sock, "$format\n$url")) {
   socket_shutdown($sock, 1);
   while ($tmp = socket_read($sock, 4096))
    $stream_url .= $tmp;
  }
  socket_shutdown($sock, 2);
  socket_close($sock);
  if (strlen($stream_url) && strpos($stream_url, "error://") !== 0)
   $success = true;
 } else {
  // get URL via the youtube-dl command directly
  $r = null;
  ob_start();
  passthru("youtube-dl --quiet --playlist-end=1 --max-downloads=1".
           " -f ".escapeshellarg($format)." -g ".escapeshellarg($url), $r);
  $stream_url = rtrim(ob_get_contents(), "\r\n");
  ob_end_clean();
  $success = !$r;
  if (!$success)
   $stream_url = "";
 }
 
 # for Twitch URLs without `/profile`, exclude VODs
 if (preg_match("/^https?:\/\/([^.]+?\.)?twitch\.tv\/[^\/]+\/?$/", strtolower($url)) &&
     preg_match("/^https?:\/\/vod/", strtolower($stream_url))) {
  $stream_url = "error://not-found";
  $success = false;
 }
 
 return $stream_url;
}


function redirect($url, $format = null) {
 $stream_url = stream_url($url, $format);
 $success = (strlen($stream_url) && strpos($stream_url, "error://") !== 0);
 
 if ($success) {
  header("Location: $stream_url");
  echo "$stream_url\n";
 } else {
  $error = str_replace("error://", "", strtolower($stream_url));
  if ($error == "not-found")
   $code = "404 Not Found";
  elseif ($error == "gone")
   $code = "410 Gone";
  elseif ($error == "bad-request")
   $code = "400 Bad Request";
  else
   $code = "500 Internal Server Error";
  header("HTTP/1.0 $code");
  echo "<h1>$code</h1>";
 }
}


function main() {
 if ($_SERVER["REQUEST_URI"] == "/index.php") {
  header("Location: /");
  return;
 }
 
 $input = ltrim($_SERVER["REQUEST_URI"], "/");
 if (strpos($input, "?") !== 0 && !empty(trim($input, "/"))) {
  // get URL and format
  list($format, $url) = explode('/', $input, 2);
  // handle the case where no format is given
  if ((strpos($format, ":") === strlen($format) - 1 && strpos($url, "/") === 0) ||
      (strpos($format, ".") !== false)) {
   $url = "$format/$url";
   $format = null;
  }
  redirect($url, $format);
 } elseif (isset($_GET["url"])) {
  redirect($_GET["url"], (isset($_GET["format"])) ? $_GET["format"] : null);
 } else {
  header("Content-Type: text/html; charset=utf-8");
  readfile("index.html");
 }
}


if (php_sapi_name() == "cli-server") {
 $request = $_SERVER["SCRIPT_FILENAME"];
 if (!preg_match("/\.php$/i", $request) && file_exists($request))
  return false;
}


main();
exit();

?>
