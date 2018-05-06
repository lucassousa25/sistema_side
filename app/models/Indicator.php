<?php

class Indicator extends \HXPHP\System\Model
{
	public static function gerarIndicadores($product_id)
	{
		$callbackObj = new \stdClass; // Atribuindo classe vazio do framework
		$callbackObj->user = null;
		$callbackObj->status = false;
		$callbackObj->errors = array();
		$callbackObj->indicators = array();

		$product = Product::find($product_id);
		$sell = Sell::all(array('conditions' => array('product_id' => $product_id)));
		
		if (!is_null($sell)) {

			$totalVendas = null;
			$mediaEstoque = null;
			$mediaVendas = null;
			$giroEstoque = null;
			$coberturaEstoque = null;

			// Variaveis de inserção no Banco
			$registrarGiro = null;
			$registrarCobertura = null;

			// Variaveis de Atualização do banco
			$product_indicator_exists_giro = self::find_by_product_id_and_description($product_id, 'Giro de Estoque');
			$product_indicator_exists_cobertura = self::find_by_product_id_and_description($product_id, 'Cobertura de Estoque');

			$dateInsert = date('Y-m-d H:i:s');

			foreach ($sell as $linha) {
				if (date_format($linha->date_sell, 'm') == date('m')) // Verifica se está no mês atual
					$totalVendas += $linha->quantity;
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

			$array_indicator = [
				'product_id'  => null,
				'description' => null,
				'value'		  => null,
				'date_insert' => null	
			];

			if (!empty($giroEstoque)) {
				$array_indicator['product_id'] = $product_id;
				$array_indicator['description'] = 'Giro de Estoque';
				$array_indicator['value'] = $giroEstoque;
				$array_indicator['date_insert'] = $dateInsert;

				if (!is_null($product_indicator_exists_giro)) {
					$product_indicator_exists_giro->value = $giroEstoque;
					$product_indicator_exists_giro->date_insert = $dateInsert;
					
					$atualizarGiro = $product_indicator_exists_giro->save(false);
				}
				else {
					$registrarGiro = self::create($array_indicator);	
				}
				
			}

			if (!empty($coberturaEstoque)) {
				$array_indicator['product_id'] = $product_id;
				$array_indicator['description'] = 'Cobertura de Estoque';
				$array_indicator['value'] = intval($coberturaEstoque);
				$array_indicator['date_insert'] = $dateInsert;

				if (!is_null($product_indicator_exists_cobertura)) {
					$product_indicator_exists_cobertura->value = intval($coberturaEstoque);
					$product_indicator_exists_cobertura->date_insert = $dateInsert;
					
					$atualizarCobertura = $product_indicator_exists_cobertura->save(false);
				}
				else {
					$registrarCobertura = self::create($array_indicator);
				}

			}

			if(!is_null($registrarGiro) && !is_null($registrarCobertura)) {
				//$callbackObj->user = $coberturaEstoque;
				$callbackObj->status = true;
				$callbackObj->indicators['giro_estoque'] = $giroEstoque;
				$callbackObj->indicators['cobertura_estoque'] = intval($coberturaEstoque);

				return $callbackObj;
			}
			elseif (!is_null($product_indicator_exists_giro) && !is_null($product_indicator_exists_cobertura)) {
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
}