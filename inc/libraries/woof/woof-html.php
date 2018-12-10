<?php

class WOOF_HTML {
  
  public static $attributes = array(
    "global"      => "itemscope,itemtype,itemprop,accesskey,class,contenteditable,contextmenu,dir,draggable,dropzone,hidden,id,lang,spellcheck,style,tabindex,title,onabort,onblur,oncanplay,oncanplaythrough,onchange,onclick,oncontextmenu,ondblclick,ondrag,ondragend,ondragenter,ondragleave,ondragover,ondragstart,ondrop,ondurationchange,onemptied,onended,onerror,onfocus,oninput,oninvalid,onkeydown,onkeypress,onkeyup,onload,onloadeddata,onloadedmetadata,onloadstart,onmousedown,onmousemove,onmouseout,onmouseover,onmouseup,onmousewheel,onpause,onplay,onplaying,onprogress,onratechange,onreadystatechange,onreset,onscroll,onseeked,onseeking,onselect,onshow,onstalled,onsubmit,onsuspend,ontimeupdate,onvolumechange,onwaiting,xml:lang,xml:space,xml:base",
    "a"           => "href,target,rel,hreflang,media,type",
    "area"        => "target,rel,media,hreflang,type,shape,coords",
    "audio"       => "autoplay,preload,controls,loop,mediagroup,muted,src",
    "base"        => "href,target",
    "blockquote"  => "cite",
    "body"        => "onafterprint,onbeforeprint,onbeforeunload,onblur,onerror,onfocus,onhashchange,onload,onmessage,onoffline,ononline,onpopstate,onresize,onstorage,onunload",
    "button"      => "type,name,disabled,form,type,value,formaction,autofocus,formenctype,formmethod,formtarget,formnovalidate ",
    "canvas"      => "height,width",
    "col"         => "span",
    "colgroup"    => "span",
    "command"     => "type,label,icon,disabled,radiogroup",
    "del"         => "cite,datetime",
    "details"     => "open",
    "embed"       => "src,type,height,width",
    "fieldset"    => "name,disabled,form",
    "form"        => "action,method,enctype,name,accept-charset,novalidate,target,autocomplete",
    "html"        => "manifest",
    "iframe"      => "scrolling,marginheight,marginwidth,longdesc,frameborder,align,src,srcdoc,name,width,height,sandbox,seamless",
    "img"         => "src,srcset,sizes,alt,height,width,usemap,ismap,border",
    "input"       => "name,disabled,checked,form,type,maxlength,readonly,size,value,autocomplete,multiple,autofocus,list,pattern,required,placeholder,dirname,formaction,autofocus,formenctype,formmethod,formtarget,formnovalidate,alt,src,height,width,min,max,step",
    "ins"         => "cite,datetime",
    "keygen"      => "challenge,keytype,autofocus,name,disabled,form",
    "label"       => "for,form",
    "li"          => "value",
    "link"        => "href,rel,hreflang,media,type,sizes",
    "map"         => "name",
    "menu"        => "type,label",
    "meta"        => "name,content,http-equiv,charset",
    "meter"       => "value,min,low,high,max,optimum",
    "object"      => "data,type,height,width,usemap,name,form",
    "ol"          => "start,reversed,type",
    "optgroup"    => "label,disabled",
    "option"      => "disabled,selected,label,value",
    "output"      => "name,form,for",
    "param"       => "name,value",
    "progress"    => "value,max",
    "q"           => "cite",
    "script"      => "src,defer,async,type,charset,language",
    "select"      => "name,disabled,form,size,multiple,autofocus,required",
    "source"      => "srcset,sizes,type,media",
    "style"       => "type,media,scoped",
    "table"       => "cellpadding,cellspacing,border",
    "td"          => "colspan,rowspan,headers",
    "textarea"    => "name,disabled,form,readonly maxlength,autofocus,required,placeholder,dirname,rows,wrap,cols",
    "th"          => "scope,colspan,rowspan,headers",
    "time"        => "datetime",
    "track"       => "kind,src,srclang,label,default",
    "video"       => "autoplay,preload,controls,loop,poster,height,width,mediagroup,muted,src"
  );
  
  public static function tag($tag, $attr = array(), $text = null, $empty_attr = true, $validate_attr = true) {
  
    $html = self::open($tag, $attr, is_null($text), $empty_attr, $validate_attr);
      
    if (!is_null($text)) {
      $html .= stripslashes( $text );
      $html .= self::close($tag);
    }
  
    return $html;
  }

