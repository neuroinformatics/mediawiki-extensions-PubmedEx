<?php

namespace MediaWiki\Extension\PubmedEx;

class FileCache
{
    /**
     * cache directory.
     *
     * @var string
     */
    protected $mCacheDir;

    /**
     * cache expires.
     *
     * @var int
     */
    protected $mExpires;

    /**
     * constructor.
     *
     * @param string $cachedir
     * @param int    $expires
     *
     * @throws \Exception
     */
    public function __construct($cachedir, $expires = 604800)
    {
        $this->mCacheDir = $cachedir;
        $this->mExpires = $expires;
        if (!is_dir($cachedir)) {
            if (!@mkdir($cachedir, 0777, true)) {
                throw new \Exception('Could not create cache directory : '.$cachedir);
            }
        } elseif (!is_writable($cachedir)) {
            throw new \Exception('Could not found writable cache directory');
        }
    }

    /**
     * save data.
     *
     * @param string $fname
     * @param string $data
     *
     * @throws \Exception
     */
    public function save($type, $fname, $data)
    {
        [$dpath, $fpath] = $this->getPath($type, $fname);
        if (!is_dir($dpath)) {
            if (!@mkdir($dpath, 0777, true)) {
                throw new \Exception('Could not create cache sub directory : '.$dpath);
            }
        }
        if (false === @file_put_contents($fpath, $data)) {
            throw new \Exception('Could not write cache file : '.$fpath);
        }
    }

    /**
     * load data.
     *
     * @param string $fname
     *
     * @return string|bool
     *
     * @throws \Exception
     */
    public function load($type, $fname)
    {
        if (!$this->check($type, $fname)) {
            return false;
        }
        [$dpath, $fpath] = $this->getPath($type, $fname);
        $data = @file_get_contents($fpath);
        if (false === $data) {
            throw new \Exception('Could not read cache file : '.$fpath);
        }

        return $data;
    }

    /**
     * check whether cache exists.
     *
     * @param string $type
     * @param string $fname
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function check($type, $fname)
    {
        [$dpath, $fpath] = $this->getPath($type, $fname);
        if (false === file_exists($fpath)) {
            return false;
        }
        if ($this->mExpires > 0) {
            if (false === ($st = @stat($fpath))) {
                throw new \Exception('Could not get cache file status : '.$fpath);
            }
            $now = time();
            if ($st['mtime'] + $this->mExpires < $now) {
                if (false === @unlink($fpath)) {
                    throw new \Exception('Could not remove cache file : '.$fpath);
                }

                return false;
            }
        }

        return true;
    }

    /**
     * get cache file path.
     *
     * @param string $type
     * @param string $fname
     *
     * @return [string, string]
     */
    protected function getPath($type, $fname)
    {
        $dpath = sprintf('%s/%s/%s', $this->mCacheDir, $type, substr($fname, 0, 2));
        $fpath = sprintf('%s/%s', $dpath, $fname);

        return [$dpath, $fpath];
    }
}
