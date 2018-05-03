<?php

class Product extends \HXPHP\System\Model
{
	// Validando a presença dos campos (phpActiveRecord) 
	static $validates_presence_of = array(
		array(
			'description',
			'message' => 'A descrição é um campo Obrigatório!'
		),
		array(
			'value',
			'message' => 'O valor de venda é um campo Obrigatório!'
		),
		array(
			'est_minimo',
			'message' => 'O estoque mínimo é um campo Obrigatório!'
		),
		array(
			'est_atual',
			'message' => 'O estoque atual é um campo Obrigatório!'
		)
	);

	public static function cadastrar(array $post, $user_id)
	{
		$callbackObj = new \stdClass; // Atribuindo classe vazio do framework
		$callbackObj->user = null;
		$callbackObj->status = false;
		$callbackObj->errors = array();
		$callbackObj->product_description = null; // armazenando nome do produto para retorno no controller

		// user_id | description | internal_code | cost | value | est_inicial | est_minimo | est_maximo
		// est_atual | data_entrada | provider

		$user_id_array = [
			'user_id' => $user_id
		];


		$data_entrada = [
			'data_entrada' => date('Y-m-d H:i:s')
		];

		$post['cost'] = str_replace(',', '.', $post['cost']);
		$post['cost'] = floatval($post['cost']);
		$post['value'] = str_replace(',', '.', $post['value']);
		$post['value'] = floatval($post['value']);

		$post = array_merge($user_id_array, $post, $data_entrada);

		$validations = self::find_by_user_id_and_description($user_id, $post['description']);

		if(is_null($validations)) {
			$cadastrar = self::create($post);
			
			if ($cadastrar->is_valid()) {
				$callbackObj->user = $cadastrar;
				$callbackObj->status = true;
				$callbackObj->product_description = $post['description'];
				return $callbackObj;
			}

			$errors = $cadastrar->errors->get_raw_errors();
		
			foreach ($errors as $field => $message) {
				array_push($callbackObj->errors, $message[0]);
			}

			return $callbackObj;
		}
		else {
			$errors = array('description' => array('0' => 'Já existe um produto com descrição.'));
			
			foreach ($errors as $field => $message) {
				array_push($callbackObj->errors, $message[0]);
			}
			return $callbackObj;
		}
		
	}


