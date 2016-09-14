# Kirby Responsive Images

A simple drop-in implementation of [responsive images](https://responsiveimages.org/) for Kirby CMS. Automatically generates thumbnails for low-resolution devices.

## Installation

[Download the latest release](https://github.com/jancbeck/kirby-responsive-images/releases/) and unpack to your kirby `/site/plugins` directory.

You can also rename the `responsive-images.php` file to `image.php` and move it to the `site/tags` directory if you prefer to keep all your custom tags there.

## Usage

There nothing you need to change in order to have responsive image support. Just include images as you would do normally:

`(image:workflow@3x.jpg link:workflow@3x.jpg width:1244)`

Make sure your `/thumbs` directory is present and writable and ImageMagick is available.

Although the plugin works without configuration, these options are available and can be added to your `/site/config/config.php`:

It is also possible to link the image to one of the generated sizes using the keyword set via the `responsiveimages.sources` config. The following example will link to the smallest possible image size:

`(image:workflow@3x.jpg link:small width:1244)`

### Sizes

To control the sizes attribute you can use [media queries](https://ericportis.com/posts/2014/srcset-sizes/).

```
<?php
c::set('responsiveimages.sizes', array( 
    array(
        'size_value' => '10em',
        'mq_value'   => '60em',
        'mq_name'    => 'min-width'
    ),
    array(
        'size_value' => '$img_width',
        'mq_value'   => '30em',
        'mq_name'    => 'min-width'
    ),
    'alignleft' => array(
        'size_value' => 'calc(50vw - 30px)'
    ),
));
```

1. `$img_width` is a placeholder that will be replaced by the images actual pixel width.
2. The array key will be matched against the `classes` attribute of the image. In the example above `'size_value' => 'calc(50vw - 30px)'` will only be added if the image has the `alignleft` attribute.

### Sources

By default, the plugin will generate thumbnails that are 480, 768 and 1200 pixels wide. You can overwrite these settings like these: 

```
<?php
c::set('responsiveimages.sources', array( 
    'small'  => array('width' => 444),
    'medium' => array('width' => 666),
    'large'  => array('width' => 999, 'grayscale' => true) // good for debugging
));
```

1. The key names may be used to link the image or for default (see *Usage* and *Fallbacks* section).
2. Each array item takes the same arguments as Kirbys [thumb()](http://getkirby.com/docs/cheatsheet/helpers/thumb) function (`quality`, `blur`, `upscale` etc..).

### Fallbacks

Browsers that support responive images will ignore the `src` attribute of the image and pick what ever is best for their device from the `srcsset` attribute.
However, you might want to control what older browsers that don't support responsive images load. For that use case there is an option to set the default size based on the key name of the size (explained in the previous section).

```
<?php
c::set('responsiveimages.defaultsource', 'small');
```

This will show older browsers the "small" image size. If this option is not set, browsers will load the original image.

## Support

Please [open an issue](https://github.com/jancbeck/kirby-responsive-images/issues/new) for support.

## Contributing

Please contribute using [Github Flow](https://guides.github.com/introduction/flow/). Create a branch, add commits, and [open a pull request](https://github.com/jancbeck/kirby-responsive-images/compare/).
See also the [original issue](https://github.com/getkirby/kirby/issues/73#issuecomment-149279023). 

## License

The code is available under the [MIT license](https://opensource.org/licenses/MIT).