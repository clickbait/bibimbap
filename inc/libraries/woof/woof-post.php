<?php

class WOOF_Post extends WOOF_Wrap {

  public static $aliases = array(
      "author" => "post_author",
      "date" => "post_date",
      "date_gmt" => "post_date_gmt",
      "content" => "post_content",
      "title" => "post_title",
      "excerpt" => "post_excerpt",
      "status" => "post_status",
      "password" => "post_password",
      "name" => "post_name",
      "slug" => "post_name",
      "modified" => "post_modified",
      "modified_gmt" => "post_modified_gmt",
      "content_filtered" => "post_content_filtered",
      "parent" => "post_parent",
      "type" => "post_type",
      "mime_type" => "post_mime_type"
  );
     
  protected $_col;
  public $_is_new = false;
  public $error;
  
	public $_meta = array();
	
  public $_depth;
  protected $_reps = array();

  static function trim_excerpt($text, $args) {
    
  	$text = strip_shortcodes( $text );
    $text = apply_filters('the_content', $text);
  	$text = str_replace(']]>', ']]&gt;', $text);
  	$text = strip_tags($text);

    $r = wp_parse_args($args, array(
        "more" => '[...]'
      )
    );
      
  	$excerpt_length = apply_filters('excerpt_length', $r["more"]);
	
  	$excerpt_more = apply_filters('excerpt_more', ' ' . '[...]');
  	$words = preg_split("/[\n\r\t ]+/", $text, $excerpt_length + 1, PREG_SPLIT_NO_EMPTY);
  	
  	if ( count($words) > $excerpt_length ) {
  		array_pop($words);
  		$text = implode(' ', $words);
  		$text = $text . $excerpt_more;
  	} else {
  		$text = implode(' ', $words);
  	}

  	return $text;

  }

  public function col() {
    if (!isset($this->_col)) {
      $this->_col = new WOOF_PostCol($this);
    }
    
    return $this->_col;
  }

  public function title() {
    return $this->item->post_title;
  }

  public function depth($nocache = false) {
    if (!isset($this->_depth) || $nocache) {
      $this->_depth = $this->ancestors->count() + 1;
    } 
    
    return $this->_depth; 
  }
  
  public function title_rss() {
  	$title = $this->title;
  	$title = apply_filters('the_title_rss', $title);
  	return $title;
  }

  public function menu_order($base = 0) {
    return $this->item->menu_order + $base;
  }
  
  public function thumbnail() {

    $ret = false;
    
    global $wf;

    $ic = $wf->get_image_class();

    $image = get_post_thumbnail_id( $this->item->ID );

    if ($image && is_numeric($image)) {
      
      $file_url = wp_get_attachment_url( $image );
      $file_path = WOOF_File::infer_content_path($file_url);
    
    
      $ret = new $ic( $file_path, $file_url );
    
    }

    if (!$ret) {
      return new WOOF_Silent(__("Cannot generate thumbnail - no featured image has been set", WOOF_DOMAIN));
    }
    
    return $ret;

  }

  public function featured_image() {
    return $this->thumbnail();
  }

  public function has_thumbnail() {
    return get_post_thumbnail_id( $this->item->ID );
  }

  public function has_featured_image() {
    return $this->has_thumbnail();
  }

  public function raw_content($args = array()) {
    $r = wp_parse_args( $args );
    $r["raw"] = 1;
    
    return $this->content($args);
  }
  
  public function content($args = array()) {
    global $wf;
    
    $r = wp_parse_args(
      $args, 
      array("raw" => false)
    );
    
    $content = $this->item->post_content;
    
    
    if (!$r["raw"]) {
      $wf->filter_post = $this;
      $content = apply_filters('the_content', $content);
    }

    if (isset($r["clickable"]) && $r["clickable"]) {
      $content = make_clickable($content);
    }
    
    return $content;
  }

  public function content_feed($feed_type = null) {
  	if ( !$feed_type )
  		$feed_type = get_default_feed();

  	$content = $this->content();
  	$content = str_replace(']]>', ']]&gt;', $content);
  	return apply_filters('the_content_feed', $content, $feed_type);
  }
  
  public function date($format = NULL) {
    global $wf;
    
    if ($format) {
      return $wf->date_format($format, strtotime($this->item->post_date));
    } else {
      return strtotime($this->item->post_date);
    }
  }

