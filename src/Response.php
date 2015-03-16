<?php

/**
 * Response
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

class Response
{
	public $settings = null;

	private $data = null;

	public function __construct($settings, $data)
	{
		$this->settings = $settings;
		$this->data = array_merge(array('body'=>''), $data);

		if (
			$this->data['body'] && (
				($this->data['code'] < 400 && $this->settings['parse.body'] ) ||
				($this->data['code'] >= 400 && $this->settings['parse.body.onerror'])
			)
		) {
			$this->parse();
		}
	}

	public function get($key = null)
	{
		if (!$key && isset($this->data['parsed.body'])) {
			return $this->data['parsed.body'];
		} elseif (!$key) {
			return $this->data['body'];
		} elseif (isset($this->data[$key])) {
			return $this->data[$key];
		}
	}

	public function set($key, $val = null)
	{
		return $this->data[$key] = $val;
	}

	public function __get($key = null)
	{
		if (isset($this->data[$key])) {
			return $this->data[$key];
		} else {
			return null;
		}
	}

	/**
	 * Parse response body
	 *
	 * @access public
	 */
	private function parse ()
	{
		$type = $this->settings['parse.body'];

		if (in_array($type, array('auto', 'json')) && $json =  @json_decode($this->data['body'], false)) {
			$parsed = $json;
		} elseif (in_array($type, array('auto', 'json.assoc')) && $json =  @json_decode($this->data['body'], true)) {
			$parsed = $json;
		} elseif (in_array($type, array('auto', 'xml')) && $xml = @simplexml_load_string($this->data['body'])) {
			$parsed = $xml;
		} else { //raw
			$parsed = @$this->data['body'];
		}

		$this->set('parsed.body', $parsed);
	}
}


