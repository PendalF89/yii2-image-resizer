<?php

namespace pendalf89\imageresizer;

use Yii;
use yii\base\Component;
use yii\helpers\FileHelper;
use Imagine\Image\Box;
use Imagine\Image\ManipulatorInterface;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

/**
 * Class ImageResizer
 * Class for creation thumbs from original image.
 *
 * @package pendalf89\imageresizer
 */
class ImageResizer extends Component
{
	/**
	 * @var array image sizes
	 * For example:
	 *  [
	 *    ['width' => 300, 'height' => 200, 'suffix' => 'md'],
	 *    ['width' => 300, 'height' => 100, 'suffix' => 'sm', 'mode' => 'inset', 'thumbnailBackgroundAlpha' => 0, 'fixedSize' => true],
	 *    ['width' => 200, 'height' => 50],
	 *  ]
	 *
	 * If 'suffix' not set, than width and height be used for suffix name.
	 */
	public $sizes;

	/**
	 * @var string directory with images
	 */
	public $dir;

	/**
	 * @var bool handle directory recursively
	 */
	public $recursively = true;

	/**
	 * @var bool enable rewrite thumbs if its already exists
	 */
	public $enableRewrite = true;

	/**
	 * @var bool enable to delete all images, which sizes not in $this->sizes array.
	 * If true, all thumbs for image will be deleted before resize.
	 */
	public $deleteNonActualSizes = false;

	/**
	 * @var array|string the driver to use. This can be either a single driver name or an array of driver names.
	 * If the latter, the first available driver will be used.
	 *
	 * For more information see yii\imagine\BaseImage
	 */
	public $driver = [Image::DRIVER_GMAGICK, Image::DRIVER_IMAGICK, Image::DRIVER_GD2];

	/**
	 * @var string image creation mode.
	 * For more information see Imagine\Image\ManipulatorInterface
	 * Available values:
	 * "inset" - thumb will not be truncated;
	 * "outbound" - thumb will be truncated.
	 */
	public $mode = ManipulatorInterface::THUMBNAIL_INSET;

	/**
	 * @var string background transparency to use when creating thumbnails in `ImageInterface::THUMBNAIL_INSET`.
	 * If "true", background will be transparent, if "false", will be white color.
	 * Note, than jpeg images not support transparent background.
	 */
	public $bgTransparent = false;

	/**
	 * @var bool want you to get thumbs of a fixed size or not. Has no effect, if $mode set "outbound".
	 *
	 * If "true" then thumbs will be the exact same size as in the $sizes array.
	 * The background will be filled with white color.
	 * Background transparency is controlled by the parameter $bgTransparent.
	 *
	 * If "false", then thumbs will have a proportional size. If the size of the thumbs larger than the original image,
	 * the thumbs will be the size of the original image.
	 */
	public $fixedSize = true;

	/**
	 * Create work directory, if it not exists.
	 */
	public function init()
	{
		$dir = $this->getDirectory();
		if (!is_null($dir) && !is_dir($dir)) {
			$this->createDirectory();
		}
	}

	/**
	 * Resize and save thumbnails from all images.
	 */
	public function resizeAll()
	{
		$filenames = $this->collectFilenames();
		foreach ($filenames as $filename) {
			if ($this->isOriginal($filename)) {
				$this->resize($filename);
			}
		}
	}

	/**
	 * Resize image and save thumbnails.
	 * If rewrite disabled and thumbnail already exists, than skip.
	 *
	 * @param string $filename image filename
	 */
	public function resize($filename)
	{
		if (!self::isImage($filename)) {
			return;
		}
		Image::$driver = $this->driver;
		if ($this->deleteNonActualSizes) {
			$this->deleteFiles($this->getThumbs($filename));
		}
		foreach ($this->sizes as $size) {
			$newFilename = $this->addSuffix($filename, $this->getSuffixBySize($size));
			if (!$this->enableRewrite && file_exists($newFilename)) {
				continue;
			}

			$bgTransparent = isset($size['bgTransparent']) ? $size['bgTransparent'] : $this->bgTransparent;
			$mode          = isset($size['mode']) ? $size['mode'] : $this->mode;
			$fixedSize     = isset($size['fixedSize']) ? $size['fixedSize'] : $this->fixedSize;

			Image::$thumbnailBackgroundAlpha = self::getThumbnailBackgroundAlphaValue($filename, $bgTransparent);
			if ($fixedSize) {
				Image::thumbnail($filename, $size['width'], $size['height'], $mode, true)->save($newFilename);
			} else {
				Image::getImagine()->open($filename)->thumbnail(new Box($size['width'], $size['height']), $mode)->save($newFilename);
			}
		}
	}

