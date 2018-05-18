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

		$listarIndicadores = Indicator::listar($user_id);

		$this->view->setVars([
						'user' => $user,
						'products' => $listarIndicadores['registros'],
						'anterior' => $listarIndicadores['anterior'],
						'proximo' => $listarIndicadores['proximo'],
						'pagina' => $pagina = $listarIndicadores['pagina'],
						'total_paginas' => $listarIndicadores['total_paginas'],
						'total_produtos' => $listarIndicadores['total_produtos'],
						'primeiro_produto' => $listarIndicadores['primeiro_produto'] + 1
					])
				->setHeader('header_side')
				->setFooter('footer_side')
				->setTemplate(true)
				->setTitle('SIDE | Indicadores');
	}

	public function indexAction()
	{
		$this->view->setFile('listar');
	}

	public function geraIndicadorAction($product_id = 1, $date = null)
	{
		$user_id = $this->auth->getUserId(); // Obtendo atributos do usuário

		if (is_numeric($product_id)) {
			$registrarIndicadores = Indicator::gerarIndicadores($user_id, $product_id, $date);

			if ($registrarIndicadores->status === false) {
				$this->load('Helpers\Alert', array(
					'error',
					'Não foi possível gerar os Indicadores. Verifique os erros abaixos:',
					$registrarIndicadores->errors
				));

				$this->view->setFile('listar');
				
			}
			else {
				## Exibindo alert com informações dos indicadores ##
				$this->load('Helpers\Alert', array(
					'info',
					'Verifique as informações abaixo:',
					'Giro de Estoque: O estoque girou ' . $registrarIndicadores->indicators['giro_estoque'] . ' vez(es).' .
					'\n Cobertura de Estoque: O Estoque cobrirá ' . $registrarIndicadores->indicators['cobertura_estoque'] . ' dia(s).' .
					'\n Estoque Mínimo: ' . $registrarIndicadores->indicators['estoque_minimo'] .
					'\n Ponto de Pedido: ' . $registrarIndicadores->indicators['ponto_reposicao'] .
					'\n Lote de Reposição: ' . $registrarIndicadores->indicators['lote_reposicao']
				));

				self::listarAction();
			}
		}
	}

	public function gerarTodosIndicadoresAction($date = null)
	{
		$user_id = $this->auth->getUserId(); // Obtendo atributos do usuário

		if (!is_null($date)) {
			$allProductsDate = Parameter::all(array('conditions' => "date LIKE '$date'"));
			
			foreach ($allProductsDate as $linha) :
				$registrarIndicadoresProduto = Indicator::gerarIndicadores($user_id, $linha->product_id, $date);
			endforeach;

			$this->load('Helpers\Alert', array(
				'info',
				'Verifique as informações abaixo:',
				'A tabela exibe os indicadores gerado de cada produto. Os valores em branco significa falta de parâmetros necessários.'
			));

			self::listarAction();
		}
	}

	public function listarAction($pagina = 1)
	{
		$user_id = $this->auth->getUserId(); // Obtendo atributos do usuário

		if(!empty($pagina)) {
			$listarIndicadores = Indicator::listar($user_id, $pagina);

			$this->view->setVars([
					'products' => $listarIndicadores['registros'],
					'anterior' => $listarIndicadores['anterior'],
					'proximo' => $listarIndicadores['proximo'],
					'pagina' => $listarIndicadores['pagina'],
					'total_paginas' => $listarIndicadores['total_paginas'],
					'total_produtos' => $listarIndicadores['total_produtos'],
					'primeiro_produto' => $listarIndicadores['primeiro_produto'] + 1
					])
					->setFile('listar');
		}
	}
}