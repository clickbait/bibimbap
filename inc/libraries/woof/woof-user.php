<?php

  
  
class WOOF_User extends WOOF_Wrap {

  protected $wp_user;
  protected $roles;
  
  
  protected function fetch_info() {
    if (!isset($this->wp_user)) {
      $this->wp_user = get_userdata($this->item->ID);
    }
  }

  protected function fetch_wp_user() {
    if (!isset($this->wp_user)) {
      $this->wp_user = new WP_User( $this->item->ID );
    }
  }

  public function info($name = null) {

    $this->fetch_info();

    if ($name) {
      return $this->wp_user->{$name};  
    }

    return $this->wp_user;
  }
  
  public function id() {
    return $this->item->ID;
  }
  
  public function sid() {
    return $this->id();
  }
  
  public function can($capability) {
    return user_can($this->ID, $capability);
  }
  
  public function has_role($id) {
    return in_array($id, $this->roles()->extract("name")); 
  }

  public function is($id) {
    $user = $wf->user($id);
    return $this->id() == $user->id();
  }
  
  public function is_current() {
    global $wf;
    
    $current = $wf->the_user();
    
    if ($current->exists()) {
      return $this->id() == $current->id();
    }
    
    return false;
  }
  
  public function is_an($role) {
    return in_array($role, $this->roles()->extract("name"));
  }

  public function is_a($role) {
    return in_array($role, $this->roles()->extract("name"));
  }

  public function role() {
    $roles = $this->roles();
    return $roles[0];
  }

  public function role_name() {
    return $this->role()->name;
  }
  
  
  public function roles() {
    global $wf;
    
    if (!isset($this->roles)) {
      $this->fetch_wp_user();
      
      if ( !empty( $this->wp_user->roles ) && is_array( $this->wp_user->roles ) ) {
        $ro = array();
        
        foreach ($this->wp_user->roles as $role_name) {
          $ro[] = $wf->role($role_name);
        }
        
        $this->roles = new WOOF_Collection($ro);
      } else {
        $this->roles = new WOOF_Collection(array());
      }
    
    }

    return $this->roles;

  }
  
  public function posts($args = array()) {
    
    global $wf;
    
    $r = wp_parse_args( $args,
      array(
        'post_type' => 'any',
        'posts_per_page' => -1
      )
    );

    // single author= match does not work for some bizarre reason

    $r['author__in'] = array($this->id());
    
    $posts = $wf->posts($r);
    
    return $posts;

  }
  
  public function query_posts($args = array()) {
    $r = wp_parse_args($args);
    $r["query"] = "1";
    return $this->posts($r);
  }
  
  public function url($root_relative = true) {
    $url = get_author_posts_url($this->id());
    
    if ($root_relative) {
      return WOOF::root_relative_url($url);
    }

    return $url;
    
  }

  public function avatar($size = 96) {
    
    global $wf;
    
    $avatar = get_avatar( $this->id(), $size );

    // get the URL from the image
    
    if (preg_match("/src\=(?:\"|\')([^\'\"]*)(?:\"|\')/", $avatar, $matches)) {
    
      if (isset($matches[1]) && $matches[1] != "") {
        return $wf->image_from_url($matches[1], "gravatar-".md5($matches[1]), null, "png");
      }
    
    }
    
    return new WOOF_Silent(__("No avatar found", WOOF_DOMAIN));
    
  }

  public function permalink() {
    return $this->url(false);
  }
  
  public function mailto($args = array()) {
    
    $email = $this->email();
    
    if ($email && $email != "") {
      
      $defaults = array(
        'text' => $email
      );
    
      $r = wp_parse_args( $args, $defaults );
    
      
      $tag = '<a href="mailto:'.$email;
      
      // TODO - add spam obfuscation and subject,cc,bcc etc
      
      $tag .= '"';
    
      foreach ($r as $key => $value) {
        if ($key != "text" && $key != "href") {
          $tag .= ' '.$key.'="'.esc_attr($value).'"';
        }
      }
    
      $tag .= '>'.$r['text'].'</a>';

      return $tag;
    
    }
    
  }
  
  function link($args = array()) {

    
    $defaults = array(
      'text' => $this->full_name(),
      'root_relative' => true,
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
  
  
  public function email() {
    return $this->info("user_email");
  }
    
  public function first_name() {
    return $this->info("first_name");
  }

  public function last_name() {
    return $this->info("last_name");
  }
  
  public function title() {
    return $this->full_name();
  }
  
  public function full_name() {
    $fn = $this->first_name();
    $ln = $this->last_name();
    
    if (trim($fn) == "" && trim($ln) == "") {
      // fallback to nickname if there are no first name and last name fields
      return $this->nickname();
    }
    
    return $fn." ".$ln;
  }
  
  public function fullname() {
    return $this->full_name();
  }
  
  public function nickname() {
    return $this->info("nickname");
  }

  public function nick_name() {
    return $this->info("nickname");
  }

  public function user_name() {
    return $this->info("login");
  }

  public function registered($format = NULL) {
    global $wf;
    
    $date = $this->info("user_registered");
    
    if ($format) {
      return $wf->date_format($format, strtotime($date));
    } else {
      return strtotime($date);
    }
    
  }

  public function login() {
    return $this->info("user_login");
  }
  
  public function name() {
    return $this->info("user_login");
  }
  
  public function description() {
    return $this->info("description");
  }
  
  public function __toString() {
		return $this->link();
	}	

}
