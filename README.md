BnayStream Redirector
=====================

Stream redirector based on youtube-dl.

Copyright (c) 2016 Scott Zeid.
Released under [the X11 License](https://tldrlegal.com/l/x11).  
<https://code.s.zeid.me/bnaystream-redirector>

- - - -

This program will redirect you to the direct stream URL for a given live stream
in a given [youtube-dl](https://rg3.github.io/youtube-dl/) format.

You can also specify the stream URL and format directly in the request
URL using one of the following syntaxes:

* `<path-to-app>/[{format}/]{url}`
* `<path-to-app>/?url={url}&format={format}`

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

* (Optional) Copy `index.conf.dist` to `index.conf` and modify it as desired.

* (Optional) Add a `favicon.ico` file to the root directory.


Configuration
-------------

The following options may be set in `index.conf`, which is parsed as a
PHP INI file:

### `url_prefix`

A value with which request URLs must be prefixed in order to be considered
valid.  If this is set and not empty, then non-prefixed requests will result
in either 404 errors or (for the root if `non_prefixed_root_redirect` is
non-empty) redirects.

Static files like CSS and JavaScript files do not need to be prefixed unless
your server is configured to serve them from the prefix.

### `non_prefixed_root_redirect`

A URL to which non-prefixed requests for the application's root will be
redirected.  If this is unset or empty, then non-prefixed requests for the
root will result in 404 errors.
