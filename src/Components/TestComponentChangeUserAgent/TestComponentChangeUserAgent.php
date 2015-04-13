<?php
class TestComponentChangeUserAgent
{
	public function run($settings = array())
	{
		$settings['header'][] = 'User-Agent: Mozilla/4.0';
		return $settings;
	}
}
