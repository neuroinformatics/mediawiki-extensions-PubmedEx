<?php
/*L----------------------------------------------------------------------

xml22-edit.inc 

Version 0.4.0

Copyright (C) 2002,2003 Ingo Schramm

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

---------------------------------------------------------------------L*/

require_once("xml22-share.inc");

//------ EDITING

//
// array xml22_create_document( string $version [,string $standalone = "no", 
//                                                string $encoding = "" )
//
//
function xml22_create_document( $version = "1.0", $standalone = "no", $encoding = "" ) {

  if ( ! preg_match( "/^(\d+)\.(\d+)\.*(\d*)$/", $version ) ) {
	xml22_error("version string does not match");
	return false;
  }

  $str = "<?xml version=\"$version\" ";
  if ( $encoding ) {
	$str .= "encoding=\"$encoding\" ";
  }
  $str .= "standalone=\"$standalone\"?>";
  
  // $doc[0] = array(...); return $doc
  return array( array( 'tag'     => "",
					   'index'   => "0",
					   'level'   => "0",
					   'content' => $str
					   ));

}

//
// array xml22_create_fragment( array $firsttag )
//
//
function xml22_create_fragment( &$firsttag ) {

  if ( ! $firsttag = xml22_check_tag( $firsttag ) ) { return false; }

  $firsttag['index'] = "0";
  $firsttag['level'] = '0';
  if( isset( $firsttag['parindex'] ) ) { 
	unset($firsttag['parindex']);
  }
  //  $frag[] = $firsttag;
  //  return $frag;
  return array( $firsttag );
}

//
// array xml22_create_tag( string $name, [string $content = '',
//                                        array $template = false,
//                                        bool  $mode = XML22_BYSIBLING )
//
//
define( "XML22_BYSIBLING", 0 );
define( "XML22_BYPARENT",  1 );

function xml22_create_tag( $name, $content = '', $template = false, $mode = XML22_BYSIBLING ) {

  if ( $template ) {
	if ( ! $template = xml22_check_tag( $template ) ) { return false; }
	$tag['index']    = $template['index'] + 1;
	if  ( $mode ) { 
	  $tag['level']    = $template['level'] + 1; 
	  $tag['parindex'] = $template['index'];
	}
	else          { 
	  $tag['level']    = $template['level'];
	  if ( isset($tag['parindex']) ) {
		$tag['parindex'] = $template['parindex'];
	  }
	}
  }
  else {
	$tag['index']    = false;
	$tag['level']    = false;
	$tag['parindex'] = false;
  }
  $tag['tag'] = $name;
  if ( $content ) {	$tag['content'] = $content; }

  return $tag;
}

//
// array xml22_add_doctype( array $document, array $doctype )
//
//
function xml22_add_doctype( $document, $doctype ) {

  if ( ! xml22_is_document($document) ) {
	xml22_error("array is no document, cannot add doctype");
	return false; 
  }
  if ( ! xml22_check_array($doctype) )  { return false; }

  $dt = array();

  if ( ! isset($doctype['root']) ) {
	xml22_error("doctype misses name of root node");
	return false;
  }
  $dt[] = $doctype['root'];

  if ( isset($doctype['SYSTEM']) ) {
	$dt[] = 'SYSTEM';
	$dt[] = "\"$doctype[SYSTEM]\"";
  }
  elseif ( isset($doctype['PUBLIC']) ) {
	if ( ! isset($doctype['name']) ) {
 	  xml22_error("no 'name' specified for PUBLIC doctype");
 	  return false;
 	}
	$dt[] = 'PUBLIC';
	$dt[] = $doctype['name'];
	$dt[] = "\"$doctype[PUBLIC]\"";
  }
  else {
	xml22_error("no DTD specified in doctype");
 	return false;
  }

  // is there already a doctype?
  $search = array( 'tag' => '/^!DOCTYPE$/' );
  if ( $doctag = array_pop(xml22_get_by_regex( $document, $search, XML22_GET_FIRST ) )) {
	$document[$doctag['index']]['type'] = $dt;
	return $document;
  }
  else {
	// create doctype element
	$search  = array('content' => '/^<\?xml version/' );
	$version = array_pop(xml22_get_by_regex( $document, $search, XML22_GET_FIRST ));
	$tag = xml22_create_tag('!DOCTYPE', '', $document[$version['index']], XML22_BYSIBLING );
	$tag['type'] = $dt;
	// insert in document
	return xml22_insert_tag( $document, $tag );
  }
}

