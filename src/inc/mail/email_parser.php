<?php
/*******************************************************************************
*  Title: Help Desk Software HESK
*  Version: 2.3 from 15th September 2011
*  Author: Klemen Stirn
*  Website: http://www.hesk.com
********************************************************************************
*  COPYRIGHT AND TRADEMARK NOTICE
*  Copyright 2005-2011 Klemen Stirn. All Rights Reserved.
*  HESK is a registered trademark of Klemen Stirn.

*  The HESK may be used and modified free of charge by anyone
*  AS LONG AS COPYRIGHT NOTICES AND ALL THE COMMENTS REMAIN INTACT.
*  By using this code you agree to indemnify Klemen Stirn from any
*  liability that might arise from it's use.

*  Selling the code for this program, in part or full, without prior
*  written consent is expressly forbidden.

*  Using this code, in part or full, to create derivate work,
*  new scripts or products is expressly forbidden. Obtain permission
*  before redistributing this software over the Internet or in
*  any other medium. In all cases copyright and header must remain intact.
*  This Copyright is in full effect in any country that has International
*  Trade Agreements with the United States of America or
*  with the European Union.

*  Removing any of the copyright notices without purchasing a license
*  is expressly forbidden. To remove HESK copyright notice you must purchase
*  a license for this script. For more information on how to obtain
*  a license please visit the page below:
*  https://www.hesk.com/buy.php
*******************************************************************************/

/* Check if this is a valid include */
if (!defined('IN_SCRIPT')) {die('Invalid attempt');}

function getFileExtension($fileName)
{
   $parts=explode(".",$fileName);
   return $parts[count($parts)-1];
}

function convert_html_to_text($data)
{

	$h2t =& new html2text($data);

	// Simply call the get_text() method for the class to convert
	// the HTML to the plain text. Store it into the variable.
	$text = $h2t->get_text();
    return $text;

  $search = array ("'<script[^>]*?>.*?</script>'si",  // Strip out javascript
                 "'<style[^>]*?>.*?</style>'si", // Strip out Style
                 "'<[/!]*?[^<>]*?>'si",          // Strip out HTML tags
                 "'([rn])[s]+'",                 // Strip out white space
                 "'&(quot|#34);'i",              // Replace HTML entities
                 "'&(amp|#38);'i",
                 "'&(lt|#60);'i",
                 "'&(gt|#62);'i",
                 "'&(nbsp|#160);'i",
                 "'&(iexcl|#161);'i",
                 "'&(cent|#162);'i",
                 "'&(pound|#163);'i",
                 "'&(copy|#169);'i",
                 "'&#(d+);'e");                  // evaluate as php

  $replace = array ("",
                 "",
                 "",
                 "\1",
                 "\"",
                 "&",
                 "<",
                 ">",
                 " ",
                 chr(161),
                 chr(162),
                 chr(163),
                 chr(169),
                 "chr(\1)");

  return preg_replace($search, $replace, $data); 
}

function get_temp_fname($tmpdir)
{
  return $tmpdir.md5(uniqid(mt_rand(), true));
}

// read config file , return value of  varname or false
function get_config($varname)
{
  $tmpdir = get_temp_fname(HESK_PATH . "attachments/");
  mkdir($tmpdir, 0777);

  $config = array(
  	"TempDir" => $tmpdir,
    "AttachmentsDir" => $tmpdir,
  );

  if ( array_key_exists($varname,$config) )
  {
    return $config[$varname];
  }
  return FALSE;
}

function parser()
{

  $tempdir = get_config("TempDir");
  if ( ! is_dir($tempdir) ){
    die("The temporary directory \"".$tempdir."\" doesn't exist.");
  }

  // get a unique temporary file name
  $tmpfilepath = tempnam($tempdir, strval(mt_rand(1000,9999)));

  // read the mail that is forwarded to the script
  // then save the mail to a temporary file
  save_forward_mail($tmpfilepath);
  
  if(file_exists($tmpfilepath) === FALSE){
    die("Failed to save the mail as ".$tmpfilepath.".");
  }
  
  $ret = analyze($tmpfilepath,$tempdir);
  return $ret;
}


