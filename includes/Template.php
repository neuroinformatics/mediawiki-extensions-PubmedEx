<?php

namespace MediaWiki\Extension\PubmedEx;

class Template
{
    /**
     * template file.
     *
     * @var string
     */
    protected $mTemplateFile;

    /**
     * constructor.
     *
     * @param string $fname
     *
     * @throws \Exception
     */
    public function __construct($fname)
    {
        $dpath = dirname(__DIR__).'/templates';
        $fpath = $dpath.'/'.$fname;
        if (!file_exists($fpath)) {
            throw new \Exception('Could not find template file : '.$fname);
        }
        $this->mTemplateFile = $fpath;
    }

    /**
     * render.
     *
     * @param array $article
     *
     * @return string
     */
    public function render($article)
    {
        $Article = ArticleFactory::create($article);
        ob_start();
        include $this->mTemplateFile;
        $html = ob_get_contents();
        ob_end_clean();

        return $html;
    }
}
