<?php

class IndicadoresController extends \HXPHP\System\Controller
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
				->setTitle('SIDE | Indicadores');
	}

	public function indexAction()
	{
		$this->view->setFile('listar');
	}

	public function geraIndicadorAction($product_id = 1)
	{
		$user_id = $this->auth->getUserId(); // Obtendo atributos do usuário

		if (is_numeric($product_id)) {
			$registrarIndicadores = Indicator::gerarIndicadores($product_id);

			if ($registrarIndicadores->status === false) {
				$this->load('Helpers\Alert', array(
					'error',
					'Não foi possível gerar os Indicadores. Verifique os erros abaixos:',
					$registrarIndicadores->errors
				));

				$this->view->setFile('listar');
				
			}
			else {
				## Configurando para retorno a listagem de produtos ##
				$user = User::find($user_id);

				$listarProduto = Product::listar($user_id);

				$anterior = $listarProduto['anterior'];
				$proximo = $listarProduto['proximo'];
				$pagina = $listarProduto['pagina'];
				$total_paginas = $listarProduto['total_paginas'];
				$total_produtos = $listarProduto['total_produtos'];
				$primeiro_produto = ($listarProduto['primeiro_produto'] + 1);
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
						->setTitle('SIDE | Produtos')
						->setPath('produtos')
						->setFile('listar');
				


				## Exibindo alert com informações dos indicadores ##
				$this->load('Helpers\Alert', array(
					'info',
					'Verifique as informações abaixo:',
					'Giro de Estoque: O estoque girou ' . $registrarIndicadores->indicators['giro_estoque'] . ' vez(es).' .
					'\n Cobertura de Estoque: O Estoque cobrirá ' . $registrarIndicadores->indicators['cobertura_estoque'] . ' dia(s).'
				));
			}
		}
	}
}