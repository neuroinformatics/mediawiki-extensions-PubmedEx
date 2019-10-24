<?php

namespace MediaWiki\Extension\PubmedEx;

class Hooks
{
    /**
     * register tag renderer callbacks.
     *
     * @param \Parser $parser
     */
    public static function onParserFirstCallInit(\Parser $parser)
    {
        // register <pubmed> tag callback
        $parser->setHook('pubmed', [self::class, 'renderPubmedEx']);
    }

    /**
     * render <pubmed>.
     *
     * @param string   $input
     * @param array    $args
     * @param \Parser  $parser
     * @param \PPFrame $frame
     *
     * @return string
     */
    public static function renderPubmedEx($input, array $args, \Parser $parser, \PPFrame $frame)
    {
        global $wgUploadDirectory;
        global $wgPubmedApiKey, $wgPubmedCache, $wgPubmedCacheExpires, $wgPubmedLimit, $wgPubmedOffset, $wgPubmedTemplateFile;
        global $wgPubmedProxyHost, $wgPubmedProxyPort, $wgPubmedProxyUser, $wgPubmedProxyPass;
        $html = [];
        $term = trim($input);
        $limit = isset($args['limit']) ? (int) $args['limit'] : $wgPubmedLimit;
        $offset = isset($args['offset']) ? (int) $args['offset'] : $wgPubmedOffset;
        $templatefile = isset($args['templatefile']) ? $args['templatefile'] : $wgPubmedTemplateFile;
        $cache = '' !== $wgPubmedCache ? $wgPubmedCache : $wgUploadDirectory.'/pubmed';
        try {
            $pubmed = new Pubmed($wgPubmedApiKey);
            $pubmed->setCache($cache, $wgPubmedCacheExpires);
            if ('' !== $wgPubmedProxyHost) {
                $pubmed->setProxy($wgPubmedProxyHost, $wgPubmedProxyPort, $wgPubmedProxyUser, $wgPubmedProxyPass);
            }
            $articles = $pubmed->search($term, $limit, $offset);
            if (!empty($articles)) {
                $template = new Template($templatefile);
                foreach ($articles as $article) {
                    $html[] = $template->render($article);
                }
            } else {
                $html[] = '<span style="color:red;font-weight:bold;">Resource not found in PubMed.</span>';
            }
        } catch (\Exception $e) {
            $html[] = '<span style="color:red;font-weight:bold;">'.$e->getMessage().'</span>';
        }
        // start the rendering the html output
        $output = '<!-- MediaWiki extension PubmedEx -->';
        $output .= implode('<br />', $html);
        $output .= '<!-- End of PubmedEx -->';

        return $output;
    }
}
