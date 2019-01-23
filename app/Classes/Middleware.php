<?php 

namespace App\Classes;


class Middleware 
{
	
	public $container;

	public function __construct($container)
	{
		$this->container = $container;
	}
}