<?php

// Copyright (c) 2016 Scott Zeid.
// Released under the X11 License:  <https://tldrlegal.com/l/x11>

$config = [
 "url_prefix" => "",
 "non_prefixed_root_redirect" => "",
];

if (is_file("index.conf"))
 $config = array_merge($config, parse_ini_file("index.conf"));


function stream_url($url, $format = null) {
 // handle the case where no format is given
 if (empty($format))
  $format = "best";
 
 // URL cleanup
 if (strpos($url, "://") === false)
  $url = "http://$url";
 
 // format cleanup
 if (preg_match("/^https?:\/\/([^.]+?\.)?twitch\.tv\//", strtolower($url)) &&
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


function main($request_uri = null, $url_prefix = "", $https = true) {
 if ($request_uri === null)
  $request_uri = $_SERVER["REQUEST_URI"];
 
 if ($request_uri == "/index.php") {
  header("Location: $url_prefix/");
  return;
 }
 
 $input = ltrim($request_uri, "/");
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
  $root = "http".(($https) ? "s" : "")."://{$_SERVER["HTTP_HOST"]}"
          .((!empty($url_prefix)) ? "/".trim($url_prefix, "/") : "")
          ."/".ltrim($request_uri, "/");
  echo str_replace("___ROOT___", rtrim($root, "/"), file_get_contents("index.html"));
 }
}


if (php_sapi_name() == "cli-server") {
 $request = $_SERVER["SCRIPT_FILENAME"];
 if (!preg_match("/\.php$/i", $request) && file_exists($request))
  return false;
}


// prefix handling
$request_uri = $_SERVER["REQUEST_URI"];
$url_prefix = trim($config["url_prefix"], "/");
$prefix_depth = (!empty($url_prefix)) ? count(explode("/", $url_prefix)) : 0;
if (!empty($config["url_prefix"])) {
 $url_prefix = "/$url_prefix";
 $prefix_re = "@^".preg_quote($url_prefix)."([/?])@";
 if ($request_uri == $url_prefix) {
  // make sure the prefix has a trailing slash after it
  header("Location: $request_uri/");
  exit();
 } elseif (preg_match($prefix_re, $request_uri)) {
  // remove prefix from request URI
  $request_uri = preg_replace($prefix_re, "\\1", $request_uri);
 } else {
  // reject requests without prefix
  if ($config["non_prefixed_root_redirect"] &&
      preg_replace("/\?.*$/", "", $request_uri) == "/") {
   header("Location: {$config["non_prefixed_root_redirect"]}");
  } else {
   header("HTTP/1.0 404 Not Found");
   echo "<h1>404 Not Found</h1>";
  }
  exit();
 }
 
 // handle static file requests
 $request_no_query = preg_replace("/\?.*$/", "", $request_uri);
 if (is_file(__DIR__.DIRECTORY_SEPARATOR.$request_no_query)) {
  if ($request_no_query != "/".basename(__FILE__)) {
   header("Location: $request_uri");
   exit();
  }
 }
}

$https = !empty($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] != "off";


main($request_uri, $url_prefix, $https);
exit();

?>
