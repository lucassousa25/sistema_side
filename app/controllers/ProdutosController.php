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

		$this->load('Storage\Session');

		$this->auth->redirectCheck(false); //Configuração de redirecionamento de páginas (private/public)

		$user_id = $this->auth->getUserId(); // Obtendo atributos do usuário
		$user = User::find($user_id);

		$listarProduto = Product::listar();

		$anterior = $listarProduto['anterior'];
		$proximo = $listarProduto['proximo'];
		$pagina = $listarProduto['pagina'];
		$total_paginas = $listarProduto['total_paginas'];
		$total_produtos = $listarProduto['total_produtos'];
		$primeiro_produto = $listarProduto['primeiro_produto'] + 1;
		$products = $listarProduto['registros'];

		$this->view->setVars([
						'user' => $user,
						'products' => $products,
						'anterior' => $anterior,
						'proximo' => $proximo,
						'pagina' => $pagina,
						'total_paginas' => $total_paginas,
						'total_produtos' => $total_produtos,
						'primeiro_produto' => $primeiro_produto
					])
				->setHeader('header_side')
				->setFooter('footer_side')
				->setTemplate(true)
				->setTitle('SIDE | Produtos');



	}

	public function indexAction($pagina = 1)
	{
		
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
							->setFile('listar'); # Redirecinando para página de listagem

				$this->load('Helpers\Alert', array(
					'success',
					'O produto ' . $cadastrarProduto->product_description . ' foi cadastrado no sistema!'
				));
			}
		}
	}

	public function cadastrarProdutoAction()
	{
		$this->view->setFile('cadastrar');
	}

	public function listarAction($pagina = 1)
	{
		$get = $pagina;

		if(!empty($get)) {
			$listarProduto = Product::listar($get);

			$anterior = $listarProduto['anterior'];
			$proximo = $listarProduto['proximo'];
			$pagina = $listarProduto['pagina'];
			$total_paginas = $listarProduto['total_paginas'];
			$total_produtos = $listarProduto['total_produtos'];
			$primeiro_produto = $listarProduto['primeiro_produto'] + 1;
			$products = $listarProduto['registros'];

			$this->view->setVars([
					'products' => $products,
					'anterior' => $anterior,
					'proximo' => $proximo,
					'pagina' => $pagina,
					'total_paginas' => $total_paginas,
					'total_produtos' => $total_produtos,
					'primeiro_produto' => $primeiro_produto
					])
					->setFile('listar');
		}
	}

	public function ImportarPlanilhaAction()
	{
		$file = $_FILES['file'];
		$linhas = null;
		$colunas = null;
		$planilha = null;
		
		if(!empty($file) && isset($file) && file_exists($file['tmp_name'])) {
			$planilha = new SimpleXLSX($file['tmp_name']);
			list($colunas, $linhas) = $planilha->dimension();
		}
		else {
			echo 'Arquivo não encontrado!';
			exit();
		}

		try{
			$matriz = array();
			$titulo = array();

			foreach($planilha->rows() as $linha => $valor):
				if ($linha == 0){
					$titulo = $valor;
				}
				if ($linha >= 1):

					$matriz[$linha-1] = $valor;
					
				endif;
			endforeach;

			$this->view->setVars([
					'titulo' => $titulo,
					'dados' => $matriz,
					'colunas' => $colunas,
					'linhas' => $linhas
					])
					->setFile('planilha');

		}catch(Exception $erro){
			echo 'Erro: Não foi possível fazer o tratamento da planilha (' . $erro->getMessage() . ')';
		}
	}

	public function inserirDadosPlanilhaAction()
	{
		$this->view->setFile('planilha');

		$user_id = $this->auth->getUserId(); // Obtendo atributos do usuário

		$post = $this->request->post();

		$nomeTitulo = array();

		for ($i=0; $i < $post['total_colunas']; $i++) :
			$nomeTitulo[$i] = $post['select' . $i];
		endfor;

		$matrizOriginal = $this->session->get('dadosPlanilha'); // Capturando Sessão com array de dados

		$inserirDados = Product::inserirDadosPlanilha($post, $nomeTitulo, $matrizOriginal, $user_id);

		if ($inserirDados->status === false) {
			
		}
		else {
			$this->view->setVar('products', Product::all())
						->setFile('listar'); # Redirecinando para página de listagem

			$this->load('Helpers\Alert', array(
				'success',
				'Foram cadastrados ' . $inserirDados->products_quantity . ' com sucesso!'
			));
		}
	}
}

