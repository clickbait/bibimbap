<?php

class WOOF_File extends WOOF_Wrap {

  
  protected $path;
  protected $url;
  protected $filename;
  protected $pathinfo;
  protected $external_url;

  public static function infer_content_path($url) {
    
    global $wf;
    
    $url = $wf->purl($url);
    
		$blogs_dir = false;
		$sites = false;
		
    // remove any folders before wp-content, to allow multisite URLs to work correctly
    
    if (preg_match("/wp-content\/blogs.dir/", $url)) {
			$blogs_dir = true;
      $url = preg_replace("/\/[_0-9a-zA-Z-]+\/wp-content\/blogs.dir/", "/wp-content/blogs.dir", $url); // ignore multisite URLs
      $content_url = preg_replace("/\/[_0-9a-zA-Z-]+\/wp-content/", "/wp-content", WF_CONTENT_URL); // ignore multisite URLs
    } else if (preg_match("/wp-content\/uploads\/sites/", $url)) {
			$sites = true;
			$url = preg_replace("/\/[_0-9a-zA-Z-]+\/wp-content\/uploads\/sites/", "/wp-content/uploads/sites", $url);
      $content_url = preg_replace("/\/[_0-9a-zA-Z-]+\/wp-content/", "/wp-content", WF_CONTENT_URL); // ignore multisite URLs
		} else {
      $content_url = WF_CONTENT_URL;
    }
    
    $pos = strpos($url, WF_CONTENT_URL);
    
    if ($pos === false && !$blogs_dir && !$sites) {
      
      // custom upload directory?
			
      $ud = wp_upload_dir();
      $url_path = str_replace($ud["baseurl"], "", $url);
      
      if ($url_path == $url) {
        // this URL is external
        return "";
      }
      
      $path = $ud["basedir"] . str_replace("/", WOOF_DIR_SEP, $url_path);
      
    } else {
      $url_path = str_replace($content_url, "", $url);
      $path = WP_CONTENT_DIR . str_replace("/", WOOF_DIR_SEP, $url_path);
    }
    
    return $path;
  }

  
  public static $filesizes = array();
  private $filesize;
  private $mime;
  
  function __construct($path, $url) {
    $this->path = $path;
    $this->url = $url;
    $this->pathinfo = pathinfo($this->path);
  }
  
  function is_external() {
    return isset($this->external_url);
  }
  
  function set_external_url($url) {
    $this->external_url = $url;
  }
  
  function debug_data() {
    return $this->info();
  }
  
  function filename() {
    return $this->pathinfo["filename"];
  }

  function extension($with_period = false) {
    return ($with_period ? "." : "").$this->pathinfo["extension"];
  }
  
  function basename() {
    return $this->pathinfo["basename"];
  }
  
  function name() {
    return $this->pathinfo["basename"];
  }

  function path() {
    return $this->path; 
  }
  
  function filepath() {
    return $this->path; 
  }  
  
  function filesize($unit = "AUTO", $with_unit = true, $sep = "&nbsp;", $base = 1024) {
    return self::get_filesize($this->path, $unit, $with_unit, $sep, $base);
  }

  function size($unit = "AUTO", $with_unit = true, $sep = "&nbsp;", $base = 1024) {
    return self::get_filesize($this->path, $unit, $with_unit, $sep, $base);
  }

  function size_json($unit = "AUTO", $with_unit = true, $sep = " ", $base = 1024) {
    return self::get_filesize($this->path, $unit, $with_unit, $sep, $base);
  }

  function bytes() {
    return filesize($this->filepath());
  }

  function filesizeinbytes() {
    return filesize($this->filepath());
  }
  
  function blank() {
    return !$this->exists();
  }
  
  function contents() {
    $handle = fopen($this->path, "r");
    $contents = fread($handle, $this->bytes());
    fclose($handle);
    return $contents;
  }
  
  function render($data = array(), $strip_whitespace = false) {
    return WOOF::render_template($this->contents(), array_filter( $data, "is_not_woof_silent" ), $strip_whitespace );
  }
  
  public function info() {
    
    $info = array(
      "url" => $this->url(),
      "absolute_url" => $this->url(false),
      "size" => $this->filesize(),
      "size_in_bytes" => $this->filesizeinbytes(),
      "filetype" => $this->filetype(),
      "short_filetype" => $this->short_filetype(),
      "filename" => $this->filename(),
      "basename" => $this->basename(),
      "extension" => $this->extension(),
      "modified" => $this->modified("c"),
      "accessed" => $this->accessed("c")
    );
    
    return $info;
  }
  

