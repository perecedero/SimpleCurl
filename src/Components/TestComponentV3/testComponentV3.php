<?php
class TestComponentV3
{
	public function run( $settings=array() )
	{
		//$settings['method']= '';
		$settings['header'][]='User-Agent: Mozilla/4.0';
		return $settings;
	}

}
