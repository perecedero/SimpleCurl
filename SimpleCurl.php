<?php

/**
 * SimpleCurl
 *
 * ----
 *
 * Licensed under The MIT License
 * Redistributions of files must retain the above copyright notice.
 *
 * @package		Perecedero
 * @subpackage	misc
 * @license		MIT License
 * @author		Ivan Lansky (@perecedero)
 */

namespace \Perecedero\Misc\SimpleCurl

class SimpleCurl
{

	/**
	 * Strores last call status
	 *
	 * @var array
	 * @access public
	 */
	public $response = null;

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
	 * Store call output
	 *
	 * @var String
	 * @access Private
	 */
	private $buffer = '';


	/**
	 * Clean Previous call status
	 *
	 * @access Private
	 */
	private function cleanLastCallStatus()
	{
		$this->response = array(
			'code' => null,
			'message' => null,
			'body' => null,

			'latency' => null,
			'size' => null
		);

		$this->buffer = '';

		$this->tmp_write_file = null;
		$this->tmp_read_file = null;
		$this->tmp_read_file_size = null;
	}

	/**
	 * Perform a cURL to a given URL
	 *
	 * @param array $args. list of options for the call
	 * @access public
	 */
	public function call($args)
	{
		date_default_timezone_set('UTC');

		$this->cleanLastCallStatus();

		$default_args = array(
			'header' =>array(),
			'user-agent' => 'Perecedero/Misc/SimpleCurl/PHP',
			'verify.ssl' => false,
			'follow.location' => false,
			'return.header' => false,
			'return.header.sent' => false,
			'return.body' => 'auto', //auto, xml, json, json.associative, raw, false
			'return.body.onerror' => false
		);

		$args = array_merge($default_args, $args);

		/*
		 * Start set cURL Options
		*/

		$curl_handle = curl_init();
		curl_setopt($curl_handle, CURLOPT_FRESH_CONNECT, true );

		curl_setopt($curl_handle, CURLOPT_URL, $args['url']);
		curl_setopt($curl_handle, CURLOPT_HTTPHEADER, $args['header']);
		curl_setopt($curl_handle, CURLOPT_USERAGENT, $args['user-agent']);

		if (isset($args['cookie'])) {

			$cookie = $args['cookie'];

			if(is_array($args['cookie'])){
				$cookie = array();
				foreach($args['cookie'] as $key => $value) { $cookie[] = urlencode($key).'='.urlencode($value);}
				$cookie = join ('; ', $cookie);
			}

			curl_setopt($curl_handle, CURLOPT_COOKIE, $cookie);
		}

		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_handle, CURLOPT_HEADER, $args['return.header'] );
		curl_setopt($curl_handle, CURLINFO_HEADER_OUT, $args['return.header.sent'] );
		curl_setopt($curl_handle, CURLOPT_NOBODY, !$args['return.body'] );

		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, $args['verify.ssl']);
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, $args['verify.ssl']);

		curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, $args['follow.location']);

		if (isset($args['user.pwd'])) {
			curl_setopt($curl_handle, CURLOPT_USERPWD, $args['user']);
		}

		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
		if (isset($args['timeout'])) {
			curl_setopt($curl_handle, CURLOPT_TIMEOUT, $args['timeout']);
		}

		if (isset($args['upload.file.POST']) && $args['upload.file.POST']) {
			foreach ($args['upload.file.POST'] as $file){
				$args['post']['upload.file'][] = '@' . $file;
			}
		}

		if (isset($args['upload.file.PUT']) && $args['upload.file.PUT']) {

			curl_setopt($curl_handle, CURLOPT_POST, false);
			curl_setopt($curl_handle, CURLOPT_PUT, true);

			$this->tmp_read_file = @fopen($args['upload.file.PUT'], 'rb');
			$this->tmp_read_file_size = filesize($args['upload.file.PUT']);

			curl_setopt($curl_handle, CURLOPT_INFILE, $this->tmp_read_file );
			curl_setopt($curl_handle, CURLOPT_BUFFERSIZE, 128);
			if ($this->tmp_read_file_size >= 0) {
				curl_setopt( $curl_handle, CURLOPT_INFILESIZE, $this->tmp_read_file_size );
			}
		}

		if (isset($args['post'])) {
			curl_setopt($curl_handle, CURLOPT_POST, true);
			curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $args['post']);
		}

		if (isset($args['method']) && in_array( strtoupper($args['method']), array('OPTIONS', 'GET', 'HEAD', 'POST', 'PUT', 'DELETE', 'TRACE', 'CONNECT'))) {
			curl_setopt($curl_handle, CURLOPT_CUSTOMREQUEST, strtoupper($args['method']));
		}

		if(isset($args['proxy']) && $args['proxy']){
			//proxy = host:port
			curl_setopt ($curl_handle, CURLOPT_PROXY, $args['proxy']);
		}

		if (isset($args['save.output.in']) && $args['save.output.in']) {
			$this->tmp_write_file =  fopen($args['save.output.in'], 'wb');
		}

		curl_setopt($curl_handle, CURLOPT_READFUNCTION, array($this, '__responseReadCallback'));
		curl_setopt($curl_handle, CURLOPT_WRITEFUNCTION, array($this, '__responseWriteCallback'));
		curl_setopt($curl_handle, CURLOPT_HEADERFUNCTION, array($this, '__responseHeaderCallback'));

		/*
		 * Execute cURL
		*/

		if (!curl_exec($curl_handle)){

			//set error response status
			$this->response = array (
				'code' => curl_errno($curl_handle),
				'message' => curl_error($curl_handle)
			);

			//close resources
			curl_close($curl_handle);

			if ($this->tmp_write_file != null){
				@fclose($this->tmp_write_file); @unlink($args['save.output.in']);
			}

			if ($this->tmp_read_file != null){
				@fclose($this->tmp_read_file);
			}

			//exit
			return false;
		}

		//set response status
		$this->response = array (
			'code' => curl_getinfo($curl_handle, CURLINFO_HTTP_CODE),
			'body' => $this->buffer,
			'latency' => curl_getinfo($curl_handle, CURLINFO_TOTAL_TIME),
			'size' => strlen($this->buffer)
		);

		if ($args['return.header.sent']) {
			$this->response['header.sent'] = curl_getinfo($curl_handle, CURLINFO_HEADER_OUT);
		}

		//close resources
		curl_close($curl_handle);

		if ($this->tmp_write_file != null){
			@fclose($this->tmp_write_file);
		}

		if ($this->tmp_read_file != null){
			@fclose($this->tmp_read_file);
		}

		//check errors from server
		if ($this->response['code'] >= 400) {

			//clean resources
			if(isset($args['save.output.in'])) {
				@unlink($args['save.output.in']);
			}

			if(!$args['return.body.onerror']){
				return false;
			}
		}

		if ($this->tmp_write_file == null && $args['return.body'] ){
			return $this->parseResponse($args['return.body']);
		} else {
			return true;
		}
	}

	/**
	 * Custom read function
	 *
	 * @access Private
	 */
	private function __responseReadCallback(&$curl, $fp, $len)
	{
		if ( $this->tmp_read_file == null || feof( $this->tmp_read_file )) {
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
		if ( $this->tmp_write_file != null) {
			return fwrite($this->tmp_write_file, $data);
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
		return strlen($data);
	}

	/**
	 * Parse cURL response
	 *
	 * @access Private
	 */
	public function parseResponse ($type = null)
	{

		if (in_array($type, array('auto', 'xml')) && $xml = @simplexml_load_string($this->buffer) ) {

			$response = array();
			$this->parse_xml_node($response, $xml, $first_time =true);
			return $response;

		} elseif (in_array($type, array('auto', 'json')) && $json =  @json_decode($this->buffer, false) ) {

			return $json;

		} elseif (in_array($type, array('auto', 'json.assoc')) && $json =  @json_decode($this->buffer, true) ) {

			return $json;

		} elseif (in_array($type, array('auto', 'raw'))){

			return $this->buffer;
		}
	}

	/**
	 * Read XML recursibly and parse it
	 *
	 * @access Private
	 */
	private function parse_xml_node (&$array, $node, $first_time=false)
	{
		if(!$first_time) { $i=0; }
		foreach($node->children() as $name => $xmlchild) {
			if(count($xmlchild) == 0) {
				$array[$name]= (string)$node->$name;
				continue;
			}
			if (isset($i)) {
					$this->parse_xml_node ($array[$i][$name], $xmlchild);
					$i++;
			} else {
				$this->parse_xml_node ($array[$name], $xmlchild);
			}
		}
	}
}

