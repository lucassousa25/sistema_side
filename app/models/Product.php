<?php

class Product extends \HXPHP\System\Model
{
	// Validando a presença dos campos (phpActiveRecord) 
	static $validates_presence_of = array(
		array(
			'description',
			'message' => 'A descrição é um campo Obrigatório!'
		)
	);

	public static function cadastrar(array $post, $user_id)
	{
		$callbackObj = new \stdClass; // Atribuindo classe vazio do framework
		$callbackObj->status = false;
		$callbackObj->errors = array();
		$callbackObj->product_description = null; // armazenando nome do produto para retorno no controller

		// user_id | internal_code | description | unity | date_insert

		$data_entrada = date('Y-m-d H:i:s');

		if (!empty($post['custo'])) {
			$post['custo'] = str_replace(',', '.', $post['custo']);
			$post['custo'] = floatval($post['custo']);
		}
		else {
			$post['custo'] = null;
		}

		if(empty($post['demanda_media']))
			$post['demanda_media'] = null;

		if(empty($post['tempo_reposicao']))
			$post['tempo_reposicao'] = null;

		if(empty($post['freq_compra']))
			$post['freq_compra'] = null;

		if(empty($post['total_vendas'])) {
			$post['total_vendas'] = null;
		}
		else {
			$post['total_vendas'] = str_replace("." , "" , $post['total_vendas'] ); // Primeiro tira os pontos
			$post['total_vendas'] = str_replace(',', '.', $post['total_vendas']);
		}

		$array_product_insert = [
			'user_id' => $user_id,
			'internal_code' => $post['internal_code'],
			'description' => $post['description'],
			'unity' => $post['unity'],
			'date_insert' => $data_entrada
		];

		$array_product_parameters = [
			'product_id' => null,
			'estoque_atual' => $post['estoque_atual'],
			'custo' => $post['custo'],
			'tempo_reposicao' => $post['tempo_reposicao'],
			'demanda_media' => $post['demanda_media'],
			'freq_compra' => $post['freq_compra'],
			'quantidade_vendida' => $post['quantidade_vendida'],
			'total_vendas' => number_format($post['total_vendas'], 2, '.', ''),
			'date' => date('Y-m-d')
		];

		$validations = self::find_by_user_id_and_description($user_id, $post['description']);

		if(is_null($validations)) {
			$cadastrar = self::create($array_product_insert);
			$product = self::find_by_user_id_and_description($user_id, $post['description']);
			$array_product_parameters['product_id'] = $product->id;

			$cadastrarParametros = Parameter::create($array_product_parameters);
			die();
			if ($cadastrar->is_valid() && $cadastrarParametros->is_valid()) {
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

	public static function editar($product_id, array $post, $user_id)
	{
		$callbackObj = new \stdClass; // Atribuindo classe vazio do framework
		$callbackObj->status = false;
		$callbackObj->errors = array();
		$callbackObj->product_description = null; // armazenando nome do produto para retorno no controller


		if (!empty($post['custo'])) {
			$post['custo'] = str_replace(',', '.', $post['custo']);
			$post['custo'] = floatval($post['custo']);
			$post['custo'] = number_format($post['custo'], 2);
		}
		else {
			$post['custo'] = null;
		}

		if(empty($post['demanda_media']))
			$post['demanda_media'] = null;

		if(empty($post['tempo_reposicao']))
			$post['tempo_reposicao'] = null;

		if(empty($post['freq_compra']))
			$post['freq_compra'] = null;

		if(empty($post['total_vendas'])) {
			$post['total_vendas'] = null;
		}
		else {
			$post['total_vendas'] = str_replace("." , "" , $post['total_vendas'] ); // Primeiro tira os pontos
			$post['total_vendas'] = str_replace(',', '.', $post['total_vendas']);
		}

		$product = self::find_by_user_id_and_id($user_id, $product_id);

		if (!is_null($product)) {

			$product->internal_code = $post['internal_code'];
			$product->description = $post['description'];
			$product->unity = $post['unity'];
			
			$maiorData = Parameter::find('all', array('select' => 'MAX(date) as date'));
			$parametros_produto = Parameter::find('all', array('conditions' => array("product_id = $product_id and date LIKE ?", "%".strftime('%Y-%m', strtotime($maiorData[0]->date))."%")));

			if (!is_null($parametros_produto)) {
				$parametros_produto[0]->custo = $post['custo'];
				$parametros_produto[0]->estoque_atual = $post['estoque_atual'];
				$parametros_produto[0]->tempo_reposicao = $post['tempo_reposicao'];
				$parametros_produto[0]->demanda_media = $post['demanda_media'];
				$parametros_produto[0]->freq_compra = $post['freq_compra'];
				$parametros_produto[0]->quantidade_vendida = $post['quantidade_vendida'];
				$parametros_produto[0]->total_vendas = number_format($post['total_vendas'], 2, '.', '');

				if ($product->save(false) && $parametros_produto[0]->save(false)) {
					$callbackObj->status = true;
					$callbackObj->product_description = $post['description'];
					return $callbackObj;
				}

			}

			$errors = $cadastrar->errors->get_raw_errors();
		
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
		$callbackObj->products_quantity_updated = null; // armazena quantidade de produtos com falha no cadastrp para retorno no controller

		$data_entrada = date('Y-m-d H:i:s'); // Capturando data atual do sistema

		$matrizProduto = array(); // Definindo matriz auxiliar de dados do produto
		$matrizParameters = array(); // Definido matriz auxiliar de dados dos parametros do produto

		for ($i=0; $i < $post['total_colunas']; $i++) { 
			for ($j=0; $j < ($post['total_linhas'] - 1); $j++) { 
				if($nomeTitulo[$i] == "codigo_interno") {
					$matrizProduto[$j][0] = $matrizOriginal[$j][$i];
				}
				if($nomeTitulo[$i] == "descricao") {
					$matrizProduto[$j][1] = $matrizOriginal[$j][$i];
				}
				if($nomeTitulo[$i] == "unidade") {
					$matrizProduto[$j][2] = $matrizOriginal[$j][$i];
				}
				if($nomeTitulo[$i] == "estoque_atual") {
					$matrizParameters[$j][0] = $matrizOriginal[$j][$i];
				}
				if($nomeTitulo[$i] == "custo") {
					$matrizParameters[$j][1] = $matrizOriginal[$j][$i];
				}
				if($nomeTitulo[$i] == "tempo_reposicao") {
					$matrizParameters[$j][2] = $matrizOriginal[$j][$i];
				}
				if($nomeTitulo[$i] == "demanda_media") {
					$matrizParameters[$j][3] = $matrizOriginal[$j][$i];
				}
				if($nomeTitulo[$i] == "freq_compra") {
					$matrizParameters[$j][4] = $matrizOriginal[$j][$i];
				}
				if($nomeTitulo[$i] == "quantidade_vendida") {
					$matrizParameters[$j][5] = $matrizOriginal[$j][$i];
				}
				if($nomeTitulo[$i] == "total_vendas") {
					$matrizParameters[$j][6] = str_replace(',', '.', $matrizOriginal[$j][$i]);
				}
			}
		}

		$linhaNum = 0;
		### Foreach de inserção de dados na banco ###
		foreach ($matrizProduto as $linhaProduto) :
			
			uksort($linhaProduto, 'strnatcmp'); // Reordenando linha por ordem numérica
			
			// Array com chaves-título 
			$dadosProduto = [
				'user_id' => $user_id,
				'internal_code' => null,
				'description' => null,
				'unity' => null,
				'date_insert' => $data_entrada
			];
			$parametrosProduto = [
				'product_id' => null,
				'estoque_atual' => null,
				'custo' => null,
				'tempo_reposicao' => null,
				'demanda_media' => null,
				'freq_compra' => null,
				'quantidade_vendida' => null,
				'total_vendas' => null,
				'date' => $post['data']
			];

			if (isset($linhaProduto[0]) && !empty($linhaProduto[0])) {
				$dadosProduto['internal_code'] = $linhaProduto[0];
			}
			else {
				$dadosProduto['internal_code'] = null;
			}

			if (isset($linhaProduto[1]) && !empty($linhaProduto[1])) {
				if(is_string($linhaProduto[1])) {
					$dadosProduto['description'] = $linhaProduto[1];
				} else {
					if(!in_array('A coluna Descrição não pode ser um valor numérico.', $callbackObj->errors))
						array_push($callbackObj->errors, 'A coluna Descrição não pode ser um valor numérico.');
				}
			}
			else {
				if(!in_array('A descrição é um campo obrigatório.', $callbackObj->errors))
						array_push($callbackObj->errors, 'A descrição é um campo obrigatório.');
			}

			if (isset($linhaProduto[2]) && !empty($linhaProduto[2])) {
				if(is_string($linhaProduto[2])) {
					$dadosProduto['unity'] = $linhaProduto[2];
				} else {
					if(!in_array('A coluna de Unidade não pode ser um valor numérico.', $callbackObj->errors))
						array_push($callbackObj->errors, 'A coluna de Unidade não pode ser um valor numérico.');
				}
			}
			else {
				$dadosProduto['unity'] = null;
			}
			
			$validations = self::find_by_user_id_and_description($user_id, $dadosProduto['description']);
			
			if (isset($matrizParameters[$linhaNum]))
				uksort($matrizParameters[$linhaNum], 'strnatcmp'); // Reordenando linha de inserção

			if(is_null($validations)) {
				$cadastrarProduto = self::create($dadosProduto); // Cadastrando Produtos (Tabela Produto)
				
				$product = self::find_by_user_id_and_description($user_id, $dadosProduto['description']);

				if (isset($product->id)) {
					$parametrosProduto['product_id'] = $product->id;
				}

				if (isset($matrizParameters[$linhaNum][0])) {
					if(!empty($matrizParameters[$linhaNum][0]) && is_numeric($matrizParameters[$linhaNum][0])) {
						$parametrosProduto['estoque_atual'] = $matrizParameters[$linhaNum][0];
					}else {
						if(!in_array('A coluna de Estoque Atual precisa conter valores numéricos!', $callbackObj->errors))
							array_push($callbackObj->errors, 'A coluna de Estoque Atual precisa conter valores numéricos!');
					}
				}

				if (isset($matrizParameters[$linhaNum][1])) {
					if(!empty($matrizParameters[$linhaNum][1]) && is_numeric($matrizParameters[$linhaNum][1])) {
						$parametrosProduto['custo'] = $matrizParameters[$linhaNum][1];
					}else {
						if(!in_array('A coluna de Custo precisa conter valores numéricos!', $callbackObj->errors))
							array_push($callbackObj->errors, 'A coluna de Custo precisa conter valores numéricos!');
					}
				}

				if (isset($matrizParameters[$linhaNum][2])) {
					if(!empty($matrizParameters[$linhaNum][2]) && is_numeric($matrizParameters[$linhaNum][2])) {
						$parametrosProduto['tempo_reposicao'] = $matrizParameters[$linhaNum][2];
					}else {
						if(!in_array('A coluna de Tempo de Reposição precisa conter valores numéricos!', $callbackObj->errors))
							array_push($callbackObj->errors, 'A coluna de Tempo de Reposição precisa conter valores numéricos!');
					}
				}

				if (isset($matrizParameters[$linhaNum][3])) {
					if(!empty($matrizParameters[$linhaNum][3]) && is_numeric($matrizParameters[$linhaNum][3])) {
						$parametrosProduto['demanda_media'] = $matrizParameters[$linhaNum][3];
					}else {
						if(!in_array('A coluna de Demanda Média precisa conter valores numéricos!', $callbackObj->errors))
							array_push($callbackObj->errors, 'A coluna de Demanda Média precisa conter valores numéricos!');
					}
				}

				if (isset($matrizParameters[$linhaNum][4])) {
					if(!empty($matrizParameters[$linhaNum][4]) && is_numeric($matrizParameters[$linhaNum][4])) {
						$parametrosProduto['freq_compra'] = $matrizParameters[$linhaNum][4];
					}else {
						if(!in_array('A coluna de Frequência de compras precisa conter valores numéricos!', $callbackObj->errors))
							array_push($callbackObj->errors, 'A coluna de Frequência de compras precisa conter valores numéricos!');
					}
				}

				if (isset($matrizParameters[$linhaNum][5])) {
					if(!empty($matrizParameters[$linhaNum][5]) && is_numeric($matrizParameters[$linhaNum][5])) {
						$parametrosProduto['quantidade_vendida'] = $matrizParameters[$linhaNum][5];
					}else {
						if(!in_array('A coluna de Quantidade vendida precisa conter valores numéricos!', $callbackObj->errors))
							array_push($callbackObj->errors, 'A coluna de Quantidade vendida precisa conter valores numéricos!');
					}
				}

				if (isset($matrizParameters[$linhaNum][6])) {
					if(!empty($matrizParameters[$linhaNum][6]) && is_numeric($matrizParameters[$linhaNum][6])) {
						$parametrosProduto['total_vendas'] = number_format($matrizParameters[$linhaNum][6], 2, '.', '');
					}else {
						if(!in_array('A coluna de Total de Vendas precisa conter valores numéricos!', $callbackObj->errors))
							array_push($callbackObj->errors, 'A coluna de Total de Vendas precisa conter valores numéricos!');
					}
				}
				

				if ($cadastrarProduto->is_valid()) {
					$cadastrarParametros = Parameter::create($parametrosProduto);
					
					if($cadastrarParametros->is_valid()) :
						$callbackObj->products_quantity += 1;
					else :
						$callbackObj->products_quantity_errors += 1;
					endif;
				}
				else {
					$callbackObj->products_quantity_errors += 1;
				}


			}
			else {

				$parametrosByProduto = Parameter::find_by_product_id_and_date($validations->id, $post['data']);
				$product = self::find_by_user_id_and_description($user_id, $dadosProduto['description']);

				$product->internal_code = $dadosProduto['internal_code'];

				if (is_null($parametrosByProduto)) {
					
					if (isset($product->id)) {
						$parametrosProduto['product_id'] = $validations->id;
					}

					if (isset($matrizParameters[$linhaNum][0])) {
						if(!empty($matrizParameters[$linhaNum][0]) && is_numeric($matrizParameters[$linhaNum][0])) {
							$parametrosProduto['estoque_atual'] = $matrizParameters[$linhaNum][0];
						}else {
							if(!in_array('A coluna de Estoque Atual precisa conter valores numéricos!', $callbackObj->errors))
								array_push($callbackObj->errors, 'A coluna de Estoque Atual precisa conter valores numéricos!');
						}
					}

					if (isset($matrizParameters[$linhaNum][1])) {
						if(!empty($matrizParameters[$linhaNum][1]) && is_numeric($matrizParameters[$linhaNum][1])) {
							$parametrosProduto['custo'] = $matrizParameters[$linhaNum][1];
						}else {
							if(!in_array('A coluna de Custo precisa conter valores numéricos!', $callbackObj->errors))
								array_push($callbackObj->errors, 'A coluna de Custo precisa conter valores numéricos!');
						}
					}

					if (isset($matrizParameters[$linhaNum][2])) {
						if(!empty($matrizParameters[$linhaNum][2]) && is_numeric($matrizParameters[$linhaNum][2])) {
							$parametrosProduto['tempo_reposicao'] = $matrizParameters[$linhaNum][2];
						}else {
							if(!in_array('A coluna de Tempo de Reposição precisa conter valores numéricos!', $callbackObj->errors))
								array_push($callbackObj->errors, 'A coluna de Tempo de Reposição precisa conter valores numéricos!');
						}
					}

					if (isset($matrizParameters[$linhaNum][3])) {
						if(!empty($matrizParameters[$linhaNum][3]) && is_numeric($matrizParameters[$linhaNum][3])) {
							$parametrosProduto['demanda_media'] = $matrizParameters[$linhaNum][3];
						}else {
							if(!in_array('A coluna de Demanda Média precisa conter valores numéricos!', $callbackObj->errors))
								array_push($callbackObj->errors, 'A coluna de Demanda Média precisa conter valores numéricos!');
						}
					}

					if (isset($matrizParameters[$linhaNum][4])) {
						if(!empty($matrizParameters[$linhaNum][4]) && is_numeric($matrizParameters[$linhaNum][4])) {
							$parametrosProduto['freq_compra'] = $matrizParameters[$linhaNum][4];
						}else {
							if(!in_array('A coluna de Frequência de compras precisa conter valores numéricos!', $callbackObj->errors))
								array_push($callbackObj->errors, 'A coluna de Frequência de compras precisa conter valores numéricos!');
						}
					}

					if (isset($matrizParameters[$linhaNum][5])) {
						if(!empty($matrizParameters[$linhaNum][5]) && is_numeric($matrizParameters[$linhaNum][5])) {
							$parametrosProduto['quantidade_vendida'] = $matrizParameters[$linhaNum][5];
						}else {
							if(!in_array('A coluna de Quantidade vendida precisa conter valores numéricos!', $callbackObj->errors))
								array_push($callbackObj->errors, 'A coluna de Quantidade vendida precisa conter valores numéricos!');
						}
					}

					if (isset($matrizParameters[$linhaNum][6])) {
						if(!empty($matrizParameters[$linhaNum][6]) && is_numeric($matrizParameters[$linhaNum][6])) {
							$parametrosProduto['total_vendas'] = number_format($matrizParameters[$linhaNum][6], 2, '.', '');
						}else {
							if(!in_array('A coluna de Total de Vendas precisa conter valores numéricos!', $callbackObj->errors))
								array_push($callbackObj->errors, 'A coluna de Total de Vendas precisa conter valores numéricos!');
						}
					}

					$cadastrarParametros = Parameter::create($parametrosProduto);

					if($cadastrarParametros->is_valid()) :
						$callbackObj->products_quantity_updated += 1;
					else :
						$callbackObj->products_quantity_errors += 1;
					endif;
				}
				else {
					if(!empty($matrizParameters[$linhaNum][0])) :
						if (is_numeric($matrizParameters[$linhaNum][0])) :
							$parametrosByProduto->estoque_atual = $matrizParameters[$linhaNum][0];
						else :
							if(!in_array('A coluna de Estoque Atual precisa conter valores numéricos!', $callbackObj->errors)) :
								array_push($callbackObj->errors, 'A coluna de Estoque Atual precisa conter valores numéricos!');
							endif;
						endif;
					else :
						$parametrosByProduto->estoque_atual = null;
					endif;

					if(!empty($matrizParameters[$linhaNum][1])) :
						if (is_numeric($matrizParameters[$linhaNum][1])) :
							$parametrosByProduto->custo = $matrizParameters[$linhaNum][1];
						else :
							if(!in_array('A coluna de Custo precisa conter valores numéricos!', $callbackObj->errors)) :
								array_push($callbackObj->errors, 'A coluna de Custo precisa conter valores numéricos!');
							endif;
						endif;
					else :
						$parametrosByProduto->custo = null;
					endif;

					if(!empty($matrizParameters[$linhaNum][2])) :
						if (is_numeric($matrizParameters[$linhaNum][2])) :
							$parametrosByProduto->tempo_reposicao = $matrizParameters[$linhaNum][2];
						else :
							if(!in_array('A coluna de Tempo de Reposição precisa conter valores numéricos!', $callbackObj->errors)) :
								array_push($callbackObj->errors, 'A coluna de Tempo de Reposição precisa conter valores numéricos!');
							endif;
						endif;
					else :
						$parametrosByProduto->tempo_reposicao = null;
					endif;

					if(!empty($matrizParameters[$linhaNum][3])) :
						if (is_numeric($matrizParameters[$linhaNum][3])) :
							$parametrosByProduto->demanda_media = $matrizParameters[$linhaNum][3];
						else :
							if(!in_array('A coluna de Demanda Média precisa conter valores numéricos!', $callbackObj->errors)) :
								array_push($callbackObj->errors, 'A coluna de Demanda Média precisa conter valores numéricos!');
							endif;
						endif;
					else :
						$parametrosByProduto->demanda_media = null;
					endif;

					if(!empty($matrizParameters[$linhaNum][4])) :
						if (is_numeric($matrizParameters[$linhaNum][4])) :
							$parametrosByProduto->freq_compra = $matrizParameters[$linhaNum][4];
						else :
							if(!in_array('A coluna de Frequência de Compras precisa conter valores numéricos!', $callbackObj->errors)) :
								array_push($callbackObj->errors, 'A coluna de Frequência de Compras precisa conter valores numéricos!');
							endif;
						endif;
					else :
						$parametrosByProduto->freq_compra = null;
					endif;

					if(!empty($matrizParameters[$linhaNum][5])) :
						if (is_numeric($matrizParameters[$linhaNum][5])) :
							$parametrosByProduto->quantidade_vendida = $matrizParameters[$linhaNum][5];
						else :
							if(!in_array('A coluna de Quantidade vendida precisa conter valores numéricos!', $callbackObj->errors)) :
								array_push($callbackObj->errors, 'A coluna de Quantidade vendida precisa conter valores numéricos!');
							endif;
						endif;
					endif;

					if(!empty($matrizParameters[$linhaNum][6])) :
						if (is_numeric($matrizParameters[$linhaNum][6])) :
							$parametrosByProduto->total_vendas = number_format($matrizParameters[$linhaNum][6], 2, '.', '');
						else :
							if(!in_array('A coluna de Total de Vendas precisa conter valores numéricos!', $callbackObj->errors)) :
								array_push($callbackObj->errors, 'A coluna de Total de Vendas precisa conter valores numéricos!');
							endif;
						endif;
					else :
						$parametrosByProduto->total_vendas = null;
					endif;

					$parametrosByProduto->save(false);
					$callbackObj->products_quantity_updated += 1;
				}

			}

			$linhaNum++;
		endforeach;

		return $callbackObj;

	}


	public static function listar($user_id)
	{
		//$exib_produtos = 10;
		//$first_prod = $pagina - 1; 
		//$first_prod = $first_prod * $exib_produtos;

		$all_rgs = self::find('all', array('conditions' => array('user_id' => $user_id)));
		//$consulta = self::find('all', array('limit' => $exib_produtos, 'offset' => $first_prod, 'conditions' => array('user_id' => $user_id)));
		$maiorData = Parameter::find('all', array('select' => 'MAX(date) as date'));
		$parametros = Parameter::find('all', array('conditions' => array("date LIKE ?", "%".strftime('%Y-%m', strtotime($maiorData[0]->date))."%"), 'order' => 'date desc'));
		$parametrosData = Parameter::find('all', array('select' => 'DISTINCT(left(date,7)) as data', 'order' => 'date desc'));


		$total_registros = count($all_rgs); // verifica o número total de registros
		//$total_registros_por_pagina = count($consulta); // verifica o número total de registros por páginas [Produtos]
		$total_parametros = count($parametros); // verifica o número total de registros [Parametros]
		//$total_paginas = ceil($total_registros / $exib_produtos); // verifica o número total de páginas

		//$anterior = $pagina - 1; 
		//$proximo = $pagina + 1;

		$array_tabela = array();

		for ($i=0; $i < $total_registros; $i++) {
			for ($j=0; $j < $total_parametros; $j++) { 
				if ($parametros[$j]->product_id == $all_rgs[$i]->id) {
					$array_tabela[$i]['id'] = $all_rgs[$i]->id;
					$array_tabela[$i]['internal_code'] = $all_rgs[$i]->internal_code;
					$array_tabela[$i]['description'] = $all_rgs[$i]->description;
					$array_tabela[$i]['unity'] = $all_rgs[$i]->unity;
					$array_tabela[$i]['estoque_atual'] = $parametros[$j]->estoque_atual;
					$array_tabela[$i]['quantidade_vendida'] = $parametros[$j]->quantidade_vendida;
					$array_tabela[$i]['date_insert'] = $all_rgs[$i]->date_insert;
				}
			 } 
		}

		$dados = [
			//'anterior' => $anterior,
			//'proximo' => $proximo,
			//'pagina' => $pagina,
			//'total_paginas' => $total_paginas,
			'total_produtos' => $total_registros,
			//'primeiro_produto' => $first_prod,
			'registros' => $array_tabela,
			'datas' => $parametrosData
		];

		return $dados;
	}

}