<?php

/**
 * 
 */
class LoginController extends \HXPHP\System\Controller
{

	public function __construct($configs)
	{
		parent::__construct($configs);
	}

	public function indexAction()
	{
		$this->view->setTemplate(false)
				->setTitle('SIDE | Login');
	}

}