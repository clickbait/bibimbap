<?php


class WOOF_Expression extends WOOF_Wrap {
 
  protected $object;
  protected $expr;
  protected $ret;
  
  function __construct($object, $expr, $ret = "string") {
    $this->object = $object;
    $this->expr = $expr;
    $this->ret = $ret;
    
  }

  function val() {
    return self::eval_expression($this->object, $this->expr, $this->ret);
  }
  
  function evaluate() {
    return self::eval_expression($this->object, $this->expr, $this->ret);
  }
  
  public static function eval_expression($object, $expression, $ret = "string") {

    $parsed = $expression;

    if (preg_match_all("/\{\{(.+?)\}\}/", $expression, $matches, PREG_SET_ORDER)) { 

      foreach ($matches as $match) {
        $parsed = str_replace($match[0], self::eval_token($object, $match[1]), $parsed);
      }
    
    } else {
      // assume there are no braces, so evaluate as a token
      if ($ret == "string") {
        $parsed = (string) self::eval_token( $object, $expression );
      } else if ($ret == "json") {
				
				$obj = self::eval_token( $object, $expression );
				
				if (is_object($obj) && method_exists($obj, "json")) {
					return $obj->json();
				} else {
					return $obj;
				}
				
      } else {
        $parsed = self::eval_token( $object, $expression );
      }
    
    }
    
    return $parsed;
  }

  public static function eval_token($object, $token) {
    
    $parts = explode(".", $token);

    $base = $object;
		
    foreach ($parts as $part) {
    
      // enhanced support for arguments etc
      
      if (preg_match("/([A-Za-z0-9\_]+)\((.+)\)/", $part, $matches)) {

        $args = array();
        
        $targs = json_decode($matches[2], true);
        
        if ($targs) {
          // args are a single associative array
          $args = array( $targs );
        } else if (is_numeric($matches[2])) {
          $args = array( (float) $matches[2] );
        } else { // treat as a string
          $args = array( $matches[2] );
        }
				
        if (is_object($base)) {
          $base = $base->__call($matches[1], $args);
        }

        if (is_woof_silent($base)) {
          $base = $base->__get($matches[1]);
        }
        
        
      } else {
        
    
        if (is_object($base)) {
          
          if ($part == "content") {
            // content exception
            $base = $base->__get($part);
          } else {
            
            if (method_exists($base, $part)) {
              $base = call_user_func(array($base, $part)); 
            } else {
						  $base = $base->__get($part);
            }
          
          }
          
        }

      }

    }

    if (!is_woof_silent($base)) {
      return $base;
    }
    
    return "";
  }
      
  
}