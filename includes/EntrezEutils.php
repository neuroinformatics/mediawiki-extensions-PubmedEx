<?php

namespace MediaWiki\Extension\PubmedEx;

class EntrezEutils
{
    public const ESEARCH_URL = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/esearch.fcgi';
    public const EFETCH_URL = 'https://eutils.ncbi.nlm.nih.gov/entrez/eutils/efetch.fcgi';

    /**
     * api key.
     *
     * @var string
     */
    protected $mApiKey = '';

    /**
     * waiting time between each query.
     *
     * @var int
     */
    protected $mWait = 400000;

    /**
     * proxy host.
     *
     * @var string
     */
    protected $mProxyHost = '';

    /**
     * proxy port.
     *
     * @var int
     */
    protected $mProxyPort = 3128;

    /**
     * proxy user.
     *
     * @var string
     */
    protected $mProxyUser = '';

    /**
     * proxy password.
     *
     * @var string
     */
    protected $mProxyPass = '';

    /**
     * constructor.
     *
     * @param string $apiKey
     */
    public function __construct($apiKey)
    {
        $this->mApiKey = $apiKey;
        if ('' !== $this->mApiKey) {
            // if api key privided, a site can post up to 10 requests per second
            $this->mWait = 100000;
        }
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
        $this->mProxyHost = $host;
        $this->mProxyPort = $port;
        $this->mProxyUser = $user;
        $this->mProxyPass = $pass;
    }

    /**
     * execute esearch.
     *
     * @param string $db
     * @param string $term
     *
     * @return string
     *
     * @throws \Exception
     */
    public function esearch($db, $term, $limit, $start)
    {
        $url = self::ESEARCH_URL.'?db='.$db.'&term='.urlencode($term);

        return $this->query($url, $limit, $start);
    }

    /**
     * execute efetch.
     *
     * @param string   $db
     * @param string[] $ids
     *
     * @return string
     *
     * @throws \Exception
     */
    public function efetch($db, $ids, $limit, $start)
    {
        $url = self::EFETCH_URL.'?db='.$db.'&id='.implode(',', $ids);

        return $this->query($url, $limit, $start);
    }

    /**
     * query.
     *
     * @param string $url
     *
     * @return string
     *
     * @throws \Exception
     */
    protected function query($url, $limit, $start)
    {
        $url .= '&retmax='.$limit;
        $url .= '&retstart='.$start;
        if ('' !== $this->mApiKey) {
            $url .= '&api_key='.$this->mApiKey;
        }
        $url .= '&retmode=xml';
        usleep($this->mWait);
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, false);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_AUTOREFERER, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FAILONERROR, true);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($curl, CURLOPT_TIMEOUT, 30);
        if ('' !== $this->mProxyHost) {
            curl_setopt($curl, CURLOPT_PROXY, $this->mProxyHost);
            curl_setopt($curl, CURLOPT_PROXYPORT, $this->mProxyPort);
            if ('' !== $this->mProxyUser && '' !== $this->mProxyPass) {
                curl_setopt($curl, CURLOPT_PROXYUSERPWD, $this->mProxyUser.':'.$this->mProxyPass);
            }
        }
        $error = '';
        $code = 0;
        $ret = curl_exec($curl);
        if (false === $ret) {
            $error = curl_error($curl);
        } else {
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if (200 !== $code) {
                $error = 'Unexpected HTTP code '.$code.' returned';
            }
        }
        curl_close($curl);
        if ('' !== $error) {
            throw new \Exception($error);
        }

        return $ret;
    }
}
