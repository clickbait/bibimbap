<?php

class WOOF_PostType extends WOOF_Collection {
  protected $name;
  protected $item;
  
  function __construct($name) {
    $this->name = $name;
    $dontcare = $this->item();
  }
  
  function debug_data() {
    // reset to the standard debug data for WOOF_Wrap
    return $this->standard_debug_data();
  }
  
  function iterator_items() {
    return $this->posts()->items();
  }
  
  function create( $args = array(), $update = false, $strip = true) {

    global $blog_id;
    global $wf;
    global $user_ID;
    
    $defaults = array(
      'ID' => md5(uniqid()),
      'post_status' => 'draft', 
      'post_type' => $this->name,
      'post_name' => '',
      'post_author' => $user_ID,
      'ping_status' => get_option('default_ping_status'), 
      'post_parent' => 0,
      'menu_order' => 0,
      'to_ping' =>  '',
      'pinged' => '',
      'post_password' => '',
      'guid' => '',
      'post_content_filtered' => '',
      'post_excerpt' => '',
      'import_id' => 0
    );

    $item = wp_parse_args( WOOF::remap_args($args, WOOF_Post::$aliases), $defaults );
    
    $p = $wf->wrap_post( (object) $item );
    $p->_is_new = true;

    if (isset($this->_site_id)) {
		$p->set_site_id( $blog_id );
    } else {
  		$p->set_site_id( $blog_id );
    }
		
    if ($update) {
      return $p->update();
    }
    
    return $p;
  }
  
  function insert( $args, $strip = true ) {
    return $this->create( $args, true, $strip );
  }
  
  function find_or_create($slug) {
      
    if (!is_null($slug)) {
      
      $check = $this->post($slug);
    
      if ($check->exists()) {
        return $check;
      }
      
    }
  
    $args = array();
    
    if (!is_numeric($slug)) {
      $args["slug"] = $slug;
    }
    
    return $this->create($args);
  }
  
  
  function title() {
    return $this->label();
  }
  
  function posts($args = array()) {
    
    global $wf;
    
    $r = wp_parse_args(
      $args, 
      array(
        "orderby" => "title",
        "order" => "asc",
        "post_type" => $this->name,
        "posts_per_page" => -1 // show all posts by default
      )
    );
    
    return $wf->posts($r);

  }
  
  public function query_posts($args = array()) {
    $r = wp_parse_args($args);
    $r["query"] = "1";
    return $this->posts($r);
  }
  
  function latest($count = 1, $args = array()) {
    global $wf;
    
    $r = wp_parse_args(
      $args
    );
  
    $r["post_type"] = $this->name;

    return $wf->latest($count, $r);
  }


  function top_posts($args = array()) {
    global $wf;
    
    $r = wp_parse_args(
      $args, 
      array(
        "post_type" => $this->name
      )
    );
  
    return $wf->top_posts($r);
  }

  private function walk_struct($node, &$struct, &$flat, $from = 1, $to = 0, $depth = 1) {

    $lower = $from;
    $upper = $to;
    
    if ($to == 0) {
      $upper = 9999;
    }

    if ($depth >= $lower && $depth <= $upper) {
      $node["post"]->_depth = $depth;
      $flat[] = $node["post"];
    }
    
    foreach ($node["children"] as $child) {
      $this->walk_struct( $struct[$child["post"]->id], $struct, $flat, $from, $to, $depth + 1);
    }
    
  }