  public static function is_data_attr($key) {
    return substr(strtolower($key), 0, 5) == "data-";
  }
  
  public static function is_valid_attr($tag, $attr) {
    
    // check if it's a global attribute

    if (preg_match("/(^|,)".$attr."($|,)/", self::$attributes["global"])) {
      return true;
    }
    
    // allow data attributes

    if (self::is_data_attr($attr)) {
      return true;
    }
    
    // now check for specific attributes for this tag 

    if (isset(self::$attributes[$tag])) {
      return preg_match("/(^|,)".$attr."($|,)/", self::$attributes[$tag]);
    }
    
    return false;

  }
  
  public static function attr($attr = array(), $options = array()) {
    
      $a = wp_parse_args(
        $attr,
        array(
          "validate" => true,
          "empty" => false
        )
      );
    
      $o = wp_parse_args($options);
      
      
      $validate_attr = WOOF::is_true_arg($o, "validate");
      $empty_attr = WOOF::is_true_arg($o, "empty");
      
      unset($a["validate"]);
      unset($a["empty"]);
      
      $tag = null;
      
      if (isset($o["tag"])) {
        $tag = $o["tag"];
      }
    
      
      
      $html = '';
    
      foreach ($a as $key => $value) {
      
        $use = true;
      
        if ($validate_attr && !is_null($tag)) {
          $use = self::is_valid_attr($tag, $key);
        }
      
        if ($use) {
      
          $val = $value;
    
          if (is_array($value)) {
            $val = implode(" ", $val);
          }

          if ($key == "itemscope") {
            $html .= ' ' . $key;
          } else {
          
            if (!(trim($val) == "" && !$empty_attr)) {
              $html .= ' '.$key.'="'.esc_attr($val).'"';
            }
      
          }
        
        }
      
      }
    
      return $html;
     
  }
  
  public static function open($tag, $attr = array(), $self_close = false, $empty_attr = true, $validate_attr = true) {

    $html = "<$tag";
  
    $html .= self::attr($attr, array("tag" => $tag, "validate" => $validate_attr, "empty" => $empty_attr));
    
    if ($self_close) {
      $html .= ' /';
    }
  
    $html .= '>';
  
    return $html; 

  }
 
  public static function close($tag) {
    return "</$tag>"; 
  }

  public static function pos_class($pos, $count, $prefix = "", $fc = "first", $lc = "last") {
    if ($pos == 0) {
      return $prefix.$fc;
    }
    
    if ($pos == $count - 1) {
      return $prefix.$lc;
    }

    return "";
  }

  public static function pos_class_1($pos, $count, $prefix = "", $fc = "first", $lc = "last") {
    if ($pos == 1) {
      return $prefix.$fc;
    }
    
    if ($pos == $count) {
      return $prefix.$lc;
    }
    
    return "";
  }

  public static function options( $options, $vals, $options_attr = array(), $assoc = null) {
    
    if (!is_array($vals)) {
      $vals = array( $vals );
    }
    
    $html = '';
    
    $count = 0;
    
    foreach ($options as $text => $value) {
        
      $optgroup_attr = null;
    
      
      if ( !is_string($text) && !$assoc ) {
        // standard non-associative array, so set the text to be the value
        $text = $value;
      }
      
      if (is_array($value)) {
      
        $ogv = $value;
        
        // we have an optgroup
        
        if (isset($value["optgroup_attr"])) {
          $html .= self::open("optgroup", $value["optgroup_attr"] );
        } else {
          $html .= self::open("optgroup", array( "label" => $text ) );
        }
      
        if (isset($value["options_attr"]) && isset($value["options"])) {
          // assume values also has attributes
          $html .= self::options( $value["options"], $vals, $value["options_attr"]);
        } else {
          $html .= self::options( $value, $vals );
        }
        
        $html .= self::close("optgroup");
      
      } else {
        
        if ($options_attr && count($options_attr) && isset($options_attr[$count]) && is_array($options_attr[$count])) {
          $attr = array_merge( $options_attr[$count], array( "value" => $value ) );
        } else {
          $attr = array( "value" => $value );
        }
        
        if (in_array($value, $vals)) {
          $attr["selected"] = "selected"; 
        } 
        
        $html .= self::tag( "option", $attr, $text, true );

      }
      
      $count++;
    }

    return $html;
    
  }
  
