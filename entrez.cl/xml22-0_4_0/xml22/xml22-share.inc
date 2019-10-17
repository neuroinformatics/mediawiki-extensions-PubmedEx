<?php
/*L----------------------------------------------------------------------

xml22-share.inc 

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

// misc private

// private
function xml22_check_array( &$array ) {
  if ( ! is_array($array) ) { 
	xml22_error("variable is not an array");	
	return false; }
  if ( ! count($array) )    { 
	xml22_error("array is empty");
	return false; 
  }
  return true;
}

//private
function xml22_check_tag( &$tag ) {

  if ( ! xml22_check_array ( $tag ) ) { return false; }
  if ( ! isset( $tag['index'] ) ) {
	$tag = array_pop( $tag );
	if( ! xml22_check_array( $tag ) ) {
	  return false;
	}
  }
  if ( ! isset( $tag['index'] ) ) { 
	xml22_error("tag misses 'index' field");
	return false;
  }
  if ( ! isset( $tag['level'] ) ) { 
	xml22_error("tag of document index $tag[index] misses 'level' field");
	return false;
  }
  //0.3.2
  if ( ! isset($tag['tag']) ) {
	xml22_error("tag of document index $tag[index] misses 'tag' field");
	return false;
  }
  return $tag;
}

//private
function xml22_rebuild_indices( &$document, $start, $length ) {

  $buffer = array();

  // shift all indices for length
  // starting at index of the (last) inserted tag
  foreach(  $document as $index => $tag ) {
	if ( $tag['index'] >= $start ) {
	  $tag['index']    += $length;
	}
	if ( isset($tag['parindex']) ) {
	  if ( $tag['parindex'] >= $start ) {
		$tag['parindex'] += $length;
	  }
	}

	// rebuild also pointers to children
	if ( isset( $tag['children'] ) ) {
	  foreach( $tag['children'] as $key => $index ) {
		if ( $index >= $start ) {
		  $tag['children'][$key] += $length;
		}
	  }
	}
	if( $tag ) { 
	  $buffer[$tag['index']] = $tag;
	}
  }
  ksort( $buffer );
  return $buffer;
}

//private
function xml22_tab( $tabsize = 3 ) {

  static $tab   = '';

  if ( $tab ) { return $tab; }

  for( $i = 0; $i < $tabsize; $i++ ) {	$tab .= " "; }
  return $tab;
}

//private
function xml22_get_tag_index( &$tag ) {

  if ( $tag = xml22_check_tag( $tag ) ) {
	return $tag['index'];
  }
  elseif ( is_int( $tag ) ) { 
	return $tag;
  }
  else {
	xml22_error("variable neither array nor int");
	return false;
  }
}

//private 
function xml22_get_last_child_index( &$document, &$tag ) {
  if ( isset( $tag['children'] ) ) {
	$lastchild = array_pop( xml22_get_all_descendants($document, $tag) );
	return $lastchild['index'];
  }
  return false;
}


//private
function xml22_get_from_dtd( &$doc, $type, $name ) {

  $search  = array( 'tag' => '/^!DOCTYPE$/' );
  $doctype = array_shift( xml22_get_by_regex( $doc, $search, XML22_GET_FIRST, 1 ) );
  if ( isset( $doctype[$type] ) ) {
	if ( $name ) {
	  if ( isset( $doctype[$type][$name] ) ) {
		//return $doctype[$type][$name];
        $tag = $doc[$doctype[$type][$name]];
        return substr_replace( $tag['content'], '', 0, strlen($name) );
	  }
	}
	else {
	  return $doctype[$type];
	}
  }
  return false;
}

?>