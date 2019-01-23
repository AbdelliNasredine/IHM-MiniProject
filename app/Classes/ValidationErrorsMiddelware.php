<?php 

namespace App\Classes;
 
class ValidationErrorsMiddelware extends Middleware
{
	public function __invoke($req , $res ,$next)
	{
		if (isset($_SESSION['er'])) {
			$this->container->view->getEnvironment()->addGlobal('validationerrors', $_SESSION['er']);
			unset($_SESSION['er']);
		}
		$req = $next($req , $res);
		return $req;
	}
}