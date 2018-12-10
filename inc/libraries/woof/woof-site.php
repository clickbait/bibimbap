<?php

/* 

Class: WOOF_Site
  
  A WOOF wrapper object representing a site in a WordPress multi-site environment
    
*/

class WOOF_Site extends WOOF_Wrap {
  
  public $_name;

  public function id() {
    return $this->item->blog_id;
  }

  public function sid() {
    return $this->id();
  }

  public function url($args = array()) {
    
    global $wf;
    
    $r = wp_parse_args( 
      $args,
      array(
        "root_relative" => false
      )
    );
    
    if (!isset($r["protocol"])) {
      $r["protocol"] = ( isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]=="on") ? "https://" : "http://";
    }
    
    $url = $r["protocol"].$this->full_path();
    
    if ($r["root_relative"]) {
      $url = WOOF::root_relative_url($url);
    }
    
    return apply_filters("woof_permalink", $url."/");
    
  }
  
  public function permalink() {
    return $this->url(array("root_relative" => false));
  }
  
  public function is_child_of($obj) {
    return FALSE;
  }

  public function has_parent() {
    return FALSE;
  }

  public function parent() {
    return $this;
  }

  public function is_a($type) {
    return false;
  }

  public function is_page($post) {
    return false;
  }

  public function is($post, $type = null) {
    return false;
  }
  
  
  public function ancestors() {
    return new WOOF_Collection(array());
  }

  public function children() {
    return new WOOF_Collection(array());
  }

  public function siblings() {
    return new WOOF_Collection(array());
  }
  
  public function hierarchical() {
    return false;
  }
  
  public function network_id() {
    return $this->item->site_id;
  }

  public function registered($format = NULL) {
    return $this->date_val($this->item->date_registered, $format);
  }

  public function last_updated($format = NULL) {
    return $this->date_val($this->item->last_updated, $format);
  }

  public function mature() {
    return $this->item->mature != "0";
  }

  public function is_public() {
    return $this->item->public != "0";
  }

  public function deleted() {
    return $this->item->deleted != "0";
  }

  public function spam() {
    return $this->item->spam != "0";
  }

  public function archived() {
    return $this->item->archived != "0";
  }

  public function full_path() {
    $path = trim($this->item->domain, "\/");
    
    if ($this->item->path && $this->item->path != "" && $this->item->path != "/") {
      $path .= "/".trim($this->item->path, "\/");
    }
    
    return $path;
  }

  public function title() {
    return $this->name();
  }
  
  public function name() {
    
    if (!$this->_name) {

      if (is_multisite()) {
        $this->details = get_blog_details($this->id());
        $this->_name = $this->details->blogname;
      } else {
        $this->_name = get_bloginfo("name");
      }
  
    }

    return $this->_name;
  }
  
  
  
  public function link($args = array()) {
  
    $defaults = array(
      'text' => $this->title(),
      'root_relative' => false
    );
  
    $r = wp_parse_args( $args, $defaults );

    $root_relative = WOOF::is_true_arg($r, "root_relative");
    $tag = '<a href="'.$this->url($root_relative).'"';


    foreach ($r as $key => $value) {
      if ($key != "text" && $key != "href" && $key != "current_class" && $key != "root_relative") {
        $tag .= ' '.$key.'="'.esc_attr($value).'"';
      }
    }
  
    $tag .= '>'.$r['text'].'</a>';

    return $tag;
  }
  
  public function switch_to() {
    if (is_multisite()) {
      switch_to_blog( $this->id() );
    }
  }
  
  public function activate() {
    if (is_multisite()) {
      switch_to_blog( $this->id() );
    }
  }

  public function on() {
    if (is_multisite()) {
      switch_to_blog( $this->id() );
    }
  }
  
  public function __get($name) {
    
    global $wf;
    
    // for unknown properties switch the blog and access this property on WOOF
    
    $res = parent::__get($name);
    
    if (is_woof_silent($res)) {
      
      if (is_multisite()) {

        if (switch_to_blog( $this->id(), true )) {

			    if (property_exists($wf, $name)) {
			      $res = $wf->{$name};
					} else {
          	$res = $wf->__get($name);
					}
					
          restore_current_blog();
      
          if (!is_woof_silent($res)) {
            return $res;
          }
        }
    
      }
    
    }
    
    return $res;
    
  }
  
  public function __call($name, $arguments) {
    
    global $wf;

    $res = parent::__call($name, $arguments);

    if (is_woof_silent($res)) {

      if (is_multisite()) {

        // for unknown methods switch the blog and access this property on WOOF
    
        if (switch_to_blog( $this->id(), true )) {

			    if (method_exists($wf, $name)) {
			      $res = call_user_func_array (array($wf, $name), $arguments); 
			    } else {
				  	$res = $wf->__call($name, $arguments);
					}
					
          restore_current_blog();

          if (!is_woof_silent($res)) {
            return $res;
          }
        }
    
      }
    
    }
    
    return $res;
    
  }
  
}
