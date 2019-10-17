<?php
define("MEDIAWIKI",TRUE);
define("SHELLTEST",TRUE);
define("MW_INSTALL_PATH","/data/www/data/htdocs/wiki/");

ini_set('include_path', '/server/www/apps/local/wiki/edv/extensions/Pubmed/entrez.cl/');
printf(">> %s\n\n",get_include_path());

# Extension PubMed
$wgPubmedPath="/server/www/apps/local/wiki/edv/extensions/Pubmed";

$wgPubmedLimit=10;
$wgPubmedProxyHost="http://proxy.medma.uni-heidelberg.de";
$wgPubmedProxyPort="8080";
$wgPubmedProxyUser="ab3";
$wgPubmedProxyPass="xyz";
#$wgPubmedCache="/tmp/pubmedcache2";
$wgPubmedLayoutFile="layout_ext.def";
$wgPubmedLayoutLinks="PMID,WORLDCAT,EZB,DOI";
$wgPubmedDEBUG=1;
$wgPubmedOffset=0;


require_once("entrez_eutil.class.php");

$entrez=new entrez_eutils_fcgi();

$entrez->set_debug(1);

if( !empty($wgPubmedProxyHost)){
	$entrez->setProxy($wgPubmedProxyHost,$wgPubmedProxyPort,$wgPubmedProxyUser,$wgPubmedProxyPass);
}

#$entrez->esearch(array("db"=>"pubmed","term"=>"Bohne-lang"));

#$entrez->search1("Pubmed","16381827,16239495");

if(isset($wgPubmedCache)) $entrez->_cache = $wgPubmedCache;
$entrez->search2("Pubmed","16381827");

?>