  public function comments($args = array()) {
    global $wf;
    
    $r = wp_parse_args($args);
    $r["post_id"] = $this->id();
    
    return $wf->comments( $r );
  }
  
  public function comments_open() {
    return $this->item->comment_status == "open";
  }

  public function comments_count() {
    return get_comments_number($this->id());
  }
  
  public function comments_number($zero = "", $one = "", $many = "") {

    $val = $this->comments_count();
    
    if ($zero == "") {
      $zero = __("No comments", WOOF_DOMAIN);
    }

    if ($one == "") {
      $one = __("1 comment", WOOF_DOMAIN);
    }

    if ($many == "") {
      $many = __("%d comments", WOOF_DOMAIN);
    }
    
    return WOOF::items_number($val, $zero, $one, $many);
  }

  public function comments_link($args = array()) {
    
    $defaults = array(
      'root_relative' => false,
      'zero' => "",
      'one' => "",
      'many' => ""
    );
    
    
    $r = wp_parse_args( $args, $defaults );
    
    if (!isset($r["text"])) {
      $r['text'] = $this->comments_number($r["zero"], $r["one"], $r["many"]);
    }
   
    $tag = '<a href="'.$this->comments_url($r["root_relative"]).'"';

    foreach ($r as $key => $value) {
      if ($key != "text" && $key != "href" && $key != "root_relative") {
        $tag .= ' '.$key.'="'.esc_attr($value).'"';
      }
    }
    
    $tag .= '>'.$r['text'].'</a>';

    return $tag;
    
  }
  
  public function comments_url($root_relative = false) {
    $url = get_comments_link($this->id());
    
    if ($root_relative) {
      return WOOF::root_relative_url($url);
    }
    
    return $url;
  } 
  
  public function modified($format = NULL) {
    global $wf;
    
    if ($format) {
      return $wf->date_format($format, strtotime($this->item->post_modified));
    } else {
      return strtotime($this->item->post_modified);
    }
  }
  
  public function format() {
    return get_post_format($this->id());
  }
  
  public function excerpt($args = array()) {

    $defaults = array("length" => 55, "force" => false, "more" => "&hellip;&nbsp;", "link" => false, "raw" => true, "strip_shortcodes" => true);
    
    $r = wp_parse_args( $args, $defaults );

    $ex = $this->item->post_excerpt;
    
    if (trim($ex) == "" || $r["force"] == "true" || $r["force"] == "1") {
      
      if ($r["length"]) {
        $el = create_function('$length', 'return '.$r["length"].';');
        add_filter("excerpt_length", $el);
      }

      $link = "";
      
      if ($r["link"]) {
        $link = $this->link( array( "text" => $r["link"] ) );
      }

      if (isset($r["more"])) {
        $em = create_function('$more', 'return "'.$r["more"].'".\''.$link.'\';');
        add_filter("excerpt_more", $em);
      }

      
      if (isset($r["content"])) { // allow the content to be provided manually (for pre-processing)
        $content = $r["content"];
      } else {

        if (trim($ex) != "") {
          $content = trim($ex);
        } else {
          $content = $this->content($r);
        }

      }
      
      if (!$r["strip_shortcodes"]) {
        $content = strip_shortcodes($content);
      }
      
      $ex = self::trim_excerpt($content, $args);
      
      if ($el) {
        remove_filter("excerpt_length", $el);
      }
      
      if ($em) {
        remove_filter("excerpt_more", $em);
      }  
    }    


    return trim($ex);
  }

  function is_current() {
    global $wf, $wp_query;
     
    return $this->id() == WOOF::$the_id;
  }

  public function type_name() {
    return $this->item->post_type;
  }
  
  public function type() {
    return new WOOF_PostType($this->item->post_type);
  }

  public function post_type() {
    return new WOOF_PostType($this->item->post_type);
  }

  public function id() {
    return $this->item->ID;
  }
  
  public function sid() {
    return $this->site_id() . ":" . $this->id();  
  }
  
  public function is_a($type) {
    return $this->type_name() == $type;
  } 

  public function is_an($type) {
    return $this->type_name() == $type;
  } 

  public function is($post, $type = null) {
    
    global $wf;
    
    if ($type == null) {
      $type = $this->type_name;
    }
    
    $post = $wf->post($post, $type);

    if ($post) {
      return $this->id() == $post->id();
    }
  
    return false;
  }


