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
        $dPath = dirname(__DIR__).'/templates';
        $fPath = $dPath.'/'.$fname;
        if (!file_exists($fPath)) {
            throw new \Exception('Could not find template file : '.$fname);
        }
        $this->mTemplateFile = $fPath;
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
