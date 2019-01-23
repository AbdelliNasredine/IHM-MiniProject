<?php 

namespace App\Classes;

use \PDO;

Class Auth {
 
	public $db;

	public function __construct($db)
	{
		$this->db = $db;
	}

	public function check(){

		return isset($_SESSION['user_id']);

	}

	public function current_user(){

        $query  = 'SELECT * FROM users WHERE user_id = ?';

        $result = $this->db->prepare($query);

        if (isset($_SESSION['user_id'])) {

        $result->execute( array($_SESSION['user_id']) );

        
        return $result->fetchAll(PDO::FETCH_ASSOC);

        }
	}

	public function Is_User($email , $password){

        $query  = 'SELECT * FROM users WHERE user_email = ?';

        $result = $this->db->prepare($query);

        $result->execute( array($email) );

        $user   = $result->fetchAll(PDO::FETCH_ASSOC);

        if ( empty($user) ) {
            
            return false;
        }

        if ( password_verify($password , $user[0]['user_password']) ) {

            $_SESSION['user_id'] = $user[0]['user_id'];
            $_SESSION['admin']   = $user[0]['user_admin_permission'];
            return true;
        }
        
        return false;
	
	}
    public function Is_user_existed($email){

        $q = "SELECT * FROM users WHERE user_email = ?";

        $r = $this->db->prepare( $q );

        $r->execute( array($email) );

        if ( !empty($r->fetch(PDO::FETCH_ASSOC)) ) {
            
            return true;

        }else {

            return false;
        
        }

    }
}
