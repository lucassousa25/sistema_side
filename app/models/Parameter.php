<?php

class Parameter extends \HXPHP\System\Model
{
	// Validando a presença dos campos (phpActiveRecord) 
	static $validates_presence_of = array(
		array(
			'date',
			'message' => 'A data referente é um campo Obrigatório!'
		)
	);
}