  public function slug() {
    return $this->item->post_name; 
  }

  public function permalink($encode = false) {
    
    global $blog_id;
    
    $this->switch_site();
    
    $pl = get_permalink($this->id());
    
    if ($encode) {
      $pl = urlencode($pl);
    }
    
    $ret = $pl;
  
    $this->restore_site();

    $ret = apply_filters("woof_".$this->type_name()."_permalink", $ret, $this);
    return apply_filters("woof_permalink", $ret, $this);
  }

  public function permalink_rss($encode = false) {
  	return apply_filters('the_permalink_rss', $this->permalink($encode) );
  }

  public function url($root_relative = false) {

    if ($root_relative) {
      return WOOF::root_relative_url($this->permalink());
    } else {
      return $this->permalink();
    }

  }

  public function surl() {
    global $wf;
    return $wf->surl($this->permalink());
  }
  
  
  public function url_in_site() {
    
    $url = $this->url(true);

    if (!is_multisite()) {
      return $url;
    }
    
    $site = $this->site();
    
    $path = trim($site->path, "/");
    
    if ($path != "") {
      $url = preg_replace( "/\/$path\//", "/", $url);
    }
    
    return $url;
    
  }
  
  public function urlencode($root_relative = TRUE) {
    return urlencode($this->url($root_relative));
  }

  function menu_edit_url($root_relative = true) {
    return preg_replace("/^\/wp-admin\//", "", $this->edit_url());
  }
  
  function edit_url($root_relative = true) {
    $url = admin_url("post.php?post=".$this->id()."&amp;action=edit");
    
    if ($root_relative) {
      return WOOF::root_relative_url( $url );
    } 
    
    return $url;
  }

