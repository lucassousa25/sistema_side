<?php

class Sell extends \HXPHP\System\Model
{
	public function cadastrar(array $post, $user_id)
	{
		$callbackObj = new \stdClass; // Atribuindo classe vazio do framework
		$callbackObj->user = null;
		$callbackObj->status = false;
		$callbackObj->errors = array();

		$data_venda = date('Y-m-d H:i:s');

		$quantidade = intval($post['quantity']);
		$valor_produto = str_replace(',', '.', $post['valor_produto']);
		$valor_produto = floatval($valor_produto);

		$total_venda = $quantidade * $valor_produto;

		$array_venda = [
			'user_id' => $user_id,
			'product_id' => intval($post['product_id']),
			'quantity' => $quantidade,
			'total' => $total_venda,
			'date_sell' => $data_venda
		];

		$cadastrar_venda = self::create($array_venda);

		if($cadastrar_venda->is_valid()) {
			$callbackObj->user = $cadastrar_venda;
			$callbackObj->status = true;

			$product = Product::find_by_id($post['product_id']);
			
			$product->est_atual -= $array_venda['quantity'];

			$product->save(false);

			return $callbackObj;
		}

		$errors = $cadastrar->errors->get_raw_errors(); 
		
		foreach ($errors as $field => $message) {
			array_push($callbackObj->errors, $message[0]);
		}
		return $callbackObj;
	}

	public static function listar($pagina = 1)
	{
		if (!isset($pagina)) {
			$pagina = 1;
		}
		
		$exib_vendas = 10;
		$primeiro_registro = $pagina - 1; 
		$primeiro_registro = $primeiro_registro * $exib_vendas;

		$all_rgs = self::find('all');
		$consulta = self::find('all', array('limit' => $exib_vendas, 'offset' => $primeiro_registro));
		$consultaProdutos = Product::find('all');
			
		$total_registros = count($all_rgs); // verifica o número total de registros [Vendas]
		$total_produtos = count($consultaProdutos); // verifica o número total de registros [Produtos]
		$total_paginas = ceil($total_registros / $exib_vendas); // verifica o número total de páginas
		
		$anterior = $pagina - 1; 
		$proximo = $pagina + 1;
		
		$array_tabela = array();

		for ($i=0; $i < $total_registros; $i++) {
			for ($j=0; $j < $total_produtos; $j++) { 
				if ($consultaProdutos[$j]->id == $all_rgs[$i]->product_id) {
					$array_tabela[$i]['codigo_interno'] = $consultaProdutos[$j]->internal_code;
					$array_tabela[$i]['description'] = $consultaProdutos[$j]->description;
					$array_tabela[$i]['value'] = $consultaProdutos[$j]->value;
					$array_tabela[$i]['quantity'] = $all_rgs[$i]->quantity;
					$array_tabela[$i]['total'] = $all_rgs[$i]->total;
					$array_tabela[$i]['date_sell'] = $all_rgs[$i]->date_sell;
				}
			 } 
		}

		$dados = [
			'anterior' => $anterior,
			'proximo' => $proximo,
			'pagina' => $pagina,
			'total_paginas' => $total_paginas,
			'total_vendas' => $total_registros,
			'primeira_venda' => $primeiro_registro,
			'registros' => $array_tabela
		];

		return $dados;
	}
}