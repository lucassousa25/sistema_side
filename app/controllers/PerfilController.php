<?php

/**
 * 
 */
class PerfilController extends \HXPHP\System\Controller
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

		$user_id = $this->auth->getUserId(); // Obtendo atributos do usuário

		$this->view->setFile('editar')
				->setTitle('SIDE | Editar perfil')
				->setHeader('header_side')
				->setFooter('footer_side')
				->setTemplate(true)
				->setVar('user', User::find($user_id));
	}
}