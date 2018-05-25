<?php

class Indicator extends \HXPHP\System\Model
{
	public static function gerarIndicadores($user_id, $product_id, $date)
	{
		$callbackObj = new \stdClass; // Atribuindo classe vazio do framework
		$callbackObj->status = false;
		$callbackObj->errors = array();
		$callbackObj->indicators = array();

		$product = Product::find($product_id);
		$parameters = Parameter::all(array('conditions' => "product_id = $product_id and date LIKE '%$date%'"));
		
		if (!is_null($parameters)) {

			// Variaveis  (indicadores)
			$giroEstoque = null;
			$coberturaEstoque = null;
			$estoqueMinimo = null;
			$pontoDePedido = null;
			$loteDeReposicao = null;

			// Variaveis parâmetros
			$totalVendas = null;
			$mediaEstoque = null;

			// Variaveis de inserção no Banco
			$registrarIndicadores = null;

			// Variaveis de Atualização do banco
			$product_indicator_exists = self::find_by_product_id_and_date($product_id, $parameters[0]->date);
			
			##########################################################
			######## ------- Cálculos dos Indicadores ------- ########
			##########################################################

			### GIRO DE ESTOQUE ###
			$totalVendas = $parameters[0]->quantidade_vendida;
			$mediaEstoque = (($parameters[0]->estoque_atual + $parameters[0]->quantidade_vendida) / 2);

			if ($totalVendas == null || $mediaEstoque == null) {
				$callbackObj->errors = 'Não foram oferecidos os parametros necessários.';
							
			}
			else {
				$giroEstoque = number_format(($totalVendas / $mediaEstoque), 2, '.', ','); // Fazendo cálculo do giro de estoque
			}

			### COBERTURA DE ESTOQUE ###
			$arrayData = explode('-', strftime('%Y-%m-%d', strtotime($parameters[0]->date)));
			$diasNoMes = cal_days_in_month(0, $arrayData[1], $arrayData[0]); // Captura a quantidade de dias do mês atual

			if ($giroEstoque == null) {
				$callbackObj->errors = 'Não foram oferecidos os parametros necessários.';
			}
			else {
				$coberturaEstoque = ($diasNoMes / $giroEstoque); // Fazendo cálculo da cobertura de estoque
			}

			### ESTOQUE MÍNIMO ###
			if ($parameters[0]->demanda_media == null || $parameters[0]->tempo_reposicao == null) {
				$callbackObj->errors = 'Não foram oferecidos os parametros necessários.';
			}
			else {
				$estoqueMinimo = (($parameters[0]->demanda_media / $parameters[0]->tempo_reposicao) * $parameters[0]->tempo_reposicao); // Fazendo cálculo do estoque mínimo
				$estoqueMinimo = number_format($estoqueMinimo, 2);
			}

			### PONTO DE REPOSIÇÃO ###
			if ($estoqueMinimo == null || $estoqueMinimo == 0) {
				$callbackObj->errors = 'Não foram oferecidos os parametros necessários.';
			}
			else {
				$pontoDePedido = 2 * intval($estoqueMinimo);
			}

			### LOTE DE REPOSIÇÃO ###
			if ($parameters[0]->demanda_media == null || $parameters[0]->freq_compra == null) {
				$callbackObj->errors = 'Não foram oferecidos os parâmetros necessário.';
			}
			else {
				$loteDeReposicao = ($parameters[0]->demanda_media / $parameters[0]->freq_compra);
				$loteDeReposicao = number_format($loteDeReposicao, 2);
			}

			// Montando array de inserção
			$array_indicators = [
				'user_id'  => $user_id,
				'product_id'  => $product_id,
				'giro_estoque' => $giroEstoque,
				'cobertura_estoque' => intval($coberturaEstoque),
				'estoque_minimo' => $estoqueMinimo,
				'ponto_reposicao' => $pontoDePedido,
				'lote_reposicao' => $loteDeReposicao,
				'date' => $parameters[0]->date	
			];

			if (!is_null($product_indicator_exists)) {
				$product_indicator_exists->giro_estoque = $array_indicators['giro_estoque'];
				$product_indicator_exists->cobertura_estoque = $array_indicators['cobertura_estoque'];
				$product_indicator_exists->estoque_minimo = $array_indicators['estoque_minimo'];
				$product_indicator_exists->ponto_reposicao = $array_indicators['ponto_reposicao'];
				$product_indicator_exists->lote_reposicao = $array_indicators['lote_reposicao'];
				
				$atualizarIndicadores = $product_indicator_exists->save(false); // Atualizando dado atual no Banco

				$callbackObj->status = true;
				$callbackObj->indicators['giro_estoque'] = $array_indicators['giro_estoque'];
				$callbackObj->indicators['cobertura_estoque'] = $array_indicators['cobertura_estoque'];

				if (!is_null($array_indicators['estoque_minimo'])) :
					$callbackObj->indicators['estoque_minimo'] = $array_indicators['estoque_minimo'];
				else :
					$callbackObj->indicators['estoque_minimo'] = 'Não foram oferecidos os parâmetros necessário.';
				endif;

				if (!is_null($array_indicators['ponto_reposicao'])) :
					$callbackObj->indicators['ponto_reposicao'] = $array_indicators['ponto_reposicao'];
				else :
					$callbackObj->indicators['ponto_reposicao'] = 'Não foram oferecidos os parâmetros necessário.';
				endif;

				if (!is_null($array_indicators['lote_reposicao'])) :
					$callbackObj->indicators['lote_reposicao'] = $array_indicators['lote_reposicao'];
				else :
					$callbackObj->indicators['lote_reposicao'] = 'Não foram oferecidos os parâmetros necessário.';
				endif;

				return $callbackObj;
			}
			else {
				$registrarIndicadores = self::create($array_indicators); // Inserindo dado no Banco 

				$callbackObj->status = true;
				$callbackObj->indicators['giro_estoque'] = $array_indicators['giro_estoque'];
				$callbackObj->indicators['cobertura_estoque'] = $array_indicators['cobertura_estoque'];
				
				if (!is_null($array_indicators['estoque_minimo'])) :
					$callbackObj->indicators['estoque_minimo'] = $array_indicators['estoque_minimo'];
				else :
					$callbackObj->indicators['estoque_minimo'] = 'Não foram oferecidos os parâmetros necessário.';
				endif;

				if (!is_null($array_indicators['ponto_reposicao'])) :
					$callbackObj->indicators['ponto_reposicao'] = $array_indicators['ponto_reposicao'];
				else :
					$callbackObj->indicators['ponto_reposicao'] = 'Não foram oferecidos os parâmetros necessário.';
				endif;

				if (!is_null($array_indicators['lote_reposicao'])) :
					$callbackObj->indicators['lote_reposicao'] = $array_indicators['lote_reposicao'];
				else :
					$callbackObj->indicators['lote_reposicao'] = 'Não foram oferecidos os parâmetros necessário.';
				endif;

				return $callbackObj;
			}

		} else {	
			echo "não há vendas registradas desse produto!";
			die();
		}
	}

