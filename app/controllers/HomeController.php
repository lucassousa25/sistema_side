<?php

class HomeController extends \HXPHP\System\Controller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function indexAction()
	{
		$this->view->setTemplate(true)
				->setHeader('header_side')
				->setFooter('footer_side')
				->setTitle('SIDE | Home');
	}
}