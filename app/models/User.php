<?php

class User extends \HXPHP\System\Model
{
	static $belongs_to = array(
		array('role')
	);
	// Validando a presença dos campos (phpActiveRecord) 
	static $validates_presence_of = array(
		array(
			'name',
			'message' => 'O nome é um campo Obrigatório!'
		),
		array(
			'email',
			'message' => 'O email é um campo Obrigatório!'
		),
		array(
			'username',
			'message' => 'O nome de usuário é um campo Obrigatório!'
		),
		array(
			'password',
			'message' => 'A senha é um campo Obrigatório!'
		)
	);

	// Validando exclusividades dos campos (phpActiveRecord)
	static $validates_uniqueness_of = array(
        array(
       		'email', 
       		'message' => 'Já existe um usuário com esse e-mail cadastrado.'
       	),
        array(
        	'username',
        	'message' => 'Já existe um usuário com esse nome de usuário cadastrado.'
        )
    );
    
	public static function cadastrar(array $post)
	{
		$callbackObj = new \stdClass; // Atribuindo classe vazio do framework
		$callbackObj->user = null;
		$callbackObj->status = false;
		$callbackObj->errors = array();

		$role = Role::find_by_role('user');

		if(is_null($role)) {
			array_push($callbackObj->errors, 'A role user não existe. Contate o Administrador!');
			return $callbackObj;
		}

		$user_data = array(
			'role_id' => $role->id,
			'status' => 1
		);

		$pass = \HXPHP\System\Tools::hashHX($post['password']); //Função interna do framework para criptorafia

		$post = array_merge($post, $user_data, $pass);

		$cadastrar = self::create($post);

		if($cadastrar->is_valid()) {
			$callbackObj->user = $cadastrar;
			$callbackObj->status = true;
			return $callbackObj;
		}

		$errors = $cadastrar->errors->get_raw_errors(); 

		foreach ($errors as $field => $message) {
			array_push($callbackObj->errors, $message[0]);
		}

		return $callbackObj;
	}
}