<?php

class WOOF_Collection extends WOOF_Wrap implements Iterator, arrayaccess, Countable {

  public static $sort_name = array();
  public static $sort_values = array();
  
  protected $iterator_valid = FALSE;
  protected $_items;
    
  protected $_meta = array();
  
  protected $_base = 0;
  
  public static function get_sort_name() {
    if (isset(WOOF_Collection::$sort_name[count(WOOF_Collection::$sort_name) - 1])) {
      return WOOF_Collection::$sort_name[count(WOOF_Collection::$sort_name) - 1];
    }

    return "id";
  }
  
  public static function get_sort_values() {
    if (isset(WOOF_Collection::$sort_values[count(WOOF_Collection::$sort_values) - 1])) {
      return WOOF_Collection::$sort_values[count(WOOF_Collection::$sort_values) - 1];
    }

    return array();
  }
  
  function __construct($items = array()) {
    global $blog_id;
    $this->_site_id = $blog_id;

    $this->_base = 0;
    $this->_items = $items;
  }
  
  public function dump() {
    echo "<pre>";
    print_r($this);
    echo "</pre>";
  }
  
  // iterator methods

  function init_items() {
    if (!isset($this->_items)) {
      
      if (method_exists($this, "iterator_items")) {
        $this->_items = $this->iterator_items();
      }
    }
  }
  
  function rewind() {
    $this->init_items();
    $this->iterator_valid = (FALSE !== reset($this->_items)); 
  }

  
  function current() {
    $cur = current($this->_items);
    return $cur; 
    
  }
     
  function key() {
    $key = key($this->_items);
    return $key;
  }


  function next() {
    $this->iterator_valid = (FALSE !== next($this->_items));
  }

  function valid() {
    
    $this->init_items();
    return $this->iterator_valid;
  }
  
  // arrayaccess methods
  
  
  public function offsetSet($offset, $value) {
    $this->init_items();
    if (is_null($offset)) {
        $this->_items[] = $value;
    } else {
        $this->_items[$offset] = $value;
    }
  }
  
  public function offsetExists($offset) {
    $this->init_items();
    return isset($this->_items[$offset]);
  }
  public function offsetUnset($offset) {
    $this->init_items();
    unset($this->_items[$offset]);
  }

  public function offsetGet($offset) {
    $this->init_items();
    return $this->eq($offset);
  }
  
  function cls( $index, $args = array() ) {
    return $this->item_class($index, $args);
  }
  
  function item_class( $index, $args = array() ) {
    
    $r = wp_parse_args($args, array( "first" => "first", "last" => "last" ) );
    
    $classes = array();
    
    if ($index == $this->_base) {
      $classes[] = $r["first"];
    } else if ($index == (count($this) - 1 + $this->_base)) {
      $classes[] = $r["last"];
    }
    
    if (isset($r["with"]) && $r["with"]) {
      $classes[] = $r["with"];
    }
    
    if (count($classes)) {
      return 'class="'.implode(" ", $classes).'"';
    }
    
    return "";
  }
  
  function debug_data() {
    
    $data = array();
    
    foreach ($this as $item) {
      $data[] = $item->debug_data();
    }
    
    return $data;
  }
  
  function reverse($clone = true) {
    
    if (is_array($this->_items)) {
      
      if ($clone) {
        return new WOOF_Collection(array_reverse($this->_items));
      } else {
        $this->_items = array_reverse($this->_items);
      }
    
    }
  
    return $this;
    
  }
  
  function rand() {
    $min = $this->_base;
    $max = $this->_base + ($this->count() - 1);
    $r = rand($min, $max);
    return $this->item($r);
  }
  
