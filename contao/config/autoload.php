<?php

/**
 * This file is part of richardhj/contao-widget-upload-preview.
 *
 * Copyright (c) 2016-2017 Richard Henkenjohann
 *
 * @package   richardhj/contao-widget-upload-preview
 * @author    Richard Henkenjohann <richardhenkenjohann@googlemail.com>
 * @copyright 2016-2017 Richard Henkenjohann
 * @license   https://github.com/richardhj/contao-widget-upload-preview/blob/master/LICENSE LGPL-3.0
 */

/**
 * Register the templates
 */
TemplateLoader::addFiles(
    [
        'form_upload_preview' => 'system/modules/upload-preview-widget/templates/form',
    ]
);
