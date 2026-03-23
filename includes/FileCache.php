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
     * @param string $cacheDir
     * @param int    $expires
     *
     * @throws \Exception
     */
    public function __construct($cacheDir, $expires = 604800)
    {
        $this->mCacheDir = $cacheDir;
        $this->mExpires = $expires;
        if (!is_dir($cacheDir)) {
            if (!@mkdir($cacheDir, 0777, true)) {
                throw new \Exception('Could not create cache directory : '.$cacheDir);
            }
        } elseif (!is_writable($cacheDir)) {
            throw new \Exception('Could not found writable cache directory');
        }
    }

    /**
     * save data.
     *
     * @param string $type
     * @param string $fname
     * @param string $data
     *
     * @throws \Exception
     */
    public function save($type, $fname, $data)
    {
        [$dPath, $fPath] = $this->getPath($type, $fname);
        if (!is_dir($dPath)) {
            if (!@mkdir($dPath, 0777, true)) {
                throw new \Exception('Could not create cache sub directory : '.$dPath);
            }
        }
        if (false === @file_put_contents($fPath, $data)) {
            throw new \Exception('Could not write cache file : '.$fPath);
        }
    }

    /**
     * load data.
     *
     * @param string $type
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
        [$dPath, $fPath] = $this->getPath($type, $fname);
        $data = @file_get_contents($fPath);
        if (false === $data) {
            throw new \Exception('Could not read cache file : '.$fPath);
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
        [$dPath, $fPath] = $this->getPath($type, $fname);
        if (false === file_exists($fPath)) {
            return false;
        }
        if ($this->mExpires > 0) {
            if (false === ($st = @stat($fPath))) {
                throw new \Exception('Could not get cache file status : '.$fPath);
            }
            $now = time();
            if ($st['mtime'] + $this->mExpires < $now) {
                if (false === @unlink($fPath)) {
                    throw new \Exception('Could not remove cache file : '.$fPath);
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
        $dPath = sprintf('%s/%s/%s', $this->mCacheDir, $type, substr($fname, 0, 2));
        $fPath = sprintf('%s/%s', $dPath, $fname);

        return [$dPath, $fPath];
    }
}
