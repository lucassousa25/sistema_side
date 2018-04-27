<?php

class Indicator extends \HXPHP\System\Model
{
	public static function gerarIndicadores(int $product_id)
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

			foreach ($sell as $linha) {
				if (date_format($linha->date_sell, 'm') == date('m')) // Verifica se está no mês atual
					$totalVendas += $linha->quantity;
			}
			
			$mediaEstoque = (($product->est_inicial + $product->est_atual) / 2);
			$giroEstoque = number_format(($totalVendas / $mediaEstoque), 2, ',', '.');
			$callbackObj->indicators['giro_estoque'] = $giroEstoque;

			$mediaVendas = number_format(($totalVendas / date('d')), 1, '.', ',');
			$coberturaEstoque = number_format(($product->est_atual / $mediaVendas), 2, ',', '.');
			$callbackObj->indicators['cobertura_estoque'] = $coberturaEstoque;
			
			var_dump($callbackObj->indicators);
			die();

		} else {	
			echo "não há vendas registradas desse produto!";
			die();
		}

		
	}
}