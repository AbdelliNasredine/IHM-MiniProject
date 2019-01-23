<?php 

namespace App\Classes;

use Respect\Validation\Validator as Respect;
use Respect\Validation\Exceptions\NestedValidationException ;  

class Validator 
{
	protected $errs;

	public function validate($req , array $rules)
	{
		foreach ($rules as $field => $rule) {
			try{
				$rule->setName(ucfirst($field))->assert($req->getParam($field));
			} catch (NestedValidationException  $e){
				$this->errs[$field]=$e->getMessages(); 
			}
		}

		$_SESSION['er'] = $this->errs;

		return $this;
	}
	public function failed()
	{
		return !empty($this->errs);
	}
}