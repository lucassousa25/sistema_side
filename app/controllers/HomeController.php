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

		$produtos_abaixo_est_minimo = null;
		$produtos_abaixo_ponto_reposicao = null;
		$produtos_prox_ponto_reposicao = null;

		$maiorData = Parameter::find('first', array('select' => 'DISTINCT(left(date,7)) as data', 'order' => 'date desc')); // Capturando data mais atual
		if(!is_null($maiorData)) :
			$produtos_est_minimo = Indicator::all(array('select' => 'product_id, estoque_minimo, ponto_reposicao', 'conditions' => "date LIKE '%$maiorData->data%'"));
		
			$produtos_est_atual = Parameter::all(array('select' => 'product_id, estoque_atual', 'conditions' => "date LIKE '%$maiorData->data%'"));
			
			foreach ($produtos_est_minimo as $linha1) :
				foreach ($produtos_est_atual as $linha2) {
					if ($linha2->product_id == $linha1->product_id) {

						if ($linha2->estoque_atual < $linha1->estoque_minimo) {
							$produtos_abaixo_est_minimo++; // Guardando produtos abaixo do estoque mínimo
						}
						elseif (($linha2->estoque_atual >= $linha1->estoque_minimo) && ($linha2->estoque_atual < $linha1->ponto_reposicao)) {
							$produtos_abaixo_ponto_reposicao++; // Guardando produtos abaixo do ponto de pedido
						}
						elseif (($linha2->estoque_atual >= $linha1->ponto_reposicao) && ($linha2->estoque_atual < ($linha1->ponto_reposicao + ($linha1->ponto_reposicao * 0.2)))) {
						 	$produtos_prox_ponto_reposicao++; // Guardando produtos próximo do ponto de pedido
						}

					}
				}
			endforeach;
		endif;


		$datasExistentes = Parameter::find('all', array('select' => 'DISTINCT(left(date,7)) as data', 'limit' => 6, 'order' => 'date desc'));
		$totalVendas = array();
		$datas = array();

		foreach ($datasExistentes as $linha => $valor) { 
			$valores_vendas = Parameter::find(array('select' => 'SUM(total_vendas) as total_vendas, date as data', 'conditions' => "date LIKE '%$valor->data%'"));
			$totalVendas[$linha] = $valores_vendas->total_vendas; // Guardando o total de vendas por mÊs
			$datas[$linha] = $valores_vendas->data; // Guardando datas cadastradas [mês/Ano]
		}

		$produtos_mais_vendidos = array();	

		if(!is_null($maiorData)) :
			$valores_mais_vendidos = Parameter::all(array('select' => 'product_id, total_vendas', 'limit' => 10, 'conditions' => "date LIKE '%$maiorData->data%'", 'order' => 'total_vendas desc')); // Capturando os 10 maiores faturamentos de produtos no ultimo mês
			$produtos = Product::all();


			for ($i=0; $i < count($produtos); $i++) { 
				for ($j=0; $j < count($valores_mais_vendidos); $j++) { 
					if ($produtos[$i]->id == $valores_mais_vendidos[$j]->product_id) { 
						# GUARDANDO VALORES EM ARRAY #
						$produtos_mais_vendidos[$j]['descricao'] = $produtos[$i]->description;
						$produtos_mais_vendidos[$j]['valores_venda'] = $valores_mais_vendidos[$j]->total_vendas;
					}
				}
			}

			ksort($produtos_mais_vendidos); // Reordenando por indice
		endif;

		// Enviando dados para a view
		$this->view->setFile('index')
               ->setVars([
               		'produtos_abaixo_est_minimo' => $produtos_abaixo_est_minimo,
               		'produtos_abaixo_ponto_reposicao' => $produtos_abaixo_ponto_reposicao,
               		'produtos_prox_ponto_reposicao' => $produtos_prox_ponto_reposicao,
               		'valores_vendas' => $totalVendas,
               		'produtos_mais_vendidos' => $produtos_mais_vendidos,
               		'datas_dados' => $datas
               		]);
	}
}