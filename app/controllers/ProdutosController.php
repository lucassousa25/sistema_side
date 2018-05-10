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

		if (isset($_FILES['file'])) {
			$file = $_FILES['file'];
		}
		else {	
			$arquivo_existente = end(scandir(ROOT_PATH . 'public/uploads/sheets'));
			$file['name'] = $arquivo_existente;
			$file['type'] = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
			$file['tmp_name'] = ROOT_PATH . 'public/uploads/sheets/' . $arquivo_existente;
			$file['error'] = 0;
			$file['size'] = filesize(ROOT_PATH . 'public/uploads/sheets/' . $arquivo_existente);
		}

		// Pasta onde o arquivo vai ser salvo
		$_UP['pasta'] = ROOT_PATH . 'public/uploads/sheets/';
		 
		// Tamanho máximo do arquivo (em Bytes)
		$_UP['tamanho'] = 1024 * 1024 * 1; // 1Mb
		 
		// Array com as extensões permitidas
		$_UP['extensoes'] = array('xlsx');
		 
		// Renomeia o arquivo? (Se true, o arquivo será salvo como .xlsx e um nome único)
		$_UP['renomeia'] = false;

		// Array com os tipos de erros de upload do PHP
		$_UP['erros'][0] = 'Não houve erro';
		$_UP['erros'][1] = 'O arquivo no upload é maior do que o limite do PHP';
		$_UP['erros'][2] = 'O arquivo ultrapassa o limite de tamanho especificado no sistema';
		$_UP['erros'][3] = 'O upload do arquivo foi feito parcialmente';
		$_UP['erros'][4] = 'Não foi feito o upload do arquivo. Tente novamente!';
		 
		// Verifica se houve algum erro com o upload. Se sim, exibe a mensagem do erro
		if ($file['error'] != 0) {
			$this->load('Helpers\Alert', array(
				'error',
				'Não foi possível fazer o upload:',
				$_UP['erros'][$file['error']]
			));

			$this->view->setFile('listar');
		}
		// Faz a verificação da extensão do arquivo
		elseif (array_search(strtolower(end(explode('.', $file['name']))), $_UP['extensoes']) === false) {
			
			$this->load('Helpers\Alert', array(
				'error',
				'Ocorreu um erro ao importar o arquivo!',
				'Por favor, envie um arquivo na extensão .xlsx'
			));

			$this->view->setFile('listar');
		}
		elseif ($_UP['tamanho'] < $file['size']) {
			$this->load('Helpers\Alert', array(
				'warning',
				'Ocorreu um erro ao importar o arquivo!',
				'O arquivo enviado é muito grande, envie arquivos de até 1Mb.'
			));

			$this->view->setFile('listar');
		}
		else {
			// Primeiro verifica se deve trocar o nome do arquivo
			if ($_UP['renomeia'] == true) {
				// Cria um nome baseado no UNIX TIMESTAMP atual e com extensão .jpg
				$nome_final = time().'.xlsx';
			} else {
				// Mantém o nome original do arquivo
				$nome_final = $file['name'];
			}

			// Definindo variáveis para o preparo da Planilha
			$linhas = null;
			$colunas = null;
			$planilha = null;

			if (!file_exists($_UP['pasta'] . $nome_final)) {
				move_uploaded_file($file['tmp_name'], $_UP['pasta'] . $nome_final);

				if(!empty($file) && isset($file) && file_exists($_UP['pasta'] . $nome_final)) {

					$planilha = SimpleXLSX::parse($_UP['pasta'] . $nome_final);
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
			else {
				if(!empty($file) && isset($file) && file_exists($_UP['pasta'] . $nome_final)) {

					$planilha = SimpleXLSX::parse($_UP['pasta'] . $nome_final);
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
					'Foram cadastrados ' . $inserirDados->products_quantity . ' produto(s) com sucesso!'
				));

				$arquivo_existente = end(scandir(ROOT_PATH . 'public/uploads/sheets'));
				unlink(ROOT_PATH . 'public/uploads/sheets/' . $arquivo_existente);
				self::listarAction();
			endif;
			
			if(is_null($inserirDados->products_quantity) && !is_null($inserirDados->products_quantity_errors)) :
				$this->load('Helpers\Alert', array(
					'error',
					'Não foram cadastrados produtos!\n' .
					'Total de ' . $inserirDados->products_quantity_errors . ' produtos não cadastrados. Verifique os erros abaixo:',
					$inserirDados->errors
				));

				self::ImportarPlanilhaAction();
			endif;
			if(!is_null($inserirDados->products_quantity) && !is_null($inserirDados->products_quantity_errors)) :
				$this->load('Helpers\Alert', array(
					'info',
					'Foram cadastrados ' . $inserirDados->products_quantity . ' produto(s) com sucesso!\n' .
					'Total de ' . $inserirDados->products_quantity_errors . ' produto(s) não cadastrados. Verifique os erros abaixo:',
					$inserirDados->errors
				));

				self::listarAction();
			endif;

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

