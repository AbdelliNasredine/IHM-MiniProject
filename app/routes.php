<?php 
	
    use Psr\Http\Message\ServerRequestInterface as Request;
    use Psr\Http\Message\ResponseInterface as Response;
    use Respect\Validation\Validator as v;
    use App\Classes\Auth as auth;

    // Route de Page d'acceuil 	:

    $app->get('/' , function(Request $req , Response $res){

    	$vars = [
            'page'  => [
            'title' => 'Binevenu - Acceuil.',
            ],
        ];
        if (isset($_SESSION['user_id'])) {
            
            if($_SESSION['admin'] == 1)

                return $res->withRedirect($this->router->pathFor('admin')); 

            return $res->withRedirect($this->router->pathFor('cours'));
        
        }
    	return $this->view->render($res , 'home.twig' ,$vars);

    })->setName('acceuil');

    
    // Route de Page d'inscription (affichage de vue) :

    $app->get('/inscription' , function(Request $req , Response $res){

        if (isset($_SESSION['user_id'])) {
            
            return $res->withRedirect($this->router->pathFor('acceuil'));

        }

    	return $this->view->render($res , 'register.twig' );

    })->setName('inscription');

    // Route qui fait l'inscription avec la methode post :

    $app->post('/inscription' , function(Request $req , Response $res){

        // validation des donneés :

        $validation = $this->validator->validate($req , [
            'username' => v::noWhitespace()->notEmpty(),
            'email'    => v::noWhitespace()->notEmpty()->email(),
            'password' => v::noWhitespace()->notEmpty()->length(6,null),
        ]);

        if ( $validation->failed() ) {
            return $res->withRedirect( $this->router->pathFor('inscription'));
        }

        $name     = $req->getParam('username');

        $email    = $req->getParam('email');

        $password = password_hash($req->getParam('password') , PASSWORD_DEFAULT);

        // test si un "user" est dija dans la base de donnée avec ces donnée :

        $auth = new Auth($this->db);

        if ($auth->Is_user_existed($email)) {
            
            $this->flash->addMessage('error' , 'Votre email est deja utilisé , essai un autre !');

            return $res->withRedirect($this->router->pathFor('inscription'));

        }else {

            // nouveaux utilisateur
        
        	$query = "INSERT INTO users (`user_id` ,`user_name` ,`user_password`,`user_img`,`user_email`,`user_admin_permission`) VALUES ('',?,?,?,?,'')";
        	
        	$result = $this->db->prepare($query);

            $result->execute( array($name,$password,'default.png',$email) );

            $this->flash->addMessage('info' , 'Vous avez enregistré ! ');
                
            return $res->withRedirect($this->router->pathFor('acceuil'));

        }
    });

    // Route de Connection au site (methode post)

    $app->get('/conection' , function(Request $req , Response $res){

        return $res->withRedirect($this->router->pathFor('acceuil'));
    
    });

    $app->post('/conection' , function(Request $req , Response $res){

        $email    = $req->getParam('email');

        $password = $req->getParam('password');

        $auth = new Auth($this->db);

        if ( $auth->Is_User($email,$password) ) {
            
            if( isset($_SESSION['admin']) )

                if ( $_SESSION['admin'] == 1 )

                return $res->withRedirect($this->router->pathFor('admin'));
            
            return $res->withRedirect($this->router->pathFor('acceuil'));

        }
        $this->flash->addMessage('error' , 'Email ou mote de pass erronée');

        return $res->withRedirect($this->router->pathFor('acceuil'));

    });

    // Route de Déconnection :

    $app->get('/deconnecter' , function(Request $req , Response $res){

        unset($_SESSION['user_id']);

        return $res->withRedirect($this->router->pathFor('acceuil'));

    })->setName('deconnecter');

    // Route de Cours :

    $app->get('/cours' , function(Request $req , Response $res){

        if(isset($_SESSION['user_id'])){
            
            $query = "SELECT * FROM cours ";

            $result = $this->db->query( $query )->fetchAll(PDO::FETCH_ASSOC);

            $this->view->getEnvironment()->addGlobal('cours' ,$result );

            return $this->view->render($res , 'cours.twig' );

        }else{

            return $res->withRedirect($this->router->pathFor('acceuil'));

        }

    })->setName('cours');

    $app->get('/cours/{id}' , function(Request $req , Response $res , array $args){
    
        if (isset($_SESSION['user_id'])) {
            
            $cour_id = (int) $args['id'];

            $query = "SELECT * FROM cours WHERE cours_id = ?";

            $result = $this->db->prepare( $query );

            $result->execute( array($cour_id) );

            $this->view->getEnvironment()->addGlobal('single' ,$result->fetchAll(PDO::FETCH_ASSOC));

            return $this->view->render($res , 'cour.twig');

        }else{

            return $res->withRedirect($this->router->pathFor('acceuil'));
        }        

    });

    // les route de forum :

    $app->get('/forum' , function(Request $req , Response $res){

        if (isset($_SESSION['user_id'])) {
            
            $query = " SELECT * FROM categories";

            $result = $this->db->query($query);

            $all = [];

            while ( $row = $result->fetch(PDO::FETCH_ASSOC) ) {
                
                $query = " SELECT suject_id , categorie_id , user_name , suject_title , suject_date FROM sujects INNER JOIN users ON users.user_id = sujects.user_id WHERE sujects.categorie_id = ? ";

                $temp = $this->db->prepare($query);

                $temp->execute( array($row['categorie_id']) );

                $all[ $row['cat_title'] ] = [

                        'sujects' =>  $temp->fetchAll(PDO::FETCH_ASSOC),
                        
                        'nb' => '',

                ];

                // nb de suject par categories :

                $query = "SELECT count(*) FROM sujects WHERE categorie_id = ". $row['categorie_id'] ;

                $nb = $this->db->query($query);

                $all[$row['cat_title']]['nb'] = $nb->fetch(PDO::FETCH_ASSOC)["count(*)"]; 
            };

            $this->view->getEnvironment()->addGlobal('allForum' , $all); 

            return $this->view->render($res , 'forum.twig'  );

        }else{

            return $res->withRedirect($this->router->pathfor('acceuil')); 
        }

    })->setName('forum');

    $app->get('/forum/{cat}/{id}' , function(Request $req , Response $res , array $args){

        if (isset($_SESSION['user_id'])) {

            $suject_id = (int) $args['id'];

            $query = " SELECT user_name , user_img , suject_id , categorie_id , suject_title , suject_content , suject_date FROM sujects INNER JOIN users ON users.user_id = sujects.user_id WHERE suject_id = ?";

            $result = $this->db->prepare($query);

            $result->execute( array($suject_id) );

            $suject = $result->fetch(PDO::FETCH_ASSOC);

            $query = " SELECT user_name , user_img , comment_content , comment_date FROM  comments INNER JOIN users ON users.user_id = comments.user_id WHERE comments.suject_id = ". $suject['suject_id'] ." ORDER BY comment_date ASC ";

            $temp = $this->db->query( $query );

            $this->view->getEnvironment()->addGlobal('all' ,[

                'suject'    => $suject,

                'comments'  => $temp->fetchAll(PDO::FETCH_ASSOC),

            ]);

            return $this->view->render($res , 'suject.twig');

        }else{

            return $res->withRedirect($this->router->pathfor('acceuil')); 
        
        }

    });

    $app->post('/add/comments/{cat_id}/{suject_id}' , function (Request $req , Response $res ,array $args){

    if (isset($_SESSION['user_id'])) {

        $user_id = $_SESSION['user_id'];

        $cat = $args['cat_id'];

        $suject = $args['suject_id'];

        $comment = $req->getParam('comment');

        if (empty($comment)) {
            
            // comment vide : 

            $this->flash->addMessage('error' , 'Comment  est vide !');

            return $res->withRedirect($this->router->pathFor('forum'));

        }else {

            $query = "INSERT INTO comments (`comment_id`,`categorie_id`,`suject_id`,`user_id`,`comment_content`,`comment_date`) VALUES ('',? , ? , ? , ? , ? ) ";

            $resulta = $this->db->prepare($query); 

            $resulta->execute(array($cat,$suject,$_SESSION['user_id'],$comment,date("Y-m-d")));

            return $res->withRedirect("/forum/$cat/$suject");        
        }

    }else{

        return $res->withRedirect($this->router->pathfor('acceuil'));

    }

    });

    $app->post('/add/suject' , function(Request $req , Response $res){

        if ( isset($_SESSION['user_id']) ) {

            $detail = $req->getParam('suject');

            // validation :

            if ( empty($detail) ) {
                // un des deux est vide :
                $this->flash->addMessage('error' , 'champ obligatoire est vide !');

                return $res->withRedirect($this->router->pathFor('forum'));

            }else{

                $query = " INSERT INTO sujects (`suject_id`,`categorie_id`,`user_id`,`suject_title`,`suject_content`,`suject_date`) VALUES ('',?,?,?,?,?) ";

                $resulta = $this->db->prepare($query);

                $resulta->execute( array($req->getParam('categorie'),$_SESSION['user_id'],$req->getParam('titre'),$req->getParam('suject'),date("Y-m-d")) );
                
                return $res->withRedirect($this->router->pathFor('forum'));

            }

        }else{

            return $res->withRedirect($this->router->pathFor('acceuil'));

        }

    });


    //Routes de page d'options :
    $app->get('/options' , function (Request $req , Response $res){

        if (isset($_SESSION['user_id'])) {
        
            $query  = " SELECT user_name , user_email ,user_img FROM users WHERE user_id = ? ";

            $result = $this->db->prepare( $query );

            $result->execute( array($_SESSION['user_id']) );

            $this->view->getEnvironment()->addGlobal('user' , $result->fetch(PDO::FETCH_ASSOC) );

            return $this->view->render($res , 'option.twig'); 

        }else{

            return $res->withRedirect($this->router->pathFor('acceuil'));

        }
    })->setName('options');

    $app->post('/modifie/info' , function(Request $req ,Response $res , array $args ){


            if ($_FILES["img"]["name"] != '') {
                
                $ext_alowed = array("jpg","png");

                $tmp = explode('.', $_FILES["img"]["name"]);

                $file_extension = end($tmp);

                if (in_array($file_extension, $ext_alowed)) {

                        $name = md5(rand()) . '.' . $file_extension;

                        $path = "app/resources/profil/" . $name;

                        move_uploaded_file($_FILES["img"]["tmp_name"], $path);

                        $query = " UPDATE users SET user_img = ?  WHERE user_id = ?";

                        $result = $this->db->prepare( $query );

                        $result->execute(array($name,$_SESSION['user_id']));

                        $this->flash->addMessage('info' , 'modification réussie');

                        return $res->withRedirect($this->router->pathFor('options'));

                }else{

                        // extention erronné 

                        $this->flash->addMessage('error' , " extention d'image inconu , essai un .png ou .jpg ");

                        return $res->withRedirect($this->router->pathFor('options'));

                }


            }else {

                // pas image selecter 

                $this->flash->addMessage('error' , " selectionner une image s'il vous plait");

                return $res->withRedirect($this->router->pathFor('options')); 

            }

    });

