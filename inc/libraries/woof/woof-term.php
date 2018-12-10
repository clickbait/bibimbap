<?php


class WOOF_Term extends WOOF_Wrap {
  
  protected $top;

  public $_depth;
  public $_is_new = false;
  public $error;
  
  public static $aliases = array(
    "group" => "term_group",
    "title" => "name"
  );
     
     
  public function id() {
    return $this->item->term_id;
  }

  public function sid() {
    return $this->site_id() . ":" . $this->id();  
  }
  
  public function term_set() {
    return $this->item->term_set;
  }
  
  public function count() {
    return $this->item->count;
  }
  
  public function name() {
    return $this->item->name;
  }
  
  public function title() {
    return $this->item->name;
  }
    
  public function slug() {
    return $this->item->slug;
  }

  public function has_parent() {
    if ($this->item->parent != 0) {
      return $this->parent();
    }
    
    return false;
  }

  public function depth($nocache = false) {
    if (!isset($this->_depth) || $nocache) {
      $this->_depth = $this->ancestors->count() + 1;
    } 
    
    return $this->_depth; 
  }
  
  public function edit_url($root_relative = true) {
    $url = admin_url("edit-tags.php?action=edit&taxonomy=".$this->taxonomy_name()."&tag_ID=".$this->id());
     
    if ($root_relative) {
      return WOOF::root_relative_url( $url );
    } 
    
    return $url;
  }


  function edit_link($args = array()) {

    $type = $this->taxonomy();
    
    $cap = $type->cap("manage_terms", "manage_terms");
    
    if (current_user_can($cap, $this->id())) {
    
      $defaults = array(
        'text' => $this->name(),
        'root_relative' => true
      );
    
      $r = wp_parse_args( $args, $defaults );

      $tag = '<a href="'.$this->edit_url($r["root_relative"]).'"';
    
      foreach ($r as $key => $value) {
        if ($key != "text" && $key != "href" && $key != "root_relative") {
          $tag .= ' '.$key.'="'.esc_attr($value).'"';
        }
      }
    
      $tag .= '>'.$r['text'].'</a>';

      return $tag;

    }
    
    return "";
  }
  
  public function url() {
    return WOOF::root_relative_url( $this->permalink() );
  }

  public function permalink() {
    return get_term_link( $this->item );      
  }
  
  public function surl() {
    global $wf;
    return $wf->surl($this->permalink());
  }
  
