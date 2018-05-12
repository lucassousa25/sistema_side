<?php

class Parameter extends \HXPHP\System\Model
{
	// Validando a presença dos campos (phpActiveRecord) 
	static $validates_presence_of = array(
		array(
			'current_stock',
			'message' => 'O Estoque é um campo Obrigatório!'
		),
		array(
			'value',
			'message' => 'O Preço é um campo Obrigatório!'
		),
		array(
			'lead_time',
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