  function extract($name, $args = array(), $remove_call_args = "separator,prefix,suffix") {
    $ret = array();
    
    $this->init_items();

    $r = wp_parse_args(
      $args,
      array(
        "prefix" => "", 
        "suffix" => ""
      )
    );
    
    $call_args = $r;
    
    if (isset($r["args"])) {
      $call_args = wp_parse_args($r["args"]);
    } else {
      
      $to_unset = $remove_call_args;

      if (!is_array($to_unset)) {
        $to_unset = explode(",", $to_unset);
      }
      
      foreach ($to_unset as $key) {
        if (isset($call_args[$key])) {
          unset($call_args[$key]);
        }
      
      }
      
    }
    
    foreach ($this->_items as $item) {
      $ret[] = $r["prefix"].$item->call($name, array($call_args)).$r["suffix"];
    }
    
    return $ret;
  }

  function flatten($name = "id", $args = array(), $remove_call_args = "separator,prefix,suffix") {
    
    $r = wp_parse_args($args,
      array("separator" => ", ")
    );
    
    if (isset($r["sep"])) {
      $r["separator"] = $r["sep"];
    }
    
    return join($r["separator"], $this->extract($name, $args, $remove_call_args));
  } 

  function csv($name) {
    return $this->flatten($name, array("separator" => ","));
  } 

  
  function tag_wrap($tag = "li", $name, $attr = array(), $args = array("separator" => "")) {
  
    $ip = array();
    
    $r = wp_parse_args($args);
    
    if (isset($r["separator"])) {
      unset($r["separator"]);
    }
    
    $items = $this->extract($name, $r);
    
    foreach ($items as $item) {
      $ip[] = WOOF_HTML::tag($tag, $attr, $item);
    }
    
    return implode($args["separator"], $ip);
  }
  

  function implode($glue = ",", $name = "name") {
    return implode(",", $this->extract($name));
  }
  
  function by_number($name, $order = "ASC", $case_insensitive = true, $clone = false) {
    return $this->sort($name, $order = "ASC", $case_insensitive, $clone, true);
  }
  
  function by($name, $order = "ASC", $case_insensitive = true, $clone = false, $numeric = "AUTO") {
    return $this->sort($name, $order, $case_insensitive, $clone, $numeric);
  }

  function sort($name, $order = "ASC", $case_insensitive = true, $clone = false, $numeric = "AUTO") {

    $this->init_items();

    $items = &$this->_items;
    
    if (count($items)) {
      
      // arrays are required to prevent a single variable being overwritten in nested calls to sort 

      array_push(WOOF_Collection::$sort_name, $name);
      
      if ($case_insensitive) {
        $cmp_func = "strcasecmp";
      } else {
        $cmp_func = "strcmp";
      }
      
      if ($clone) {
        $the_clone = $items;
      } else {
        $the_clone = &$items;
      }
      
      if (is_null($name)) {
        
        // special case, where we just want to access the item itself (for a simple collection of primitive types)
    
        if ($numeric === true) {

          if (strtoupper($order) == "ASC") {
            usort( $the_clone, create_function('$a,$b', 'return (float) $a < (float) $b ? 1 : -1;') );
          } else {
            usort( $the_clone, create_function('$a,$b', 'return (float) $b < (float) $a ? 1 : -1;') );
          }
          
        } else {

          if (strtoupper($order) == "ASC") {
            usort( $the_clone, create_function('$a,$b', 'return '.$cmp_func.'(  $a, $b );') );
          } else {
            usort( $the_clone, create_function('$a,$b', 'return '.$cmp_func.'(  $b, $a );') );
          }

        }
      
      } else {
        
        if ($numeric == "AUTO") {
        
          // check for known numeric properties
          if ($name == "menu_order") {
            $numeric = true;
          }
          
          if ($name == "id" && !WOOF::is_or_extends($items[0], "WOOF_Role")) {
            // WOOF_Role has string-based IDs
            $numeric = true;
          }

        }
        
        if ($numeric === true) {

          if (strtoupper($order) == "ASC") {
            usort( $the_clone, create_function('$a,$b', '$sn = WOOF_Collection::get_sort_name(); return (float) WOOF::eval_expression($sn, $a) > (float) WOOF::eval_expression($sn, $b) ? 1 : -1;') );
          } else {
            usort( $the_clone, create_function('$a,$b', '$sn = WOOF_Collection::get_sort_name(); return (float) WOOF::eval_expression($sn, $b) < (float) WOOF::eval_expression($sn, $a) ? 1 : -1;') );
          }

        } else {
          
          if (strtoupper($order) == "ASC") {
            usort( $the_clone, create_function('$a,$b', '$sn = WOOF_Collection::get_sort_name(); return '.$cmp_func.'(  WOOF::eval_expression($sn, $a), WOOF::eval_expression($sn, $b) );') );
          } else {
            usort( $the_clone, create_function('$a,$b', '$sn = WOOF_Collection::get_sort_name(); return '.$cmp_func.'(  WOOF::eval_expression($sn, $b), WOOF::eval_expression($sn, $a) );') );
          }
        
        }
        
      }

      array_pop(WOOF_Collection::$sort_name);
      
      if ($clone) {
        return new WOOF_Collection($the_clone);
      } else {
        return $this;
      }


    }
    
    return $this;
    
  }

