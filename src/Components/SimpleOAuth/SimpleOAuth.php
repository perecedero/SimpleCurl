<?php

/**
 * SimpleOAuth
 *
 * ----
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @package		Perecedero
 * @subpackage	SimpleOAuth
 * @license		MIT
 * @author		Ivan Lansky (@perecedero)
 */

//namespace Perecedero\SimpleOAuth;

class SimpleOAuth
{
	/**
	 * Stores all the credentials types: client, request, token
	 *
	 * @var array
	 * @access private
	 */
	public $credentials = [
		'client' => ['key'=>null, 'secret' => null],
		'request' => ['key'=>null, 'secret' => null],
		'token' => ['key'=>null, 'secret' => null],
	];

	/**
	 * Store the request and oauth parameters
	 *
	 * @var array
	 * @access public
	 */
	public $parameters = [];

	/**
	 * Stores request attributes like Method and URL
	 *
	 * @var array
	 * @access private
	 */
	public $request = [];


	public $signatureWithPost = true;

	/**
	 * Construct the object  setting up client credentials
	 *
	 * @param string $consumerKey.
	 * @param string $sharedSecret.
	 * @access public
	 */
	public function __construct($args = array())
	{
		$default_args = array('consumerKey' => null, 'sharedSecret' => null);
		$args = array_merge($default_args, $args);

		$this->credentials('client', $args['consumerKey'], $args['sharedSecret']);

		if (isset($args['xxx'])) {
			$this->signatureWithPost = $args['xxx'];
		}
	}

	/**
	 * set the credentials for $type
	 *
	 * @param string $type. credentials type can be: client, request, token
	 * @param string $key.
	 * @param string $secret.
	 * @access public
	 */
	public function credentials($type = 'client',  $key = null, $secret = null)
	{
		$this->credentials[$type] = [
			'key' => $key,
			'secret' => $secret
		];
	}

	/**
	 * wrapper for token credentials
	 *
	 * @param string $oauthToken.
	 * @param string $oauthTokenSecret.
	 * @access public
	 */
	public function tokens($oauthToken = null, $oauthTokenSecret = null)
	{
		$this->credentials('token', $oauthToken, $oauthTokenSecret);
	}

	/**
	 * Set a new parameter value for the auth header
	 *
	 * @param string $key.  name of the param
	 * @param string $value. value for the param
	 * @param string $type. specify if the value is for request parameter or a oauth parameter
	 * @access public
	 */
	public function param($key, $value, $type = 'request')
	{
		if ($type == 'request') {
			$this->parameters['request'][urldecode($key)] = urldecode($value);
		} else {
			$this->parameters['oauth'][$key] = $value;
		}
	}


	/**
	 * Prepare the object to generate a new auth header
	 * if token credentials are present they will be used too
	 *
	 * @access public
	 */
	public function init($method = null, $url = null)
	{
		$default_url = array('scheme'=>'http', 'host'=>null, 'port'=>null, 'path'=>null, 'query'=>null);
		$url =  array_merge($default_url, parse_url($url));

		$this->request = array(
			'method' => $method,
			'url' => $url
		);

		//load default values
		$this->parameters = array(
			'request' => $this->url('query', 'array'),
			'oauth' => array(
				'oauth_nonce' => $this->nonce(),
				'oauth_signature_method' => 'HMAC-SHA1',
				'oauth_timestamp' => $this->timestamp(),
				'oauth_consumer_key' => $this->credentials['client']['key'],
				'oauth_version' => '1.0'
			)
		);

		//add oauth token if present
		if ($this->credentials['token']['key']) {
			$this->parameters['oauth']['oauth_token'] = $this->credentials['token']['key'];
		}
	}


	/**
	 * Calculate oAuth signature
	 *
	 * @access public
	 */
	public function signature()
	{
		// read RFC 5849 (oAuth 1.0)
		// http://tools.ietf.org/html/rfc5849#section-3.4.1

		//build sorted list of request and protocol parameters
		$params = array_merge($this->parameters['oauth'],  $this->parameters['request']);
		ksort($params);
		$sorted_params_list = http_build_query ($params, '', '&', PHP_QUERY_RFC3986);

		//build base string
		$base_string = strtoupper($this->request['method']) . '&' . urlencode( $this->url('baseURI') ). '&' . urlencode( $sorted_params_list);

		//build signature
		$key = $this->credentials['client']['secret'] . "&" . $this->credentials['token']['secret'];
		return  base64_encode(hash_hmac('sha1', $base_string, $key, TRUE));
	}

	/**
	 * Return the Authorization header value
	 *
	 * @access public
	 */
	public function makeHeader()
	{
		//calculate signature and set it to oauth values
		$oauth_signature = $this->signature();
		$this->param('oauth_signature', $oauth_signature, 'oauth');

		//make  string
		$values = http_build_query($this->parameters['oauth'], '', ', ');
		$values = str_replace(array('=', ', '),  array('="','", '), $values) . '"';
		return  'OAuth ' .  $values;
	}

	private function url($component, $format = 'string')
	{
		if ($component == 'query' && $format == 'array') {
			parse_str($this->request['url']['query'], $res);
			return $res;
		} else if ($component == 'baseURI') {
			$url = $this->request['url'];
			$authority = $url['host'] . ( ($url['port']) ? ':' . $url['port'] : '' );
			return strtolower($url['scheme']) . '://' . $authority . $url['path'];
		}
	}

	private function timestamp()
	{
		return strtotime("now");
	}

	private function nonce()
	{
		return $this->timestamp() + mt_rand() / mt_getrandmax() ;
	}

	public function run($settings)
	{
		$this->init($settings['method'], $settings['url']);


		if ($this->signatureWithPost && isset($settings['post'])) {

			if (is_string($settings['post'])) {
				parse_str($settings['post'], $post);
			} else {
				$post = $settings['post'];
			}

			foreach ($post as $k => $v) {
				$this->param($k, $v, 'request');
			}
		}

		$settings['header'][] = 'Authorization: ' . $this->makeHeader();

		return $settings;
	}

}

