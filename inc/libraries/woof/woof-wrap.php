<?php

class WOOF_Wrap implements arrayaccess {
  
  protected $item;
  protected $_pc = array();
  protected $_site_id;
  protected $_property_mode = "silent";
    
  public function __construct($item = null) {
    global $blog_id;
    
    $this->_site_id = $blog_id;

    if (!is_null($item)) {
      $this->item = $item;
    }
  }

  public function set_property_mode($mode) {
    if ($mode == "silent") {
      $this->_property_mode = "silent";
    } else {
      $this->_property_mode = "standard";
    }
  }
  
  public function site() {
    
    global $wf;
    
    if (isset($this->_site_id)) {
      return $wf->site($this->_site_id);
    }
    
    return new WOOF_Silent("This object does not belong to a site");
    
  }
  
	public function set_site_id($id) {
		$this->_site_id = $id;
	}
	
  public function site_id() {
    return $this->_site_id;
  }  
  
  public function switch_site() {
    if (is_multisite()) {
      switch_to_blog($this->_site_id);
    }
  }

  public function restore_site() {
    if (is_multisite()) {
      restore_current_blog();
    }
  }
  
  public function exists() {
		if (is_woof_silent($this->item)) {
			return false;
		}
    
    if (!empty($this->item)) {
      return $this;
    }
		
    return false;
  }
  
  public function error() {
    return "";
  }

  protected function pcache_key($args) {
    global $blog_id;
    return md5( $blog_id . serialize($args) );
  }

  public function purge() {
    unset( $this->_pc );
  }
  
  public function puncache($property, $args) {
    $key = $this->pcache_key($args);
    unset( $this->_pc[$property][$key] );
  }
  
  
  public function pcache($property, $args, $value = null) {
    
    $ret = null;
    
    $key = $this->pcache_key($args);
    
    if (!is_null($value)) {
      $this->_pc[$property][$key] = $value;
    }
    
    if (isset($this->_pc[$property]) && isset($this->_pc[$property][$key])) {
      $ret = $this->_pc[$property][$key];
    }
    
    return $ret;
  }
  
  public function dasherize($property) {
    return str_replace("_", "-", $this->underscore($property));
  }
  
  public function underscore($property) {
    global $wf;

    $val = $this->get($property);
    
    if (!is_woof_silent($val)) {
      $val = trim($val);
      return WOOF_Inflector::underscore($val);
    }
  
    return "";
  }
  
  
  
  public function san($property, $args = array()) {
    global $wf;

    $r = wp_parse_args( $args, array("with" => "-", "remove_single_quote" => true, "trim" => true) );
    
    $val = $this->get($property);
    
    if (!is_woof_silent($val)) {

      if ($r["trim"]) {
        $val = trim($val);
      }

      $result = sanitize_title_with_dashes($val);

      
      if ($r["with"] != "_") {
        $result = str_replace("_", $r["with"], $result);
      }
    
      if ($r["remove_single_quote"]) {
        $result = str_replace("'", "", $result);
      }
    
      return $result;
    }
  
    return "";
  }
  
  public function get($name, $fallback = null) {

    $this->switch_site();
    
    if ($name == "exists") {
      // avoids strange server issues when returning $this directly.
      return clone $this;
    } 

    $class = get_class($this);
    
    if (method_exists($this, "get_delegate")) {
      $delegate = $this->get_delegate();
    
      $delegate_result = $delegate->get($name);
      
      if (!is_woof_silent($delegate_result)) {
        return $delegate_result;
      }
    }
    
    if (method_exists($this, $name)) {
      $result = $this->{$name}();
    } else {
      if (isset($this->item) && is_object($this->item)) {
        if (isset($this->item->{$name})) {
          $result = $this->item->{$name};
        }
      
      }
    }    
    
    $this->restore_site();
    
    if (isset($result)) {
      
      // favour the delegate result's error message
      if (is_woof_silent($result) && isset($delegate_result)) {
        return $delegate_result;
      }
      
      return $result;
    }
  
    
    if ($this->_property_mode == "silent") {
      return new WOOF_Silent(sprintf(__("Property '%s' does not exist", WOOF_DOMAIN), $name));
    }
  
    return null;
    
  }
  
  public function date_val($date, $format = NULL) {
    global $wf;
    
    if ($format) {
      return $wf->date_format($format, strtotime($date));
    } else {
      return strtotime($date);
    }
    
  }
  
  public function standard_debug_data() {
    $data = (object) ( (array) $this->item );
    return $this->debug_data_escape( $data );
  }
  
  public function debug_data() {
    return $this->standard_debug_data();
  }
  
  public function debug_data_escape( $data ) {
    
    $known_keys = array( "post_content" );
    
    // escape known HTML content
    
    $new_data = array();
    
    foreach ( (array) $data as $key => $item) { 
      
      if (in_array($key, $known_keys)) {
        $new_data[$key] = htmlentities( $item );
      } else {
        $new_data[$key] = $item;
      }
      
    }
    
    return (object) $new_data;
    
  }