//
// array xml22_add_root( array $document, string $name, string $namespace )
//
//
function xml22_add_root( $document, $name, $namespace ) {

  if ( ! xml22_is_document($document) ) {
	xml22_error("array is no document, cannot add root");
	return false; 
  }
  // is there already a root node?
  if ( $root = xml22_get_root( $document ) ) {
	$document[$root['index']]['tag']        = $name;
	$document[$root['index']]['attributes'] = array( 'xmlns' => $namespace );
	return $document;
  }
  // empty document
  else {
	$search = array( 'level' => "/^0$/" );
	$last  = xml22_get_by_regex( $document, $search, XML22_GET_FIRST, 0, -1);

	$root             = xml22_create_tag( $name, '', $last, XML22_BYSIBLING );
	$root['parindex'] = 'self';
	$root['level']    = 0;
	$root['tag']      = $name;
	$root['attributes'] = array( 'xmlns' => $namespace );

    $document[0]['meta']['rootidx'] = $root['index'];

	return xml22_insert_tag( $document, $root);
  }

}

//
// array xml22_add_attribute( array $tag, string $name, string $value )
//
//
function xml22_add_attribute( $tag, $name, $value ) {

  if ( ! $tag = xml22_check_tag( $tag ) ) { return false; }
  $tag['attributes'][$name] = $value;
  return $tag;

}

//
// array xml22_delete_attribute( array $tag, string $name )
//
//
function xml22_delete_attribute( $tag, $name, $value = '' ) {

  if ( ! $tag = xml22_check_tag( $tag ) )   { return false; }
  if ( ! isset($tag['attributes']) )        { return $tag; }

  if ( isset( $tag['attributes'][$name]) ) {
	unset( $tag['attributes'][$name] );
  }
  else {
	xml22_error("no attribute '$name' found for deletion");
	return false;
  }
  if ( ! count( $tag['attributes'] ) ) {
	unset($tag['attributes'] );
  }
  return $tag;
}

define("XML22_OVWRT", 1 );
define("XML22_CAT",   2 );
define("XML22_CDATA", 1 );

//
// array xml22_add_content( array $tag, string $content
//                          [, bool $action = XML22_INSERT, 
//                             bool $cdata = 0 ] )
//
//
function xml22_add_content( $tag, $content, $action = XML22_OVWRT, $cdata = 0 ) {

  if ( ! $tag = xml22_check_tag( $tag ) ) { return false; }
  if ( ! isset($tag['content']) ) {	$tag['content'] = ""; }

  // cat or replace
  if ( $action & XML22_CAT ) {
	$tag['content'] .= $content;
  }
  else {
	$tag['content']  = $content;
  }
  // enclose in a CDATA?
  if ( $cdata ) {
	// if we have to enclose the content in a <![CDATA
	// delete all <![CDATA already inside of it
	$tag['content'] = str_replace('/<![CDATA[/', '', $tag['content'] );
	$tag['content'] = str_replace('/]]/', '', $tag['content'] );
	// and enclose the whole content
	$tag['content'] = '<![CDATA['.$tag['content'].']]';
  }
  return $tag;
}

