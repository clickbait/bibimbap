<?php

class WOOF_Role extends WOOF_Wrap {
  
  
  public function users() {
    
    global $wf;
      
    $wp_user_search = new WP_User_Query( array("role" => $this->id()) );  
    $results = $wp_user_search->get_results();  
    $users = $wf->wrap_users( $results );
      
    return $users;

  }

  public function id() {
    return $this->item->id;
  }

  public function sid() {
    return $this->id();
  }
  
  public function name() {
    return $this->item->name;
  }

  public function can($cap) {
    return in_array($cap, array_keys( array_filter($this->item->capabilities)));
  }
  
  public function capabilities() {
    return array_filter($this->item->capabilities);
  }
  
  public function __construct($item) {
    $this->item = (object) $item;
  }
  
  public function add_cap($cap) {
    $r = get_role($this->id());
    
    if ($r) {
      $r->add_cap($cap);
    }
  }

  public function remove_cap($cap) {
    $r = get_role($this->id());
    
    if ($r) {
      $r->remove_cap($cap);
    }
  }
  
	public function __toString() {
		return $this->item->name;	
	}

}
