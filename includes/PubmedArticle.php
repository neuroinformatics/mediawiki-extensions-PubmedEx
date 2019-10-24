<?php

namespace MediaWiki\Extension\PubmedEx;

class PubmedArticle extends AbstractArticle
{
    /**
     * Article.
     *
     * @var array
     */
    protected $mArticle;

    /**
     * Journal.
     *
     * @var array
     */
    protected $mJournal;

    /**
     * constructor.
     *
     * @see AbstractArticle::__construct()
     *
     * @param array $article
     */
    public function __construct($article)
    {
        parent::__construct($article);
        // PubmedArticle > MedlineCitation > Article
        $this->mArticle = $this->mData['PubmedArticle']['MedlineCitation']['Article']; // required
        // PubmedArticle > MedlineCitation > Article > Journal
        $this->mJournal = $this->mData['PubmedArticle']['MedlineCitation']['Article']['Journal']; // required
    }

    /**
     * get article type.
     *
     * @see AbstractArticle::getArticleType()
     *
     * @return string
     */
    protected function getArticleType()
    {
        return 'PubmedArticle';
    }

    /**
     * get authors.
     *
     * @see AbstractArticle::getAuthors()
     *
     * @return string
     */
    protected function getAuthors()
    {
        $ret = [];
        // PubmedArticle > MedlineCitation > Article > AuthorList? > Author+
        // <!ELEMENT Author (((LastName, ForeName?, Initials?, Suffix?) | CollectiveName), Identifier*, AffiliationInfo*) >
        $Author = $this->findNode($this->mArticle, ['AuthorList', 'Author']);

        return $this->renderAuthors($Author);
    }

    /**
     * get title.
     *
     * @see AbstractArticle::getTitle()
     *
     * @return string
     */
    protected function getTitle()
    {
        // PubmedArticle > MedlineCitation > Article > ArticleTitle
        $ArticleTitle = $this->mArticle['ArticleTitle']; // required
        // PubmedArticle > MedlineCitation > Article > VernacularTitle?
        $VernacularTitle = $this->findNode($this->mArticle, ['VernacularTitle']);

        return $this->text($ArticleTitle).(null !== $VernacularTitle ? ' '.$this->text($VernacularTitle) : '');
    }

    /**
     * get publication year.
     *
     * @see AbstractArticle::getYear()
     *
     * @return string
     */
    protected function getYear()
    {
        // PubmedArticle > MedlineCitation > Article > Journal > JournalIssue > PubDate
        // <!ELEMENT PubDate ((Year, ((Month, Day?) | Season)?) | MedlineDate) >
        $ret = $this->findNode($this->mJournal, ['JournalIssue', 'PubDate', 'Year']);
        if (null === $ret) {
            // PubmedArticle > PubmedData? > History? > PubMedPubDate+
            // <!ELEMENT PubMedPubDate (Year, Month, Day, (Hour, (Minute, Second?)?)?)>
            // <!ATTLIST PubMedPubDate
            //             PubStatus (received | accepted | epublish |
            //               ppublish | revised | aheadofprint |
            //               retracted | ecollection | pmc | pmcr | pubmed | pubmedr |
            //               premedline | medline | medliner | entrez | pmc-release) #REQUIRED >
            $PubMedPubDate = $this->findNode($this->mData, ['PubmedArticle', 'PubmedData', 'History', 'PubMedPubDate']);
            if (null !== $PubMedPubDate) {
                foreach ($PubMedPubDate as $_PubMedPubDate) {
                    $PubStatus = $this->attribute($_PubMedPubDate, 'PubStatus');
                    if (in_array($PubStatus, ['epublish', 'ppublish', 'pubmed'])) {
                        $ret = $_PubMedPubDate['Year']; // required
                        break;
                    }
                }
            }
            if (null === $ret) {
                // PubmedArticle > MedlineCitation > Article > ArticleDate* > Year
                $ArticleDate = $this->findNode($this->mArticle, ['ArticleDate']);
                foreach ($ArticleDate as $_ArticleDate) {
                    $ret = $_ArticleDate['Year']; // required
                    break;
                }
            }
        }

        return $this->text($ret);
    }

    /**
     * get volume.
     *
     * @see AbstractArticle::getVolume()
     *
     * @return string
     */
    protected function getVolume()
    {
        // PubmedArticle > MedlineCitation > Article > Journal > JournalIssue > Volume?
        $ret = $this->findNode($this->mJournal, ['JournalIssue', 'Volume']);

        return $this->text($ret);
    }

    /**
     * get pages.
     *
     * @see AbstractArticle::getPages()
     *
     * @return string
     */
    protected function getPages()
    {
        // PubmedArticle > MedlineCitation > Article
        // <!ELEMENT Article (Journal,ArticleTitle,((Pagination, ELocationID*) | ELocationID+),
        //           Abstract?,AuthorList?, Language+, DataBankList?, GrantList?,
        //           PublicationTypeList, VernacularTitle?, ArticleDate*) >
        // <!ELEMENT Pagination ((StartPage, EndPage?, MedlinePgn?) | MedlinePgn) >
        $Pagination = $this->findNode($this->mArticle, ['Pagination']);
        if (null !== $Pagination) {
            $MedlinePgn = $this->findNode($Pagination, ['MedlinePgn']);
            if (null !== $MedlinePgn) {
                return $this->text($MedlinePgn);
            } else {
                $StartPage = $this->findNode($Pagination, ['StartPage']);
                if (null !== $StartPage) {
                    $EndPage = $this->findNode($Pagination, ['EndPage']);

                    return $this->text($StartPage).(null !== $EndPage ? '-'.$this->text($EndPage) : '');
                }
            }
        }

        return '';
    }

