<?php 
// ==============================================================
//	Copyright (C) 2014 Mark Vejvoda
//	Under GNU GPL v3.0
// ==============================================================
namespace riprunner;

require_once 'config_constants.php';
require_once __RIPRUNNER_ROOT__ . '/third-party/Twig/Autoloader.php';

\Twig_Autoloader::register();

$twig_template_loader = new \Twig_Loader_Filesystem(
		__RIPRUNNER_ROOT__ . '/views');
// This allows customized views to be placed in the folder below
$twig_template_loader->addPath(__RIPRUNNER_ROOT__ . '/views-custom', 'custom');

$twig = new \Twig_Environment($twig_template_loader, array(
	'cache' => __RIPRUNNER_ROOT__ . '/temp/twig',
	'debug' => true,
	'strict_variables' => true
));

/*
class Template { 
	private $vars = array();
	 
	public function __get($name) { 
		return $this->vars[$name]; 
	}
	 
	public function __set($name, $value) { 
		if($name == 'view_template_file') { 
			throw new Exception("Cannot bind variable named 'view_template_file'"); 
		} 
		$this->vars[$name] = $value; 
	}
	 
	public function render($view_template_file) { 
		if(array_key_exists('view_template_file', $this->vars)) { 
			throw new Exception("Cannot bind variable called 'view_template_file'"); 
		} 
		extract($this->vars); 
		ob_start(); 
		include($view_template_file); 
		return ob_get_clean(); 
	} 
}
*/