  function filetype() {
    $type = self::$file_types[strtolower($this->extension())];
    
    if ($type) {
      return $type;
    }
  
    return __("Unknown File Type", WOOF_DOMAIN);
  }
  
  function short_filetype() {
    $type = self::$short_file_types[strtolower($this->extension())];
    
    if ($type) {
      return $type;
    }
  
    return __("Unknown Type", WOOF_DOMAIN);
  }
  
  function json() {
    
    $json = array();
    
    if ($this->exists()) {
      $json["href"] = $this->url();
    }
    
    $json["size"] = $this->size("AUTO", true, "");
    $json["bytes"] = $this->bytes();
    
    return $json;
  }
	
	
  function url($root_relative = false) {
    global $wf;
    
    $u = $this->url;
    
    if ($root_relative) {
      return WOOF::root_relative_url($u);
    } else {
      return $wf->purl($u);
    }
  }
  
  function surl() {
    global $wf;
    return $wf->surl($this->url);
  }
  
  function relative_url() {
    return $this->url(true);
  }

  function absolute_url() {
    return $this->url(false);
  }

  function abs() {
    return $this->url(false);
  }

  function __toString() {
    return $this->link();
  }
  
  function link($args = array()) {
    // get an <a> tag linking to this page.
    // by default, the text of the link will be the page title
    // which is highly convenient!
    
    $defaults = array(
      'text' => $this->name(),
      'root_relative' => false
    );
    
    $r = wp_parse_args( $args, $defaults );
    
    if (!isset($r["href"])) {
      if ($r["root_relative"]) {
        $r["href"] = $this->url(true);
      } else {
        $r["href"] = $this->permalink();
      }
    }
    
    unset($r["root_relative"]);

    $tag = "<a ";
    
    foreach ($r as $key => $value) {
      if ($key != "text") {
        $tag .= ' '.$key.'="'.esc_attr($value).'"';
      }
    }
    
    $tag .= '>'.$r['text'].'</a>';

    return $tag;
  }
  
  function enclosure($attr = array()) {
    
    $a = wp_parse_args($attr);
    
    $a["length"] = $this->bytes();
    $a["type"] = $this->mime();
    $a["url"] = $this->permalink();
    
    return WOOF_HTML::tag("enclosure", $a, null, true, false);
    
  }
  
    
  function permalink() {
    // synonym for url, but assuming root_relative URLs are NOT desired (as is the definition of "permalink")
    return $this->url(false);
  }

  function delete() {
    if ($this->exists()) {
      return unlink($this->path());
    }
  }
  
  function unlink() {
    return $this->delete();
  }
  
  function mime() {
    if (!$this->mime) {
      if (function_exists('mime_content_type')) {
        $this->mime = mime_content_type($this->filepath());
      } else {

        // for now, at least return image types
        
        $simple_map = array(
          'jpeg' => 'image/jpeg',
          'jpg' => 'image/jpeg',
          'png' => 'image/png',
          'gif' => 'image/gif',
          'bmp' => 'image/bmp',
          'tiff' => 'image/tiff'
        );
        
        $ext = $this->extension();
        
        if (isset($simple_map[$ext])) {
          $this->mime = $simple_map[$ext];
        } else {
          $this->mime = 'application/octet-stream';
        }
      }
    
    }
    
    return $this->mime;
  }
 
  function exists() {
    if (file_exists($this->path)) {
      return $this;
    } 
    
    return false;
  }
  
  function modified($format = null) {
    global $wf;
    
    $time = filemtime($this->path);
    
    if (!is_null($format)) {
      return $wf->date_format($format, $time);
    }
    
    return $time;
  }
  
  function changed($format = null) {
    global $wf;
    
    $time = filectime($this->path);
    
    if (!is_null($format)) {
      return $wf->date_format($format, $time);
    }
    
    return $time;  
  }

  function accessed($format = null) {
    global $wf;
    
    $time = fileatime($this->path);
    
    if (!is_null($format)) {
      return $wf->date_format($format, $time);
    }
    
    return $time;  
  }
  
  
  public static function get_filesize($path, $unit = "AUTO", $with_unit = TRUE, $sep = "&nbsp;", $base = 1024) {
    if (!array_key_exists($path, self::$filesizes)) {
      self::$filesizes[$path] = filesize($path);
    }
    
    $size = self::$filesizes[$path];
    
    $runit = $unit;
    
    return self::format_filesize($size, $unit, $with_unit, $sep, $base);
  }
  
