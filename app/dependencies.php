<?php 


	$container = $app->getContainer();

	// partie middelware :
	$app->add( new \App\Classes\ValidationErrorsMiddelware($container) );

	// partie container :
	$container['db'] = function ($container)
	{
		
		$db  = $container['settings']['db'];
		$pdo = new PDO('mysql:host=' . $db['host'] . ';dbname=' . $db['dbname'] , $db['user'] , $db['pass']);
		$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
		return $pdo;
	};

	$container['auth'] = function ($container) 
	{
		return new \App\Classes\Auth($container);
	};
	
	$container['flash'] = function ($container) 
	{
		return new \Slim\Flash\Messages;
	};

	$container['view'] = function ($container)
	{
		
		$path = __DIR__ . '\resources\views';
		$view = new \Slim\Views\Twig($path , [
			'cache' => false,
		]);
		$view->addExtension(new \Slim\Views\TwigExtension(
			$container->router,
			$container->request->getUri()
		));

		$auth = new \App\Classes\Auth($container->db);

		$view->getEnvironment()->addGlobal('auth' , [

			'check' => $auth->check(),
			'user'  => $auth->current_user(),

		]);

		$view->getEnvironment()->addGlobal('flash' ,$container->flash );

		return $view;
	};


	$container['validator'] = function ($container)
	{
		return new \App\Classes\Validator;
	};









