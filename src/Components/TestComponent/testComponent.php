<?php
class TestComponent
{
    public function run( $settings=array() )
    {
        $settings['method']= 'HEAD';
        return $settings; 
    }
    
}