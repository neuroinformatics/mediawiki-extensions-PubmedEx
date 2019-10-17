<?php

define("MEDIAWIKI",TRUE); 
define("MW_INSTALL_PATH","/data/www/data/htdocs/wiki/");
define("SHELLTEST",TRUE);

ini_set('include_path', '/server/www/apps/local/wiki/edv/extensions/Pubmed/');
printf(">> %s\n\n",get_include_path());


# Extension PubMed
$wgPubmedPath="/server/www/apps/local/wiki/edv/extensions/Pubmed";

$wgPubmedLimit=40;
$wgPubmedProxyHost="proxy.medma.uni-heidelberg.de";
$wgPubmedProxyPort="8080";
$wgPubmedProxyUser="";
$wgPubmedProxyPass="";
$wgPubmedCache="/tmp/pubmedcache2";
$wgPubmedLayoutFile="layout_ext.def";
$wgPubmedLayoutLinks="PMID,WORLDCAT,EZB,DOI";
$wgPubmedDEBUG=1;
$wgPubmedOffset=0;

include("Pubmed.php");

/*
$input="15980568";
echo Pubmed( $input, $argv );

$input="11675023";
echo Pubmed( $input, $argv );

$input="18567071";
echo Pubmed( $input, $argv );

$input="15784146";
echo Pubmed( $input, $argv );

*/

$input="cancer";
echo Pubmed( $input, $argv );


?>
