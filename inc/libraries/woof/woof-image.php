<?php

class WOOF_Image extends WOOF_File {

  private $size;
  protected $_attr;
  protected $exif;
  
  public static $COLOR_SEPIA = '#5f370e';
  
  function __construct($path, $url, $attr = array("alt" => "")) {
    parent::__construct($path, $url);
    $this->_attr = $attr;
  }
  
  static function is_image() {
    return true;
  }
  
  
  /* 
  
  This function stands-in for wp_load_image now that it is deprecated in WP3.5 
  
  Better support for image editors may be added in the future, 
  which is an alternative direction to this class
  
  */
  
  static function load($file) {
  
  	if ( is_numeric( $file ) )
  		$file = get_attached_file( $file );

  	if ( ! is_file( $file ) )
  		return sprintf(__('File &#8220;%s&#8221; doesn&#8217;t exist?'), $file);

  	if ( ! function_exists('imagecreatefromstring') )
  		return __('The GD image library is not installed.');

  	// Set artificially high because GD uses uncompressed images in memory
  	@ini_set( 'memory_limit', apply_filters( 'image_memory_limit', WP_MAX_MEMORY_LIMIT ) );
  	$image = imagecreatefromstring( file_get_contents( $file ) );

  	if ( !is_resource( $image ) )
  		return sprintf(__('File &#8220;%s&#8221; is not an image.'), $file);

  	return $image;
  
  }
  
  static function imagecopymerge_alpha($dst_im, $src_im, $dst_x, $dst_y, $src_x, $src_y, $src_w, $src_h, $pct, $trans = NULL) {
    $dst_w = imagesx($dst_im);
    $dst_h = imagesy($dst_im);

    // bounds checking
    $src_x = max($src_x, 0);
    $src_y = max($src_y, 0);
    $dst_x = max($dst_x, 0);
    $dst_y = max($dst_y, 0);
    if ($dst_x + $src_w > $dst_w)
      $src_w = $dst_w - $dst_x;
    if ($dst_y + $src_h > $dst_h)
      $src_h = $dst_h - $dst_y;

    for($x_offset = 0; $x_offset < $src_w; $x_offset++)
      for($y_offset = 0; $y_offset < $src_h; $y_offset++)
      {
        // get source & dest color
        $srccolor = imagecolorsforindex($src_im, imagecolorat($src_im, $src_x + $x_offset, $src_y + $y_offset));
        $dstcolor = imagecolorsforindex($dst_im, imagecolorat($dst_im, $dst_x + $x_offset, $dst_y + $y_offset));

        // apply transparency
        if (is_null($trans) || ($srccolor !== $trans))
        {
          $src_a = $srccolor['alpha'] * $pct / 100;
          // blend
          $src_a = 127 - $src_a;
          $dst_a = 127 - $dstcolor['alpha'];
          $dst_r = ($srccolor['red'] * $src_a + $dstcolor['red'] * $dst_a * (127 - $src_a) / 127) / 127;
          $dst_g = ($srccolor['green'] * $src_a + $dstcolor['green'] * $dst_a * (127 - $src_a) / 127) / 127;
          $dst_b = ($srccolor['blue'] * $src_a + $dstcolor['blue'] * $dst_a * (127 - $src_a) / 127) / 127;
          $dst_a = 127 - ($src_a + $dst_a * (127 - $src_a) / 127);
          $color = imagecolorallocatealpha($dst_im, $dst_r, $dst_g, $dst_b, $dst_a);

          // $background = imagecolorallocate($dst_im, 0, 0, 0);
          // imagecolortransparent($dst_im, $background);

          // paint
          if (!imagesetpixel($dst_im, $dst_x + $x_offset, $dst_y + $y_offset, $color))
            return false;
          imagecolordeallocate($dst_im, $color);
        }
      }
    return true;
  }


  /**
	 * Based on the WordPress-native function image_resize
	 */

	public static function image_resize( $file, $max_w, $max_h, $crop = false, $far = false, $iar = false, $dest_path = null, $jpeg_quality = 90, $up = true, $co = "0,0" ) {
		$image = WOOF_Image::load( $file );
    
		if ( !is_resource( $image ) )
			return new WP_Error('error_loading_image', $image);

		$size = @getimagesize( $file );

		if ( !$size )
				return new WP_Error('invalid_image', __('Could not read image size', WOOF_DOMAIN), $file);

		list($orig_w, $orig_h, $orig_type) = $size;
		
		$mime_type = image_type_to_mime_type($orig_type);
		
		$dims = self::image_resize_dimensions($orig_w, $orig_h, $max_w, $max_h, $crop, $far, $iar, $up, $co);
		
		
		if ( !$dims ){
			$dims = array(0,0,0,0,$orig_w,$orig_h,$orig_w,$orig_h);
		}
		list($dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h) = $dims;

    
    $newimage = imagecreatetruecolor( $dst_w, $dst_h );
    imagealphablending($newimage, false);
    imagesavealpha($newimage, true);
    $transparent = imagecolorallocatealpha($newimage, 255, 255, 255, 127);
    imagefilledrectangle($newimage, 0, 0, $dst_w, $dst_h, $transparent);
    imagecopyresampled( $newimage, $image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);

		// convert from full colors to index colors, like original PNG.
		if ( IMAGETYPE_PNG == $orig_type && !imageistruecolor( $image ) )
			imagetruecolortopalette( $newimage, false, imagecolorstotal( $image ) );

		// we don't need the original in memory anymore
		imagedestroy( $image );
		$info = pathinfo($dest_path);
		$dir = $info['dirname'];
		$ext = $info['extension'];
		$name = basename($dest_path, ".{$ext}");
		
		$destfilename = "{$dir}/{$name}.{$ext}";
		
		if (!self::save_image($destfilename, $newimage, $mime_type, array("q" => $jpeg_quality))) {
			return new WP_Error('resize_path_invalid', __( 'Resize path invalid' ));
	  }

		// Set correct file permissions
		$stat = stat( dirname( $destfilename ));
		$perms = $stat['mode'] & 0000666; //same permissions as parent folder, strip off the executable bits
		@ chmod( $destfilename, $perms );

		return $destfilename;
  }


  protected function infer_content_type($r) {
    if (isset($r["extension"])) {
      
      $extension = strtolower($r["extension"]);
      
      if ($extension == "png") {
        return "image/png";
      } else if ($extension == "gif") {
        return "image/gif";
      } else if ($extension == "svg") {
        return "image/svg";
      } else {
        return "image/jpeg";
      }
      
    } else {
      return $this->mime();
    }
  }

  public static function save_image($path, $image, $mime_type, $attr = array(), $destroy = true) {

    $r = wp_parse_args( $attr, array( "q" => 90, "alpha" => true ) );

    switch ( $mime_type ) {
  		case 'image/jpeg':
  			return imagejpeg( $image, $path, $r["q"] );
  		case 'image/png':
        if (isset($r["alpha"])) {
          imagesavealpha($image, true);
        }
  			return imagepng($image, $path);
  			
  		case 'image/gif':
  			return imagegif($image, $path);
  		default:
  			return false;
  	}
    
    if ($destroy) {
      imagedestroy( $image );
    }

    
  }

