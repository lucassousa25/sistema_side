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
	}

	public function indexAction()
	{
		$this->view->setFile('index')
               ->setHeader('header_side')
               ->setFooter('footer_side')
               ->setTemplate(true);

        $this->auth->redirectCheck(false); //Configuração de redirecionamento de páginas (private/public)
	}
}