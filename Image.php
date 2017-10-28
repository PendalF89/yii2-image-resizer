<?php

namespace pendalf89\imageresizer;

use Imagine\Image\BoxInterface;
use Imagine\Image\ManipulatorInterface;
use Imagine\Image\Point;
use Imagine\Image\Palette\RGB;

class Image extends \yii\imagine\Image
{
	/**
	 * @inheritdoc
	 */
	public static function thumbnail($image, $width, $height, $mode = ManipulatorInterface::THUMBNAIL_OUTBOUND, $addBox = false)
	{
		$img = self::ensureImageInterfaceInstance($image);

		/** @var BoxInterface $sourceBox */
		$sourceBox = $img->getSize();
		$thumbnailBox = static::getThumbnailBox($sourceBox, $width, $height);

		if (!$addBox) {
			if (($sourceBox->getWidth() <= $thumbnailBox->getWidth() && $sourceBox->getHeight() <= $thumbnailBox->getHeight()) || (!$thumbnailBox->getWidth() && !$thumbnailBox->getHeight())) {
				return $img->copy();
			}
		}

		$img = $img->thumbnail($thumbnailBox, $mode);

		if ($mode == ManipulatorInterface::THUMBNAIL_OUTBOUND) {
			return $img;
		}

		$size = $img->getSize();

		if ($size->getWidth() == $width && $size->getHeight() == $height) {
			return $img;
		}

		$palette = new RGB();
		$color = $palette->color(static::$thumbnailBackgroundColor, static::$thumbnailBackgroundAlpha);

		// create empty image to preserve aspect ratio of thumbnail
		$thumb = static::getImagine()->create($thumbnailBox, $color);

		// calculate points
		$startX = 0;
		$startY = 0;
		if ($size->getWidth() < $width) {
			$startX = ceil($width - $size->getWidth()) / 2;
		}
		if ($size->getHeight() < $height) {
			$startY = ceil($height - $size->getHeight()) / 2;
		}

		$thumb->paste($img, new Point($startX, $startY));

		return $thumb;
	}
}