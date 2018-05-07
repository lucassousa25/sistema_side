<?php

class Indicator extends \HXPHP\System\Model
{
	public static function gerarIndicadores($user_id, $product_id)
	{
		$callbackObj = new \stdClass; // Atribuindo classe vazio do framework
		$callbackObj->user = null;
		$callbackObj->status = false;
		$callbackObj->errors = array();
		$callbackObj->indicators = array();


		$product = Product::find($product_id);
		$sell = Sell::all(array('conditions' => array('product_id' => $product_id)));
		
		if (!is_null($sell)) {

			// Variaveis parâmetros e indicadores
			$totalVendas = null;
			$mediaEstoque = null;
			$mediaVendas = null;
			$giroEstoque = null;
			$coberturaEstoque = null;

			// Variaveis de inserção no Banco
			$registrarIndicadores = null;

			// Variaveis de Atualização do banco
			$product_indicator_exists = self::find_by_product_id($product_id);

			$dateInsert = date('Y-m-d H:i:s');

			foreach ($sell as $linha) {
				if (date_format($linha->date_sell, 'm') == date('m')) { // Verifica se está no mês atual
					$totalVendas += $linha->quantity;
				} 
			}
			

			$mediaEstoque = (($product->est_inicial + $product->est_atual) / 2);

			if ($totalVendas == 0 || $mediaEstoque == 0) {
				$errors = array('description' => array('0' => 'Não foi registradas vendas desse produto nesse mês.'));
		
				foreach ($errors as $field => $message) {
					array_push($callbackObj->errors, $message[0]);
				}
				return $callbackObj;
			}
			else {
				$giroEstoque = number_format(($totalVendas / $mediaEstoque), 2, '.', ',');
			}

			$mediaVendas = number_format(($totalVendas / date('d')), 1, '.', ',');
			$diasNoMes = cal_days_in_month(0, date('m'), date('y'));

			if ($mediaVendas == 0 || $giroEstoque == 0) {
				$errors = array('description' => array('0' => 'Não foi registradas vendas desse produto.'));
		
				foreach ($errors as $field => $message) {
					array_push($callbackObj->errors, $message[0]);
				}
				return $callbackObj;
			}
			else {
				$coberturaEstoque = ($diasNoMes / $giroEstoque);
			}

			$array_indicators = [
				'product_id'  => null,
				'user_id'  => null,
				'giro_estoque' => null,
				'cobertura_estoque' => null,
				'date_insert' => null	
			];

			if (!empty($giroEstoque) && !empty($coberturaEstoque)) {
				$array_indicators['product_id'] = $product_id;
				$array_indicators['user_id'] = $user_id;
				$array_indicators['giro_estoque'] = $giroEstoque;
				$array_indicators['cobertura_estoque'] = $coberturaEstoque;
				$array_indicators['date_insert'] = $dateInsert;

				if (!is_null($product_indicator_exists) && (date_format($product_indicator_exists->date_insert, 'm') == date('m')) && (date_format($product_indicator_exists->date_insert, 'y') == date('y'))) {
					$product_indicator_exists->giro_estoque = $giroEstoque;
					$product_indicator_exists->cobertura_estoque = $coberturaEstoque;
					$product_indicator_exists->date_insert = $dateInsert;
					
					$atualizarIndicadores = $product_indicator_exists->save(false);
				}
				else {
					$registrarIndicadores = self::create($array_indicators);	
				}
				
			}

			if(!is_null($registrarIndicadores)) {
				//$callbackObj->user = $coberturaEstoque;
				$callbackObj->status = true;
				$callbackObj->indicators['giro_estoque'] = $giroEstoque;
				$callbackObj->indicators['cobertura_estoque'] = intval($coberturaEstoque);

				return $callbackObj;
			}
			elseif (!is_null($product_indicator_exists)) {
				$callbackObj->status = true;
				$callbackObj->indicators['giro_estoque'] = $giroEstoque;
				$callbackObj->indicators['cobertura_estoque'] = intval($coberturaEstoque);

				return $callbackObj;
			}
			else {
				$errors = $cadastrar->errors->get_raw_errors(); 
		
				foreach ($errors as $field => $message) {
					array_push($callbackObj->errors, $message[0]);
				}
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

		$all_rgs = self::find('all', array('conditions' => array('user_id' => $user_id), 'order' => 'date_insert desc'));
		$all_rgs_by_page = self::find('all', array('limit' => $exib_limit, 'offset' => $first_indicator, 'conditions' => array('user_id' => $user_id), 'order' => 'date_insert desc'));
		$consultaProdutos = Product::find('all');
			
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
					$array_tabela[$i]['date_insert'] = $all_rgs_by_page[$i]->date_insert;
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
			'registros' => $array_tabela
		];

		return $dados;
	}
}