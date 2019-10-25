<?php

namespace MediaWiki\Extension\PubmedEx;

/**
 * xml parser for the pubmed response
 * this is based on http://dtd.nlm.nih.gov/ncbi/pubmed/out/pubmed_190101.dtd.
 */
class XmlParser
{
    /**
     * force text elements of efetch result xml.
     */
    const EFETCH_FORCE_TEXT_ELEMENTS = [
        'AbstractText',
        'Affiliation',
        'ArticleTitle',
        'BookTitle',
        'Citation',
        'CoiStatement',
        'CollectionTitle',
        'CollectiveName',
        'Keyword',
        'Param',
        'PublisherName',
        'SectionTitle',
        'Suffix',
        'VernacularTitle',
        'VolumeTitle',
    ];

    /**
     * repeatable elements of efetch result xml.
     */
    const EFETCH_REPEATABLE_ELEMENTS = [
        'PubmedArticleSet' => ['PubmedArticle', 'PubmedBookArticle'],
        'BookDocumentSet' => ['BookDocument'],
        'PubmedBookArticleSet' => ['PubmedBookArticle'],
        'BookDocument' => ['LocationLabel', 'Language', 'AuthorList', 'PublicationType', 'KeywordList', 'ItemList', 'ReferenceList'],
        'DeleteCitation' => ['PMID'],
        'DeleteDocument' => ['PMID'],
        'MedlineCitation' => ['CitationSubset', 'OtherID', 'OtherAbstract', 'KeywordList', 'SpaceFlightMission', 'GeneralNote'],
        'PubmedData' => ['ReferenceList'],
        'Article' => ['ELocationID', 'Language', 'ArticleDate'],
        'Abstract' => ['AbstractText'],
        'AccessionNumberList' => ['AccessionNumber'],
        'AffiliationInfo' => ['Identifier'],
        'ArticleIdList' => ['ArticleId'],
        'AuthorList' => ['Author'],
        'Author' => ['Identifier', 'AffiliationInfo'],
        'Book' => ['AuthorList', 'Isbn', 'ELocationID'],
        'ChemicalList' => ['Chemical'],
        'CommentsCorrectionsList' => ['CommentsCorrections'],
        'DataBankList' => ['DataBank'],
        'GeneSymbolList' => ['GeneSymbol'],
        'GrantList' => ['Grant'],
        'History' => ['PubMedPubDate'],
        'Investigator' => ['Identifier', 'AffiliationInfo'],
        'InvestigatorList' => ['Investigator'],
        'ItemList' => ['Item'],
        'KeywordList' => ['Keyword'],
        'MeshHeading' => ['QualifierName'],
        'MeshHeadingList' => ['MeshHeading'],
        'Object' => ['Param'],
        'ObjectList' => ['Object'],
        'OtherAbstract' => ['AbstractText'],
        'PersonalNameSubjectList' => ['PersonalNameSubject'],
        'PublicationTypeList' => ['PublicationType'],
        'ReferenceList' => ['Reference', 'ReferenceList'],
        'Section' => ['Section'],
        'Sections' => ['Section'],
        'SupplMeshList' => ['SupplMeshName'],
    ];

    /**
     * get Id list from esearch result xml.
     *
     * @param string $xml
     *
     * @return string[]
     */
    public static function eSearchGetIds($xml)
    {
        $ids = [];
        $doc = new \DOMDocument();
        $doc->loadXML($xml);
        $doc->normalizeDocument();
        $listId = $doc->getElementsByTagName('Id');
        foreach ($listId as $elId) {
            $ids[] = trim($elId->nodeValue);
        }

        return $ids;
    }

    /**
     * get article list from efetch result xml.
     *
     * @param string $xml
     *
     * @return array
     */
    public static function eFetchGetArticles($xml)
    {
        $articles = [];
        $doc = new \DOMDocument();
        $doc->loadXML($xml);
        $doc->normalizeDocument();
        // PubmedArticle
        $nlPubmedArticle = $doc->getElementsByTagName('PubmedArticle');
        foreach ($nlPubmedArticle as $elPubmedArticle) {
            $article = self::element2array($elPubmedArticle);
            $pmid = $article['MedlineCitation']['PMID']['@text'];
            $articles[$pmid] = ['PubmedArticle' => $article];
        }
        // PubmedBookArticle
        $nlPubmedBookArticle = $doc->getElementsByTagName('PubmedBookArticle');
        foreach ($nlPubmedBookArticle as $elPubmedBookArticle) {
            $article = self::element2array($elPubmedBookArticle);
            $pmid = $article['BookDocument']['PMID']['@text'];
            $articles[$pmid] = ['PubmedBookArticle' => $article];
        }

        return $articles;
    }

    /**
     * convert element to array.
     *
     * @param \DomElement $element
     *
     * @return array
     */
    protected static function element2array($element)
    {
        $ret = [];
        foreach ($element->attributes as $at) {
            $ret['@attributes'][$at->name] = $at->value;
        }
        if (in_array($element->tagName, self::EFETCH_FORCE_TEXT_ELEMENTS)) {
            $text = self::getInnerXml($element);
            if (isset($ret['@attributes'])) {
                $ret['@text'] = $text;
            } else {
                $ret = $text;
            }
        } else {
            $text = '';
            $children = [];
            foreach ($element->childNodes as $ch) {
                if (XML_TEXT_NODE === $ch->nodeType) {
                    $text .= $ch->wholeText;
                } elseif (XML_ELEMENT_NODE === $ch->nodeType) {
                    $children[$ch->tagName][] = self::element2array($ch);
                }
            }
            $text = trim($text);
            if (!empty($children)) {
                foreach (array_keys($children) as $chTag) {
                    if (isset(self::EFETCH_REPEATABLE_ELEMENTS[$element->tagName]) && in_array($chTag, self::EFETCH_REPEATABLE_ELEMENTS[$element->tagName])) {
                        $ret[$chTag] = $children[$chTag];
                    } else {
                        $ret[$chTag] = $children[$chTag][0];
                    }
                }
                if ('' !== $text) {
                    $ret['@text'] = $text;
                }
            } else {
                if (isset($ret['@attributes'])) {
                    $ret['@text'] = $text;
                } else {
                    $ret = $text;
                }
            }
        }

        return $ret;
    }

    /**
     * get inner xml.
     *
     * @param \DomNode|null $node
     *
     * @return string
     */
    protected static function getInnerXml($node)
    {
        $text = '';
        if (null !== $node) {
            foreach ($node->childNodes as $child) {
                $text .= $child->ownerDocument->saveXML($child);
            }
        }

        return trim($text);
    }
}
