<?php

class CallerTest extends PHPUnit_Framework_TestCase
{

	public function testUploadFileWithPutMethod()
	{
		$file = __DIR__ . '/Resources/toBeUploaded.file';

		$c = new \Perecedero\SimpleCurl\Caller(array('url.domain'=>'127.0.25.1:8000'));

		$res = $c->call(array(
			'url.path' => '/serverapi.php/file/new',
			'upload.file' =>  $file,
			'method' => 'PUT'
		));

		$expectedResult =  json_encode(array('read' => file_get_contents($file)));

		$this->assertJsonStringEqualsJsonString($expectedResult, $res->body, 'The server did no receive our file: ' . $res->code );
	}

	public function testUploadFileWithImplicitPOSTMethod()
	{
		$file = __DIR__ . '/Resources/toBeUploaded.file';

		$c = new \Perecedero\SimpleCurl\Caller(array('url.domain'=>'127.0.25.1:8000'));

		$res = $c->call(array(
			'url.path' => '/serverapi.php/file/update',
			'upload.file' =>  $file,
		));

		$expectedResult =  json_encode(array('read' => file_get_contents($file)));

		$this->assertJsonStringEqualsJsonString($expectedResult, $res->body, 'The server did no receive our file: ' . $res->code);
	}

	public function testDownloadfileWithSaveOn()
	{
		$file = __DIR__ . '/Resources/downloaded.file';

		$c = new \Perecedero\SimpleCurl\Caller(array('url.domain'=>'127.0.25.1:8000'));

		$res = $c->call(array(
			'url.path' => '/serverapi.php/file/1234',
			'save.on' =>  $file,
		));

		$obteinedResult = @file_get_contents($file);
		$expectedResult = 'test file to download. viki mussa';

		$this->assertEquals($expectedResult, $obteinedResult, 'Can not download the file: ' . $res->code );
	}

	public function testCallResponseHasTheRightInstanceOnSuccess()
	{
		$c =  new \Perecedero\SimpleCurl\Caller();
		$returnedResponse = $c->call(array(
			 'url' => '127.0.25.1:8000',
		));

		$this->assertInstanceOf('Perecedero\SimpleCurl\Response', $returnedResponse, 'response is not our object');
	}

	public function testCallrResponseHasTheRightInstanceOnFailure()
	{
		$c =  new \Perecedero\SimpleCurl\Caller();
		$returnedResponse = $c->call(array(
			 'url' => '8000', //Curl must fail on this call
		));

		$this->assertInstanceOf('Perecedero\SimpleCurl\Response', $returnedResponse, 'response is not our object');
	}

	public function testLoadComponent()
	{
		$nameComponent = 'TestComponentChangeMethodToHead';

		$c =  new \Perecedero\SimpleCurl\Caller();
		$c->loadComponent($nameComponent);
		$this->assertInstanceOf($nameComponent, $c->TestComponentChangeMethodToHead);
	}

	public function testLoadComponentChangingName()
	{
		$nameComponent = 'TestComponentChangeMethodToHead';

		$c =  new \Perecedero\SimpleCurl\Caller();
		$c->loadComponent($nameComponent, array('name' => 'tccm'));
		$this->assertInstanceOf($nameComponent, $c->tccm);
	}

	public function testRunComponent()
	{
		$c =  new \Perecedero\SimpleCurl\Caller();
		$c->loadComponent('TestComponentChangeMethodToHead');

		$response = $c->call(array(
			'url' => '127.0.25.1:8000',
		));

		$str= $response->get('headers.sent');
		$this->assertTrue(strpos($str, 'HEAD') !== false , 'El componente TestComponentChangeMethodToHead no modifico el metodo');

	}

	public function testOptionUsesToSelectWichComponentToUseInTheCall()
	{
		$c =  new \Perecedero\SimpleCurl\Caller();
		$c->loadComponent('TestComponentChangeMethodToHead');
		$c->loadComponent('TestComponentChangeUserAgent');

		$response = $c->call(array(
			'url' => '127.0.25.1:8000',
			'use'=>array('TestComponentChangeUserAgent'),
		));
		$str= $response->get('headers.sent');

		$this->assertTrue(strpos($str, 'HEAD') === false , 'El componente TestComponentChangeMethodToHead funciono si estar activado');
	}

	public function testSimpleOAuthComponent()
	{
		$c =  new \Perecedero\SimpleCurl\Caller(array(
			'url.domain' => 'https://api.twitter.com/1.1',
			'parse.body.onerror' => true,
		));

		$c->loadComponent('SimpleOAuth', array('name' => 'oauth', array(
			'consumerKey' => CONSUMER_KEY,
			'sharedSecret' => CONSUMER_SECRET,
			'xxx' => false
		)));

		$c->oauth->tokens(TOKEN, TOKEN_SECRET);


		//~ $response = $c->call(array(
			//~ 'url.path' => '/statuses/update.json',
			//~ 'method' => 'POST',
			//~ 'post' => array(
				//~ 'status' => ' @MussaMVictoria hola desde SimpleCurl y SimpleOauth!!'
			//~ )
		//~ ));

		//~ $response = $c->call(array(
			//~ 'url.path' => '/direct_messages/new.json',
			//~ 'post' => array(
				//~ 'screen_name' => 'MussaMVictoria',
				//~ 'text' => 'ahora deberia funcionar!'
			//~ )
		//~ ));

		$response = $c->call(array(
			'url.path' => '/application/rate_limit_status.json?resources=help,users,statuses',
		));

		$this->assertEquals('200', $response->code , 'Twitte no reconocio el auth header');
	}
}