  /**
   * Based on the Wordpress-core function image_resize_dimensions
   */
  public static function image_resize_dimensions($orig_w, $orig_h, $dest_w, $dest_h, $crop = false, $far = false, $iar = false, $up = true, $co = "0,0") {
        
    
	  if ($orig_w <= 0 || $orig_h <= 0)
  		return false;
  	// at least one of dest_w or dest_h must be specific
  	if ($dest_w <= 0 && $dest_h <= 0)
  		return false;

    $c_orig_h = $orig_h;
    $c_orig_w = $orig_w;
  
    
  	if ( $crop ) {
  	  
  		// crop the largest possible portion of the original image that we can size to $dest_w x $dest_h
  		$aspect_ratio = $orig_w / $orig_h;

      if ($up && $crop !== 2) {
        $new_w = $dest_w;
        $new_h = $dest_h;
      } else {
    		$new_w = min($dest_w, $orig_w);
    		$new_h = min($dest_h, $orig_h);
      }
  
      
  		if ( !$new_w ) {
  			$new_w = intval($new_h * $aspect_ratio);
  		}

  		if ( !$new_h ) {
  			$new_h = intval($new_w / $aspect_ratio);
  		}
		
  	  $size_ratio = max($new_w / $orig_w, $new_h / $orig_h);


  		$c_orig_w = intval($new_w / $size_ratio);
  		$c_orig_h = intval($new_h / $size_ratio);
      
  		$crop_w = round($new_w / $size_ratio);
  		$crop_h = round($new_h / $size_ratio);

  		$s_x = floor( ($orig_w - $crop_w) / 2 );
  		$s_y = floor( ($orig_h - $crop_h) / 2 );

                      
  	} else {
      // don't crop, just resize using $dest_w x $dest_h as a maximum bounding box
  		$crop_w = $dest_w;
  		$crop_h = $dest_h;

  		$s_x = 0;
  		$s_y = 0;

  		$new_w = $crop_w;
  		$new_h = $crop_h;

  	  $size_ratio = max($new_w / $orig_w, $new_h / $orig_h);
    }

    // apply anchoring

    if( $far ) {
    
      // force aspect ratio
      
      // determine crop offsets
      $coff = array(0,0);

      $crop_parts = explode(",", $co);
    
      if (count($crop_parts) == 1) {
        if (is_numeric($crop_parts[0])) {
          $coff[0] = $coff[1] = (int) $crop_parts[0];
        }
      } else {

        if (is_numeric($crop_parts[0]) ) {
          $coff[0] = (int) $crop_parts[0];
        }

        if (is_numeric($crop_parts[1]) ) {
          $coff[1] = (int) $crop_parts[1];
        }
      }
    
      // for imagecopyresampled, the offsets must actually be scaled to the original size of the image, so do that
      $c_x = round( $coff[0] / $size_ratio );
      $c_y = round( $coff[1] / $size_ratio );

    
      $use_w = $c_orig_w;
      $use_h = $c_orig_h;
      
      if ($crop === 2 || $crop === "2") {
    
        $use_w = $new_w;
        $use_h = $new_h;

      }
      
      switch ( strtoupper($far) ) {
  			case 'L' :
  			case 'TL':
  			case 'BL':
  				$s_x = 0; 
  				break;
  			case 'R' :
  			case 'TR':
  			case 'BR':
  				$s_x = round(($orig_w  - $use_w));
  				break;
  			default: // T, B, C
  				$s_x = round(($orig_w  - $use_w) / 2 );
  		}

      switch ( strtoupper($far) ) {
  			case 'BL':
  			case 'B':
  			case 'BR':
  				$s_y = round(($orig_h - $use_h));
  				break;
  			case 'T' :
  			case 'TL':
  			case 'TR':
  				$s_y = 0;
  				break;
  			default: // L, C, R
  				$s_y = round(($orig_h - $use_h) / 2);
  		}
  		
      // now add offsets, ensuring that the boundaries do not exceed the original uncropped resize 
      // otherwise there'll be black-space

  		//$s_x = min( max( 0, $s_x + $c_x ), ( $orig_w - $c_orig_w ) ); 
  		$s_y = min( max( 0, $s_y + $c_y ), ( $orig_h - $use_h ) ); 
        
      if ( $iar ) {
        //ignore aspect ratio and resize the image
        $crop_w = $orig_w;
        $crop_h = $orig_h;

        $s_x = 0;
        $s_y = 0;
   
        $new_w = ceil($orig_w * $use_h / $orig_w);
        $new_h = ceil($orig_h * $use_h / $orig_h);
    	}
 
    	// if the resulting image would be the same size we don't want to resize it
    	if ( $new_w == $orig_w && $new_h == $orig_h )
    		return false;

    }

    if ($crop === 2 || $crop === "2") {
      
      // check that the image won't be larger than the total width
      
      $crop_h = $dest_h;
      $crop_w = $dest_w;
      
      if ($crop_w > $orig_w) {  
        $crop_w = $orig_w;
        $new_w = $crop_w;
        
        if ($dest_h <= 0) { // unspecified destination width
          $crop_h = $orig_h;
          $new_h = $crop_h;
        } 
      
      }
    
      if ($crop_h > $orig_h) {
        $crop_h = $orig_h;
        $new_h = $crop_h;
        
        if ($dest_w <= 0) { // unspecified destination width
          $crop_w = $orig_w;
          $new_w = $crop_w;
        } 
      }
      
      
    }
    
  	// the return array matches the parameters to imagecopyresampled()
  	// int dst_x, int dst_y, int src_x, int src_y, int dst_w, int dst_h, int src_w, int src_h
  	return array( 0, 0, (int) $s_x, (int) $s_y, (int) $new_w, (int) $new_h, (int) $crop_w, (int) $crop_h );
  
  }
  

  
	
	public static function parse_color($color) {
	  
	  $ret = array( "hex" => "#FFFFFF", "rgb" => array(255,255,255));
	  
    if (is_string($color) && preg_match('/#?[A-Z0-9a-z]{3,6}/', $color)) {
      // a hex value
      $ret["hex"] = "#".preg_replace("/#/", "", $color);
      $ret["rgb"] = WOOF::hex_to_rgb($color);

    } else {
      
      if (is_array($color) && count($color) == 3) {

        $rgb = array( (int) $color[0], (int) $color[1], (int) $color[2] );

      } else if (is_array($color) && count($color) == 4) {
        $rgb = array( (int) $color[0], (int) $color[1], (int) $color[2], (int) $color[3] );
      }
        else {
        
        $parts = explode(",", $color);
        
        if (count($parts) == 3) {
          $rgb = array( (int) $parts[0], (int) $parts[1], (int) $parts[2] );
        } else if (count($parts) == 4) {
          $rgb = array( (int) $parts[0], (int) $parts[1], (int) $parts[2], (int) $parts[3] );
        }

      }  
      
      if ($rgb) {
        $ret["hex"] = WOOF::rgb_to_hex($rgb[0], $rgb[1], $rgb[2]);
        $ret["rgb"] = $rgb;
      } else {
        trigger_error('Input color '.$color.' format incorrect. Please specify either a hex color string, a string with 3 comma separated values 0 - 255 for the r,g,b components, or an array of r,g,b integers from 0 - 255)', E_WARNING);
      } 
      
    }
      
    

    return $ret;
    
  }

  public function tag( $attr = array() ) {
    
    $r = wp_parse_args(
      $attr, 
      array(
        "width" => $this->width(),
        "height" => $this->height(),
        "root_relative" => false
      )
    );
    
    $r = wp_parse_args($this->attr(), $r);
    
    if (isset($r["rr"])) {
      $r["root_relative"] = $r["rr"];
    }
    
    $classes = array();
    
    if (isset($r["class"])) {
      $classes = explode(" ", $r["class"]);
    }
    
    // ensure src is not overridden
    
    if ($r["root_relative"]) {
      $r["src"] = $this->url();
    } else {
      $r["src"] = $this->permalink();
    }
    
    if (WOOF::is_true_arg($r, "fluid")) {
      unset($r["width"], $r["height"]);
      
      $r["data-width"] = $this->width();
      $r["data-height"] = $this->height();
    }

    $lazy = WOOF::is_true_arg($r, "lazy");
    
    if ($lazy) {
      $r["data-original"] = $r["src"];
      $classes[] = "lazy";
      unset($r["src"]);
      
      $ratio = $this->width() / $this->height();
    }
    
    if (count($classes)) {
      $r["class"] = implode(" ", $classes);
    }
  
    // ensure that preset args are not used as an attribute
    unset($r["root_relative"], $r["fluid"], $r["lazy"]);
    
    $ret = '';
    $ret .= WOOF_HTML::tag("img", $r, null, true);
    
    return $ret;
  }
  
