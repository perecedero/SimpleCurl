<?php

/**
 * SimpleCurl 
 * 
 * ----
 * 
 * Licensed under The GPL v3 License
 * Redistributions of files must retain the above copyright notice.
 *
 * @package		Perecedero
 * @subpackage	misc
 * @license		GPL v3 License
 * @author		Ivan Lansky (@perecedero)
 */


class SimpleCurl
{

	/**
	 * Strores the status of las call
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

		/*
		 * Init 
		*/

		$this->cleanLastCallStatus();

		$default_args = array(
			'header' =>array(),
			'user-agent' => 'Perecedero/Misc/SimpleCurl/PHP',
			'verify.ssl' => false,
			'return.header' => false,
			'return.body' => true,
			'parse.response' => 'auto' //auto, xml, json, raw, false
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
				foreach($args['cookie'] as $key => $value) { $cookie[] = $key.'='.$value;}
				$cookie = join (';', $cookie);
			}

			curl_setopt($curl_handle, CURLOPT_COOKIE, $cookie);
		}

		curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl_handle, CURLOPT_HEADER, $args['return.header'] );
		curl_setopt($curl_handle, CURLOPT_NOBODY, !$args['return.body'] );

		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYHOST, $args['verify.ssl']);
		curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, $args['verify.ssl']);

		curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, true);

		curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 5);
		if (isset($args['timeout'])) {
			curl_setopt($curl_handle, CURLOPT_TIMEOUT, $args['timeout']);
		}

		if (isset($args['upload.file.POST'])) {
			foreach ($args['upload.file.POST'] as $file){
				$args['post']['upload.file'][] = '@' . $file;
			}
		}

		if (isset($args['post'])) {
			curl_setopt($curl_handle, CURLOPT_POST, true);
			curl_setopt($curl_handle, CURLOPT_POSTFIELDS, $args['post']);
		}

		if (isset($args['upload.file.PUT'])) {

			curl_setopt($curl_handle, CURLOPT_POST, false);
			curl_setopt($curl_handle, CURLOPT_PUT, true);

			$this->tmp_read_file = @fopen($args['upload.file.PUT'], 'rb');
			$size = filesize($args['upload.file.PUT']);

			curl_setopt($curl_handle, CURLOPT_INFILE, $this->tmp_read_file );
			curl_setopt($curl_handle, CURLOPT_BUFFERSIZE, 128);
			if ($size >= 0) {
				curl_setopt( $curl_handle, CURLOPT_INFILESIZE, $size );
			}
		}

		if (isset($args['save.output.in'])) {
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

		//close resources
		curl_close($curl_handle);

		if ($this->tmp_write_file != null){
			@fclose($this->tmp_write_file);
		}

		if ($this->tmp_read_file != null){
			@fclose($this->tmp_read_file);
		}

		//check errors from server
		if (in_array($this->response['code'], array(401, 404, 500, 505))) {

			if ($this->response['code'] == 401){ $this->response['message'] = "Wrong Credentials";}
			if ($this->response['code'] == 404){ $this->response['message'] = "Object Not found";}
			if ($this->response['code'] == 500){ $this->response['message'] = "Server Error: 500";}
			if ($this->response['code'] == 505){ $this->response['message'] = "Server Error: 505";}

			//clean resources
			if(isset($args['save.output.in'])) {
				@unlink($args['save.output.in']);
			}

			//exit
			return false;
		}

		if ($this->tmp_write_file == null && $args['return.body'] && $args['parse.response'] ){
			return $this->parseResponse($args['parse.response']);
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
	private function parseResponse ($type)
	{
		if (in_array($type, array('auto', 'xml')) && $xml = @simplexml_load_string($this->buffer) ) {

			$response = array();
			$this->parse_xml_node(&$response, $xml, $first_time =true);
			return $response;

		} elseif (in_array($type, array('auto', 'json')) && $json =  json_decode($this->buffer) ) {

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
	private function parse_xml_node ($array, $node, $first_time=false)
	{
		if(!$first_time) { $i=0; }

		foreach($node->children() as $name => $xmlchild) {

			if(count($xmlchild) == 0) {
				$array[$name]= (string)$node->$name;
				continue;
			} 

			if (isset($i)) {
					$this->parse_xml_node (&$array[$i][$name], $xmlchild);
					$i++;
			} else {
				$this->parse_xml_node (&$array[$name], $xmlchild);
			}
		}
	}
}
?>