// espace admin 

    $app->get('/admin',function (Request $req , Response $res){

        if( isset($_SESSION['admin']) )

            if( $_SESSION['admin'] == 1 ){

                // les donnée :

                // la liste d'utilisateur :

                $query = " SELECT *  FROM users ";

                $u = $this->db->query($query)->fetchAll(PDO::FETCH_ASSOC);

                // statistique : 

                $s1 = $this->db->query(" SELECT count(*) FROM cours")->fetch(PDO::FETCH_ASSOC);

                $s2 = $this->db->query(" SELECT count(*) FROM sujects")->fetch(PDO::FETCH_ASSOC);

                $s3 = $this->db->query(" SELECT count(*) FROM comments")->fetch(PDO::FETCH_ASSOC);

                $s4 = $this->db->query(" SELECT count(*) FROM users")->fetch(PDO::FETCH_ASSOC);

                $this->view->getEnvironment()->addGlobal('u' , $u );
                
                $this->view->getEnvironment()->addGlobal('s1' , $s1["count(*)"] );
                
                $this->view->getEnvironment()->addGlobal('s2' , $s2["count(*)"] );

                $this->view->getEnvironment()->addGlobal('s3' , $s3["count(*)"] );

                $this->view->getEnvironment()->addGlobal('s4' , $s4["count(*)"] );

                return $this->view->render($res , 'admin.twig');
        
            }else {

                return $res->withRedirect($this->router->pathFor('acceuil'));
        
            }
    })->setName('admin');

    $app->post('/add/cours' , function(Request $req , Response $res){
        
        if( isset($_SESSION['user_id']) ){

            $query = "INSERT INTO cours (`cours_id`,`cours_title`,`cours_disc`,`cours_content`,`cours_date`) VALUES ('',?,?,?,?)";

            $result = $this->db->prepare($query);

            $result->execute( array($req->getParam('titre'),$req->getParam('desc'),$req->getParam('content'),date("Y-m-d")) );

            $this->flash->addMessage('info' , 'le cour a été ajouter');

            return $res->withRedirect($this->router->pathFor('admin'));


        }else{

            return $res->withRedirect($this->router->pathFor('acceuil'));
        
        }
    });



