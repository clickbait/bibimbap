<?php

/*

Plugin Name: WOOF
Plugin URI: http://wordpress.org/extend/plugins/woof/
Description: A rich object-oriented developer API to access content and data in your WordPress site.
Author: Traversal
Version: 0.1.2
Author URI: http://traversal.com.au

*/

if (!class_exists("WOOF")) {
  
define("WOOF_SSL", isset($_SERVER["HTTPS"]) && ($_SERVER["HTTPS"] == "on") );

if (!defined("WF_CONTENT_URL")) {
  if (isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"] == "on") {
    define("WF_CONTENT_URL", preg_replace("/http:\/\//", "https://", WP_CONTENT_URL));
  } else {
    define("WF_CONTENT_URL", preg_replace("/https:\/\//", "http://", WP_CONTENT_URL));
  }
}



if (!defined('WOOF_DIR_SEP')) {
	if (strpos(php_uname('s'), 'Win') !== false )
		define('WOOF_DIR_SEP', '\\');
	else 
		define('WOOF_DIR_SEP', '/');
}

$wf_upload_dir = wp_upload_dir();

if ( !defined("WOOF_CONTENT_DIR") ) {
  define("WOOF_CONTENT_DIR", $wf_upload_dir['basedir'] . WOOF_DIR_SEP . 'woof' . WOOF_DIR_SEP); 
}

if ( !defined("WOOF_CONTENT_URL") ) {
  define("WOOF_CONTENT_URL", $wf_upload_dir['baseurl'].'/woof/'); 
}

if ( !defined("WOOF_CONTENT_IMAGE_CACHE_FOLDER") ) {
  define("WOOF_CONTENT_IMAGE_CACHE_FOLDER", "image-cache"); 
  define("WOOF_CONTENT_IMAGE_CACHE_URL", WOOF_CONTENT_URL . WOOF_CONTENT_IMAGE_CACHE_FOLDER.'/'); 
  define("WOOF_CONTENT_IMAGE_CACHE_DIR", WOOF_CONTENT_DIR . WOOF_CONTENT_IMAGE_CACHE_FOLDER.WOOF_DIR_SEP); 
}

if ( !defined("WOOF_CONTENT_IMAGE_FROM_URL_FOLDER") ) {
  define("WOOF_CONTENT_IMAGE_FROM_URL_FOLDER", "image-from-url"); 
  define("WOOF_CONTENT_IMAGE_FROM_URL_URL", WOOF_CONTENT_URL . WOOF_CONTENT_IMAGE_FROM_URL_FOLDER . '/'); 
  define("WOOF_CONTENT_IMAGE_FROM_URL_DIR", WOOF_CONTENT_DIR . WOOF_CONTENT_IMAGE_FROM_URL_FOLDER . WOOF_DIR_SEP); 
}

if ( !defined("WOOF_CONTENT_FILE_FROM_URL_FOLDER") ) {
  define("WOOF_CONTENT_FILE_FROM_URL_FOLDER", "file-from-url"); 
  define("WOOF_CONTENT_FILE_FROM_URL_URL", WOOF_CONTENT_URL . WOOF_CONTENT_FILE_FROM_URL_FOLDER . '/'); 
  define("WOOF_CONTENT_FILE_FROM_URL_DIR", WOOF_CONTENT_DIR . WOOF_CONTENT_FILE_FROM_URL_FOLDER . WOOF_DIR_SEP); 
}

if ( !defined("WOOF_DOMAIN") ) {
  define("WOOF_DOMAIN", "woof");
}

if ( !defined("WOOF_MS_FILES") ) {
  define("WOOF_MS_FILES", get_site_option('ms_files_rewriting') );
}

define("WOOF_DOCS_BASE", "http://masterpressplugin.com/docs/developer/classes/");

/* -- Autoload to prevent the need to load all classes -- */

function woof_dasherize($word) {
  return  strtolower(preg_replace('/[^A-Z^a-z^0-9]+/','-',
  preg_replace('/([a-z\d])([A-Z])/','\1_\2',
  preg_replace('/([A-Z]+)([A-Z][a-z])/','\1_\2',$word))));
}

 
function woof_autoloader($class) {
  $file_name = woof_dasherize($class);
  $path = plugin_dir_path( __FILE__ ) . $file_name . '.php';
  
  if (!file_exists($path)) {
    
    // support lazy shorthand aliases
    
    $aliases = array(
      "wh" => "woof-html"
    );

    if (isset($aliases[$file_name])) {
      $path = plugin_dir_path( __FILE__ ) . $aliases[$file_name] . '.php';
    }
  
  }
  
  if (file_exists($path)) {
    include_once($path);
  }
}

if (function_exists("spl_autoload_register")) {
  
  spl_autoload_register('woof_autoloader');

} else {
  
  // include all necessary files
  
  include(plugin_dir_path(__FILE__)."woof-inflector.php");
  include(plugin_dir_path(__FILE__)."woof-silent.php");
  include(plugin_dir_path(__FILE__)."woof-html.php");
  include(plugin_dir_path(__FILE__)."woof-wrap.php");
  include(plugin_dir_path(__FILE__)."woof-expression.php");
  include(plugin_dir_path(__FILE__)."woof-file.php");
  include(plugin_dir_path(__FILE__)."woof-image.php");
  include(plugin_dir_path(__FILE__)."woof-collection.php");
  include(plugin_dir_path(__FILE__)."woof-post.php");
  include(plugin_dir_path(__FILE__)."woof-post-type.php");
  include(plugin_dir_path(__FILE__)."woof-user.php");
  include(plugin_dir_path(__FILE__)."woof-role.php");
  include(plugin_dir_path(__FILE__)."woof-comment.php");
  include(plugin_dir_path(__FILE__)."woof-site.php");
  include(plugin_dir_path(__FILE__)."woof-term.php");
  include(plugin_dir_path(__FILE__)."woof-taxonomy.php");
  include(plugin_dir_path(__FILE__)."woof-attachment.php");
  include(plugin_dir_path(__FILE__)."woof-request.php");
  
  
}


if (!function_exists("is_silent")) {
  
  function is_silent($obj) {
    if (is_object($obj) && get_class($obj) == "WOOF_Silent") {
      return true;
    }
    
    return false;
  }
  
}

function is_woof_silent($obj) {
  if (is_object($obj) && get_class($obj) == "WOOF_Silent") {
    return true;
  }
  
  return false;
}

function is_woof_collection($obj, $of = "") {
  $is_collection = WOOF::is_or_extends($obj, "WOOF_Collection");
  
  if ($is_collection && $of) {
    return $obj->is_collection($of);
  }
  
  return $is_collection;
  
}


function is_not_woof_silent($obj) {
  return !is_woof_silent($obj);
}


if (!class_exists("WOOF")) {

  
  /* 

  Class: WOOF
    The top-level API object for the WOOF (WordPress Object Oriented Framework) API. 
    
    Note that most non-static operations should be accessed via a pre-instantiated singleton global object $wf 
    which is an instance of WOOF if just using WOOF, or MEOW if you're using MasterPress. MEOW adds methods 
    for the custom field features offered by MasterPress. See <http://masterpressplugin.com/docs/developer/introduction-and-key-concepts/packages>
    
    All examples will be written to assume this class is accessed on the global object $wf

  */
  
  class WOOF extends WOOF_Wrap {
    
    protected $templates_by_post_id;
  
    protected $global_post;
  
    protected static $active_blog;
    
    protected $disabled_filters = array();
    
    // define directories to point to woof-specific directories
    public $content_dir = WOOF_CONTENT_DIR; 
    public $content_url = WOOF_CONTENT_URL;
    
    public $content_image_cache_folder = WOOF_CONTENT_IMAGE_CACHE_FOLDER;
    public $content_image_cache_url = WOOF_CONTENT_IMAGE_CACHE_URL;
    public $content_image_cache_dir = WOOF_CONTENT_IMAGE_CACHE_DIR;

    public $content_image_from_url_folder = WOOF_CONTENT_IMAGE_FROM_URL_FOLDER;
    public $content_image_from_url_url = WOOF_CONTENT_IMAGE_FROM_URL_URL;
    public $content_image_from_url_dir = WOOF_CONTENT_IMAGE_FROM_URL_DIR;

    public $content_file_from_url_folder = WOOF_CONTENT_FILE_FROM_URL_FOLDER;
    public $content_file_from_url_url = WOOF_CONTENT_FILE_FROM_URL_URL;
    public $content_file_from_url_dir = WOOF_CONTENT_FILE_FROM_URL_DIR;
    
    public $woof_docs_base = WOOF_DOCS_BASE;
    
		protected static $posts_args;
		
    protected static $mustache;
    
    public $wp_remote_timeout = 30;

    public $last_sql_query = "";

    public $class_prefix = "MY";
    
    public $filter_post;
    protected $post_cache = array();
    protected $page_cache = array();
    
    protected $_classes = array();
    
    protected $post_class = "WOOF_Post";
    protected $post_type_class = "WOOF_PostType";
    protected $term_class = "WOOF_Term";
    protected $user_class = "WOOF_User";
    protected $role_class = "WOOF_Role";
    protected $comment_class = "WOOF_Comment";
    protected $attachment_class = "WOOF_Attachment";
    protected $taxonomy_class = "WOOF_Taxonomy";
    protected $site_class = "WOOF_Site";
    protected $image_class = "WOOF_Image";
    protected $file_class = "WOOF_File";

    protected $parent;
    protected $ancestors;
    protected $top;
    protected $children;
    protected $children_items;
    protected $template_path;
    protected $taxonomies;
    protected $the_user;
    
    protected $tft;
    
    public $date_map = array(
      
      "date-time-long" => "l, jS F Y @ g:i A",
      
      "date-time-sortable-no-sec" => "YmdHi",
      "date-time-sortable" => "YmdHis",

      'day-0s' =>             'd',	
      '0-day' =>              'd',	
      'day-short' =>          'D',	
      'day' =>                'j',	
      'date' =>               'j',	
      'date-suffix' =>        'jS',
      'day-long' =>           'l', 
      'week' =>               'N',	
      'suffix' =>             'S',	
      'day-of-week' =>        'w',	
      'day-of-year' =>        'z',	
      'week-of-year' =>       'W',	
      'month-long' =>         'F',	
      'month-0s' =>           'm',	
      '0-month' =>            'm',	
      'month-short' =>        'M',	
      'month' =>              'n',	
      'days-in-month' =>      't',	
      'leap' =>               'L',	
      'year-iso' =>           'o',	
      'year' =>               'Y',	
      'year-short' =>         'y',	
      'am-pm' =>              'a',	
      'AM-PM' =>              'A',	
      'ampm' =>               'a',	
      'AMPM' =>               'A',	
      'swatch' =>             'B',	
      'hour' =>               'g',	
      'hour-24' =>            'G',	
      'hour-0s' =>            'h',	
      '0-hour' =>             'h',	
      'hour-24-0s' =>         'H',	
      '0-hour-24' =>          'H',	
      'min' =>                'i',	
      'min-0s' =>             'i',	
      '0-min' =>              'i',	
      'sec' =>                's',	
      'sec-0s' =>             's',	
      '0-sec' =>              's',	
      'micro' =>              'u',	
      'timezone' =>           'e',	
      'daylight-savings' =>   'I', 
      'gmt-offset' =>         'O',	
      'gmt-offset-colon' =>   'P',	
      'timezone-short' =>     'T',	
      'timezone-offset' =>    'Z'	
    );
      
    public $core_capabilities = array(
      'activate_plugins',
  		'add_users',
  		'create_users',
  		'delete_others_pages',
  		'delete_others_posts',
  		'delete_pages',
  		'delete_plugins',
  		'delete_posts',
  		'delete_private_pages',
  		'delete_private_posts',
  		'delete_published_pages',
  		'delete_published_posts',
  		'delete_users',
  		'edit_dashboard',
  		'edit_files',
  		'edit_others_pages',
  		'edit_others_posts',
  		'edit_pages',
  		'edit_plugins',
  		'edit_posts',
  		'edit_private_pages',
  		'edit_private_posts',
  		'edit_published_pages',
  		'edit_published_posts',
  		'edit_theme_options',
  		'edit_themes',
  		'delete_themes',
  		'edit_users',
  		'import',
  		'export',
  		'install_plugins',
  		'install_themes',
  		'list_users',
  		'manage_categories',
  		'manage_links',
  		'manage_options',
  		'moderate_comments',
  		'promote_users',
  		'publish_pages',
  		'publish_posts',
  		'read',
  		'read_private_pages',
  		'read_private_posts',
  		'remove_users',
  		'switch_themes',
  		'unfiltered_html',
  		'unfiltered_upload',
  		'update_core',
  		'update_plugins',
  		'update_themes',
  		'upload_files',
  		'level_0',
  		'level_1',
  		'level_2',
  		'level_3',
  		'level_4',
  		'level_5',
  		'level_6',
  		'level_7',
  		'level_8',
  		'level_9',
  		'level_10'
		);
		
		
		
    public $legacy_capabilities = array(
    
  	);
  	
      
    static $the_id;
    
    
    public function __call($name, $arguments = array()) {
      
      if (count($arguments) <= 1) {
        
        // if we have a single argument, we can allow posts and terms to be accessed like this: 
        // $wf->car("ferrari"), or $wf->ingredient("lime")
           
        // first, try to find a post type with the name of the call, and all the post function
        
        $singular = WOOF_Inflector::singularize($name);
        
        foreach ($this->types() as $type) {
          if ($type->name == $name) {
            return call_user_func_array( array($type, "post"), $arguments );
          } else if ($type->name == $singular) {
            // call multiple object method (e.g. "cars")
            return call_user_func_array( array($type, "posts"), $arguments );
          }
        } 

        // next, try to find a taxonomy with the name, and call the term function 
      
        foreach ($this->taxonomies() as $tax) {
          if ($tax->name == $name) {
            return call_user_func_array( array($tax, "term"), $arguments );
          } else if ($tax->name == $singular) {
            // call multiple object method (e.g. "cars")
            return call_user_func_array( array($tax, "terms"), $arguments );
          }
        }
      
      }
      
      
      // now try to call a method on the current wordpress object
      
      $obj = $this->object();
      
      if (method_exists($obj, $name)) {
        return call_user_func_array( array($obj, $name), $arguments );
      }


      return parent::__call($name, $arguments);

    }
    
    public function __get($name) {

      $type = $this->type($name);
      
      if ($type->exists()) {
        return $type;        
      }
      
      $singular = WOOF_Inflector::singularize($name);
      
      $type = $this->type($singular);
      
      if ($type->exists()) {
        return $type;
      }
      
      // next look for a taxonomy

      $tax = $this->taxonomy($name);
      
      if ($tax->exists()) {
        return $tax;
      }
      
      $tax = $this->taxonomy($singular);
      
      if ($tax->exists()) {
        return $tax;
      }
      
      // now try to call a method on the current wordpress object
      // this way, extension classes can work correctly without needing to call $wf->the.
      
      $obj = $this->object();
      
      if (method_exists($obj, $name)) {
        return call_user_func( array($obj, $name) );
      }
      
      return parent::__get($name);

    }
    
    /**
     * Returns string with newline formatting converted into tag of choice paragraphs.
     * Based on the function nl2p by Michael Tomasello: 
     * http://www.youngcoders.com/share-php-script/26933-nl2p-alternative-nl2br.html
    */
    static function nl2($string, $tag = "p", $line_breaks = false, $xml = true) {
      
      if (trim($string) == "") {
        return "";
      }
      
      // Remove existing HTML formatting to avoid double-wrapping things
      $string = str_replace(array('<'.$tag.'>', '</'.$tag.'>', '<br>', '<br />'), '', $string);
  
      // It is conceivable that people might still want single line-breaks
      // without breaking into a new paragraph.
      if ($line_breaks)
          return '<'.$tag.'>'.preg_replace(array("/([\n]{2,})/i", "/\n/i"), array("</".$tag.">\n<".$tag.">", '<br'.($xml == true ? ' /' : '').'>'), trim($string)).'</'.$tag.'>';
      else 
          return '<'.$tag.'>'.preg_replace("/([\n]{1,})/i", "</".$tag.">\n<".$tag.">", trim($string)).'</'.$tag.'>';
        
    }

    public static function is_or_extends($var, $class) {
      return is_object($var) && ( get_class($var) == $class || is_subclass_of($var, $class) );
    }
       
  
    public static function nl2p($string, $line_breaks = false, $xml = true) {
      return self::nl2($string, "p", $line_breaks, $xml);
    }
    
    public static function pad($str, $chr) {
      return self::lpad(self::rpad($str, $chr), $chr);
    }
    
    public static function lpad($str, $chr) {
      if (substr($str, 0, 1) != $chr) {
        return $chr.$str;
      }
      
      return $str;
    }

    public static function rpad($str, $chr) {
      if (substr($str, -1) != $chr) {
        return $str.$chr;
      }

      return $str;
    }

    public static function capture_sql($request) {
      global $wf;
      
      $wf->last_sql_query = $request;
    }
	
	    
    public static function remap_args($args, $aliases) {
      
      $new_args = array();
      
      foreach ($args as $key => $value) {
        if (isset($aliases[$key])) {
          $new_args[$aliases[$key]] = $value;
        } else {
          $new_args[$key] = $value;
        }
      }
      
      return $new_args;
      
    }

    public static function copy_args(&$src, &$dest, $keys = array()) {
      if (!is_array($keys)) {
        $keys = explode(",", $keys);
      }

      foreach ($keys as $key) {
        if (isset($src[$key])) {
          $dest[$key] = $src[$key];
        }
      }
      
    }
    

    public static function is_false_arg($args, $key) {
      if (!isset($args[$key])) {
        return true;
      } else {
        return $args[$key] === "0" || $args[$key] === "" || $args[$key] === "false" || $args[$key] === 0 || $args[$key] === null || $args[$key] === false;
      }
    }

    public static function is_true_arg($args, $key) {
      $result = WOOF::is_false_arg($args, $key);
      
      if (!$result) {
        return true;
      }
      
      return false;
    }
    
    public static function array_arg($args, $key) {
    
      $r = wp_parse_args($args);
      
      $ret = array();
    
      if (isset($r[$key])) {
        $ret = $r[$key];
      
        if (!is_array($ret)) {
          $ret = explode(",", $ret);
        }
      }
      
      return $ret;
    }
    
    public static function args_empty($args) {
      
      if (is_array($args)) {
        return !count($args);
      } else {
        return trim($args) == "";
      }
      
    }
    
    public static function json_indent($json) {
      
      if (is_array($json)) {
        $json = json_encode($json);
      }
      
      /* Thanks to this post: http://recursive-design.com/blog/2008/03/11/format-json-with-php/ */
      
      $result      = '';
      $pos         = 0;
      $strLen      = strlen($json);
      $indentStr   = '  ';
      $newLine     = "\n";
      $prevChar    = '';
      $outOfQuotes = true;

      for ($i=0; $i<=$strLen; $i++) {

          // Grab the next character in the string.
          $char = substr($json, $i, 1);

          // Are we inside a quoted string?
          if ($char == '"' && $prevChar != '\\') {
              $outOfQuotes = !$outOfQuotes;
        
          // If this character is the end of an element, 
          // output a new line and indent the next line.
          } else if(($char == '}' || $char == ']') && $outOfQuotes) {
              $result .= $newLine;
              $pos --;
              for ($j=0; $j<$pos; $j++) {
                  $result .= $indentStr;
              }
          }
        
          // Add the character to the result string.
          $result .= $char;

          // If the last character was the beginning of an element, 
          // output a new line and indent the next line.
          if (($char == ',' || $char == '{' || $char == '[') && $outOfQuotes) {
              $result .= $newLine;
              if ($char == '{' || $char == '[') {
                  $pos ++;
              }
            
              for ($j = 0; $j < $pos; $j++) {
                  $result .= $indentStr;
              }
          }
        
          $prevChar = $char;
      }

      return $result;
  
    }
    
    public static function eval_expression($expression, $object, $ret = "string") {
      return WOOF_Expression::eval_expression($object, $expression, $ret);
    }

    public static function eval_token($token, $object) {
      return WOOF_Expression::eval_token($object, $token);
    }
    
    public static function incl_phpquery() {
      include_once(plugin_dir_path(__FILE__)."phpquery.php");
    }


    public function disable_filter($name, $function) {
      global $wp_filter;
      
      $filters = array();
      $after = array();
      
      $priority = has_filter($name, $function);
      
      if ($priority) {

        $same_priority = $wp_filter[$name][$priority];
        
        $index = array_search( $function, array_keys( $same_priority ) );
         
        // find the fitlers after wpautop, and ensure they are all added again later
        $after = array_slice(  $same_priority, $index + 1 );
        
        $this->disabled_filters[$name."_".serialize($function)] = array(
          "item" => array("key" => $name, "value" => $same_priority[$function], "priority" => $priority ),
          "after" => $after
        );
        
        remove_filter( $name, $function );

        return true;
        
      }
        
      return false;
      
    }

    public function enable_filter($name, $function) {
      
      global $wp_filter;
          
      // lookup our disabled filters
      
      $key = $name."_".serialize($function);
      
      if (isset($this->disabled_filters[$key])) {
        
        $info = $this->disabled_filters[$key];
        $item = $info["item"];
        $pri = $item["priority"];
        
        if (count($info["after"])) {
          // remove these filters first
          
          $add_after = array();
          
          foreach ($info["after"] as $after_info) {
            // check if it's still active

              if ($priority = has_filter($name, $after_info["function"])) {

                if ($priority == $pri) {

                  remove_filter( $name, $after_info["function"], $pri, $after_info["accepted_args"] );
                  $add_after[] = $after_info;

                }
              
              }

          }
          
          // re-add the filter
          add_filter( $name, $function, $pri, $item["value"]["accepted_args"] );
          
          // re-add the after filters
          foreach ($add_after as $after_info) {
            add_filter( $name, $after_info["function"], $pri, $after_info["accepted_args"] );
          }
          
        }
        
        unset( $this->disabled_filters[$key] );
        
        return true;
      }
      
      return false;
      
    }

    public function expr($object, $expr, $ret = "string") {
      return new WOOF_Expression($object, $expr, $ret);
    }

    public function markdown($text) {
      if (!function_exists("Markdown")) {
        include_once(plugin_dir_path(__FILE__)."php-markdown-extra.php");
      }
    
      return Markdown($text);
    }
  
    public function json_uncache($url) {
      $this->uncache("json_".md5($url));
    }
    
    public function remote_get($url, $args = array(), $data = array()) {
      $r = wp_parse_args( $args );
      $r["method"] = "GET";
      
      $req = new WOOF_Request($url, $r, $data);
      $req->send();
      return $req;
    }
    
    public function remote_post($url, $args = array(), $data = array()) {
      $r = wp_parse_args( $args );
      $r["method"] = "POST";

      $req = new WOOF_Request($url, $r, $data);
      $req->send();
      return $req;
    }
    
    public function json_get($url, $assoc = false, $cache = false, &$req = null) {
      
      $do_request = !$cache;
      
      $cache_key = "json_".md5($url);
      
      $json = false;
      
      if ($cache) {
        // check the cache
        $json_str = $this->cache($cache_key);
        $json = json_decode($json_str, $assoc);

        if (!$json_str) {
          $do_request = true;
        }
      }
      
      if ($do_request) {
        
        $get = wp_remote_get( $url );
        
        $req = $get;
        
        if (isset($req["response"]["code"]) && $req["response"]["code"] == 200) {
          $json_str = $req["body"];
          
          $json = json_decode($json_str, $assoc);
          
          if ($json && $cache) {
            $this->cache($cache_key, $json_str, $cache);
          }
        }
               
      }
      
      return $json;

    }
      
    public static function render_template($tmpl, $data, $strip_whitespace = false) {
      if (!isset(self::$mustache)) {
        require(plugin_dir_path(__FILE__)."mustache.php");
        self::$mustache = new Mustache();
      }
    
      $tmpl = self::$mustache->render( $tmpl, $data );
      
      if ($strip_whitespace) {
        $tmpl = WOOF_HTML::strip_whitespace( $tmpl );
      }
    
      return $tmpl;
    }

    public function wp_content_dir($main = false) {
      global $blog_id;

      if ($main) {
        return WP_CONTENT_DIR;
      } else if (is_multisite()) {
	
				if (WOOF_MS_FILES) {

        	return WP_CONTENT_DIR . WOOF_DIR_SEP . "blogs.dir" . WOOF_DIR_SEP . $blog_id;

				} else {

				  if (is_main_site()) {
				    return WP_CONTENT_DIR;
				  } else {
        	  return WP_CONTENT_DIR . WOOF_DIR_SEP . "uploads" . WOOF_DIR_SEP . "sites" . WOOF_DIR_SEP . $blog_id;
          }

				}
				
      } 
      
      return WP_CONTENT_DIR;
    }
    
    public function wp_content_url($main = false) {
      global $blog_id;
      
      if ($main) {
        return WF_CONTENT_URL;
      } else if (is_multisite()) {

				if (WOOF_MS_FILES) {
          return WF_CONTENT_URL . "/blogs.dir/" . $blog_id;
				} else {
				  if (is_main_site()) {
				    return WF_CONTENT_URL;
				  } else {
        	  return WF_CONTENT_URL . "/uploads/sites/" . $blog_id;
          }
				}

      } 
      
      return WF_CONTENT_URL;
    }
    
    public static function items_number($count, $zero, $one, $many) {
      
      if ($count == 0) {
        return sprintf( $zero, $count );
      }
    
      if ($count == 1) {
        return sprintf( $one, $count );
      } 
    
      return sprintf( $many, $count );
    
    }


    public static function array_remove_keys(&$array, $keys = array()) {
      if (!is_array($keys)) {
        $keys = explode(",", $keys);
      }
      
      foreach ($keys as $key) {
        if (isset($array[$key])) {
          unset($array[$key]);
        }
      }
    
    }
  
    public static function array_remove($array, $value, $strict = false) {
      if (is_array($array)) {
        return array_diff_key($array, array_flip(array_keys($array, $value, $strict)));
      }
      
      return $array;
    }
  
    public static function parse_term_composite($id) {
      if (preg_match("/(.+)\:(\d+)/", $id, $matches)) {
        return array($matches[1], $matches[2]);
      }
      
      return array("*", $id);
      
    }
    
    public function shortcode_id($attr) {
      
      global $wf;

      $html = "";

      if (count($attr) == 1) {
        if (isset($attr["id"])) {
          $id = $attr["id"];
        } else {
          $id = $attr[0];
        }
  
      } 
        
      if (is_numeric($id)) {
        return $id;
      }
      
      // fallback to 1
      return 1;
      
    }
    
    public static function truncate_basic($text, $max_length, $trailing = "&hellip;") {
    
      if (strlen($text) > $max_length) {
        $text = substr($text, 0, $max_length).$trailing;
      }
    
      return $text;
    }
  
    public static function truncate($str, $args = array()) {
      
      $r = wp_parse_args( 
        $args,
        array(
          "length" => 80,
          "etc" => "&hellip;",
          "words" => 1,
          "middle" => 0
        )
      );
      
      return self::truncate_advanced($str, $r["length"], $r["etc"], !$r["words"], $r["middle"]);
    }
    
    public static function truncate_advanced($string, $length = 80, $etc = '...', $break_words = false, $middle = false) {
      
      if (!is_string($string)) {
        return '';
      }
      
      if ($length == 0)
        return '';

      if (preg_match("/&[a-z];/", $etc)) {
        $etc_len = 1;
      } else {
        $etc_len = strlen($etc);
      }
      
      if (is_callable('mb_strlen')) {
          if (mb_detect_encoding($string, 'UTF-8') === 'UTF-8') {
              $charset='UTF-8';
              // $string has utf-8 encoding
              if (mb_strlen($string, $charset) > $length) {
                  $length -= min($length, mb_strlen($etc_len, $charset));
                  if (!$break_words && !$middle) {
                      $string = preg_replace('/\s+?(\S+)?$/u', '', mb_substr($string, 0, $length + 1, $charset));
                  }
                  if (!$middle) {
                      return mb_substr($string, 0, $length, $charset) . $etc;
                  } else {
                      return mb_substr($string, 0, ceil($length / 2), $charset) . $etc . mb_substr($string, - ceil($length / 2), ceil($length / 2), $charset);
                  }
              } else {
                  return $string;
              }
          }
      }
      
      // $string has no utf-8 encoding

      if (strlen($string) > $length) {
          $length -= min($length, $etc_len);
          if (!$break_words && !$middle) {
              $string = preg_replace('/\s+?(\S+)?$/', '', substr($string, 0, $length + 1));
          }
          if (!$middle) {
              return substr($string, 0, $length) . $etc;
          } else {
              return substr($string, 0, $length / 2) . $etc . substr($string, - $length / 2);
          }
      } else {
          return $string;
      }
      
    }
  
    // From: http://snipplr.com/view.php?codeview&id=4621 (THANKS!)
  
    public static function hex_to_rgb($hex) {
  		$hex = preg_replace("/#/", "", $hex);
  		$color = array();
		
  		if(strlen($hex) == 3) {
  		  $r = substr($hex, 0, 1);
  		  $g = substr($hex, 1, 1);
  		  $b = substr($hex, 2, 1);
  			$color = array( hexdec($r.$r), hexdec($g.$g), hexdec($b.$b) );
  		}
  		else if(strlen($hex) == 6) {
  			$color = array( hexdec(substr($hex, 0, 2)), hexdec(substr($hex, 2, 2)), hexdec(substr($hex, 4, 2)));
  		}
		
  		return $color;
  	}
    
  	public static function rgb_to_hex($r, $g, $b) {
  		//String padding bug found and the solution put forth by Pete Williams (http://snipplr.com/users/PeteW)
  		$hex = "#";
  		$hex.= str_pad(dechex($r), 2, "0", STR_PAD_LEFT);
  		$hex.= str_pad(dechex($g), 2, "0", STR_PAD_LEFT);
  		$hex.= str_pad(dechex($b), 2, "0", STR_PAD_LEFT);
		
  		return $hex;
  	}

	  public static function echoif($condition, $val) {
	    if (isset($condition) && $condition) {
	      echo $val;
      }
    }
      
    public static function is_assoc($arr) {
      return !(self::is_indexed_array($arr) && self::is_sequential_array($arr));
    }

    public static function is_indexed_array(&$arr) {
      for (reset($arr); is_int(key($arr)); next($arr));
      return is_null(key($arr));
    }

    public static function is_sequential_array(&$arr, $base = 0) {
      for (reset($arr), $base = (int) $base; key($arr) === $base++; next($arr));
      return is_null(key($arr));
    }
    

    public static function array_dim($array) {
      if (is_array(reset($array)))  
        $return = self::array_dim(reset($array)) + 1;
      else
        $return = 1;

      return $return;
    }    


    public static function parse_query($query) {

      global $wf;
    
      $r = &$query->query_vars;
        
      if (isset($r["has"])) {

        // custom param
        
        $keys = explode(",", $r["has"]);
        
        if (count($keys) > 1) {
          $r['meta_query'] = array();
          
          foreach ($keys as $key) {
            $r['meta_query'][] = array(
              'key' => $key,
        			'value' => array(''),
        			'compare' => 'NOT IN'
            );
          }

	      } else {
          $r['meta_key'] = $r["has"];
          $r['meta_value'] = array(''); // should be able to use equals, but this doesn't work as an empty value is not regarded at all
          $r['meta_compare'] = 'NOT IN';		
        }
      
        unset($r["has"]);

      }
    
    }
    
    static function root_relative_url($input) {
      return preg_replace('!http(s)?://' . $_SERVER['SERVER_NAME'] . '/!', '/', $input);
    } 


    static function domain_of_url($url) {
      if (preg_match('/http(?:s)?\:\/\/([^\/]+)/', $url, $matches)) {
        return $matches[1];
      }
      
      return "";
    } 
    
    static function remember() {
      global $wp_query, $blog_id, $wf;
      
      self::$active_blog = $blog_id;
      
      if (isset($wp_query) && isset($wp_query->post)) {
        $id = $wp_query->post->ID;
        
        // allows load_template to auto-globalize $wf
        $wp_query->query_vars["wf"] = $wf;


        if (is_null($id)) {
          if (isset($_GET["post"])) {
            self::$the_id = (int) $_GET["post"];
          }
        } else {
          self::$the_id = $id;
        }
      }
    
    }

    public function id_for_slug($slug, $type = "post") {
      global $wpdb;
      $id = $wpdb->get_var( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE post_name = %s AND post_type='$type'", $slug ));
      
      return $id;
    }


    public function protect() {
      global $post;
      $this->global_post = $post;
    }

    public function release() {
      global $post;
      
      if (isset($this->global_post)) {
        $post = $this->global_post;
      }
      
    }

    public function restore() {
      if (is_multisite()) {
        switch_to_blog(self::$active_blog);
      }
    }

    public function taxonomies($args = array(), $operator = "and") {
      return $this->wrap_taxonomies( get_taxonomies(wp_parse_args($args), "objects", $operator ) );
    }

    public function collection($items = array()) {
      return new WOOF_Collection($items);
    }
    
    public function taxonomy($name = null) {
      if (is_null($name)) {
        return $this->the_taxonomy();
      }
      
      if (WOOF::is_or_extends($name, "WOOF_Taxonomy")) {
        return $name;
      }
      
      $tax = get_taxonomy($name);

      if ($tax) {
        
        $tc = $this->get_taxonomy_class();
        
        return new $tc($tax);
      }
      
      return new WOOF_Silent( sprintf( __( "Taxonomy %s does not exist", WOOF_DOMAIN ), $name ) );
    }
    
    
    public function loop() {
      global $posts, $wp_query, $query_string;
      return $this->wrap($wp_query->posts);
    }
       
    

    public function pargs($args = array()) {
      
      global $paged;
      
      $defaults = array("post_type" => "any", "posts_per_page" => "-1");
      
      $r = wp_parse_args( $args, $defaults );

      if ($r["posts_per_page"]) {
        $r["numberposts"] = $r["posts_per_page"];
      }
      
      return $r;
    }
    
    
  
    
    public function posts($args = array()) {
      
      global $wp_query;
      
      $r = $this->pargs($args);
      
      $reset = false;
      
      $ret = array();
    
      if (isset($r["sql"])) {
        $r["suppress_filters"] = false;
        add_filter( 'posts_request', array("WOOF", "capture_sql") );
      }

      if (isset($r["args"])) {
        return $r;
      }
      
      if (isset($r["query"]) && (bool) $r["query"] ) {
        
        if (isset($r["posts_per_page"])) {
          global $paged;

          if (isset($paged)) {
            $r['paged'] = $paged;
          }
        }
        
        if (isset($r["reset"])) {
          $reset = (bool) $r["reset"];
          unset($r["reset"]);
        }
        
        unset($r["query"]);
        query_posts($r);

        $ret = $this->loop();

        if ($reset) {
          wp_reset_query();
        }
  
        if (isset($r["sql"])) {
          remove_filter( 'posts_request', array("WOOF", "capture_sql"));
          return $this->last_sql_query;
        }
    
        return $ret;
        
      } else {

				self::$posts_args = $r;
				
				$collect_meta = isset($r["include"]);
				 	
				if ( $collect_meta ) {
					add_filter('posts_fields', array( 'WOOF', '_posts_fields' ) );
					add_filter('posts_join', array( 'WOOF', '_posts_join' ) );
					add_filter('posts_where', array( 'WOOF', '_posts_where' ) );
					add_filter('posts_orderby', array( 'WOOF', '_posts_orderby' ) );
				}

        $q = new WP_Query($r);
				
				self::$posts_args = null;
				
        $res = $q->posts;

				if ( $collect_meta ) {
					remove_filter('posts_fields', array( 'WOOF', '_posts_fields' ) );
					remove_filter('posts_join', array( 'WOOF', '_posts_join' ) );
					remove_filter('posts_where', array( 'WOOF', '_posts_where' ) );
					remove_filter('posts_orderby', array( 'WOOF', '_posts_orderby' ) );
				}
        
        if (isset($r["sql"])) {
          remove_filter( 'posts_request', array("WOOF", "capture_sql"));
          return $this->last_sql_query;
        }

				$meta = array();
				
				if ( $collect_meta ) {
        	
					// now collect the posts together in a new array, removing duplicates

					$ur = array(); // unique results 
					
					foreach ($res as $p) {
						
						$id = $p->ID;

						if ( ! isset( $ur[$id] ) ) {
						
							// create an entry in the array for the post
							
							$np = clone $p;
								
							// remove unwanted properties
							// meta key / value will be the key and value first encountered
							
							unset( $np->meta_key, $np->meta_value, $np->meta_id, $np->post_id );
							
							$ur[$id] = $this->wrap_post( $p );
							
						} 
						
						// gather metadata together
						
						if (!isset($meta[$id])) {
							$meta[$id] = array();
						}
						
						$the = $ur[$id];
					
					
						if (!isset($meta[$id][$p->meta_key])) {
							$meta[$id][$p->meta_key] = array();
						}

						$meta[$id][$p->meta_key][] = $p->meta_value;
						
					} // endforeach
					
					// set meta properties
				
					foreach ($meta as $id => $m) {
						$the = $ur[$id];
						$the->_meta = $m;
						$the->process_meta();

						// apply filters
						
						apply_filters( "woof_posts_process_meta", $the );
						
					}
					
					$res = array_values( $ur );
					
					$coll = $this->collection ( $res );
					
					
				} else {
					// simply wrap the results in a collection
        	$coll = $this->wrap ( $res );
				}
			
        $coll->copy_meta( $q, "post_count,found_posts,max_num_pages");
				
        return $coll;
        
      }
      
      
      
    }

		public static function _posts_fields( $fields ) {
			global $wpdb;
			
			$r = self::$posts_args;
			$include = $r["include"];
			
			if ( !preg_match( "/" . $wpdb->postmeta . "\.*/", $fields ) ) {
				$fields .= ", {$wpdb->postmeta}.meta_key, {$wpdb->postmeta}.meta_value "; 
			}
			
			// pr("FIELDS:");
			// pr($fields);
			
			return $fields;
		}
		
		public static function _posts_join( $join ) {
			global $wpdb;
			
			$pm_join = " INNER JOIN wp_postmeta ON ({$wpdb->posts}.ID = {$wpdb->postmeta}.post_id) ";
			
			$r = self::$posts_args;
			$include = $r["include"];
			
			
			if ( !preg_match( "/" . $wpdb->postmeta . "/", $join ) ) {
				$join .= $pm_join;
			}
			

				
			// pr("JOIN:");
			// pr($join);
			return $join;
		}
		
		public static function _posts_orderby( $orderby ) {
			global $wpdb;
			$r = self::$posts_args;
			
			$orderby .= ", {$wpdb->postmeta}.meta_id ";
				
			// pr("JOIN:");
			// pr($join);
			return $orderby;
		}
		
		public static function _posts_where( $where ) {
			$r = self::$posts_args;
			$include = $r["include"];

			// filter based on certain fields
				
			// build the condition
			
			$parts = explode(",", $include);
				
			$or = array();
			
			foreach ($parts as $part) {
				// normalize part: allow * to stand-in for %
				$npart = str_replace("*", "%", $part);
				$or[] = " meta_key LIKE '" . $npart . "'";
			}
			
			$where .= " AND ( " . implode( " OR ", $or ) . " ) ";

			//pr("WHERE:");
			//pr($where);
			
			return $where;
		}

    public function comments($args = array()) {
      return $this->wrap_comments( get_comments($args) );
    }
    

    public function query_posts($args = array()) {
      $r = wp_parse_args($args);
      $r["query"] = "1";
      return $this->posts($r);
    }
    
    public function reset() {
      wp_reset_query();
    }
    
    public function posts_in($ids, $args = array()) {
      $r = wp_parse_args( $args );
      
      if (!is_array($ids)) {
        $ids = explode(",", $ids);
      }
      
      $r["post__in"] = $ids;
      
      return $this->posts($r);
    }

    public function posts_not_in($ids, $args = array()) {
      $r = wp_parse_args( $args );

      if (!is_array($ids)) {
        $ids = explode(",", $ids);
      }

      $r["post__not_in"] = $ids; 
      
      return $this->posts($r);
    }


    
    public function posts_by_title($args = array()) {
      $r = wp_parse_args( $args, array( "order" => "asc") );
      $r["orderby"] = "title";
      
      return $this->posts($r);
    }

    public function posts_by_menu_order($args = array()) {
      $r = wp_parse_args( $args, array( "order" => "asc" ) );
      
      $r["orderby"] = "menu_order";
      
      return $this->posts($r);
    }

    public function attachments($args = array()) {
      global $wf;
    
      $r = wp_parse_args( 
        $args, 
        array(
          'numberposts' => -1,
          'order' => 'ASC',
          'orderby' => 'menu_order ID'
        )
      );
    
      $r["post_type"] = 'attachment';

      return $this->wrap_attachments( get_posts( $r ) );

    }
  
    public function attachment($id = null) {
      
      $post = $this->post($id, "attachment");
      
      if ($post->exists()) {
        return $this->wrap_attachment($post->item);
      }
      
      return new WOOF_Silent( sprintf( __("There is no attachment with ID %s", WOOF_DOMAIN ), $id) );

    }
  
    
    function post_types($arg = null) {
      
      if (is_array($arg)) {

        // do a query on the post types
        
        $types = get_post_types($arg);
        
        return $this->wrap_post_types($types);
        
      } else {
        
        
        // retrieve all post types
        
        $types = get_post_types(array(), "names");
        $post_types = $this->wrap_post_types($types);
        
        if (is_string($arg) && $arg != "") {

          if ($arg == "any") { // special case
            
            $ptc = $this->get_post_type_class();
            
            return new $ptc("any");
          }
          
          foreach ($post_types as $post_type) {
            if ($post_type->name() == $arg) {
              return $post_type;
            }
          }

          return new WOOF_Silent( sprintf( __("The post type named %s does not exist", WOOF_DOMAIN), $arg ) );
        } 
        
        return $post_types;
        
      }
      
    }

    /*
    
      Method: types
        A more concise alias for the <post_types> method
         
    */
    
    
    function types($arg = null) {
      return $this->post_types($arg);
    }

    function type($name = null) {
      if (is_null($name)) {
        return $this->the_type();
      }
      
      return $this->post_types($name);
    }

    public function sites($args = array()) {
      
      $r = wp_parse_args( 
        $args,
        array("nocache" => "0")
      );
        
      if (is_multisite()) {
        $ret = $this->wrap_sites( $this->get_sites($args) );
      } else {
        $ret = $this->wrap_sites( $this->site() );
      }
      
      return $ret;
      
    }

    public function multisite_call($func, $args = array()) {

      global $wf;
    
      if (is_multisite()) {
      
        // store the current site
        $the_site = $wf->site();
    
        foreach ($wf->sites() as $site) {
      
          // we need to move the meta data across from one key to another
      
          if (switch_to_blog($site->id())) {
            call_user_func_array($func, $args);
          }

        }
    
        switch_to_blog($the_site->id());
    
      } else {
      
        call_user_func_array($func, $args);

      }
    
    }
  

    /*
    
      Method: fposts
        Returns filtered posts, by calling the <posts> method with suppress_filters turned off.
        Filtered posts queries take into account any filters currently setup by plug-ins etc.
        
      Arguments: 
        $args - String or Array, the same parameter accepted by Wordpress' get_posts function <http://codex.wordpress.org/Template_Tags/get_posts>
        
      Returns:
        WOOF_Collection - a collection of <WOOF_Post> objects
        
      See Also:
        <posts>
        
    */

    function fposts($args = array()) {

      $r = wp_parse_args( $always, $args );

      $r['suppress_filters'] = false;

      return $this->posts($r);
    }


    public function template_for_post($post) {
      
      global $wpdb;
      
      if (is_numeric($post)) {
        // assume we have an ID, so just look that up
        $id = $post;
      } else if (WOOF::is_or_extends($post, "WOOF_Post")) {
        $id = $post->id();
      } 
      
      if (!isset($this->templates_by_post_id)) {
        
        $this->templates_by_post_id = array();
        
        // grab all of the templates at once (for performance) 
        $sql = "SELECT post_id, meta_value FROM $wpdb->postmeta WHERE meta_key = '_wp_page_template'";
        
        $results = $wpdb->get_results($sql);
        
        foreach ($results as $result) {
          $this->templates_by_post_id[$result->post_id] = $result->meta_value;
        }
      
      } 
      
      if (isset($id) && isset($this->templates_by_post_id[$id]) && $this->templates_by_post_id[$id] != "default") {
        return $this->templates_by_post_id[$id];
      } else if (isset($id)) {
        
        // if nothing was found, try getting the slug
        
        $post = $this->post($id);
        
        $slug = $post->slug;
        $file = $this->theme_file("page-".$slug.".php");
         
        if ($file->exists()) {
          $t = $file->basename();
          $this->templates_by_post_id[$id] = $t;
          return $t;
        }
        
        
      }
    
      return "";
      
    }
    
    


    function term_by_name($name, $taxonomy) {
      $term = get_term_by("name", $name, $taxonomy);
      
      if ($term) {
        return $this->wrap_term($term);
      }
    
      return new WOOF_Silent( sprintf( __("There is no %s term named %s", WOOF_DOMAIN ), $taxonomy, $name) );
    } 

    public function term_by_slug($slug, $taxonomy) {

      $term = get_term_by("slug", $slug, $taxonomy);
      
      if ($term) {
        return $this->wrap_term($term);
      }
    
      return new WOOF_Silent( sprintf( __("There is no %s term with slug %s", WOOF_DOMAIN ), $taxonomy, $slug) );

    } 
    
    public function term($id = null, $taxonomy = null) {
      
      if (is_null($id)) {
        return $this->the_term($taxonomy);
      }
      
      if (is_object($id) && ( get_class($id) == $this->term_class || get_class($id) == $this->get_term_class() ) ) {
        return $id; // is already a term object
      }

      if (is_string($taxonomy)) {
        $taxs = explode(",", $taxonomy);
        
        if (count($taxs) > 1) {
          $taxonomy = $taxs;
        }
      }
      
      if (is_array($taxonomy)) {
        
        // check through each of the taxomomies in the order given
        
        foreach ($taxonomy as $tax) {
          
          $tax_obj = $this->taxonomy($tax);
                  
          if (is_numeric($id)) {
            $term = $this->term_by_id($id, $tax_obj->name);
          } else {
            $term = $this->term_by_slug($id, $tax_obj->name);
          }
          
          if ($term->exists()) {
            return $term;
          }
        }
        
      } 
      else if (is_null($taxonomy)) {
        
        // get the taxonomy simply by ID, since we have no taxonomy to use
        
        if (is_numeric($id)) {
          $term = $this->term_by_id($id); 
        
          if (!is_woof_silent($term)) {
            return $term;
          }
        } else {
          
          // if we have a slug, try all of the taxonomies
          
          // loop through all of the taxonomies until we find the slug
          
          foreach ($this->taxonomies() as $tax) {
            
            $term = $this->term_by_slug($id, $tax->name);
            
            if ($term->exists()) {
              return $term;
            }
          }
          
          // return new WOOF_Silent( __("You need to supply a taxonomy if the ID is not numeric", WOOF_DOMAIN ) );
        }
        
      } else {
      
        if (gettype($id) == "string") {
        
          $term = get_term_by("slug", $id, $taxonomy);
        
          if ($term) {
            return $this->wrap_term($term);
          } else {
            // try by name
            $term = get_term_by("name", $id, $taxonomy);
            
            if ($term) {
              return $this->wrap_term($term);
            }
          }
          
        } 
      
        // if we still haven't found it, try by ID
      
        if (is_int($id) || is_numeric($id)) {
          $term = get_term_by("id", $id, $taxonomy);
        
          if ($term) {
            return $this->wrap_term($term);
          }
        }
      
      }
      
      return new WOOF_Silent( sprintf( __("There is no %s term for the ID %s", WOOF_DOMAIN ), $taxonomy, $id) );
    }


    public function term_by_id($id, $taxonomy_name = null) {
      
      global $wpdb;
      
      $sql = "SELECT t.term_id, t.name, t.slug, t.term_group, tt.term_taxonomy_id, tt.count, tt.description, tt.parent, tt.taxonomy FROM $wpdb->terms t INNER JOIN $wpdb->term_taxonomy tt ON tt.term_id = t.term_id WHERE t.term_id = ".$id;

      if (!is_null($taxonomy_name)) {
        $sql .= " AND tt.taxonomy = '".$taxonomy_name."' ";
      }


      $term = $wpdb->get_row($sql);
      
      if ($term) {
        return $this->wrap_term($term);
      }
      
      return new WOOF_Silent( sprintf( __("There is no term with ID %s", WOOF_DOMAIN), $id ) );
    } 

    
    public function the_category() {
      return $this->the_term("category");
    }

    public function the_tag() {
      return $this->the_term("post_tag");
    }
    
    public function category($slug = null) {
      return $this->term($slug, "category");
    }

    public function tag($slug = null) {
      return $this->term($slug, "post_tag");
    }

    public function has_request($name) {
      global $wp_query;
      return isset($wp_query->query_vars[$name]);
    }

    public function has_qv($name) {
      return $this->has_request($name);
    }
    
    public function request($name, $fallback = "") {
      return $this->qv($name, $fallback);
    }

    public function qv($name, $fallback = "") {
      $val = get_query_var($name);
      
      if (!$val) {
        return $fallback;
      }
      
      return $val;
    }
    
    
    protected function wrap_objects($items, $item_class = "WOOF_Wrap", $collection_class = "WOOF_Collection") {
      $ret = array();

      if (!is_array($items) && is_object($items)) { // a single item
        return new $collection_class ( array( new $item_class($items) ) );
      }
      
      if (is_array($items)) {
        foreach ($items as $item) {
          $ret[] = new $item_class($item);
        }
      }
    
      return new $collection_class($ret, $item_class);
    }


    protected function wrap_object($item, $item_class) {
      return new $item_class($item);
    }

    // takes a standard posts result and wraps each in an object
    public function wrap($posts) {
      $ret = array();

      $pc = $this->get_post_class();
      
      if (!is_array($posts) && is_object($posts)) { // assume this is a single post
        return new $pc($post);
      }
      
      foreach ($posts as $post) {
        $ret[] = new $pc($post);
      }
      
      return new WOOF_Collection($ret, "WOOF_Post");
    }


    // takes a standard terms array and wraps each in an object
    public function wrap_terms($terms) {
      if (!is_wp_error($terms)) {
        return $this->wrap_objects( $terms, $this->get_term_class(), "WOOF_Collection" );
      } 
      
      return new WOOF_Collection();
    }

    // takes a standard taxonomies array and wraps each in an object
    public function wrap_taxonomies($taxonomies) {
      if (!is_wp_error($taxonomies)) {
        return $this->wrap_objects( $taxonomies, $this->get_taxonomy_class(), "WOOF_Collection" );
      }

      return new WOOF_Collection();
    }

    // takes a standard taxonomies array and wraps each in an object
    public function wrap_post_types($post_types) {
      if (!is_wp_error($post_types)) {
        return $this->wrap_objects( $post_types, $this->get_post_type_class(), "WOOF_Collection" );
      }

      return new WOOF_Collection();
    }

    
    
    // takes a standard sites array and wraps each in an object
    public function wrap_sites($sites) {
      return $this->wrap_objects( $sites, $this->get_site_class(), "WOOF_Collection" );
    }
    
    // takes a standard users array and wraps each in an object
    public function wrap_users($users) {
      return $this->wrap_objects( $users, $this->get_user_class(), "WOOF_Collection" );
    }

    // takes a standard comments array and wraps each in an object
    public function wrap_comments($comments) {
      return $this->wrap_objects( $comments, $this->get_comment_class(), "WOOF_Comment" );
    }

    // takes a standard roles array and wraps each in an object
    public function wrap_roles($roles) {
      $objects = array();
      
      foreach ($roles as $id => $role) {
        $obj = new stdClass();

        $obj->id = $id;
        $obj->name = $role["name"];
        $obj->capabilities = $role["capabilities"];
      
        $objects[] = $obj;
      }
      
      return $this->wrap_objects( $objects, $this->get_role_class(), "WOOF_Collection" );
    }

    // takes a standard attachments (posts) array and wraps each in an object
    public function wrap_attachments($attachments) {
      return $this->wrap_objects( $attachments, $this->get_attachment_class(), "WOOF_Collection" );
    }

  
    // takes a standard term object and wraps in a WOOF_Term
    public function wrap_term($term) {
      return $this->wrap_object( $term, $this->get_term_class() );
    }

    // takes a standard taxonomy object and wraps in a WOOF_Taxonomy
    public function wrap_taxonomy($taxonomy) {
      return $this->wrap_object( $taxonomy, $this->get_taxonomy_class() );
    }

    // takes a standard post object and wraps in a WOOF_Post
    public function wrap_post($post) {
      
      if (is_subclass_of($post, "WOOF_Post")) {
        return $post;
      } 

      return $this->wrap_object( $post, $this->get_post_class() );

    }

    // takes a standard taxonomy object and wraps in a WOOF_Taxonomy
    public function wrap_post_type($post_type) {
      return $this->wrap_object( $post_type, $this->get_post_type_class() );
    }
    
    // takes a standard user object and wraps in a WOOF_User
    public function wrap_user($user) {
      return $this->wrap_object( $user, $this->get_user_class() );
    }

    // takes a standard attachment object and wraps in a WOOF_Attachment
    public function wrap_attachment($attachment) {
      return $this->wrap_object( $attachment, $this->get_attachment_class() );
    }

    // takes a standard comment object and wraps in a WOOF_Comment
    public function wrap_comment($comment) {
      return $this->wrap_object( $comment, $this->get_comment_class() );
    }


    // takes a standard site object and wraps in a WOOF_Site
    public function wrap_site($site) {
      return $this->wrap_object( $site, $this->get_site_class() );
    }


    public function pages($args = array()) {
      $pages = get_pages($args);
      
      $pc = $this->get_post_class();

      $ret = array();
      
      foreach ($pages as $page) {
        $ret[] = new $pc($page);
      }
      
      return new WOOF_Collection($ret);
    }
    
    function post_type() {
      return $this->the()->type();
    }

    function page_by_path($path) {
      $post = get_page_by_path($path);
      
      if ($post) {
        return $this->wrap($post);
      }
      
      return new WOOF_Silent( sprintf( __("There is no page at path %s", WOOF_DOMAIN), $path ) ); 
    }
    
    function object_type() {
        
      if (is_tax() || is_category() || is_tag()) {
        return "taxonomy";
      } else if (is_author()) {
        return "user";
      } else if (is_single() || is_archive() || is_page()) {
        return "post";
      } else {
        return "site";
      }
  	  
    }
    
    function object() {

      $object = null;
      
      if (is_tax() || is_category() || is_tag()) {
        $object = $this->the_term();
      } else if (is_author()) {
        $object = $this->the_author();
      } else if (is_single() || is_page()) {
        $object = $this->the();
      } else if ($this->is_front_or_home()) {
        $object = $this->the_site();
      } else if (is_archive() || is_post_type_archive()) {
        $object = $this->the_type();
      } 
      
      if (is_null($object)) {
        return new WOOF_Silent( __("There is no current object", WOOF_DOMAIN) );
      }
      
  	  return $object;
  	
    }
  
    function post($id = null, $type = "post") {
      
      $orig = $id;
      
      if (!is_null($id) && is_object($id) && ( get_class($id) == $this->post_class || get_class($id) == $this->get_post_class() ) ) {
        return $id; // they user has supplied a WOOF / MEOW post object already
      }
      
      $ret = null;
      
      global $wp_query;

      if (!is_null($id) && gettype($id) == "string" && !is_numeric($id)) {
        // assume its a slug, so reset the ID to an actual integer
        $id = $this->id_for_slug($id, $type);
      } else if (!$id) {
        
        // try to get the "remembered" id - this is the best guess at the ID of the current page being viewed
        $id = WOOF::$the_id;

        // get the ID of the current post, as per the wordpress global wp_query post property
        
        if (isset($wp_query, $wp_query->post)) {
          if (!$id) {
            $id = $wp_query->post->ID;
          }
        }
        
      }
      
      // we should have established something for the id now, try to get the post

      // now we check the property cache on the resolved ID - we don't do this at the start, since the ID 
      // can possibly be changing globally, which we can't cache 

      if (isset($id)) {
        
        if (is_numeric($id)) {
          $id = (int) $id;
        }
        
        $args = array($id, $type);
      
        $po = get_post($id);
      
        if ($po) {
          $pc = $this->get_post_class();
          $ret = new $pc($po);
        }

      }
    
      if (!$ret) {
        return new WOOF_Silent( sprintf( __("Cannot find %s with id or slug '%s'", WOOF_DOMAIN), $type, $orig ) );
      }
    
      return $ret;
    }

		function is_other_site( $id ) {
			
			if (is_multisite()) {
				
				if ($id && is_numeric($id)) {
					return $this->site->id != $id;
				}

			}
				
			return false;
		}
		
    function site($id = null) {

      if (is_multisite()) {
        
        $sites = $this->sites(array("public_only" => false));
      
        if ($id == null) {
          global $blog_id;
          $id = $blog_id;
        }
      
        if ($id) {
          foreach ($sites as $site) {
            if ($site->id() == $id) {
              return $site;
            }
          }
        }
      } else {

        $url = $this->site_url();
        
        $admin_email = get_bloginfo("admin_email");
        
        $admin = $this->user_by_email($admin_email);
        
        
        // mockup an object that represents the site, with a structure that mirrors what would be returned in the multisite environment
        $site = new stdClass();
        
        $site->blog_id = 1;
        $site->site_id = 1;
        $site->domain = WOOF::domain_of_url($url);
        $site->path = "/";
        
        if ($admin) {
          $site->registered = $admin->registered("Ymd H:i:s");
          $site->last_updated = $admin->registered("Ymd H:i:s");
        }

        $site->public = get_option("blog_public");
        $site->archived = 0;
        $site->mature = 0;
        $site->spam = 0;
        $site->deleted = 0;
        $site->lang_id = get_option("language");
        $site->email = $admin_email;
        $site->ip = '';
        
        return $this->wrap_site($site);
        
      }
      
      return new WOOF_Silent( __("Could not find a site with id $id", WOOF_DOMAIN ) );
    }
    
    function the() {
      global $post;
      
      if (isset($post) && !is_null($post)) {
        return $this->wrap_post( $post );
      }
      
      return new WOOF_Silent( __("There is no current post", WOOF_DOMAIN ) );
    }
    
    
    public function the_taxonomy() {

      // get the current active taxonomy (if any)
      
      $tv = get_query_var("taxonomy");
      
      if ($tv && $tv != "") {
        return $this->taxonomy( $tv );
      }
    
      return new WOOF_Silent( __("There is no active taxonomy", WOOF_DOMAIN ) );
    }

    public function the_term($taxonomy = null) {
      // get the current active term (if any)
      
      if (is_tag()) {
        if (is_null($taxonomy) || $taxonomy == "post_tag") {
          return $this->term( get_query_var("tag"), "post_tag" );
        } else {
          return new WOOF_Silent( sprintf( __( "There is no active %s", WOOF_DOMAIN ), $taxonomy ) );
        }
        
      } else if (is_category()) {
        if (is_null($taxonomy) || $taxonomy == "category") {
          return $this->term_by_id( get_query_var("cat"), "category" );
        } else {
          return new WOOF_Silent( sprintf( __( "There is no active %s", WOOF_DOMAIN ), $taxonomy ) );
        }
      } else {
        $tv = get_query_var("term");
        
        if ($tv && $tv != "") {
          return $this->term( get_query_var("term"), get_query_var("taxonomy") );
        }
      }
    
      return new WOOF_Silent( __("There is no active term", WOOF_DOMAIN ) );
    }

    public function the_site() {
      // get the active site (Wordpress seems to NOT always return this properly in get_current_site, not sure why)
      return $this->site(); // null id will return the current site
    }
    
    public function the_type() {
      // get the current active post type (if any)
      
      $pt = get_query_var("post_type");
      
      if (isset($pt) && $pt != "") {
        return $this->types( get_query_var("post_type") );
      } else {
        return $this->types( "post" );
      }
    
    }

    public function the_user() {
      global $current_user;
      
      if ($current_user) {
        return $this->user($current_user->ID);
      }
      
      return new WOOF_Silent( __("There is no active user - no users are currently logged in", WOOF_DOMAIN) );
    }
    
    public function the_author() {
      if ($this->has_qv("author_name")) {
        $author = $this->user_by_login($this->qv("author_name"));
        
        if (!is_woof_silent($author)) {
          return $author;
        }
      
      }
      
      $the = $this->the();
      
      if ($the->exists()) {
        // fallback to the author of the current post
        return $this->the->author();
      }
    
      return new WOOF_Silent( __("There is no current author", WOOF_DOMAIN) );
    }
    
    public function page($id = NULL) {
      return $this->post($id, "page");
    }

    public function is_front() {
      return is_front_page();
    }

    public function is_home() {
      return is_home_page();
    }
    
    public function is_front_or_home() {
      return is_front_page() || is_home();
    }

    public function template_path() {
      if (!$this->template_path) {
        $this->template_path = get_page_template();
      }
      
      return $this->template_path;
    }
    
    public function page_number() {
      return $this->page_num();
    }

    public function page_num() {
      global $paged;
      
      if (!isset($paged) || $paged == 0) {
        return 1; // normalize the result, where page 0 is usually just page 1
      }
      
      return $paged;
    }

    public function is_paged() {
      return $this->page_num() != 0;
    }

    public function is_page_number($n) {
      return $this->page_num() == $n;
    }
    
    public function page_count() {
      global $wp_query;
      return $wp_query->max_num_pages;
    }
    
    
    public function template_basename($suffix = "") {
      return basename($this->template_path(), $suffix);
    }
    
    public function template() {
      return $this->template_basename(".php");
    }

  
    public function is_template($templates) {
      if (!is_array($templates)) {
        $templates = explode(",", $templates);
      }
      
      return in_array($this->template_basename(".php"), $templates);
    }

    public function latest($count = 1, $args = array()) {
    
      $r = wp_parse_args(
        $args, 
        array(
          "post_type" => "post",
          "orderby" => "post_date",
          "order" => "desc"
        )
      );
  
      $r["posts_per_page"] = $count;
    
    
      $posts = $this->posts($r);
      
      if ($count == 1) {
        return $posts->first();
      }
      
      return $posts;
      
    }

    public function top_posts($args = array()) {
    
      $r = wp_parse_args(
        $args, 
        array(
          "orderby" => "post_date",
          "order" => "desc"
        )
      );
      
      $r["post_parent"] = 0;
  
      $posts = $this->posts($r);
    
      return $posts;
    }
    
    public function top_latest($count = 1, $args = array()) {
    
      $r = wp_parse_args(
        $args, 
        array(
          "orderby" => "post_date",
          "order" => "desc"
        )
      );
  
      $posts = $this->posts($r);
        
      $r["post_parent"] = 0;
      $r["numberposts"] = $count;

      if ($count == 1) {
        return $posts->first();
      }
      
      return $posts;
    }
    

    public function theme_absolute_url($file = "", $timestamp = true, $parent = "auto") {
      return $this->theme_url($file, $timestamp, false, $parent);
    }

    public function parent_theme_url($file = "", $timestamp = true, $root_relative = true) {
      return $this->theme_url($file, $timestamp, $root_relative, true);
    }

    public function parent_theme_absolute_url($file = "", $timestamp = true, $root_relative = true) {
      return $this->theme_url($file, $timestamp, false, true);
    }

    
    public function child_theme_url($file = "", $timestamp = true, $root_relative = true) {
      return $this->theme_url($file, $timestamp, $root_relative, false);
    }

    public function child_theme_absolute_url($file = "", $timestamp = true, $root_relative = true) {
      return $this->theme_url($file, $timestamp, false, false);
    }
    
    public function theme_file_info($file = "", $timestamp = true, $root_relative = true, $parent = "auto") {
      
      $ret = array();
      
      $parent_url = get_bloginfo('template_url');
      $parent_path = get_template_directory();

      $base_url = $parent_url;
      $base_path = $parent_path;
      
      if ($parent == "auto" || $parent === FALSE) {
        $base_url = get_stylesheet_directory_uri();
        $base_path = get_stylesheet_directory();
      } 
    
      
      $ret["path"] = $base_path;
      
      if ($file == "") {
        if ($root_relative) {
          $ret["url"] = WOOF::root_relative_url($base_url);
        } else {
          $ret["url"] = $base_url;
        }
        
        return $ret;
      }
      
      
      $ret["url"] = $base_url."/".ltrim($file, "/");
      $ret["path"] = $base_path.WOOF_DIR_SEP.ltrim($file, WOOF_DIR_SEP);
      

      if ($parent == "auto" && ($base_url != $parent_url)) {
        
        // check if the file exists
        
        if (!file_exists($ret["path"])) {
          
          // assume the file is in the parent
          
          $base_url = $parent_url;
          $base_path = $parent_path;
          
          $ret["url"] = $base_url."/".ltrim($file, "/");
          $ret["path"] = $base_path.WOOF_DIR_SEP.ltrim($file, WOOF_DIR_SEP);
          
        }
        
      }
      
      $info = pathinfo($file);
      
      // don't add the timestamp for directories
      
      if ($timestamp && isset($info["extension"]) && $info["extension"] != "") {
        
        if (file_exists($ret["path"])) {
          $ret["url"] .= "?".filemtime($ret["path"]);
        }
        
      }
      
      if ($root_relative) {
        $ret["url"] = WOOF::root_relative_url($ret["url"]);
      }
      
      return $ret;
      
    }
    
    
    public function theme_url($file = "", $timestamp = true, $root_relative = true, $parent = "auto") {
      $info = $this->theme_file_info($file, $timestamp, $root_relative, $parent);
      return $info["url"];
    }

    public function theme_path($file = "", $timestamp = true, $root_relative = true, $parent = "auto") {
      $info = $this->theme_file_info($file, $timestamp, $root_relative, $parent);
      return $info["path"];
    }
    
		


    // Convenient Theme enqueue methods
    
    public function do_enqueue_theme_script($base, $files, $deps = array(), $in_footer = false, $timestamp = true, $default_extension = "js", $parent = "auto") {
      
      $sub_base = "";
      
      if (!is_array($files)) {
        $pattern = "/^[a-z0-9\_\-\/]+\:/i";

        if (preg_match($pattern, $files, $matches)) {
          $sub_base = trim($matches[0], "/:")."/";
        }

        $files = explode(",", preg_replace($pattern, "", $files));
      }
      
      foreach ($files as $file) {

        if (trim($file) != "") {

          $pi = pathinfo($file);
        
          $ext = "";
        
          if (isset($pi["extension"])) {
            $ext = strtolower($pi["extension"]);
          }
        
          $sub_path = $base.$sub_base.trim($file);
          
          $info = $this->theme_file_info($sub_path, $timestamp, false, $parent);
          $url = $info["url"];
          $path = $info["path"];
          
					// if the file is not there, or the file is a path, try adding the extension.
					// the path check is because if we enqueue an extensionless file that is ALSO a path, we assume we don't want to enqueue the path
					
          if (!file_exists($path) || is_dir($path) ) {

            if ($ext != $default_extension) {
              
              // add the default extension as this file couldn't be found
              $file = $file.".".$default_extension;

              $sub_path = $base.$sub_base.trim($file);
              $info = $this->theme_file_info($sub_path, $timestamp, false, $parent);
              $url = $info["url"];
              $path = $info["path"];
            }
            
          }
          
          $id = sanitize_title_with_dashes($base.$sub_base.$file);
          
          if ($deps === false) {

            // use inline tags instead of enqueue
            if (file_exists($path)) {
              echo WOOF_HTML::tag("script", array("id" => $id, "type" => "text/javascript", "src" => $url), "")."\n";
            } else {
              echo "\n<!-- script tag failed: theme script $sub_path was not found -->\n";
            }

          } else {
            
            if (file_exists($path)) {
              wp_enqueue_script($id, $url, $deps, false, $in_footer);
            } else {
              echo "\n<!-- theme enqueue failed: script $sub_path was not found -->\n";
            }
          }
          
        }

      }

      
    }

		
    public function do_enqueue_theme_style($base, $files, $deps = array(), $media = "all", $timestamp = true, $default_extension = "css", $parent = "auto") {
      
      $sub_base = "";
      
      if (!is_array($files)) {
        $pattern = "/^[a-z0-9\_\-\/]+\:/i";

        if (preg_match($pattern, $files, $matches)) {
          $sub_base = trim($matches[0], "/:")."/";
        }

        $files = explode(",", preg_replace($pattern, "", $files));
      }

      
      foreach ($files as $file) {

        if (trim($file) != "") {
        
          $pi = pathinfo($file);
        
          $ext = "";
        
          if (isset($pi["extension"])) {
            $ext = strtolower($pi["extension"]);
          }
                


          $sub_path = $base.$sub_base.trim($file);
          
          $info = $this->theme_file_info($sub_path, $timestamp, false, $parent);
          $url = $info["url"];
          $path = $info["path"];
        
					// if the file is not there, or the file is a path, try adding the extension.
					// the path check is because if we enqueue an extensionless file that is ALSO a path, we assume we don't want to enqueue the path
					
          if (!file_exists($path) || is_dir($path) ) {
          
            if ($ext != $default_extension) {
              
              // add the default extension as this file couldn't be found
              $file = $file.".".$default_extension;

              $sub_path = $base.$sub_base.trim($file);
              $info = $this->theme_file_info($sub_path, $timestamp, false, $parent);
              $url = $info["url"];
              $path = $info["path"];
            }
            
          }
        
          
          $id = sanitize_title_with_dashes($base.$sub_base.$file);
          
          if ($deps === false) {

            // use inline tags instead of enqueue
            if (file_exists($path)) {
              echo WOOF_HTML::tag("link", array("id" => $id, "rel" => "stylesheet", "type" => "text/css", "href" => $url), null);
            } else {
              echo "\n<!-- link tag failed: theme stylesheet $sub_path was not found -->\n";
            }

          } else {
            
            if (file_exists($path)) {
              wp_enqueue_style($id, $url, $deps, false, $media);
            } else {
              echo "\n<!-- theme enqueue failed: stylesheet $sub_path was not found -->\n";
            }
            
          }
          
        }
      
      }
      
    }
    
		// more readable synonyms
		
		public function enqueue_theme_script_at($base, $files, $deps = array(), $in_footer = false, $timestamp = true, $default_extension = "js", $parent = "auto") {
			$this->do_enqueue_theme_script( $base, $files, $deps, $in_footer, $timestamp, $default_extension, $parent );
		}
			
		public function enqueue_theme_style_at($base, $files, $deps = array(), $media = "all", $timestamp = true, $default_extension = "css", $parent = "auto") {
			$this->do_enqueue_theme_style( $base, $files, $deps, $media, $timestamp, $default_extension, $parent );
		}
		
		public function theme_script_at($base, $files, $deps = array(), $in_footer = false, $timestamp = true, $default_extension = "js", $parent = "auto") {
			$this->do_enqueue_theme_script( $base, $files, $deps, $in_footer, $timestamp, $default_extension, $parent );
		}
			
		public function theme_style_at($base, $files, $deps = array(), $media = "all", $timestamp = true, $default_extension = "css", $parent = "auto") {
			$this->do_enqueue_theme_style( $base, $files, $deps, $media, $timestamp, $default_extension, $parent );
		}
    
		// preset path enqueues
			    
    public function enqueue_theme_js($files, $deps = array(), $in_footer = false, $timestamp = true, $default_extension = "js", $parent = "auto") {
      $this->do_enqueue_theme_script("js/", $files, $deps, $in_footer, $timestamp, $default_extension, $parent);
    }

    public function enqueue_theme_javascript($files, $deps = array(), $in_footer = false, $timestamp = true, $default_extension = "js", $parent = "auto") {
      $this->do_enqueue_theme_script("javascripts/", $files, $deps, $in_footer, $timestamp, $default_extension, $parent);
    }

    public function enqueue_theme_script($files, $deps = array(), $in_footer = false, $timestamp = true, $default_extension = "js", $parent = "auto") {
      $this->do_enqueue_theme_script("scripts/", $files, $deps, $in_footer, $timestamp, $default_extension, $parent);
    }

    public function enqueue_theme_css($files, $deps = array(), $media = "all", $timestamp = true, $default_extension = "css", $parent = "auto") {
      $this->do_enqueue_theme_style("css/", $files, $deps, $media, $timestamp = true, $default_extension, $parent);
    }

    public function enqueue_theme_js_css($files, $deps = array(), $media = "all", $timestamp = true, $default_extension = "css", $parent = "auto") {
      $this->do_enqueue_theme_style("js/", $files, $deps, $media, $timestamp = true, $default_extension, $parent);
    }

    public function enqueue_theme_javascript_css($files, $deps = array(), $media = "all", $timestamp = true, $default_extension = "css", $parent = "auto") {
      $this->do_enqueue_theme_style("javascript/", $files, $deps, $media, $timestamp = true, $default_extension, $parent);
    }

    public function enqueue_theme_script_css($files, $deps = array(), $media = "all", $timestamp = true, $default_extension = "css", $parent = "auto") {
      $this->do_enqueue_theme_style("script/", $files, $deps, $media, $timestamp = true, $default_extension, $parent);
    }
    
    public function enqueue_theme_stylesheet($files, $deps = array(), $media = "all", $timestamp = true, $default_extension = "css", $parent = "auto") {
      $this->do_enqueue_theme_style("stylesheets/", $files, $deps, $media, $timestamp = true, $default_extension, $parent);
    }
    
    public function enqueue_theme_style($file = "style.css", $deps = array(), $media = "all", $timestamp = true, $default_extension = "css", $parent = "auto") {
      $this->do_enqueue_theme_style("", $file, $deps, $media, $timestamp = true, $default_extension, $parent);
    }
    


    public function theme_js($files, $deps = array(), $in_footer = false, $timestamp = true, $default_extension = "js", $parent = "auto") {
      $this->do_enqueue_theme_script("js/", $files, $deps, $in_footer, $timestamp, $default_extension, $parent);
    }

    public function theme_javascript($files, $deps = array(), $in_footer = false, $timestamp = true, $default_extension = "js", $parent = "auto") {
      $this->do_enqueue_theme_script("javascripts/", $files, $deps, $in_footer, $timestamp, $default_extension, $parent);
    }

    public function theme_script($files, $deps = array(), $in_footer = false, $timestamp = true, $default_extension = "js", $parent = "auto") {
      $this->do_enqueue_theme_script("scripts/", $files, $deps, $in_footer, $timestamp, $default_extension, $parent);
    }

    public function theme_script_css($files, $deps = array(), $media = "all", $timestamp = true, $default_extension = "css", $parent = "auto") {
      $this->do_enqueue_theme_style("script/", $files, $deps, $media, $timestamp = true, $default_extension, $parent);
    }
    
    public function theme_css($files, $deps = array(), $media = "all", $timestamp = true, $default_extension = "css", $parent = "auto") {
      $this->do_enqueue_theme_style("css/", $files, $deps, $media, $timestamp = true, $default_extension, $parent);
    }

    public function theme_js_css($files, $deps = array(), $media = "all", $timestamp = true, $default_extension = "css", $parent = "auto") {
      $this->do_enqueue_theme_style("js/", $files, $deps, $media, $timestamp = true, $default_extension, $parent);
    }

    public function theme_javascript_css($files, $deps = array(), $media = "all", $timestamp = true, $default_extension = "css", $parent = "auto") {
      $this->do_enqueue_theme_style("javascripts/", $files, $deps, $media, $timestamp = true, $default_extension, $parent);
    }
    
    public function theme_stylesheet($files, $deps = array(), $media = "all", $timestamp = true, $default_extension = "css", $parent = "auto") {
      $this->do_enqueue_theme_style("stylesheets/", $files, $deps, $media, $timestamp = true, $default_extension, $parent);
    }
    
    public function theme_style($file = "style.css", $deps = array(), $media = "all", $timestamp = true, $default_extension = "css", $parent = "auto") {
      $this->do_enqueue_theme_style("", $file, $deps, $media, $timestamp = true, $default_extension, $parent);
    }
    
    public function script($ids, $in_footer = false, $enqueue = true) {
      
      global $wp_scripts;
      
      
      // enqueue known scripts
      
      if (!is_array($ids)) {
        $ids = explode(",", $ids);
      }

      foreach ($ids as $id) {
        wp_enqueue_script(trim($id), false, array(), false, $in_footer);
      }
      
      if (!$enqueue) {
        $wp_scripts->print_scripts($ids);
      }
      


    }

    public function spacer($args = array()) {
    
      $r = wp_parse_args( $args,
        array("w" => 1, "h" => 1)
      );
      
      if (!is_numeric($r["w"])) {
        $r["w"] = 1;
      } else {
        $r["w"] = (int) $r["w"];
      }

      if (!is_numeric($r["h"])) {
        $r["h"] = 1;
      } else {
        $r["h"] = (int) $r["h"];
      }
      
      $w = $r["w"];
      $h = $r["h"];
      
      $base_path = $this->content_image_cache_dir."site/0".WOOF_DIR_SEP;
      $base_url = $this->content_image_cache_url."site/0/";
      $filename = "spacer.{$w}x{$h}.png";
      
      $path = $base_path.$filename;
      $url = $base_url.$filename;
      
      if (!file_exists($path)) {
        
        $png = imagecreatetruecolor($r["w"], $r["h"]);
        imagesavealpha($png, true);

        $trans_colour = imagecolorallocatealpha($png, 0, 0, 0, 127);
        imagefill($png, 0, 0, $trans_colour);

      
        if ( !file_exists($base_path) ) {
          wp_mkdir_p($base_path);
        }
      
        imagepng($png, $path);
      
      }
      
      
      return new WOOF_Image($path, $url);
    }
    
    public function transparent_image($args = array()) {
      return $this->spacer($args);
    }
    
    public function style($ids, $media = 'all', $enqueue = true) {
      // enqueue known scripts
      
      global $wp_styles;
      
      if (!is_array($ids)) {
        $ids = explode(",", $ids);
      }
      
      foreach ($ids as $id) {
        wp_enqueue_style(trim($id), false, array(), $media);
      }
      
      if (!$enqueue) {
        $wp_styles->do_items($ids);
      }
      
    }
    
    
    public function body_class($class = array()) {
      global $body_class;
      
      $cls = get_body_class($class);
      
      if (isset($body_class)) {
        
        if (!is_array($body_class)) {
          $body_class = explode(" ", $body_class);
        }
        
        if (count($body_class)) {
          $cls = array_merge($cls, $body_class);
        }
      
      } 
      
      if (count($cls)) {
        return 'class="'.implode(" ", $cls).'"';
      }
      
      return '';
    }


    public function body_id($id = "") {
      global $body_id;
      
      if (isset($body_id) && $id == "") {
        $id = $body_id;
      }
      
      if (strlen($id)) {
        return 'id="'.$id.'"';
      }
      
      return "";
    }
    
    public function body_attr($class = array(), $id = "") {
      global $body_id;
      
      if (isset($body_id)) {
        $parts = explode(".", $body_id); 
        
        if (count($parts) > 1) {
          if (!count($class)) {
            $class = explode(" ", $parts[1]);
          }
          
          if ($id == "") {
            $id = $parts[0];
          }
          
        } else {

          if ($id == "") {
            $id = $parts[0];
          }
          
        }
      }
      
      return $this->body_id($id)." ".$this->body_class($class);
    }
    
    public function content_url($file = "", $timestamp = true, $root_relative = true, $main = false) {
      
      $base_url = $this->wp_content_url($main);

      if ($file == "") {
        if ($root_relative) {
          return WOOF::root_relative_url($base_url);
        } else {
          return $base_url;
        }
      }
      
      if (substr($file, 0, 1) != "/") {
        $file = "/".$file;
      }
      
      $url = $base_url.$file;
      
      if ($timestamp) {
        $path = $base_url.$file;
        
        if (file_exists($path)) {
          $url .= "?".filemtime($path);
        }
        
      }
      
      if ($root_relative) {
        return WOOF::root_relative_url($url);
      }
      
      return $url;
    }
    
    public function sanitize($val) {
      return sanitize_title_with_dashes($val);
    }

    public function san($val, $args = array()) {
      return sanitize_title_with_dashes($val);
    }

    public function prepare($str) {
      return apply_filters("the_content", $str);
    }

    public function users($args = array()) {
      return $this->wrap_users( get_users($args) );
    }

    public function roles() {
      
      global $table_prefix;
  
      return $this->wrap_roles( get_option($table_prefix."user_roles") );

    }
    
    public function role($id) {
      foreach ($this->roles() as $role) {
        if ($role->id() == $id) {
          return $role;
        }
      }

      foreach ($this->roles() as $role) {
        if ($role->name() == $id) {
          return $role;
        }
      }

      return new WOOF_Silent( sprintf( __("There is no role with id '%s'", WOOF_DOMAIN), $id ) );
    }

    public function theme_file($path, $parent = "auto") {
      $info = $this->theme_file_info($path, false, false, $parent);
      $fc = $this->get_file_class();
      return new $fc( $info["path"], $info["url"] );
    }

    public function parent_theme_file($path) {
      return $this->theme_file($path, true);
    }

    public function child_theme_file($path) {
      return $this->theme_file($path, false);
    }


    public function uploaded_file($path, $time = null) {
      $wpud = wp_upload_dir($time);
      $file_path = $wpud["basedir"].WOOF_DIR_SEP.$path;
      $file_url = $wpud["baseurl"]."/".$path;
      $fc = $this->get_file_class();
      return new $fc( $file_path, $file_url );
    }

    public function do_file_from_url($url, $name = "", $dir = null, $assume_extension = "txt", $default_dir, $type) {
      $exists = false;
    
      $info = pathinfo($url);
      
      $ext = "";
      
      if (isset($info["extension"])) {
        $ext = $info["extension"];
      }
    
      if ($ext == "" || !preg_match("/^[a-z0-9]{1,20}$/", $ext)) {
        $ext = $assume_extension;
      }
      
      if (is_null($name)) {
        $file = $info["filename"].".".$ext;

      } else {

        if ($name == "") {
          $file = str_replace("_", "-", WOOF_Inflector::underscore($info["filename"])).".".md5($url).".".$ext;
        } else {
          $file = basename($name).".".$ext;
        }

      }
      
      if (is_null($dir)) {
        $dir = $default_dir;
      }

      if (!is_dir($dir)) { 
        if (!wp_mkdir_p($dir)) {
          $dir = $default_dir;
        }
      }
      
			if (file_exists($dir)) { // make sure the base directory exists
				
	      $file_path = rtrim($dir, WOOF_DIR_SEP).WOOF_DIR_SEP.$file;
      
	      if (!file_exists($file_path)) {
      
	        $data = wp_remote_get( $url, array("timeout" => $this->wp_remote_timeout ) );
      
	        if (!is_wp_error($data)) {
	          if ($data["response"]["code"] == 200) {
	            // save the file
            
							//pr($dir);

	            $handle = fopen ( $file_path , "w" );
        
	            fwrite( $handle, $data["body"] );
	            fclose( $handle );
              
	            $exists = true;
	          }
	        }
      
	      } else {
	        $exists = true;
	      }

    
	      if ($exists) {
	        $cd = $this->wp_content_dir();
	        
          if (strpos($cd, $file_path) === FALSE) {
            $cd = $this->wp_content_dir(true);   
          }
          
          $file_path = str_replace($cd, "", $file_path);
          
	        if ($type == "image") {
            $img = $this->content_image( $file_path ); 
            $img->set_external_url($url);
            return $img;
	        } else {
	          $file = $this->content_file( $file_path ); 
            $file->set_external_url($url);
            return $file;
	        }
        
	      }
  
	      return new WOOF_Silent( sprintf( __("There was no %s at URL %s", WOOF_DOMAIN ), $type, $url ) );
    	
			} else { // file_exists $dir
				
	      return new WOOF_Silent( sprintf( __("Cannot cache %s from URL %s, as the content directory %s does not exist", WOOF_DOMAIN ), $type, $url, $dir ) );
				
			} 
			
		}

    public function file_from_url($url, $name = "", $dir = null, $assume_extension = "txt") {
      return $this->do_file_from_url($url, $name, $dir, $assume_extension, $this->content_file_from_url_dir, "file");
    }

    public function image_from_url($url, $name = "", $dir = null, $assume_extension = "jpg") {
      return $this->do_file_from_url($url, $name, $dir, $assume_extension, $this->content_image_from_url_dir, "image");
    }
    
    public function do_content_file($path, $main = "auto", $type = "file") {
      
      $ic = $this->get_image_class();
      $fc = $this->get_file_class();
      
      if ($main == "auto") {
        // first check for an image in the blogs.dir directory
        $base_dir = $this->wp_content_dir(false);
        $base_url = $this->wp_content_url(false);
        $file_path = $base_dir.WOOF_DIR_SEP.trim($path, WOOF_DIR_SEP);
        $file_url = $base_url."/".trim($path, "/");
        
        if (!file_exists($file_path)) {
          // now check in the main directory
          $base_dir = $this->wp_content_dir(true);
          $base_url = $this->wp_content_url(true);
          $file_path = $base_dir.WOOF_DIR_SEP.trim($path, WOOF_DIR_SEP);
          $file_url = $base_url."/".trim($path, "/");
        }
        

      } else {

        $base_dir = $this->wp_content_dir($main);
        $base_url = $this->wp_content_url($main);
        $file_path = $base_dir.WOOF_DIR_SEP.trim($path, WOOF_DIR_SEP);
        $file_url = $base_url."/".trim($path, "/");
      }

      if ($type == "file") {
        return new $fc( $file_path, $file_url );
      } else {
        return new $ic( $file_path, $file_url );
      }
      
    }

    public function content_file($path, $main = "auto") {
      return $this->do_content_file($path, $main, "file");
    }
            
    public function content_image($path, $main = "auto") {
      return $this->do_content_file($path, $main, "image");
    }

    public function parent_theme_image($file, $base_dir = "images") {
      return $this->theme_image($file, $base_dir, true);
    }

    public function child_theme_image($file, $base_dir = "images") {
      return $this->theme_image($file, $base_dir, false);
    }
  
    public function theme_image($file, $base_dir = "images", $parent = "auto") {
      $info = $this->theme_file_info(WOOF_DIR_SEP.trim($base_dir, WOOF_DIR_SEP).WOOF_DIR_SEP.trim($file, WOOF_DIR_SEP), false, false, $parent);
      $ic = $this->get_image_class();
      $image = new $ic( $info["path"], $info["url"] );
      
      if ($image->exists()) {
        return $image;
      }
      
      return new WOOF_Silent( sprintf( __( "The theme image at images/%s does not exist", WOOF_DOMAIN ), $file ), "comment" );
    }

    public function parent_theme_img($file, $base_dir = "img") {
      return $this->theme_img($file, $base_dir, true);
    }

    public function child_theme_img($file, $base_dir = "img") {
      return $this->theme_img($file, $base_dir, false);
    }
      
    public function theme_img($file, $base_dir = "img", $parent = "auto") {
      $info = $this->theme_file_info(WOOF_DIR_SEP.trim($base_dir, WOOF_DIR_SEP).WOOF_DIR_SEP.trim($file, WOOF_DIR_SEP), false, false, $parent);
      $ic = $this->get_image_class();

      $image = new $ic( $info["path"], $info["url"] );
      
      if ($image->exists()) {
        return $image;
      }
      
      return new WOOF_Silent( sprintf( __( "The theme image at images/%s does not exist", WOOF_DOMAIN ), $file ), "comment" );
    }


    public function uploaded_image($path, $time = null) {
      $wpud = wp_upload_dir($time);
      $file_path = $wpud["basedir"].WOOF_DIR_SEP.$path;
      $file_url = $wpud["baseurl"]."/".$path;
      $ic = $this->get_image_class();
      return new $ic( $file_path, $file_url );
    }
    
    
    // transients API wrapper

    public function seconds($duration) {
      preg_match("/(\d+)\s*(s|m|h|d|w|y)/", $duration, $matches);
      
      if (count($matches) == 3) {
        $num = (int) $matches[1];  
        $unit = $matches[2];
      
        
        switch ($unit) {
          
          case "s" : return $num;
          case "m" : return $num * 60;
          case "h" : return $num * 60 * 60;
          case "d" : return $num * 60 * 60 * 24;
          case "w" : return $num * 60 * 60 * 24 * 7;
          case "y" : return $num * 60 * 60 * 24 * 365;
          default  : return $num;
        }
        
      } else {

        // try the form hh:mm:ss
        
        if (preg_match("/(\d\d):(\d\d):(\d\d)/", $duration, $matches)) {

          $hours = (int) $matches[1];
          $minutes = (int) $matches[2];
          $seconds = (int) $matches[3];
          
          return ( $hours * 3600 ) + ($minutes * 60) + $seconds;

        } else if (preg_match("/(\d\d):(\d\d)/", $duration, $matches)) {
          
          $minutes = (int) $matches[1];
          $seconds = (int) $matches[2];

          return ($minutes * 60) + $seconds;
          
        }
        
        
        return 1;

      }

    }
    
    public function cache( $name, $value = null, $expires = 600, $json = false ) {
      
      if ($value != null) {

        if (is_string($expires)) {
          $expires = $this->seconds($expires);
        }
        
        if ($json) {
          $value = json_encode($value);
        }

        set_transient( $name, $value, $expires );
      } else {
        
        $value = get_transient( $name );
        
        if ($json) {
          $value = json_decode( $value, true );
        }
      
      }
      
      return $value;
      
    }
    
    public function uncache( $name ) {
      delete_transient( $name );
    }

    public function uncache_like( $key ) {
      global $wpdb;
      $sql = "DELETE FROM $wpdb->options WHERE option_name like '_transient_".$key."%' OR option_name LIKE '_transient_timeout_".$key."%'";
      $matches = $wpdb->query($sql);
    }
    
    
    public function user($id = null) {
      
      if (is_null($id)) {
        return $this->the_user();
      }
      
      if (is_object($id) && ( get_class($id) == $this->user_class || get_class($id) == $this->get_user_class() ) ) {
        return $id; // is already a user object
      }

      
      if (gettype($id) == "string") {
      
        // look for the user by login first
        
        $user = $this->user_by_login($id);
      
        if (!is_woof_silent($user)) {
          return $user;
        } else {
          // next try by email
          $user = $this->user_by_email($id);
          
          if (!is_woof_silent($user)) {
            return $user;
          }
        }
        
      } 
    
      // if we still haven't found it, try by ID
    
      if (is_int($id) || is_numeric($id)) {
        $user = $this->user_by_id($id);
      
        if (!is_woof_silent($user)) {
          return $user;
        }
      }
      
      return new WOOF_Silent( sprintf( __("There is no user for the ID %s", WOOF_DOMAIN ), $id ) );
    }
    
    
    public function user_by_id($id) {
      if ($info = get_userdata($id)) {
        return $this->wrap_user($info);
      }
        
      return new WOOF_Silent( sprintf( __("There is no user for the id %s", WOOF_DOMAIN), $id) );
    } 

    public function user_by_email($email) {
      if ($info = get_user_by("email", $email)) {
        return $this->wrap_user($info);
      }
        
      return new WOOF_Silent( sprintf( __("There is no user for the email %s", WOOF_DOMAIN), $email) );
    }

    public function user_by_login($login) {
      if ($info = get_user_by('login', $login)) {
        return $this->wrap_user($info);
      }

      return new WOOF_Silent( sprintf( __("There is no user for the login %s", WOOF_DOMAIN), $login) );
    }
    
    // a few convenience methods for getting attributes on the CURRENT page / post only
    
    function is($post, $type = null) {
      if ($type == null) {
        $type = "post";
      }
      
      return $this->post(null, $type)->is($post, $type);
    }

    function is_page($page) {
      return $this->post(null, "page")->is($page, "page");
    }
    
    function is_a($type) {
      return $this->object()->is_a($type);
    }

    function is_an($type) {
      return $this->object()->is_a($type);
    }
    
    function title() {
      return $this->object()->title();
    }

    function author($id = null) {
      
      if (is_null($id)) {
        return $this->the_author();
      }
      
      return $this->user($id);
    }

    function current_url() {
      return ( ( isset($_SERVER["HTTPS"]) && $_SERVER["HTTPS"]=="on") ? "https://" : "http://" ) . $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
    }
    
    function purl($url = null) {
      
      if (is_null($url)) {
        $url = $this->current_url();
      }
      
      if (WOOF_SSL) {
        return preg_replace("!http://!", "https://", $url);
      } else {
        return preg_replace("!https://!", "http://", $url);
      }

    }
    
    function surl($url = null) {
      
      if (is_null($url)) {
        $url = $this->current_url();
      }
      
      return preg_replace("!http://!", "https://", $url);

    }
    
    function permalink($encode = false) {
      $p = $this->object();
      
      if (is_woof_silent($p)) {
        
        return $this->current_url();
      }
      
      return $p->permalink($encode);
    }
    
    function id() {
      return $this->object()->id();
    }

    function url($root_relative = true) {
      $p = $this->object();
      
      if (is_woof_silent($p)) {
        if ($root_relative) {
          return WOOF::root_relative_url( $this->current_url() );
        } else {
          return $this->current_url();
        }
      }
      
      return $this->object()->url($root_relative);
    }

    public function comments_count() {
      return $this->post()->comments_count();
    }

    public function results_number($zero = "No Search Results", $one = "%d Search Result", $many = "%d Search Results") {
      global $wp_query;
      return WOOF::items_number($wp_query->found_posts, $zero, $one, $many);
    }

    public function results_count() {
      global $wp_query;
      $wp_query->found_posts;
    }
    
    public function taxonomies_for_type($type_name) {
      if (!isset($this->tft)) {
        $tft = array();
        
        foreach ($this->taxonomies() as $tax) {
          foreach ($tax->types() as $type) {
            
            $type_name = $type->name;
            
            if (!is_woof_silent($type_name)) {
              if (!isset($tft[$type_name])) {
                $tft[$type_name] = array();
              }
            
              $tft[$type_name][] = $tax;
            }
          
          }
          
        }

        $this->tft = array();
        
        foreach ($tft as $type => $taxonomies) {
          $this->tft[$type] = new WOOF_Collection($taxonomies);
        }
        
      }
      
      if (isset($this->tft[$type_name])) {
        return $this->tft[$type_name];
      }
      
      return new WOOF_Silent( sprintf( __( "No taxonomies for %s", WOOF_DOMAIN ), $type_name ) );
      
    }
    
    public function related_by_term($args = array()) {
      return $this->post()->related_by_term($args);
    }
    
    public function comments_number($zero = "", $one = "", $many = "") {
      return $this->post()->comments_number($zero, $one, $many);
    }

    public function comments_url($root_relative = false) {
      return $this->post()->comments_url();
    } 

    public function comments_link($args) {
      return $this->post()->comments_link($args);
    } 
  
    public function comments_open() {
      return $this->post()->comments_open();
    }

    function site_url($root_relative = false) {
      $url = get_bloginfo("url");
      
      if ($root_relative) {
        return WOOF::root_relative_url($url);
      }
      
      return $url;
    }
    
    function site_title() {
      return get_bloginfo("name");
    }
    
    function site_name() {
      return get_bloginfo("name");
    }

    function site_description() {
      return get_bloginfo("description");
    }

    function site_tagline() {
      return get_bloginfo("tagline");
    }
    
    
    function urlencode($root_relative = false) {
      return urlencode( $this->url($root_relative) );
    }

    function content($args = array("raw" => false)) {
      return $this->post()->content($args);
    }

    function raw_content($args = array()) {
      return $this->post()->raw_content($args);
    }

    function format_date($format, $timestamp = null) {
      return $this->date_format($format, $timestamp);
    }
    
    function date_format($format, $timestamp = null) {
      
      if (is_null($timestamp)) {
        $timestamp = time();
      }
      
      $df = $format;
      
      // replace long-form date parts with the equivalent PHP date format letters
      
      if ( preg_match_all("/\[([^\]]*)\]/", $format, $matches, PREG_SET_ORDER) ) {
        foreach ($matches as $match) {
          
          $key = $match[1];
          
          if (isset($this->date_map[$key])) {
            $df = str_replace( $match[0], $this->date_map[$key], $df );
          } else {
            $df = str_replace( $match[0], "", $df );
          }
          
        }
      }
      
      return date($df, $timestamp);

    }
    
    function date($format = NULL) {
      return $this->post()->date($format);
    }

    function modified($format = NULL) {
      return $this->post()->modified($format);
    }

    function excerpt($args = array()) {
      return $this->post()->excerpt($args);
    }

    function slug() {
      return $this->object()->slug();
    }

    function top() {
      return $this->object()->top();
    }

    function featured_image() {
      return $this->post()->featured_image();
    }
    
    function parent() {
      return $this->post()->parent();
    }

    function has_parent() {
      return $this->object()->has_parent();
    }

    function is_child_of($object, $object_type = null) {
      return $this->object()->is_child_of($object, $object_type);
    }

    function child_of($object, $object_type = null) {
      return $this->is_child_of($object, $object_type);
    }
    
    function children($args = array()) {
      return $this->object()->children($args);
    }

    function has_children() {
      return $this->object()->has_children();
    }
    
    function siblings($include_this = false) {
      return $this->object()->siblings($include_this);
    }
    
    function ancestors() {
      return $this->object()->ancestors();
    }

    function terms($taxonomy, $args = array()) {
      $terms = get_terms($taxonomy, wp_parse_args($args, array("hide_empty" => 0)));

      if (!is_wp_error($terms)) {
        return $this->wrap_terms($terms);
      }
      
      return $this->wrap_terms(array());
    }

    function terms_by_id($ids, $taxonomy = NULL) {
      global $wpdb, $wf;
      
      if (!is_array($ids)) {
        $ids = explode(",", $ids);
      }
  
      $results = array();
      
      if (count($ids)) {
        
        // check for composite IDs
        
        $ids_by_taxonomy = array();
        
        foreach ($ids as $id) {
          
          list($term_taxonomy, $term_id) = WOOF::parse_term_composite($id);
          
          if (!is_null($taxonomy)) {
            $term_taxonomy = $taxonomy;
          }
          
          if (!isset($ids_by_taxonomy[$term_taxonomy])) {
            $ids_by_taxonomy[$term_taxonomy] = array($term_id);
          } else {
            $ids_by_taxonomy[$term_taxonomy][] = $term_id;
          }
          
        }
        
        
        // build the where clause
        
        $where_parts = array();
        
        foreach ($ids_by_taxonomy as $tax_name => $ids) {
          
          $real_ids = array();
          
          foreach ($ids as $id) {
            if (is_numeric($id)) {
              $real_ids[] = $id;
            }
          }
          
          if (count($real_ids)) {
            
            $where = " ( t.term_id IN (".implode(",", $real_ids)." ) ";
          
            if ($tax_name != "*") {
              $where .= " AND tt.taxonomy = '".$tax_name."' ";
            }
          
            $where .= " ) ";
          
            $where_parts[] = $where;
          
          }
        
        }

        if (count($where_parts)) {
          $sql = "SELECT t.term_id, t.name, t.slug, t.term_group, tt.term_taxonomy_id, tt.count, tt.description, tt.parent, tt.taxonomy FROM $wpdb->terms t INNER JOIN $wpdb->term_taxonomy tt ON tt.term_id = t.term_id WHERE ( ".implode(" OR ", $where_parts)." ) ";
          $results = $wpdb->get_results($sql);
        }
      
      }
      
      return $wf->wrap_terms($results);
    }
    
    
    function get_sites($args = array()){
    // replacement for wp-includes/ms-deprecated.php#get_blog_list
    // see wp-admin/ms-sites.php#352
    //  also wp-includes/ms-functions.php#get_blogs_of_user
    //  also wp-includes/post-template.php#wp_list_pages
    	global $wpdb;

      if (!is_multisite()) {
        return array();
      }
      
    	$defaults = array(
    		'include_id'		=>'',			// includes only these sites in the results, comma-delimited
    		'exclude_id'		=>'',			// excludes these sites from the results, comma-delimted
    		'blogname_like'		=>'',			// domain or path is like this value
    		'ip_like'			=>'',			// Match IP address
    		'reg_date_since'	=>'',			// sites registered since (accepts pretty much any valid date like tomorrow, today, 5/12/2009, etc.)
    		'reg_date_before'	=>'',			// sites registered before
    		'include_user_id'	=>'',			// only sites owned by these users, comma-delimited
    		'exclude_user_id'	=>'',			// don't include sites owned by these users, comma-delimited
    		'include_spam'		=> false,		// Include sites marked as "spam"
    		'include_deleted'	=> false,		// Include deleted sites
    		'include_archived'	=> false,		// Include archived sites
    		'include_mature'	=> false,		// Included blogs marked as mature
    		'public_only'		=> true,		// Include only blogs marked as public
    		'sort_column'		=> 'registered',// or registered, last_updated, blogname, site_id.
    		'order'				=> 'asc',		// or desc
    		'limit_results'		=> '',			// return this many results
    		'start'				=> ''			// return results starting with this item
    	);
    	
    	
    	if( !function_exists('make_email_list_by_user_id')){
    		function make_email_list_by_user_id($user_ids){
    			$the_users = explode(',',$user_ids);
    			$the_emails = array();
    			foreach( (array) $the_users as $user_id){
    				$the_user = get_userdata($user_id);
    				$the_emails[] = $the_user->user_email;
    			}
    			return $the_emails;
    		}
    	}

    	// array_merge
    	$r = wp_parse_args( $args, $defaults );
    	extract( $r, EXTR_SKIP );

      if (isset($orderby)) {
        $sort_column = $orderby;
      }
      
    	$query = "SELECT b.*, 1.`email`, 1.`ip` FROM {$wpdb->blogs} as b ";
    	$query .= "LEFT JOIN {$wpdb->registration_log} as l ON b.`blog_id` = l.`blog_id` ";
    	$query .= "WHERE b.`site_id` = '{$wpdb->siteid}' ";

    	if ( !empty($include_id) ) {
    		$list = implode("','", explode(',', $include_id));
    		$query .= " AND b.blog_id IN ('{$list}') ";
    	}
    	if ( !empty($exclude_id) ) {
    		$list = implode("','", explode(',', $exclude_id));
    		$query .= " AND b.blog_id NOT IN ('{$list}') ";
    	}
    	if ( !empty($blogname_like) ) {
    		$query .= " AND ( b.domain LIKE '%".$blogname_like."%' OR b.path LIKE '%".$blogname_like."%' ) ";
    	}
    	if ( !empty($ip_like) ) {
    		$query .= " AND l.IP LIKE '%".$ip_like."%' ";
    	}
    	if( !empty($reg_date_since) ){
    		$query .= " AND unix_timestamp(b.date_registered) > '".strtotime($reg_date_since)."' ";
    	}
    	if( !empty($reg_date_before) ){
    		$query .= " AND unix_timestamp(b.date_registered) < '".strtotime($reg_date_before)."' ";
    	}
    	if ( !empty($include_user_id) ) {
    		$the_emails = make_email_list_by_user_id($include_user_id);
    		$list = implode("','", $the_emails);
    		$query .= " AND l.email IN ('{$list}') ";
    	}
    	if ( !empty($exclude_user_id) ) {
    		$the_emails = make_email_list_by_user_id($include_user_id);
    		$list = implode("','", $the_emails);
    		$query .= " AND l.email NOT IN ('{$list}') ";
    	}
    	if ( !empty($ip_like) ) {
    		$query .= " AND l.IP LIKE ('%".$ip_like."%') ";
    	}

      if ($public_only) {
    	  $query .= " AND b.public = '1'";
      }
      
    	$query .= " AND b.archived = ". (($include_archived) ? "'1'" : "'0'");
    	$query .= " AND b.mature = ". (($include_mature) ? "'1'" : "'0'");
    	$query .= " AND b.spam = ". (($include_spam) ? "'1'" : "'0'");
    	$query .= " AND b.deleted = ". (($include_deleted) ? "'1'" : "'0'");

    	if ( $sort_column == 'site_id' ) {
    		$query .= ' ORDER BY b.`blog_id` ';
    	} elseif ( $sort_column == 'lastupdated' ) {
    		$query .= ' ORDER BY b.`last_updated` ';
    	} elseif ( $sort_column == 'blogname' ) {
    		$query .= ' ORDER BY b.`domain` ';
    	} else {
    		$sort_column = 'registered';
    		$query .= " ORDER BY b.`registered` ";
    	}

    	$order = ( 'desc' == $order ) ? "DESC" : "ASC";
    	$query .= $order;

    	$limit = '';
    	if( !empty($limit_results) ){
    		if( !empty($start) ){
    			$limit = $start." , ";
    		}
    		$query .= "LIMIT ".$limit.$limit_results;
    	}
      
    	$results = $wpdb->get_results($query);

    	return $results;	
    }
    
    
    
    function pagination($args = array()) {
      /* Based on code from Sparklette studios http://design.sparklette.net/teaches/how-to-add-wordpress-pagination-without-a-plugin/# */

      $html = "";
      
      global $wp_query;

      $r = wp_parse_args( 
        $args,
        array(
          "pages" => '', 
          "page_var" => "paged",
          "range" => 5,
          
          "link_callback" => "get_pagenum_link",
          "class_page" => "page",

          "class_current" => "current",
          "class_inactive" => "inactive",
          "class_wrap" => "pagination",
          "tag_wrap" => "nav",

          "tag_wrap_pages" => "div",
          "class_pages" => "pages",
          
          "show_page_count" => true,
          "tag_page_count" => "span",
          "class_page_count" => "page-count",
          "t_page_count" => __("Page %d of %d", WOOF_DOMAIN),
          
          "show_first" => true,
          "class_first" => "first",
          "title_first" => __("First Page", WOOF_DOMAIN),
          "t_first" => __("&laquo; First", WOOF_DOMAIN),
          
          "show_previous" => true,
          "class_previous" => "previous",
          "title_previous" => __("Previous Page", WOOF_DOMAIN),
          "t_previous" => __("&lsaquo; Previous", WOOF_DOMAIN),

          "show_next" => true,
          "class_next" => "next",
          "title_next" => __("Next Page", WOOF_DOMAIN),
          "t_next" => __("Next &rsaquo;", WOOF_DOMAIN),
          
          "show_last" => true,
          "class_last" => "last",
          "title_last" => __("Last Page", WOOF_DOMAIN),
          "t_last" => __("Last &raquo;", WOOF_DOMAIN)
        )
      );

      
      extract($r);

      $showitems = ($range * 2) + 1;  
      
      $paged = get_query_var($r["page_var"]);
      
      if (!$paged) $paged = 1;

      if ($pages == '') {

        $pages = $wp_query->max_num_pages;

        if (!$pages) {
          $pages = 1;
        }

      }   

      if (1 != $pages) {
        
        if ($show_page_count) {
          $html .= WOOF_HTML::tag($tag_page_count, array("class" => $class_page_count), sprintf($t_page_count, $paged, $pages));
        }
        

        $limit = !($paged > 2 && $paged > $range + 1 && $showitems < $pages);
        
        if ($show_first && ($show_first == "always" || !$limit)) {
          if ($paged == 1) {
            $html .= WOOF_HTML::tag("span", array("class" => $class_first), $t_first);
          } else {
            $href = call_user_func( $r["link_callback"], 1 );
            $html .= WOOF_HTML::tag("a", array("href" => $href, "title" => $title_first, "class" => $class_first), $t_first);
          }
        }

        $limit = !($paged > 1 && $showitems < $pages);
        
        if ($show_previous && ($show_previous == "always" || !$limit)) {
          if ($paged == 1) {
            $html .= WOOF_HTML::tag("span", array("class" => $class_previous), $t_previous);
          } else {
            $href = call_user_func( $r["link_callback"], $paged - 1 );
            $html .= WOOF_HTML::tag("a", array("href" => $href, "title" => $title_previous, "class" => $class_previous), $t_previous);
          }
        }

        $html .= WOOF_HTML::open($tag_wrap_pages, array("class" => $class_pages));

        $pc = 0;

        for ($i=1; $i <= $pages; $i++) {
          if (1 != $pages && ( !($i >= $paged + $range + 1 || $i <= $paged - $range - 1) || $pages <= $showitems )) {
            $pc++;
            
            $class = array($class_page);
            
            if ($paged == $i) {
              $class[] = $class_current;
              $html .= WOOF_HTML::tag("span", array("class" => implode(" ", $class)), $i);
              
            } else {
              $class[] = $class_inactive;
              $href = call_user_func( $r["link_callback"], $i );
              $html .= WOOF_HTML::tag("a", array("href" => $href, "class" => implode(" ", $class)), $i);
            }
            
            
          }
        }

        $html = WOOF_HTML::open($tag_wrap, array("class" => $class_wrap." with-$pc-pages $class_wrap-with-$pc-pages")).$html;
        
        $html .= WOOF_HTML::close($tag_wrap_pages);

        $limit = !( $paged < $pages && $showitems < $pages );
        
        if ($show_next && ($show_next == "always" || !$limit)) {
          
          if ($pages == $paged) {
            $html .= WOOF_HTML::tag("span", array("class" => $class_next), $t_next);
          } else {
            $href = call_user_func( $r["link_callback"], $paged + 1 );
            $html .= WOOF_HTML::tag("a", array("href" => $href, "title" => $title_next, "class" => $class_next), $t_next);
          }
        
        }

        $limit = !($pages - 1 && $paged + $range - 1 < $pages && $showitems < $pages);
        
        if ($show_last && ($show_last == "always" || !$limit)) {
          if ($pages == $paged) {
            $html .= WOOF_HTML::tag("span", array("class" => $class_last), $t_last);
          } else {
            $href = call_user_func( $r["link_callback"], $pages );
            $html .= WOOF_HTML::tag("a", array("href" => $href, "title" => $title_last, "class" => $class_last), $t_last);
          }
        }

        
        $html .= WOOF_HTML::close($tag_wrap);
    
      }
      
      return $html;
      
    }


    public function get_post_class() {
      //return $this->post_class;
      
      $custom = $this->class_prefix."_Post";
      
      if (!isset($this->_classes["post"])) {
        $this->_classes["post"] = class_exists($custom, false) && is_subclass_of($custom, "WOOF_Post") ? $custom : $this->post_class;
      }
      
      return $this->_classes["post"];
      
    }

    public function get_term_class() {
      $custom = $this->class_prefix."_Term";

      if (!isset($this->_classes["term"])) {
        $this->_classes["term"] = class_exists($custom, false) && is_subclass_of($custom, "WOOF_Term") ? $custom : $this->term_class;
      }
      
      return $this->_classes["term"];
    }

    public function get_post_type_class() {
      $custom = $this->class_prefix."_PostType";

      if (!isset($this->_classes["post_type"])) {
        $this->_classes["post_type"] = class_exists($custom, false) && is_subclass_of($custom, "WOOF_PostType") ? $custom : $this->post_type_class;
      }
      
      return $this->_classes["post_type"];
    }


    public function get_user_class() {
      $custom = $this->class_prefix."_User";

      if (!isset($this->_classes["user"])) {
        $this->_classes["user"] = class_exists($custom, false) && is_subclass_of($custom, "WOOF_User") ? $custom : $this->user_class;
      }
      
      return $this->_classes["user"];
    }

    public function get_role_class() {
      $custom = $this->class_prefix."_Role";

      if (!isset($this->_classes["role"])) {
        $this->_classes["role"] = class_exists($custom, false) && is_subclass_of($custom, "WOOF_Role") ? $custom : $this->role_class;
      }
      
      return $this->_classes["role"];
    }

    public function get_comment_class() {
      $custom = $this->class_prefix."_Comment";

      if (!isset($this->_classes["comment"])) {
        $this->_classes["comment"] = class_exists($custom, false) && is_subclass_of($custom, "WOOF_Comment") ? $custom : $this->comment_class;
      }
      
      return $this->_classes["comment"];
    }

    public function get_attachment_class() {
      $custom = $this->class_prefix."_Attachment";

      if (!isset($this->_classes["attachment"])) {
        $this->_classes["attachment"] = class_exists($custom, false) && is_subclass_of($custom, "WOOF_Attachment") ? $custom : $this->attachment_class;
      }
      
      return $this->_classes["attachment"];
    }

    public function get_taxonomy_class() {
      $custom = $this->class_prefix."_Taxonomy";

      if (!isset($this->_classes["taxonomy"])) {
        $this->_classes["taxonomy"] = class_exists($custom, false) && is_subclass_of($custom, "WOOF_Taxonomy") ? $custom : $this->taxonomy_class;
      }
      
      return $this->_classes["taxonomy"];
    }

    public function get_site_class() {
      $custom = $this->class_prefix."_Site";

      if (!isset($this->_classes["site"])) {
        $this->_classes["site"] = class_exists($custom, false) && is_subclass_of($custom, "WOOF_Site") ? $custom : $this->site_class;
      }
      
      return $this->_classes["site"];
    }

    public function get_image_class() {
      $custom = $this->class_prefix."_Image";

      if (!isset($this->_classes["image"])) {
        $this->_classes["image"] = class_exists($custom, false) && is_subclass_of($custom, "WOOF_Image") ? $custom : $this->image_class;
      }
      
      return $this->_classes["image"];
    }

    public function get_file_class() {
      $custom = $this->class_prefix."_File";

      if (!isset($this->_classes["file"])) {
        $this->_classes["file"] = class_exists($custom, false) && is_subclass_of($custom, "WOOF_File") ? $custom : $this->file_class;
      }
      
      return $this->_classes["file"];
    }
        
        
    public function add_filter($arg1, $arg2, $priority = 10) {
      if (class_exists($arg1) && is_subclass_of($arg1, "WOOF_Hook")) {
        call_user_func_array(array($arg1, "filter"), array($arg2, $arg1, $priority));
      } else {
        
        // determine the function

        $hook = $arg2;

        $params = 1;

        if ($arg2 === true) {
          $hook = "__return_true";
        } else if ($arg2 === false) {
          $hook = "__return_false";
        } else {

          if (preg_match("/([A-Za-z\_0-9]+)(?:\:\:|\.|\-\>)([A-Za-z\_0-9]+)/", $arg2, $matches)) {
            $class = $matches[1];
            $method = $matches[2];

            if (method_exists($class, $method)) {
              $hook = array($class, $method);

              $p = new ReflectionMethod($class, $method); 

              if ($p) {
                $params = $p->getNumberOfParameters();
              }

            }

          } else {

            if (class_exists($arg2) && is_string($arg1)) {
              
              // if it's just a class, we'll look for a method with the same name as that hook IN the class
              $method = $arg1;
              $class = $arg2;
              
              if (method_exists($class, $method)) {
                
                $hook = array($class, $method);

                $p = new ReflectionMethod($class, $method); 

                if ($p) {
                  $params = $p->getNumberOfParameters();
                }

              }
              
              
            } else {
            
              $hook = $arg2;
            
              $p = new ReflectionFunction($hook); 

              if ($p) {
                $params = $p->getNumberOfParameters();
              }
              
            }
            
          }

        }


        if (!is_array($arg1)) {
          $arg1 = explode(",", $arg1);
        }

        foreach ($arg1 as $name) {
          $hook_name = trim($name);
          add_filter($hook_name, $hook, $priority, $params);
        }


        
      }
    }
      
  }
  
  
  global $wf;
  
  /* -- Instantiate the API for the front-end -- */

  if(empty($wf)) {
    $wf = new WOOF();
  }

  class WOOF_Handlers {
  
    public static function setup() {
      global $wf;

      // setup custom post class event handlers

      $post_class = $wf->get_post_class();

      if (method_exists($post_class, "on_save")) {
        add_action( "save_post", array("WOOF_Handlers", "save_post") ); 
      }

      if (method_exists($post_class, "after_save_meta")) {
        add_action( "mp_after_save_meta", array("WOOF_Handlers", "mp_after_save_meta"), 10, 2 ); 
      }
  
    }
  
    function save_post( $post_id ) {
      global $wf;
      $wf->post($post_id)->on_save();
    }

    function mp_after_save_meta( $object_id, $object_type ) {
      global $wf;
      
      if ($object_type == "post") {
        $wf->post($object_id)->after_save_meta();
      }
    
    }
  
  }

  class WOOF_Hook {
    
    public static function filter($names, $class, $priority = 10) {
      
      if (!is_array($names)) {
        $names = explode(",", $names);
      }
      
      foreach ($names as $name) {
        $mn = trim($name);

        if (method_exists($class, $mn)) {
          
          // infer the number of params
          $p = new ReflectionMethod($class, $mn); 
          
          $args = 1;
          
          if ($p) {
            $args = $p->getNumberOfParameters();
          }
          
          add_filter($mn, array($class, $mn), $args, $priority);
        }
        
      }
      
    }
    
  }

} // endif class_exists


/**
 * Returns the global $wf object, removing the need to globalise it inside functions
 *
 * @return WOOF
 * @author Travis Hensgen
 */

function wf() {
	global $wf;
	return $wf;
}


function woof_remember($name) {
  WOOF::remember();
  return $name;
}

function woof_init() {
  
  global $wf;

  woof_remember("");
  
  if (defined("MASTERPRESS_DOMAIN")) { // this is part of MasterPress
    load_plugin_textdomain( WOOF_DOMAIN, false, "core/api/woof/languages" );
  } else {

    // create the directories
    
    wp_mkdir_p(WOOF_CONTENT_IMAGE_CACHE_DIR);
    wp_mkdir_p(WOOF_CONTENT_IMAGE_FROM_URL_DIR);
    wp_mkdir_p(WOOF_CONTENT_FILE_FROM_URL_DIR);

    load_plugin_textdomain( WOOF_DOMAIN, false, "woof/languages" );
  }

  WOOF_Handlers::setup();
  
}

  
add_action('init', "woof_init");
add_action('template_redirect', "woof_remember");
add_filter('parse_query', array("WOOF", "parse_query"));

} // if (!class_exists("WOOF"))
