<?php

class xmltool {

	var $encoding_from="";
	var $encoding_to="";

	function set_encoding_from($encoding){ $this-> encoding_from = $encoding; }
	function set_encoding_to ($encoding){ $this-> encoding_to = $encoding; }
	
	function xmltool ()
	{

		//error_reporting(~E_ALL);

		#print_r($_SERVER); die;
		//phpinfo(); die;
/*
		if(@isset($_SERVER["PWD"])){   // bei aufruf via script stehen nicht die normalen env-vars zu verfuegung
			$xpath=$_SERVER["PWD"]; // $HOME von openlit
			$path = $xpath;
		} 
		if(@isset($_SERVER["PATH_TRANSLATED"])){ // anders als beim webaufruf.	
			$xpath=$_SERVER["PATH_TRANSLATED"]; 
			$path2 = preg_replace('|^(.+/)(.+)$|', '\1', $xpath );
		}
		#ini_set("include_path", "$path;\\xml22-0_4_0\\xml22;$path.\\xml22-0_4_0\\xml22" ); // Windoof
		ini_set("include_path", "$path/$path2/xml22-0_4_0/xml22" );
*/
		require_once("xml22.inc");

		$options = array( 'XML22_OPT_EXTERNALS' => TRUE );
		xml22_setup($options);
	}	



	// fly recursivly through the xml doc
	/*

   [14] => Array
        (
            [tag] => CirculationLetterEntry
            [index] => 14
            [level] => 2
            [parindex] => 4
            [attributes] => Array
                (
                    [letterNumber] => 23
                    [letterType] => 1
                )

            [children] => Array
                (
                    [0] => 15
                    [1] => 17
                    [2] => 23
                    [3] => 29
                )


	   [5] => Array
	        (
	            [tag] => Item
	            [index] => 5
	            [level] => 2
	            [parindex] => 3
	            [attributes] => Array
	                (
	                    [Name] => PubDate
	                    [Type] => Date
	                )

	            [content] => 2001 Jun 12
		ODER
  		   [children] => Array
        	        (
                        [0] => 4
                        [1] => 5
                    	[2] => 6
			)

	        )
	*/
	function _sub_xml2rec(&$doc,$no)
	{
		$ret=array();

		# Wenn es Attribute gibt -> diese auff√ºhren		
		if(isset($doc[$no]["attributes"])){
			foreach($doc[$no]["attributes"] as $attribute_ind => $attribute_val){
				$ret[$attribute_ind]=$attribute_val;
			}
		}

		# Ebene dar√ºber
		if(isset($doc[$no]["children"])){
			foreach($doc[$no]["children"] as $children_index => $children_val){
				$index=$doc[$children_val]["tag"];
				$neues_element=$this->_sub_xml2rec($doc,$children_val);

				# Wenn es mehr als ein Element gibt, brauch ich die Daten in einem Array
				if(!isset($ret[$index])){ 		// Element gibt es noch nicht 
					$ret[$index][]=$neues_element;	// ok dann wird es gesetzt
				}else{ 					// wenn doch schon vorhanden
				
					if(!isset($ret[$index][0])){	// schon ein Array?	
						$ret[$index]=array($ret[$index]); // nein, dann umwandeln
					}
					if(!is_array($ret[$index])) $ret[$index]=array($ret[$index]);
					$ret[$index][]=$neues_element;		// und neues hinzu	
				}
			}
		}

		# Inhalt
		if(isset($doc[$no]["content"])){
			if(count($ret)==0){			// evt. sind schon Attributeintraege vorhanden
				$ret = $doc[$no]["content"];	
			} else {
				$ret["content"] = $doc[$no]["content"];
			}
		}

		return $ret;	
	}

	function xml2rec($efile)
        {
                $ret=array();
		$mark=array();
		$doc=array();

		$efile=trim($efile);
		#print_r($efile);
                $doc=xml22_parse($efile, FALSE, $this->encoding_from, $this->encoding_to );
                #print_r($doc);
		#die("EX");
		if(@empty($doc)) return $ret;
	
		foreach($doc as $ind => $val){
			if($val["level"]==0){	
				$ret["xml"][$ind]=$this->_sub_xml2rec($doc,$ind);
			}
		}
	
                return $ret;
        }

}


/*
       function _lade_xml_daten($url)
        {
                # <?xml version="1.0" encoding="iso-8859-1

                $efile=implode("", file($url));

                $tmp = str_replace("\""," ",substr($efile,0,80));
                $tmp=strstr($tmp,"encoding=");
                if( !empty($tmp)){
                        list($v1,$v2)=sscanf($tmp,"%s %s");
                        #printf("Charsert:$v2\n");
                        $this->xmltool->set_encoding_from($v2);
                        # hier noch rumbasteln
                        
#                        if( strtolower($v2) != strtolower($this->targetcharset)){
#                                printf("Conv %s %s \n",$v2,$this->targetcharset);
#
                    #        $efile=iconv( $v2,$this->targetcharset."//IGNORE.",$efile);
                        #}
                        
                }
                $doc=$this->xmltool->xml2rec($efile);
                #printf("URL:%s\n",$url);
                #print_r($doc);
                return $doc;
        }

*/
?>
