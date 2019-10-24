<?php

namespace MediaWiki\Extension\PubmedEx;

class PubmedBookArticle extends AbstractArticle
{
    /**
     * BookDocument.
     *
     * @var array
     */
    protected $mBookDocument;

    /**
     * Book.
     *
     * @var array
     */
    protected $mBook;

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
        // PubmedBookArticle > BookDocument
        $this->mBookDocument = $this->mData['PubmedBookArticle']['BookDocument']; // required
        // PubmedBookArticle > BookDocument > Book
        $this->mBook = $this->mData['PubmedBookArticle']['BookDocument']['Book']; // required
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
        return 'PubmedBookArticle';
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
        // PubmedBookArticle > BookDocument > AuthorList* > Author+
        // <!ATTLIST AuthorList
        //             CompleteYN (Y | N) "Y"
        //             Type ( authors | editors )  #IMPLIED >
        $AuthorList = $this->findNode($this->mBookDocument, ['AuthorList']);
        if (null !== $AuthorList) {
            foreach ($AuthorList as $_AuthorList) {
                $Type = $this->attribute($_AuthorList, 'Type');
                if ('authors' === $Type) {
                    $Author = $_AuthorList['Author']; // required
                    return $this->renderAuthors($Author);
                }
            }
        }

        return '';
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
        // PubmedBookArticle > BookDocument > ArticleTitle?
        $ArticleTitle = $this->findNode($this->mBookDocument, ['ArticleTitle']);

        return $this->text($ArticleTitle);
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
        // PubmedBookArticle > BookDocument > Book > BeginningDate? > Year
        $BeginningDateYear = $this->findNode($this->mBook, ['BeginningDate', 'Year']);
        if (null !== $BeginningDateYear) {
            // PubmedBookArticle > BookDocument > Book > BeginningDate? > Year
            $EndingDateYear = $this->findNode($this->mBook, ['EndingDate', 'Year']);

            return $this->text($BeginningDateYear).(null !== $EndingDateYear ? '-'.$this->text($EndingDateYear) : '');
        }
        // PubmedBookArticle > BookDocument > Book > PubDate
        // <!ELEMENT PubDate ((Year, ((Month, Day?) | Season)?) | MedlineDate) >
        $Year = $this->findNode($this->mBook, ['PubDate', 'Year']);
        if (null === $Year) {
            // PubmedBookArticle > PubmedBookData? > History? > PubMedPubDate+
            // <!ELEMENT PubMedPubDate (Year, Month, Day, (Hour, (Minute, Second?)?)?)>
            // <!ATTLIST PubMedPubDate
            //             PubStatus (received | accepted | epublish |
            //               ppublish | revised | aheadofprint |
            //               retracted | ecollection | pmc | pmcr | pubmed | pubmedr |
            //               premedline | medline | medliner | entrez | pmc-release) #REQUIRED >
            $PubMedPubDate = $this->findNode($this->mPubmedArticle, ['PubmedData', 'History', 'PubMedPubDate']);
            if (null !== $PubMedPubDate) {
                foreach ($PubMedPubDate as $_PubMedPubDate) {
                    $PubStatus = $this->attribute($_PubMedPubDate, 'PubStatus');
                    if (in_array($PubStatus, ['epublish', 'ppublish', 'pubmed'])) {
                        $Year = $_PubMedPubDate['Year']; // required
                        break;
                    }
                }
            }
        }

        return $this->text($Year);
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
        // PubmedBookArticle > BookDocument > Book > Volume?
        $Volume = $this->findNode($this->mBook, ['Volume']);

        return $this->text($Volume);
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
        // PubmedBookArticle > BookDocument > Pagination?
        // <!ELEMENT Pagination ((StartPage, EndPage?, MedlinePgn?) | MedlinePgn) >
        $Pagination = $this->findNode($this->mBookDocument, ['Pagination']);
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
        // PubmedBookArticle > PubmedBookData? > PublicationStatus
        $PublicationStatus = $this->findNode($this->mData, ['PubmedBookArticle', 'PubmedBookData', 'PublicationStatus']);