  function flatten_posts( $args = array() ) {
    
    $r = wp_parse_args( 
      $args,
      array(
        "orderby" => "menu_order",
        "order" => "asc",
        "from" => 0,
        "to" => 0
      )
    );
  
    $r["posts_per_page"] = -1;
    
    if (isset($r["at"])) {
      $r["from"] = $r["at"];
      $r["to"] = $r["at"];
    } else if (isset($r["depth"])) {
      $r["to"] = $r["depth"];
    } 
    
    $all = $this->posts($r);
  
    if ($this->hierarchical()) {
    
      // we need to flatten the structure
    
      // build the structure

      $struct = array();
    
      // first build a structure for all posts
      foreach ($all as $the) {
        $struct[ $the->id ] = array("post" => $the, "children" => array());
      }
    
      // copy the array to allow modification
      $loop = $struct;
    
      // now fill in the children
      foreach ($loop as $node) {

        $parent = $node["post"]->post_parent;
      
        if ($parent != 0) {
          if (isset($struct[$parent])) {
            $struct[$parent]["children"][] = $node;
          }
        } 
      
      }
    
      // now walk the top nodes

      $flat = array();
      
      foreach ($struct as $node) {
        $parent = $node["post"]->post_parent;
      
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
  

  function cap_array() {
    $cap = $this->item->cap;
    return (array) $cap;
  }
  

  function top_latest($count = 1, $args = array()) {
    global $wf;
    
    $r = wp_parse_args(
      $args, 
      array(
        "post_type" => $this->name
      )
    );
  
    return $wf->top_latest($count, $r);
  }
      
  function posts_by_title($args = array()) {
    $r = wp_parse_args( $args, array("order" => "asc") );
    $r["orderby"] = "title";
     
    return $this->posts($r);
  }

  function posts_by_menu_order($args = array()) {
    $r = wp_parse_args( $args, array("order" => "asc") );
    $r["orderby"] = "menu_order";
    return $this->posts($r);
  }
  
  function uncache_data() {
    global $wf;
    $key = $this->name."_data";
    $wf->uncache($key);
  }
  
  function cache_data($expires = "1y") {
    
    global $wf;
    
    $key = $this->name."_data";
    
    $wf->cache($key, $data, $expires, true); // always use JSON which is more readable and concise
    
  }
  
  function data($slug, $name, $value = null) {
    
    global $wf;
    
    $key = $this->name."_data";
    $ret = null;
    
    $data = $wf->cache($key, null, 600, true);

    // retrieve the transient data
    
    if (!$data) {
      $data = array();
      $ret = null; // we didn't find the data
    } 

    if (!isset($data[$slug])) {
      $data[$slug] = array();
      $ret = null; // we didn't find the data
    }
      
    if (isset($data[$slug][$name])) {
      // set the return data
      $ret = $data[$slug][$name];
    }
      
    if (is_null($ret)) {
      
      // now set the value if it's present
      if (!is_null($value)) {
        $ret = $value;
      
        if (WOOF::is_or_extends($value, "WOOF_Expression")) {
          $ret = $value->val();
        }
      
        $data[$slug][$name] = $ret;
      } 
    
    }

    return $ret;
    
  }
  
  function taxonomies() {
    global $wf;
    return $wf->taxonomies_for_type($this->name);
  }
  
  function named($slug) {
    global $wf;
    return $wf->post($slug, $this->name);
  }

  function post($id) {
    return $this->named($id);
  }
  
  function item( $arg = null ) {
    if (!$this->item) {
      $this->item = get_post_type_object($this->name);      
    }
    
    return $this->item;
  }
  
  public function cap($key, $fallback = "") {
    $cap = $this->item->cap;
    
    if (isset($cap->{$key})) {
      return $cap->{$key};
    }
    
    return $fallback;
  }
  
  function with_terms($terms, $args = array(), $operator = "IN", $relation = "OR") {
    return $this->in_a( $terms, NULL, $args, $operator, $relation );
  }
  
  function in_a($terms, $taxonomy = NULL, $args = array(), $operator = "IN", $relation = "OR") {
    
    global $wf;

    if (!is_array($terms) && is_string($terms)) {
      $terms = explode(",", $terms);
    }

    $r = wp_parse_args( 
      $args,
      array(
        'posts_per_page' => -1
      )
    );

    $r['post_type'] = $this->name;
    
    if (!isset($taxonomy)) {
      
      // if the taxonomy isn't set, $terms MUST be a single or collection of WOOF_Term objects for this to work
      
      if (WOOF::is_or_extends($terms, "WOOF_Term")) {
        $terms = $wf->collection( array($terms) );
      }
      
      if (is_woof_collection($terms, "WOOF_Term") && count($terms)) {
        
        // now build a tax query
        
        $r['tax_query'] = array("relation" => $relation);
          
        $grouped = $terms->group_by("taxonomy_name");
        
        foreach ($grouped as $taxonomy_name => $terms) {
          
          $r['tax_query'][] = array(
      	    'taxonomy' => $taxonomy_name,
      	    'terms' => $terms->extract("slug"),
      	    'field' => 'slug',
            'operator' => $operator
    	    );

        }
        
        
      } else {

        return new WOOF_Silent(__("To perform a query without specifying a taxonomy, you must provide a single WOOF_Term or a collection of WOOF_Term objects", WOOF_DOMAIN));
      
      }
      
      
    } else {
    
      $r['tax_query'] = array(
        array(
    	    'taxonomy' => $taxonomy,
    	    'terms' => $terms,
    	    'field' => 'slug',
          'operator' => $operator
    	  )
      );
    
    }
    
    return $wf->posts($r);
      
  }

  
  
  function in_an($terms, $taxonomy = NULL, $args = array(), $operator = "IN", $relation = "OR") {
    return $this->in_a($terms, $taxonomy, $args, $operator, $relation = "OR");
  }
  
  function not_in_a($terms, $taxonomy = NULL, $args = array()) {
    return $this->in_a($terms, $taxonomy, $args, "NOT IN");
  }

  function not_in_an($terms, $taxonomy = NULL, $args = array()) {
    return $this->in_a($terms, $taxonomy, $args, "NOT IN");
  }


  function in_the($terms, $taxonomy = NULL, $args = array(), $operator = "IN", $relation = "OR") {
    return $this->in_a($terms, $taxonomy, $args, $operator, $relation);
  }

  function not_in_the($terms, $taxonomy = NULL, $args = array()) {
    return $this->in_a($terms, $taxonomy, $args, "NOT IN");
  }


  function with_a($terms, $taxonomy = NULL, $args = array(), $operator = "IN", $relation = "OR") {
    return $this->in_a($terms, $taxonomy, $args, $operator, $relation);
  }

  function with_an($terms, $taxonomy = NULL, $args = array(), $operator = "IN", $relation = "OR") {
    return $this->in_a($terms, $taxonomy, $args, $operator, $relation);
  }

  function not_with_a($terms, $taxonomy = NULL, $args = array()) {
    return $this->not_in_a($terms, $taxonomy, $args);
  }

  function not_with_an($terms, $taxonomy = NULL, $args = array()) {
    return $this->in_a($terms, $taxonomy, $args, true);
  }
  
  function with_the($terms, $taxonomy = NULL, $args = array(), $operator = "IN", $relation = "OR") {
    return $this->in_a($terms, $taxonomy, $args, $operator);
  }

  function not_with_the($terms, $taxonomy = NULL, $args = array()) {
    return $this->in_a($terms, $taxonomy, $args, "NOT IN");
  }

  function that_are($terms, $args = array(), $operator = "IN", $relation = "OR") {
    return $this->in_a($terms, "category", $args, $operator, $relation);
  }

  function which_are($terms, $args = array(), $operator = "IN", $relation = "OR") {
    return $this->in_a($terms, "category", $args, $operator, $relation);
  }

  function that_are_not($terms, $args = array()) {
    return $this->not_in_a($terms, "category", $args);
  }

  function which_are_not($terms, $args = array()) {
    return $this->not_in_a($terms, "category", $args);
  }
  
  
  function tagged($terms, $args = array(), $operator = "IN", $relation = "OR") {
    return $this->in_a($terms, "post_tag", $args, $operator, $relation);
  }

  function not_tagged($terms, $args = array()) {
    return $this->not_in_a($terms, "post_tag", $args);
  }
	
  function with_meta($predicates, $args = array() ) {
		
    global $wf;
				
		$relation = "AND";
		
		// setup post args
	
		
    $r = wp_parse_args( 
      $args,
      array(
        'posts_per_page' => -1
      )
    );
	
    $r['post_type'] = $this->name;
	
		if (isset($r["relation"])) {
			// allow passthru of relation from top level
			$relation = $r["relation"];

			// ... but relation is not a valid top-level param
			unset($r["relation"]); 
		}
		
		if (!is_array($predicates)) {
			$predicates = array( $predicates );
		}
		
		// =', '!=', '>', '>=', '<', '<=', 'LIKE', 'NOT LIKE', 'IN', 'NOT IN', 'BETWEEN', 'NOT BETWEEN', 'EXISTS' (only in WP >= 3.5), and 'NOT EXISTS'
		// 'NUMERIC', 'BINARY', 'CHAR', 'DATE', 'DATETIME', 'DECIMAL', 'SIGNED', 'TIME', 'UNSIGNED'. Default value is 'CHAR'.
			
		$type = "(number|binary|char|date|datetime|decimal|signed|time|unsigned)";
		$operator = "(=|\!=|>=|<=|>|<|like|not like|in|not in|between|not between|exists|not exists|empty|not empty)";

		// allows predicates like "(char) details.car like sweet"
		
		$pattern = "/(?:\($type\))?\s*([a-z0-9\.\_\-]+)\s*$operator?\s*(.*)$/i";

		$meta_query = array( "relation" => $relation );
		
		foreach ($predicates as $predicate) {
			
			if (is_array($predicate)) {
				
				// pass the predicate through as accepted by WP_Meta_Query
				$meta_query[] = $predicate;
				
			} else if (is_string($predicate)) {
				
				preg_match( $pattern, $predicate, $matches );
				
				$parts = array(
					"compare" => "=",
					"type" => "CHAR"
				);
				
				if (sizeof($matches) == 5) {
					
					$type = strtoupper(trim($matches[1]));
					$key = trim($matches[2]);
					$compare = strtoupper(trim($matches[3]));
					$value = trim($matches[4]);
						
					if ($key != "") {

						// key must be present

						if ($type != "") {
							$parts["type"] = $type;
						} 

						if ($compare == "") { 
							// if there's no operator, assume a not empty
							$compare = "NOT EMPTY";
						}
						
						$parts["key"] = $key;

						
						if ($compare != "") {
							$parts["compare"] = $compare;
						}
						
						
						$parts["value"] = $value;

						if ($parts["compare"] == "EXISTS" || $parts["compare"] == "NOT EXISTS") {
							$parts["value"] = 'bug #23268';
						}
						
						// insert the predicate
						
						$meta_query[] = $parts;

					} // key != ""
					
				} // size == 5
				
			} // is_string predicate
			
			
			
		} // foreach
    
		if (sizeof($meta_query) > 1) {
	    $r['meta_query'] = $meta_query;
		}

    return $wf->posts($r);
      
  }

  
  public function url($root_relative = true) {
    global $wp_rewrite, $wf;
    $item = $this->item();

    if (!$item->has_archive) {
      return false; // this post type doesn't have an archive
    }
    
    $with_front = $item->rewrite["with_front"];
    
    if ($with_front) {
      $ret = $wp_rewrite->front.$item->rewrite["slug"];
    } else {
      $ret = $item->rewrite["slug"];
    }
    
    if (!$root_relative) {
      $ret = $wf->site->url().$ret;
    } else {
      $ret = "/".$ret;
    }
    
    
    return $ret;
    
  }
  
  public function feed_url($root_relative = true) {
    return $this->url($root_relative)."/feed/";
  }
  
  public function archive_url($root_relative = true) {
    return $this->url();
  }

  public function plural_label() {
    $item = $this->item();
    
    if ($item) {
      return $item->labels->name;
    }
    
    return "";
  }

  public function supports_keys() {
    global $_wp_post_type_features;
    
    return array_keys($_wp_post_type_features[$this->name()]);
  }
  
  public function supports($feature) {
    return post_type_supports($this->name, $feature);
  }
  

  public function label($key = null) {

    if (isset($key)) {
      
      if (isset($this->item->labels->{$key})) {
        return $this->item->labels->{$key};
      }
      
    }

    return $this->plural_label();
  }

  public function singular_label() {
    $item = $this->item();
    
    if ($item) {
      return $item->labels->singular_name;
    }
    
    return "";
  }
  
  public function archive_link($args = array()) {
    return $this->link($args);
  }

  public function slug() {
    return $this->item->rewrite["slug"];
  }
  
  public function rewrite_slug($fallback = false) {
    $item = $this->item;
    
    $slug = $item->rewrite["slug"];
     
    if ($slug == "" && $fallback) {
      $slug = $this->name();
    }
    
    return $slug;
  }

	public function __toString() {
		return $this->name();	
	}

  
  public function link($args = array()) {
    // get an <a> tag linking to the archive page of the post type
    // by default the text will be the plural label for the post type
    // if the post type doesn't have an archive, an empty string will be returned.
  
    $defaults = array(
      'text' => $this->plural_label(),
      'root_relative' => true,
      'current_class' => 'current'
    );
  
    $r = wp_parse_args( $args, $defaults );

    $post_type_archive_url = $this->archive_url($r["root_relative"]);
    
    if (!$post_type_archive_url) {
      return '';
    }
    
    if ( $r['current_class'] && trim($_SERVER["REQUEST_URI"], "/") == trim($post_type_archive_url, "/") ) {
      if (isset($r['class'])) {
        $r['class'] .= (' '.$r['current_class']);
      } else {
        $r['class'] = $r['current_class'];
      }
    }

 
    if ($r["root_relative"]) {
      $tag = '<a href="'.WOOF::lpad($post_type_archive_url, "/").'"';
    } else {
      $tag = '<a href="'.get_bloginfo("home").$post_type_archive_url.'"';
    }

    foreach ($r as $key => $value) {
      if ($key != "text" && $key != "href" && $key != "current_class" && $key != "root_relative") {
        $tag .= ' '.$key.'="'.esc_attr($value).'"';
      }
    }
  
    $tag .= '>'.$r['text'].'</a>';

    return $tag;
  }
    
  
}