function analyze($tmpfilepath,$tempdir)
{
  $mime=new mime_parser_class;

  $mime->mbox = 0;
  $mime->decode_bodies = 1;
  $mime->ignore_syntax_errors = 1;
  $mime->track_lines = 0;

  $parameters=array(
  	'File'=>$tmpfilepath,
    'SaveBody'=>$tempdir,
  );
  
  /* only process the first email */
  if($mime->Decode($parameters, $decoded))
  {
      if($mime->decode_bodies)
      {
		if($mime->Analyze($decoded[0], $results)){
		  return process_results($results,$tempdir) ;
		}else{
		  echo 'MIME message analyse error: '.$mime->error."\n";
		}
      }
 
  
  }
  return False;
}


function process_addrname_pairs($email_info)
{

  $result = array();

  foreach($email_info as $info){
    $address = "";
    $name = "";
    if ( array_key_exists("address", $info) ){
      $address = $info["address"];
    }
    if ( array_key_exists("name", $info) ){
      $name = $info["name"];
    }
    
    $result[] = array("address"=>$address,"name"=>$name);
  }

  return $result;

}


function process_attachments($attachments)
{
  $result = array();
  foreach($attachments as $info){
    $orig_name = "";
    $size = 0;
    $stored_name = "";
    $type = "";

    if ( array_key_exists("Type", $info) ){
      $type = $info["Type"];
    }

    if ( array_key_exists("FileName", $info) ){
      $orig_name = $info["FileName"];
    }

    if (!strlen($orig_name)){
    	continue;
    }

    if ( array_key_exists("Data", $info) ){
      $data = $info["Data"];
      $size = strlen($data);

      if ($size == 0){
      	continue;
      }

      $attachsdir = get_config("AttachmentsDir");

      if ( ! is_dir($attachsdir) ){
        die("The attachments directory \"".$attachsdir."\" doesn't exist.");
      }

      $stored_name = save_attachment($attachsdir,getFileExtension($orig_name),$data);
    }
    else {
    	$stored_name = $info['DataFile'];
        $size = filesize($stored_name);
    }

    $result[] = array("orig_name"=>$orig_name,"size"=>$size,"stored_name"=>$stored_name,"type"=>$type);
  }

  return $result;
}

/*
save an attachment file into the predefined directory.
return stored name 
*/
function save_attachment($dir,$extension,$data)
{
  $dir = rtrim($dir,"/\\"); /* " */

  $path = "";
  $stored_name = "";
  do{
    $stored_name = date("YmdHis")."_".strval(mt_rand()).".".$extension;
    $path = $dir . "/" . $stored_name;
  }while(file_exists($path));
  
  $fp = fopen($path,"w");
  if($fp === FALSE){
    die("Cannot save file ".$path." .");
  }
  fwrite($fp,$data);
  fclose($fp);
  
  return $stored_name;
}

