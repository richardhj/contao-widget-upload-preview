<?php


/**
 * Class UploadPreviewFieldFE
 * Widget for providing a single file upload input and a preview if image already set.
 * Based on the widget of the extension "avatar".
 * @property int     maxlength       Maximal upload size in bytes, system configuration value "maxFileSize" is default
 *           value
 * @property string  extensions      Comma separated string with allowed extensions, system configuration value
 *           "validImageTypes" is default value
 * @property string  uploadFolder    UUID in DBAFS, is necessary to set save the uploaded image permanently unless you
 *           are using following property
 * @property boolean useUserHomeDir  Set true to save uploaded image in member's homeDir, makes "uploadFolder" property
 *           useless
 * @property string  fallbackImage   UUID in DBAFS, a image that will be shown if no image is provided
 * @property string  renameFile      Provide a string the saved file name will become, the logged in member's data are
 *           available as simple tokens
 * @property boolean resizeFile      Set true to automatically resize the image if dimensions are greater than maxdims
 * @property array   maxdims         array(width, height, [mode]) Set this array to avoid too big images
 * @property array   outputSize      array(width, height, [mode]) Set this array to adjust the generated preview image
 *           size
 * @author Kirsten Roschanski <kirsten@kat-webdesign.de>
 * @author Tristan Lins <tristan.lins@bit3.de>
 * @author Richard Henkenjohann
 */
class UploadPreviewFieldFE extends Widget implements uploadable
{

	/**
	 * Template
	 * @var string
	 */
	protected $strTemplate = 'form_upload_preview';

	/**
	 * The CSS class prefix
	 * @var string
	 */
	protected $strPrefix = 'widget widget-upload_preview';


	/**
	 * Submit user input
	 * @var boolean
	 */
	protected $blnSubmitInput = true;


	/**
	 * {@inheritdoc}
	 */
	public function __construct($arrAttributes = null)
	{
		$arrAttributes = array_merge
		(
			array
			(
				'maxlength'  => \Config::get('maxFileSize'),
				'extensions' => \Config::get('validImageTypes'),
				'maxdims'    => array(0, 0)
			),
			(array)$arrAttributes
		);

		parent::__construct($arrAttributes);
	}


	/**
	 * Add specific attributes
	 *
	 * @param string
	 * @param mixed
	 */
	public function __set($strKey, $varValue)
	{
		switch ($strKey)
		{
			/** @noinspection PhpMissingBreakStatementInspection */
			case 'maxdims':
				$varValue = deserialize($varValue, true);

			default:
				parent::__set($strKey, $varValue);
		}
	}


	/**
	 * Validate the input and set the value
	 */
	public function validate()
	{
		$arrImageDimensions = $this->maxdims;
		$this->storeFile = ($this->uploadFolder != '' || $this->useUserHomeDir) ? true : false;

		// No file specified
		if (!isset($_FILES[$this->strName]) || empty($_FILES[$this->strName]['name']))
		{
			if ($this->mandatory)
			{
				if ($this->strLabel == '')
				{
					$this->addError($GLOBALS['TL_LANG']['ERR']['mdtryNoLabel']);
				}
				else
				{
					$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['mandatory'], $this->strLabel));
				}
			}