  public function attr($key = null, $val = null) {
    if (is_null($key)) {
      return $this->_attr;
    } else {
      if (!is_null($val)) {
        $this->_attr[$key] = $val;
        return $val;
      } else {
        
        if (isset($this->_attr[$key])) {
          return esc_attr( $this->_attr[$key] );
        }
        
      }
      
    }
    
    return "";
  }

  public static function is_file_name($path) {
    $pi = pathinfo($path);
    return isset($pi["extension"]) && $pi["extension"] != "" && isset($pi["filename"]) && $pi["filename"] != "";
  }

  public static function is_absolute_url($path) {
    return preg_match("/^http(s?):\/\//", $path) && self::is_file_name($path);
  }
  
  public static function is_root_relative_url($path) {
    return preg_match("/^\//", $path) && self::is_file_name($path); 
  }

  public static function is_relative_url($path) {
    return preg_match("/\//", $path) && self::is_file_name($path); 
  }


  protected function resolve_assoc_url($file) {
    
    if ( is_object( $file ) && ( get_class($file) == "WOOF_Image" || is_subclass_of( $file, "WOOF_Image" ) ) ) {
      return $file->permalink();
    }   
    
    if (self::is_absolute_url($file)) {
      return $file;
    } else if (self::is_relative_url($file) || self::is_file_name($file)) {
      $pi = pathinfo($this->permalink());
      return $pi["dirname"]."/".$file;
    } else if (self::is_root_relative_url($file)) {
      return $file;
    } else {
      
      // assume this is a suffix to place before the extension
      $pi = pathinfo($this->permalink());
      
      return $pi["dirname"]."/".$pi["filename"].$file.".".$pi["extension"];
    }

  }
  
  public function picture($args = array()) {
    
    global $wf;
    
    $r = wp_parse_args($args, array("class" => "picture", "2x" => false));
    
    $alt = $this->attr("alt");
    $title = $this->attr("title");
    
    $lazy = WOOF::is_true_arg($r, "lazy");
    
    $shim = WOOF::is_true_arg($r, "shim") || $lazy;
    
    $classes = explode(" ", $r["class"]);
    
    if ($lazy) {
      $classes[] = "lazy";
    }

    $r["class"] = implode(" ", $classes);

    // generate the tag
    
    $image_1x = $this;
    
    $html = "";
    
    $inner_html = "";
    
    foreach ($r as $key => $value) {
      
      $is_2x = false;
      $min = false;
      $max = false;
      $media = array();
      $image_2x = false;

      if (preg_match( "/2x(?:-min-)(\d+)(?:-max-)(\d+)/", $key, $matches)) {

        $is_2x = true;
        $min = $matches[1];
        $max = $matches[2];
        
      } else if (preg_match( "/2x(?:-min-)(\d+)/", $key, $matches)) {

        $is_2x = true;
        $min = $matches[1];
        
      } else if (preg_match( "/2x(?:-max-)(\d+)/", $key, $matches)) {
        
        $is_2x = true;
        $max = $matches[1];
        
      } else if (preg_match( "/2x/", $key, $matches)) {
        
        $is_2x = true;

      } else if (preg_match( "/min-(\d*)-max-(\d*)/", $key, $matches ) ) {
        
        $min = $matches[1];
        $max = $matches[2];
      
      } else if (preg_match( "/min-(\d*)/", $key, $matches ) ) {
        
        $min = $matches[1];
        
      } else if (preg_match( "/max-(\d*)/", $key, $matches ) ) {
        
        $max = $matches[1];
        
      }
        
      if ($min) {
        $media[] = "(min-width: ".$min."px)";
      } 

      if ($max) {
        $media[] = "(max-width: ".$max."px)";
      } 
    
  
      if ($is_2x) {
        
        if ( $value === "1" || $value === true || $value === 1 ) {

          // this is the 2x image, scale it down to half to build the 1x
          $image_1x = $this->resize( array("w" => floor( $this->width() / 2 ) ) );
          $url = $this->permalink();
          
        } else {
          
          
          $url = self::resolve_assoc_url( $value );
          
        }

        $and = "";
        
        if (count($media)) {
          $and = " and ";
        }
        
        $attr = array(
          "data-src" => $url
        );
        
        
        // output the 2x
        $attr["data-media"] = implode(" and ", $media).$and."(min-device-pixel-ratio: 2.0)";
        $inner_html .= WOOF_HTML::tag("div", $attr, "" );

        $attr["data-media"] = implode(" and ", $media).$and."(-webkit-min-device-pixel-ratio: 1.5)";
        $inner_html .= WOOF_HTML::tag("div", $attr, "" );

        $attr["data-media"] = implode(" and ", $media).$and."(min--moz-device-pixel-ratio: 1.5)";
        $inner_html .= WOOF_HTML::tag("div", $attr, "" );
        
      } else if ($min || $max) {
        
        if (preg_match( "/([0-9\?]+)x([0-9\?]+)/", $value, $matches ) ) {
          
          $resize_args = array("w" => $matches[1], "h" => $matches[2]);
          
          if (strpos($width, "?") !== false) {
            unset($resize_args["w"]);
          }
          
          if (strpos($height, "?") !== false) {
            unset($resize_args["h"]);
          }
          
          $url = $this->resize( $resize_args )->permalink();
          
        } else {
          
          $url = self::resolve_assoc_url( $value );
          
        }
        
        $inner_html .= WOOF_HTML::tag("div", array("data-src" => $url, "data-media" => implode(" and ", $media)), "" );
        
      }
      
    }
    
    // add a style attribute for the width
    
    $w = "";
    
    $r["style"] = "";
    
    if (isset($r["width"])) {
      $w = $r["width"];
    }

    if (isset($r["w"])) {
      $w = $r["w"];
    }
    
    if (is_numeric($w)) {
      $r["style"] .= "width: ".$w."px";
    } else if ($w == "1x") {
      $r["style"] .= "width: ".$image_1x->width()."px";
    }

    $iw = $image_1x->width();
    $ih = $image_1x->height();

    $r["data-height"] = $ih;
    $r["data-width"] = $iw;
    
    $attr = array(
      "data-picture" => "",
      "data-alt" => $alt, 
      "data-title" => $title
    );

    if (isset($r["alt"])) {
      $attr["data-alt"] = $r["alt"];
    }

    if (isset($r["title"])) {
      $attr["data-title"] = $r["title"];
    }
    
    $pr = array_merge($r, $attr );
    
    unset($pr["itemprop"]);
    
    $html .= WOOF_HTML::open( "div", $pr, false, true );

    // output the 1x first (this is critical)
    $html .= WOOF_HTML::tag( "div", array("data-src" => $image_1x->permalink() ), "" );
    
    $html .= $inner_html;
    
    // output the fallback content
    $html .= WOOF_HTML::tag("noscript", array(), $image_1x->fluid($r) );

    if ($shim) {
      $shim_image = $wf->spacer("w=$iw&h=$ih");
      $html .= $shim_image->tag("class=shim");
    }
    
    $html .= WOOF_HTML::close( "div" );
    
    return $html;

  }
  
  public function picture_2x($args = array()) {

    $r = wp_parse_args( 
      $args
    );
    
    $r["2x"] = true;

    return $this->picture( $r );

  }
  
  
  