  function group_by($name) {

    $this->init_items();

    $abt = array();
    $ret = array();
    
    foreach ($this->_items as $item) {

      $val = WOOF::eval_expression( $name, $item, "string" );
			
			if (!is_woof_silent($val)) {
        $abt[$val][] = $item;
      }
      
    }
    
    foreach ($abt as $key => $arr) {
      $ret[$key] = new WOOF_Collection($arr);
    }

    return $ret;
    
  }
  
  function sort_to($name, $values, $order = "ASC", $clone = false) {

    $this->init_items();
    
    if (is_array($values)) {
      $items = $this->_items;
      
      $class = get_class($this);
      
      // arrays are required to prevent a single variable being overwritten in nested calls to sort_to 
      
      array_push(WOOF_Collection::$sort_name, $name);
      array_push(WOOF_Collection::$sort_values, $values);

      //WOOF_Collection::$sort_name = $name;
      //WOOF_Collection::$sort_values = $values;
    
      if ($clone) {
        $clone_items = $items;
      } else {
        $clone_items = &$this->_items;
      }
    
      if (is_null($name)) {
        
        // special case, where we just want to access the item itself (for a simple collection of primitive types)
      
        $sort_func = "usort";
      
        if (WOOF::is_assoc($clone_items)) {

          if (strtoupper($order) == "ASC") {
            uasort( $clone_items, create_function('$a,$b', '$sv = WOOF_Collection::get_sort_values(); $as = array_search($a, $sv); $bs = array_search($b, $sv); if ($as === false && $bs !== false) return 1; if ($bs === false && $as !== false) return -1; if ($as === $bs) return 0; return ($as < $bs) ? -1 : +1;'));
          } else {
            uasort( $clone_items, create_function('$a,$b', '$sv = WOOF_Collection::get_sort_values(); $bs = array_search($b, $sv); $as = array_search($a, $sv); if ($as === false && $bs !== false) return 1; if ($bs === false && $as !== false) return -1; if ($as === $bs) return 0; return ($as < $bs) ? 1 : -1;'));
          }
        
        
        } else {

          if (strtoupper($order) == "ASC") {
            usort( $clone_items, create_function('$a,$b', '$sv = WOOF_Collection::get_sort_values(); $as = array_search($a, $sv); $bs = array_search($b, $sv); if ($as === false && $bs !== false) return 1; if ($bs === false && $as !== false) return -1; if ($as === $bs) return 0; return ($as < $bs) ? -1 : +1;'));
          } else {
            usort( $clone_items, create_function('$a,$b', '$sv = WOOF_Collection::get_sort_values(); $bs = array_search($b, $sv); $as = array_search($a, $sv); if ($as === false && $bs !== false) return 1; if ($bs === false && $as !== false) return -1; if ($as === $bs) return 0; return ($as < $bs) ? 1 : -1;'));
          }
        
        }
      
      
      } else {

        if (strtoupper($order) == "ASC") {
          usort( $clone_items, create_function('$a,$b', '$sv = WOOF_Collection::get_sort_values(); $sn = WOOF_Collection::get_sort_name(); $as = array_search(WOOF::eval_expression($sn, $a), $sv); $bs = array_search(WOOF::eval_expression($sn, $b), $sv); if ($as === false && $bs !== false) return 1; if ($bs === false && $as !== false) return -1; if ($as === $bs) return 0; return ($as < $bs) ? -1 : +1;'));
        } else {
          usort( $clone_items, create_function('$a,$b', '$sv = WOOF_Collection::get_sort_values(); $sn = WOOF_Collection::get_sort_name(); $bs = array_search(WOOF::eval_expression($sn, $b), $sv); $as = array_search(WOOF::eval_expression($sn, $a), $sv); if ($bs === false && $as !== false) return -1; if ($as === $bs) return 0; return ($as < $bs) ? 1 : -1;'));
        }
      
      }
    
      array_pop(WOOF_Collection::$sort_name);
      array_pop(WOOF_Collection::$sort_values);


      if ($clone) {
        return new $class($clone_items);
      } else {
        return $this;
      }
      
      
    } 
    
    return $this;
    
  }
  
  
  function orderby($name, $order = "ASC", $case_insensitive = true, $numeric = false) {
    return $this->sort($name, $order, $case_insensitive, true, $numeric);
  }

     
  function first($count = 1) {
    
    $this->init_items();

    $items = $this->_items;
    
    if (count($items)) {
      if ($count == 1) {
        return $items[$this->_base];
      } else {
        return $this->range(0, $count - 1);
      }
    }
  
    return new WOOF_Silent(__("Can't get first item. Collection is empty", WOOF_DOMAIN));
  }

