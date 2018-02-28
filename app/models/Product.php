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
			'sell_value',
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

	// Validando exclusividades dos campos (phpActiveRecord)
	static $validates_uniqueness_of = array(
        array(
       		'description', 
       		'message' => 'Já existe um produto com essa descrição.'
       	)
    );

	public static function cadastrar(array $post, $user_id)
	{
		$callbackObj = new \stdClass; // Atribuindo classe vazio do framework
		$callbackObj->user = null;
		$callbackObj->status = false;
		$callbackObj->errors = array();
		$callbackObj->product_description = null; // armazenando nome do produto para retorno no controller

		// user_id | desription | internal_code | cost | sell_value | est_inicial | est_minimo | est_maximo
		// est_atual | data_entrada | provider

		$user_id_array = [
			'user_id' => $user_id
		];


		$data_entrada = [
			'data_entrada' => date('Y-m-d h:i:s')
		];

		$post = array_merge($user_id_array, $post, $data_entrada);

		$cadastrar = self::create($post);

		if($cadastrar->is_valid()) {
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
}