        return $this->text($PublicationStatus);
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
        // alias
        return $this->getMedium();
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
        // PubmedBookArticle > PubmedBookData? > ArticleIdList > ArticleId+
        // <!ATTLIST ArticleId
        //             IdType (doi | pii | pmcpid | pmpid | pmc | mid |
        //               sici | pubmed | medline | pmcid | pmcbook | bookaccession) "pubmed" >
        $ArticleId = $this->findNode($this->mPubmedArticle, ['PubmedBookData', 'ArticleIdList', 'ArticleId']);
        if (null !== $ArticleId) {
            foreach ($ArticleId as $_ArticleId) {
                $IdType = $this->attribute($_ArticleId, 'IdType');
                if ('doi' === $IdType) {
                    return $this->text($_ArticleId);
                }
            }
        }
        // PubmedBookArticle > BookDocument > Book > ELocationID*
        // <!ATTLIST ELocationID
        //             EIdType (doi | pii) #REQUIRED
        //             ValidYN  (Y | N) "Y">
        $ELocationID = $this->findNode($this->mBook, ['ELocationID']);
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
        // PubmedBookArticle > BookDocument > PMID
        $PMID = $this->mBookDocument['PMID']; // required

        return $this->text($PMID);
    }

    /**
     * get pubmed central id.
     *
     * @see AbstractArticle::getPmcid()
     *
     * @return string
     */
    protected function getPmc()
    {
        // PubmedBookArticle > PubmedBookData? > ArticleIdList > ArticleId+
        // <!ATTLIST ArticleId
        //             IdType (doi | pii | pmcpid | pmpid | pmc | mid |
        //               sici | pubmed | medline | pmcid | pmcbook | bookaccession) "pubmed" >
        $ArticleId = $this->findNode($this->mPubmedArticle, ['PubmedBookData', 'ArticleIdList', 'ArticleId']);
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
     * get book title.
     *
     * @return string
     */
    protected function getBook()
    {
        // PubmedBookArticle > BookDocument > Book > BookTitle
        $BookTitle = $this->mBook['BookTitle']; // required

        return $this->text($BookTitle);
    }

    /**
     * get editors.
     *
     * @return string
     */
    protected function getEditors()
    {
        // PubmedBookArticle > BookDocument > Book > AuthorList* > Author+
        // <!ATTLIST AuthorList
        //             CompleteYN (Y | N) "Y"
        //             Type ( authors | editors )  #IMPLIED >
        $AuthorList = $this->findNode($this->mBook, ['AuthorList']);
        if (null !== $AuthorList) {
            foreach ($AuthorList as $_AuthorList) {
                $Type = $this->attribute($_AuthorList, 'Type');
                if ('editors' === $Type) {
                    $Author = $_AuthorList['Author']; // required
                    return $this->renderAuthors($Author);
                }
            }
        }

        return '';
    }

    /**
     * get number of editors.
     *
     * @return int
     */
    protected function getNumOfEditors()
    {
        // PubmedBookArticle > BookDocument > Book > AuthorList* > Author+
        // <!ATTLIST AuthorList
        //             CompleteYN (Y | N) "Y"
        //             Type ( authors | editors )  #IMPLIED >
        $AuthorList = $this->findNode($this->mBook, ['AuthorList']);
        if (null !== $AuthorList) {
            foreach ($AuthorList as $_AuthorList) {
                $Type = $this->attribute($_AuthorList, 'Type');
                if ('editors' === $Type) {
                    $Author = $_AuthorList['Author']; // required
                    return count($Author);
                }
            }
        }

        return 0;
    }

    /**
     * get edition.
     *
     * @return string
     */
    protected function getEdition()
    {
        // PubmedBookArticle > BookDocument > Book > Edition?
        $Edition = $this->findNode($this->mBook, ['Edition']);

        return $this->text($Edition);
    }

    /**
     * get publisher name.
     *
     * @return string
     */
    protected function getPublisherName()
    {
        // PubmedBookArticle > BookDocument > Book > Publisher > PublisherName
        $PublisherName = $this->mBook['Publisher']['PublisherName']; // required

        return $this->text($PublisherName);
    }

    /**
     * get publisher location.
     *
     * @return string
     */
    protected function getPublisherLocation()
    {
        // PubmedBookArticle > BookDocument > Book > Publisher > PublisherLocation?
        $PublisherLocation = $this->findNode($this->mBook, ['Publisher', 'PublisherLocation']);

        return $this->text($PublisherLocation);
    }

    /**
     * get cited medium.
     *
     * @return string
     */
    protected function getMedium()
    {
        // PubmedBookArticle > BookDocument > Book > Medium?
        $Medium = $this->findNode($this->mBook, ['Medium']);

        return $this->text($Medium);
    }
}