	/**
	 * Search all thumbs by original image filename
	 *
	 * @param string $original original image filename
	 *
	 * @return array
	 */
	public function getThumbs($original)
	{
		$originalPathinfo = pathinfo($original);
		$filenames        = scandir($originalPathinfo['dirname']);
		$thumbs           = [];
		foreach ($filenames as $filename) {
			$filename        = "$originalPathinfo[dirname]/$filename";
			$currentPathinfo = pathinfo($filename);
			if ($currentPathinfo['filename'] === $originalPathinfo['filename']) {
				continue;
			}
			if (strpos($currentPathinfo['filename'], $originalPathinfo['filename']) === 0) {
				$thumbs[] = $filename;
			}
		}

		return $thumbs;
	}

	/**
	 * Delete original image with thumbs
	 *
	 * @param string $original original image filename
	 */
	public function deleteWithThumbs($original)
	{
		$filenames   = $this->getThumbs($original);
		$filenames[] = $original;
		$this->deleteFiles($filenames);
	}

	/**
	 * Add suffix to filename
	 *
	 * @param string $filename
	 * @param string $suffix
	 *
	 * @return string
	 */
	protected function addSuffix($filename, $suffix)
	{
		$pathinfo = pathinfo($filename);

		return "$pathinfo[dirname]/$pathinfo[filename]$suffix.$pathinfo[extension]";
	}

	/**
	 * Collect images filenames from dir
	 *
	 * @return array
	 */
	protected function collectFilenames()
	{
		$dir       = $this->getDirectory();
		$filenames = [];
		if ($this->recursively) {
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $file) {
				if (!$file->isDir()) {
					$filenames[] = $file->getPathName();
				}
			}
		} else {
			$filenames = scandir($dir);
			foreach ($filenames as $key => $filename) {
				$filename = "$dir/$filename";
				if (is_dir($filename)) {
					unset($filenames[$key]);
				} else {
					$filenames[$key] = $filename;
				}
			}
		}

		return $filenames;
	}

	/**
	 * If filename doesn`t match mask "-123x123." or doesn`t match suffix, than this original filename.
	 *
	 * @param string $filename
	 *
	 * @return bool
	 */
	public function isOriginal($filename)
	{
		if (preg_match('/-\d+x\d+\./u', $filename)) {
			return false;
		}
		foreach ($this->sizes as $size) {
			if (isset($size['suffix']) && preg_match("/-$size[suffix]\./u", $filename)) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get suffix by size array
	 *
	 * @param array $size
	 *
	 * @return string
	 */
	protected function getSuffixBySize($size)
	{
		if (isset($size['suffix'])) {
			return "-$size[suffix]";
		}

		return "-$size[width]x$size[height]";
	}

	/**
	 * Returns work directory with images
	 *
	 * @return bool|string
	 */
	protected function getDirectory()
	{
		return Yii::getAlias($this->dir);
	}

	/**
	 * Delete files.
	 *
	 * @param array $filenames
	 */
	protected function deleteFiles($filenames)
	{
		foreach ($filenames as $filename) {
			if (file_exists($filename) && !is_dir($filename)) {
				unlink($filename);
			}
		}
	}

	/**
	 * Create directory from $this->dir property
	 */
	protected function createDirectory()
	{
		mkdir($this->getDirectory(), 0755, true);
	}

	/**
	 * Checks is the file a image
	 *
	 * @param $filename
	 *
	 * @return bool
	 * @throws \yii\base\InvalidConfigException
	 */
	protected static function isImage($filename)
	{
		return strpos(FileHelper::getMimeType($filename), 'image/') !== false;
	}

	/**
	 * Returns transparency integer value depending on $bgTransparent value and $filename mime type.
	 *
	 * @param string $filename
	 * @param bool $bgTransparent
	 *
	 * @return int transparency
	 * @throws \yii\base\InvalidConfigException
	 */
	protected static function getThumbnailBackgroundAlphaValue($filename, $bgTransparent)
	{
		// for gif images
		$minTransparent = 0;
		$maxTransparent = 100;
		// inverse values for png images
		if (strpos(FileHelper::getMimeType($filename), '/png') !== false) {
			$minTransparent = 100;
			$maxTransparent = 0;
		}

		return $bgTransparent ? $maxTransparent : $minTransparent;
	}
}
