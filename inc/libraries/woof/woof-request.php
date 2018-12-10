<?php


class WOOF_Request extends WOOF_Wrap {
  
  protected $data;
  protected $args;
  protected $url;

  public function __construct($url, $args = array(), $data = array()) {
    
    global $wf;
    
    $this->data = wp_parse_args( $data );
    $this->url = $url;
    
    if (!preg_match("/^http(s?):\/\//", $url)) {
      $url = "http://" . $url;
    } 

    $this->url = $url;
    
    $r = $this->args = wp_parse_args( 
      $args,
      array(
        "method" => "GET",
        "cache" => false,
        "cache_invalid" => false
      )
    );
    
  }
  
  public function send($nocache = false) {
    
    global $wf;
    
    $data = $this->data;
    $r = $this->args;
    $url = $this->url;
    $cache = $r["cache"];
    $cache_invalid = $r["cache_invalid"];
    
    $d = wp_parse_args($data);
    
    if ($nocache) {
      
      $do_request = true;
      
    } else {
    
      $do_request = WOOF::is_false_arg($r, "cache");
    
      $cache_key = $this->cache_key();
    
    
      if (WOOF::is_true_arg($r, "cache")) {
        // check the cache
        $this->item = $wf->cache($cache_key, null, 0, true);
      
        if (is_null($this->item) || false === $this->item) {
          $do_request = true;
        }

      } else {
        
        $wf->uncache($cache_key);
        
      }
    
    }
    
    if ($do_request) {
      
      unset($r["cache"], $r["cache_invalid"]);
      
      if ($r["method"] == "GET") {
        
        $data_qs = http_build_query($data);
        
        if (preg_match("/\?/", $url)) {
          $url = trim($url, "&") . $data_qs;
        } else {
          $url = $url . "?" . $data_qs;
        }

        $this->item = wp_remote_get( $url );
      
      } else {
        
        if (!isset($r["body"])) {
          $r["body"] = $data;
        }
        
        $this->item = wp_remote_post( $url, $r );
     
      }
     
      if ( is_wp_error( $this->item ) ) {
        
        $this->item = new WOOF_Silent( $this->item->get_error_message() );
        
      } else {
        if ($cache_invalid || ( isset($this->item["response"]["code"]) && $this->item["response"]["code"] == 200)) {
          if ($cache && !$nocache) {
            $wf->cache($cache_key, $this->item, $cache, true);
          }
        }
      
      }
    
             
    }
    
  }
  
  public function cache_key() {
    $r = $this->args;
    unset($r["cache"]);
    
    return "wf_" . $r["method"]. "_" . md5($this->url . serialize($r) . serialize( $this->data ));
  }
  
  public function uncache() {
    global $wf;
    $wf->uncache( $this->cache_key() );
  }
  
  public function response() {
    return $this->item;
  }

  public function valid() {
    return $this->code() == 200;
  }
  
  public function code() {
    return $this->item["response"]["code"];
  }
  
  public function debug_data() {
    return $this->item;
  }
  
  public function __toString() {
    return $this->body();
  }

  public function body() {
    if (isset($this->item["body"])) {
      return $this->item["body"];
    }
    
    return "";
  }
  
  public function json($assoc = false) {
    return json_decode( $this->body(), $assoc );
  }
  
}
