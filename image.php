<?php

// responsiveimage tag
kirbytext::$tags['image'] = array(
  'attr' => array(
    'width',
    'height',
    'alt',
    'text',
    'title',
    'class',
    'imgclass',
    'linkclass',
    'caption',
    'link',
    'target',
    'popup',
    'rel',
    'srcset',
    'sizes',
    'sources',
    'usescale',
    'sizeattr',
    'mergesrcset'
  ),
  'html' => function($tag) {

    $url            = $tag->attr('image');
    $alt            = $tag->attr('alt');
    $title          = $tag->attr('title');
    $link           = $tag->attr('link');
    $caption        = $tag->attr('caption');
    $srcset         = $tag->attr('srcset');
    $sizes          = $tag->attr('sizes');
    $sources        = $tag->attr('sources');
    $use_scale      = $tag->attr('usescale');
    $size_attr      = $tag->attr('sizeattr');
    $merge_srcset   = $tag->attr('mergesrcset');
    $file           = $tag->file($url);

    // use the file url if available and otherwise the given url
    $url = $file ? $file->url() : url($url);
    $use_scale = $use_scale == "true" || $use_scale == true;
    $size_attr = $size_attr == "true" || $size_attr == true;
    $merge_srcset = $merge_srcset == "true" || $merge_srcset == true;

    // alt is just an alternative for text
    if($text = $tag->attr('text')) $alt = $text;

    // try to get the title from the image object and use it as alt text
    if($file) {

      if(empty($alt) and $file->alt() != '') {
        $alt = $file->alt();
      }

      if(empty($title) and $file->title() != '') {
        $title = $file->title();
      }

    }

    $width = $tag->attr('width');
    $height = $tag->attr('height');
    if($size_attr && $file) {
        $image_scale = kirby_get_image_scale($file);
        $width = round(($tag->attr('width') | $file->width()) / $image_scale);
        $height = round(($tag->attr('height') | $file->height()) / $image_scale);
    }

    if(empty($alt) && $alt != "") $alt = pathinfo($url, PATHINFO_FILENAME);

    // link builder
    $_link = function($image) use($tag, $url, $link, $file) {

      if(empty($link)) return $image;

      // build the href for the link
      if($link == 'self') {
        $href = $url;
      } else if($file and $link == $file->filename()) {
        $href = $file->url();
      } else if($tag->file($link)) {
        $href = $tag->file($link)->url();
      } else {
        $href = $link;
      }

      return html::a(url($href), $image, array(
        'rel'    => $tag->attr('rel'),
        'class'  => $tag->attr('linkclass'),
        'title'  => $tag->attr('title'),
        'target' => $tag->target()
      ));

    };

    //srcset builder
    if($file && (empty($srcset) || $merge_srcset)) {
    	$srcset = kirby_get_srcset($file, $use_scale, $sources, $merge_srcset, $srcset);
    }

    //sizes builder
    if(!$use_scale && $file && empty($sizes)) {
        $classes = ( ! empty( $tag->attr('imgclass'))) ? explode( ' ', $tag->attr('imgclass')) : '';
    	$sizes = kirby_get_sizes($file, $tag->attr('width'), $classes);
	}

    // image builder
    $_image = function($class) use($tag, $url, $alt, $title, $srcset, $sizes, $width, $height) {
      return html::img($url, array(
        'width'  => $width,
        'height' => $height,
        'class'  => $class,
        'title'  => $title,
        'alt'    => $alt,
        'srcset' => $srcset,
        'sizes'  => $sizes
      ));
    };

    if(kirby()->option('kirbytext.image.figure') or !empty($caption)) {
      $image  = $_link($_image($tag->attr('imgclass')));
      $figure = new Brick('figure');
      $figure->addClass($tag->attr('class'));
      $figure->append($image);
      if(!empty($caption)) {
        $figure->append('<figcaption>' . html($caption) . '</figcaption>');
      }
      return $figure;
    } else {
      $class = trim($tag->attr('class') . ' ' . $tag->attr('imgclass'));
      return $_link($_image($class));
    }

  }
);

/**
 *  Returns the image scale based on the filename
 *  2x will return a scale of 2 e.g.
 *
 *  @param   File  $file
 *
 *  @return  int
 */
function kirby_get_image_scale( $file ) {
    $file_name_parts = explode("@", $file->name());
    if(count($file_name_parts)) {
        $last_part = end($file_name_parts);
        return intval(str_replace('x', '', $last_part));
    }
}

/**
 *  Returns the srcset attribute value for a given Kirby file
 *  Generates thumbnails on the fly
 *
 *  @param   File  $file
 *  @uses   kirby_get_srcset_array
 *  @uses   kirby_get_image_scale
 *  @uses   thumb
 *
 *  @return  string
 */
