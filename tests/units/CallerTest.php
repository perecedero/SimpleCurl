<?php

class CallerTest extends PHPUnit_Framework_TestCase
{

	//testear que se sobrescriben los settings instanciar

	//testear que se sobrescriben los datos al hacer el call

	//TDD!!

	public function testUploadFileWithPutMethod()
	{
		$c = new \Perecedero\SimpleCurl\Caller(array('url.domain'=>'127.0.25.1:8000'));

		$res = $c->call(array(
			'url.path' => '/serverapi.php/file/new',
			'upload.file' =>  __DIR__ . '/test.file',
			'method' => 'PUT'
		));

		$expectedResult =  json_encode(array('read' => file_get_contents(__DIR__ . '/test.file')));

		$this->assertEquals(200, $res->code, 'Response HTTP status code');
		$this->assertJsonStringEqualsJsonString($expectedResult, $res->body, 'Response body');
	}

	public function testUploadFileWithPOSTMethod()
	{
		$c = new \Perecedero\SimpleCurl\Caller(array('url.domain'=>'127.0.25.1:8000'));

		$res = $c->call(array(
			'url.path' => '/serverapi.php/file/update',
			'upload.file' =>  __DIR__ . '/test.file',
			'method' => 'POST'
		));

		$expectedResult =  json_encode(array('read' => file_get_contents(__DIR__ . '/test.file')));

		$this->assertEquals(200, $res->code, 'Response HTTP status code');
		$this->assertJsonStringEqualsJsonString($expectedResult, $res->body, 'Response body');
	}

	public function testUploadFileWithImplicitPOSTMethod()
	{
		$c = new \Perecedero\SimpleCurl\Caller(array('url.domain'=>'127.0.25.1:8000'));

		$res = $c->call(array(
			'url.path' => '/serverapi.php/file/update',
			'upload.file' =>  __DIR__ . '/test.file',
		));

		$expectedResult =  json_encode(array('read' => file_get_contents(__DIR__ . '/test.file')));

		$this->assertEquals(200, $res->code, 'Response HTTP status code');
		$this->assertJsonStringEqualsJsonString($expectedResult, $res->body, 'Response body');
	}

}
