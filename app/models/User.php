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

	public static function login(array $post)
	{
		$callbackObj = new \stdClass; // Atribuindo classe vazio do framework
		$callbackObj->user = null;
		$callbackObj->status = false;
		$callbackObj->code = null;
		$callbackObj->tentativas_restantes = null;

		$user = self::find_by_username($post['username']);

		if(!is_null($user)) {
			$password = \HXPHP\System\Tools::hashHX($post['password'], $user->salt);
			
			if($user->status === 1) {
				if( LoginAttempt::existemTentativas($user->id) ) { // Condição se ainda existem tentativas de login
					if ($password['password'] === $user->password) { // Comparando passwords   
						$callbackObj->user = $user;
						$callbackObj->status = true; // Status de login se torna 'true' par verificação no Controller
						LoginAttempt::limparTentativas($user->id); // Zera as tentativas no caso de sucesso ao logar
					}
					else {
						// Condicionamento para exibição de erros se tratanto da quantidade de tentativas
						if(LoginAttempt::tentativasRestantes($user->id) <= 3) { // Exibe contagem quando chegar em 3 tentativas erradas
							$callbackObj->code = "tentativas-esgotando";
							$callbackObj->tentativas_restantes = LoginAttempt::tentativasRestantes($user->id);
						}
						else {
							$callbackObj->code = "dados-incorretos";
						}

						LoginAttempt::registrarTentativa($user->id);
					}
				}
				else{
					$callbackObj->code = "usuario-bloqueado";

					$user->status = 0; //Status = 0 quando não existir mais tentativas (login_attempts = 5)
					$user->save(false); //Pular as validaçãoes de usuário (phpActiveRecord)
				}
			}
			else {
				$callbackObj->code = "usuario-bloqueado";
			}
		}
		else {
			$callbackObj->code = "usuario-inexistente";
		}

		return $callbackObj;
	}
}