function process_results($result,$tempdir)
{
  $r = array();
  
  // from address and name
  $r["from"] = process_addrname_pairs($result["From"]);
  
  // to  address and name
  $r["to"] = process_addrname_pairs($result["To"]);
  
  // cc address and name
  if( array_key_exists("Cc", $result) ){
    $r["cc"] = process_addrname_pairs($result["Cc"]);
  }else{
    $r["cc"] = array();
  }
  
  // bcc address and name
  if( array_key_exists("Bcc", $result) ){
    $r["bcc"] = process_addrname_pairs($result["Bcc"]);
  }else{
    $r["bcc"] = array();
  }  
  
  // reply-to address and name
  if( array_key_exists("Reply-to", $result) ){
    $r["reply-to"] = process_addrname_pairs($result["Reply-to"]);
  }else{
    $r["reply-to"] = array();
  }

  // subject and subject encoding
  $r["subject"] = $result["Subject"];
  $r["subject_encoding"] = isset($result["SubjectEncoding"]) ? strtoupper($result["SubjectEncoding"]) : "";

  // the message shall be converted to text if it is in html
  if (!isset($result["Data"]) && isset($result["DataFile"]))
  {
  	$result["Data"] = file_get_contents($result["DataFile"]);
    #unlink($result["DataFile"]);
  }
  if ( $result["Type"] === "html" ){
    $r["message"] = convert_html_to_text($result["Data"]);
  }else{
    $r["message"] = $result["Data"];
  }

  // Message encoding
  $r["encoding"] = isset($result["Encoding"]) ? strtoupper($result["Encoding"]) : "";

  // attachments
  if( array_key_exists("Attachments", $result) ){
    $r["attachments"] = process_attachments($result["Attachments"]);
  }else{
    $r["attachments"] = array();
  }

  // Name of the temporary folder
  $r["tempdir"] = $tempdir;

  return $r;
}

/*
  save the forwarded mail to a temporary file
  no return value
*/
function save_forward_mail($tmpfilepath)
{
  // create a temporary file
  $tmpfp = fopen($tmpfilepath,"w");

  // open the stdin as a file handle
  #$fp = fopen('5ff3c4913098854837bcb9ff2ea2bb56.txt','r');
  $fp = fopen("php://stdin", "r");
  while (!feof($fp))
  {
    fwrite($tmpfp, fgets($fp,4096));
  }
  fclose($fp);

  fclose($tmpfp);
}



#test();

function test()
{
  $results = parser();

  print_r($results);
  exit();
  
  // from address and name
  echo "from :\n";
  echo $results["from"][0]["address"]."\n";
  echo $results["from"][0]["name"]."\n";
  
  echo "\nto :\n";
  foreach( $results["to"] as $to ){
    echo $to["address"]."\n";
    echo $to["name"]."\n";
  }

  echo "\nreply-to :\n";
  foreach( $results["reply-to"] as $to ){
    echo $to["address"]."\n";
    echo $to["name"]."\n";
  }  
  
  echo "\ncc :\n";
  foreach( $results["cc"] as $to ){
    echo $to["address"]."\n";
    echo $to["name"]."\n";
  }  
  
  echo "\nbcc :\n";
  foreach( $results["bcc"] as $to ){
    echo $to["address"]."\n";
    echo $to["name"]."\n";
  }  
  
  echo "\nsubject :\n";
  echo $results["subject"]."\n";  

  echo "\nmessage :\n";
  echo $results["message"]."\n"; 
  
  echo "\nattachments :\n";
  foreach( $results["attachments"] as $attach ){
     echo $attach["orig_name"]."\n";
     echo $attach["size"]."\n";
     echo $attach["stored_name"]."\n";
     echo $attach["type"]."\n";
  }
  
}


function deleteAll($directory, $empty = false) {
    if(substr($directory,-1) == "/") {
        $directory = substr($directory,0,-1);
    }

    if(!file_exists($directory) || !is_dir($directory)) {
        return false;
    } elseif(!is_readable($directory)) {
        return false;
    } else {
        $directoryHandle = opendir($directory);

        while ($contents = readdir($directoryHandle)) {
            if($contents != '.' && $contents != '..') {
                $path = $directory . "/" . $contents;

                if(is_dir($path)) {
                    deleteAll($path);
                } else {
                    unlink($path);
                }
            }
        }

        closedir($directoryHandle);

        if($empty == false) {
            if(!rmdir($directory)) {
                return false;
            }
        }

        return true;
    }
}



class html2text
{

    /**
     *  Contains the HTML content to convert.
     *
     *  @var string $html
     *  @access public
     */
    var $html;

    /**
     *  Contains the converted, formatted text.
     *
     *  @var string $text
     *  @access public
     */
    var $text;

