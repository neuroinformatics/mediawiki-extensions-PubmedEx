<?php

error_reporting(E_ALL);

$file  = "test.xml";
$args  = array();
$tests = array();
$path        = realpath("../");

$_SC_CLK_TCK = 100;
$TESTREPEAT  = 100;
$DUMP        = true;
$PROFILE     = true;


if ( isset( $SCRIPT_FILENAME ) ) {
  header("Content-type:text/plain");  
}
else {
  $SCRIPT_FILENAME = $argv[0];
  if ( $argc ) {
	foreach ( $argv as $arg ) {
	  switch ( $arg ) {
	  case $argv[0]:
		continue;
	  case '-h':
	  case '--help':
print $SCRIPT_FILENAME; ?> [options]
Options:
		-h, --help       : help
		--[no]dump       : dump parser results
		--[no]profile    : print profiling information
		--repeat=<num>   : repeat test <num> times
<?php
		exit();
	  case "--dump":
		$DUMP = true;
		continue;
	  case "--nodump":
		$DUMP = false;
		continue;
	  case '--profile':
		$PROFILE = true;
		continue;
	  case '--noprofile':
		$PROFILE = false;
		continue;
	  default:
		if ( preg_match('/^--repeat/', $arg ) ) {
		  $split = preg_split('/\s*=\s*/', $arg);
		  $TESTREPEAT = $split[1];
		}
		else {
		  print "unknown command line switch: $arg\n";
		  exit();
		}
	  }
	}
  }
}

define( "TESTMSG",    "execute $TESTREPEAT runs\n");
define( "PARSING",    1 );
define( "SEARCHING",  2 );
define( "EDITING",    4 );

ini_set("include_path", "$path:$path/xml22" );
include "xml22.inc";

define( "_SC_CLK_TCK", 100); 

if ( $PROFILE ) {
?>
Fields of time statistics are: 
real time in seconds, user cpu time in seconds, system cpu time in seconds.
Scince there is no way to call sysconf() via PHP we assume _SC_CLK_TCK
to equal <?php print $_SC_CLK_TCK ?>.

<?php
}

//FUNCTIONS
define( "INIT", 1 );
function getmicrotime( $init = 0 ){

  static $starttime;

  if ( $init ) {
	list($usec, $sec) = explode(" ",microtime());
	$starttime        = ((float)$usec + (float)$sec);
	return $starttime;
  }
  else {
	list($usec, $sec) = explode(" ",microtime());
	$now =  ((float)$usec + (float)$sec);
	$time = round( ($now - $starttime), 4 );
	$starttime = $now;
	return $time;
  }
}

function gettimes( $init = 0 ) {

  global $_SC_CLK_TCK;

  static $lasttimes;

  if ( $init ) {
	$lasttimes = posix_times();
	return $lasttimes;
  }
  else {
	$nowtimes = posix_times();
	foreach ( $nowtimes as $key => $val ) {
	  $times[$key] = ($val / $_SC_CLK_TCK) - ($lasttimes[$key] / $_SC_CLK_TCK);
	}
	$lasttimes = $nowtimes;
	return $times;
  }
}

function print_timestats( $runs ) {

  global $TESTREPEAT;

  if ( ! count($runs) ) {
	print "Error: empty set, cannot determine time statistics\n";
	return FALSE;
  }

  foreach ( $runs[0] as $name => $val ) {
	$min[$name]     = $val;
	$max[$name]     = 0;
	$total[$name]   = 0;
	$average[$name] = 0;
  }

  foreach( $runs as $num => $time ) {
	foreach( $time as $name => $val ) {
	  $total[$name] += $val;
	  if ( $min[$name] > $val ) { $min[$name] = $val; }
	  if ( $max[$name] < $val ) { $max[$name] = $val; }
	}
  }
  
  foreach ( $total as $name => $val ) {
	$average[$name] = $total[$name] / $TESTREPEAT;
  }

  print sprintf("total:    %02.4ft %01.6fu %01.6fs\n", 
				$total['time'], ($total['utime']), 
				($total['stime']));
  print sprintf("min:      %02.4ft %01.6fu %01.6fs\n", 
				$min['time'], ($min['utime']), 
				($min['stime']));
  print sprintf("max:      %02.4ft %01.6fu %01.6fs\n", 
				$max['time'], ($max['utime']), 
				($max['stime']));
  print sprintf("average:  %02.4ft %01.6fu %01.6fs\n", 
				$average['time'], ($average['utime']), 
				($average['stime']));

  flush();

}

function print_teststats() {

  global $tests;
  global $TESTREPEAT;

  print sprintf("\n***performed %d tests in total\n", ( count($tests) * $TESTREPEAT ) );

  foreach( $tests as $num => $ok ) {
	print sprintf("testrun %02d: %d ok %d failed\n", $num, $ok, ($TESTREPEAT - $ok) );
  }

}

