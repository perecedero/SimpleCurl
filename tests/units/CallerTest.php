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


	public function testDownloadfileWithSaveOn()
	{
		$c = new \Perecedero\SimpleCurl\Caller(array('url.domain'=>'127.0.25.1:8000'));

		$res = $c->call(array(
			'url.path' => '/serverapi.php/file/1234',
			'save.on' =>  __DIR__ . '/downloadTest.file',
		));
		$this->assertEquals(200, $res->code, 'Response HTTP status code');
		$this->assertTrue(file_exists( __DIR__ . '/downloadTest.file'));
		$obteinedResult =file_get_contents(__DIR__ . '/downloadTest.file');
		$expectedResult= "test file to download. viki mussa";
		$this->assertEquals($expectedResult,$obteinedResult);

		//$this->assertJsonStringEqualsJsonString( json_encode(array("Mascott" => "Tux")), '{"Mascott":"Tux"}');
		//anda por que son iguales
	}

	public function testIsRightInstance()
	{
		$c =  new \Perecedero\SimpleCurl\Caller();
		$returnedResponse = $c->call(array(
			 'url' => '127.0.25.1:8000',
		));
		$this->assertInstanceOf('Perecedero\SimpleCurl\Response', $returnedResponse);
	}

	public function testLoadComponent()
	{
		$c =  new \Perecedero\SimpleCurl\Caller();
		$nameComponent= 'TestComponent';
		$c->loadComponent($nameComponent);
		$this->assertInstanceOf($nameComponent, $c->TestComponent);
	}

	public function testRunComponent()
	{
		$c =  new \Perecedero\SimpleCurl\Caller();
		$nameComponent= 'TestComponent';
		$c->loadComponent($nameComponent);
		$response = $c->call(array(
			'url' => '127.0.25.1:8000',
		));
		$str= $response->get('headers.sent');
		$this->assertTrue(strpos($str, 'HEAD') !== false , 'El componente no ejecuto la funcion run');

	}

	//falta Header usser aggent
	// headers['User-Agent']='CERN-LineMode/2.15 libwww/2.17b3';

	public function testRunTwoComponent()
	{
		$c =  new \Perecedero\SimpleCurl\Caller();
		$nameComponent= 'TestComponentV2';
		$c->loadComponent($nameComponent);
		$response = $c->call(array(
			'url' => '127.0.25.1:8000',
		));
		$str= $response->get('headers.sent');
		$this->assertTrue(strpos($str, 'POST') !== false , 'El componente V2 no ejecuto la funcion run');

	}

	public function testUseComponent()
	{
		$c =  new \Perecedero\SimpleCurl\Caller();
		$nameComponent= 'TestComponent';
		$c->loadComponent($nameComponent);
		$nameComponentTwo= 'TestComponentV2';
		$c->loadComponent($nameComponentTwo);
		$vecComponent=[$nameComponent,$nameComponentTwo];
		$response = $c->call(array(
			'url' => '127.0.25.1:8000',
			'uses'=>$vecComponent,
		));
		$str= $response->get('headers.sent');
		//echo $str;
		//$this->assertTrue(strpos($str, 'HEAD') !== false , 'El componente V2 no ejecuto la funcion run');
		$this->assertTrue(strpos($str, 'POST') !== false , 'El componente V2 no ejecuto la funcion run');


	}

	 public function testUseWhitThreeComponent()
	{
		$c =  new \Perecedero\SimpleCurl\Caller();
		$nameComponent= 'TestComponent';
		$c->loadComponent($nameComponent);
		$nameComponentTwo= 'TestComponentV2';
		$c->loadComponent($nameComponentTwo);

		$nameComponentThree= 'TestComponentV3';
		$c->loadComponent($nameComponentThree);

		$vecComponent=[$nameComponent,$nameComponentTwo,$nameComponentThree];
		$response = $c->call(array(
			'url' => '127.0.25.1:8000',
			'uses'=>$vecComponent,
		));
		$str= $response->get('headers.sent');
		//echo $str;
		//$this->assertTrue(strpos($str, 'HEAD') !== false , 'El componente V2 no ejecuto la funcion run');
		$this->assertTrue(strpos($str, 'Mozilla/4.0') !== false , 'El componente V3 no ejecuto la funcion run');


	}
}