  private function infer_srcset_sizes( $spec ) {
    
    $ret = array();
    
    $ss_sizes = preg_split( "/\s?@\s?/", $spec );
    
    $srcset_spec = "";
    $sizes_spec = "";
    
    if (sizeof($ss_sizes) == 2) {
      $srcset_spec = $ss_sizes[0];
      $sizes_spec = $ss_sizes[1];
    } else {
      $srcset_spec = $ss_sizes[0];
    }
    
    // now work out the srcset attribute
    $ss = preg_split("/\s?,\s?/", $srcset_spec);
      
    $srcset = array();
    
    $smallest = false;
    
    foreach ($ss as $ss_item) {
      $info = array();
      $parts = preg_split("/\s+/", $ss_item);
      
      foreach ($parts as $part) {
        if (preg_match("/^(\d+)w?$/", $part, $matches)) {
          // width specifier
          $info["w"] = $matches[1];
        } else if (preg_match("/^([0-9\.]+)x$/", $part, $matches)) {
          // device-pixel-ratio specifier
          $info["x"] = $matches[1];
        } else {
          // assume this is an href
          $info["href"] = $part;
        }
      }
      
      // now build the source set (bss)

      $bss = array();
      
      if (isset($info["w"])) {
        $width = (int) $info["w"];
        
        $href = ""; 
        
        if (isset($info["href"])) {
          $href = $info["href"];
        } else {
          // resize the image
          $href = $this->resize("w=" . $info["w"])->url();
        }

        $bss[] = $href;
        $bss[] = $info["w"] . "w";

        if (!$smallest || $width < $smallest) {
          $smallest = $width;
          $ret["src"] = $href;
        }
        
        
      } else {
          
        if (isset($info["href"]) && isset($info["x"])) {
          // href and 2x URL must be specified, as there's no meaningful way to infer this
          $bss[] = $info["href"];
          $bss[] = $info["x"] . "x";
        }
        
      }
      
      if (count($bss)) {
        $srcset[] = implode(" ", $bss);
      }
      
      
    }

    if (count($srcset)) {
      $ret["srcset"] = implode(", ", $srcset);
    }
    
    if ($sizes_spec) {
      $ret["sizes"] = $sizes_spec;
    }
      
    return $ret;
    
    
  }

  
  public function infer_srcset_attr($spec, $args = array(), $tag = "img") {
    
    // first mark the default source as this images src. 
    // we will take the smallest "w" in the srcset as the default, unless specified in the "src" argument
    
    $attr = array();
    
    if ($tag == "img") {
      $attr["src"] = $this->absolute_url();
    }
  
    $src_spec = false;
    
    $r = wp_parse_args( 
      $args
    );
    
    if (isset($r["src"])) {

      if ($tag == "img") {
        $attr["src"] = $r["src"];
      }

      $src_spec = true;
    }

    if ($tag == "img") {
      
      $alt = $this->attr("alt");
      $title = $this->attr("title");
    
      if ($alt) {
        $attr["alt"] = $alt;
      }

      if ($title) {
        $attr["title"] = $title;
      }

      if (isset($r["alt"])) {
        $attr["alt"] = $r["alt"];
      }

      if (isset($r["title"])) {
        $attr["title"] = $r["title"];
      }


    }

    
    // now parse the spec to get the srcset and sizes  
  
    // The syntax supported is "[srcset] @ [sizes]"
    // srcset and sizes are constructed as in this article: http://www.smashingmagazine.com/2014/05/14/responsive-images-done-right-guide-picture-srcset/
    
    // ... with the caveat that we can drop the URLs in ours, and they will be built automatically by auto-cropping the image 
    
    $sss = $this->infer_srcset_sizes( $spec );
    
    if (!$src_spec && isset($sss["src"]) && $tag == "img") {
      // take the smallest image as the default source (best practice)
      $attr["src"] = $sss["src"];
    }
    
    if (isset($sss["srcset"])) {
      $attr["srcset"] = $sss["srcset"];
    }

    if (isset($sss["sizes"])) {
      $attr["sizes"] = $sss["sizes"];
    }
    
    return $attr;
  }
  
  public function source($spec, $media = null, $args = array()) {
    $attr = $this->infer_srcset_attr($spec, $args, "source");

    if (isset($media)) {
      $attr["media"] = $media;
    }

    return WOOF_HTML::tag("source", $attr, null);
  } 
    
  public function srcset($spec, $args = array()) {
    
    $attr = $this->infer_srcset_attr($spec, $args, "img");
    return WOOF_HTML::tag("img", $attr, null);
    
  }
  
  
  
  public function fluid( $attr = array() ) {
    
    $r = wp_parse_args(
      $attr
    );
    
    $r["fluid"] = true;
    
    return $this->tag($r);
  }

  
  function lazy( $attr = array() ) {

    // support for jquery lazyload
    
    $r = wp_parse_args(
      $attr
    );
    
    $r["lazy"] = true;

    return $this->tag($r);
  }

  public function __toString() {
    return $this->tag();
  }
  
  function html($attr = array()) {
    return $this->tag($attr); 
  }
  
  public function info() {
    
    $info = array(
      "url" => $this->url(),
      "absolute_url" => $this->url(false),
      "size" => $this->filesize(),
      "size_in_bytes" => $this->filesizeinbytes(),
      "filetype" => $this->filetype(),
      "short_filetype" => $this->short_filetype(),
      "filename" => $this->filename(),
      "basename" => $this->basename(),
      "extension" => $this->extension(),
      "modified" => $this->modified("c"),
      "accessed" => $this->accessed("c"),
      "width" => $this->width(),
      "height" => $this->height()
    );
    
    
    return $info;

  }
  
  function width() {
    $this->sizeinfo();
    return $this->size[0];
  }
  
  function sizeinfo() {
    if (!$this->size) {
      $this->size = getimagesize($this->filepath());
    }
    
    return $this->size;
  }

  function orientation() {
    if ($this->width() <= $this->height()) {
      return "portrait";
    } else if ($this->height() <= $this->width()) {
      return "landscape";
    }
    
    return "square";
  }
  
  function is_portrait() {
    return $this->orientation() == "portrait";
  }

  function is_square() {
    return $this->orientation() == "square";
  }

  function is_landscape() {
    return $this->orientation() == "landscape";
  }
  
  function height() {
    $this->sizeinfo();
    return $this->size[1];
  }

  function bit() {
    $this->sizeinfo();
    return $this->size["bit"];
  }
  
  function exif($sections = NULL, $arrays = false, $thumbnail = false) {

    if (!isset($this->exif)) {
      $exif = exif_read_data($this->filepath(), $sections, $arrays, $thumbnail);

      if ($exif) {
        $this->exif = new WOOF_EXIF($exif);
      } else {
        $this->exif = new WOOF_Silent(__("No EXIF data was found in this image", WOOF_DOMAIN));
      }

    }

    return $this->exif;

  }
  
  function thumb_link($attr = array()) {

    if (!is_array($attr)) {
      // assume that they've just provided a thumbnail size
      $attr = array("thumbsize" => $attr, "fullsize" => true);
    }
    

    $r = wp_parse_args($attr, array("target" => "_blank", "thumbsize" => "w=200&h=200", "fullsize" => "w=960&h=720"));

    if (!isset($r["thumb"])) {
      $r["thumb"] = $this;
    }

    if (!isset($r["full"])) {
      $r["full"] = $this;
    }
    
    $r["text"] = $r["thumb"]->resize($r["thumbsize"]);
    
    if (isset($r["fullsize"]) && $r["fullsize"] != "" && $r["fullsize"] !== true) {
      $r["href"] = $r["full"]->resize($r["fullsize"])->url();
    }
    
    unset($r["thumbsize"]);
    unset($r["thumb"]);
    unset($r["fullsize"]);
    unset($r["full"]);
    
    return $this->link($r);
  }
  
  function crop( $args = array() ) {
    $r = wp_parse_args($args, array());
    $r["c"] = 2;
    return $this->resize($r);
  }
  