    /**
     * get publication status.
     *
     * @see AbstractArticle::getPublicationStatus()
     *
     * @return string
     */
    protected function getPublicationStatus()
    {
        // PubmedArticle > PubmedData? > PublicationStatus
        $ret = $this->findNode($this->mData, ['PubmedArticle', 'PubmedData', 'PublicationStatus']);

        return $this->text($ret);
    }

    /**
     * get cited medium.
     *
     * @see AbstractArticle::getCitedMedium()
     *
     * @return string
     */
    protected function getCitedMedium()
    {
        // PubmedArticle > MedlineCitation > Article > Journal > JournalIssue
        // <!ATTLIST JournalIssue
        //             CitedMedium (Internet | Print) #REQUIRED >
        return $this->attribute($this->mJournal['JournalIssue'], 'CitedMedium'); // required
    }

    /**
     * get doi.
     *
     * @see AbstractArticle::getDoi()
     *
     * @return string
     */
    protected function getDoi()
    {
        // PubmedArticle > PubmedData? > ArticleIdList > ArticleId+
        // <!ATTLIST ArticleId
        //             IdType (doi | pii | pmcpid | pmpid | pmc | mid |
        //               sici | pubmed | medline | pmcid | pmcbook | bookaccession) "pubmed" >
        $ArticleId = $this->findNode($this->mData, ['PubmedArticle', 'PubmedData', 'ArticleIdList', 'ArticleId']);
        if (null !== $ArticleId) {
            foreach ($ArticleId as $_ArticleId) {
                $IdType = $this->attribute($_ArticleId, 'IdType');
                if ('doi' === $IdType) {
                    return $this->text($_ArticleId);
                }
            }
        }
        // PubmedArticle > MedlineCitation > Article
        // <!ELEMENT Article (Journal,ArticleTitle,((Pagination, ELocationID*) | ELocationID+),
        //             Abstract?,AuthorList?, Language+, DataBankList?, GrantList?,
        //             PublicationTypeList, VernacularTitle?, ArticleDate*) >
        // <!ATTLIST ELocationID
        //             EIdType (doi | pii) #REQUIRED
        //             ValidYN  (Y | N) "Y">
        $ELocationID = $this->findNode($this->mArticle, ['ELocationID']);
        if (null !== $ELocationID) {
            foreach ($ELocationID as $_ELocationID) {
                $EIdType = $this->attribute($_ELocationID, 'EIdType');
                if ('doi' === $EIdType) {
                    return $this->text($_ELocationID);
                }
            }
        }

        return '';
    }

    /**
     * get pubmed id.
     *
     * @see AbstractArticle::getPmid()
     *
     * @return string
     */
    protected function getPmid()
    {
        // PubmedArticle > MedlineCitation > PMID
        $ret = $this->mData['PubmedArticle']['MedlineCitation']['PMID']; // required

        return $this->text($ret);
    }

    /**
     * get pubmed central id.
     *
     * @see AbstractArticle::getPmc()
     *
     * @return string
     */
    protected function getPmc()
    {
        // PubmedArticle > PubmedData? > ArticleIdList > ArticleId+
        // <!ATTLIST ArticleId
        //             IdType (doi | pii | pmcpid | pmpid | pmc | mid |
        //               sici | pubmed | medline | pmcid | pmcbook | bookaccession) "pubmed" >
        $ArticleId = $this->findNode($this->mData, ['PubmedArticle', 'PubmedData', 'ArticleIdList', 'ArticleId']);
        if (null !== $ArticleId) {
            foreach ($ArticleId as $_ArticleId) {
                $IdType = $this->attribute($_ArticleId, 'IdType');
                if ('pmc' === $IdType) {
                    return $this->text($_ArticleId);
                }
            }
        }

        return '';
    }

    /**
     * get journal.
     *
     * @return string
     */
    protected function getJournal()
    {
        // PubmedArticle > MedlineCitation > Article > Journal > Title?
        $ret = $this->findNode($this->mJournal, ['Title']);
        if (null === $ret) {
            // PubmedArticle > MedlineCitation > Article > Journal > ISOAbbreviation?
            $ret = $this->findNode($this->mJournal, ['ISOAbbreviation']);
            if (null === $ret) {
                // PubmedArticle > MedlineCitation > MedlineJournalInfo > MedlineTA
                $ret = $this->mData['PubmedArticle']['MedlineCitation']['MedlineJournalInfo']['MedlineTA']; // required
            }
        }

        return $this->text($ret);
    }

    /**
     * get issn.
     *
     * @return string
     */
    protected function getIssn()
    {
        // PubmedArticle > MedlineCitation > Article > Journal > ISSN?
        $ret = $this->findNode($this->mJournal, ['ISSN']);

        return $this->text($ret);
    }

    /**
     * get issue.
     *
     * @return string
     */
    protected function getIssue()
    {
        // PubmedArticle > MedlineCitation > Article > Journal > JournalIssue > Issue?
        $ret = $this->findNode($this->mJournal, ['JournalIssue', 'Issue']);

        return $this->text($ret);
    }
}
