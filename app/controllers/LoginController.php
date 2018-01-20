<?php

/**
 * 
 */
class LoginController extends \HXPHP\System\Controller
{

	public function __construct()
	{
		parent::__construct();
	}

	public function indexAction()
	{
		$this->view->setTemplate(false)
				->setTitle('SIDE | Login');
	}

}