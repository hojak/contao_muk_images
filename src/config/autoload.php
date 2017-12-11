<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2016 Leo Feyer
 *
 * @license LGPL-3.0+
 */


/**
 * Register the namespaces
 */
ClassLoader::addNamespaces(array
(
	'MUK',
	'MUK\\Images',
));


/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
    // Classes
    'MUK\\Images\\ImageHelper' => 'system/modules/muk_images/classes/ImageHelper.php',

    // Models
    
    // BE-Modules

    // FE-Modules

    // ContentElements

    // other
));


/**
 * Register the templates
 */
/*
TemplateLoader::addFiles(array( 
));
*/