    /**
     *  Maximum width of the formatted text, in columns.
     *
     *  Set this value to 0 (or less) to ignore word wrapping
     *  and not constrain text to a fixed-width column.
     *
     *  @var integer $width
     *  @access public
     */
    var $width = 70;

    /**
     *  List of preg* regular expression patterns to search for,
     *  used in conjunction with $replace.
     *
     *  @var array $search
     *  @access public
     *  @see $replace
     */
    var $search = array(
        "/\r/",                                  // Non-legal carriage return
        "/[\n\t]+/",                             // Newlines and tabs
        '/[ ]{2,}/',                             // Runs of spaces, pre-handling
        '/<script[^>]*>.*?<\/script>/i',         // <script>s -- which strip_tags supposedly has problems with
        '/<style[^>]*>.*?<\/style>/i',           // <style>s -- which strip_tags supposedly has problems with
        //'/<!-- .* -->/',                         // Comments -- which strip_tags might have problem a with
        '/<h[123][^>]*>(.*?)<\/h[123]>/ie',      // H1 - H3
        '/<h[456][^>]*>(.*?)<\/h[456]>/ie',      // H4 - H6
        '/<p[^>]*>/i',                           // <P>
        '/<br[^>]*>/i',                          // <br>
        '/<b[^>]*>(.*?)<\/b>/ie',                // <b>
        '/<strong[^>]*>(.*?)<\/strong>/ie',      // <strong>
        '/<i[^>]*>(.*?)<\/i>/i',                 // <i>
        '/<em[^>]*>(.*?)<\/em>/i',               // <em>
        '/(<ul[^>]*>|<\/ul>)/i',                 // <ul> and </ul>
        '/(<ol[^>]*>|<\/ol>)/i',                 // <ol> and </ol>
        '/<li[^>]*>(.*?)<\/li>/i',               // <li> and </li>
        '/<li[^>]*>/i',                          // <li>
        '/<a [^>]*href="([^"]+)"[^>]*>(.*?)<\/a>/ie',
                                                 // <a href="">
        '/<hr[^>]*>/i',                          // <hr>
        '/(<table[^>]*>|<\/table>)/i',           // <table> and </table>
        '/(<tr[^>]*>|<\/tr>)/i',                 // <tr> and </tr>
        '/<td[^>]*>(.*?)<\/td>/i',               // <td> and </td>
        '/<th[^>]*>(.*?)<\/th>/ie',              // <th> and </th>
        '/&(nbsp|#160);/i',                      // Non-breaking space
        '/&(quot|rdquo|ldquo|#8220|#8221|#147|#148);/i',
		                                         // Double quotes
        '/&(apos|rsquo|lsquo|#8216|#8217);/i',   // Single quotes
        '/&gt;/i',                               // Greater-than
        '/&lt;/i',                               // Less-than
        '/&(amp|#38);/i',                        // Ampersand
        '/&(copy|#169);/i',                      // Copyright
        '/&(trade|#8482|#153);/i',               // Trademark
        '/&(reg|#174);/i',                       // Registered
        '/&(mdash|#151|#8212);/i',               // mdash
        '/&(ndash|minus|#8211|#8722);/i',        // ndash
        '/&(bull|#149|#8226);/i',                // Bullet
        '/&(pound|#163);/i',                     // Pound sign
        '/&(euro|#8364);/i',                     // Euro sign
        '/&[^&;]+;/i',                           // Unknown/unhandled entities
        '/[ ]{2,}/'                              // Runs of spaces, post-handling
    );

