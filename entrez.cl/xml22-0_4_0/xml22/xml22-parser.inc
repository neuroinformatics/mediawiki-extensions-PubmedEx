<?php
/*L----------------------------------------------------------------------

xml22-parser.inc 

Version 0.4.0

Copyright (C) 2003 Ingo Schramm

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or (at
your option) any later version.

This program is distributed in the hope that it will be useful, but
WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307
USA

----------------------------------------------------------------------L*/

require_once("xml22-share.inc");

// API

//----- SETUP

//
// bool xml22_setup( array $options )
//
//
function xml22_setup( &$options ) {

  static $setup = false;

  if ( $setup ) {
	xml22_error("you tried to run xml22_setup more than once");
	return false;
  }

  $return   = false;
  $optnames = array('XML22_OPT_CACHING'    => '/^(0|1)$/',
					'XML22_OPT_TABSIZE'    => '/^\d+$/',
					'XML22_OPT_WRITESTYLE' => '/^\d$/',
					'XML22_OPT_EXTERNALS'  => '/^(0|1)$/',
					);

  foreach ( $optnames as $optname => $regex ) {
	if( isset($options[$optname]) ) {
	  if ( preg_match($regex, $options[$optname]) ) {
		define( $optname, $options[$optname] );
		$return = true;
	  }
	  else {
		xml22_error("option does not match regex: $optname => $options[$optname]" );
	  }
	}
  }

  $setup = true;
  return $return;
}

//
// array xml22_error( string $msg = false )
//
//
function xml22_error( $msg = false ) {

  static $errors;

  if ( $msg ) {
	$errors[]       = $msg;
	$errors['last'] = $msg;
	return true;
  }
  else {
	$buffer = $errors;
	unset($errors['last']);
	return $buffer;
  }
}


//
// array xml22_parse( string $file, bool $usecache = 1 )
//

define( "XML22_CACHE",   1 );
define( "XML22_NOCACHE", 0 );

function xml22_parse( $file, $usecache = XML22_CACHE, $charset_from="ISO-8859-1", $charset_to="" ) {

  global $xml22_document;
  global $xml22_file;

  // locals
  $buffer = array();
  $fopen  = true;
  // statics
  static $cache_index;

  if ( defined('XML22_OPT_CACHING') ) { $usecache = XML22_OPT_CACHING; }

  if ( strstr( $file, '<?xml' ) ) { // no file, an xml string
	$fopen    = false;  
	$usecache = false;
	xml22_parser_globals('init', 'xml string');
  }
  else { // a file name
	if ( file_exists( $file ) ) {
	  xml22_parser_globals('init', $file);
	}
	else {
	  //if we have a misspelled XML string, shorten it for error message
	  $file = substr( $file, 0, 256 );
	  xml22_error("file not found: $file");
	  return false;
	}
  }

  if ( $usecache ) {
	// is the file already parsed and cached?   
	if ( $cache_index[$xml22_file] ) {
	  // check if the file has been changed
	  clearstatcache();
	  if ( $cache_index[$xml22_file]['mtime'] == ( filemtime($xml22_file) ) ) {
		$tmp = $cache_index[$xml22_file]['fp'];
		$docdata = '';
		if ( rewind($tmp) ) {
		  while ( $read = fread( $tmp, 1024 ) ) {
			$docdata .= $read;
		  }
		  // unset globals
		  xml22_parser_globals('unset');
		  // unfold cached data, return it and go
		  return unserialize( $docdata );
		}
		else {
		  xml22_error("could not read cache, file deskriptor broken for '$xml22_file'");
		}
	  }
	  //else: file has been changed - parse it again
	}
  }

  $xml_parser = xml_parser_create($charset_from);

  if( !empty( $charset_to) ) xml_parser_set_option($parser, XML_OPTION_TARGET_ENCODING, $charset_to);  // ABL

  xml_parser_set_option($xml_parser, XML_OPTION_SKIP_WHITE, 1);
  xml_parser_set_option($xml_parser, XML_OPTION_CASE_FOLDING, 0);
  xml_set_element_handler($xml_parser, "xml22_startElement", "xml22_endElement");
  xml_set_character_data_handler( $xml_parser, "xml22_cData" );
  xml_set_processing_instruction_handler($xml_parser, "xml22_PIHandler");
  xml_set_default_handler( $xml_parser, "xml22_default");

  if ( $fopen ) {
	if (!($fp = fopen($xml22_file, "r"))) {
	  xml22_error("file '$xml22_file': could not open");
	  xml22_parser_globals('unset');
	  return false;
	}
  }

  //parse
  $stop = false;
  do {
	if( $fopen ) { // read a file
	  if( ! ($data = fread($fp, 4096) ) ) { break; }
	  if(    $stop = feof($fp) )          { fclose( $fp ); }
	}
	else { // read a string
	  $data = $file;
	  $stop = true;
	}
	if (! xml_parse($xml_parser, $data, $stop) ) {
	  xml22_error(sprintf("in file '$xml22_file': XML error %s at line %d",
						  xml_error_string(xml_get_error_code($xml_parser)),
						  xml_get_current_line_number($xml_parser)));
	  xml22_parser_globals('unset');
	  return false;
	}
  } while( ! $stop );

  xml_parser_free($xml_parser);

  // cache for later use
  if ( $usecache ) {
	if ( $tmp = tmpfile() ) {
	  clearstatcache();
	  $cache_index[$xml22_file]['mtime'] = filemtime($xml22_file);
	  $cache_index[$xml22_file]['fp']    = $tmp;
	  $docdata = serialize( $xml22_document );
	  fwrite( $tmp, $docdata );
	}
	else {
	  xml22_error("could not get temporary file for caching");
	}
  }

  $buffer = $xml22_document;
  xml22_parser_globals('unset');
  return $buffer;
}


