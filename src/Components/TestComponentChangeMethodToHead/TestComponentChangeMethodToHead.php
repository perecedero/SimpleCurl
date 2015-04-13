<?php
class TestComponentChangeMethodToHead
{
	public function run($settings = array())
	{
		$settings['method'] = 'HEAD';
		return $settings;
	}

}