  function image_cache_info($filename) {
  
    global $wf;
    
    $mod = $this->modified("c");
    
    $sub_dir = substr( md5($filename.$mod), 0, 1 );
    
    $sub_url = "";
    
    if (is_admin()) {
      $sub_url = "admin/" . $sub_dir . "/";
      $sub_dir = ( "admin" . WOOF_DIR_SEP ) . $sub_dir;
    } else {
      $sub_url = "site/" . $sub_dir . "/";
      $sub_dir = ( "site" . WOOF_DIR_SEP ) . $sub_dir;
    }

    $dir_path = $wf->content_image_cache_dir . $sub_dir;

    if ( !file_exists($dir_path) ) {
      wp_mkdir_p($dir_path);
    }
    
    if (file_exists($dir_path)) {
      $dir_path .= WOOF_DIR_SEP;
    } else {
      // couldn't create so use top level
      $sub_url = "";
      $dir_path = $wf->content_image_cache_dir;
    }
    
    return array("path" => $dir_path . $filename, "url" => $wf->content_image_cache_url. $sub_url . $filename);
    
  }
  
  
  public function to( $type, $attr = array() ) {
    
    $norm = strtolower($type);
    
    $ext_pattern = "/\.[a-z]+$/";
    $ext_replace = "." . $norm;
    
    $new_path = preg_replace( $ext_pattern , $ext_replace, $this->path() );
    $new_url = preg_replace( $ext_pattern, $ext_replace, $this->url() );
      
    // the new path
    
    switch ( $norm ) {
      case "jpg" :
      case "jpeg" : 
        $mime_type = "image/jpeg"; 
        break;
      case "png" :
        $mime_type = "image/png"; 
        break;
      case "gif" :
        $mime_type = "image/gif"; 
        break;
      default: 
        return new WOOF_Silent( __("Invalid Image format", WOOF_DOMAIN) );
         
    }
    
    // create a new image resource

    $image = WOOF_Image::load( $this->path );
    
    // save the new image
    
    WOOF_Image::save_image( $new_path, $image, $mime_type, $attr, $destroy = true);
    
    return new WOOF_Image( $new_path, $new_url, $this->attr() );
    
  }

  public function jpg( $attr = array() ) {
    return $this->to("jpg", $attr);
  }

  public function jpeg( $attr = array() ) {
    return $this->to("jpeg", $attr);
  }

  public function png( $attr = array() ) {
    return $this->to("png", $attr);
  }

  public function gif( $attr = array() ) {
    return $this->to("gif", $attr);
  }


  function resize( $params = array() ) {
    
    global $wf;

    $basename = $this->basename();
    
      if ($basename != "") {

        $nocache = false;
        
        global $wf;

        $params = wp_parse_args($params, array());
        
        if (isset($params["nc"])) {
          $nocache = true;
          unset($params["nc"]);
        }
        
        if (isset($params["c"])) {
          $params["zc"] = $params["c"];
        }

        if (isset($params["ca"])) {
          $params["far"] = $params["ca"];
        }

        if (isset($params["from"])) {
          $params["far"] = $params["from"];
        }
        
      	$md5_params = md5($this->modified("c").$this->url().$basename.serialize($params));

        $name_image = $this->basename();
        $image_path = $this->filepath();


        $parts = explode(".", $basename);
        
        
        $args = wp_parse_args( 
          $params,
          array(
          	'zc'=> 1,
        		'w'	=> 0,
        		'h'	=> 0,
        		'q'	=>  85,
        		'src' => $image_path,
            'far' => false,
            'iar' => false,
            'up' => 1,
            'co' => "0,0"
        	)
        );

        
        if (isset($args["out"])) {
          $pi = pathinfo($args["out"]);
          
          $ext = $pi["extension"];
          
          if ($ext == "" || $ext != $this->extension()) {
            $ext = $this->extension(); 
          }
          
          $thumb_filename = $pi["filename"].".".$ext;
          
        } else { // auto-generate a thumb name
          
          $thumb_filename = $parts[0].".".$md5_params.".".$this->extension();
        }
                
        $info = self::image_cache_info($thumb_filename);
        $thumb_path = $info["path"];
        $thumb_url = $info["url"];


        if (!file_exists($thumb_path) || $nocache) {

	        $thumb_pi = pathinfo($thumb_path);

					if (!file_exists($thumb_pi["dirname"])) {
						return new WOOF_Silent( sprintf( __("Cannot resize the image, as the image cache folder at %s does not exist", WOOF_DOMAIN),  $thumb_pi["dirname"] ) );
					}

        	$size = @getimagesize($image_path);

          $sizes = array();
        	$sizes['w'] = $size[0];
        	$sizes['h'] = $size[1];

  
        	if ( ( $args['w'] > 0 ) && ( $args['h'] == 0 ) ) {
        	  if ($sizes['w'] != 0) {
        	    $args['h'] = round( ( $args['w'] * $sizes['h'] ) / $sizes['w'] );
            }
          
        	} elseif ( ( $args['w'] == 0 ) && ( $args['h'] > 0 ) ) {
        	  if ($sizes['h'] != 0) {
        	    $args['w'] = round( ( $args['h'] * $sizes['w'] ) / $sizes['h'] );
            }
          
        	}

        	$thumb_path = self::image_resize(
        	  $args['src'],
        	  (int) $args['w'],
        	  (int) $args['h'],
        	  $args['zc'],
            $args['far'],
            $args['iar'],
        	  $thumb_path,
        	  $args['q'],
            $args['up'],
            $args['co']
        	);
          
        }

        if ( is_wp_error( $thumb_path ) ) {
          return new WOOF_Silent($thumb_path->get_error_message());
  
        } else {
	        
	        $ic = $wf->get_image_class();
	        
	        $image = new $ic( $thumb_path, $thumb_url, $this->attr() );

	        
          return $image;
  
        }
  
      }

    return new WOOF_Silent(__("image path has not been set", WOOF_DOMAIN));
  }

  function res() {
  	$image = WOOF_Image::load( $this->path() );

		if ( !is_resource( $image ) )
			return new WOOF_Silent(__('Image could not be loaded', WOOF_DOMAIN));
    
    return $image;
  }
  
  function json() {
    
    $json = array();
    
    if ($this->exists()) {
      $json["href"] = $this->url();
    }
    
    $json["width"] = $this->width();
    $json["height"] = $this->height();
    
    $json["size"] = $this->size("AUTO", true, "");
    $json["bytes"] = $this->bytes();
    
    return $json;
  }
	
  
  //synonyms
  
  function generate( $params = array() ) {
    // synonym for resize
    return $this->resize( $params );
  }

  function thumb( $params = array() ) {
    // synonym for resize
    return $this->resize( $params );
  }



  static function empty_mp_thumb($args = "w=60") {
     
    $r = wp_parse_args( $args, array() );
    
    if (isset($r["w"]) && is_numeric($r["w"])) {
      $width = (int) $r["w"];
    } else {
      $width = 60;
    }

    if (isset($r["h"]) && is_numeric($r["h"])) {
      $height = (int) $r["h"];
    } else {
      $height = 60;
    }
    
    $container_width = $width;
    $container_height = $height;
    
    $container_class = "";
    
    if (isset($r["class"])) {
      $container_class = " " . $r["class"];
    }
    
    $style_attr = 'width: '.$container_width.'px; height: '.$container_height.'px;';
    
    $div_attr = array("class" => "mp-thumb empty".$container_class );

    if (isset($r["no_image"])) {
      $no_image = $r["no_image"];
    } else {
      $no_image = __( "( no image )", WOOF_DOMAIN );
    }
    
    $span_attr = array("class" => "no-image", "style" => $style_attr);
    
    $html = WOOF_HTML::open("div", $div_attr);
    $html .= WOOF_HTML::tag("span", $span_attr, $no_image);
    $html .= WOOF_HTML::close("div");

    return $html;
  
  }
  