//
// bool xml22_register_pi_func( string $target, string $funcname )
//
//
function xml22_register_pi_func( $target, $funcname ) {

  global $xml22_pihandlers;

  if ( function_exists( $funcname ) ) {
	$xml22_pihandlers[$target] = $funcname;
	return true;
  }
  else {
	xml22_error("tried to register an undefined callback function for PI '$target': $funcname");
	return false;
  }
}


//
// array xml22_register_char_entity_set( array $match = null, array $replace = null )
//
//
function xml22_register_char_entity_set( $match = null, $replace = null ) {

  if ( $match and $replace ) {
	if ( is_array( $match ) and is_array( $replace ) ) {
	  if ( ! ( count($match) == count($replace) ) ) {
		xml22_error("attempt to register uncomplete entity set denied");
		return false;
	  }
	  # register (concat) the set
	  xml22_replace_entities( "", $match, $replace );
	  return true;
	}
	else {
	  xml22_error("arrays expected but not found");
	  return false;
	}
  }

}

//----- WRITING

define( "XML22_NORMALIZE", 1 );
define( "XML22_BEAUTIFY",  2 );

//
// bool xml22_write_document( array &$document, ressource $fd [, int $style = XML22_NORMALIZE] )
//
//
function xml22_write_document( &$document, $fd, $style = XML22_NORMALIZE ) {

  if ( ! xml22_check_array($document) ) { return false; }
  if ( ! is_resource( $fd ) ) {
	xml22_error( "cannot write to file descriptor: variable ist no resource");
	return false;
  }

  if ( $string = xml22_write_document_str( $document, $style ) ) {
	if ( fwrite( $fd, $string ) < 0 ) {
	  return false;
	}
	return true;
  }
  return false; 
}

