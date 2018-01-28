<?php

class HomeController extends \HXPHP\System\Controller
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

		$this->auth->redirectCheck(false); //Configuração de redirecionamento de páginas (private/public)

		$user_id = $this->auth->getUserId(); // Obtendo atributos do usuário
		$user = User::find($user_id);

		$this->view->setFile('index')
               ->setHeader('header_side')
               ->setFooter('footer_side')
               ->setTemplate(true)
               ->setTitle('SIDE | Home')
               ->setVar('user', $user);
	}

	public function indexAction()
	{	
				
	}
}