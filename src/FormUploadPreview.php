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

namespace Richardhj\Contao\Widget;

use Contao\Config;
use Contao\File;
use Contao\FilesModel;
use Contao\FormFileUpload;
use Contao\Image;
use Contao\StringUtil;

/**
 * Class FormUploadPreview
 *
 * @property mixed $thumbnailSize
 * @property mixed $fallbackImage
 * @property bool  $enableReset
 * @property bool  $addMetaWizard
 *
 * @package Richardhj\Contao\Widget
 */
class FormUploadPreview extends FormFileUpload
{

    /**
     * Template
     *
     * @var string
     */
    protected $strTemplate = 'form_upload_preview';

    /**
     * The CSS class prefix
     *
     * @var string
     */
    protected $strPrefix = 'widget widget-upload-preview';

    /**
     * Submit user input
     *
     * @var boolean
     */
    protected $blnSubmitInput = true;

    /**
     * Initialize the object.
     *
     * @param array $attributes An optional attributes array
     */
    public function __construct($attributes = null)
    {
        // Adjust these variables in your child class on behalf.
        // Alternatively, overwrite these variables via the `eval` array.
        $attributes = array_merge(
            [
                /**
                 * @var mixed UUID of the folder to save upload
                 */
                'uploadFolder'   => null,

                /**
                 * @var bool Do not replace the file, if there is a file with the same name already
                 */
                'doNotOverwrite' => false,

                /**
                 * @var bool Use the home directory of the user as save path
                 */
                'useHomeDir' => false,

                /**
                 * @var bool Store the file
                 */
                'storeFile'      => true,

                /**
                 * @var int Maximum file size (bytes)
                 */
                'maxlength'      => Config::get('maxFileSize'),

                /**
                 * @var string Allowed file extension
                 */
                'extensions'     => Config::get('validImageTypes'),

                /**
                 * @var mixed UUID of the thumbnail to show, if no picture uploaded
                 */
                'fallbackImage'  => null,

                /**
                 * @var array|int Size of the thumbnail. Either in the format [width, height, mode] or the tl_image_size.id
                 */
                'thumbnailSize'  => [],

                /**
                 * @var bool Show a "reset image" checkbox
                 */
                'enableReset'    => false,

                /**
                 * @var bool Display the meta wizard to fetch meta data like in the backend file manager
                 */
                'addMetaWizard'  => false,
            ],
            $attributes
        );

        if (null === $attributes['uploadFolder'] && isset($attributes['path'])) {
            $attributes['uploadFolder'] = FilesModel::findByPath($attributes['path'])->uuid;
        }

        parent::__construct($attributes);
    }

    /**
     * Validate the user input and set the value.
     *
     * @throws \Exception
     */
    public function validate()
    {
        // Delete the file when requested
        if (true === $this->enableReset && $this->getPost($this->strName.'_reset')) {
            if (null !== ($fileModel = FilesModel::findByPk($this->varValue))) {
                $file = new File($fileModel->path);
                $file->delete();
            }

            unset($_FILES[$this->strName]);

            return;
        }

        // Handle file upload
        parent::validate();

        // Set the image as varValue
        $file = $_SESSION['FILES'][$this->strName];
        if (true === $file['uploaded']) {
            $this->varValue = StringUtil::uuidToBin($file['uuid']);
        }
    }

    /**
     * Generate the widget and return it as string
     *
     * @return string
     */
    public function generate()
    {
        $return = '';

        if (empty($this->thumbnailSize)) {
            $this->thumbnailSize = [
                Config::get('imageWidth'),
                Config::get('imageHeight'),
                'box',
            ];
        }

        $file = FilesModel::findByPk($this->varValue);
        if (null === $file && null !== $this->fallbackImage) {
            $file = FilesModel::findByPk($this->fallbackImage);
        }
        if (null !== $file) {
            $altTag = $file->name;
            $size   = deserialize($this->thumbnailSize);
            $image  = Image::create($file->path, $size)->executeResize();

            $return .= sprintf(
                '<img src="%s" width="%s" height="%s" alt="%s" class="uploaded-image">',
                $image->getResizedPath(),
                $image->getTargetWidth(),
                $image->getTargetHeight(),
                $altTag
            );
        }

        if ($this->addMetaWizard) {
//@todo add meta wizard here
        }

        $return .= parent::generate();

        if ($this->enableReset) {
            $return .= sprintf(
                '<input type="checkbox" name="%s" class="checkbox" value=""><label>%s</label> ',
                $this->strName.'_reset',
                'Reset'
            );
        }

        return $return;
    }
}