  function mp_thumb($args = "w=60") {
    
		$html = "";

    // updated to always generate a 2x image, for simplicity
    // also now allows more customisation to handle things like overlays
     
    $r = wp_parse_args( $args, array() );
    
    if (isset($r["w"]) && is_numeric($r["w"])) {
      $width = (int) $r["w"];
    } else {
      $width = 60;
    }

    if (isset($r["h"]) && is_numeric($r["h"])) {
      $height = (int) $r["h"];
    } 
            
    $desired_width = $width;
    
    if (isset($height)) {
      $desired_height = $height;
    }
  
    $container_width = $width;
    $my_width = $this->width();
    $my_height = $this->height();
    
    if (isset($height)) {
      $ratio = $width / $height;
    } else {
      $ratio = $my_width / $my_height;
    }
  
    $is_small = false;
    
    $container_class = "";
    
    if ($my_width < $width) {
      $container_width = $my_width;
      
      if ($my_width < $width) {
        $container_class = " small";
        $is_small = true;
      } 
    
    }
    
    if (isset($r["class"])) {
      $container_class .= " " . $r["class"];
    }
    
    if (isset($height)) {
      $container_height = $height;
    } else {
      $container_height = ceil( $container_width / $ratio );
    }
  
    
    if ($this->is_external()) {
      $link_attr = array( "href" => $this->external_url, "class" => "thumbnail" );
    } else {
      $link_attr = array( "href" => $this->url(), "class" => "thumbnail" );
    }
    
    if (isset($r["link_attr"])) {
      $link_attr = wp_parse_args( $r["link_attr"], $link_attr );
    }
    
    if (isset($r["href"])) {
      $link_attr["href"] = $r["href"];
    }
    
    // check the ratio - if the dimensions are between 1x and 2x the desired width and height, we need to crop the image to the same ratio as the desired width and height
    
    $resize_args = array(
      "up" => 0,
      "w" => $width * 2
    );
    
    $tweaked_height = false;
    
    if (isset($height)) {
      
      if ( ( $height > $width ) && ( $my_height > $height && $my_height < $height * 2 ) ) {
        // adjust the height to match
        $desired_ratio = $width / $height;
        $height = round( $my_width / $desired_ratio );
        $resize_args["w"] = $my_width;
        $tweaked_height = true;
        $container_height = $desired_height;
        $resize_args["h"] = $height;
      } else {
        $resize_args["h"] = $height * 2;
      }
      
    }
        
    
    $image = $this->resize( $resize_args );
    
		if ($image->exists()) {
	
	    if (!$tweaked_height) {
	      if ($my_height > $image->height()) { 
	        // adjust the container height to account for rounding differences
	        $container_height = min($container_height, $image->height() / 2);
	      } else {
	        $container_height = min($container_height, $image->height());
	      }
	    } 
    
	    if (isset($r["watermark"])) {
	      $watermark_args = array("at" => "c");
      
	      if (isset($r["watermark_args"])) {
	        $watermark_args = $r["watermark_args"];
	      }
      
	      $image = $image->watermark($r["watermark"], $watermark_args);
      
	    }
    
	    $style_attr = 'width: '.$container_width.'px; height: '.$container_height.'px;';
    
	    $link_attr["style"] = $style_attr;
    
	    $div_attr = array("class" => "mp-thumb".$container_class );

	    $thumb_only = WOOF::is_true_arg($r, "thumb_only");
    
	    if ($thumb_only) {
	      $div_attr["style"] = $style_attr;
	    }
    
	    $html = WOOF_HTML::open("div", $div_attr);
    
	    if (!$thumb_only) {
	      $html .= WOOF_HTML::open("a", $link_attr );
	    }
  
	    $html .= $image->fluid( array("data-thumb_width" => $container_width, "data-thumb_height" => $container_height ) );
    
	    if (!$thumb_only) {
	      $html .= WOOF_HTML::close("a");
	    }

	    if (isset($r["no_image"])) {
	      $html .= WOOF_HTML::tag("span", "class=no-image", $r["no_image"]);
	    }
    
	    $html .= WOOF_HTML::close("div");
			
		} // image exists
		
    return $html;
  }
 

  function mt($args = "w=60") {
    return $this->mp_thumb($args);
  }

  function resample( $params = array() ) {
    // synonym for resample (which may read better for a "quality" conversion)
    return $this->resize( $params );
  }
  
  function cacheinfo($attr = array()) {
    
    global $wf;
    
    $r = wp_parse_args( 
      $attr,
      array( 
        "prefix" => '',
        "suffix" => '',
        "maxlength" => 72
      )
    );
    
    $extension = $this->extension();
    
    if (isset($r["extension"])) {
      $extension = $r["extension"];
    }
    
    $mod = $this->modified("YmdHis");
    
    $basename = $r["prefix"].$this->filename()."-".$mod."-".$r["suffix"].".".$extension;
    
    if (preg_match('/^([^.]*\.)(.*?)(\.[^.]*)$/', $basename, $matches)) {
      
      if (strlen($matches[2]) > (int) $r["maxlength"]) {
        $basename = $matches[1].md5($matches[2]).$matches[3];
      }
    }
    
    $info = self::image_cache_info($basename);
    $thumb_path = $info["path"];
    $thumb_url = $info["url"];
    
    
    return array( 
      "path" => $thumb_path,
      "url" =>  $thumb_url
    );

  }
  
  public function filter($filter, $attr = array(), $args = array() ) {
    
    global $wf;
    
    $r = wp_parse_args( 
      $attr,
      array( 
        "prefix" => '',
        "suffix" => ''
      )
    );
    
    $ci = $this->cacheinfo($r);
    
    $ic = $wf->get_image_class();
    
    if (file_exists($ci["path"])) {

      return new $ic( $ci["path"], $ci["url"], $this->attr() );
      
    } else {
  
      $image = WOOF_Image::load($this->filepath());
  
      $filter_args = array_merge( array($image, $filter), $args ); 

      call_user_func_array( "imagefilter", $filter_args );
      
      if ( self::save_image( $ci["path"], $image, $this->infer_content_type($r), $attr ) ) {
        return new $ic( $ci["path"], $ci["url"], $this->attr() );
      }
    
    }
    
    
    // if it fails just return the image object
    
    return $this;
    
  }
  
  // filters and effects

  public function brightness($val, $attr = array()) {
    return $this->filter(IMG_FILTER_BRIGHTNESS, wp_parse_args( $attr, array( "suffix" => ".brightness.".$val ) ), array( $val ) );
  }

  public function contrast($val, $attr = array()) {
    return $this->filter(IMG_FILTER_CONTRAST, wp_parse_args( $attr, array( "suffix" => ".contrast.".$val ) ), array( $val ) );
  }

  public function edgedetect($attr = array()) {
    return $this->filter(IMG_FILTER_EDGEDETECT, wp_parse_args( $attr, array( "suffix" => ".edgedetect" ) ) );
  }

  public function emboss($attr = array()) {
    return $this->filter(IMG_FILTER_EMBOSS, wp_parse_args( $attr, array( "suffix" => ".emboss" ) ) );
  }

  public function gaussian_blur($attr = array()) {
    return $this->filter(IMG_FILTER_GAUSSIAN_BLUR, wp_parse_args( $attr, array( "suffix" => ".gblur" ) ) );
  }

  public function gblur($attr = array()) {
    return $this->gaussian_blur($attr);
  }

  public function selective_blur($attr = array()) {
    return $this->filter(IMG_FILTER_SELECTIVE_BLUR, wp_parse_args( $attr, array( "suffix" => ".sblur" ) ) );
  }

  public function blur($attr = array()) {
    return $this->selective_blur($attr);
  }
  
  public function mean_removal($attr = array()) {
    return $this->filter(IMG_FILTER_MEAN_REMOVAL, wp_parse_args( $attr, array( "suffix" => ".mr" ) ) );
  }

  public function sketchy($attr = array()) {
    // synonym for the less meaningful mean_removal
    return $this->filter(IMG_FILTER_MEAN_REMOVAL, wp_parse_args( $attr, array( "suffix" => ".sketchy" ) ) );
  }

  public function smooth($val, $attr = array()) {
    return $this->filter(IMG_FILTER_SMOOTH, wp_parse_args( $attr, array( "suffix" => ".smooth".$val ) ), array( $val ) );
  }

