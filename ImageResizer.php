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
	 *		['width' => 300, 'height' => 200],
	 *		['width' => 300, 'height' => 100],
	 *	]
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
	 * @var bool enable to delete all images, which sizes not in $this->sizes array
	 */
	public $deleteNonActualSizes = true;

	/**
	 * Run image resizing.
	 */
	public function run()
	{
		$this->setSizesPostfixes();
		$filenames = $this->collectFilenames();

		if ($this->deleteNonActualSizes) {
			$this->cleanDirectory($filenames);
		}

		foreach ($filenames as $filename) {
			if ($this->isOriginal($filename)) {
				$this->resize($filename);
			}
		}
	}

	/**
	 * Resize and save new images.
	 * If rewrite disabled and filename already exists, than skip.
	 *
	 * @param string $filename image filename
	 */
	protected function resize($filename)
	{
		Image::$driver = $this->driver;

		foreach ($this->sizes as $size) {
			$newFilename = $this->addPostfix($filename, $size['postfix']);

			if (!$this->enableRewrite && file_exists($newFilename)) {
				continue;
			}

			Image::thumbnail($filename, $size['width'], $size['height'], $this->mode)->save($newFilename);
		}
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
		$dir = $this->getDirectory();
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
	 * Set postfixes to sizes array
	 */
	protected function setSizesPostfixes()
	{
		foreach ($this->sizes as $key => $size) {
			$this->sizes[$key]['postfix'] = "-$size[width]x$size[height]";
		}
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
	 * Delete all non-original images.
	 *
	 * @param array $filenames
	 */
	protected function cleanDirectory($filenames)
	{
		foreach ($filenames as $filename) {
			if (!$this->isOriginal($filename)) {
				unlink($filename);
			}
		}
	}
}