<?php

// image tag
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
    'sizes'
  ),
  'html' => function($tag) {

    $url     = $tag->attr('image');
    $alt     = $tag->attr('alt');
    $title   = $tag->attr('title');
    $link    = $tag->attr('link');
    $caption = $tag->attr('caption');
    $srcset  = $tag->attr('srcset');
    $sizes   = $tag->attr('sizes');
    $file    = $tag->file($url);

    // use the file url if available and otherwise the given url
    $url = $file ? $file->url() : url($url);

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

    if(empty($alt)) $alt = pathinfo($url, PATHINFO_FILENAME);

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
    if($file && empty($srcset)) {
    	$srcset = $file->url() .' '. $file->width() .'w';
    	$sources_arr = kirby()->option('responsiveimages.sources');

    	// set some arbitrary defaults
    	if (empty($sources_arr)) {
    		$sources_arr = array(
				array('width' => 320),
				array('width' => 768),
				array('width' => 1200),
			);
    	}

    	foreach ($sources_arr as $source) {
    		$thumb = thumb($file, $source);
    		$srcset .= ', '. $thumb->url() .' '. $thumb->width() .'w';
    	}

    }

    //sizes builder
    if($file && empty($sizes)) {
    	
    	// let users overwrite the native image size via attribute
    	$img_width = ( empty($tag->attr('width')) ? $file->width() : $tag->attr('width') ) . 'px';

    	// let users overwrite the sizes via config
    	$sizes_arr = kirby()->option('responsiveimages.sizes');

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
    	}

		foreach ( $sizes_arr as $key => $size ) {
			// skip if the size is only applied to a class
			if (is_string($key) && $tag->attr('imgclass') && $key !== $tag->attr('imgclass')) {
				continue;
			}

			// allow config rules relative to native image size
			$size = str_replace( '$img_width', "$img_width", $size );

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
	}

    // image builder
    $_image = function($class) use($tag, $url, $alt, $title, $srcset, $sizes) {
      return html::img($url, array(
        'width'  => $tag->attr('width'),
        'height' => $tag->attr('height'),
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