	public static function inserirDadosPlanilha(array $post, array $nomeTitulo, array $matrizOriginal, $user_id)
	{
		$callbackObj = new \stdClass; // Atribuindo classe vazio do framework
		$callbackObj->user = null;
		$callbackObj->status = false;
		$callbackObj->errors = array(); // Array com mensagens de erro
		$callbackObj->products_quantity = null; // armazena a quantidade de produtos cadastrados para retorno no controller
		$callbackObj->products_quantity_errors = null; // armazena quantidade de produtos com falha no cadastrp para retorno no controller

		$user_id_array = [
			'user_id' => $user_id // Transformando user_id em array
		];

		$data_entrada = [
			'data_entrada' => date('Y-m-d h:i:s') // Capturando data atual do sistema
		];

		$matrizAux = array(); // Definindo matriz auxiliar de dados

		for ($i=0; $i < $post['total_colunas']; $i++) { 
			for ($j=0; $j < ($post['total_linhas'] - 1); $j++) { 
				if($nomeTitulo[$i] == "descricao") {
					$matrizAux[$j][0] = $matrizOriginal[$j][$i];
				}
				if($nomeTitulo[$i] == "codigo_interno") {
					$matrizAux[$j][1] = $matrizOriginal[$j][$i];
				}
				if($nomeTitulo[$i] == "custo") {
					$matrizAux[$j][2] = $matrizOriginal[$j][$i];
				}
				if($nomeTitulo[$i] == "valor_venda") {
					$matrizAux[$j][3] = $matrizOriginal[$j][$i];
				}
				if($nomeTitulo[$i] == "estoque_minimo") {
					$matrizAux[$j][5] = $matrizOriginal[$j][$i];
				}
				if($nomeTitulo[$i] == "estoque_atual") {
					$matrizAux[$j][4] = $matrizOriginal[$j][$i]; // Armazenando no estoque inicial
					$matrizAux[$j][6] = $matrizOriginal[$j][$i];
				}
			}
		}

		

		### Foreach de inserção de dados na banco ###
		foreach ($matrizAux as $linha) :
			
			uksort($linha, 'strnatcmp'); // Reordenando linha por ordem numérica

			// Array com chaves-título 
			$linhaChaves = [
				'description' => null,
				'internal_code' => null,
				'cost' => null,
				'value' => null,
				'est_inicial' => null,
				'est_minimo' => null,
				'est_maximo' => null,
				'est_atual' => null
			];

			if (isset($linha[0])) {
				if(!empty($linha[0]) && is_string($linha[0])) {
					$linhaChaves['description'] = $linha[0];
				} else {
					if(!in_array('O campo descrição não pode ser um valor numérico.', $callbackObj->errors))
						array_push($callbackObj->errors, 'O campo descrição não pode ser um valor numérico.');
				}
			}

			if (isset($linha[1])) {
				if(!empty($linha[1]) && is_integer($linha[1])) {
					$linhaChaves['internal_code'] = $linha[1];
				} else {
					if(!in_array('O campo Código precisa ser um valor inteiro!', $callbackObj->errors))
						array_push($callbackObj->errors, 'O campo Código precisa ser um valor inteiro!');
				}
			}

			if (isset($linha[2])) {
				if(!empty($linha[2]) && is_float($linha[2])) {
					$linhaChaves['cost'] = $linha[2];
				} else {
					if(!in_array('O campo Custo precisa ser um valor real!', $callbackObj->errors))
						array_push($callbackObj->errors, 'O campo Custo precisa ser um valor real!');
				}
			}

			if (isset($linha[3])) {
				if(!empty($linha[3]) && is_float($linha[3])) {
					$linhaChaves['value'] = $linha[3];
				} else {
					if(!in_array('O campo Valor precisa ser um valor real!', $callbackObj->errors))
						array_push($callbackObj->errors, 'O campo Valor precisa ser um valor real!');
				}
			}

			if (isset($linha[4])) {
				if(!empty($linha[4]) && is_numeric($linha[4])) {
					$linhaChaves['est_inicial'] = $linha[4];
				
				}
			}

			if (isset($linha[5])) {
				if(!empty($linha[5]) && is_numeric($linha[5])) {
					$linhaChaves['est_minimo'] = $linha[5];
				} else {
					if(!in_array('O campo Estoque mínimo precisa ser um valor numérico!', $callbackObj->errors))
						array_push($callbackObj->errors, 'O campo Estoque mínimo precisa ser um valor numérico!');
				}
			}

			if (isset($linha[6])) {
				if(!empty($linha[6]) && is_numeric($linha[6])) {
					$linhaChaves['est_atual'] = $linha[6];
				} else {
					if(!in_array('O campo Estoque precisa ser um valor numérico!', $callbackObj->errors))
						array_push($callbackObj->errors, 'O campo Estoque precisa ser um valor numérico!');
				}
			}


			$linhaDados = array_merge($user_id_array, $linhaChaves, $data_entrada);

			$validations = self::find_by_user_id_and_description($user_id, $linhaDados['description']);

			if(is_null($validations)) {
				$cadastrar = self::create($linhaDados);

				if($cadastrar->is_valid()) :
					$callbackObj->user = $cadastrar;
					$callbackObj->status = true;
					$callbackObj->products_quantity += 1;
				else :
					$errors = $cadastrar->errors->get_raw_errors(); 

					foreach ($errors as $field => $message) {
						if(!in_array($message[0], $callbackObj->errors))
							array_push($callbackObj->errors, $message[0]);
					}

					$callbackObj->products_quantity_errors += 1;
				endif;
			}
			else {
				$errors = array('description' => array('0' => 'Já existe um produto com descrição.'));
				
				foreach ($errors as $field => $message) {
					if(!in_array($message[0], $callbackObj->errors))
						array_push($callbackObj->errors, $message[0]);
				}

				$callbackObj->products_quantity_errors += 1;
			}
		endforeach;

		return $callbackObj;

	}


	public static function listar($user_id, $pagina = 1)
	{
		if (!isset($pagina)) {
			$pagina = 1;
		}
		
		$exib_produtos = 10;
		$first_prod = $pagina - 1; 
		$first_prod = $first_prod * $exib_produtos;

		$all_rgs = self::find('all', array('conditions' => array('user_id' => $user_id), 'order' => 'data_entrada desc'));
		$sql 	 = self::find('all', array('limit' => $exib_produtos, 'offset' => $first_prod, 'conditions' => array('user_id' => $user_id), 'order' => 'data_entrada desc'));
			
		$total_registros = count($all_rgs); // verifica o número total de registros
		$total_paginas = ceil($total_registros / $exib_produtos); // verifica o número total de páginas
		
		$anterior = $pagina - 1; 
		$proximo = $pagina + 1;

		$dados = [
			'anterior' => $anterior,
			'proximo' => $proximo,
			'pagina' => $pagina,
			'total_paginas' => $total_paginas,
			'total_produtos' => $total_registros,
			'primeiro_produto' => $first_prod,
			'registros' => $sql
		];

		return $dados;
	}

	// public static function atualizaEstoque($user_id)
	// {
	// 	$products = self::find('all', array('conditions' => array('user_id' => $user_id)));

	// 	if (date('d') == '01') {
			
	// 	}
	// }
}