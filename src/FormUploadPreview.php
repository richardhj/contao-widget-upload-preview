<?php

namespace Richardhj\Contao\Widget;

use Contao\Config;
use Contao\FilesModel;
use Contao\FormFileUpload;
use Contao\Image;

/**
 * Class FormUploadPreview
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
     * {@inheritdoc}
     */
    public function __construct($attributes = null)
    {
        $attributes = array_merge(
            [
                'uploadFolder'   => null,
                'doNotOverwrite' => false,
                'useUserHomeDir' => false,
                'storeFile'      => true,
                'maxlength'      => Config::get('maxFileSize'),
                'extensions'     => Config::get('validImageTypes'),
                'fallbackImage'  => null,
                'thumbnailSize'  => [],
            ],
            $attributes
        );

        parent::__construct($attributes);
    }

    /**
     * Generate the widget and return it as string
     *
     * @return string
     */
    public function generate()
    {
        $return = '';

        $imageDimensions = (!empty($this->thumbnailSize))
            ? $this->thumbnailSize
            : [
                Config::get('imageWidth'),
                Config::get('imageHeight'),
                'box',
            ];

        $file = FilesModel::findByPk($this->varValue);
        if (null === $file && null !== $this->fallbackImage) {
            $file = FilesModel::findByPk($this->fallbackImage);
        }
        if (null !== $file) {
            $altTag = $file->name;

            $return .= '<img src="'
                       .Image::get($file->path, $imageDimensions[0], $imageDimensions[1], $imageDimensions[2])
                       .'" width="'.$imageDimensions[0].'" height="'.$imageDimensions[1].'" alt="'.$altTag
                       .'" class="uploaded-image">';
        }

//@todo add meta wizard here

        $return .= parent::generate();

        return $return;
    }
}
