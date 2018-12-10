<?php

class WOOF_Attachment extends WOOF_Post {
  
  public $file;
  
  public function __construct($item) {
    
    global $wf;
    
    parent::__construct($item);
    
    $id = $this->id();
    
    $file_url = wp_get_attachment_url( $id );
    $file_path = WOOF_File::infer_content_path($file_url);

    if (wp_attachment_is_image( $id ) ) {
      $c = $wf->get_image_class();
      $this->file = new $c( $file_path, $file_url );
    } else {
      $c = $wf->get_file_class();
      $this->file = new $c( $file_path, $file_url );
    }
    
  }
  
  public function is_image() {
    return wp_attachment_is_image( $this->id() );
  }
  
  function link($args = array()) {
    // get an <a> tag linking to this attachment.
    
    $defaults = array(
      'text' => $this->title(),
      'root_relative' => false
    );
    
    $r = wp_parse_args( $args, $defaults );
    
    $root_relative = WOOF::is_true_arg($r, "root_relative");
    $tag = '<a href="'.$this->url($root_relative).'"';

    foreach ($r as $key => $value) {
      if ($key != "text" && $key != "href" && $key != "root_relative") {
        $tag .= ' '.$key.'="'.esc_attr($value).'"';
      }
    }
    
    $tag .= '>'.$r['text'].'</a>';

    return $tag;
  }

  function file() {
    return $this->file;
  }
  
  function get_delegate() {
    return $this->file;
  }

}
