Yii2 image resizer
================
This Yii2 component provide creation thumbnails from original image.
Just add array with sizes and have fun!

Installation
------------
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

Either run

```
php composer.phar require --prefer-dist pendalf89/yii2-image-resizer "*"
```

or add

```
"pendalf89/yii2-image-resizer": "*"
```

to the require section of your `composer.json` file.

Configuration:

```php
'components' => [
    'imageResizer' => [
        'class'                => 'pendalf89\imageresizer\ImageResizer',
        // directory with images
        'dir'                  => '@runtime/images',
        // image sizes. If 'suffix' not set, than width and height be used for suffix name.
        'sizes'                => [
           ['width' => 800, 'height' => null, 'suffix' => 'lg'], // in this case height will be calculated automatically
	   ['width' => 300, 'height' => 200, 'suffix' => 'md'],
	   ['width' => 300, 'height' => 100, 'suffix' => 'sm', 'mode' => 'inset', 'thumbnailBackgroundAlpha' => 0, 'fixedSize' => true],
	   ['width' => 200, 'height' => 50], // without suffix. Not recommended.
        ],
        // handle directory recursively
        'recursively'          => true,
        // enable rewrite thumbs if its already exists
        'enableRewrite'        => true,
        // array|string the driver to use. This can be either a single driver name or an array of driver names.
        // If the latter, the first available driver will be used.
        'driver'               => ['gmagick', 'imagick', 'gd2'],
        // image creation mode.
        'mode'                 => 'inset',
        // enable to delete all images, which sizes not in $this->sizes array
        'deleteNonActualSizes' => false,
        // background transparency to use when creating thumbnails in `ImageInterface::THUMBNAIL_INSET`.
	// If "true", background will be transparent, if "false", will be white color.
	// Note, than jpeg images not support transparent background.
        'bgTransparent' => false,
        // want you to get thumbs of a fixed size or not. Has no effect, if $mode set "outbound".
	// If "true" then thumbs will be the exact same size as in the $sizes array.
	// The background will be filled with white color.
	// Background transparency is controlled by the parameter $bgTransparent.
	// If "false", then thumbs will have a proportional size. If the size of the thumbs larger than the original image,
	// the thumbs will be the size of the original image.
        'fixedSize' => true,
    ],
],
```

Usage
------------

```php
// resize all images.
Yii::$app->imageResizer->resizeAll();
// resize one image.
Yii::$app->imageResizer->resize('path/to/original/image.png');
// returns thumbs filenames from original image.
Yii::$app->imageResizer->getThumbs('path/to/original/image.png');
```
