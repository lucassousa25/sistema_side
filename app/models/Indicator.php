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
			
			var_dump(date('d'));
			var_dump($mediaEstoque);
			die();

			$mediaVendas = number_format(($totalVendas / date('d')), 1, '.', ',');

			if ($mediaVendas == 0 || $product->est_atual == 0) {
				$errors = array('description' => array('0' => 'Não foi registradas vendas desse produto.'));
		
				foreach ($errors as $field => $message) {
					array_push($callbackObj->errors, $message[0]);
				}
				return $callbackObj;
			}
			else {
				$coberturaEstoque = number_format(($product->est_atual / $mediaVendas), 2, ',', '.');
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
				
				$registrarGiro = self::create($array_indicator);	
			}


			if (!empty($coberturaEstoque)) {
				$array_indicator['product_id'] = $product_id;
				$array_indicator['description'] = 'Cobertura de Estoque';
				$array_indicator['value'] = intval($coberturaEstoque);
				$array_indicator['date_insert'] = $dateInsert;

				$registrarCobertura = self::create($array_indicator);
			}

			if($registrarGiro->is_valid() && $registrarCobertura->is_valid()) {
				$callbackObj->user = $coberturaEstoque;
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