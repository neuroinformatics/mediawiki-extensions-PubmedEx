<?php

function get_pubmed($article)
{
	$ret = array();
	$names = "";


       // Name string building
        // ["MedlineCitation"]["Article"]["AuthorList"]["Author"]["0"]["LastName"]
        // ["MedlineCitation"]["Article"]["AuthorList"]["Author"]["0"]["ForeName"]
        // ["MedlineCitation"]["Article"]["AuthorList"]["Author"]["0"]["FirstName"]
        // ["PubmedArticleSet"]["0"]["PubmedArticle"]["0"]["MedlineCitation"]["0"]["Article"]["0"]["AuthorList"]["0"]["Author"]["1"]["LastName"]["0"]=Loukianiouk

        if(isset($article["MedlineCitation"]["0"]["Article"]["0"]["AuthorList"]["0"]["Author"])){
                $authorlist = $article["MedlineCitation"]["0"]["Article"]["0"]["AuthorList"]["0"]["Author"];

		// CollectiveName 18391952 / 21909115

		if( isset($authorlist[0]["CollectiveName"]["0"]) ) {
                        $names = sprintf("%s",$authorlist[0]["CollectiveName"]["0"]);
                 } else {
			$firstname=( isset($authorlist["0"]["ForeName"]["0"])?$authorlist["0"]["ForeName"]["0"]:(isset($authorlist["0"]["FirstName"]["0"])?$authorlist["0"]["FirstName"]["0"]:""));
			$names = sprintf("%s %s",$firstname, (isset($authorlist["0"]["LastName"]["0"]))?$authorlist["0"]["LastName"]["0"]:"");
		}
		for($i=1; $i<count($authorlist); $i++){
			if( isset($authorlist[$i]["CollectiveName"]["0"]) ) {
				$names .= sprintf(", %s",$authorlist[$i]["CollectiveName"]["0"]);
			} else {
				$firstname=( isset($authorlist[$i]["ForeName"]["0"])?$authorlist[$i]["ForeName"]["0"]:(isset($authorlist[$i]["FirstName"]["0"])?$authorlist[$i]["FirstName"]["0"]:""));
				$names .= sprintf(", %s %s",$firstname, (isset($authorlist[$i]["LastName"]["0"]))?$authorlist[$i]["LastName"]["0"]:"");
			}
		}
	}

# ["Authors"]
	$ret["Authors"]= $names;

# ["Title"]
	// Title
	// ["PubmedArticleSet"]["0"]["PubmedArticle"]["1"]["MedlineCitation"]["0"]["Article"]["0"]["ArticleTitle"]["0"]
	$ret["Title"] = "";
	#print_r($article);
	if(isset($article["MedlineCitation"]["0"]["Article"]["0"]["ArticleTitle"]["0"]) && !empty($article["MedlineCitation"]["0"]["Article"]["0"]["ArticleTitle"]["0"])){
		if( is_array( $article["MedlineCitation"]["0"]["Article"]["0"]["ArticleTitle"]["0"] )){
			$ret["Title"] = implode("",$article["MedlineCitation"]["0"]["Article"]["0"]["ArticleTitle"]["0"]); 
			if( is_array( $article["MedlineCitation"]["0"]["Article"]["0"]["ArticleTitle"]["0"]["i"] )){	
				$ret["Title"] = implode("",$article["MedlineCitation"]["0"]["Article"]["0"]["ArticleTitle"]["0"]["i"]);
			}	
		
		} else {
			$ret["Title"] = $article["MedlineCitation"]["0"]["Article"]["0"]["ArticleTitle"]["0"];
		}
	}	
	if(isset($article["MedlineCitation"]["0"]["Article"]["0"]["VernacularTitle"]["0"]) && !empty($article["MedlineCitation"]["0"]["Article"]["0"]["VernacularTitle"]["0"])){
		$ret["Title"] .= sprintf(" [%s] ",$article["MedlineCitation"]["0"]["Article"]["0"]["VernacularTitle"]["0"]);
	}	
	if(isset($article["MedlineCitation"]["0"]["Article"]["0"]["TransliteratedTitle"]["0"]) && !empty($article["MedlineCitation"]["0"]["Article"]["0"]["TransliteratedTitle"]["0"])){
		$ret["Title"] .= sprintf(" [%s]", $article["MedlineCitation"]["0"]["Article"]["0"]["TransliteratedTitle"]["0"]);
	}
	#$ret["Title"] = str_replace(array("<i>","</i>"),"",$ret["Title"]);
	
# ["Journal"]
	// ["PubmedArticleSet"]["0"]["PubmedArticle"]["1"]["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["ISOAbbreviation"]["0"]
	$ret["Journal"] ="";
	if(isset($article["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["ISOAbbreviation"]["0"]) && !empty($article["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["ISOAbbreviation"]["0"])){
		$ret["Journal"] = $article["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["ISOAbbreviation"]["0"];
	} else {
		if(isset($article["MedlineCitation"]["0"]["MedlineJournalInfo"]["0"]["MedlineTA"]["0"])){
			$ret["Journal"] = $article["MedlineCitation"]["0"]["MedlineJournalInfo"]["0"]["MedlineTA"]["0"];
		}
	}

# ["Issn"]
	// ["PubmedArticleSet"]["0"]["PubmedArticle"]["1"]["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["ISSN"]["0"]["content"]
	$ret["Issn"] = "";
	if(isset($article["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["ISSN"]["0"]["content"]) && !empty($article["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["ISSN"]["0"]["content"])){
		$ret["Issn"] = $article["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["ISSN"]["0"]["content"];
	}
	$ret["Essn"] = "";
        if(isset($article["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["ESSN"]["0"]["content"]) && !empty($article["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["ESSN"]["0"]["content"])){
                $ret["Essn"] = $article["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["ESSN"]["0"]["content"];
        }
# ["Year"]
    // Year
    // ["PubmedArticleSet"]["0"]["PubmedArticle"]["0"]["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["JournalIssue"]["0"]["PubDate"]["0"]["Year"]["0"]

    // Year
    // ["PubmedArticleSet"]["0"]["PubmedArticle"]["0"]["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["JournalIssue"]["0"]["PubDate"]["0"]["Year"]["0"]
    $ret["Year"] = "";
    if(isset($article["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["JournalIssue"]["0"]["PubDate"]["0"]["Year"]["0"]) &&
        !empty($article["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["JournalIssue"]["0"]["PubDate"]["0"]["Year"]["0"]))
    {
        $ret["Year"] = $article["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["JournalIssue"]["0"]["PubDate"]["0"]["Year"]["0"];
    } else {
        // Added by Spencer Bliven
        if(isset($article["PubmedData"]["0"]["History"]["0"]["PubMedPubDate"]["0"]["Year"]["0"]) &&
            !empty($article["PubmedData"]["0"]["History"]["0"]["PubMedPubDate"]["0"]["Year"]["0"]))
        {
            $ret["Year"] = $article["PubmedData"]["0"]["History"]["0"]["PubMedPubDate"]["0"]["Year"]["0"];
        } else {
            // ["PubmedArticleSet"]["0"]["PubmedArticle"]["1"]["MedlineCitation"]["0"]["Article"]["0"]["ArticleDate"]["0"]["Year"]["0"]
            if(isset($article["0"]["Article"]["0"]["ArticleDate"]["0"]["Year"]["0"])){
                $ret["Year"] = $article["0"]["Article"]["0"]["ArticleDate"]["0"]["Year"]["0"];
            }

        }
    }


# ["Volume"]
# ["Issue"]
	// Vol
	// ["MedlineCitation"]["Article"]["Journal"]["JournalIssue"]["Volume"]=2
	// ["MedlineCitation"]["Article"]["Journal"]["JournalIssue"]["Issue"]=3
	// ["PubmedArticleSet"]["0"]["PubmedArticle"]["1"]["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["JournalIssue"]["0"]["Volume"]["0"]
	
	$ret["Volume"]  = "";	
	if( isset($article["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["JournalIssue"]["0"]["Volume"]["0"]) && !empty($article["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["JournalIssue"]["0"]["Volume"]["0"])){
		$ret["Volume"]  = $article["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["JournalIssue"]["0"]["Volume"]["0"];
	}

	// ["PubmedArticleSet"]["0"]["PubmedArticle"]["1"]["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["JournalIssue"]["0"]["Issue"]["0"]
	$ret["Issue"]="";
	if( isset($article["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["JournalIssue"]["0"]["Issue"]["0"]) && !empty($article["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["JournalIssue"]["0"]["Issue"]["0"])){
		$ret["Issue"] = $article["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["JournalIssue"]["0"]["Issue"]["0"];
	}

# ["Pages"]
	//pages
	// ["MedlineCitation"]["Article"]["Pagination"]["MedlinePgn"] 						
	// ["PubmedArticleSet"]["0"]["PubmedArticle"]["0"]["MedlineCitation"]["0"]["Article"]["0"]["Pagination"]["0"]["MedlinePgn"]["0"]
	$ret["Pages"] = "";
	if(isset($article["MedlineCitation"]["0"]["Article"]["0"]["Pagination"]["0"]["MedlinePgn"]["0"]) && 
	   !empty($article["MedlineCitation"]["0"]["Article"]["0"]["Pagination"]["0"]["MedlinePgn"]["0"]) ){
		$ret["Pages"] = $article["MedlineCitation"]["0"]["Article"]["0"]["Pagination"]["0"]["MedlinePgn"]["0"];
	}

# ["PublicationStatus"]
	//["PubmedData"]["PublicationStatus"]=ppublish
	//["PubmedData"]["PublicationStatus"]=aheadofprint
	// ["PubmedArticleSet"]["0"]["PubmedArticle"]["0"]["PubmedData"]["0"]["PublicationStatus"]["0"]
	$ret["PublicationStatus"] = "";
	if(isset($article["PubmedData"]["0"]["PublicationStatus"]["0"])){
		$ret["PublicationStatus"] = $article["PubmedData"]["0"]["PublicationStatus"]["0"]; 
	}

# ["CitedMedium"]
	// ["MedlineCitation"]["Article"]["Journal"]["JournalIssue"]["CitedMedium"] Print Internet
	// ["PubmedArticleSet"]["0"]["PubmedArticle"]["0"]["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["JournalIssue"]["0"]["CitedMedium"]
	 $ret["CitedMedium"] = "";
	if(isset($article["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["JournalIssue"]["0"]["CitedMedium"]) && 
	   !empty($article["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["JournalIssue"]["0"]["CitedMedium"])){
		$ret["CitedMedium"] = $article["MedlineCitation"]["0"]["Article"]["0"]["Journal"]["0"]["JournalIssue"]["0"]["CitedMedium"];
	}

# ["DOI"] 
# ["PMID"]
	// ["PubmedData"]["ArticleIdList"]["ArticleId"]["0"]["_"]=10.1002/cncr.23754
	// ["PubmedData"]["ArticleIdList"]["ArticleId"]["0"]["IdType"]=doi
	// ["PubmedData"]["ArticleIdList"]["ArticleId"]["1"]["_"]=18567018
	// ["PubmedData"]["ArticleIdList"]["ArticleId"]["1"]["IdType"]=pubmed
	// ["PubmedArticleSet"]["0"]["PubmedArticle"]["0"]["PubmedData"]["0"]["ArticleIdList"]["0"]["ArticleId"]["0"]["IdType"]=pii
	// ["PubmedArticleSet"]["0"]["PubmedArticle"]["0"]["PubmedData"]["0"]["ArticleIdList"]["0"]["ArticleId"]["0"]["content"]=34/suppl_1/D115


	$article_idtype = $article["PubmedData"]["0"]["ArticleIdList"]["0"]["ArticleId"];
	$ret["Doi"]=""; $ret["Pmid"]="";
	foreach( $article_idtype as $id_ind => $id_val){
		if( isset($id_val["IdType"])){
			if( $id_val["IdType"] == "doi"){
				$ret["Doi"] = $id_val["content"];
			}
			if( $id_val["IdType"] == "pubmed"){        
                                $ret["Pmid"] = $id_val["content"];    
                        }		 	
	
		}
	}

	return $ret;
}


$layoutfile = 0;

function gen_layout_pubmed($article,$argv)
{
	$ret = "";
	GLOBAL $layoutfile,$wgPubmedLayoutFile,$wgPubmedPath,$wgPubmedLayoutLinks;

	if( isset($argv['layoutfile']) && !empty($argv['layoutfile'])){ 
		$PubmedLayoutFile = $argv['layoutfile'];
	} else {
		if(isset($wgPubmedLayoutFile) && !empty($wgPubmedLayoutFile)){
			$PubmedLayoutFile = $wgPubmedLayoutFile;
		} else {
			$PubmedLayoutFile = "layout_ext.def";
		}
	}

        if( isset($argv['layoutlinks']) && !empty($argv['layoutlinks'])){
	 	$PubmedLayoutLinks = $argv['layoutlinks'];
	} else {
		if(isset($wgPubmedLayoutLinks) && !empty($wgPubmedLayoutLinks)){
			$PubmedLayoutLinks = $wgPubmedLayoutLinks;
		} else {
			$PubmedLayoutLinks = "PMID,WORLDCAT,EZB,DOI";
		}
	}

	if( !isset($wgPubmedPath) || empty($wgPubmedPath)){
		$wgPubmedPath = "extensions/Pubmed";
	}

	$data = get_pubmed($article);


/*
# ["Authors"]
# ["Title"]
# ["Journal"]
# ["Issn"]
# ["Year"]      
# ["Volume"]
# ["Issue"]
# ["Pages"]
# ["PublicationStatus"]
# ["CitedMedium"]
# ["DOI"] 
# ["PMID"]
*/


	$data["Special"] = "(";
	if(isset($data["CitedMedium"])){
		if($data["CitedMedium"] == "Print"){
			$data["Special"].= sprintf("P");
		}
		if($data["CitedMedium"] == "Internet"){
			$data["Special"] .= sprintf("I");
		}
	}
	if(isset($data["PublicationStatus"])){
		$data["Special"] .= " ";
		if($data["PublicationStatus"] == "ppublish"){
			$data["Special"] .= sprintf("p");
		}
		if($data["PublicationStatus"] == "aheadofprint"){
			$data["Special"] .= sprintf("a");
		}
		if($data["PublicationStatus"] == "epublish"){
			$data["Special"] .= sprintf("e");
		}
	}
	$data["Special"] .= ")";
			

		if( (file_exists("${wgPubmedPath}/layouts/${PubmedLayoutFile}") || file_exists("layouts/${PubmedLayoutFile}") )  ){
			if( file_exists("${wgPubmedPath}/layouts/${PubmedLayoutFile}")) $new_layout = implode("",file("${wgPubmedPath}/layouts/${PubmedLayoutFile}"));
			if( file_exists("layouts/${PubmedLayoutFile}")) $new_layout = implode("",file("layouts/${PubmedLayoutFile}"));
		} else {
			$new_layout = "<br> ##Authors##  <br> ##Title## <br> ##Journal##: ##Year##, ##Volume##(##Issue##);##Pages## <p>";
		}
	

	$tags = array("Authors","Title","Journal","Issn","Year","Volume","Issue","Pages","Special","Pmid","Doi");

	foreach($tags as $tag){
		if(!is_array($data[$tag]))
			$new_layout = str_replace("##${tag}##",isset($data[$tag])?$data[$tag]:"",$new_layout);	
	}

	$tags_file=array();
			if(strstr($PubmedLayoutLinks,",")){
				$tags_file = explode(",",$PubmedLayoutLinks);
				foreach($tags_file as $tags_file_ind => $tags_file_val){
					$tags_file [$tags_file_ind] = trim($tags_file_val);
				}
			} else {
				$PubmedLayoutLinks=trim($PubmedLayoutLinks);
				$tags_file = array($PubmedLayoutLinks);
			}

	if( !empty($tags_file)){
	foreach($tags_file as $val_file){
		$local_def = array("${PubmedLayoutFile}.link.${val_file}","${wgPubmedPath}/layouts/{$PubmedLayoutFile}.link.${val_file}","layouts/{$PubmedLayoutFile}.link.${val_file}");
		$layfi = "";
		foreach($local_def as $val){
                        if(file_exists($val)){
				$layfi = implode("",file($val));
			}
		}
		$ok=0;
		foreach($tags as $tag){
			if(stristr($layfi,"##${tag}##")){
				if(isset($data[$tag]) &&  !empty($data[$tag])) {
					$ok=1;
					$layfi = str_replace("##${tag}##",isset($data[$tag])?$data[$tag]:"",$layfi);
				}
			}
		}
		$new_layout = str_replace("##${val_file}##",($ok==1)?$layfi:" ",$new_layout);
	}}
	$new_layout= preg_replace(array('/##*##/','/,\ \(\)/'),"",$new_layout);			

	$new_layout=str_replace("()","",$new_layout);

	return $new_layout;
}

?>
