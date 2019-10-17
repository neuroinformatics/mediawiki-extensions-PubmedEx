<?php
// MediaWiki Entrez  Extension Ver 1.0 (http://www.mediawiki.org/wiki/Extension:PubMed)

/**#@+
 * A parser extension that adds tag, <pubmed> for accessing data from PubMed 
 *
 * @addtogroup Extensions
 *
 * @link http://meta.wikimedia.org/wiki/Pubmed Documentation
 * @link http://www.pubmed.org
 * @link http://www.phpmagazin.de/itr/online_artikel/psecom,id,965,nodeid,62,_language,de.html
 *
 * @author Andreas Bohne-Lang <bohne-lang@medma.uni-heidelberg.de> 
 * @copyright Copyright © 2008, Andreas Bohne-Lang
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

//  -------------------------------------------------

// load connection class and layout routine
require_once("entrez.cl/entrez_eutil.class.php");
include("layout.inc.php");
 
// set up MediaWiki to react to the "<pubmed>" tag
$wgExtensionFunctions[] = "wfPubmed";

$wgExtensionCredits['parserhook'][] = array(
        'name' => 'PubMed / Entrez',
        'version' => 2.0,
        'description' => 'Imports data from PubMed (www.pubmed.org) by &lt;pubmed&gt; ... &lt;/pubmed&gt;',
        'author' => 'Andreas Bohne-Lang',
        'url' => 'http://www.mediawiki.org/wiki/Extension:Pubmed'
);
 
function wfPubmed() {
        global $wgParser;
        $wgParser->setHook( "pubmed", "Pubmed" );
}
 
//---------------------



// the function that reacts to "<pubmed>"
 
function Pubmed( $input, $argv ) {

	GLOBAL $wgEmergencyContact,$wgScriptPath,$wgPubmedCache,$wgPubmedPath,$wgPubmedLayoutFile,$wgPubmedLayoutLinks,$wgPubmedLimit;
	GLOBAL $wgPubmedProxyHost,$wgPubmedProxyPort, $wgPubmedProxyUser, $wgPubmedProxyPass,$wgPubmedDEBUG,$wgPubmedOffset,$wgPubmedWDSLVersion;


	$out="";

	if( !isset($wgPubmedPath) || empty($wgPubmedPath)) $wgPubmedPath="/extensions/Pubmed";

	$ncbi = new entrez_eutils_fcgi();

	if( isset($argv['debug']) ){ 
		$ncbi->set_debug(1); 
	} else {
		if( isset($wgPubmedDEBUG) && ($wgPubmedDEBUG==1) ){
			$ncbi->set_debug(1); 
		}
	}

	if( !empty($wgPubmedProxyHost)) $ncbi->setProxy($wgPubmedProxyHost,$wgPubmedProxyPort,$wgPubmedProxyUser, $wgPubmedProxyPass);

	if( !isset($wgPubmedCache) || !file_exists($wgPubmedCache)){ 
		$ncbi -> _cache = "";
	} else {
		$ncbi -> _cache = $wgPubmedCache;
	}

	if( isset($argv['limit']) && !empty($argv['limit'])){ 
		$PubmedLimit = $argv['limit']; 
	} else {
		if(  isset($wgPubmedLimit)  && ! empty($wgPubmedLimit)){
			$PubmedLimit =$wgPubmedLimit;
		} else {
			$PubmedLimit = 100;
		}
	}

	$PubmedOffset=0;
	if( isset($argv['offset']) && !empty($argv['offset'])){
                $PubmedLimit = $argv['offset'];
        } else {
                if(  isset($wgPubmedOffset)  && ! empty($wgPubmedOffset)){
                        $PubmedOffset =$wgPubmedLimit;
                }
        }

	if($PubmedLimit <=0) $PubmedLimit=500;

	$result = $ncbi -> search2('pubmed',$input,$PubmedOffset,$PubmedLimit );

	$article_data = $result["PubmedArticleSet"]["0"]["PubmedArticle"];

  	
	for( $i=0; ($i< $PubmedLimit) && ($i< count($article_data)) ; $i++){
		$out .= gen_layout_pubmed($article_data["$i"],$argv ); 
	}

	// start the rendering the html outupt
     	$output  = "<!-- MediaWiki extension http://www.mediawiki.org/wiki/Extension:Pubmed -->";
     	$output .= $out;
     	$output .= "<!-- End of Pubmed -->";
 
	// send the output to MediaWiki
    	return $output;
}
?>
