<?php

/* 

Class: WOOF_Taxonomy
  A WOOF wrapper object representing a taxonomy in WordPress <http://codex.wordpress.org/Taxonomies>
    
*/

class WOOF_Taxonomy extends WOOF_Collection {

  public function __construct($item = null) {
    WOOF_Wrap::__construct($item);
  }
  
  function debug_data() {
    // reset to the standard debug data for WOOF_Wrap
    return $this->standard_debug_data();
  }
  
  function iterator_items() {
		return $this->terms()->items();
  }
    
  function create( $args = array(), $update = false, $strip = true) {

    global $wf, $blog_id;
    
    $defaults = array(
      'term_id' => md5(uniqid()),
      'term_group' => 0,
      'taxonomy' => $this->name,
      'description' => '',
      'parent' => 0,
      'count' => 0
    );
  
    $item = wp_parse_args( WOOF::remap_args($args, WOOF_Term::$aliases), $defaults );
    
    $t = $wf->wrap_term( (object) $item );
    $t->_is_new = true;
		$t->set_site_id( $blog_id );

    if ($update) {
      return $t->update($strip);
    }
    
    return $t;
  }
  
  function insert( $args = array(), $strip = true ) {
    return $this->create( $args, true, $strip );
  }
  
  function find_or_create($id) {


    if (!is_null($id)) {

      $check = $this->term($id);
    
      if ($check->exists()) {
        return $check;
      }
    
    }
  
    $args = array();
    
    if (!is_numeric($id)) {
      $args["slug"] = $id;
    }
    
    return $this->create($args);
  }

  public function title() {
    return $this->item->name;
  }
  
  public function singular_label() {
    $item = $this->item;
    
    if ($item) {
      if (isset($item->labels->singular_name) && $item->labels->singular_name != "") {
        return $item->labels->singular_name;
      } else {
        return WOOF_Inflector::titleize($item->name);
      }
    }
    
    return "";
  }

  public function label() {
    return $this->plural_label();
  }
  
  public function plural_label() {
    $item = $this->item;
    
    if ($item) {
      
      if (isset($item->labels->name) && $item->labels->name != "" && $item->labels->name != $item->name) {
        return WOOF_Inflector::titleize($item->labels->name);
      } else {
        return WOOF_Inflector::titleize(WOOF_Inflector::pluralize($item->name));
      }
      
    }
    
    return "";
  }
  
  
  private function walk_struct($node, &$struct, &$flat, $from = 1, $to = 0, $depth = 1) {

    $lower = $from;
    $upper = $to;
    
    if ($to == 0) {
      $upper = 9999;
    }

    if ($depth >= $lower && $depth <= $upper) {
      $node["term"]->_depth = $depth;
      $flat[] = $node["term"];
    }
    
    foreach ($node["children"] as $child) {
      $this->walk_struct( $struct[$child["term"]->id], $struct, $flat, $from, $to, $depth + 1);
    }
    
  }

  function flatten_terms( $args = array() ) {
    
    $r = wp_parse_args( 
      $args,
      array(
        "orderby" => "name",
        "order" => "asc",
        "from" => 0,
        "to" => 0
      )
    );
  
    if (isset($r["at"])) {
      $r["from"] = $r["at"];
      $r["to"] = $r["at"];
    } else if (isset($r["depth"])) {
      $r["to"] = $r["depth"];
    } 
    
    $all = $this->terms($r);

    if ($this->hierarchical()) {
    
      // we need to flatten the structure
    
      // build the structure

      $struct = array();
    
      // first build a structure for all posts
      foreach ($all as $term) {
        $struct[ $term->id ] = array("term" => $term, "children" => array());
      }
    
      // copy the array to allow modification
      $loop = $struct;
    
      // now fill in the children
      foreach ($loop as $node) {

        $parent = $node["term"]->item->parent;
      
        if ($parent != 0) {
          if (isset($struct[$parent])) {
            $struct[$parent]["children"][] = $node;
          }
        } 
      
      }
    
      // now walk the top nodes

      $flat = array();
      
      foreach ($struct as $node) {
        $parent = $node["term"]->item->parent;
      
        if ($parent == 0) {
          self::walk_struct( $node, $struct, $flat, (int) $r["from"], (int) $r["to"] );
        }
      }
    
      $ret = new WOOF_Collection( $flat );
    
    } else {

      $ret = $all;

    }
    
    
    return $ret;
    
  }
  
  public function post_types() {
    global $wf;
    $types = $this->item->object_type;
    
    $valid_types = array();
    
    foreach ($types as $type) {
      if ($wf->type($type)->exists()) {
        $valid_types[] = $type;
      }
    }
    
    return $wf->wrap_post_types($valid_types);
  }
  
  public function types() {
    return $this->post_types();
  }
  
  public function term($id) {
    global $wf;
		$res = $wf->term($id, $this->name);
		return $res;
  }
  
  function cap_array() {
    $cap = $this->item->cap;
    return (array) $cap;
  }
  
  public function cap($key = "", $fallback = "") {
    if (isset($this->item->cap)) {
      
      $cap = $this->item->cap;
    
      if (isset($key, $cap->{$key})) {
        return $cap->{$key};
      }
    
    }
    
    return $fallback;
  }
  
  public function terms($args = array()) {
    global $wf;
    $res = $wf->terms($this->item->name, wp_parse_args( $args, array("hide_empty" => 0 )));
		return $res;
  }

  public function non_empty_terms($args = array()) {
		$r = wp_parse_args( $args );
		$r["hide_empty"] = 1;
    return $this->terms($r);
  }

  public function top_terms($args = array()) {
		$r = wp_parse_args( $args );
		$r["hide_empty"] = 0;
		$r["parent"] = 0;
    return $this->terms($r);
  }

  public function top_level_terms($args = array()) {
    return $this->top_terms($args);
  }

  public function non_empty_top_level_terms($args = array()) {
		$r = wp_parse_args( $args );
		$r["hide_empty"] = 1;
		$r["parent"] = 0;
    return $this->terms($r);
  }

  public function non_empty_top_terms($args = array()) {
    return $this->non_empty_top_level_terms($args);
  }

  public function rewrite_slug($fallback = false) {
    $item = $this->item;
    
    $slug = $item->rewrite["slug"];
     
    if ($slug == "" && $fallback) {
      $slug = $this->name;
    }
    
    return $slug;
  }
  

	public function __toString() {
		return $this->item->name;	
	}


}

