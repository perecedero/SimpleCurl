<?php

/**
 * Caller
 *
 * ----
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @package		Perecedero
 * @subpackage	SimpleCurl
 * @license		MIT License
 * @author		Ivan Lansky (@perecedero)
 */
namespace Perecedero\SimpleCurl;

use  \Perecedero\SimpleCurl\Response as Response;

class Caller
{

	/**
	 * Default settings for the call
	 *
	 * @var array
	 * @access public
	 */
	public $settings = array(
		'url' => null,
		'header' => array(),
		'return.body' => true,
		'parse.body' => 'auto', //auto, xml, json, json.assoc, raw, false
		'parse.body.onerror' => false
	);

	/**
	 *  components container
	 *
	 * @var array
	 * @access public
	 */
	public $components = array();

	/**
	 * File pontiter to write call output
	 *
	 * @var file pointer
	 * @access Private
	 */
	private $tmp_write_file = null;

	/**
	 * File pontiter to read from in file PUT upload
	 *
	 * @var file pointer
	 * @access Private
	 */
	private $tmp_read_file = null;

	/**
	 * size of the file open on $tmp_read_file
	 *
	 * @var file pointer
	 * @access Private
	 */
	private $tmp_read_file_size = null;

	/**
	 * Stores last call response body
	 *
	 * @var String
	 * @access Private
	 */
	private $buffer = '';

	/**
	 * Stores last call response headers
	 *
	 * @var String
	 * @access Private
	 */
	private $headersReceived = '';


	public function __construct($settings = array())
	{
		if ($settings && is_array($settings)) {
			$this->settings = array_merge($this->settings, $settings);
		}
	}

	/**
	 * Clean Previous call status
	 *
	 * @access Private
	 */
	private function cleanLastCallStatus()
	{
		$this->headersReceived = '';
		$this->buffer = '';
		$this->tmp_write_file = null;
		$this->tmp_read_file = null;
		$this->tmp_read_file_size = null;
	}

	/**
	 * Perform a cURL to a given URL
	 *
	 * @param array $settings. list of options for the call
	 * @access public
	 */
	public function call($settings = array())
	{
		$this->cleanLastCallStatus();

		if ($settings) {
			$this->settings = array_merge($this->settings, $settings);
		}

		//build url
		if (!isset($settings['url'])) {
			$this->settings['url'] = $this->builUrl();
		}

		//obtain method
		if (!isset($settings['method'])) {
			if (isset($settings['post']) || isset($settings['upload.file'])) {
				$this->settings['method'] = 'POST';
			} else {
				$this->settings['method'] = 'GET';
			}
		}

		// invoco run components
		$componentToRun= (isset($settings['use']))? $settings['use'] : array_keys($this->components);
		foreach ($componentToRun as $comp){
		   $this->settings= $this->$comp->run($this->settings);
		}

		/*
		 * Start set cURL Options
		*/

		$ch = curl_init();
		curl_setopt($ch, CURLOPT_FRESH_CONNECT, true );

		curl_setopt($ch, CURLOPT_URL, $this->settings['url']);
		curl_setopt($ch, CURLOPT_HTTPHEADER, $this->settings['header']);
		curl_setopt($ch, CURLOPT_USERAGENT,'Perecedero/SimpleCurl 1.0');

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLINFO_HEADER_OUT, true);
		curl_setopt($ch, CURLOPT_NOBODY, !$this->settings['return.body'] );

		if (isset($this->settings['proxy']) && $this->settings['proxy']) {
			curl_setopt ($ch, CURLOPT_PROXY, $this->settings['proxy']);
		}

