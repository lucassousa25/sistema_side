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

		Product::atualizaEstoque($user_id);

		$listarProduto = Product::listar($user_id);

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

	public function indexAction()
	{
		$this->view->setFile('listar');
	}

	public function cadastrarAction()
	{
		$this->view->setFile('cadastrar');
		
		$this->request->setCustomFilters(array(
			
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
				self::listarAction();

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

		$user_id = $this->auth->getUserId(); // Obtendo atributos do usuário

		if(!empty($get)) {
			$listarProduto = Product::listar($user_id, $get);

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
		if (isset($_FILES['file']))
			$file = $_FILES['file'];
		
		$linhas = null;
		$colunas = null;
		$planilha = null;

		if(!empty($file) && isset($file) && file_exists($file['tmp_name'])) {
			$planilha = new SimpleXLSX($file['tmp_name']);
			list($colunas, $linhas) = $planilha->dimension();
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

			}
			catch(Exception $erro){
				echo 'Erro: Não foi possível fazer o tratamento da planilha (' . $erro->getMessage() . ')';
			}
		}
		else {
			$this->load('Helpers\Alert', array(
				'warning',
				'Planilha não encontrada. Por favor tente novamente!'
			));

			$this->view->setFile('listar');
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

		if (isset($post) && !empty($post)) {
			$inserirDados = Product::inserirDadosPlanilha($post, $nomeTitulo, $matrizOriginal, $user_id);
			
			if (!is_null($inserirDados->products_quantity) && is_null($inserirDados->products_quantity_errors)) :
				$this->load('Helpers\Alert', array(
					'success',
					'Foram cadastrados ' . $inserirDados->products_quantity . ' com sucesso!'
				));
			endif;
			if(is_null($inserirDados->products_quantity) && !is_null($inserirDados->products_quantity_errors)) :
				$this->load('Helpers\Alert', array(
					'error',
					'Não foram cadastrados produtos!\n' .
					'Total de ' . $inserirDados->products_quantity_errors . ' produtos não cadastrados. Verifique os erros abaixo:',
					$inserirDados->errors
				));
			endif;
			if(!is_null($inserirDados->products_quantity) && !is_null($inserirDados->products_quantity_errors)) :
				$this->load('Helpers\Alert', array(
					'info',
					'Foram cadastrados ' . $inserirDados->products_quantity . 'produto(s) com sucesso!\n' .
					'Total de ' . $inserirDados->products_quantity_errors . ' produto(s) não cadastrados. Verifique os erros abaixo:',
					$inserirDados->errors
				));
			endif;

			self::listarAction();
		}
		else {
			$this->load('Helpers\Alert', array(
				'error',
				'Não foi possível receber os dados!\n' .
				'Tente enviar novamente.'
			));

			$this->view->setFile('listar');
		}
	}
}

