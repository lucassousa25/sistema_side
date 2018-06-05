<?php

class HomeController extends \HXPHP\System\Controller
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

		$listarProduto = Product::listar($user_id);

		$this->view->setFile('index')
               ->setHeader('header_side')
               ->setFooter('footer_side')
               ->setTemplate(true)
               ->setTitle('SIDE | Home')
               ->setVars([
               		'user' => $user,
               		'datas' => $listarProduto['datas']
               		]);
	}

	public function indexAction()
	{	
		$this->view->setFile('index');

		$maiorData = Parameter::find('first', array('select' => 'DISTINCT(left(date,7)) as data', 'order' => 'date desc'));
		$produtos_est_minimo = Indicator::all(array('select' => 'product_id, estoque_minimo, ponto_reposicao', 'conditions' => "date LIKE '%$maiorData->data%'"));
		$produtos_est_atual = Parameter::all(array('select' => 'product_id, estoque_atual', 'conditions' => "date LIKE '%$maiorData->data%'"));

		$produtos_abaixo_est_minimo = null;
		$produtos_abaixo_ponto_reposicao = null;
		$produtos_prox_ponto_reposicao = null;

		foreach ($produtos_est_minimo as $linha1) :
			foreach ($produtos_est_atual as $linha2) {
				if ($linha2->product_id == $linha1->product_id) {

					if ($linha2->estoque_atual < $linha1->estoque_minimo) {
						$produtos_abaixo_est_minimo++;
					}
					elseif (($linha2->estoque_atual >= $linha1->estoque_minimo) && ($linha2->estoque_atual < $linha1->ponto_reposicao)) {
						$produtos_abaixo_ponto_reposicao++;
					}
					elseif (($linha2->estoque_atual >= $linha1->ponto_reposicao) && ($linha2->estoque_atual < ($linha1->ponto_reposicao + ($linha1->ponto_reposicao * 0.2)))) {
					 	$produtos_prox_ponto_reposicao++;
					}

				}
			}
		endforeach;

		$this->view->setFile('index')
               ->setVars([
               		'produtos_abaixo_est_minimo' => $produtos_abaixo_est_minimo,
               		'produtos_abaixo_ponto_reposicao' => $produtos_abaixo_ponto_reposicao,
               		'produtos_prox_ponto_reposicao' => $produtos_prox_ponto_reposicao
               		]);
	}
}