  function edit_link($args = array()) {

    $this->switch_site();

    $ret = "";
    
    $type = $this->type();
    
    $cap = $type->cap("edit_post", "edit_post");
    
    if (current_user_can($cap, $this->id())) {
    
      $defaults = array(
        'text' => $this->title(),
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

      $ret = $tag;

    }
    
    $this->restore_site();
    
    return $ret;
  }
  
  
  function relative_link($args = array()) {
    $args = array_merge( $args , array('root_relative' => true));
    return $this->link($args);
  }

  function rlink($args = array()) {
    $args = array_merge( $args , array('root_relative' => true));
    return $this->link($args);
  }
  
  function link($args = array()) {
    // get an <a> tag linking to this page.
    // by default, the text of the link will be the page title
    // which is highly convenient!
    
    $defaults = array(
      'text' => $this->title(),
      'root_relative' => false,
      'current_class' => 'current'
    );
    
    $r = wp_parse_args( $args, $defaults );
    
    if ($r['current_class'] && $this->is_current()) {
      if (isset($r['class'])) {
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

  public function attachments($args = array()) {
    global $wf;
    
    $this->switch_site();
    
    $r = wp_parse_args( 
      $args, 
      array(
        'numberposts' => -1,
        'order' => 'ASC',
        'orderby' => 'menu_order ID'
      )
    );
    
    $r["post_parent"] = $this->id();
    $r["post_type"] = 'attachment';

    $attachments = $wf->wrap_attachments( get_posts( $r ) );

    $this->restore_site();
    
    return $attachments;
  }
  
  public function image_attachments($args = array()) {
    $r = wp_parse_args( $args );
    $r["post_mime_type"] = "image";
    
    return $this->attachments( $r );
  }
  
  public function terms($taxonomy = NULL, $fields = array()) {
    global $wf;

    $this->switch_site();
    
    $ret = $wf->collection();
    
    if (isset($taxonomy)) {
      $ret = $wf->wrap_terms( get_the_terms($this->id(), $taxonomy) );
    } else {
      
      $terms = $wf->collection();
      
      $taxonomies = $this->type->taxonomies();
      
      foreach ($taxonomies as $tax) {
        $terms = $terms->merge( $wf->wrap_terms( get_the_terms($this->id(), $tax->name) ) );
      }
      
      $ret = $terms;
      
    }
  
    $this->restore_site();
  
    return $ret;
    
  }
  
  public function categories() {
    return $this->terms("category");
  }

  public function tags() {
    return $this->terms("post_tag");
  }

  public function has_category($term) {
    return $this->has_term($term);
  }

  public function has_tag($term) {
    return $this->has_term($term, "post_tag");
  }

  public function tagged($term) {
    return $this->has_term($term, "post_tag");
  }

  public function is_tagged($term) {
    return $this->has_term($term, "post_tag");
  }
  
  public function has_term($term, $taxonomy = "category") {
    
    $term_slug = $term;
    
    if (is_string($term)) {
      $term_slug = explode(",", $term_slug);
    } else if (is_object($term)) {
      $term_slug = $term->slug();
    } else if (is_array($term_slug)) {
      $term_slug = implode(",", $term);
    }
    
    foreach ($this->terms($taxonomy) as $term) {
      if (in_array($term->slug(), $term_slug)) {
        return $term;
      }
    }
    
    return false;
  }
  
  public function hierarchical() {
    global $wf;
    
    $type = $this->type();

    return $type->hierarchical();
  }

  public function meta($key, $single = false) {
    $id = $this->id();
    
    $this->switch_site();
    
    $items = array();
    
    if ($single) {
      $ret = get_post_meta($id, $key, true);
    } else {
      $items = get_post_meta($id, $key);
      $ret = WOOF_SimpleCollection($items);
    }
  
    $this->restore_site();
    
    return $ret;
    
  }
  
  public function related_by_term($args = array()) {
    
    global $wf;
    
    $ret = new WOOF_Silent( __("There are no related posts", WOOF_DOMAIN ) );
    
    $this->switch_site();
    
    $r = wp_parse_args( 
      $args,
      array(
        "taxonomy" => "*",
        "meta" => "",
        "exclude_terms" => array()
      )
    );
    
    $pargs = $r;

    unset($pargs["taxonomy"]);
    unset($pargs["meta"]);
    unset($pargs["exclude_terms"]);
    
    $pargs["post__not_in"] = array($this->id()); // don't include THIS post
    
    extract($r);
    
    $tax_query = array("relation" => "OR");
    
    if ($taxonomy == "*") {
      $tax_names = $this->type()->taxonomies()->extract("name");  
    } else if ($taxonomy && $taxonomy != "") {
      $tax_names = explode(",", $taxonomy);
    }

    if (!is_array($exclude_terms)) {
      $exclude_terms = explode(",", $exclude_terms);
    }
    
    foreach ($tax_names as $tax_name) {
      $terms = $this->terms($tax_name)->extract("slug");
      $t = $terms;
      
      if (count($exclude_terms)) {
        $t = array_diff($terms, $exclude_terms);
      }
      
      if (count($t)) {
        $tax_query[] = array("taxonomy" => $tax_name, "operator" => "IN", "terms" => $t, "field" => "slug");
      }
    
    }


    if (count($tax_query) > 1) {

      $pargs["tax_query"] = $tax_query;
      $posts = $wf->posts($pargs);
      
      $ret = $posts;
    }
    
    $this->restore_site();
    
    return $ret;
    
  }
  
  public function children($args = array()) {
  
    global $wf;
    
    $ret = $wf->collection();

    $this->switch_site();
    
    if ($this->hierarchical()) {
      
      $defaults = array(
        'sort_order' => 'ASC',
        'sort_column' => 'menu_order',
        'order' => 'ASC',
        'orderby' => 'menu_order',
        'hierarchical' => 0,
        'exclude' => NULL,
        'include' => NULL,
        'meta_key' => NULL,
        'meta_value' => NULL,
        'authors' => NULL,
        'exclude_tree' => NULL,
        'number' => NULL,
        'offset' => 0,
        'post_type' => $this->type_name(),
        'post_status' => 'publish'
      );
  
      $r = wp_parse_args( $args, $defaults );
    
      $r = array_merge( $r , array('post_parent' => $this->item->ID));
      
      $ret = $wf->posts($r);

    }
    
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
  
  function siblings($include_this = false, $args = array()) {
    
    $this->switch_site();
    
    $r = wp_parse_args(
      $args,
      $this->hierarchical() ? array("orderby" => "menu_order", "order" => "asc") : array("orderby" => "post_date", "order" => "desc")
    );
    
    if ($this->has_parent()) {
      $siblings = $this->parent()->children($r);
    } else {
      $siblings = $this->type()->top_posts($r);
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
 
    $this->switch_site();
    
    $r = wp_parse_args( $args, array(
      "mode" => "sibling",
      "loop" => false
    ));
     
    extract($r);
     
    $loop = WOOF::is_true_arg($r, "loop");

    $flatten_args = $r;
    unset($flatten_args["mode"], $flatten_args["loop"], $flatten_args["post_parent"]);

    $ret = new WOOF_Silent( __("There is no next post", "WOOF_DOMAIN") );

    if ($mode == "flat") {
      
      $siblings = $this->type->flatten_posts($flatten_args);

    } else if ($mode == "cousin") {
      
      $flatten_args["at"] = count($this->ancestors()) + 1;
      $siblings = $this->type->flatten_posts($flatten_args);
      
    } else {

      $siblings = $this->siblings( true, $args );

    }
    
    $index = $siblings->index_of($this->id);
     
    if ($index == $siblings->count() - 1) {
     
      if ($loop) {
        $ret = $siblings->first();
      }

    } else {
    
      $ret = $siblings[ $index + 1 ];
       
    }
    
    $this->restore_site();
    
    return $ret;

  }
  
  function next_by_date($args = array()) {
    $r = wp_parse_args($args);
    
    $r["orderby"] = "post_date";
    $r["order"] = "asc";
    
    return $this->next($r);
  }
  
  
  function prev($args = array()) {

    $ret = new WOOF_Silent( __("There is no previous post", "WOOF_DOMAIN") );
    
    $this->switch_site();
    
    $r = wp_parse_args( $args, array(
      "mode" => "sibling",
      "loop" => false
    ));
     
    extract($r);
       
    $loop = WOOF::is_true_arg($r, "loop");

    $flatten_args = $r;
    unset($flatten_args["mode"], $flatten_args["loop"], $flatten_args["post_parent"]);

    if ($mode == "flat") {
      
      $siblings = $this->type->flatten_posts($flatten_args);

    } else if ($mode == "cousin") {
      
      $flatten_args["at"] = count($this->ancestors()) + 1;
      $siblings = $this->type->flatten_posts($flatten_args);
      
    } else {

      $siblings = $this->siblings( true, $args );

    }

    $index = $siblings->index_of($this->id);
     
    if ($index == 0) {
     
      if ($loop) {
        $ret = $siblings->last();
      }

    } else {
       
      $ret = $siblings[ $index - 1 ];
       
    }
    
    $this->restore_site();
    
    return $ret; 

  }

  function prev_by_date($args = array()) {
    $r = wp_parse_args($args);
    
    $r["orderby"] = "post_date";
    $r["order"] = "asc";
    
    return $this->prev($r);
  }

  
  function has_status($status) {
    if (!is_array($status)) {
      $status = explode(",", $status);
    }
    
    return in_array($this->item->post_status, $status);
  }
  
  function status() {
    return $this->item->post_status;
  }
  
  function in_trash() {
    return $this->has_status("trash");
  }
  
  function author() {
    global $wf;
    return $wf->wrap_user( get_userdata( $this->item->post_author ) );
  }
  
  function ancestors() {
    global $wf;
    
    $this->switch_site();
    
  	$ancestors = array();
    
    if ($this->hierarchical()) {
      foreach (get_ancestors($this->id(), $this->type()->name) as $ancestor) {
        $ancestors[] = $wf->post($ancestor);
      }
	  }
    
    $ret = new WOOF_Collection($ancestors);

    $this->restore_site();
    
	  return $ret;
  }
  
  public function parent() {
    
    $this->switch_site();
    
    global $wf;

    if ($this->hierarchical()) {
  
      if ($this->item->post_parent != 0) {
        $ret = $wf->post($this->item->post_parent);
      } else {
				// behaviour change - this function used to return the same post, but now returns a silent post
				$ret = new WOOF_Silent( __( "This post has no parent", WOOF_DOMAIN ) );
      }
      
    } else { 
			$ret = new WOOF_Silent( sprintf( __( "The post has no parent, as the post type %s it is based on is not hierarchical", WOOF_DOMAIN ), $this->type_name() ) );
    }
    
    $this->restore_site();
    
    return $ret;
  
	}
  
  public function has_parent() {
    
    if ($this->hierarchical() && $this->item->post_parent != 0) {
      return $this->parent();
    }
    
    return false;
  }
  
  public function is_child_of($post) {
    
    $this->switch_site();
    
    $ret = false;
    
    global $wf;
    
    if ($this->hierarchical()) {
      
      $p = $wf->post($post);
      
      if ($p->exists()) {
        $ids = $this->ancestors()->extract("id");
        $ret = in_array($p->id(), $ids);
      }

    }
    
    $this->restore_site();
    
    return $ret;
  }

  function default_template() {
    
    global $wf;
    
    $slug = $this->slug;
    
    $type_name = $this->type_name();    
    
    if ($type_name == "page") {

      $ret = "page.php";
      $file = $wf->theme_file("page-".$slug.".php");

    } else if ($type_name == "post") {
      
      $ret = "single.php";
      $file = $wf->theme_file("single-post.php");
      
    } else {

      $ret = "single-".$type_name.".php";
      $file = $wf->theme_file("single-".$type_name."-".$slug.".php");

    }

    if (isset($file) && $file->exists()) {
      $ret = $file->basename();
    }
    
    return $ret;
    
  }

  function template_name() {
    
    $template_file = $this->template();
    
    if (isset($template_file) && $template_file != "") {
      
      $templates = array_flip( get_page_templates() );

      if (isset($templates[$template_file])) {
        return $templates[$template_file];
      }
    }
    
    return "";
  }
  
  function template() {
      
    $ret = "";
    
    $this->switch_site();
    
    global $wf;
    
    $template = $wf->template_for_post($this->id());

    if ($template && $template != "default") {
      $file = $wf->theme_file($template);
      $ret = $template;
    } else {
      // look for the file "page-[slug].php" as per the WordPress template hierarchy.
      
      $slug = $this->slug;
      
      $file = $wf->theme_file("page-".$slug.".php");
       
      if ($file->exists()) {
        $ret = $file->basename();
      }
    }

    $this->restore_site();
    
    return $ret;
    
  }
  
  function rep($class, $arguments = null) {
    
    if (!isset($this->_reps[$class])) {
      
      if (class_exists($class)) {

        if (is_null($arguments)) {
          $arguments = array($this->id());
        }
      
        $reflection_class = new ReflectionClass($class);
        $this->_reps[$class] = $reflection_class->newInstanceArgs($arguments);
      } else {
        $this->_reps[$class] = new WOOF_Silent( sprintf( __("There is no class by the name of %s", $class) ) );
      }
      
    }
    
    return $this->_reps[$class];
    
  }
  
  function top() {
    
	  if ($this->hierarchical()) {
  	  $ancestors = $this->ancestors();
  
  	  if ($ancestors->count() > 0) {
  	    $ret = $ancestors->last();
	    } else {
	      $ret = $this; // regard this page as the top, for silent failure
      }
    
	  } else {
	    $ret = $this;
    }
	   
	  return $ret;
  }
  
  
  function info() {
      
    $a = array();
    
    $a["title"] = $this->title;
    $a["content"] = $this->content;
    $a["type"] = $this->type_name();
    $a["date"] = $this->date("c");
    $a["slug"] = $this->slug;
    $a["permalink"] = $this->permalink;
    $a["author"] = $this->author->email;
    
    if ($this->has_featured_image()) {
      $a["feature_image"] = $this->featured_image->permalink();
    }
    
    return $a;
      
  }
    
    
  public function __set($name, $value) {
    
    // don't allow the ID to be set
    
    if ($name != "ID") {

      $nn = $name; // normalized name
    
      if (isset(self::$aliases[$name])) {
        // accept some better names that remove the redundant "post_" prefix, or allow "slug" in place of "post_name"
        $nn = self::$aliases[$name];
      }
    
      $this->item->$nn = $value;
    
    }

  }
  
  public function assign($values) {
    foreach ($values as $key => $value) {
      $this->{$key} = $value;
    }
  }
  
  public function add_term($id, $taxonomy = null) {

    $this->switch_site();

    global $wf;
    $term = $wf->term($id, $taxonomy);
    
    if ($term->exists()) {
      wp_set_object_terms( $this->id(), (int) $term->id(), $term->taxonomy_name(), true );
    }

    $this->restore_site();
    
  }
  
  public function clear_terms($taxonomy = NULL) {
    
    $this->switch_site();
    
    if (!isset($taxonomy)) {
      
      foreach ($this->type->taxonomies as $tax) {
        wp_set_object_terms( $this->id, NULL, $tax->name );
      } 
      
    } else {
      wp_set_object_terms( $this->id, NULL, $taxonomy );
    }
    
    $this->restore_site();
    
  }
  
  public function remove_term($id, $taxonomy = NULL) {
    
    $this->switch_site();
    
    global $wf;
    
    $to_remove = $wf->term($id, $taxonomy);
    
    if ($to_remove->exists()) {

      $taxonomy_name = $to_remove->taxonomy_name();
      
      $terms = $this->terms( $taxonomy_name );

      $terms_to_retain = array();
      
      $found = false;
      
      foreach ($terms as $term) {
        if ($term->id == $to_remove->id()) {
          $found = true;
        } else {
          $terms_to_retain[] = (int) $term->id();
        }
      }
      
      wp_set_object_terms( $this->id(), $terms_to_retain, $taxonomy_name, false );

    }
    
    $this->restore_site();
    
  }
  
  function delete() {
    if (!$this->_is_new) {
      $this->switch_site();
      wp_delete_post($this->id());
      $this->restore_site();
    }
  }
  
  function publish() {
    $this->switch_site();
    wp_publish_post($this->id());
    $this->restore_site();
  }
  
  function update($strip = true) {

    $this->switch_site();

    // inserts or updates the post
    
    if ($strip) {
      $this->item->post_title = wp_strip_all_tags($this->item->post_title);
      $this->item->post_type = wp_strip_all_tags($this->item->post_type);
    }
      
    if (!$this->_is_new) { // this post exists already

      $result = wp_update_post($this->item, true);
      
      if (is_wp_error($result)) {
        $this->error = $result;
      }
      
    } else {

      unset($this->item->ID);
      
      $result = wp_insert_post($this->item, true);
      
      if (!is_wp_error($result)) {
        $this->_is_new = false;
        $this->item->ID = $result;
      } else {
        $this->error = $result;
      }
    }

    $this->restore_site();

    return $this;

  }
  
  
	public function __toString() {
		return $this->link();	
	}
	

  // implement magic methods to get related terms
  
  public function __call($name, $arguments = array()) {
    
    global $wf;
    
    // try to find a taxonomy whose plural name is this $name
    
    $singular = WOOF_Inflector::singularize($name);
    
    $tax = $wf->taxonomy($singular);
    
    if ($tax->exists()) {
      return $this->terms($singular);  
    }
    
    return parent::__call($name, $arguments);
    
  }
  
  
  public function __get($name) {
    
    global $wf;
    
    // try to find a taxonomy whose plural name is this $name
    
    $singular = WOOF_Inflector::singularize($name);
    
    $tax = $wf->taxonomy($singular);
    
    if ($tax->exists()) {
      return $this->terms($singular);  
    }
    
    return parent::__get($name);
    
  }
  
	public function process_meta() {
		
	}
	
}


class WOOF_PostCol extends WOOF_Wrap {

  protected $post;
  
  function __construct($post) {
    $this->post = $post;
  }
  
  function author() {
    $author = $this->post->author();
    
    $label = $author->display_name;
    
    $post_type = "post";
    
    if (isset($_GET["post_type"])) {
      $post_type = $_GET["post_type"];
    }
    
    
    return WOOF_HTML::tag("a", array("href" => admin_url("edit.php?post_type=".$post_type."&author=".$author->id."&mp_view=".urlencode( sprintf( __("By '%s'", WOOF_DOMAIN), $label) ))), $label);
    
  }
  
  function terms($taxonomy) {
    
    global $wf;
    
    $label = $wf->taxonomy($taxonomy)->singular_label();
    
    $post_type = "post";
    
    if (isset($_GET["post_type"])) {
      $post_type = $_GET["post_type"];
    }
    
    $terms = $this->post->terms($taxonomy);
    
    $ret = array();
    
    foreach ($terms as $term) {
      $ret[] = WOOF_HTML::tag("a", array("href" => admin_url("edit.php?post_type=".$post_type."&".$taxonomy."=".$term->slug()."&mp_view=".urlencode( sprintf( __("With the %s '%s'", WOOF_DOMAIN), $label, $term->name) ))), $term->name);
    }
    
    return implode(", ", $ret);
    
  }
	
}

