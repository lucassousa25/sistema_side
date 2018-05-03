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

		$listarVenda = Sell::listar($user_id);

		$anterior = $listarVenda['anterior'];
		$proximo = $listarVenda['proximo'];
		$pagina = $listarVenda['pagina'];
		$total_paginas = $listarVenda['total_paginas'];
		$total_vendas = $listarVenda['total_vendas'];
		$primeira_venda = $listarVenda['primeira_venda'] + 1;
		$registros = $listarVenda['registros'];

		$this->view->setVars([
						'user' => $user,
						'vendas' => $registros,
						'anterior' => $anterior,
						'proximo' => $proximo,
						'pagina' => $pagina,
						'total_paginas' => $total_paginas,
						'total_vendas' => $total_vendas,
						'primeira_venda' => $primeira_venda
					])
				->setHeader('header_side')
				->setFooter('footer_side')
				->setTemplate(true)
				->setTitle('SIDE | Vendas');
	}

	public function indexAction()
	{
		$this->view->setFile('listar');
	}

	public function listarAction($pagina = 1)
	{
		$get = $pagina;

		$user_id = $this->auth->getUserId(); // Obtendo atributos do usuário

		$listarVenda = Sell::listar($user_id, $get);

		$anterior = $listarVenda['anterior'];
		$proximo = $listarVenda['proximo'];
		$pagina = $listarVenda['pagina'];
		$total_paginas = $listarVenda['total_paginas'];
		$total_vendas = $listarVenda['total_vendas'];
		$primeira_venda = $listarVenda['primeira_venda'] + 1;
		$registros = $listarVenda['registros'];

		$this->view->setVars([
						'vendas' => $registros,
						'anterior' => $anterior,
						'proximo' => $proximo,
						'pagina' => $pagina,
						'total_paginas' => $total_paginas,
						'total_vendas' => $total_vendas,
						'primeira_venda' => $primeira_venda
					])
				->setFile('listar');
	}

	public function vendaAction($product_id = null)
	{
		if (!is_null($product_id)) {
			$product = Product::find_by_id($product_id);

			$this->view->setVars([
					'product_id' => $product->id,
					'description' => $product->description,
					'sell_value_unit' => $product->value,
					'est_atual' => $product->est_atual
					])
					->setFile('venda');
		}

	}

	public function registraVendaAction()
	{
		$this->view->setFile('venda');
		
		$this->request->setCustomFilters(array(
			'product_id' => FILTER_SANITIZE_NUMBER_INT,
			'est_atual' => FILTER_SANITIZE_NUMBER_INT
		));

		$user_id = $this->auth->getUserId(); // Obtendo atributos do usuário

		$post = $this->request->post();

		if(!empty($post)) {
			$registrarVenda = Sell::cadastrar($post, $user_id);

			if ($registrarVenda->status === false) {
				$this->load('Helpers\Alert', array(
					'error',
					'Não foi possível registra sua venda. Verifique os erros abaixos:',
					$registrarVenda->errors
				));
			}
			else {
				$this->load('Helpers\Alert', array(
					'success',
					'A venda foi registrada no sistema!'
				));

				self::listarAction();
			}
		}
	}
}