define( 'XML22_DEL_CHILDREN', 1 );
//
// array xml22_delete_tag( array $document, array $tag )
//
//
function xml22_delete_tag( $document, $tag, $descend = false ) {

  if ( (! xml22_check_array($document)) ||
       (! $tag = xml22_check_tag($tag))    ) { return false; }

  $count = 1;
  if( $descend ) {
	if ( $lastchldx = xml22_get_last_child_index( $document, $tag ) ) {
	  //0.3.1
	  //$count = ( $lastchldx - $tag['index'] ) + 1;
	  //0.3.2
	  $count += ( $lastchldx - $tag['index'] );
	}
  }

  array_splice( $document, $tag['index'], $count );
  //remove me from parents children list
  if ( $tag['index'] > 0 ) {
 	if ( isset($document[$tag['parindex']]['children']) ) {
	  $flip = array_flip( $document[$tag['parindex']]['children'] );
	  unset( $document[$tag['parindex']]['children'][$flip[$tag['index']]] );
	}
  }
  return xml22_rebuild_indices( $document, $tag['index'], -$count );
}


//
// array xml22_insert_tag( array $document, array $tag, [mixed $ancestor = 0] )
//
//
function xml22_insert_tag( $document, &$tag, $_ancestor = 0 ) {

  if ( (! xml22_check_array($document)) ||
       (! $tag = xml22_check_tag($tag))    ) { return false; }

  //if ( ! xml22_check_array( $document ) ) { return false; }
  //if ( ! $tag = xml22_check_tag($tag) )   { return false; }

  // get my index
  if ( $_ancestor ) {
	//may be 0
	if ( ( $ancestor = xml22_get_tag_index( $_ancestor ) ) === false ) {
	  return false;
	}
	//$tag['index'] = $ancestor+1;
	$tag['index'] = $ancestor++;     
  }
  else { 
	$ancestor     = $tag['index'] - 1; 
  }
  // get parent's index
  if ( isset($tag['parindex']) ) {
	if ( ! $tag['parindex'] ) { 
	  if     ( $document[$ancestor]['level'] < $tag['level'] ) {
		$tag['parindex'] = $ancestor;
	  }
	  elseif ( $document[$ancestor]['level'] > $tag['level'] ) {
		$tag['parindex'] = $document[$document[$ancestor]['parindex']]['parindex'];
	  }
	  else {
		$tag['parindex'] = $document[$ancestor]['parindex'];
	  }
	}
  }
  // open a gap
  $document = xml22_rebuild_indices( $document, $tag['index'], 1 );

  // and fill it in
  $document[$tag['index']] = $tag;

  if ( isset( $tag['parindex'] ) ) {
	if ( ! strstr($tag['parindex'], 'self' ) ) {
	  $document[$tag['parindex']]['children'][] = $tag['index'];
	  sort( $document[$tag['parindex']]['children'], SORT_NUMERIC );
	}
  }
  ksort( $document );

  return $document;
}


//
// array xml22_replace_tag( array $document, array $oldtag, array $newtag )
//
//
function xml22_replace_tag( $document, $oldtag, &$newtag ) {

  if ( (! xml22_check_array($document))       ||
       (! $oldtag = xml22_check_tag($oldtag)) ||
       (! $newtag = xml22_check_tag($newtag))   ) { return false; }

  $index              = $oldtag['index'];
  $document[$index]   = $newtag;
  $newtag['index']    = $index;
  $newtag['level']    = $oldtag['level'];
  $newtag['parindex'] = $oldtag['parindex'];

  return $document;
}


//
// array xml22_move_tag( array &$document, array $tag, mixed $newancestor )
//
//
function xml22_move_tag( &$document, &$tag, $newancestor ) {

  if ( (! xml22_check_array($document)) ||
       (! $tag = xml22_check_tag($tag))   ) { return false; }

  return xml22_insert_tag( xml22_delete_tag( $document, $tag ), $tag, $newancestor );
}

//
// array xml22_copy_fragment( array &$document, array &$start, int $offset )
//
//
function xml22_copy_fragment( &$document, $start, $offset, $norm = true ) {

  if ( (! xml22_check_array($document))     ||
       (! $start = xml22_check_tag($start))   ) { return false; }

  //check if offset is in range
  $offset = xml22_frag_offset( $document, $start, $offset );
  //make fragment
  for( $i = $start['index']; $i <= ($start['index'] + $offset); $i++ ) {
	$frag[] = $document[$i];
  }
  if ( $norm ) {
	// normalize indices to base 0
	xml22_frag_rebuild_indices( $frag, -$start['index'], -$start['level'] );
  }

  return $frag;
}

