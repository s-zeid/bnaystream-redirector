BnayStream Redirector
=====================

Stream redirector based on youtube-dl.

Copyright (c) 2016 Scott Zeid.
Released under [the X11 License](https://tldrlegal.com/l/x11).  
<https://stream.bnay.me/>  
<https://code.s.zeid.me/bnaystream-redirector>

- - - -

This program will redirect you to the direct stream URL for a given live stream
in a given [youtube-dl](https://rg3.github.io/youtube-dl/) format.

You can also specify the stream URL and format directly in the request
URL using one of the following syntaxes:

* `https://stream.bnay.me/[{format}/]{url}`
* `https://stream.bnay.me/?url={url}&format={format}`

The direct stream URL is returned both in the response's Location header and
as the response's body.  Not found, gone, bad request, and internal application
errors use the proper HTTP status codes.


Dependencies
------------

### Runtime

* youtube-dl
* Python 3
* PHP 5.6+

### Setup

* GNU make
* NPM


Installation
------------

* Run `make`.  Currently, this just runs `npm install` to pull in client-side
  libraries.

* Configure your server to use `index.php` as the 404 handler for the path from
  which the application is served.

* Serve the application from its own domain or subdomain.

* Run `ytdl-urld`, serving on localhost TCP port 26298 (which it does by
  default).

* (Optional) Add a `favicon.ico` file to the root directory.
