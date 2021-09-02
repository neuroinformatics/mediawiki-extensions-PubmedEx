<?php

namespace MediaWiki\Extension\PubmedEx;

class Pubmed
{
    public const CACHE_TYPE_ESEARCH = 'esearch';
    public const CACHE_TYPE_EFETCH = 'efetch';
    public const CACHE_TYPE_PMID = 'pmid';

    /**
     * eutil instance.
     *
     * @var EntrezEutils
     */
    protected $mEutils;

    /**
     * cache instance.
     *
     * @var FileCache
     */
    protected $mCache = null;

    /**
     * constructor.
     *
     * @param string $apiKey
     */
    public function __construct($apiKey = '')
    {
        $this->mEutils = new EntrezEutils($apiKey);
    }

    /**
     * set cache.
     *
     * @param string $cache
     * @param int    $expires
     *
     * @throws \Exception
     */
    public function setCache($cache, $expires = 604800)
    {
        $this->mCache = new FileCache($cache, $expires);
    }

    /**
     * set proxy.
     *
     * @param string $host
     * @param int    $port
     * @param string $user
     * @param string $pass
     **/
    public function setProxy($host, $port = 3128, $user = '', $pass = '')
    {
        $this->mEutils->setProxy($host, $port, $user, $pass);
    }

    /**
     * search.
     *
     * @param string $term
     * @param int    $limit
     * @param int    $offset
     *
     * @return array|bool
     *
     * @throws \Exception
     */
    public function search($term, $limit = 100, $offset = 0)
    {
        $pmids = $this->getPubmedIds($term, $limit, $offset);
        $articles = $this->getArticles($pmids);

        return $articles;
    }

    /**
     * search term and get pubmed ids.
     *
     * @param string $term
     * @param int    $limit
     * @param int    $offset
     *
     * @return string[]
     *
     * @throws \Exception
     */
    protected function getPubmedIds($term, $limit, $offset)
    {
        $term = trim($term);
        if (preg_match('/^\d+((\s*,\s*)+\d+)*$/', $term)) {
            return array_map('trim', explode(',', $term));
        }
        $cfname = md5($term.$limit.$offset).'.xml';
        $xml = $this->loadCache(self::CACHE_TYPE_ESEARCH, $cfname);
        $isNew = false;
        if (false === $xml) {
            $xml = $this->mEutils->esearch('pubmed', $term, $limit, $offset);
            if (false === $xml) {
                return [];
            }
            $isNew = true;
        }
        $pmids = XmlParser::eSearchGetIds($xml);
        if ($isNew && !empty($pmids)) {
            $this->saveCache(self::CACHE_TYPE_ESEARCH, $cfname, $xml);
        }

        return $pmids;
    }

    /**
     * get abstracts.
     *
     * @param string[] $pmids
     *
     * @return array
     *
     * @throws \Exception
     */
    protected function getArticles($pmids)
    {
        $ret = [];
        $efetchIds = [];
        foreach ($pmids as $pmid) {
            $cfname = $pmid.'.json';
            $json = $this->loadCache(self::CACHE_TYPE_PMID, $cfname);
            $article = (false !== $json) ? json_decode($json, true) : false;
            if (false === $article) {
                $efetchIds[] = $pmid;
            } else {
                $ret[$pmid] = $article;
            }
        }
        if (!empty($efetchIds)) {
            // if more than about 200 UIDs are to be provided, the request should be made using the HTTP POST method.
            // see:  https://www.ncbi.nlm.nih.gov/books/NBK25499/
            $limit = 100;
            for ($offset = 0; $offset < count($efetchIds); $offset += $limit) {
                $ids = array_slice($efetchIds, $offset, $limit);
                $cfname = md5(implode(',', $ids)).'.xml';
                $xml = $this->loadCache(self::CACHE_TYPE_EFETCH, $cfname);
                if (false === $xml) {
                    $xml = $this->mEutils->efetch('pubmed', $ids, $limit, 0);
                    if (false !== $xml) {
                        $this->saveCache(self::CACHE_TYPE_EFETCH, $cfname, $xml);
                    }
                }
                if (false !== $xml) {
                    $articles = XmlParser::eFetchGetArticles($xml);
                    foreach ($articles as $pmid => $article) {
                        $ret[$pmid] = $article;
                        $cfname = $pmid.'.json';
                        $this->saveCache(self::CACHE_TYPE_PMID, $cfname, json_encode($article));
                    }
                }
            }
        }

        return $ret;
    }

    /**
     * load cache data.
     *
     * @param string $type
     * @param string $fname
     *
     * @return array|bool
     *
     * @throws \Exception
     */
    protected function loadCache($type, $fname)
    {
        return null !== $this->mCache ? $this->mCache->load($type, $fname) : false;
    }

    /**
     * save cache data.
     *
     * @param string $type
     * @param string $fname
     * @param mixed  $value
     *
     * @throws \Exception
     */
    protected function saveCache($type, $fname, $value)
    {
        null !== $this->mCache && $this->mCache->save($type, $fname, $value);
    }
}
