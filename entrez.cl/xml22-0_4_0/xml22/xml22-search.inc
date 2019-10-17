<?php
/*L----------------------------------------------------------------------

xml22-search.inc 

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

//SEARCHING

define("XML22_GET_ALL",   0 );
define("XML22_GET_FIRST", 1 );


//
// array xml22_get_all_siblings( array &$document, array &$tag );
//
// Note: returned array *includes* search
function xml22_get_all_siblings( &$document, &$tag ) {

  if ( (! xml22_check_array($document))   ||
       (! $search = xml22_check_tag($tag))  ) { return false; }

  $tokens = array();

  if ( isset($document[$search['parindex']]['children'])   &&    // prevent warnings
	   is_array($document[$search['parindex']]['children'])   ) {
	foreach ( $document[$search['parindex']]['children'] as $key => $index ) {
	  $tokens[$index] = $document[$index];
	}
	return $tokens;
  }
  return false;
}


//
// array xml22_get_all_descendants( array &$document, array &$tag );
//
//
function xml22_get_all_descendants( &$document, &$tag ) {

  if ( (! xml22_check_array($document)) ||
       (! $tag = xml22_check_tag($tag)) ||
       (! isset($tag['children']))        ) { return false; }

  $buffer = array();

  foreach ( $tag['children'] as $key => $index ) {
	$buffer[$index] = $document[$index];
	if ( $result = xml22_get_all_descendants( $document, $document[$index] ) ) {
	  foreach( $result as $index => $array ) {
		// we don't use array_merge() to preserve document indices 
		$buffer[$index] = $result[$index];
	  }
	}
  }
  return $buffer;
}


//
// array xml22_get_all_ancestors( array &$document, array $tag );
//                              
//
function xml22_get_all_ancestors( &$document, &$tag ) {

  if ( (! xml22_check_array($document))     ||
       (! $current = xml22_check_tag($tag))   ) { return false; }

  $tokens = array();

  while ( isset($document[$current['parindex']]) ) {
	$tokens[$current['parindex']] = $document[$current['parindex']];
	$current = $document[$current['parindex']];
  }
  return $tokens;
}


define("XML22_START_ROOT", true );
//
// array xml22_get_all_of_name( array &$document, string $name )
//
//
function xml22_get_all_of_name( &$document, $name, $mode = false) {

  $search = array( 'tag' => "|^$name$|" );

  return xml22_get_all( $document, $search, $mode );
}


//
// array xml22_get_all_of_content( array &$document, string $regex )
//
//
function xml22_get_all_of_content( &$document, $regex, $mode = false ) {

  $search = array( 'content' => $regex );

  return xml22_get_all( $document, $search, $mode );
}


//
// array xml22_get_first_child( array &$document, array &$parent )
//
//
function xml22_get_first_child( &$document, $parent ) {

  return xml22_get_child($document, $parent, 'shift' );
}


//
// array xml22_get_last_child( array &$document, array &$parent )
//
//
function xml22_get_last_child( &$document, $parent ) {

  return xml22_get_child($document, $parent, 'pop' );
}


//
// array xml22_get_next_sibling( array &$document, array &$tag )
//
//
function xml22_get_next_sibling( &$document, $tag ) {

  return xml22_get_sibling( $document, $tag, 1 );
}


//
// array xml22_get_prev_sibling( array &$document, array &$tag )
//
//
function xml22_get_prev_sibling( &$document, $tag ) {

  return xml22_get_sibling( $document, $tag, -1 );
}


//
// array xml22_get_parent( array &$document, array &$tag )
//
//
function xml22_get_parent( &$document, $tag ) {

  if ( (! xml22_check_array($document) ) || 
       (! $tag = xml22_check_tag($tag) ) ||
       ( $tag['level'] == 0 )              ) { return false; }

  if ( isset( $tag['parindex'] ) ) {
	return $document[$tag['parindex']];
  }
  return false;
}

//
// array xml22_get_root( array &$document )
//
//
function xml22_get_root( &$document ) {

  if ( isset($document[0]['meta']['rootidx']) ) {
	return $document[$document[0]['meta']['rootidx']];
  }
  else {
	return false;
  }
}

//
// array xml22_get_by_id( array $&document, string $id [, string $idname = ""] )
//
//
function xml22_get_by_id( &$document, $id, $idname = ".*(id|ID)" ) {

//0.3.5
//   if ( ! $idname ) {
// 	$idname = ".*(id|ID)";
//   }
  $search = array( 'attributes' => array( "^$idname$" => "/^$id$/" ) );

  $tag = xml22_get_by_regex( $document, $search,
							 XML22_GET_FIRST, xml22_get_rootindex($document) );
  if ( ! $tag ) { return false; }

  return array_pop( $tag );
}

//
// string xml22_get_name( array &$tag )
//
//
function xml22_get_name( &$tag ) {

  if ( ! $tag = xml22_check_tag( $tag ) ) { return false; }

  return $tag['tag'];
}

//
// string xml22_get_content( array &$tag )
//
//
function xml22_get_content( &$tag, $plain = true, $rplc = '' ) {

  if ( ! $tag = xml22_check_tag( $tag ) ) { return false; }
  if ( ! isset($tag['content']) ) {
	xml22_error("tag of document index $tag[index] has no content");
	return false;
  }
  if ( $plain ) {
	return $tag['content'];
  }
  else {
	$str = str_replace( '<![CDATA[', $rplc, $tag['content'] );
	$str = str_replace( ']]>',       $rplc, $str            );
	return $str;
  }
}


//
// string xml22_get_attribute( array &$tag, string $attrname )
//
//
function xml22_get_attribute( &$tag, $attrname ) {

  if ( ! $tag = xml22_check_tag( $tag ) ) { return false; }
  if ( ! isset($tag['attributes']) ) {
	xml22_error("tag of document index $tag[index] has no attributes");
	return false;
  }
  
  foreach( $tag['attributes'] as $attr => $val ) {
	if ( preg_match("/^$attrname$/", $attr ) ) {
	  return $val;
	}
  }
  return false;
}

//
// array xml22_get_version( array &$document )
//
//
function xml22_get_version( &$document ) {

  //for valid XML, version always at position 0
  if ( (! xml22_check_array($document))         ||
       (! $tmp = xml22_check_tag($document[0]))   ) { return false; }
  
  $tmp['content'] = preg_replace( '/(<\?|\?>)/', '', $tmp['content'] );
  $tmp            = preg_split("/\s+/", $tmp['content']);
  foreach( $tmp as $piece ) {
	$piece = trim($piece);
	if ( $arr = explode("=", $piece ) ) {
	  if ( ! isset( $arr[1] ) ) { continue; }
	  $arr[1] = str_replace( '"', '', $arr[1] );
	  switch ( $arr[0] ) {
	  case 'version':
		$version['version'] = $arr[1];
		break;
	  case 'standalone':
		$version['standalone'] = $arr[1];
		break;
	  case 'encoding':
		$version['encoding'] = $arr[1];
		break;
	  }
	}
  }
  return $version;
}

//
// string xml22_get_doctype( array &$document )
//
//
function xml22_get_doctype( &$document ) {

  if ( ! xml22_check_array($document) ) { return false; }

  $search = array( 'tag' => '/^!DOCTYPE$/');
  $dt = array_pop( xml22_get_by_regex( $document, $search, XML22_GET_FIRST, 1 ));
  if ( ! count($dt) ) { return false; }

  if ( isset( $dt['type'] ) ) {
	$doctype['root'] = $dt['type'][0];
	if ( count( $dt['type'] ) > 3 ) {
	  $doctype['name']   = str_replace('"', '', $dt['type'][2] );
	  $doctype['PUBLIC'] = str_replace('"', '', $dt['type'][3] );
	}
	else {
	  $doctype['SYSTEM'] = str_replace('"', '', $dt['type'][2] );
	}
  }
  return $doctype;
}


//
// mixed xml22_get_entity( string &$doc, string $name )
//
//
function xml22_get_entity( &$doc, $name = '' ) {

  if ( ! xml22_check_array($doc) ) { return false; }

  return xml22_get_from_dtd( $doc, 'entities', $name );

}

//
// mixed xml22_get_notation( string &$doc, string $name )
//
//
function xml22_get_notation( &$doc, $name = '' ) {

  if ( ! xml22_check_array($doc) ) { return false; }

  return xml22_get_from_dtd( $doc, 'notations', $name );

}


//
// bool xml22_has_children( array &$tag )
//
//
function xml22_has_children( &$tag ) {

  if ( ! $tag = xml22_check_tag( $tag ) ) { return false; }

//   if ( isset( $tag['children'] ) ) { return true; }
//   return false;
  if ( isset( $tag['children'] ) ) { return count($tag['children']); }
  return 0;
}

//
// int xml22_is_below_root( array &$tag )
//
//
function xml22_is_below_root( &$tag ) {

  if ( ! $tag = xml22_check_tag( $tag ) ) { return false; }

  if ( $tag['level'] ) { return 1; }
  else {
	if ( isset( $tag['parindex']) ) { return 0; }
    return -1;
  }
}

//
// bool xml22_is_tag( array &$search );
//
//
function xml22_is_tag( &$search ) {
  if ( xml22_get_type( $search) == 1 ) { return true; }
  return false;
}

//
// bool xml22_is_fragment( array &$search );
//
//
function xml22_is_fragment( &$search ) {
  if ( xml22_get_type( $search) == 2 ) { return true; }
  return false;
}

//
// bool xml22_is_tag( array &$search );
//
//
function xml22_is_document( &$search ) {
  if ( xml22_get_type( $search) == 3 ) { return true; }
  return false;
}


//
// array xml22_get_by_regex( array &$document, array $$query, 
//                          [int $tokencount, 
//                           int $startindex, int $offset] )
//
function xml22_get_by_regex( &$document, &$query, $tokencount = XML22_GET_ALL,
							 $startindex = 0, $offset = 0 ) {

  if ( ! xml22_check_array($document) ) { return false; }

  $tokens     = array();
  $buffer     = array();
  $matchcount = 0;
  $attrmatch  = 0;
  $idxcount   = 0;

  if ( ! $offset ) { $offset = count($document); }
 
  if ( $offset < 0 ) { 
	$document   = array_reverse($document);
	$endindex   = $startindex - $offset;
  }
  else {
	$endindex   = $startindex + $offset;
  }

  // start search
  foreach( $document as $i => $tag ) {
	if ( $idxcount < $startindex ) { $idxcount++; continue; } // we pay the price
	if ( $idxcount > $endindex )   { break; }
	$matchcount       = 0;
	$searchattributes = true;

	foreach( $query as $qukey => $quregex ) {
	  //attributes
	  if ( ( $searchattributes)       &&
		   ( $qukey == 'attributes')  &&
		   ( is_array($quregex))         ) {
		if ( isset($tag['attributes']) ) { // prevent warnings
		  $attrmatch = 0;
		  foreach( $tag['attributes'] as $attrname => $attrval ) {
			foreach( $quregex as $quname => $attrregex ) {
			  // because of this regex search we cannot access by hash key
			  // and need two loops :(
			  // the regex search is used only by xml22_get_by_id, so
			  // if we once know which attributes are of type ID, we can skip the loop
			  if ( preg_match("/$attrname/", $quname ) ) {
				if( preg_match($attrregex, $attrval )) {
				  $attrmatch++;
				}
			  }
			}
		  }
		  // AND, all 'attributes' queries must match
		  if( count($quregex) == $attrmatch ) {
			$matchcount++;
			$searchattributes = false;
		  }
		}
	  }//attributes
	  //non attribute query
	  else {
		//access by hash key
		if( isset($tag[$qukey]) ) {
		  if ( ! is_array( $quregex ) ) {           // prevent warnings
			if( preg_match($quregex, $tag[$qukey] )) {
			  $matchcount++;
			}
		  }
		}
	  }//non attribute query
	}//foreach $query
	// AND, all queries must match
	if ( $matchcount >= count($query) ) {
	  $tokens[] = $tag;
	}
	// do we already have enough?
	if ( $tokencount ) {
	  if ( count($tokens) == $tokencount ) {
		break;
	  }
	}
	$idxcount++;
  }//foreach $document
  // call-by-reference, we should rebuild the array if changed
  if ( $offset < 0 ) { $document = array_reverse($document); }
  return $tokens;
}


//PRIVATE

//private
function xml22_get_type( &$search ) {

  if ( ! xml22_check_array ( $search ) ) { return false; }
  
  if ( isset( $search['index'] ) ) { return 1; } //tag
  else {
	//0.3.2, added to check fragments that aren't normalized to 0
	$keys = array_keys($search);
	if ( ! isset($search[$keys[0]]['index']) ) { return false; }
	if ( isset( $search[$keys[0]]['content'] ) ) {
	  if ( preg_match( "/^\s*<\?xml/", $search[$keys[0]]['content'] ) ) {
		return 3; //doc
	  }
	}
	return 2; //frag
  }
  return false;
}




//private
function xml22_get_rootindex(&$document) {

  //$document may be a fragment...
  if ( isset($document[0]['meta']['rootidx']) ) {
	return $document[0]['meta']['rootidx'];
  }
  return 0;
}


//private
function xml22_get_all( &$document, $search, $mode = false ) {

  if ( ! xml22_check_array($document) ) { return false; }
  
   $startcount = 0;
   if ( $mode ) {
	 $startcount = xml22_get_rootindex($document);
   }

  return xml22_get_by_regex( $document, $search, XML22_GET_ALL, $startcount );
}

//private
function xml22_get_child( &$document, $parent, $mode ) {

  if ( (! xml22_check_array($document))    ||
       (! $par = xml22_check_tag($parent))   ) { return false; }

  //make function name
  $action = 'array_'.$mode;

  if ( isset( $par['children'] ) ) {
	if ( is_array( $par['children'] ) ) {
	  if ( count($par['children']) ) {
		$child = $action($par['children']);
		if ( isset( $document[$child] ) ) {
		  return $document[$child];
		}
	  }
	}
  }
  return false;
}

//private
function xml22_get_sibling( &$document, $tag, $indexer ) {

  if ( (! xml22_check_array($document)) ||
       (! $tag = xml22_check_tag($tag))   ) { return false; }

  $parent   = $tag['parindex'];
  $siblings = $document[$parent]['children'];
  $flip     = array_flip( $siblings );
  $sb       = $flip[$tag['index']] + $indexer;
  if ( isset( $siblings[$sb] ) ) {
	if ( isset( $document[$siblings[$sb]] ) ) {
	  return $document[$siblings[$sb]];
	}
  }
  return false;
}


?>