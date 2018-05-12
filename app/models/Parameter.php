<?php

class Parameter extends \HXPHP\System\Model
{
	// Validando a presença dos campos (phpActiveRecord) 
	static $validates_presence_of = array(
		array(
			'estoque_atual',
			'message' => 'O Estoque é um campo Obrigatório!'
		),
		array(
			'valor',
			'message' => 'O Preço é um campo Obrigatório!'
		),
		array(
			'tempo_reposicao',
			'message' => 'O Tempo de reposição é um campo Obrigatório!'
		),
		array(
			'demanda_mensal',
			'message' => 'A demanda mensal é um campo Obrigatório!'
		),
		array(
			'freq_compra_mensal',
			'message' => 'A Frequência de compras é um campo Obrigatório!'
		)
	);
}