//
// array xml22_delete_fragment( array $document, array &$start, int $offset )
//
//
function xml22_delete_fragment( $document, $start, $offset ) {

  if ( (! xml22_check_array($document))     ||
       (! $start = xml22_check_tag($start))   ) { return false; }

  $offset = xml22_frag_offset( $document, $start, $offset );
  $frag   = xml22_copy_fragment( $document, $start, $offset, false );
  array_splice( $document, $start['index'], ++$offset );
  xml22_frag_update_parent( $document, $frag, $start['index'], false );
  return xml22_rebuild_indices( $document, $start['index'], -$offset );
}


//
// array xml22_insert_fragment( array $document, array &$fragment, array &$ancestor )
//
//
function xml22_insert_fragment( $document, $fragment, &$ancestor ) {

  if ( (! xml22_check_array($document))           || 
       (! xml22_check_array($fragment))           ||
       (! $ancestor = xml22_check_tag($ancestor))   ) { return false; }

  $startidx = xml22_get_insert_idx( $document, $ancestor );

  if ( xml22_is_below_root( $ancestor ) === 0 ) { //ancestor is root
	$level = 1;
  }
  else { //ancestor is not root
	$level    = $ancestor['level'];
  }
  xml22_frag_rebuild_indices( $fragment, $startidx, $level);
  $document = xml22_rebuild_indices( $document, $startidx, count($fragment) );
  array_splice( $document, $startidx, 0, $fragment );
  xml22_frag_update_parent( $document, $fragment, $startidx, true );
  return $document;
}

//
// array xml22_replace_fragment( array $document, array &$fragment, array &$start )
//
//
function xml22_replace_fragment( $document, $fragment, &$start ) {

  if ( (! xml22_check_array($document))    ||
       (! xml22_check_array($fragment))    ||
       (! $start = xml22_check_tag($start))  ) { return false; }

  $oldfrag = xml22_copy_fragment( $document, $start, ( count($fragment - 1) ), false );
  xml22_frag_update_parent( $document, $oldfrag, $start['index'], false );
  xml22_frag_rebuild_indices( $fragment, $start['index'], $start['level']);
  //replace tag by tag
  foreach ( $fragment as $tag ) {
	$document[$tag['index']] = $tag;
  }
  xml22_frag_update_parent( $document, $fragment, $start['index'], true );
  return $document;
}


//
// array xml22_move_fragment( array &$document, array $start, int $offset, array &$newancestor )
//
//
function xml22_move_fragment( &$document, $start, $offset, $newancestor ) {

  if ( (! xml22_check_array($document))                 ||
       (! $start       = xml22_check_tag($start))       ||
       (! $newancestor = xml22_check_tag($newancestor))   ) { return false; }

  $fragment = xml22_copy_fragment( $document, $start, $offset );

  //changing document
  $doc = xml22_delete_fragment( $document, $start, $offset );
  //restore newancestor
  if ( $newancestor['index'] >= $start['index'] ) {
	$srchfrom = $newancestor['index'] - ( $offset + 1 );
  }
  else {
	$srchfrom = $newancestor['index'];
  }
  $newancestor = $doc[$srchfrom];
  //insert
  return xml22_insert_fragment( $doc, $fragment, $newancestor );
  //return $doc;

}


//
// array xml22_add_child( array $document, array $tag, array $parent )
//
//
function xml22_add_child( $document, &$tag, $parent ) {

  if ( ! $parent = xml22_check_tag($parent) ) { return false; }

  $tag['level']    = $parent['level'] + 1;
  $tag['parindex'] = $parent['index'];

  $tag['index'] = xml22_get_insert_idx( $document, $parent );

  return xml22_insert_tag( $document, $tag );
}