  function link($args = array()) {

    // get an <a> tag linking to this page.
    // by default, the text of the link will be the term name
    // which is highly convenient
    
    $defaults = array(
      'text' => $this->name(),
      'root_relative' => true,
      'current_class' => 'current'
    );
    
    $r = wp_parse_args( $args, $defaults );

    if ($r['current_class'] && $this->is_current()) {
      if ($r['class']) {
        $r['class'] .= (' '.$r['current_class']);
      } else {
        $r['class'] = $r['current_class'];
      }
    }
    
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
  

  public function query_posts($args = array()) {
    $r = wp_parse_args($args);
    $r["query"] = "1";
    return $this->posts($r);
  }
  
  public function posts($args = array()) {
    
    global $wf;
    
    $r = wp_parse_args( 
      $args, array(
        'posts_per_page' => -1,
        'post_type' => 'any',
        'tax_query' => array(
          array(
      	    'taxonomy' => $this->item->taxonomy,
      	    'terms' => array( $this->slug() ),
      	    'field' => 'slug',
            'operator' => 'IN'
      	  )
        )
      ));
      
    $this->switch_site();
    
    $ret = $wf->posts($r);
    
    $this->restore_site();
    
    return $ret;
    
  }

  function posts_by_title($args = array()) {
    $r = wp_parse_args( $args, array( "order" => "asc") );
    $r["orderby"] = "title";
    return $this->posts($r);
  }
  
  function posts_by_menu_order($args = array()) {
    $r = wp_parse_args( $args, array("order" => "asc") );
    $r["orderby"] = "menu_order";
    
    return $this->posts($r);
  }

  public function children($args = array()) {
    
    $this->switch_site();
    
    global $wf;
    
    // messy methods like this are why WOOF is awesome!

    $r = wp_parse_args( $args, array(
      'orderby' => 'name',
      'hide_empty' => false,
      'order' => 'asc'
    ));

    $r = array_merge( $r , array('parent' => $this->id()));
    
    $child_terms = get_terms( $this->item->taxonomy, $r );
    
    $ret = $wf->wrap_terms( $child_terms );
      
    $this->restore_site();
    
    return $ret;
    
  }


  function has_children() {
    $children = $this->children();
    
    if ($children->count()) {
      return $children;
    }
    
    return false;
  }
  
  public function is($term, $taxonomy = null) {
    
    global $wf;
    
    $ret = false;

    $this->switch_site();
        
    $t = $wf->term($term, $taxonomy);
    
    if ($t) {
      $ret = $t->id() == $this->id();
    }
  
    $this->restore_site();
    
    return $ret;
  }

    
  public function posts_of_type($type, $args = array()) {
    $r = wp_parse_args(
      $args, array("post_type" => $type)
    );
  
    return $this->posts($r);
  }

  public function is_child_of($term, $taxonomy = null) {
    
    global $wf;
    
    $ret = false;

    $this->switch_site();
    
    if ($this->hierarchical()) {
      
      if (is_string($term)) {
        $term = $wf->term($term, $taxonomy);
      }
      
      if ($term && $term->id()) {
        $ids = $this->ancestors()->extract("id");
        $ret = in_array($term->id(), $ids);
      }

    }
    
    $this->restore_site();
    
    return $ret;
  }
  
  function siblings($include_this = false) {
    
    $this->switch_site();
    
    $r = wp_parse_args(
      $args,
      array("orderby" => "name", "order" => "asc", "hide_empty" => 0)
    );


    if ($this->has_parent()) {
      $siblings = $this->parent()->children($r);
    } else {
      $siblings = $this->taxonomy()->top_terms($r);
    }
    
    if ($include_this) {
      $ret = $siblings;
    } else {
      $siblings_without_this = array(); 
      
      foreach ($siblings as $sibling) {
        
        if (!$this->is($sibling)) {
          $siblings_without_this[] = $sibling;
        }
      }
      
      $ret = new WOOF_Collection( $siblings_without_this );
    }
    
    $this->restore_site();
    
    return $ret;
    
  }
  
  
  function next($args = array()) {
 
    $r = wp_parse_args( $args, array(
      "mode" => "sibling",
      "loop" => false
    ));
     
    extract($r);
     
    $loop = WOOF::is_true_arg($r, "loop");

    $flatten_args = $r;
    unset($flatten_args["mode"], $flatten_args["loop"], $flatten_args["parent"]);

    if ($mode == "flat") {
      
      $siblings = $this->taxonomy->flatten_terms($flatten_args);

    } else if ($mode == "cousin") {
      
      $flatten_args["at"] = count($this->ancestors()) + 1;
      $siblings = $this->taxonomy->flatten_terms($flatten_args);
      
    } else {

      $siblings = $this->siblings( true );

    }

    $index = $siblings->index_of($this->id);
     
    if ($index == $siblings->count() - 1) {
     
      if ($loop) {
        return $siblings->first();
      }

    } else {
       
      return $siblings[ $index + 1 ];
       
    }
    
    return new WOOF_Silent( __("There is no next term", "WOOF_DOMAIN") );

  }
  
  
  function prev($args = array()) {

    $r = wp_parse_args( $args, array(
      "mode" => "sibling",
      "loop" => false
    ));
     
    extract($r);
     
    $loop = WOOF::is_true_arg($r, "loop");

    $flatten_args = $r;
    unset($flatten_args["mode"], $flatten_args["loop"], $flatten_args["parent"]);

    if ($mode == "flat") {
      
      $siblings = $this->type->flatten_terms($flatten_args);

    } else if ($mode == "cousin") {
      
      $flatten_args["at"] = count($this->ancestors()) + 1;
      $siblings = $this->type->flatten_terms($flatten_args);
      
    } else {

      $siblings = $this->siblings( true );

    }

    $index = $siblings->index_of($this->id);
     
    if ($index == 0) {
     
      if ($loop) {
        return $siblings->last();
      }

    } else {
       
      return $siblings[ $index - 1 ];
       
    }
    
    return new WOOF_Silent( __("There is no previous term", "WOOF_DOMAIN") );

  }


  
  public function parent() {
    
    global $wf;
    
    $this->switch_site();
    
    if ($this->item->parent != 0) {
      $ret = $wf->wrap_term( get_term($this->item->parent, $this->item->taxonomy ) );
    } else {
      $ret = $this;
    } 
    
    $this->restore_site();
    
    return $ret;
  }
  
  public function ancestors() {

    global $wf;
      
    $ancestors = array();
    
    $term = $this;
    
    while ($term = $term->has_parent()) {
      $ancestors[] = $term;
    }
  
    $ret = new WOOF_Collection($ancestors);
    
    return $ret;
    
  }



  
  public function is_current() {
    global $wf;
    
    $this->switch_site();
    
    $ct = $wf->the_term();
    
    $ret = ($ct && $ct->id() == $this->id());
    
    $this->restore_site();
    
    return $ret;
  }
  
  public function taxonomy_and_id() {
    return $this->taxonomy_name().":".$this->id();
  }
  
  public function top() {
    if (!isset($this->top)) {
  	  
      if ($this->taxonomy()->hierarchical()) {

    	  $ancestors = $this->ancestors();
	  
    	  if ($ancestors->count()) {
    	    $this->top = $ancestors->last();
  	    } else {
  	      $this->top = $this; // regard this page as the top, for silent failure
	      }
	      
  	  } else {
  	    
  	    $this->top = $this;
	    }
	    
	  }

	  return $this->top;
	  
  }
  
  public function hierarchical() {
    global $wf;
    $tax = $this->taxonomy();
    return $tax->hierarchical();
  }


  public function taxonomy_name() {
    return $this->item->taxonomy;
  }
  
  public function taxonomy() {
    global $wf;
    
    return $wf->taxonomy($this->item->taxonomy);
  }
  
  
  public function is_a($tax) {
    return $this->item->taxonomy == $tax;
  } 

  public function is_an($tax) {
    return $this->is_a($tax);
  } 
  
  
  public function assign($values) {
    foreach ($values as $key => $value) {
      $this->{$key} = $value;
    }
  }
  
  public function relate_to($id, $type = "post") {
    global $wf;
    
    $this->switch_site();
    
    $object = $wf->post($id);
    
    if ($object->exists()) {
      wp_set_object_terms( $object->id(), (int) $this->id(), $this->taxonomy_name(), true );
    }

    $this->restore_site();
    
  }

  public function delete() {
    $this->switch_site();
    wp_delete_term( (int) $this->id(), $this->taxonomy_name() );
    $this->restore_site();
  }
  
  public function __set($name, $value) {
    
    $nn = $name; // normalized name
    
    if (isset(self::$aliases[$name])) {
      // accept some alternative property names
      $nn = self::$aliases[$name];
    }
    
    $this->item->$nn = $value;
    
  }
  
  
  function update($strip = true) {

    $this->switch_site();

    // inserts or updates the term
    
    if ($strip) {
      $this->item->name = wp_strip_all_tags($this->item->name);
    }
    
    if (!$this->_is_new) { // this term exists already
      
      $result = wp_update_term($this->item->term_id, $this->taxonomy_name(), (array) $this->item);

      if (is_wp_error($result)) {
        $this->error = $result;
      }
      
    } else {
      
      // about to create 
      $info = wp_insert_term($this->item->name, $this->taxonomy_name(), (array) $this->item); 
      
      if (!is_wp_error($info)) {
        $this->_is_new = false;
        $this->item->term_id = $info["term_id"];
        $this->item->term_taxonomy_id = $info["term_taxonomy_id"];
      } else {
        $this->error = $info;
      }
      
    }

    $this->restore_site();

    return $this;

  }
  
  
	public function __toString() {
		return $this->link();	
	}

  // implement magic methods to get related posts
  
  public function __call($name, $arguments = array()) {
    
    global $wf;
    
    // try to find a post type with this name, to get posts for this term
    
    $singular = WOOF_Inflector::singularize($name);
    
    $type = $wf->type($singular);
    
    if ($type->exists()) {
      $args = array("post_type" => $singular);
         
      if (isset($arguments[0])) {
        $args = wp_parse_args( $arguments[0] );
        $args["post_type"] = $singular;
      }
      
      return $this->posts($args);  
    }
    
    return parent::__call($name, $arguments);
    
  }
  
  
  public function __get($name) {
    
    global $wf;
    
    // try to find a post type with this name, to get posts for this term
    
    $singular = WOOF_Inflector::singularize($name);
    
    $type = $wf->type($singular);
    
    if ($type->exists()) {
      return $this->posts(array("post_type" => $singular));
    }
    
    return parent::__call($name, array());
    
  }
  
  
}


