<?php

class HomeController extends \HXPHP\System\Controller
{
	public function __construct()
	{
		parent::__construct();
	}

	public function indexAction()
	{
		$this->view->setFile('index')
               ->setHeader('header_side')
               ->setFooter('footer_side')
               ->setTemplate(true);
	}
}