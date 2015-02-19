## What is SimpleCurl

SimpleCurl is the easiest  way to do server to server communication using cURL.


### Requirements

 PHP 5+, cURL enabled

### Features

* Easy to use
* Super configurable
* Autoparse response

### Usage

```PHP
	<?php
		require_once 'SimpleCurl.php';

		$sc = new SimpleCurl();

		$result = $sc->call(array(
			//Options here
		));
```

[Complete options list](#options-list)


### Examples

Make a GET Request

```PHP
	<?php
		require_once 'SimpleCurl.php';

		$sc = new SimpleCurl();

		$result = $sc->call(array(
			'url' => 'http://search.twitter.com/search.json?q=SimpleCurl'
		));
```

Make a POST Request

```PHP
	<?php
		require_once 'SimpleCurl.php';

		$sc = new SimpleCurl();

		$res = $sc->call(array(
			'url' => 'http://wordpress.org/search/do-search.php',
			'post' => array('search'=> 'SimpleCurl')
		));
```

Response report

```PHP
	<?php
		var_export ($sc->response);

		array(
			'code' => 200,
			'body' => [raw response here],
			'latency' => 0.03,
			'size' => 1024
		)
```

### Options list

__url__:
 URL to make the request
 * type string
 * REQUIRED

__user.pwd__:
Login details string for the connection. The format of which is: \[user name\]:\[password\]
 * type String
 * default null

__method__:
A custom request method to use instead of "GET" or "HEAD" when doing a HTTP request
 * type String
 * default null

__header__:
 List of headers to be send
 * type array
 * default null

__user-agent__:
 user agent identification
 * type string
 * default 'Perecedero/Misc/SimpleCurl/PHP'

__cookie__:
 list of cookies to be send
 * type mixed (array|string)
 * default null

===

__proxy__:
 The HTTP proxy to tunnel requests through. format  \[host\]:\[port\]
 * type string
 * default null

__follow.location__:
Follow any Location: header that the server sends as part of a HTTP header in a 3xx response.
 * type boolean
 * default false

__verify.ssl__
 Verify if ssl certification is valid
 * type boolean
 * default false

__timeout__:
 Number of seconds to wait after the communication has been established
 * type integer
 * default null

===

__post__:
 List of arguments to be send via POST
 * type array
 * default null

__upload.file.POST__
 Path to the file to be send via POST
 * type string
 * default null

__upload.file.PUT__
 Path to the file to be send via PUT
 * type string
 * default null

__save.output.in__
 Path to the file to be used to store the output
 Also used to download files
 * type string
 * default null

===

__return.header__
 Return response headers on the output
 * type boolean
 * default false

__return.header.sent__
 Return the headers sent on the call on the response body
 * type boolean
 * default false

__return.body__
 return call response body instead of boolean as function return
 * type boolean
 * default true

__return.body.onerror__
 return call response body instead of boolean false if response code is 4xx/5xx
 * type boolean
 * default false

__parse.body__:
 Parse response. valid with return.body=true
 * values 'auto', 'xml', 'json', 'json.assoc', 'raw', false
 * type mixed
 * default 'auto'
