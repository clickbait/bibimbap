<?php

// a special class to facilitate silent failure when chaining calls

class WOOF_Silent implements Iterator, arrayaccess, Countable {
  
  public $error;
  protected $_mode;
  
  public static function property($name) {
    return new WOOF_Silent(sprintf(__("Property '%s' does not exist", WOOF_DOMAIN), $name));
  }

  public static function meth($name) {
    return new WOOF_Silent(sprintf(__("Method '%s' does not exist", WOOF_DOMAIN), $name));
  }

  public function __construct($error = "", $mode = "empty") {
    $this->error = $error;
    $this->_mode = $mode;
  }
  
  public function error() {
    return $this->error;
  }

  public function __call($name, $args) {
    
    if ($name == "or") {
      if (isset($args[0])) {
        return $args[0];
      } else {
        return $this;
      }
    }
    
    if (in_array($name, array("permalink", "url", "absolute_url", "abs"))) {
      return "#";
    }
    
    return new WOOF_Silent($this->error, $this->_mode);
  }
  
  public function __get($name) {
    
    if ($name == "debug" || $name == "huh") {
      return $this->debug();
    }
    
    if (in_array($name, array("permalink", "url", "absolute_url", "abs"))) {
      return "#";
    }

    if (in_array($name, array("exists", "has", "checked"))) {
      return false;
    }

    if (in_array($name, array("blank", "is_silent"))) {
      return true;
    }
    
    
    return new WOOF_Silent($this->error, $this->_mode);
  }

  public function __set($name, $value) {

  }

  
  public function is_collection($of = "") {
    return false;
  }

	public function json() {
		return null;
	}

  public function has($arg) {
    return false;
  }

  public function exists() {
    return false;
  }

  public function blank() {
    return true;
  }

  public function checked() {
    return false;
  }

  public function is_silent() {
    return true;
  }
  
  public function __toString() {
    
    if ($this->_mode == "comment") {
      return "<!-- ".$this->error." -->";
    } 
    
    return "";
  }
  
  // iterator methods
  
  function rewind() {
  
  }

  function current() {
  
  }
     
  function key() {
  
  }

  function next() {

  }

  function valid() {
    return FALSE;
  }


  // arrayaccess methods
  
  
  public function offsetSet($offset, $value) {
    
  }
  
  public function offsetExists($offset) {
    return false;
  }

  public function offsetUnset($offset) {

  }

  public function offsetGet($offset) {
    return new WOOF_Silent( sprintf( __( "no item at offset %s", WOOF_DOMAIN ), $offset ), $this->_mode );
  }  

  public function huh($args = array()) {
    return $this->debug($args);
  }
  
  public function debug($args = array()) {
    
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
  
    print_r( $this->error );

    if (WOOF::is_true_arg($r, "pre")) {
      echo "</pre>";
    }
    
    return false;
    
  }
  
  function count() {
    return 0;
  }
  
  
}