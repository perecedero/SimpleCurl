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
		require 'src/autoload.php';

		$c = new \Perecedero\SimpleCurl\Caller();

		$result = $c->call(array(
			//Options here
		));

		if ($result->code == 200) {

			$parsed_body = $result->get();
			//do something

		} else {

			//debug
			print_r($result->get('headers.sent') );
			print_r($result->get('headers.rcvd'));
			print_r($result->get('body')); //raw response body

		}

```

===

[Complete call options list](#options-list)

[Response object reference](#response-object-reference)

===

### Examples

Make a GET Request

```PHP
	<?php
		require 'src/autoload.php';

		$c = new \Perecedero\SimpleCurl\Caller(array(
			'url.domain' => 'https://api.twitter.com',
			'parse.body.onerror' => true,
			'parse.body' => 'json',
		));

		$res = $c->call(array(
			'url.path' => '/1.1/search/tweets.json?q=@twitterapi',
		));

		if ($res->code != 200) {
			$errors = $res->get()->errors;
			print_r($errors);
		}
```

Make a POST Request

```PHP
	<?php
		require 'src/autoload.php';

		$c = new \Perecedero\SimpleCurl\Caller(array(
			'url.domain' => 'http://wordpress.org',
			'parse.body.onerror' => true,
			'parse.body' => 'json',
		));

		$res = $c->call(array(
			'url.path' => '/search/do-search.php',
			'post' => array('search'=> 'SimpleCurl')
		));
```

===

### Options list

__url__:
 URL to make the request
 * type string
 * REQUIRED

__url.domain__:
 URL to make the request
 * type string
 * REQUIRED  if not passed option **url**

__url.path__:
 URL to use in combination with url.domain
 * type string

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

__cookie__:
 list of cookies to be send
 * type mixed (array|string)
 * default null

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

__return.body__
 return call response body instead of boolean as function return
 * type boolean
 * default true


__parse.body__:
 Parse response. valid with return.body=true
 * values 'auto', 'xml', 'json', 'json.assoc', 'raw', false
 * type mixed
 * default 'auto'

__parse.body.onerror__:
 Parse response on error (4xx or 5xx HTTP code received). valid with return.body=true
 * type boolean
 * default false

===

###Response Object Reference

__You can obtain all the information about the result with the get method.__

Possible method arguments are:

* 'code' : HTTP status code received
* 'headers.sent' : list of headers sent on the petition
* 'headers.rcvd' => list of headers received as part  of the response
* 'body': Raw response body
* 'parsed.body': Parsed response
* 'latency': time to conclude the petition
* 'size':  raw body size


Note: Without any argument this method will return the parsed body

```PHP

	$c = new \Perecedero\SimpleCurl\Caller(array(
		'url.domain' => 'https://api.twitter.com',
		'parse.body.onerror' => true,
		'parse.body' => 'json',
	));

	$res = $c->call(array(
		'url.path' => '/1.1/search/tweets.json?q=@twitterapi',
	));

	print_r ($res->get('headers.rcvd'));

```
```PHP

	HTTP/1.1 400 Bad Request
	content-length: 62
	content-type: application/json;charset=utf-8
	date: Mon, 09 Mar 2015 17:59:20 UTC
	server: tsa_c
	set-cookie: guest_id=v1%3A142592396054742794; Domain=.twitter.com; Path=/; Expires=Wed, 08-Mar-2017 17:59:20 UTC
	strict-transport-security: max-age=631138519
	x-connection-hash: f6f703b23be46fc71c8d1ddd457e6fbf
	x-response-time: 21

```

You can also use use the __get method to obtain all this values

```PHP

	$c = new \Perecedero\SimpleCurl\Caller(array(
		'url.domain' => 'https://api.twitter.com',
		'parse.body.onerror' => true,
		'parse.body' => 'json',
	));

	$res = $c->call(array(
		'url.path' => '/1.1/search/tweets.json?q=@twitterapi',
	));

	print_r($res->code);
	print_r($res->latency);
	print_r($res->body);

```