  public function pixelate($block_size, $advanced = false, $attr = array()) {
    return $this->filter(IMG_FILTER_PIXELATE, wp_parse_args( $attr, array( "suffix" => ".pixelate".$block_size.$advanced ) ), array( $block_size, $advanced ) );
  }
  
  public function grayscale($attr = array("suffix" => '.grayscale' )) {
    return $this->filter(IMG_FILTER_GRAYSCALE, $attr );
  }

  public function colorize($color, $attr = array()) {
    
    $colorinfo = self::parse_color($color);
    
    $rgb = $colorinfo["rgb"];
    $hex = $colorinfo["hex"];
    
    return $this->filter( IMG_FILTER_COLORIZE, array("suffix" => ".colorize.".strtolower(str_replace("#", "", $hex)) ), $rgb );
  }

       
  function tint( $color, $attr = array() ) {
    return $this->grayscale()->colorize($color);
  }  

  function sepia($color = "") {
    if ($color == "") {
      $color = WOOF_Image::$COLOR_SEPIA;
    }
    
    return $this->tint($color);
  }
  
  // convolution-based filters
  
  function convolute( $matrix, $attr = array(), $offset = 0 ) {

    global $wf;
    
    $r = wp_parse_args( 
      $attr,
      array( 
        "prefix" => '',
        "suffix" => '.convolute.'.md5(serialize($matrix))
      )
    );

    $ci = $this->cacheinfo($r);
    
    $ic = $wf->get_image_class();
    
    if (file_exists($ci["path"])) {

      return new $ic( $ci["path"], $ci["url"], $this->attr() );

    } else {

      // calculate the matrix divisor 
      $divisor = array_sum(array_map('array_sum', $matrix));            

      $image = WOOF_Image::load($this->filepath());

      imageconvolution( $image, $matrix, $divisor, $offset );


      if ( self::save_image( $ci["path"], $image, $this->mime(), $attr ) ) {
        return new $ic( $ci["path"], $ci["url"], $this->attr() );
      }

    }
    
    // if it fails just return the image object

    return $this;

  }
     
  function sharpen($attr = array()) {
    return $this->convolute(
      array( 
        array(-1.2, -1, -1.2), 
        array(-1, 20, -1), 
        array(-1.2, -1, -1.2) 
      ),
      wp_parse_args( $attr, array( "suffix" => ".sharpen" ) )
    );

  }
         
  function sobel($attr = array()) {
    return $this->convolute(
      array( 
        array(-1, -2, -1), 
        array(0, 0, 0), 
        array(1, 2, 1) 
      ),
      wp_parse_args( $attr, array( "suffix" => ".sobel" ) )
    );

  }   

  function laplace($attr = array()) {
    return $this->convolute(
      array( 
        array(0, -1, 0), 
        array(-1, 4, -1), 
        array(0, -1, 0) 
      ),
      wp_parse_args( $attr, array( "suffix" => ".laplace" ) )
    );

  }   

  function laplace_diag($attr = array()) {
    return $this->convolute(
      array( 
        array(-1, -1, -1), 
        array(-1, 8, -1), 
        array(-1, -1, -1) 
      ),
      wp_parse_args( $attr, array( "suffix" => ".laplace-diag" ) )
    );

  }
  
  
  // some other great image fx using imagecopymerge
  
  
  function reflection($attr = array()) {
    
    global $wf;
    $ic = $wf->get_image_class();
    
    // big thanks to killing_wombles0000: http://www.php.net/manual/en/function.imagecopymerge.php#102496
    
    $r = wp_parse_args( $attr, array( 
      "strength" => 80,        //    starting transparency (0-127, 0 being opaque)
      "height" => 80,           //     height of reflection in pixels
      "gap" => 0                        //    gap between image and reflection
    ));
    
    $r["suffix"] = ".reflect.".$r["strength"].".".$r["height"].".".$r["gap"];
    
    $r["extension"] = "png";
    
    
    $ci = $this->cacheinfo($r);

    if (file_exists($ci["path"]) && !isset($r["nocache"])) {
      return new $ic( $ci["path"], $ci["url"], $this->attr() );
    } else {
      
      $image = WOOF_Image::load($this->filepath());


      $orig_height = imagesy($image);                                //    store height of original image
      $orig_width = imagesx($image);                                    //    store height of original image
      $output_height = $orig_height + $r["height"] + $r["gap"];    //    calculate height of output image

      // create new image to use for output. fill with transparency. ALPHA BLENDING MUST BE FALSE
      $out = imagecreatetruecolor($orig_width, $output_height);
      imagealphablending($out, false);
      $bg = imagecolortransparent($out, imagecolorallocatealpha($out, 255, 255, 255, 127));
      imagefill($out, 0, 0, $bg);
      imagefilledrectangle($out, 0, 0, imagesx($image), imagesy($image), $bg);
    
      // copy original image onto new one, leaving space underneath for reflection and 'gap'
      imagecopyresampled ( $out , $image , 0, 0, 0, 0, imagesx($image), imagesy($image), imagesx($image), imagesy($image));

       // create new single-line image to act as buffer while applying transparency
      $reflection_section = imagecreatetruecolor(imagesx($image), 1);
      imagealphablending($reflection_section, false);
      $bg1 = imagecolortransparent($reflection_section, imagecolorallocatealpha($reflection_section, 255, 255, 255, 127));
      imagefill($reflection_section, 0, 0, $bg1);

      // 1. copy each line individually, starting at the 'bottom' of the image, working upwards. 
      // 2. set transparency to vary between reflection_strength and 127
      // 3. copy line back to mirrored position in original
      for ($y = 0; $y< $r["height"];$y++)
      {    
          $t = ((127 - $r["strength"]) + ($r["strength"]*($y/$r["height"])));
          imagecopy($reflection_section, $out, 0, 0, 0, imagesy($image)  - $y - 1, imagesx($image), 1);
          imagefilter($reflection_section, IMG_FILTER_COLORIZE, 0, 0, 0, $t);
          imagecopyresized($out, $reflection_section, 0, imagesy($image) + $y + $r["gap"], 0, 0, imagesx($image), 1, imagesx($image), 1);
      }


      if ( self::save_image( $ci["path"], $out, "image/png", array("alpha" => true) ) ) {
        return new $ic( $ci["path"], $ci["url"], $this->attr() );
      }
    
    }
    
  }
  
  public function border($attr) {

    global $wf;
    $ic = $wf->get_image_class();
    
    $r = wp_parse_args( $attr, array( 
      "width" => 1,       //  border width
      "mode" => "o",      //  mode - i (inset, same size, original image will shrink inside border), or o (outset, image size will increase for border) 
      "color" => "FFF"   //  gap between image and reflection
    ));

    $color = $r["color"];
    $color_hex = $r["color"];
    
    if (!is_array($color)) {
      $color = WOOF::hex_to_rgb($color);
    } else {
      $color_hex = WOOF::rgb_to_hex($color);
    }
    
    if (isset($r["w"])) {
      $r["width"] = $r["w"];
    }
  
    $r["suffix"] = ".border.".str_replace("#", "", $color_hex).$r["mode"].$r["width"];
  
    $ci = $this->cacheinfo($r);
    
    if (file_exists($ci["path"]) && !isset($r["nocache"])) {
      return new $ic( $ci["path"], $ci["url"], $this->attr() );
    } else {
      
      $image = WOOF_Image::load($this->filepath());
      
      if ($r["mode"] == "o") {
        $output_height = $this->height() + ($r["width"] * 2);    //    calculate height of output image
        $output_width = $this->width() + ($r["width"] * 2);    //    calculate height of output image
      } else {
        $output_height = $this->height();
        $output_width = $this->width();  
      }
      
      $out = imagecreatetruecolor($output_width, $output_height);
      imagealphablending($out, false);
      $bg = imagecolorallocate($out, $color[0], $color[1], $color[2]);
      
      imagefill($out, 0, 0, $bg);
      
      if ($r["mode"] == "o") {
        imagecopyresized($out, $image, $r["width"], $r["width"], 0, 0, $this->width(), $this->height(), $this->width(), $this->height());
      } else {
        imagecopyresized($out, $image, $r["width"], $r["width"], 0, 0, $this->width() - ($r["width"] * 2), $this->height() - ($r["width"] * 2), $this->width(), $this->height());
      }

      if ( self::save_image( $ci["path"], $out, $this->infer_content_type($r) ) ) {
        return new $ic( $ci["path"], $ci["url"], $this->attr() );
      }
      
    }

    
  }
  
