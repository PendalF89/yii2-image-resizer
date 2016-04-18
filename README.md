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
        // image sizes
        'sizes'                => [
            ['width' => 300, 'height' => 200],
            ['width' => 150, 'height' => 150],
        ],
        // handle directory recursively
        'recursively'          => true,
        // enable rewrite thumbs if its already exists
        'enableRewrite'        => false,
        // array|string the driver to use. This can be either a single driver name or an array of driver names.
        // If the latter, the first available driver will be used.
        'driver'               => ['gd2', 'imagick', 'gmagick'],
        // image creation mode.
        'mode'                 => 'outbound',
        // enable to delete all images, which sizes not in $this->sizes array
        'deleteNonActualSizes' => false,
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