  public static function format_filesize($size, $unit, $with_unit = TRUE, $sep = "&nbsp;", $base = 1024) {
    
    $size = trim(self::to_bytes($size));
    
    $ret = $size;
    
    $runit = $unit;
    
    if ($runit == "AUTO") {
      // smart formatting. If the file size is greater than 1K, show in KB, > 1MB show in MB etc
      if ($ret > $base * $base * $base) { // > 1GB
        $runit = "GB";
      } else if ($ret > $base * $base) { // > 1MB
        $runit = "MB";
      } else {
        $runit = "KB";
      } 
    }
    
    if ($runit == "KB") {
      $ret = round($ret / $base, 2);
    } else if ($runit == "MB") {
      $ret = round($ret / $base / $base, 2);
    } else if ($runit == "GB") {
      $ret = round($ret / $base / $base / $base, 2);
    }
    
    if ($with_unit) {
      $ret .= $sep.$runit;
    }
    
    return $ret;
  }
  
  public static function to_bytes($str){
    $val = trim($str);
    $last = strtolower($str[strlen($str)-1]);

    switch($last) {
        case 'g': $val *= 1024;
        case 'm': $val *= 1024;
        case 'k': $val *= 1024;        
    }

    return $val;
  }

  public static function file_type_categories() {
    return array(
    __("Office and Productivity", WOOF_DOMAIN) => array("doc", "docx", "numbers", "keynote", "mdb", "pages", "pdf", "pps", "ppt", "pptx", "wks", "xls", "xlsx"),
    __("Text / Data Files", WOOF_DOMAIN) => array("csv", "dtd", "md", "markdown", "txt", "rtf", "vcf", "xml" ), 
    __("Design and Creative", WOOF_DOMAIN) => array( "ai", "css", "eps", "fla", "psd", "pspimage", "swf" ),
    __("Archive Files", WOOF_DOMAIN) => array( "7z", "gz", "rar", "tar-gz", "zip" ),
    __("Audio and Music", WOOF_DOMAIN) => array( "aac", "aif", "aiff", "m3u", "m4a", "mid", "mp3", "mp4", "mpa", "wav", "wma" ),
    __("Image Files", WOOF_DOMAIN) => array( "bmp", "gif", "jpeg", "jpg", "png", "tif", "tiff" ),
    __("Video Files", WOOF_DOMAIN) => array( "avi", "flv", "m4v", "mkv", "mov", "mpg", "mpeg", "wmv" ),
    __("Camera RAW Files", WOOF_DOMAIN) => array( "3fr","ari","arw","srf","sr2","bay","crw","cr2","cap","iiq","eip","dcs","dcr","drf","k25","kdc","dng","erf","fff","mef","mos","mrw","nef","nrw","orf","pef","ptx","pxn","R3D","raf","raw","rw2","raw","rwl","dng","rwz","srw","x3f" )
    );
  }
      
