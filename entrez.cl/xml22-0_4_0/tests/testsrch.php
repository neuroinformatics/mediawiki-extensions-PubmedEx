<?php

error_reporting(E_ALL);

$file        = "staff.xml";
$args        = array();
$tests       = array();
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


// mixed run_test( string $function, array $args bool $dump, string $addmsg = '' )
//
// $function - name of function
//
function run_test( $function, $args, $dump = TRUE, $addmsg = '' ) {

  global $tests;
  global $TESTREPEAT;
  global $DUMP;
  global $PROFILE;

  if ( ! $DUMP ) { $dump = FALSE; }

  static $testnum;
  $testnum++;

  $failed = 0;

  headmsg( $function." (".$addmsg.")" );

  $tests[$testnum] = 0;

  $runs = array();
  getmicrotime(INIT);
  gettimes(INIT);

  for ( $i = 0; $i < $TESTREPEAT; $i++ ) {

	switch ( $function ) {
	case 'xml22_parse':
	  switch ( $addmsg ) {
	  case 'without caching':
		$result = $function($args['file'], FALSE );
		break;
	  case 'with caching':
		$result = $function( $args['file'], TRUE );
		break;
	  }
	  break;
	case 'xml22_get_by_regex':
	  if ( ! $i && $DUMP ) {
		print "searching for:\n";
		print_r($args['search']);
	  }
	  switch ( $addmsg ) {
	  case 'without attributes, find all':
		$result = $function($args['doc'], $args['search'], XML22_GET_ALL );
		break;
	  case 'without attributes, start at 6':
		$result = $function($args['doc'], $args['search'], XML22_GET_ALL, 6 );
		break;
	  case 'without attributes, start at 6, offset 7':
		$result = $function($args['doc'], $args['search'], XML22_GET_ALL, 6, 7 );
		break;
	  case 'without attributes, find first':
		$result = $function($args['doc'], $args['search'], 1 );
		break;
	  case 'without attributes, find first 2':
		$result = $function($args['doc'], $args['search'], 2 );
		break 2;
	  case "with attributes":
		$result = $function($args['doc'], $args['search'] );
		break 2;
	  }
	  break;
	case 'xml22_get_all_siblings':
	case 'xml22_get_all_descendants':
	case 'xml22_get_all_ancestors':
	case 'xml22_get_by_id':
	case 'xml22_get_first_child':
	case 'xml22_get_last_child':
	case 'xml22_get_next_sibling':
	case 'xml22_get_prev_sibling':
	case 'xml22_get_parent':
	case 'xml22_get_all_of_name':
	case 'xml22_get_all_of_content':
	  if ( ! $i && $DUMP ) {
		print "searching for:\n";
		print_r($args['search']);
		print "\n";
	  }
	  $result = $function( $args['doc'], $args['search'] );
	  break;
	case 'xml22_get_name':
	case 'xml22_get_content':
	  if ( ! $i && $DUMP ) {
		print "searching for:\n";
		print_r($args['search']);
		print "\n";
	  }
	  $result = $function( $args['search'] );
	  break;
	case 'xml22_get_attribute':
	  if ( ! $i && $DUMP ) {
		print "searching for:\n";
		print_r($args['name']);
		print "\n";
	  }
	  $result = $function( $args['search'], $args['name'] );
	  break;
	case 'xml22_get_root':
	case 'xml22_get_version':
	case 'xml22_get_doctype':
	  $result = $function( $args['doc'] );
	  break;
	case 'xml22_write_document_str':
	  $fd = tmpfile();
	  $result = $function( $args['doc'], $args['mode'] );
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
	print_r( $result );
	flush();
  }

  return $result;

}

//---MAIN---------------------------//

if ( ! file_exists( $file ) ) {
  print "could not find input file $file\n";
  exit();
}

$args['file'] = $file;
$args['doc']  = NULL;

print "running test set, be patient...\n\n";

$options = array( 'XML22_OPT_EXTERNALS' => TRUE );
xml22_setup($options);

//------------------
print "\n***parsing tests:\n";
run_test('xml22_parse', $args, FALSE, 'without caching');

$args['doc']= run_test('xml22_parse', $args, TRUE, 'with caching');
$args['search'] = array( 'tag'     => '/^name$/',
						 'content' => '/^.*[a].*/'
						 );


print "\n***searching tests:\n";

print "\n*** ***tag level search:\n";

// xml22_get_by_regex
run_test('xml22_get_by_regex', $args, TRUE, 'without attributes, find all' );
run_test('xml22_get_by_regex', $args, TRUE, 'without attributes, start at 6' );
run_test('xml22_get_by_regex', $args, TRUE, 'without attributes, start at 6, offset 7' );
run_test('xml22_get_by_regex', $args, TRUE, 'without attributes, find first' );
run_test('xml22_get_by_regex', $args, TRUE, 'without attributes, find first 2' );
$args['search'] = array( 'tag'        => '/^address$/',
						 'attributes' => array( 'domestic' => '/^Yes$/' ) );
run_test('xml22_get_by_regex', $args, TRUE, 'with attributes' );

//xml22_get_all_siblings
$search = array( 'tag'     => '/^name$/',
				 'content' => '/Jones/' );
$args['search'] = xml22_get_by_regex( $args['doc'], $search, XML22_GET_FIRST );
run_test('xml22_get_all_siblings', $args, TRUE );

//xml22_get_all_descendants
$search = array( 'tag'        => '/^employee$/' );
$args['search'] = xml22_get_by_regex( $args['doc'], $search, XML22_GET_FIRST );
run_test('xml22_get_all_descendants', $args, TRUE );

//xml22_get_all_ancestors
$search = array( 'tag'     => '/^name$/',
				 'content' => '/Jones/');
$args['search'] = xml22_get_by_regex( $args['doc'], $search, XML22_GET_FIRST );
run_test('xml22_get_all_ancestors', $args, TRUE );

print "\n*** ***XML level search:\n";

//xml22_get_version
run_test('xml22_get_version', $args, TRUE );

//xml22_get_doctype
run_test('xml22_get_doctype', $args, TRUE );

//xml22_get_root
$root = run_test('xml22_get_root', $args, TRUE );

//xml22_get_first_child
$args['search'] = $root;
$fchild = run_test('xml22_get_first_child', $args, TRUE );

//xml22_get_next_sibling
$args['search'] = $fchild;
$nsibl  = run_test('xml22_get_next_sibling', $args, TRUE );

//xml22_get_last_child
$args['search'] = $root;
$lchild = run_test('xml22_get_last_child', $args, TRUE );

//xml22_get_prev_sibling
$args['search'] = $lchild;
$psibl  = run_test('xml22_get_prev_sibling', $args, TRUE );

//xml22_get_parent
$args['search'] = $psibl;
$par = run_test('xml22_get_parent', $args, TRUE );

//xml22_get_by_id
$args['search'] = "EMP0003";
run_test('xml22_get_by_id', $args, TRUE );

//xml22_get_all_of_name
$args['search'] = "address";
$names = run_test('xml22_get_all_of_name', $args, TRUE );

//
$doctmp = $args['doc'];
//

//xml22_get_all_of_content
$args['doc']    = $names;
$args['search'] = "/Dallas/";
$names = run_test('xml22_get_all_of_content', $args, TRUE );

//xml22_get_name
$args['search'] = array_shift($names);
run_test('xml22_get_name', $args, TRUE);
print "\n";

//xml22_get_content
run_test('xml22_get_content', $args, TRUE);
print "\n";

//xml22_get_attribute
$args['name'] = 'domestic';
run_test('xml22_get_attribute', $args, TRUE);
print "\n";

//
$args['doc'] = $doctmp;
//

print "\n***writing tests:\n";

$args['mode'] = XML22_NORMALIZE;
run_test('xml22_write_document_str', $args, TRUE,
		 'writing document to a string with XML22_NORMALIZE' );
print "\n";
$args['mode'] = XML22_BEAUTIFY;
run_test('xml22_write_document_str', $args, TRUE,
		 'writing document to a string with XML22_BEAUTIFY' );


//------------------
print_teststats();

$errors = xml22_error();
if ( count( $errors ) ) {
  print "\n***Report errors:\n";
  foreach ( $errors as $key => $line ) {
	if ( $key == 'last' ) { $lasterr = $line; };
	print "$line\n";
  }
  print "\nlast error:\n$lasterr\n";
}

exit();

?>
