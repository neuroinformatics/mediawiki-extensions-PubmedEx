<?php

namespace MediaWiki\Extension\PubmedEx;

abstract class AbstractArticle
{
    /**
     * display name types.
     */
    const NAME_TYPE_LI = 0; // LastName, Initials
    const NAME_TYPE_LF = 1; // LastNAme, ForeName
    const NAME_TYPE_FL = 2; // ForeName LastName

    /**
     * article.
     *
     * @var array
     */
    protected $mData;

    /**
     * constructor.
     *
     * @param array $article
     */
    public function __construct($article)
    {
        $this->mData = $article;
    }

    /**
     * getter.
     *
     * @param string $name
     *
     * @return string
     */
    final public function __get($name)
    {
        $method = 'get'.$name;
        if (method_exists($this, $method)) {
            return $this->$method();
        }

        return '';
    }

    /**
     * get article type.
     *
     * @return string
     */
    abstract protected function getArticleType();

    /**
     * get authors.
     *
     * @return string
     */
    abstract protected function getAuthors();

    /**
     * get authors.
     *
     * @return string
     */
    abstract protected function getTitle();

    /**
     * get publication year.
     *
     * @return string
     */
    abstract protected function getYear();

    /**
     * get volume.
     *
     * @return string
     */
    abstract protected function getVolume();

    /**
     * get pages.
     *
     * @return string
     */
    abstract protected function getPages();

    /**
     * get publication status.
     *
     * @return string
     */
    abstract protected function getPublicationStatus();

    /**
     * get cited medium.
     *
     * @return string
     */
    abstract protected function getCitedMedium();

    /**
     * get doi.
     *
     * @return string
     */
    abstract protected function getDoi();

    /**
     * get pubmed id.
     *
     * @return string
     */
    abstract protected function getPmid();

    /**
     * get pubmed central id.
     *
     * @return string
     */
    abstract protected function getPmc();

    /**
     * get special.
     *
     * @return string
     */
    protected function getSpecial()
    {
        $ret = [];
        $CiteMedium = $this->getCitedMedium();
        switch ($CiteMedium) {
            case 'Print':
                $ret[] = 'P';
                break;
            case 'Internet':
                $ret[] = 'I';
                break;
        }
        $PublicationStatus = $this->getPublicationStatus();
        switch ($PublicationStatus) {
            case 'ppublish':
                $ret[] = 'p';
                break;
            case 'aheadofprint':
                $ret[] = 'a';
                break;
            case 'epublish':
                $ret[] = 'e';
                break;
         }

        return !empty($ret) ? '('.implode(' ', $ret).')' : '';
    }

    /**
     * render authors.
     *
     * @param array  $Author
     * @param int    $type
     * @param string $lastsep
     * @param int    $shownum
     *
     * @return string
     */
    protected function renderAuthors($Author, $type = self::NAME_TYPE_LI, $lastsep = ', &amp;', $shownum = 7)
    {
        $ret = [];
        if (null !== $Author) {
            foreach ($Author as $_Author) {
                if (isset($_Author['CollectiveName'])) {
                    $ret[] = $this->text($_Author['CollectiveName']);
                } else {
                    $lastName = $this->text($_Author['LastName']);
                    $foreName = $this->text($_Author['ForeName']);
                    $initials = $this->text($_Author['Initials']);
                    $initialsWithComma = '';
                    if (isset($_Author['Initials'])) {
                        for ($i = 0; $i < strlen($initials); ++$i) {
                            $initialsWithComma .= substr($initials, $i, 1).'.';
                        }
                    }
                    switch ($type) {
                        case self::NAME_TYPE_LI:
                            $ret[] = $lastName.('' !== $initialsWithComma ? ', '.$initialsWithComma : '');
                            break;
                        case self::NAME_TYPE_LF:
                            $ret[] = $lastName.('' !== $foreName ? ', '.$foreName : '').'.';
                            break;
                        case self::NAME_TYPE_FL:
                            $ret[] = ('' !== $foreName ? $foreName.' ' : '').$lastName;
                            break;
                    }
                }
            }
        }
        if (empty($ret)) {
            return '';
        }
        if (0 === $shownum) {
            $shownum = count($ret);
        }
        $last = array_pop($ret);
        $dash = '';
        if (($shownum - 1) < count($ret)) {
            $ret = array_slice($ret, 0, ($shownum - 1));
            $dash .= ', ...';
        }

        return !empty($ret) ? implode(', ', $ret).$dash.$lastsep.' '.$last : $last;
    }

    /**
     * find child node by paths.
     *
     * @param array $node
     * @param array $paths
     *
     * @return mixed
     */
    final protected function findNode($node, $paths)
    {
        $ret = $node;
        foreach ($paths as $path) {
            if (!isset($ret[$path])) {
                return null;
            }
            $ret = $ret[$path];
        }

        return $ret;
    }

    /**
     * get attribute data.
     *
     * @param array  $node
     * @param string $name
     *
     * @return string
     */
    final protected function attribute($node, $name)
    {
        if (null === $node || !isset($node['@attributes'])) {
            return '';
        }

        return isset($node['@attributes'][$name]) ? $node['@attributes'][$name] : '';
    }

    /**
     * get text data.
     *
     * @param array $node
     *
     * @return string
     */
    final protected function text($node)
    {
        if (null === $node) {
            return '';
        }

        return isset($node['@text']) ? $node['@text'] : (string) $node;
    }
}
