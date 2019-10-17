<?php
/*
 * Entrez Class 
 *
 * Andreas Bohne-Lang (2011)
 *
 * andreas.bohne-lang ate medma.uni-heidelberg.de
 */

GLOBAL $wgPubmedPath;

ini_set('include_path', $wgPubmedPath . '/entrez.cl/' . PATH_SEPARATOR . get_include_path());
ini_set('include_path', $wgPubmedPath . '/entrez.cl/xml22-0_4_0/xml22' . PATH_SEPARATOR . get_include_path());

require_once("xml.class.php");
require_once("cache.class.php");

class entrez_eutils_fcgi
{
	var $version = "2";

    	var $_serverpath_esearch ='http://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi?retmode=xml';
    	var $_serverpath_efetch  ='http://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi?retmode=xml';

	var $_debug = 0;
	var $_last_call  = 0;

	var $ProxyHost="";
	var $ProxyPort="";
	var $ProxyUser="";
	var $ProxyPass="";

	var $_cache = "";
	var $logfile="/tmp/entrez.log";

	// Klassenkonstruktor
	function entrez_eutils_fcgi () {@unlink($this->logfile); }

	function set_debug($debug=true) { $this->_debug=$debug; $this->log("-------------------------\n");}

	function log($data){
		if ($this->_debug){
		$fp=@fopen($this->logfile,"a");
		if($fp){
			$x="";
			if(is_array($data) || is_object($data) ){
				fprintf($fp,"%s\n",$this->print_rr($x,$data));
			} else {
				fprintf($fp,"%s\n",$data);
			}
			fclose($fp);
		}}
	}
			
			
	
	function setProxy($Host="",$Port="",$User="",$Pass="")
	{
		if( substr($Host,0,7)=="http://")  $Host=substr($Host,7); 
		if( substr($Host,0,8)=="https://") $Host=substr($Host,8); 
		$this->ProxyHost=$Host;
		$this->ProxyPort=$Port;
		$this->ProxyUser=$User;
		$this->ProxyPass=$Pass;
		$this->log(sprintf("Setproxy: (%s)(%s)(%s)(%s)\n", $this->ProxyHost,$this->ProxyPort,$this->ProxyUser,$this->ProxyPass));
	}

	
	function _three_sec_wait()
	{
		while(time() - $this->_last_call <=3) sleep(1);
		$this->_last_call = time();
	}


	// Die etwas andere print_r-Funktion 
	function print_rr(&$ret,$a,$b='')
	{	 
		if( is_object($a) ) $a = get_object_vars ($a);
		if( is_array($a)) {
			if( !empty($a) ){
				foreach($a as $ind => $val){
					if(  is_object($val) )
						$this->print_rr($ret,$val,$b.sprintf('->%s',$ind));
					else
						$this->print_rr($ret,$val,$b.sprintf('["%s"]',$ind));
				}
			}
		} else {
			$ret .= sprintf("%s=%s\n",$b,$a);
		}
		return $ret;
	}

        function load_web_page($url)
        {
                if( isset($this->ProxyHost) && !empty($this->ProxyHost) ){
                        $proxy_str="tcp://";
                        if( isset( $this->ProxyUser) && !empty($this->ProxyUser))  {
				 $header =   'Proxy-Authorization: Basic '.base64_encode(sprintf("%s:%s",$this->ProxyUser,$this->ProxyPass));
                        } else $header = "";
                        $proxy_str .= sprintf("%s",$this->ProxyHost);
                        if( isset( $this->ProxyPort) && !empty($this->ProxyPort)) {
                                $proxy_str .= sprintf(":%s",$this->ProxyPort);
                        }
                        $this->log(sprintf(" Proxy: %s\n", $proxy_str ));
                        $opts = array("http" => array("proxy" => $proxy_str, "header"=> $header,  "request_fulluri" => true, "user_agent"=>"PHP"));
                } else {
                        $opts = array("http" => array("request_fulluri" => false,  "user_agent"=>"PHP" ));
                }
                $this->log($opts);
                $this->log($url);
                $context = stream_context_create($opts);
                return file_get_contents($url,false,$context);
        }

	

	// Methode zum Aufruf der SOAP-Funktion
        function esearch($params) 
        {
		$this->log("in esearch\n");
		$this->log($params);

		$url=sprintf("%s&db=%s&retstart=%s&retmax=%s&term=%s",$this->_serverpath_esearch,$params["db"],$params["retstart"],$params["retmax"],urlencode($params["term"]));
		$this->log(sprintf("esearch url=%s\n",$url));
		$page_xml=$this->load_web_page($url);
		$this->log(sprintf("esearch PAge=%s\n",$page_xml));

		$xml=new xmltool();
		$doc=$xml->xml2rec($page_xml);
		$this->log($doc["xml"][0]);	

		$this->log("exit esearch\n");
		return array("eSearchResult"=> $doc["xml"][0]);	
	}	

