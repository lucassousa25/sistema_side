<?php

class ProdutosController extends \HXPHP\System\Controller
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

		$this->view->setVar('user', $user)
				->setHeader('header_side')
				->setFooter('footer_side')
				->setTemplate(true)
				->setTitle('SIDE | Produtos');
	}

	public function indexAction()
	{
		$this->view->setVar('products', Product::all())
				->setFile('listar');
	}

	public function cadastrarAction()
	{
		$this->view->setFile('cadastrar');
		
		$this->request->setCustomFilters(array(
			'cost' => FILTER_SANITIZE_NUMBER_FLOAT,
			'sell_value' => FILTER_SANITIZE_NUMBER_FLOAT
		));

		$user_id = $this->auth->getUserId(); // Obtendo atributos do usuário

		$post = $this->request->post();

		if(!empty($post)) {
			$cadastrarProduto = Product::cadastrar($post, $user_id);

			if ($cadastrarProduto->status === false) {
				$this->load('Helpers\Alert', array(
					'error',
					'Não foi possível efetuar seu cadastro. Verifique os erros abaixos:',
					$cadastrarProduto->errors
				));
			}
			else {
				$this->view->setVar('products', Product::all())
							->setFile('listar');

				$this->load('Helpers\Alert', array(
					'success',
					'O produto ' . $cadastrarProduto->product_description . ' foi cadastrado no sistema!'
				));
			}
		}
	}

	public function cadastrarProdutoAction()
	{
		$this->view->setFile('cadastrar')
				->setHeader('header_side')
				->setFooter('footer_side')
				->setTemplate(true)
				->setTitle('SIDE | Produtos');
	}
}