    /**
     *  List of pattern replacements corresponding to patterns searched.
     *
     *  @var array $replace
     *  @access public
     *  @see $search
     */
    var $replace = array(
        '',                                     // Non-legal carriage return
        ' ',                                    // Newlines and tabs
        ' ',                                    // Runs of spaces, pre-handling
        '',                                     // <script>s -- which strip_tags supposedly has problems with
        '',                                     // <style>s -- which strip_tags supposedly has problems with
        //'',                                     // Comments -- which strip_tags might have problem a with
        "strtoupper(\"\n\n\\1\n\n\")",          // H1 - H3
        "ucwords(\"\n\n\\1\n\n\")",             // H4 - H6
        "\n\n\t",                               // <P>
        "\n",                                   // <br>
        'strtoupper("\\1")',                    // <b>
        'strtoupper("\\1")',                    // <strong>
        '_\\1_',                                // <i>
        '_\\1_',                                // <em>
        "\n\n",                                 // <ul> and </ul>
        "\n\n",                                 // <ol> and </ol>
        "\t* \\1\n",                            // <li> and </li>
        "\n\t* ",                               // <li>
        '$this->_build_link_list("\\1", "\\2")',
                                                // <a href="">
        "\n-------------------------\n",        // <hr>
        "\n\n",                                 // <table> and </table>
        "\n",                                   // <tr> and </tr>
        "\t\t\\1\n",                            // <td> and </td>
        "strtoupper(\"\t\t\\1\n\")",            // <th> and </th>
        ' ',                                    // Non-breaking space
        '"',                                    // Double quotes
        "'",                                    // Single quotes
        '>',
        '<',
        '&',
        '(c)',
        '(tm)',
        '(R)',
        '--',
        '-',
        '*',
        '£',
        'EUR',                                  // Euro sign. € ?
        '',                                     // Unknown/unhandled entities
        ' '                                     // Runs of spaces, post-handling
    );

    /**
     *  Contains a list of HTML tags to allow in the resulting text.
     *
     *  @var string $allowed_tags
     *  @access public
     *  @see set_allowed_tags()
     */
    var $allowed_tags = '';

    /**
     *  Contains the base URL that relative links should resolve to.
     *
     *  @var string $url
     *  @access public
     */
    var $url;

    /**
     *  Indicates whether content in the $html variable has been converted yet.
     *
     *  @var boolean $_converted
     *  @access private
     *  @see $html, $text
     */
    var $_converted = false;

    /**
     *  Contains URL addresses from links to be rendered in plain text.
     *
     *  @var string $_link_list
     *  @access private
     *  @see _build_link_list()
     */
    var $_link_list = '';

    /**
     *  Number of valid links detected in the text, used for plain text
     *  display (rendered similar to footnotes).
     *
     *  @var integer $_link_count
     *  @access private
     *  @see _build_link_list()
     */
    var $_link_count = 0;

    /**
     *  Constructor.
     *
     *  If the HTML source string (or file) is supplied, the class
     *  will instantiate with that source propagated, all that has
     *  to be done it to call get_text().
     *
     *  @param string $source HTML content
     *  @param boolean $from_file Indicates $source is a file to pull content from
     *  @access public
     *  @return void
     */
    function html2text( $source = '', $from_file = false )
    {
        if ( !empty($source) ) {
            $this->set_html($source, $from_file);
        }
        $this->set_base_url();
    }

    /**
     *  Loads source HTML into memory, either from $source string or a file.
     *
     *  @param string $source HTML content
     *  @param boolean $from_file Indicates $source is a file to pull content from
     *  @access public
     *  @return void
     */
    function set_html( $source, $from_file = false )
    {
        $this->html = $source;

        if ( $from_file && file_exists($source) ) {
            $fp = fopen($source, 'r');
            $this->html = fread($fp, filesize($source));
            fclose($fp);
        }

        $this->_converted = false;
    }

    /**
     *  Returns the text, converted from HTML.
     *
     *  @access public
     *  @return string
     */
    function get_text()
    {
        if ( !$this->_converted ) {
            $this->_convert();
        }

        return $this->text;
    }

    /**
     *  Prints the text, converted from HTML.
     *
     *  @access public
     *  @return void
     */
    function print_text()
    {
        print $this->get_text();
    }