//
// string xml22_write_document_str( array &$document [, int $style = XML22_NORMALIZE] )
//
//
function xml22_write_document_str( &$document, $style = XML22_NORMALIZE ) {

  if ( ! xml22_check_array($document) ) { return false; }

  $stack  = array();
  $string = '';

  if ( defined('XML22_OPT_WRITESTYLE') ) { $style = XML22_OPT_WRITESTYLE; }

  $tab       = xml22_tab();
  $doclen    = count($document);

  // print tags
  //foreach ( $document as $index => $tag ) {//foreach
  for ( $index = 0; $index < $doclen; ++$index ) {

    $tag = $document[$index];

	// if XML22_BEAUTIFY precede line with tabs
	if ( $style & XML22_BEAUTIFY ) {
	  for ( $i = 0; $i < $tag['level']; $i++ ) {
		$string .= $tab;
	  }
	}
	// print opening tag
	if ( $tag['tag'] ) {//tag
	  if ( $tag['tag'] == '!DOCTYPE' ) {
		// special DOCTYPE handling
		$string .= '<!DOCTYPE';
		// write doctype
		foreach ( $tag['type'] as $val ) { $string .= " $val"; }
        // write DTD lines
        if ( isset($tag['children'])) {
          if ( $style & XML22_BEAUTIFY ) { $string .= " [\n"; }
          foreach( $tag['children'] as $idx ) {
            if ( $style & XML22_BEAUTIFY ) { $string .= $tab; }
            if ( $document[$idx]['tag'] ) {
              $string .= "<";
              $string .= $document[$idx]['tag'];
              $string .= ' ';
            }
            $string .= $document[$idx]['content'];
            if ( $document[$idx]['tag'] ) {
              $string .= ">";
            }
            if ( $style & XML22_BEAUTIFY ) { $string .= "\n"; }
            ++$index;
          }
		}
        $string .= "]>";
        if ( $style & XML22_BEAUTIFY ) { $string .= "\n"; }
        // end of DOCTYPE
        continue;
	  } // if DOCTYPE
	  else {
		$string .= "<$tag[tag]"; 
		// add attributes
		if ( isset( $tag['attributes'] ) ) {
		  foreach ( $tag['attributes'] as $name => $value ) {
			$value = xml22_str2ent( $value );
			$string .= " $name=\"$value\"";
		  }
		}
	  } // else, no DOCTYPE
	} // if ( $tag['tag'] )

	// check next level
	if ( ! isset( $document[$index+1]['level'] ) ) {
	  $nextlevel = 0;
	}
	else {
	  $nextlevel = $document[$index+1]['level'];
	}
	// if we have to jump more than one level, the structure is broken
	if ( $nextlevel > ($document[$index]['level'] + 1) ) {
	  xml22_error("cannot write document: no valid XML below array index $index");
	  return false;
	}
	// push tag if next level is greater
	if ( $nextlevel > $tag['level'] ) {
	  if ( $tag['tag'] ) { $string .= ">"; }
	  array_push( $stack, $tag['tag'] );
	}

	// print content
	if ( isset( $tag['content'] ) ) {
	  if ( $tag['tag'] ) { $string .= ">"; }
	  $string .= xml22_str2ent( $tag['content'] );
	}

	// write closing tag if next level is equal or less
	if ( $nextlevel <= $tag['level'] ) {
	  if ( $tag['tag'] ) { 
		//if ( ! strstr($tag['tag'], '!DOCTYPE') ) {
		  if ( ! isset( $tag['content'] ) ) {
			// empty tag
			$string .= "/>";
		  }
		  else {
			// tag has content
			$string .= "</$tag[tag]>";
		  }
        //}
	  }
	}
	if ( $style & XML22_BEAUTIFY ) { $string .= "\n"; }
	// next level is less
	if ( $nextlevel < $tag['level'] ) {
	  // pop a closing tag for every step between
	  // current level and next level
	  for ( $i = 0; $i < ($tag['level'] - $nextlevel); $i++ ) {
		$tagstr = array_pop( $stack );
		// if XML22_BEAUTIFY precede line with tabs
		if ( $style & XML22_BEAUTIFY ) { 
		  for ( $j = 1; $j < ( $tag['level'] - $i ); $j++ ) {
			$string .= $tab;
		  }
		}
		// print the closing tag
		$string .= "</$tagstr>";
		if ( $style & XML22_BEAUTIFY ) { $string .= "\n"; }
	  } // for $i = 0
	} // $nextlevel < $tag
  } // foreach $document
  return $string;
}