			return;
		}

		$file = $_FILES[$this->strName];
		$maxlength_kb = System::getReadableSize($this->maxlength);

		// Romanize the filename
		$file['name'] = utf8_romanize($file['name']);

		// File was not uploaded
		if (!is_uploaded_file($file['tmp_name']))
		{
			if (in_array($file['error'], array(1, 2)))
			{
				$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['filesize'], $maxlength_kb));
				System::log('File "' . $file['name'] . '" exceeds the maximum file size of ' . $maxlength_kb, __METHOD__, TL_ERROR);
			}

			if ($file['error'] == 3)
			{
				$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['filepartial'], $file['name']));
				System::log('File "' . $file['name'] . '" was only partially uploaded', __METHOD__, TL_ERROR);
			}

			unset($_FILES[$this->strName]);

			return;
		}

		// File is too big
		if ($this->maxlength > 0 && $file['size'] > $this->maxlength)
		{
			$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['filesize'], $maxlength_kb));
			System::log('File "' . $file['name'] . '" exceeds the maximum file size of ' . $maxlength_kb, __METHOD__, TL_ERROR);

			unset($_FILES[$this->strName]);

			return;
		}

		$strExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
		$uploadTypes = trimsplit(',', $this->extensions);

		// File type is not allowed
		if (!in_array(strtolower($strExtension), $uploadTypes))
		{
			$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['filetype'], $strExtension));
			System::log('File type "' . $strExtension . '" is not allowed to be uploaded (' . $file['name'] . ')', __METHOD__, TL_ERROR);

			unset($_FILES[$this->strName]);

			return;
		}

		$blnResize = false;

		if (($arrImageSize = @getimagesize($file['tmp_name'])) != false)
		{
			// Image exceeds maximum image width
			if ($arrImageDimensions[0] && $arrImageSize[0] > $arrImageDimensions[0])
			{
				if ($this->resizeFile)
				{
					$blnResize = true;
				}
				else
				{
					$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['filewidth'], $file['name'], $arrImageDimensions[0]));
					System::log('File "' . $file['name'] . '" exceeds the maximum image width of ' . $arrImageDimensions[0] . ' pixels', __METHOD__, TL_ERROR);

					unset($_FILES[$this->strName]);

					return;
				}
			}

			// Image exceeds maximum image height
			if ($arrImageDimensions[1] && $arrImageSize[1] > $arrImageDimensions[1])
			{
				if ($this->resizeFile)
				{
					$blnResize = true;
				}
				else
				{
					$this->addError(sprintf($GLOBALS['TL_LANG']['ERR']['fileheight'], $file['name'], $arrImageDimensions[1]));
					System::log('File "' . $file['name'] . '" exceeds the maximum image height of ' . $arrImageDimensions[1] . ' pixels', __METHOD__, TL_ERROR);

					unset($_FILES[$this->strName]);

					return;
				}
			}
		}

		// Store file in the session and optionally on the server
		if (!$this->hasErrors())
		{
			$_SESSION['FILES'][$this->strName] = $_FILES[$this->strName];
			System::log('File "' . $file['name'] . '" uploaded successfully', __METHOD__, TL_FILES);

			if ($this->storeFile)
			{
				if ($this->useUserHomeDir)
				{
					if (\FrontendUser::getInstance()->assignDir && \FrontendUser::getInstance()->homeDir)
					{
						$this->uploadFolder = $this->User->homeDir;
					}
				}

				$objUploadFolder = \FilesModel::findByPk($this->uploadFolder);

				// The upload folder could not be found
				if ($objUploadFolder === null)
				{
					throw new Exception("Invalid upload folder ID $this->uploadFolder");
				}

				$strUploadFolderPath = $objUploadFolder->path;

				// Store the file if the upload folder exists
				if ($strUploadFolderPath != '' && is_dir(TL_ROOT . '/' . $strUploadFolderPath))
				{
					/** @noinspection PhpUndefinedMethodInspection */
					$this->import('Files');

					if ($this->renameFile != '')
					{
						$pathinfo = pathinfo($file['name']);

						/** @type Model|null $objMember */
						$objMember = \MemberModel::findByPk(\FrontendUser::getInstance()->id);
						$targetName = standardize(
								\StringUtil::parseSimpleTokens(
									\StringUtil::decodeEntities($this->renameFile),
									($objMember !== null)
										? $objMember->row()
										: array()
								)
							)
							. '.' . strtolower($pathinfo['extension']);
					}
					else
					{
						$targetName = $file['name'];
					}

					// Do not overwrite existing files
					if ($this->doNotOverwrite && file_exists(TL_ROOT . '/' . $strUploadFolderPath . '/' . $targetName))
					{
						$offset = 1;
						$pathinfo = pathinfo($targetName);
						$name = $pathinfo['filename'];

						$arrAll = scan(TL_ROOT . '/' . $strUploadFolderPath);
						$arrFiles = preg_grep
						(
							'/^' . preg_quote($name, '/') . '.*\.' . preg_quote($pathinfo['extension'], '/') . '/',
							$arrAll
						);

						foreach ($arrFiles as $strFile)
						{
							if (preg_match('/__[0-9]+\.' . preg_quote($pathinfo['extension'], '/') . '$/', $strFile))
							{
								$strFile = str_replace('.' . $pathinfo['extension'], '', $strFile);
								$intValue = intval(substr($strFile, (strrpos($strFile, '_') + 1)));

								$offset = max($offset, $intValue);
							}
						}

						$targetName = str_replace($name, $name . '__' . ++$offset, $targetName);
					}

					$this->Files->move_uploaded_file($file['tmp_name'], $strUploadFolderPath . '/' . $targetName);
					$this->Files->chmod
					(
						$strUploadFolderPath . '/' . $targetName,
						$GLOBALS['TL_CONFIG']['defaultFileChmod']
					);


					$objFile = \Contao\Dbafs::addResource($strUploadFolderPath . '/' . $targetName);

					if ($blnResize)
					{
						Image::resize
						(
							$objFile->path,
							$arrImageSize[0],
							$arrImageSize[1],
							$arrImageSize[2]
						);
					}

					$_SESSION['FILES'][$this->strName] = array
					(
						'name'     => $targetName,
						'type'     => $file['type'],
						'tmp_name' => TL_ROOT . '/' . $strUploadFolderPath . '/' . $file['name'],
						'error'    => $file['error'],
						'size'     => $file['size'],
						'uploaded' => true
					);

					// Update value
					$this->value = $objFile->uuid;

					System::log('File "' . $targetName . '" has been moved to "' . $strUploadFolderPath . '"', __METHOD__, TL_FILES);
				}
			}
		}

		unset($_FILES[$this->strName]);
	}


	/**
	 * Generate the widget and return it as string
	 * @return string
	 */
	public function generate()
	{
		$arrImageDimensions = (!empty($this->outputSize)) ? $this->outputSize : array(Config::get('imageWidth'), Config::get('imageHeight'), 'box');

		/** @var FilesModel $objFile */
		$objFile = FilesModel::findByPk($this->varValue);
		$return = '';

		if ($objFile === null && $this->fallbackImage)
		{
			$objFile = FilesModel::findByPk($this->fallbackImage);
		}

		if ($objFile !== null)
		{
			$strAlt = $objFile->name;

			$return .= '<img src="' . Image::get(
					$objFile->path,
					$arrImageDimensions[0],
					$arrImageDimensions[1],
					$arrImageDimensions[2]
				) . '" width="' . $arrImageDimensions[0] . '" height="' . $arrImageDimensions[1] . '" alt="' . $strAlt . '" class="upload_image">';
		}
//@todo add meta wizard here
		$return .= sprintf(
			'<input type="file" name="%s" id="ctrl_%s" class="upload%s"%s%s',
			$this->strName,
			$this->strId,
			(strlen($this->strClass) ? ' ' . $this->strClass : ''),
			$this->getAttributes(),
			$this->strTagEnding
		);

		return $return;
	}
}  
