<?php

/**
 * 
 */
class LoginController extends \HXPHP\System\Controller
{

	public function __construct($configs)
	{
		parent::__construct($configs);

		$this->load(
			'Services\Auth',
			$configs->auth->after_login,
			$configs->auth->after_logout,
			true
		);
	}

	public function indexAction()
	{
		$this->view->setTemplate(false)
				->setTitle('SIDE | Login');

		$this->auth->redirectCheck(true); //Configuração de redirecionamento de páginas (private/public)
	}

	public function logarAction()
	{
		$this->auth->redirectCheck(true); //Configuração de redirecionamento de páginas (private/public)

		$this->view->setFile('index');

		$post = $this->request->post();

		if(!empty($post)) { 
			$login = User::login($post);


			// No caso de dados válidos o redirecionamento é feito para home, no casa de alguma invalidez
			// é carregado o Módulo de mensagens do framework para gerar errors ('alerts')
			if($login->status === true) {
				$this->auth->login($login->user->id, $login->user->username, $login->user->role->role);
			}
			else {
				$this->load('Modules\Messages', 'auth');
				$this->messages->setBlock('alerts');
				$error = $this->messages->getByCode($login->code, array(
					'message' => $login->tentativas_restantes
				));

				$this->load('Helpers\Alert', $error);
			}
		}
	}

}