  public static $file_types = array(
   "3dm"      => "Rhino 3D Model",
   "3g2"      => "3GPP2 Multimedia",
   "3gp"      => "3GPP Multimedia",
   "7z"       => "7-Zip Compressed",
   "8bi"      => "Photoshop Plug-in",
   "accdb"    => "Access 2007 Database",
   "aac"      => "Advanced Audio Codec File",
   "ai"       => "Adobe Illustrator File",
   "aif"      => "Audio Interchange File Format",
   "aiff"     => "Audio Interchange File Format",
   "app"      => "Mac OS X Application",
   "as"       => "ActionScript Source Code",
   "asf"      => "Advanced Systems Format",
   "asp"      => "Active Server Page",
   "asx"      => "Microsoft ASF Redirector",
   "avi"      => "Audio Video Interleave",
   "bak"      => "Backup",
   "bat"      => "DOS Batch",
   "bin"      => "Macbinary Encoded",
   "bmp"      => "Bitmap Image",
   "c"        => "C/C++ Source Code",
   "cab"      => "Windows Cabinet",
   "cer"      => "Internet Security Certificate",
   "cfg"      => "Configuration",
   "cgi"      => "Common Gateway Interface Script",
   "class"    => "Java Class",
   "com"      => "DOS Command",
   "cpl"      => "Windows Control Panel Item",
   "cpp"      => "C++ Source Code",
   "cs"       => "Visual C# Source Code",
   "csr"      => "Certificate Signing Request",
   "css"      => "Cascading Style Sheet",
   "csv"      => "Comma Separated Values",
   "cur"      => "Windows Cursor",
   "dat"      => "Data",
   "db"       => "Database",
   "dbf"      => "Database",
   "dbx"      => "Outlook Express E-mail Folder",
   "deb"      => "Debian Software Package",
   "dll"      => "Dynamic Link Library",
   "dmg"      => "Mac OS X Disk Image",
   "dmp"      => "Windows Memory Dump",
   "doc"      => "Microsoft Word Document",
   "docx"     => "Microsoft Word Open XML Document",
   "drv"      => "Device Driver",
   "drw"      => "Drawing",
   "dtd"      => "Document Type Definition",
   "dwg"      => "AutoCAD Drawing Database",
   "dxf"      => "Drawing Exchange Format",
   "efx"      => "eFax Document",
   "eps"      => "Encapsulated PostScript",
   "exe"      => "Windows Executable",
   "fla"      => "Adobe Flash Animation",
   "flv"      => "Flash Video",
   "fnt"      => "Windows Font",
   "fon"      => "Generic Font",
   "gadget"   => "Windows Gadget",
   "gam"      => "Saved Game",
   "gho"      => "Norton Ghost Backup",
   "gif"      => "Graphical Interchange Format",
   "gpx"      => "GPS Exchange",
   "gz"       => "Gnu Zipped Archive",
   "hqx"      => "BinHex 4.0 Encoded",
   "htm"      => "Hypertext Markup Language",
   "html"     => "Hypertext Markup Language",
   "iff"      => "Interchange File Format",
   "indd"     => "Adobe InDesign File",
   "ini"      => "Initialization File",
   "iso"      => "Disc Image",
   "jar"      => "Java Archive",
   "java"     => "Java Source Code",
   "jpg"      => "JPEG Image",
   "jpeg"     => "JPEG Image",
   "js"       => "JavaScript",
   "jsp"      => "Java Server Page",
   "keynote"  => "Apple Keynote Presentation",
   "keychain" => "Mac OS X Keychain",
   "kml"      => "Keyhole Markup Language",
   "lnk"      => "File Shortcut",
   "log"      => "Log",
   "m"        => "Objective-C Implementation",
   "m3u"      => "Media Playlist",
   "m4a"      => "MPEG-4 Audio",
   "m4v"      => "iTunes Video",
   "max"      => "3ds Max Scene",
   "md"       => "Markdown Text File",
   "markdown" => "Markdown Text File",
   "mdb"      => "Microsoft Access Database",
   "mid"      => "MIDI",
   "mim"      => "Multi-Purpose Internet Mail Message",
   "mkv"      => "Matroska Video File",
   "mov"      => "Apple QuickTime Movie",
   "mp3"      => "MP3 Audio",
   "mp4"      => "MPEG-4 Video",
   "mpa"      => "MPEG-2 Audio",
   "mpg"      => "MPEG Video",
   "mpeg"     => "MPEG Video",
   "msg"      => "Microsoft Outlook Mail Message",
   "msi"      => "Windows Installer Package",
   "nes"      => "Nintendo (NES) ROM",
   "numbers"  => "Apple Numbers Spreadsheet",
   "ori"      => "Original",
   "otf"      => "OpenType Font",
   "pages"    => "Apple Pages Document",
   "part"     => "Partially Downloaded",
   "pct"      => "Picture",
   "pdb"      => "Program Database",
   "pdf"      => "PDF Document",
   "php"      => "PHP Script",
   "pif"      => "Program Information",
   "pkg"      => "Mac OS X Installer Package",
   "pl"       => "Perl Script",
   "plugin"   => "Mac OS X Plug-in",
   "png"      => "Portable Network Graphic",
   "pps"      => "PowerPoint Slide Show",
   "ppt"      => "PowerPoint Presentation",
   "pptx"     => "PowerPoint Open XML Presentation",
   "prf"      => "Outlook Profile",
   "ps"       => "PostScript",
   "psd"      => "Adobe Photoshop File",
   "pspimage" => "PaintShop Photo Pro Image",
   "py"       => "Python Script",
   "qxd"      => "QuarkXPress Document",
   "qxp"      => "QuarkXPress Project",
   "ra"       => "Real Audio",
   "rar"      => "RAR Compressed Archive",
   "rels"     => "Open Office XML Relationships",
   "rm"       => "Real Media",
   "rom"      => "N64 Game ROM",
   "rpm"      => "Red Hat Package Manager",
   "rss"      => "Rich Site Summary",
   "rtf"      => "Rich Text Format",
   "sav"      => "Saved Game",
   "sdf"      => "Standard Data",
   "sit"      => "Stuffit Archive",
   "sitx"     => "Stuffit X Archive",
   "sql"      => "Structured Query Language Data",
   "svg"      => "Scalable Vector Graphics",
   "swf"      => "Shockwave Flash Movie",
   "sys"      => "Windows System",
   "tar-gz"   => "Tarball",
   "thm"      => "Thumbnail Image",
   "tif"      => "TIFF Image",
   "tiff"     => "TIFF Image",
   "tmp"      => "Temporary",
   "toast"    => "Toast Disc Image",
   "torrent"  => "BitTorrent",
   "ttf"      => "TrueType Font",
   "txt"      => "Plain Text",
   "uue"      => "Uuencoded",
   "vb"       => "VBScript",
   "vcd"      => "Virtual CD",
   "vcf"      => "vCard",
   "vob"      => "DVD Video Object",
   "wav"      => "WAVE Audio",
   "wks"      => "MS Works Spreadsheet",
   "wma"      => "Windows Media Audio",
   "wmv"      => "Windows Media Video",
   "wpd"      => "WordPerfect Document",
   "wps"      => "MS Works Document",
   "wsf"      => "Windows S cript",
   "xhtml"    => "Extensible Hypertext Markup Language",
   "xll"      => "Excel Add-In",
   "xls"      => "Excel Spreadsheet",
   "xlsx"     => "Microsoft Excel Open XML Spreadsheet",
   "xml"      => "XML",
   "yuv"      => "YUV Encoded Image",
   "zip"      => "Zip Archive",
   "zipx"     => "Extended Zip",
   
   "3fr"      => "Hasselblad Raw Image",
   "ari"      => "Arriflex Raw Image",
   "arw"      => "Sony Raw Image",
   "srf"      => "Sony Raw Image",
   "sr2"      => "Sony Raw Image",
   "bay"      => "Casio Raw Image",
   "crw"      => "Canon Raw Image",
   "cr2"      => "Canon Raw Image",
   "cap"      => "Phase_One Raw Image",
   "iiq"      => "Phase_One Raw Image",
   "eip"      => "Phase_One Raw Image",
   "dcs"      => "Kodak Raw Image",
   "dcr"      => "Kodak Raw Image",
   "drf"      => "Kodak Raw Image",
   "k25"      => "Kodak Raw Image",
   "kdc"      => "Kodak Raw Image",
   "dng"      => "Adobe Raw Image",
   "erf"      => "Epson Raw Image",
   "fff"      => "Imacon Raw Image",
   "mef"      => "Mamiya Raw Image",
   "mos"      => "Leaf Raw Image",
   "mrw"      => "Minolta Raw Image",
   "nef"      => "Nikon Raw Image",
   "nrw"      => "Nikon Raw Image",
   "orf"      => "Olympus Raw Image",
   "pef"      => "Pentax Raw Image",
   "ptx"      => "Pentax Raw Image",
   "pxn"      => "Logitech Raw Image",
   "R3D"      => "RED Raw Image",
   "raf"      => "Fuji Raw Image",
   "raw"      => "Panasonic Raw Image",
   "rw2"      => "Panasonic Raw Image",
   "raw"      => "Leica Raw Image",
   "rwl"      => "Leica Raw Image",
   "dng"      => "Leica Raw Image",
   "rwz"      => "Rawzor Raw Image",
   "srw"      => "Samsung Raw Image",
   "x3f"      => "Sigma Raw Image"

  );