// parsing private

// private
function xml22_startElement( $parser, $name, $attrs) {

  global $xml22_depth;
  global $xml22_document;
  global $xml22_curindex;
  global $xml22_last_depth;
  global $xml22_levels;

  static $rootindex = 0;

  $parser = (int) $parser;

  // prevent warnings
  if ( ! isset($xml22_depth[$parser]) ) { $xml22_depth[$parser] = 0; }
  $level = $xml22_depth[$parser];

  // setup counters
  $xml22_curindex++;
  if ( $level != $xml22_last_depth ) { $xml22_last_depth = $level; }
  // set last of level to current
  $xml22_levels[$level] = $xml22_curindex;

  xml22_parser_cat_content($string, $level, $name );

  if ( ! $level ) { 
	$xml22_document[$xml22_curindex]['parindex'] = "self";
	$xml22_document[0]['meta']['rootidx'] = $xml22_curindex;
  }

  // add attributes to array
  if ( count( $attrs) ) { $xml22_document[$xml22_curindex]['attributes'] = $attrs; }

  $xml22_depth[$parser]++;
  return true;
} 

// private
function xml22_endElement( $parser, $name) {

  global $xml22_depth;
  global $xml22_inCONTENT;

  $parser = (int) $parser;

  $xml22_inCONTENT = false;
  $xml22_depth[$parser]--;
  return true;
} 

// private
function xml22_cData( $parser, $string ) {

  global $xml22_document;
  global $xml22_curindex;
  global $xml22_inCONTENT;

  if ( preg_match( '/\S/', $string ) ) {
    $xml22_inCONTENT = true;
  }
  else {
    return true;
  }

  $string = str_replace( "\n", " ", $string );
  xml22_parser_cat_content( $string, $xml22_document[$xml22_curindex]['level'] );
  return true;
}

//private
function xml22_PIHandler($parser, $target, $data) {

  global $xml22_depth;
  global $xml22_document;
  global $xml22_curindex;
  global $xml22_inDOCTYPE;
  global $xml22_pihandlers;

  $level = xml22_get_level($parser);

  // insert to document
  $string = "<?$target $data?>";
  if ( $xml22_inDOCTYPE ) {
    $xml22_document[$xml22_inDOCTYPE]['pi'][] = ++$xml22_curindex;
    xml22_parser_cat_content( $string, $level );
  }
  else {
	$xml22_curindex++;
	xml22_parser_cat_content( $string, $level );
  }
  // if we have a function for the target, call it
  if ( isset($xml22_pihandlers[$target]) ) {
	return call_user_func( $xml22_pihandlers[$target], $data );
  }
}

