<?php

class WOOF_Comment extends WOOF_Wrap {
  
  
  public function author() {
    global $wf;
    
    if ($this->item->user_id != 0) {
      return new WOOF_CommentUser( $wf->user($this->item->user_id), $this->item->comment_author_IP );
    } else {
      $author = new stdClass();
      $author->email = $this->item->comment_author_email;
      $author->name = $this->item->comment_author;
      $author->url = $this->item->comment_author_url;
      $author->ip = $this->item->comment_author_IP;
      
      return new WOOF_CommentAuthor( new stdClass(  ) );
    }
  }

  public function date($format = NULL) {
    global $wf;
    
    if ($format) {
      return $wf->date_format($format, strtotime($this->item->comment_date));
    } else {
      return strtotime($this->item->comment_date);
    }
  }
  
  public function text( $args ) {
    
    $r = wp_parse_args(
      $args, 
      array("raw" => false)
    );
    
    if ($r["raw"]) {
      return $this->item->comment_content;
    }
    
    return get_comment_text( $this->item->comment_ID );
  }

  public function content( $args = array("raw" => false) ) {
    return $this->text( $args );
  }

  public function id() {
    return $this->item->comment_ID;
  }
  
  public function sid() {
    return $this->site_id() . ":" . $this->id();  
  }

  public function post() {
    global $wf;
    return $wf->post( (int) $this->item->comment_post_ID );
  }

  public function approved() {
    return (bool) $this->item->comment_approved;
  }

  public function agent() {
    return $this->item->comment_agent;
  }

}

class WOOF_CommentAuthor extends WOOF_Wrap {
  
}

class WOOF_CommentUser extends WOOF_User {
  protected $ip;

  public function __construct($item, $ip) {
    parent::__construct($item);
    $this->ip = $ip;
  } 
  
  public function ip() {
    return $this->ip;
  } 
}

