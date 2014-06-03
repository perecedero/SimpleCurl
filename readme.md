# What is SimpleCurl

Simplecurl is a easy way to do server to server comunication using cURL.


## Requirements

 PHP 5+

## Features

* POST/GET requests over HTTP in a simplified way
* Follows redirects
* Response report
* Cookies
* Custom headers
* Easy way to upload and download files
* Autoparse response

## Usage

Make a GET Request

	<?php
		require_once 'SimpleCurl.php';

		$sc = new SimpleCurl();

		$result = $sc->call(array(
			'url' => 'http://search.twitter.com/search.json?q=twitbin'
		));


Make a POST Request

	<?php
		require_once 'SimpleCurl.php';

		$sc = new SimpleCurl();

		$res = $sc->call(array(
			'url' => 'http://wordpress.org/search/do-search.php',
			'post' => array('search'=> 'Pressbackup')
		));


Response report

	<?php
		var_export ($sc->response);

		array(
			'code' => 200,
			'message' => null,
			'body' => [raw response here],
			'latency' => 0.03,
			'size' => 1024
		)

## Complete list of options

url:
 URL to make the request
 * type string
 * REQUIRED

header:
 List of headers to be send
 * type array
 * default null

user-agent:
 user angent identification
 * type string
 * default 'Perecedero/Misc/SimpleCurl/PHP'

cookie:
 list of cookies to be send
 * type mixed (array|string)
 * default null

post:
 List of arguments to be send via POST
 * type array
 * default null

upload.file.POST
 Path to the file to be send via POST
 * type string
 * default null

upload.file.PUT
 Path to the file to be send via PUT
 * type string
 * default null

save.output.in
 Path to the file to be used to store the output
 Also used to download files
 * type string
 * default null

verify.ssl:
 Verify if ssl certification is valid
 * type boolean
 * default false

timeout:
 Number of seconds to wait after the comunications has been stablished
 * type integer
 * default null

return.header:
 Return response headers on the output
 * type boolean
 * default false

return.body:
 return call response body instead of boolean as function return
 * type boolean
 * default true

return.body.onerror:
 return call response body instead of boolean false if response code is 4xx/5xx
 * type boolean
 * default false

parse.body:
 Parse response. valid with return.body=true
 * values 'auto', 'xml', 'json', 'json.associative', 'raw', false
 * type mixed
 * default 'auto'