	public static function listar($user_id, $pagina = 1)
	{
		if (!isset($pagina)) {
			$pagina = 1;
		}
		
		$exib_limit = 10;
		$first_indicator = $pagina - 1; 
		$first_indicator = $first_indicator * $exib_limit;

		$all_rgs = self::find('all', array('conditions' => array('user_id' => $user_id), 'order' => 'date'));
		$all_rgs_by_page = self::find('all', array('limit' => $exib_limit, 'offset' => $first_indicator, 'conditions' => array('user_id' => $user_id), 'order' => 'date desc'));
		$consultaProdutos = Product::find('all');
		$parametrosData = Parameter::find('all', array('select' => 'DISTINCT(left(date,7)) as data', 'order' => 'date desc'));
			
		$total_registros = count($all_rgs); // verifica o número total de registros
		$total_registros_por_pagina = count($all_rgs_by_page); // verifica o número total de registros
		$total_produtos = count($consultaProdutos); // verifica o número total de registros [Produtos]
		$total_paginas = ceil($total_registros / $exib_limit); // verifica o número total de páginas
		
		$anterior = $pagina - 1; 
		$proximo = $pagina + 1;

		$array_tabela = array();

		for ($i=0; $i < $total_registros_por_pagina; $i++) {
			for ($j=0; $j < $total_produtos; $j++) { 
				if ($consultaProdutos[$j]->id == $all_rgs_by_page[$i]->product_id) {
					$array_tabela[$i]['codigo_interno'] = $consultaProdutos[$j]->internal_code;
					$array_tabela[$i]['description'] = $consultaProdutos[$j]->description;
					$array_tabela[$i]['giro_estoque'] = $all_rgs_by_page[$i]->giro_estoque;
					$array_tabela[$i]['cobertura_estoque'] = $all_rgs_by_page[$i]->cobertura_estoque;
					$array_tabela[$i]['estoque_minimo'] = $all_rgs_by_page[$i]->estoque_minimo;
					$array_tabela[$i]['ponto_reposicao'] = $all_rgs_by_page[$i]->ponto_reposicao;
					$array_tabela[$i]['lote_reposicao'] = $all_rgs_by_page[$i]->lote_reposicao;
					$array_tabela[$i]['date'] = $all_rgs_by_page[$i]->date;
				}
			 } 
		}

		$dados = [
			'anterior' => $anterior,
			'proximo' => $proximo,
			'pagina' => $pagina,
			'total_paginas' => $total_paginas,
			'total_produtos' => $total_registros,
			'primeiro_produto' => $first_indicator,
			'registros' => $array_tabela,
			'datas' => $parametrosData
		];

		return $dados;
	}
}