// private
// put the most inline, may be a little faster
function xml22_default( $parser, $string) {

  global $xml22_document;
  global $xml22_curindex;
  global $xml22_depth;
  global $xml22_levels;
  global $xml22_file;
  global $xml22_inDOCTYPE;
  global $xml22_inDTD;
  global $xml22_inCDATA;
  global $xml22_inENTITY;
  global $xml22_inNOTATION;

  static $count      = 0;
  static $name       = '';
  static $dtindex    = 0;

  $matches = array();

  $level = xml22_get_level($parser);

  // !!! do *not* change the order of the elseifs !!!
  if ( strstr( $string, ']]>')  ) { // close CDATA
	xml22_parser_cat_content( $string, $level );
	$xml22_inCDATA = false;
  }
  elseif ( preg_match( '/^((<\?)|(<!--))/', $string)  ) {
	// version, comments
	if ( $xml22_inDOCTYPE ) {
	  $xml22_document[$xml22_inDOCTYPE]['comments'][] = ++$xml22_curindex;
      xml22_parser_cat_content( $string, $level );
	}
	else {
	  $xml22_curindex++;
	  xml22_parser_cat_content( $string, $level );
	}
  }
  elseif( preg_match( '/>\s*$/', $string)  ) {
	if ( $xml22_inDOCTYPE ) {
	  if ( $xml22_inNOTATION ) { // close NOTATION
		$xml22_inNOTATION = false;
		$count = 0;
		$name = '';
	  }
	  elseif ( $xml22_inENTITY ) { // close ENTITY
		$xml22_inENTITY = false;
		$count = 0;
		$name = '';
	  }
	  else { // close DOCTYPE
        $xml22_inDOCTYPE = 0;
        if ( isset($xml22_depth[$parser]) ) {
          $xml22_depth[$parser]--;
        }
	  }
	}
  }
  elseif ( strstr( $string, ']' ) ) {
	if ( $xml22_inDTD ) { // close DTD
	  $xml22_inDTD = false;
	}
  }
  elseif ( preg_match('/^(<!)(.*)/', $string, $matches ) ) {
	switch ( $matches[2] ) {
	case 'DOCTYPE': // open DOCTYPE
      if ( isset($xml22_depth[$parser]) ) {
        $xml22_depth[$parser]++;
      }
      else {
        $xml22_depth[$parser] = 1;
      }
      $xml22_inDOCTYPE      = ++$xml22_curindex;
      $dtindex              =   $xml22_curindex;
      $xml22_levels[$level] =   $xml22_curindex;
	  $xml22_document[$xml22_curindex] = array( 'tag'     => '!DOCTYPE',
												'index'   => $xml22_curindex,
												'level'   => $level );
	  return true;
	case 'ENTITY':  // open ENTITIY
	  $xml22_inENTITY = true;
	  return true;
	case 'NOTATION': // open NOTATION
	  $xml22_inNOTATION = true;
	  return true;
	case '[CDATA[':  // open CDATA
	  $xml22_inCDATA = true;
	  $string = '<![CDATA[';
	  xml22_parser_cat_content( $string, $level );
	  return true;
	}
  }
  elseif ( strstr( $string, '[' ) ) {
	if ( $xml22_inDOCTYPE ) { // open DTD
	  $xml22_inDTD = true;
	}
  }
  elseif ( preg_match("/&.+;/", $string ) ) {
	// entity
	// process
	if ( isset( $xml22_document[$dtindex]['entities'][$string] ) ) {
      $val = str_replace('"', '', xml22_get_from_dtd($xml22_document, 'entities', $string ) );
	  if ( preg_match( '/(SYSTEM|PUBLIC)/', $val ) ) { // external
		if ( defined('XML22_OPT_EXTERNALS') ) { 
		  if ( XML22_OPT_EXTERNALS ) {
			return xml22_parser_parse_ext( array_pop( preg_split('/(\s+)/', $val) ), $level);
		  }
		}
	  }
	  // general, including markup
	  elseif ( strstr( $val, '<' ) ) {
		$xml = '<?xml version="1.0"?>'.$val;
		return xml22_parser_parse_ext( $xml, $level, true);
	  }
	  else { // general, plain
		$string = $val;
	  }
	}// isset 'entities'
	//insert to doc
	return xml22_parser_cat_content( $string, $level );
  }
  else {
	// plain text
	if ( $xml22_inDTD ) {
	  if ( $xml22_inENTITY ) {
		if ( ! $count ) {
          if (! $name = xml22_parser_add_from_dtd( "!ENTITY", $string, $level ) ) {
            $name = '';
            return true;
          }
		}
		else {
		  if ( $name ) {
			// store entity definition
            $xml22_document[$xml22_curindex]['content'] .= $string;
		  }
		}
		$count++;
	  } // $xml22_inEntity
	  elseif( $xml22_inNOTATION ) {
		if ( ! $count ) {
          if (! $name = xml22_parser_add_from_dtd( "!NOTATION", $string, $level ) ) {
            $name = '';
            return true;
          }
		}
		else {
		  if ( $name ) {
			// store notation definition
            $xml22_document[$xml22_curindex]['content'] .= $string;
		  }
		}
		$count++;
	  } // $xml22_inNOTATION
	} // $xml22_inDTD
	else {
	  // DOCTYPE strings
	  if ( ! preg_match('/^\s*$/', $string) ) {
		$xml22_document[$xml22_curindex]['type'][] = $string;
	  }
	}// $xml22_inDTD
  }//plain text
  return true;
}