  public static function select( $attr, $options, $val = "", $options_attr = array(), $assoc = null ) {
    
    $html = self::open( "select", $attr );
    $html .= WOOF_HTML::options( $options, $val, $options_attr, $assoc );
    $html .= self::close("select");
    
    return $html;
    
  } 
  
  public static function option_values($str, $empty_label = "", $detect_groups = false) {
      
    $values = preg_split("/\r\n/", trim($str));
    
    $select_options = array();

    if ($empty_label != "") {
      $select_options[$empty_label] = "";
    }
    
    $parent_key = "";
    
    foreach ($values as $value) {
      
      if ($detect_groups && preg_match("/^\-\-(.*)?/", $value, $matches)) {
        
        $label = preg_replace("/--$/", "", trim($matches[1]));

        $select_options[$label] = array();
        
        $parent_key = $label;
        
      } else {
        
        if (preg_match("/(.*)\s*\=\s*(.*)/", $value, $matches)) {

          if ($parent_key != "") {
            $select_options[$parent_key][$matches[1]] = $matches[2];
          } else {
            $select_options[$matches[1]] = $matches[2];
          }

        } else {
          if ($parent_key != "") {
            $select_options[$parent_key][$value] = $value;
          } else {
            $select_options[$value] = $value;
          }
        }
      
      }
      
    }

    
    return $select_options;

  }
  
  
  public static function input_radio_group( $name, $id, $items, $val, $item_open = "", $item_close = "", $disabled = false ) {
    
    $html = '';

    foreach ($items as $label => $value) {
    
      $id_suffix = sanitize_title_with_dashes(remove_accents($value));
      
      $attr = array( "id" => $id."_".$id_suffix, "class" => "radio", "type" => "radio", "name" => $name, "value" => $value );
      
      if ($val == $value) {
        $attr["checked"] = "checked";
      }

      if ($disabled) {
        $attr["disabled"] = "disabled";
      }
      
      if ($item_open != "" && $item_close != "") {
        $html .= $item_open;
      }

      $html .= self::tag("input", $attr );
      $html .= self::tag("label", array( "for" => $id."_".$id_suffix, "class" => "radio" ), $label );

      if ($item_open != "" && $item_close != "") {
        $html .= $item_close;
      }
        
    }
    
    return $html;
    
  }

  public static function input_checkbox_group( $name, $id, $items, $vals, $item_open = "", $item_close = "", $disabled = false ) {
    
    $html = '';
        
    if (!is_array($vals)) {
      $vals = explode(",", $vals);
    }
  
    // the simplified form where the inner arrays are keyed by their labels
    foreach ($items as $label => $value) {
      
      $id_suffix = sanitize_title_with_dashes( remove_accents($value) );
      
      $attr = array( "id" => $id."_".$id_suffix, "class" => "checkbox", "type" => "checkbox", "name" => $name, "value" => htmlentities($value) );
      
      if ($disabled) {
        $attr["disabled"] = "disabled";
      }
      
      if (in_array($value, $vals)) {
        $attr["checked"] = "checked";
      }
      
      if ($item_open != "" && $item_close != "") {
        $html .= $item_open;
      }
      
      $html .= self::tag("input", $attr );
      $html .= self::tag("label", array( "for" => $id."_".$id_suffix, "class" => "checkbox" ), $label );

      if ($item_open != "" && $item_close != "") {
        $html .= $item_close;
      }

    }
    
    return $html;
    
  }
  
  


  public static function readonly_attr($test) {
    if ( $test ) {
      return ' readonly="readonly" ';
    }
    
    return '';
  }

  public static function disabled_attr($test) {
    if ( $test ) {
      return ' disabled="disabled" ';
    }
    
    return '';
  }
  
  public static function checked_attr($test) {
    if ( $test ) {
      return ' checked="checked" ';
    }
    
    return '';
  }

  public static function selected_attr($test) {
    if ( $test ) {
      return ' selected="selected" ';
    }
    
    return '';
  }

      
  public static function strip_whitespace($html) {
    
    return preg_replace('/\n|\r/', '', preg_replace('~>\s+<~', '><', $html));
  }
  
}


if (!class_exists("WH")) {
  class WH extends WOOF_HTML {}
}