function headmsg( $msg ) {

  print "\ntesting: $msg\n";
  print TESTMSG;
  flush();

}

function dump_tag( $tag ) {

  if ( isset( $tag['tag'] ) ) {
	print "<$tag[tag] ";
	if ( isset( $tag['attributes']) ) {
	  foreach ( $tag['attributes'] as $name => $val ) {
		print "$name=\"$val\"";
	  }
	}
	print ">";
	if ( isset( $tag['content'] ) ) {
	  print $tag['content'];
	}
	print "</$tag[tag]>\n";
	return TRUE;
  }
  else {
	if ( isset( $tag['content'] ) ) { print "$tag[content]\n"; }
	return TRUE;
  }
  print "Nothing to dump\n";
  return FALSE;
}

// mixed run_test( string $function, array $args bool $dump, string $addmsg = '' )
//
// $function - name of function
//
function run_test( $function, &$args, $dump = TRUE, $addmsg = '' ) {

  global $tests;
  global $TESTREPEAT;
  global $DUMP;
  global $PROFILE;

  if ( ! $DUMP ) { $dump = FALSE; }

  static $testnum;
  $testnum++;

  $failed = 0;

  $msg = $function;
  if ( $addmsg ) { $msg .= " (".$addmsg.")"; }
  headmsg( $msg );

  $tests[$testnum] = 0;

  $runs = array();
  getmicrotime(INIT);
  gettimes(INIT);
  for ( $i = 0; $i < $TESTREPEAT; $i++ ) {

	switch ( $function ) {
	case 'xml22_replace_tag':
	  $result = $function( $args['doc'], $args['search'], $args['tag'] );
	  break;
	case 'xml22_delete_tag':
	  $result = $function( $args['doc'], $args['tag'] );
	  break;
	case 'xml22_insert_tag':
	  $result = $function( $args['doc'], $args['tag']);
	  break;
	case 'xml22_move_tag':
	  $result = $function( $args['doc'], $args['search'], $args['newancestor']);
	  break;
	case 'xml22_write_document':
	  $fd = tmpfile();
	  $result = $function( $args['doc'], $fd, $args['mode'] );
	  rewind($fd);
	  if ( $i == $TESTREPEAT - 1 ) {
		while ( $line = fgets($fd, 4096) ) {
		  print $line;
		}
		fclose( $fd);
	  }
	  break;
	case 'xml22_create_document':
	  $result = $function( $args['version'] );
	  break;
	case 'xml22_add_doctype':
	  $result = $function( $args['doc'], $args['type'] );
	  break;
	case 'xml22_add_root':
	  $result = $function( $args['doc'], $args['name'], $args['xmlns'] );
	  break;
	case 'xml22_add_child':
	case 'xml22_add_sibling':
 	  $result = $function( $args['doc'], $args['tag'], $args['anc'] );
 	  break;
	case 'xml22_add_attribute':
	  $result = $function( $args['tag'], $args['name'], $args['val'] );
	case 'xml22_add_comment':
	  if ( ! $i && $DUMP ) {
		print "insert: $args[string]\n";
	  }
	  $result = $function( $args['doc'], $args['string'], $args['anc'] );
	  break;
	case 'xml22_copy_fragment':
	case 'xml22_delete_fragment':
	  $result = $function( $args['doc'], $args['start'], $args['off'] );
	  break;
	case 'xml22_insert_fragment':
	  $result = $function( $args['doc'], $args['frag'], $args['anc'] );
	  break;
	case 'xml22_move_fragment':
	  $result = $function( $args['doc'], $args['start'], $args['off'], $args['anc'] );
	  break;
	default:
	  print "unknown test target: $function\n";
	  return FALSE;
	}

	if( ! $result ) {
	  $failed++;
	}
	else {
	  $runs[$tests[$testnum]]['time']  = getmicrotime();
	  $times                           = gettimes();
	  $runs[$tests[$testnum]]['utime'] = $times['utime'];
	  $runs[$tests[$testnum]]['stime'] = $times['stime'];
	  $tests[$testnum]++;
	}
  }

  if ( $failed ) { print "$failed failed\n"; }

  if ( $PROFILE ) { print_timestats( $runs ); }

  if ( $result && $dump ) {
	print "dumping result:\n";
	print xml22_write_document_str( $result );
  }

  return $result;

}

//---MAIN---------------------------//

$args['file'] = $file;
$args['doc']  = NULL;

print "running test set, be patient...\n\n";

$setup = array( 'XML22_OPT_WRITESTYLE' => XML22_BEAUTIFY,
                'XML22_OPT_EXTERNALS'  => TRUE );
xml22_setup( $setup );

//------------------
print "\n***editing tests:\n";

