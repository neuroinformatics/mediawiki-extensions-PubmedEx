<?php

namespace MediaWiki\Extension\PubmedEx;

class ArticleFactory
{
    /**
     * create instance.
     *
     * @param array $article
     *
     * @return PubmedArticle|PubmedBookArticle
     */
    public static function create($article)
    {
        $instance = null;
        if (isset($article['PubmedArticle'])) {
            $instance = new PubmedArticle($article);
        } elseif (isset($article['PubmedBookArticle'])) {
            $instance = new PubmedBookArticle($article);
        }

        return $instance;
    }
}