//private
function xml22_get_level($parser) {

  global $xml22_depth;

  if ( isset( $xml22_depth[$parser] ) ) { 
    return $xml22_depth[$parser]; 
  }
  return 0; 
}

//private
function xml22_parser_add_from_dtd( $type, $name, $level ) {

  global $xml22_document;
  global $xml22_curindex;
  global $xml22_inDOCTYPE;
  global $xml22_file;

  $field = '';

  switch( $type ) {
  case '!NOTATION':
    $field = 'notations';
    break;
  case '!ENTITY':
    $field = 'entities';
    $name  = "&$name;";
    break;
  }

  if( strstr($name, ' ') ) { return ''; }

  if (  isset($xml22_document[$xml22_inDOCTYPE][$field][$name]) ) {
    // multiple entity definition
    xml22_error("in file '$xml22_file': multiple '<$type' declaration for '$name' discarded");
    return null;
  }

  $xml22_document[$xml22_inDOCTYPE][$field][$name] = ++$xml22_curindex;
  xml22_parser_cat_content( $name, $level, $type );

  return $name;
}

// private
function xml22_parser_cat_content( &$string, &$level, $tag = '' ) {

  global $xml22_document;
  global $xml22_curindex;
  global $xml22_levels;

  // does the entry already exist?
  if ( isset($xml22_document[$xml22_curindex]) ) {
	if ( isset( $xml22_document[$xml22_curindex]['content'] ) ) {
	  $xml22_document[$xml22_curindex]['content'] .= $string;
	}
	else {
	  $xml22_document[$xml22_curindex]['content']  = $string;
	}
  }
  // no, create one
  else {
	$xml22_document[$xml22_curindex] = array ( 'tag'     => $tag,
											   'index'   => $xml22_curindex,
											   'level'   => $level );
	if ( $string ) {
	  $xml22_document[$xml22_curindex]['content']  = $string;
	}
	//add me to parents children list
	if ( $level ) {
	  // add to parents children list
	  $parindex = $xml22_levels[($level - 1)];
	  $xml22_document[$parindex]['children'][] = $xml22_curindex;
	}
	if ( isset($parindex) ) {
	  $xml22_document[$xml22_curindex]['parindex'] = $parindex;
	}
  }
  return true;
}


//private
function xml22_parser_parse_ext( $file, $level, $internal = false ) {

  global $xml22_document;
  global $xml22_curindex;
  global $xml22_levels;

  xml22_parser_globals('save');
  $doc = xml22_parse( $file, XML22_NOCACHE );
  xml22_parser_globals('restore');
  if ( ! $doc ) { return false; }
  // remove xml declaration
  array_shift( $doc );
  // get parent
  $parindex = $xml22_levels[($level - 1)];
  $prchlevl = $xml22_document[$parindex]['level'] + 1;

  foreach( $doc as $idx => $tag ) {
	if( strstr( $tag['tag'], '!DOCTYPE' ) ) {
	  xml22_error("syntax error in external entity, no <!DOCTYPE allowed");
	  return false;
	}
	$doc[$idx]['level'] += $level;
	$doc[$idx]['index'] += $xml22_curindex;
	// parindex
	if( isset( $doc[$idx]['parindex'] ) ) { //adjust
	  if ( $doc[$idx]['parindex'] == 'self' ) {
		$doc[$idx]['parindex'] = $parindex;
	  }
	  else {
		$doc[$idx]['parindex'] += $xml22_curindex;
	  }
	}
	else { //define
	  $doc[$idx]['parindex'] = $parindex;
	}
	//adjust children list
	if ( isset( $doc[$idx]['children'] ) ) {
	  foreach( $doc[$idx]['children'] as $chidx => $ch ) {
		$doc[$idx]['children'][$chidx] += $xml22_curindex;
	  }
	}
	//insert to parents children list
	//if an immediate child
	if( $doc[$idx]['level'] == $prchlevl ) {
	  $xml22_document[$parindex]['children'][] = $doc[$idx]['index'];
	}
  }//foreach
  $xml22_document = array_merge( $xml22_document, $doc );
  // don't ask me why!
  if( $internal ) { $xml22_curindex += count( $doc ); }

  return true;
}