//create_document
$args['version'] = '1.0';
$args['doc'] = run_test('xml22_create_document', $args );

//add_doctype
$args['type'] = array( 'root' => 'staff', 'SYSTEM' => 'staff.dtd' );
$args['doc']  = run_test('xml22_add_doctype', $args );

//add_root
$args['name']  = 'staff';
$args['xmlns'] = 'http://www.w3c.org/XML/test/';
$args['doc']   = run_test('xml22_add_root', $args );

//add_child
$args['anc'] = xml22_get_root($args['doc']);
$args['tag'] = xml22_create_tag( 'employee', '');
$args['tag'] = xml22_add_attribute( $args['tag'], 'id', 'EMP-0001');
$args['tag'] = xml22_delete_attribute( $args['tag'], 'id');
$args['tag'] = xml22_add_attribute( $args['tag'], 'id', 'EMP0001');
$args['doc'] = run_test('xml22_add_child', $args, TRUE, 'included hidden tests of:
xml22_create_tag, xml22_add_attribute, xml22_delete_attribute' );

$args['anc'] = $args['tag'];
$args['tag'] = xml22_create_tag( 'EmployeeID', 'EMP0001');
$args['doc'] = run_test('xml22_add_child', $args );

//add_sibling
$args['anc'] = $args['tag'];
$args['tag'] = xml22_create_tag( 'name', 'Margaret Miller');
$args['doc'] = run_test('xml22_add_sibling', $args );

$args['anc'] = $args['tag'];
$args['tag'] = xml22_create_tag( 'position', 'Cleaner');
$args['doc'] = run_test('xml22_add_sibling', $args );

//add_child
$args['anc'] = xml22_get_root( $args['doc'] );
$args['tag'] = xml22_create_tag( 'employee', '');
$args['tag'] = xml22_add_attribute( $args['tag'], 'id', 'EMP0002');
$args['doc'] = run_test('xml22_add_child', $args);

$args['anc'] = $args['tag'];
$args['tag'] = xml22_create_tag( 'EmployeeID', '');
$args['tag'] = xml22_add_content( $args['tag'], xml22_get_attribute( $args['anc'], 'id' ));
$args['doc'] = run_test('xml22_add_child', $args, TRUE,  'included hidden tests of:
xml22_add_content, xml22_get_attribute');

//add_comment
$args['anc']    = xml22_get_root( $args['doc'] );
$args['string'] = 'the staff list starts here';
$args['doc']    = run_test('xml22_add_comment', $args);

//copy_fragment
$safe          = $args['doc'];
$args['doc']   = xml22_parse('staff.xml');
$args['start'] = xml22_get_last_child( $args['doc'], xml22_get_root( $args['doc'] ) );
$args['off']   = count( xml22_get_all_descendants($args['doc'], $args['start']) );
$args['frag']  = run_test('xml22_copy_fragment', $args, TRUE, 'copy from staff.xml' );
$args['doc']   = $safe;
unset( $safe );

//insert_fragment
$args['anc']   = xml22_get_last_child( $args['doc'], xml22_get_root( $args['doc'] ) );
$args['doc']   = run_test('xml22_insert_fragment', $args );

//delete_fragment
$args['start'] = xml22_get_by_id( $args['doc'], 'EMP0002' );
$args['off']   = count( xml22_get_all_descendants($args['doc'], $args['start']) );
$args['doc']   = run_test('xml22_delete_fragment', $args );

//copy_fragment
$safe          = $args['doc'];
$args['doc']   = xml22_parse('staff.xml');
$args['start'] = xml22_get_by_id( $args['doc'], 'EMP0002' );
$off1          = count( xml22_get_all_descendants($args['doc'], $args['start']) );
$start2        = xml22_get_by_id( $args['doc'], 'EMP0003' );
$off2          = count( xml22_get_all_descendants($args['doc'], $start2) );
$args['off']   = $off1 + $off2 + 1;
$args['frag']  = run_test('xml22_copy_fragment', $args, TRUE, 'copy from staff.xml' );
$args['doc']   = $safe;
unset( $safe );

//insert_fragment
$args['anc']   = xml22_get_last_child( $args['doc'], xml22_get_root( $args['doc'] ) );
$args['doc']   = run_test('xml22_insert_fragment', $args );

//move_fragment
$args['start'] = xml22_get_by_id( $args['doc'], 'EMP0006' );
$args['anc']   = xml22_get_root( $args['doc'] );
$args['off']   = count( xml22_get_all_descendants($args['doc'], $args['start']) );
$args['doc']   = run_test('xml22_move_fragment', $args );

//------------------
print_teststats();

$errors = xml22_error();
if ( count( $errors ) ) {
  print "\n***Report errors:\n";
  foreach ( $errors as $line ) {
	print "$line\n";
  }
}

exit();

?>