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

	public function geraIndicadorAction(int $product_id = 1)
	{
		if (intval($product_id)) {
			$registrarIndicadores = Indicator::gerarIndicadores($product_id);

			if ($registrarIndicadores->status === false) {
				$this->load('Helpers\Alert', array(
					'error',
					'Não foi possível gerar os Indicadores. Verifique os erros abaixos:',
					$cadastrarProduto->errors
				));
			}
			else {
				$this->view->setVar('products', Product::find('all', array('limit' => '10', 'offset' => '0')))
							->setFile('listar'); # Redirecinando para página de listagem

				$this->load('Helpers\Alert', array(
					'success',
					'Giro de Estoque: O estoque girou ' . $registrarIndicadores->indicators['giro_estoque'] . 
					'\n Cobertura de Estoque: O Estoque cobrirá ' . $registrarIndicadores->indicators['cobertura_estoque'] . ' dias.'
				));
			}
		}
	}
}