  public function is_collection($of = "") {
    return false;
  }
  
  public function field_debug_data() {
    
    // convert to an array and back again, as we don't want any special classes
     
    $data = (object) ( (array) $this->item );
    $data = $this->debug_data_escape( $data );
 
    $data->sets = array();
    
    foreach ($this->set_names() as $set_name) {
      
      $set_data = array();
      $set = $this->set($set_name);
      $data->sets[$set_name] = $set->debug_data();

    }
  
    return $data;
    
  }
  
  public function huh($args = array()) {
    return $this->debug($args);
  }
  
    
  public function debug($args = array()) {
    
    global $wf;
    
    $r = array();
    
    $parse = true;
    
    if (is_string($args)) {
      
      $check = explode("&", $args);
      $check_2 = explode("=", $check[0]);
      
      if (count($check_2) == 1) {
        $parse = false;
        // simple label
        $r["l"] = $check_2[0];
        $r["pre"] = true;
      }
    }
    
    if ($parse) {
      
      $r = wp_parse_args( 
        $args, 
        array(
          "pre" => "1"
        )
      );
    
    } 
  
    if (isset($r["l"])) {
      echo '<h2 class="debug-label">'.$r["l"].'</h2>';
    }
    
    if (WOOF::is_true_arg($r, "pre")) {
      echo "<pre>";
    }
  
    $data = $this->debug_data();
    
    $class = get_class( $this );
    
		$my_class = $class;
		
    $parents = array();
    
    if (is_object($data)) {
      $data = (array) $data;
    } else if (!is_array($data)) {
      $data = array("value" => $data);
    }
  
    while ($class = get_parent_class($class)) {
      $parents[] = WOOF_HTML::tag("a", "target=_blank&href=" . $wf->woof_docs_base . WOOF_Inflector::dasherize($class), $class);
    }
       
    $data = array(
			"EXTENDS" => implode(", ", $parents),
			"site_id" => $this->_site_id
		) + $data;
    
		// TODO - add support for displaying collection items in this new format
		
		$out = preg_replace("/^Array/", WOOF_HTML::tag("a", "target=_blank&href=" . $wf->woof_docs_base . WOOF_Inflector::dasherize($my_class) , $my_class), print_r($data, true));
		
		echo $out;
    
    if (WOOF::is_true_arg($r, "pre")) {
      echo "</pre>";
    }
    
    return false;
    
  }
  
  
  public function call($name, $arguments) {
    
    $class = get_class($this);
    
    if (method_exists($this, $name)) {
      return call_user_func_array (array($this, $name), $arguments); 
    }
    
    
    if (method_exists($this, "get_delegate")) {
      $delegate = $this->get_delegate();
    
      $value = $delegate->call($name, $arguments);
      
      if (!is_woof_silent($value)) {
        return $value;
      }
      
      /*
      if (method_exists($delegate, $name)) {
        return call_user_func_array (array($delegate, $name), $arguments); 
      }
      */
      
    } 
    
    if (isset($this->item->{$name})) {
      $val = $this->item->{$name};
    }
  
      
    if (isset($val)) {
      return $this->item->{$name};
    }
  
    return new WOOF_Silent(sprintf(__("Method '%s' does not exist", WOOF_DOMAIN), $name));
  }
  
  
  public function __get($name) {
    return $this->get($name);
  }

  public function __call($name, $args) {

    if ($name == "or") {
      return $this;
    }
    
    return $this->call($name, $args);
  }
  
  public function item($arg = null) {
    return $this->item;
  }

  public function object() {
    return $this->item;
  }
  
  public function tmpl($key, $tmpl = "", $default = null) {
    
    $blank = true;
    
    $val = $this->get($key);

    if ($val && !is_woof_silent($val)) {
      $blank = false;
    }
    
    if (!$blank || !is_null($default)) {
      if ($blank) {
        $value = $default;
      } else {
        $value = $val;
      }
      
      if (!preg_match("/\{\{.+\}\}/", $tmpl)) {
      
        // assume we want the value in the innermost tag
      
        WOOF::incl_phpquery();

        phpQuery::newDocumentHTML('<div class="context"></div>', $charset = 'utf-8');
        pq($tmpl)->appendTo(".context");
        pq("*:only-child:last")->append("{{val}}");
        $tmpl = pq(".context")->html();
      }

      return WOOF::render_template($tmpl, array("value" => $value, "val" => $value) );
    }
    
    return "";
    
  }
  
  
  public function offsetSet($offset, $value) {

  }
  
  public function offsetExists($offset) {
    if (!is_numeric($offset)) {
      $val = $this->get($offset);
      return $val !== FALSE;
    }
  }

  public function offsetUnset($offset) {
  }

  public function offsetGet($offset) {
    if (!is_numeric($offset)) {
      return $this->get($offset);
    }
  }

  public function eval_json_field($name, $fields) {
    global $wf;
    return $wf->eval_expression($name, $this, "json");
  }

  
  

  
}
