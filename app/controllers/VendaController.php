<?php

class VendaController extends \HXPHP\System\Controller
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

		$this->auth->redirectCheck(false); // Configuração de redirecionamento de páginas (private/public)

		$user_id = $this->auth->getUserId(); // Obtendo atributos do usuário
		$user = User::find($user_id);

		$this->view->setVar('user', $user)
				->setHeader('header_side')
				->setFooter('footer_side')
				->setTemplate(true)
				->setTitle('SIDE | Venda');
	}

	public function indexAction()
	{
		$this->view->setFile('listar');
	}

	public function vendaAction()
	{
		$this->view->setFile('listar');
		
		$this->request->setCustomFilters(array(
			'id' => FILTER_SANITIZE_NUMBER_INT,
			'valor_venda' => FILTER_SANITIZE_NUMBER_FLOAT,
			'est_atual' => FILTER_SANITIZE_NUMBER_INT
		));

		$post = $this->request->post();

		$this->view->setVars([
				'id_product' => $post['id'],
				'description' => $post['descricao'],
				'sell_value_unit' => $post['valor_venda'],
				'est_atual' => $post['est_atual']
				])
				->setFile('venda');
	}
}