  protected static function parse_coord($pair) {
    
    if (!is_array($pair)) {
      $pair = explode(",", $pair);
    }
    
    if (!count($pair)) {
      $pair = array(0, 0);
    } else if (count($pair) == 1) {
      $pair = array( (int) $pair[0], (int) $pair[0] );
    } else {
      $pair = array( (int) $pair[0], (int) $pair[1] );
    }
    
    return $pair;

  }
  
  protected static function get_offset($inset, $at = "c") {
    
    
    $inset = self::parse_coord($inset);
    
    $ix = $inset[0];
    $iy = $inset[1];
    
    
    switch ($at) {
      case "nw" : 
      case "tl" :
        $offset = array($ix, $iy); 
        break;
      case "n" : 
      case "t" :
        $offset = array(0, $iy); 
        break;
      case "ne" : 
      case "tr" :
        $offset = array(-$ix, $iy); 
        break;
      case "e" : 
      case "r" :
        $offset = array(-$ix, 0); 
        break;
      case "se" : 
      case "br" :
        $offset = array(-$ix, -$iy); 
        break;
      case "s" : 
      case "b" :
        $offset = array(0, -$iy); 
        break;
      case "sw" : 
      case "bl" :
        $offset = array($ix, -$iy); 
        break;
      case "w" : 
      case "l" :
        $offset = array($ix, 0); 
        break;
      default:
        $offset = array(0, 0);
      
    }
    
    return $offset;
  }
  
  
  protected static function get_at_coord($dw, $dh, $sw, $sh, $at = "c") {
      
    switch ($at) {
      case "nw" : 
      case "tl" :
        $c = array(0, 0); 
        break;
      case "n" : 
      case "t" :
        $c = array( round(( $dw - $sw ) / 2) , 0 ); 
        break;
      case "ne" : 
      case "tr" :
        $c = array( $dw - $sw , 0 ); 
        break;
      case "e" : 
      case "r" :
        $c = array( $dw - $sw , round(( $dh - $sh ) / 2)  ); 
        break;
      case "se" : 
      case "br" :
        $c = array( $dw - $sw , $dh - $sh ); 
        break;
      case "s" : 
      case "b" :
        $c = array( round(( $dw - $sw ) / 2) , $dh - $sh ); 
        break;
      case "sw" : 
      case "bl" :
        $c = array( 0, $dh - $sh ); 
        break;
      case "w" : 
      case "l" :
        $c = array( 0, round(( $dh - $sh ) / 2) ); 
        break;
      default:
        $c = array( round(( $dw - $sw ) / 2), round(( $dh - $sh ) / 2) );
      
    }
    
    return $c;
  }
  
  
  function watermark(WOOF_Image $watermark, $attr = array()) {

    // setup default offsets, based on specified 
    
    global $wf;
    $ic = $wf->get_image_class();
    
    
    $r = wp_parse_args( $attr, array( 
      "at" => "se",
      "inset" => 10,
      "alpha" => true,
      "q" => 90
    ));

    $w = "";
    $h = "";
    
    if (isset($r["w"])) {
      $r["width"] = $r["w"];
    }

    if (isset($r["h"])) {
      $r["height"] = $r["h"];
    }
    
    if (isset($r["height"])) {
      $h = $r["height"];
    }
    
    if (isset($r["width"])) {
      $w = $r["width"];
    }
  
    if (isset($r["size"])) {
      $w = $r["size"];
    }
    
    if (isset($h)) {
      
      if (preg_match("/(\d+)%/", $h, $m)) {
        $h = round(($m[1] / 100) * $this->height());
      }
      
    }

    if (isset($w)) {
      
      if (preg_match("/(\d+)%/", $w, $m)) {
        $w = round(($m[1] / 100) * $this->width());
      }
      
    }

    $os = "";
    
    if (isset($r["offset"])) {
      $os = $r["offset"];
    }
    
    $r["suffix"] = ".mark.".md5($watermark->filepath()).".".$r["at"].$r["inset"].$os.$w.$h;

    $ci = $this->cacheinfo($r);

    if (file_exists($ci["path"]) && !isset($r["nocache"])) {

      return new $ic( $ci["path"], $ci["url"], $this->attr() );
      
    } else {

      // work out the offsets
    
      if (isset($r["offset"])) {
        $offset = self::parse_coord($r["offset"]);
      } else {
        $offset = self::get_offset($r["inset"], $r["at"]);
      }
      
      $image = WOOF_Image::load($this->filepath());

      if (isset($w) && isset($h)) {
        $watermark = $watermark->resize("w=$w&h=$h&q=".$r["q"]);
      } else if (isset($w)) {
        $watermark = $watermark->resize("w=$w&q=".$r["q"]);
      } else if (isset($h)) {
        $watermark = $watermark->resize("h=$h&q=".$r["q"]);
      }

      $wimage = WOOF_Image::load($watermark->filepath());
      
      $sw = $watermark->width();
      $sh = $watermark->height();

      $dw = $this->width();
      $dh = $this->height();

    
      // work out the position

      $coord = self::get_at_coord( $dw, $dh, $sw, $sh, $r["at"] );
      
      // get final coord 
      
      $fc = array( $coord[0] + $offset[0], $coord[1] + $offset[1] ); 
      
      // Set the margins for the stamp and get the height/width of the stamp image
      $marge_right = 10;
      $marge_bottom = 10;

      // Merge the stamp onto our photo with an opacity (transparency) of 50%
      self::imagecopymerge_alpha($image, $wimage, $fc[0], $fc[1], 0, 0, $sw, $sh, 100);
      
      if ( self::save_image( $ci["path"], $image, $this->infer_content_type($r), $attr ) ) {
        return new $ic( $ci["path"], $ci["url"], $this->attr() );
      }
      
    }

  }
  
  
}

class WOOF_EXIF extends WOOF_Wrap {
  
  protected $data;
  
  public function __construct($item) {
    $this->data = $item;
    $this->item = (object) $item;
  }

  public function dump() {
    
    $html = WOOF_HTML::open("table");
    
    foreach ($this->data as $key => $value) {
      
      $html .= $this->dump_item($key, $value);
    }

    $html .= WOOF_HTML::close("table");
    
    return $html;
  }
  
  public function dump_item($key, $value) {
    $html = '';
    $html .= WOOF_HTML::open("tr");
    $html .= WOOF_HTML::tag("th", array("scope" => "row"), $key);

    if (is_array($value)) {
      
      $html .= WOOF_HTML::open("td");
      $html .= WOOF_HTML::open("table");
    
      foreach ($value as $sub_key => $sub_value) {
        $html .= $this->dump_item($sub_key, $sub_value);
      }

      $html .= WOOF_HTML::close("table");
      $html .= WOOF_HTML::close("td");
      
    } else {
      $html .= WOOF_HTML::tag("td", array(), $value);
    }
    
    $html .= WOOF_HTML::close("tr");
    
    return $html;
  }
  
  public function date_val($key, $format = "[date-time-long]") {
    global $wf;
    
    $val = $this->get($key);

    if ($val = $this->has($key)) {
      return $wf->format_date($format, strtotime($val));
    }
    
    return "";
  }
  
  public function taken($format = "[date-time-long]") {
    return $this->date_val("DateTimeOriginal", $format); 
  }

  public function has($key) {
    
    $val = $this->get($key);

    if ($val && !is_woof_silent($val)) {
      return $val;
    }
    
    return false;
    
  }
  
}

