<?php

class CadastroController extends \HXPHP\System\Controller
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

		$this->auth->redirectCheck(true);
	}

	public function indexAction()
	{
		$this->view->setTemplate(false)
				->setTitle('SIDE | Cadastro');
	}

	public function cadastrarAction()
	{
		$this->view->setFile('index')
				->setTemplate(false);
				->setTitle('SIDE | Cadastro');

		$this->request->setCustomFilters(array(
			'email' => FILTER_VALIDATE_EMAIL
		));
		
		$post = $this->request->post();

		if(!empty($post)) {
			$cadastrarUsuario = User::cadastrar($post);

			if ($cadastrarUsuario->status === false) {
				$this->load('Helpers\Alert', array(
					'error',
					'Não foi possível efetuar seu cadastro. Verifique os erros abaixos:',
					$cadastrarUsuario->errors
				));
			}
			else {
				$this->auth->login($cadastrarUsuario->user->id, $cadastrarUsuario->user->username);
			}
		}
	}
}