  function count() {
    $this->init_items();

    $items = $this->_items;
    return count($items);
  }

  function count_text($one, $many, $zero = null) {
    
    $count = $this->count();
    
    if (is_null($zero)) {
      $zero = $many;
    }
    
    if ($count == 0) {
      return sprintf($zero, $count);
    } else if ($count == 1) {
      return sprintf($one, $count);
    } 
    
    return sprintf($many, $count);
    
  }
    
  function is_empty() {
    $this->init_items();
    return !count($this->_items);
  }

  function last($count = 1) {
    $this->init_items();
    
    $total = $this->count();
    
    if ($count == 1) {
      return $this->eq(-1);
    } else {
      return $this->range($total - 1 - $count, $total - 1); 
    }
    
  }

  function eq($index) {
    $this->init_items();
    $items = $this->_items;
    
    if ($index < 0) {
      $index = max(0, count($items) + $index + $this->_base);
    } 
    
    if (isset($items[$index])) {
      return $items[$index];
    }
  
    return new WOOF_Silent(sprintf(__("This collection has no element at index %d, which is outside the bounds of this collection", WOOF_DOMAIN), $index));  
  }
   
  function item($index = 0) {
    return $this->eq($index);
  }

  function range($from, $to = null) {
    $this->init_items();

    if (is_null($to)) {
      $to = $this->count() - 1;
    }
    
    $slice = array_slice( $this->_items, $from , $to - ($from) + 1 );
    
    $class = get_class($this);
    
    return new $class($slice);
  }
  
  function index_of($value, $prop = "id") {
    
    global $wf;
    
    foreach ($this as $index => $item) {
      
      $item_value = $wf->eval_expression($prop, $item);
      
      if ($item_value == $value) {
        return $index + $this->_base;
      } 
      
    }
    
    return -1;

  }
  
  function find_by($prop, $val) {
    
    foreach ($this->_items as $item) {
    
      if ($item->{$prop} == $val) {
        return $item;
      }
      
    }

    return false;
  }

  function contains($prop, $value) {
    return count( $this->find_by_in($prop, array($value) ) );
  }
  
  function find_by_in($prop, $vals) {
    $this->init_items();

    if (!is_array($vals)) {
      $vals = explode(",", $vals);
    }
    
    $class = get_class($this);
    
    $matches = array();
    
    foreach ($this->_items as $item) {
      
      if (in_array($item->{$prop}, $vals)) {
        $matches[] = $item;
      }
      
    }
    
    return new $class($matches);
  }
  
