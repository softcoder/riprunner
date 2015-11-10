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
if(file_exists(__RIPRUNNER_ROOT__ . '/views-custom') === true) {
	$twig_template_loader->addPath(__RIPRUNNER_ROOT__ . '/views-custom', 'custom');
}

$twig = new \Twig_Environment($twig_template_loader, array(
	'cache' => __RIPRUNNER_ROOT__ . '/temp/twig',
	'debug' => true,
	'strict_variables' => true
));
//$twig->addExtension(new \Twig_Extension_Debug());
?>
