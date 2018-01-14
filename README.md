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
            ['width' => 300, 'height' => 200, 'suffix' => 'md'],
            ['width' => 150, 'height' => 150, 'suffix' => 'sm'],
            ['width' => 200, 'height' => 50],
        ],
        // handle directory recursively
        'recursively'          => true,
        // enable rewrite thumbs if its already exists
        'enableRewrite'        => true,
        // array|string the driver to use. This can be either a single driver name or an array of driver names.
        // If the latter, the first available driver will be used.
        'driver'               => ['gd2', 'imagick', 'gmagick'],
        // image creation mode.
        'mode'                 => 'inset',
        // enable to delete all images, which sizes not in $this->sizes array
        'deleteNonActualSizes' => false,
        // background alpha (transparency) to use when creating thumbnails in `ImageInterface::THUMBNAIL_INSET`
	    // mode with both width and height specified. Default is solid.
        'thumbnailBackgroundAlpha' => 0,
        // whether add thumbnail box if source image less than thumbnail size.
        'addBox' => true,
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