//
// array xml22_add_sibling( array $document, array $tag, array $ancestor )
//
//
function xml22_add_sibling( $document, &$tag, $ancestor ) {

  if ( ! $ancestor = xml22_check_tag($ancestor) ) { return false; }
  
  $tag['level']    = $ancestor['level'];
  $tag['parindex'] = $ancestor['parindex'];

  $tag['index'] = xml22_get_insert_idx( $document, $ancestor );

  return xml22_insert_tag( $document, $tag );
}

//
// array xml22_add_comment( array $document, string $comment, mixed $ancestor )
//
//
function xml22_add_comment( $document, $comment, $ancestor ) {

  if ( ! ($_ancestor = xml22_get_tag_index( $ancestor )) ) {
	return false;
  }

  $comment = "<!-- $comment -->";
  $tag['tag']      = "";
  if ( isset($document[$_ancestor]['children']) ) {
	$tag['level']    = $document[$_ancestor]['level'] + 1;
  }
  else {
	$tag['level']    = $document[$_ancestor]['level'];
  }
  $tag['index']    = $_ancestor + 1;
  $tag['parindex'] = $_ancestor;
  $tag['content']  = $comment;

  return xml22_insert_tag( $document, $tag );
}


//PRIVATE

//private
function xml22_get_insert_idx( &$document, &$parent ) {
  if ( $lastchidx = xml22_get_last_child_index( $document, $parent ) ) {
	return $lastchidx + 1;
  }
  else {
	return $parent['index'] + 1;
  }
}


//private
function xml22_frag_get_uppers( &$fragment ) {

  $upperlv = 65536;
  $uptags  = array();

  foreach ( $fragment as $tag ) {
	if ( $upperlv > $tag['level'] ) { 
	  $upperlv = $tag['level']; 
	}
	$uptags[$tag['level']][] = $tag;
  }
  if ( count( $uptags[$upperlv] ) ) { 
	return $uptags[$upperlv]; 
  }
  return false;
}

//private
function xml22_frag_update_parent( &$document, &$fragment, $paridx, $action ) {

  if( $uptags = xml22_frag_get_uppers( $fragment ) ) {
	if ( $action ) {
	  do {
		$paridx--;
		$parlevel = $document[$paridx]['level'];
	  }
	  while( ($parlevel >= $uptags[0]['level'] ) && ($paridx > 0) );
	}
	foreach( $uptags as $tag ) {
	  if ( $action ) { 
		$document[$paridx]['children'][]     = $tag['index'];
		$document[$tag['index']]['parindex'] = $paridx;
		sort( $document[$paridx]['children'] );
	  }
	  else { 
		if ( isset($document[$tag['parindex']]['children']) ) {
		  $flip = array_flip( $document[$tag['parindex']]['children'] );
		  unset( $document[$tag['parindex']]['children'][$flip[$tag['index']]] );
		}
	  }
	}//foreach
  }//if $uptags
  return true;
}

//private
// $index and $level < 0 means: delete
function xml22_frag_rebuild_indices( &$fragment, $index, $level ) {

  foreach ( $fragment as $idx => $tag ) {
	$fragment[$idx]['index'] += $index;
	if ( $tag['level'] ) {
	  if ( isset($fragment[$idx]['parindex']) ) {
		$fragment[$idx]['parindex'] += $index;
		if ( $fragment[$idx]['parindex'] < 0 ) {
		  // there is no parent at all
		  unset( $fragment[$idx]['parindex'] );
		}
	  }
	}
	$fragment[$idx]['level'] += $level;
	//update fragments children list
	if ( isset( $tag['children'] ) ) {
	  foreach ( $tag['children'] as $cidx => $chld ) {
		$fragment[$idx]['children'][$cidx] += $index;
	  }
	}
  }
  return true;
}

//private
function xml22_frag_offset( &$document, &$start, $offset ) {
  if ( ( $start['index'] + $offset ) > count($document) ) {
	$offset = count($document) - ( $start['index'] + 1 );
  }
  return $offset;
}
?>