        function efetch($params)
        {
		$this->_three_sec_wait();
		$this->log($params);

		if(empty($params["ids"])){ $this->log("No Id sumbitted"); return array();}

                $url=sprintf("%s&db=%s&id=%s",$this->_serverpath_efetch,$params["db"],$params["ids"]);
                $this->log(sprintf("efetch url=%s\n",$url));
                $page_xml=$this->load_web_page($url);
                $this->log(sprintf("efetch Page=%s\n",$page_xml));

                $xml=new xmltool();
                $doc=$xml->xml2rec($page_xml);
                $this->log($doc["xml"]); 
                
                return array("PubmedArticleSet"=> $doc["xml"]);    
        }


	
	function search1($db, $term, $off=0, $limit=500)
	{
		// max 500 data sets

		$ret_search = $this->esearch(array( "db" => $db, "retstart" => $off, "retmax" => $limit, "term" => $term));
		$this->log($ret_search);
		$ids = (count($ret_search["eSearchResult"]["IdList"][0]["Id"])>1)? join(",",$ret_search["eSearchResult"]["IdList"][0]["Id"]):$ret_search["eSearchResult"]["IdList"][0]["Id"][0];
		$this->log("$ids\n");
		$ret_fetch = $this->efetch(array( "db" => $db, "ids" => $ids));
		$this->log($ret_fetch);
		return $ret_fetch;
	}

	function search2($db, $term, $off=0, $limit=500)
	{
		// max 500 data sets
		$this->log("in search2($db, $term, $off, $limit)\n");
		$ret_search = $this->esearch(array( "db" => $db, "retstart" => $off, "retmax" => $limit, "term" => $term));
		if( !isset($ret_search["eSearchResult"]["ERROR"])) {
			$this->log($ret_search);
			$ids = $ret_search["eSearchResult"]["IdList"][0]["Id"];
			$ret_fetch = $this->load_articles_by_id_list($db,$ids);
			$this->log($ret_fetch);
		} else {
			$this->log("Empty result from esearch\n");
			$ret_fetch=array();
		}
		$this->log("exit search2\n");
		return $ret_fetch;
	}



	function load_articles_by_id_list($db,$ids)
        {
		$this->log("in load_articles_by_id_list($db,".print_r($ids,true).")\n");
		if( empty($this->_cache)){
			$this->log("Cacheobjekt leer\n");
			$ids = (count($ids)>1)? join(",",$ids):$ids[0];
			$ret_fetch = $this->efetch(array( "db" => $db, "ids" => $ids));
		} else {
			$this->log("Cacheobjekt anlegen\n");
			$cache = new Cache();
			$cache -> cachedir = $this->_cache;
		/*
                // max 500 data sets
		if( count($ids) > 500) {
			echo "<font color=red><b> Warning: mor than 500 entries </b></font>";
		}
		*/

		$liste = array("lokal"=>array(), "server"=>array());
		foreach($ids as $val){
			$filename=sprintf("%s_%s",$this->version,$val);	
			if( $cache -> check( $filename ) ){
				$liste["lokal"][] = $val;
			} else {
				$liste["server"][] = $val;
			}
		}

		$ret_fetch=array();
		
		if(isset($liste["server"][0])){
                	$ids = (count($liste["server"])>1)? join(",",$liste["server"]):$liste["server"][0];
			$this->log("Lade net $ids\n");
                	$ret_fetch = $this->efetch( array( "db" => $db, "ids" => $ids));
			#["PubmedArticleSet"]["0"]["PubmedArticle"]["0"]["MedlineCitation"]["0"]["PMID"]["0"]["content"]
			foreach($ret_fetch["PubmedArticleSet"]["0"]["PubmedArticle"] as $ind => $val){
				$this->log($val);
				$sPMID=$val["MedlineCitation"]["0"]["PMID"]["0"]["content"];
				$filename=sprintf("%s_%s",$this->version,$sPMID);
				$this->log("Cache save $filename\n");
				$cache -> save($filename,$val);
			}
		}
		if(isset($liste["lokal"][0])){
			$xi=0;
			foreach($liste["lokal"] as $val){
				$this->log("Lade cache $val\n");
				$filename=sprintf("%s_%s",$this->version,$val);
				$this->log("Cache load $filename\n");
				$ret_fetch["PubmedArticleSet"]["0"]["PubmedArticle"]["$xi"] = $cache -> load($filename);
				$xi++;
			}
		}

		}

		#$this->print_rr($liste);
		#$this->print_rr($ret_fetch);
			
                $this->log($ret_fetch);
		$this->log("exit load_articles_by_id_list\n");
                return $ret_fetch;
        }



} // Ende der Klasse


// Klasse entrez_dbs
class entrez_dbs extends entrez_eutils_fcgi
{
	function searchPubmed($term)
	{
		return $this->search1("pubmed",$term);
	}

	function searchPubMedCentral($term)
        {
                return $this->search1("pmc",$term);
        }
}


?>
