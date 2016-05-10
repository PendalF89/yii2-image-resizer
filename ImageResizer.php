<?php

namespace pendalf89\imageresizer;

use Imagine\Image\ManipulatorInterface;
use Yii;
use yii\base\Component;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use yii\imagine\Image;

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
	 *        ['width' => 300, 'height' => 200],
	 *        ['width' => 300, 'height' => 100],
	 *    ]
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
	public $enableRewrite = false;

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
	 */
	public $mode = ManipulatorInterface::THUMBNAIL_OUTBOUND;

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
		Image::$driver = $this->driver;

		if ($this->deleteNonActualSizes) {
			$this->deleteFiles($this->getThumbs($filename));
		}

		foreach ($this->sizes as $size) {
			$newFilename = $this->addPostfix($filename, $this->getPostfixBySize($size));

			if (!$this->enableRewrite && file_exists($newFilename)) {
				continue;
			}

			Image::thumbnail($filename, $size['width'], $size['height'], $this->mode)->save($newFilename);
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

			if (mb_substr_count($currentPathinfo['filename'], $originalPathinfo['filename'], Yii::$app->charset)) {
				$thumbs[] = $filename;
			}
		}

		return $thumbs;
	}

	/**
	 * Delete original image with filenames
	 *
	 * @param string $original original image filename
	 */
	public function deleteWithThumbs($original)
	{
		$filenames = $this->getThumbs($original);
		$filenames[] = $original;
		$this->deleteFiles($filenames);
	}

	/**
	 * Add postfix to filename
	 *
	 * @param string $filename
	 * @param string $postfix
	 *
	 * @return string
	 */
	protected function addPostfix($filename, $postfix)
	{
		$pathinfo = pathinfo($filename);

		return "$pathinfo[dirname]/$pathinfo[filename]$postfix.$pathinfo[extension]";
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
	 * If filename doesn`t match mask "-123x123.", than this original filename.
	 *
	 * @param string $filename
	 *
	 * @return bool
	 */
	protected function isOriginal($filename)
	{
		return !(boolean) preg_match('/-\d+x\d+\./u', $filename);
	}

	/**
	 * Get postfix by size array
	 * @param array $size
	 *
	 * @return string
	 */
	protected function getPostfixBySize($size)
	{
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
}