function kirby_get_srcset( $file, $use_scale, $sources, $merge, $srcset_to_merge ) {
    $srcset = "";

    if($use_scale) {
        $scale = kirby_get_image_scale($file);
        if($scale) {
            $srcset .= ''. $file->url() .' '.$scale.'x';
            for ($i=$scale-1; $i > 0; $i--) {
                $thumb = thumb($file, array('width' => $file->width() * ($i / $scale) ));
                $srcset .= ', '. $thumb->url() .' '. $i .'x';
            }

        }
    } else {
        if(empty($sources))
            $sources_arr = kirby_get_srcset_array( $file );
        else {
            $sources_arr = array();
            $sources_parts = explode(',', $sources);
            foreach ($sources_parts as $source) {
                $source = trim($source);
                $parameter = substr($source, -1);
                $value = substr($source, 0, -1);
                if($parameter == 'w')
                    $sources_arr[] = array('width' => intval($value));

                if($parameter == 'h')
                    $sources_arr[] = array('height' => intval($value));
            }
        }

        if($merge && !empty($srcset_to_merge)) {
            $urls_arr = array();
            $widths_arr = array();
            $srcset_parts = explode(',', $srcset_to_merge);
            foreach ($srcset_parts as $srcset_part) {
                $srcset_part = trim($srcset_part);
                $srcset_parts = explode(' ', $srcset_part);

                if(empty($srcset_parts) || count($srcset_parts) > 2)
                    continue;

                $urls_arr[$srcset_parts[1]] = $srcset_parts[0];
                $parameter = substr($srcset_parts[1], -1);
                $value = intval(substr($srcset_parts[1], 0, -1));

                $exists = count(array_filter(
                    $sources_arr,
                    function ($element) use ($parameter, $value) {
                        if($parameter == 'w' && isset($element['width']))
                            return $element['width'] == $value;
                        else if($parameter == 'h' && isset($element['height']))
                            return $element['height'] == $value;
                        else
                            return false;
                    }
                )) != 0;

                if(!$exists) {
                    if($parameter == 'w')
                        $sources_arr[] = array('width' => intval($value));

                    if($parameter == 'h')
                        $sources_arr[] = array('height' => intval($value));
                }
            }
        }

        $srcset .= $file->url() .' '. $file->width() .'w';
        foreach ($sources_arr as $source) {
            if($file->width() == $source['width'])
                continue;
            $width = $source['width'] . 'w';
            $url = $merge && isset($urls_arr[$width]) ? $urls_arr[$width] : null;
            if($url) {
                 $srcset .= ', '. $url .' '. $width;
            } else {
                $thumb = thumb($file, $source);
                if(!$thumb->width())
                    continue;
                $srcset .= ', '. $thumb->url() .' '. $thumb->width() .'w';
            }
        }
    }
    return $srcset;
}

/**
 *  Returns the image sources for a given Kirby file 
 *
 *  @param   File  $file
 *
 *  @return  array
 */
function kirby_get_srcset_array( $file ) {
    $sources_arr = kirby()->option('responsiveimages.sources');

    // set some arbitrary defaults
    if (empty($sources_arr)) {
        $sources_arr = array(
            'small'  => array('width' => 480),
            'medium' => array('width' => 768),
            'large'  => array('width' => 1200),
        );
    }
    return $sources_arr;
}

/**
 *  Returns the sizes attribute value for a given Kirby file
 *
 *  @param   File  $file
 *  @param   int  $width  Optional. Use when you want to force image to a certain width (retina/high-PPi usecase)
 *  @uses   kirby_get_sizes_array()
 *
 *  @return  string
 */
function kirby_get_sizes( $file, $width = null, $imgclass = array() ) {

    $sizes = '';
    $sizes_arr = kirby_get_sizes_array( $file, $width );

    foreach ( $sizes_arr as $key => $size ) {

        // skip if the size should only be applied to a given class
        if (is_string($key) && ! empty($imgclass) && ! in_array($key, $imgclass)) {
            continue;
        }

        // Use 100vw as the size value unless something else is specified.
        $size_value = ( $size['size_value'] ) ? $size['size_value'] : '100vw';
        // If a media length is specified, build the media query.
        if ( ! empty( $size['mq_value'] ) ) {
            $media_length = $size['mq_value'];
            // Use max-width as the media condition unless min-width is specified.
            $media_condition = ( ! empty( $size['mq_name'] ) ) ? $size['mq_name'] : 'max-width';
            // If a media_length was set, create the media query.
            $media_query = '(' . $media_condition . ": " . $media_length . ') ';
        } else {
            // If not meda length was set, $media_query is blank.
            $media_query = '';
        }
        // Add to the source size list string.
        $sizes .= $media_query . $size_value . ', ';
    }
    // Remove the trailing comma and space from the end of the string.
    $sizes = substr( $sizes, 0, -2 );

    return $sizes;
}

/**
 *  Returns the sizes for a given Kirby file
 *
 *  Uses 'responsiveimages.sizes' option to let site owners overwrite the defaults
 *  
 *  @param   File  $file
 *  @param   int  $width  Optional. Use when you want to force image to a certain width (retina/high-PPi usecase)
 *
 *  @return  array
 */
function kirby_get_sizes_array( $file, $width = null ) {

    // let users overwrite the sizes via config
    $sizes_arr = kirby()->option('responsiveimages.sizes');

    // let users overwrite the native image size via attribute
    $img_width = ( empty($width) ? $file->width() : $width ) . 'px';

    // default to the image width
    if (empty($sizes_arr)) {
        $sizes_arr = array(
            array(
                'size_value' => '100vw',
                'mq_value'   => $img_width,
                'mq_name'    => 'max-width'
            ),
            array(
                'size_value' => $img_width
            ),
        );
    } else {
        $sizes_arr = array_map(function($value) use ($img_width) {
            // allow config rules relative to native image size
            return str_replace( '$img_width', $img_width, $value );
        }, $sizes_arr);
    }

    return $sizes_arr;
}