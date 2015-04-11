<?php

class TestApi {

	public $method = null;
	public $request = null;

	public function __construct()
	{
		$this->method = @$_SERVER['REQUEST_METHOD'];
		$this->request = @$_SERVER['PATH_INFO'];
	}

	public function serveRequest()
	{
		if (preg_match('@\/file(s)?\/new@', $this->request)) {
			$this->newFile();
		} else if (preg_match('@\/file(s)?\/update@', $this->request)) {
			$this->updateFile();
		} else if (preg_match('@\/file(s)?\/[0..9a..zA..Z]*@', $this->request)) {
			$this->getFile();
		} else {
			$this->sendResponse('404 Not found');
		}
	}

	public function newFile()
	{
		if ($this->method != 'PUT'){
			$this->sendResponse('405 Method Not Allowed');
		} else {
			$putdata = fopen("php://input", "r");
			$content = '';
			while ($data = fread($putdata, 1024)) {
				$content .= $data;
			}
			fclose($putdata);

			if ($content) {
				$this->sendResponse('200 Ok', ['read' => $content]);
			} else {
				$this->sendResponse('400 Bad request');
			}
		}
	}

	public function updateFile()
	{
		if ($this->method != 'POST'){
			$this->sendResponse('405 Method Not Allowed');
		} else {

			$f = array_pop($_FILES);

			$content = file_get_contents($f['tmp_name']);

			if ($content) {
				$this->sendResponse('200 Ok', ['read' => $content]);
			} else {
					$this->sendResponse('400 Bad request');
			}
		}
	}

	public function sendResponse($status, $response = null, $mime = 'application/json')
	{
		//parametro arcchivo
		//poner el tipo de los archivos plain/text

		header('HTTP/1.1 '.$status, true);
		header('Status: '.$status, true);
		header('Content-type: '.$mime, true);
		if ($response) {
			if ($mime == 'application/json') {
				echo json_encode($response);
			} elseif ($mime == 'text/plain') {
				echo $response;
			}
		}
		exit;
	}

	public function getFile()
	{
		if ($this->method != 'GET'){
			$this->sendResponse('405 Method Not Allowed');
		} else {

			$content = file_get_contents('ftodownload.file');
			if ($content) {
				//faltan parametros
				$this->sendResponse('200 Ok', $content, 'text/plain');
			} else {
				//no tienen sentido sacar
				$this->sendResponse('400 Bad request');
			}
		}
		 exit;
	}
}

$api = new TestApi();
$api->serveRequest();
