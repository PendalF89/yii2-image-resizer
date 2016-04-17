<?php

namespace pendalf89\imageresizer;

use Yii;
use yii\base\Component;
use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;

class ImageResizer extends Component
{
	/**
	 * @var array image sizes
	 */
	public $sizes = [
		['width' => 300, 'height' => 200],
	];

	/**
	 * @var string directory with images
	 */
	public $dir;

	/**
	 * @var bool handle directory recursively
	 */
	public $recursively = true;

	public function runResize()
	{

	}

	protected function collectFilenamesForResize()
	{
		$dir = $this->getDirectory();
		$filenames = [];

		if ($this->recursively) {
			foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir)) as $filename) {
				if (!$filename->isDir()) {
					$filenames[] = $filename;
				}
			}
		} else {

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
}