//private
function xml22_parser_globals( $action, $file = '' ) {

  static $globnames = array( 'xml22_document',
							 'xml22_depth',
							 'xml22_curindex',
							 'xml22_file',
							 'xml22_last_depth',
							 'xml22_levels',
							 'xml22_inDOCTYPE',
							 'xml22_inDTD',
							 'xml22_inCDATA',
							 'xml22_inENTITY',
							 'xml22_inNOTATION',
							 'xml22_inCONTENT' );
  static $stack     = array();

  foreach( $globnames as $var ) { global $$var; }
  
  switch ( $action ) {
  case 'init':
	$xml22_document   = array();
	$xml22_depth      = array();
	$xml22_curindex   = -1;
	$xml22_file       = $file;
	$xml22_last_depth = 0;
	$xml22_levels     = array();
	$xml22_inDOCTYPE  = false;
	$xml22_inDTD      = false;
	$xml22_inCDATA    = false;
	$xml22_inENTITY   = false;
	$xml22_inNOTATION = false;
	$xml22_inCONTENT  = false;
	return true;
  case 'save':
	foreach( $globnames as $var ) {
	  $save[$var] = $$var; 
	}
	$stack[] = $save;
	return true;
  case 'restore':
	$save = array_pop( $stack );
	foreach( $globnames as $var ) { 
	  $$var = $save[$var]; 
	}
	return true;
  case 'unset':
	foreach( $globnames as $var ) { unset( $$var ); }
	return true;
  case 'names':
	return $globnames;
  }
}

//private
//encode entities
function xml22_replace_entities( $string, $m = false, $r = false ) {

  static $match   = array( '/&(?!\w*;)/',
						   '/<(?!!\[)/',
						   '/(?<!\]\])>/',
						   '/"/',
						   "/'/" );

  static $replace = array( '&amp;',
						   '&lt;',
						   '&gt;',
						   '&quot;',
						   '&apos;' );

  # if we got a character entity set, merge it
  if ( $m and $r ) {
	$match   = array_merge( $match,   $m );
	$replace = array_merge( $replace, $r );
	return true;
  }
  return preg_replace( $match, $replace, $string );
}

//still encode entities
function xml22_cdata2ent ( &$tokens, $space = true ) {

  static $incdata = false;

  $newstr = array();
  $string = "";

  foreach ( $tokens as $token ) {
	if ( strstr( $token, '<![CDATA[' ) ) {
	  if ( preg_match( "/(.+)(<!\[CDATA\[)(.+)/", $token, $matches ) ) {
		array_shift($matches);
		$string = xml22_cdata2ent( $matches, false );
	  }
	  else { 
		$string  = $token;
		$incdata =   true; 
	  }
	}
	elseif ( strstr( $token, ']]' ) ) {
	  if ( preg_match( "/(.+)(\]\]>)(.+)/", $token, $matches ) ) {
		array_shift($matches);
		$string = xml22_cdata2ent( $matches, false );
	  }
	  else { 
		$string  = $token;
		$incdata =  false; 
	  }
	}
	else {
	  if ( $incdata ) { $string = $token; }
	  else            { $string = xml22_replace_entities( $token ); }
	}
	if ( $space ) { $string .= " "; }
	$newstr[] = $string;
  }
  return rtrim( implode( '', $newstr ) );
}

//private
//still encode entities
function xml22_str2ent( $string ) {

  // nothing to encode? go...
  if ( preg_match( "/^<(\?)|(!--)/", $string ) ) {
	return $string; 
  }
  // are there CDATA sections?
  if ( strstr( $string, '<![CDATA[' ) ) {
	$tokens = preg_split( '/\s/', $string );
	return xml22_cdata2ent( $tokens );
  }
  // general content
  else {
	return xml22_replace_entities( $string );
  }
}

?>