  public static $short_file_types = array(
   "3dm"      => "Rhino 3D Model",
   "3g2"      => "3GPP2 Multimedia",
   "3gp"      => "3GPP Multimedia",
   "7z"       => "7-Zip Compressed",
   "8bi"      => "Photoshop Plug-in",
   "accdb"    => "Access 2007 DB",
   "aac"      => "AAC Audio File",
   "ai"       => "Illustrator File",
   "aif"      => "AIFF Audio",
   "aiff"     => "AIFF Audio",
   "app"      => "Mac OS X App",
   "as"       => "ActionScript File",
   "asf"      => "Advanced Systems Format",
   "asp"      => "ASP Page",
   "asx"      => "Microsoft ASF Redirector",
   "avi"      => "AVI Movie",
   "bak"      => "Backup File",
   "bat"      => "DOS Batch",
   "bin"      => "Macbinary Encoded",
   "bmp"      => "Bitmap Image",
   "c"        => "C/C++ Source",
   "cab"      => "Windows Cabinet",
   "cer"      => "Internet Security Certificate",
   "cfg"      => "Configuration",
   "cgi"      => "CGI Script",
   "class"    => "Java Class",
   "com"      => "DOS Command",
   "cpl"      => "Windows Control Panel Item",
   "cpp"      => "C++ Source",
   "cs"       => "C# Source",
   "csr"      => "Certificate Signing Request",
   "css"      => "CSS File",
   "csv"      => "CSV File",
   "cur"      => "Windows Cursor",
   "dat"      => "Data",
   "db"       => "Database",
   "dbf"      => "Database",
   "dbx"      => "Outlook Express E-mail Folder",
   "deb"      => "Debian Software Package",
   "dll"      => "Dynamic Link Library",
   "dmg"      => "OS X Disk Image",
   "dmp"      => "Windows Memory Dump",
   "doc"      => "Word Document",
   "docx"     => "Word XML Doc",
   "drv"      => "Device Driver",
   "drw"      => "Drawing",
   "dtd"      => "DTD File",
   "dwg"      => "AutoCAD File",
   "dxf"      => "Drawing Exchange Format",
   "efx"      => "eFax Document",
   "eps"      => "EPS File",
   "exe"      => "Windows Executable",
   "fla"      => "Flash Animation",
   "flv"      => "Flash Video",
   "fnt"      => "Windows Font",
   "fon"      => "Generic Font",
   "gadget"   => "Windows Gadget",
   "gam"      => "Saved Game",
   "gho"      => "Ghost Backup",
   "gif"      => "GIF Image",
   "gpx"      => "GPS Exchange",
   "gz"       => "GZ Archive",
   "hqx"      => "BinHex 4.0 Encoded",
   "htm"      => "HTML File",
   "html"     => "HTML File",
   "iff"      => "Interchange File Format",
   "indd"     => "InDesign File",
   "ini"      => "Initialization File",
   "iso"      => "Disc Image",
   "jar"      => "Java Archive",
   "java"     => "Java Source Code",
   "jpg"      => "JPEG Image",
   "jpeg"     => "JPEG Image",
   "js"       => "JavaScript",
   "jsp"      => "Java Server Page",
   "keynote"  => "Keynote Presentation",
   "keychain" => "Mac OS X Keychain",
   "kml"      => "Keyhole Markup Language",
   "lnk"      => "File Shortcut",
   "log"      => "Log",
   "m"        => "Objective-C Implementation",
   "m3u"      => "Media Playlist",
   "m4a"      => "MPEG-4 Audio",
   "m4v"      => "iTunes Video",
   "max"      => "3ds Max Scene",
   "md"       => "Markdown Text File",
   "markdown" => "Markdown Text File",
   "mdb"      => "Access Database",
   "mid"      => "MIDI",
   "mim"      => "Mail Message",
   "mkv"      => "Matroska Video File",
   "mov"      => "QuickTime Movie",
   "mp3"      => "MP3 Audio",
   "mp4"      => "MPEG-4 Video",
   "mpa"      => "MPEG-2 Audio",
   "mpg"      => "MPEG Video",
   "mpeg"     => "MPEG Video",
   "msg"      => "Outlook Mail Message",
   "msi"      => "Windows Installer",
   "nes"      => "Nintendo (NES) ROM",
   "numbers"  => "Numbers Spreadsheet",
   "ogg"      => "OGG Media",
   "ori"      => "Original",
   "otf"      => "OpenType Font",
   "pages"    => "Pages File",
   "part"     => "Partially Downloaded",
   "pct"      => "Picture",
   "pdb"      => "Program Database",
   "pdf"      => "PDF Document",
   "php"      => "PHP Script",
   "pif"      => "Program Information",
   "pkg"      => "OS X Package",
   "pl"       => "Perl Script",
   "plugin"   => "Mac OS X Plug-in",
   "png"      => "PNG Image",
   "pps"      => "PowerPoint Slide Show",
   "ppt"      => "PowerPoint Presentation",
   "pptx"     => "PowerPoint XML Presentation",
   "prf"      => "Outlook Profile",
   "ps"       => "PostScript",
   "psd"      => "Photoshop File",
   "pspimage" => "PaintShop Pro Image",
   "py"       => "Python Script",
   "qxd"      => "QuarkXPress Document",
   "qxp"      => "QuarkXPress Project",
   "ra"       => "Real Audio",
   "rar"      => "RAR Archive",
   "rels"     => "Open Office XML",
   "rm"       => "Real Media",
   "rom"      => "N64 Game ROM",
   "rpm"      => "Red Hat Package Manager",
   "rss"      => "Rich Site Summary",
   "rtf"      => "Rich Text Format",
   "sav"      => "Saved Game",
   "sdf"      => "Standard Data",
   "sit"      => "Stuffit Archive",
   "sitx"     => "Stuffit X Archive",
   "sql"      => "SQL Data",
   "svg"      => "SVG FIle",
   "swf"      => "Shockwave Flash Movie",
   "sys"      => "Windows System",
   "tar-gz"   => "Tarball",
   "thm"      => "Thumbnail Image",
   "tif"      => "TIFF Image",
   "tiff"     => "TIFF Image",
   "tmp"      => "Temporary",
   "toast"    => "Toast Disc Image",
   "torrent"  => "BitTorrent",
   "ttf"      => "TrueType Font",
   "txt"      => "Plain Text",
   "uue"      => "Uuencoded",
   "vb"       => "VBScript",
   "vcd"      => "Virtual CD",
   "vcf"      => "vCard",
   "vob"      => "DVD Video Object",
   "wav"      => "WAVE Audio",
   "wks"      => "Works Spreadsheet",
   "wma"      => "Windows Media Audio",
   "wmv"      => "Windows Media Video",
   "wpd"      => "WordPerfect Document",
   "wps"      => "Works Document",
   "wsf"      => "Windows Script",
   "xhtml"    => "XHTML File",
   "xll"      => "Excel Add-In",
   "xls"      => "Excel Spreadsheet",
   "xlsx"     => "Excel XML Spreadsheet",
   "xml"      => "XML File",
   "yuv"      => "YUV Image",
   "zip"      => "Zip Archive",
   "zipx"     => "Extended Zip",
   
   "3fr"      => "Hasselblad Raw Image",
   "ari"      => "Arriflex Raw Image",
   "arw"      => "Sony Raw Image",
   "srf"      => "Sony Raw Image",
   "sr2"      => "Sony Raw Image",
   "bay"      => "Casio Raw Image",
   "crw"      => "Canon Raw Image",
   "cr2"      => "Canon Raw Image",
   "cap"      => "Phase_One Raw Image",
   "iiq"      => "Phase_One Raw Image",
   "eip"      => "Phase_One Raw Image",
   "dcs"      => "Kodak Raw Image",
   "dcr"      => "Kodak Raw Image",
   "drf"      => "Kodak Raw Image",
   "k25"      => "Kodak Raw Image",
   "kdc"      => "Kodak Raw Image",
   "dng"      => "Adobe Raw Image",
   "erf"      => "Epson Raw Image",
   "fff"      => "Imacon Raw Image",
   "mef"      => "Mamiya Raw Image",
   "mos"      => "Leaf Raw Image",
   "mrw"      => "Minolta Raw Image",
   "nef"      => "Nikon Raw Image",
   "nrw"      => "Nikon Raw Image",
   "orf"      => "Olympus Raw Image",
   "pef"      => "Pentax Raw Image",
   "ptx"      => "Pentax Raw Image",
   "pxn"      => "Logitech Raw Image",
   "R3D"      => "RED Raw Image",
   "raf"      => "Fuji Raw Image",
   "raw"      => "Panasonic Raw Image",
   "rw2"      => "Panasonic Raw Image",
   "raw"      => "Leica Raw Image",
   "rwl"      => "Leica Raw Image",
   "dng"      => "Leica Raw Image",
   "rwz"      => "Rawzor Raw Image",
   "srw"      => "Samsung Raw Image",
   "x3f"      => "Sigma Raw Image"

  );
  
}