		if (isset($this->settings['verify.ssl'])) {
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->settings['verify.ssl']);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->settings['verify.ssl']);
		}

		if (isset($this->settings['follow.location'])) {
			curl_setopt($ch, CURLOPT_FOLLOWLOCATION, $this->settings['follow.location']);
		}

		if (isset($this->settings['user.pwd'])) {
			curl_setopt($ch, CURLOPT_USERPWD, $this->settings['user.pwd']);
		}

		curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
		if (isset($this->settings['timeout'])) {
			curl_setopt($ch, CURLOPT_TIMEOUT, $this->settings['timeout']);
		}

		if (isset($this->settings['cookie'])) {
			curl_setopt($ch, CURLOPT_COOKIE, $this->builCookie());
		}

		if (isset($this->settings['method']) && in_array(
			strtoupper($this->settings['method']),
			array('OPTIONS', 'GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'TRACE', 'CONNECT')
		)) {
			$this->settings['method'] = strtoupper($this->settings['method']);
			curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->settings['method']);
		}

		if (isset($this->settings['upload.file'])) {

			$files = (array)$this->settings['upload.file'];

			if (isset($this->settings['method']) && $this->settings['method'] ==  'PUT') {
				curl_setopt($ch, CURLOPT_POST, false);
				curl_setopt($ch, CURLOPT_PUT, true);

				$this->tmp_read_file = fopen($files[0], 'rb');
				$this->tmp_read_file_size = filesize($files[0]);

				curl_setopt($ch, CURLOPT_INFILE, $this->tmp_read_file);
				curl_setopt($ch, CURLOPT_BUFFERSIZE, 128);
				if ($this->tmp_read_file_size >= 0) {
					curl_setopt( $ch, CURLOPT_INFILESIZE, $this->tmp_read_file_size);
				}

			} else {
				foreach ($files as $file) {
					$this->settings['post'][basename($file)] = '@' . $file;
				}
			}
		}

		if (isset($this->settings['post'])) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $this->settings['post']);
		}

		if (isset($this->settings['save.on']) && $this->settings['save.on']) {
			$this->tmp_write_file =  fopen($this->settings['save.on'], 'wb');
		}

		curl_setopt($ch, CURLOPT_READFUNCTION, array($this, '__responseReadCallback'));
		curl_setopt($ch, CURLOPT_WRITEFUNCTION, array($this, '__responseWriteCallback'));
		curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this, '__responseHeaderCallback'));

		/*
		 * Execute cURL
		*/

		if (!curl_exec($ch)) {

			//set error response status
			$response = new \Perecedero\SimpleCurl\Response($this->settings, array(
				'code' => curl_errno($ch),
				'message' => curl_error($ch),
			));

			//close resources
			curl_close($ch);

			if ($this->tmp_write_file != null) {
				@fclose($this->tmp_write_file);
				@unlink($this->settings['save.on']);
			}

			if ($this->tmp_read_file != null) {
				@fclose($this->tmp_read_file);
			}

			//exit
			return $response;
		}

		//set response status
		$response = new \Perecedero\SimpleCurl\Response($this->settings, array(
			'code' => curl_getinfo($ch, CURLINFO_HTTP_CODE),
			'headers.sent' => curl_getinfo($ch, CURLINFO_HEADER_OUT),
			'headers.rcvd' => $this->headersReceived,
			'body' => $this->buffer,
			'latency' => curl_getinfo($ch, CURLINFO_TOTAL_TIME),
			'size' => strlen($this->buffer)
		));

		//close resources
		curl_close($ch);

		if ($this->tmp_write_file != null) {
			@fclose($this->tmp_write_file);
		}

		if ($this->tmp_read_file != null) {
			@fclose($this->tmp_read_file);
		}

		$this->buffer = $this->headersReceived = '';

		return $response;
	}

	/**
	 * Custom read function
	 *
	 * @access Private
	 */
	private function __responseReadCallback(&$curl, $fp, $len)
	{
		if ($this->tmp_read_file == null || feof( $this->tmp_read_file )) {
			return '';
		}

		return fread($this->tmp_read_file, $len);
	}

	/**
	 * Custom write function
	 *
	 * @access Private
	 */
	private function __responseWriteCallback(&$curl, &$data)
	{
		if ($this->tmp_write_file != null) {
			fwrite($this->tmp_write_file, $data);
		}

		$this->buffer .= $data;
		return strlen($data);
	}

	/**
	 * Custom write headers function
	 *
	 * @access Private
	 */
	private function __responseHeaderCallback(&$curl, &$data)
	{
		$this->headersReceived .= $data;
		return strlen($data);
	}

	private function builUrl()
	{
		if (isset($this->settings['url'])) {
			return $this->settings['url'];
		} else {
			if (!isset($this->settings['url.domain'])) {
				$this->settings['url.domain'] = '';
			}
			if (!isset($this->settings['url.path'])) {
				$this->settings['url.path'] = '';
			}
			return $this->settings['url.domain'] . $this->settings['url.path'];
		}
	}

	private function builCookie()
	{
		if (is_array($this->settings['cookie'])) {
			$cookie = http_build_query($this->settings['cookie'], '', '; ');
		} else {
			$cookie = $this->settings['cookie'];
		}
	}

	public function loadComponent( $name , $options=array() )
	{
		require_once 'Components/'.$name.'/autoload.php';
		if(!isset($this->components[$name])){
			$key= (isset($options['name']))? $options['name'] : $name;
			if(isset($options[0])) {
				$this->components[$key]= new $name($options[0]);
			} else {
				$this->components[$key]= new $name();
			}
		}
	}

	public function __get($name)
	{
		if(isset($this->components[$name])){
			return $this->components[$name];
		}else{
			return null;
		}
	}
}