    /**
     *  Alias to print_text(), operates identically.
     *
     *  @access public
     *  @return void
     *  @see print_text()
     */
    function p()
    {
        print $this->get_text();
    }

    /**
     *  Sets the allowed HTML tags to pass through to the resulting text.
     *
     *  Tags should be in the form "<p>", with no corresponding closing tag.
     *
     *  @access public
     *  @return void
     */
    function set_allowed_tags( $allowed_tags = '' )
    {
        if ( !empty($allowed_tags) ) {
            $this->allowed_tags = $allowed_tags;
        }
    }

    /**
     *  Sets a base URL to handle relative links.
     *
     *  @access public
     *  @return void
     */
    function set_base_url( $url = '' )
    {
        if ( empty($url) ) {
        	if ( !empty($_SERVER['HTTP_HOST']) ) {
	            $this->url = 'http://' . $_SERVER['HTTP_HOST'];
        	} else {
	            $this->url = '';
	        }
        } else {
            // Strip any trailing slashes for consistency (relative
            // URLs may already start with a slash like "/file.html")
            if ( substr($url, -1) == '/' ) {
                $url = substr($url, 0, -1);
            }
            $this->url = $url;
        }
    }

    /**
     *  Workhorse function that does actual conversion.
     *
     *  First performs custom tag replacement specified by $search and
     *  $replace arrays. Then strips any remaining HTML tags, reduces whitespace
     *  and newlines to a readable format, and word wraps the text to
     *  $width characters.
     *
     *  @access private
     *  @return void
     */
    function _convert()
    {
        // Variables used for building the link list
        $this->_link_count = 0;
        $this->_link_list = '';

        $text = trim(stripslashes($this->html));

        // Run our defined search-and-replace
        $text = preg_replace($this->search, $this->replace, $text);

        // Strip any other HTML tags
        $text = strip_tags($text, $this->allowed_tags);

        // Bring down number of empty lines to 2 max
        $text = preg_replace("/\n\s+\n/", "\n\n", $text);
        $text = preg_replace("/[\n]{3,}/", "\n\n", $text);

        // Add link list
        if ( !empty($this->_link_list) ) {
            $text .= "\n\nLinks:\n------\n" . $this->_link_list;
        }

        // Wrap the text to a readable format
        // for PHP versions >= 4.0.2. Default width is 75
        // If width is 0 or less, don't wrap the text.
        if ( $this->width > 0 ) {
        	$text = wordwrap($text, $this->width);
        }

        $this->text = $text;

        $this->_converted = true;
    }

    /**
     *  Helper function called by preg_replace() on link replacement.
     *
     *  Maintains an internal list of links to be displayed at the end of the
     *  text, with numeric indices to the original point in the text they
     *  appeared. Also makes an effort at identifying and handling absolute
     *  and relative links.
     *
     *  @param string $link URL of the link
     *  @param string $display Part of the text to associate number with
     *  @access private
     *  @return string
     */
    function _build_link_list( $link, $display )
    {
		if ( substr($link, 0, 7) == 'http://' || substr($link, 0, 8) == 'https://' ||
             substr($link, 0, 7) == 'mailto:' ) {
            $this->_link_count++;
            $this->_link_list .= "[" . $this->_link_count . "] $link\n";
            $additional = ' [' . $this->_link_count . ']';
		} elseif ( substr($link, 0, 11) == 'javascript:' ) {
			// Don't count the link; ignore it
			$additional = '';
		// what about href="#anchor" ?
        } else {
            $this->_link_count++;
            $this->_link_list .= "[" . $this->_link_count . "] " . $this->url;
            if ( substr($link, 0, 1) != '/' ) {
                $this->_link_list .= '/';
            }
            $this->_link_list .= "$link\n";
            $additional = ' [' . $this->_link_count . ']';
        }

        return $display . $additional;
    }

}
?>