  function i($index) {
    return $this->eq($index);
  }
  
  function items($index = null) {
    if ($index != null) {
      return $this->eq($index);
    }
    
    return $this->_items;
  }

  function number($index) {
    if ($this->_base == 0) {
      
      if ($index <= 0) {
        // negatives or zero should be the same index
        return $this->eq($index);
      } else {
        // anything else should act like a 1-base.
        return $this->eq($index - 1);
      }
      
    } else {
      return $this->eq($index);
    }
  }
  

  function prepend() {

    $this->init_items();
    $args = func_get_args();
    
    foreach ($args as $arg) {
      array_unshift($this->_items, $arg);
    }

    return $this;
  }

  function unshift() {
    
    $this->init_items();

    $args = func_get_args();
    
    foreach ($args as $arg) {
      array_unshift($this->_items, $arg);
    }

    return $this;

  }

  function push() {
    
    $this->init_items();

    $args = func_get_args();
    
    foreach ($args as $arg) {
      array_push($this->_items, $arg);
    }

    return $this;

  }
  

  function add($array) {
    $coll = $this->merge($array, false);
  }
  
  public function is_collection($of = "") {
    if ($of) {
      $item = $this->first();

      // note that an EMPTY collection will also be regarded as a collection of the given class
      // it's not correct to say it isn't, and this is likely to be the most elegant way to handle it. 
      
      if ($item->exists()) {
        return WOOF::is_or_extends($item, $of);
      } 
    
    }
    
    return TRUE;
  }


  function merge($array, $clone = true) {
    
    $this->init_items();

    $items = $array;
    
    if (is_a($array, "WOOF_Collection") || is_subclass_of($array, "WOOF_Collection")) {
      $items = $array->items();
    } else {
      $items = array();
    }
    
    $merged = array_merge($this->items(), $items);
  
    if (!$clone) {
      $this->_items = $merged;
      return $this;
    }
    
    return new WOOF_Collection($merged);

  }
  
  function append() {
    $this->init_items();

    $args = func_get_args();
    
    foreach ($args as $arg) {
      array_push($this->_items, $arg);
    }
    
    return $this;
  }
  
  public function dedupe($by = "sid") {
    $this->init_items();

    $found = array();
    $loop_items = $this->_items;
    
    foreach ($loop_items as $index => $item) {
      $id = $item->get($by);
      
      if ($id && in_array($id, $found)) {
        unset($this->_items[$index]);
      } else if ($id) {
        $found[] = $id;
      }
      
    }

    return $this;
    
  }

  public function intersect($coll, $on = "id") {
    
    global $wf;
    
    if (is_woof_collection($coll)) {
    
      if (count($coll) && count($this)) {

        $int = array();
      
        foreach ($this as $item) {
          if ($coll->find_by($on, $item->get($on))) {
            $int[] = $item;
          }
        }
      
        return $wf->collection($int);

      } else if (count($this)) {
        
        return $this;
        
      } else {
        
        return $coll;
        
      }
      
    } else {

      return $this;

    }
    
    
  }
  
  public function __toString() {
    if ($this->is_empty()) {
      return "";
    } else {
      return get_class($this);
    }
  
  }
  
  public function __get($name) {
    if (isset($this->_meta[$name])) {
      return $this->_meta[$name];
    }
    
    return parent::__get($name);
  }
  
  public function __set($name, $value) {
    $this->_meta[$name] = $value;
  }
  
  public function copy_meta($from, $keys = array()) {
    
    if (!is_array($keys)) {
      $keys = explode(",", $keys);
    }
    
    foreach ($keys as $key) {
      
      if (isset($from->$key)) {
        $this->_meta[$key] = $from->$key;
      }
      
    }
    
  }
  
  
}

class WOOF_SimpleCollection extends WOOF_Collection {
  
  function flatten($separator = ", ", $args = array(), $remove_call_args = "") {
    return implode($separator, $this->items);
  } 
  
  function __toString() {
    return $this->flatten();
  }
    
}
