<?php

/**
 * Contao Open Source CMS
 *
 * Copyright (c) 2005-2013 Leo Feyer
 *
 * @package Avatar
 * @link    https://contao.org
 * @license http://www.gnu.org/licenses/lgpl-3.0.html LGPL
 */

/**
 * Register the classes
 */
ClassLoader::addClasses(array
(
	// Library
	'UploadPreviewFieldBE' => 'system/modules/upload_preview_field/classes/UploadPreviewFieldBE.php',
	'UploadPreviewFieldFE' => 'system/modules/upload_preview_field/classes/UploadPreviewFieldFE.php',
));


/**
 * Register the templates
 */
TemplateLoader::addFiles(array
(
	// Widgets
	'form_upload_preview' => 'system/modules/upload_preview_field/templates/form',
));
