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
        $cache = '' !== $wgPubmedCache ? $wgPubmedCache : $wgUploadDirectory.'/pubmed';
        $limit = (int) (isset($args['limit']) ? $args['limit'] : $wgPubmedLimit);
        if (0 >= $limit || 500 < $limit) {
            return self::renderErrorResponse('Bad limit parameter.');
        }
        $offset = (int) (isset($args['offset']) ? $args['offset'] : $wgPubmedOffset);
        if (0 > $offset) {
            return self::renderErrorResponse('Bad offset parameter.');
        }
        $templatefile = isset($args['templatefile']) ? trim($args['templatefile']) : $wgPubmedTemplateFile;
        if (!preg_match('/\A[a-zA-Z0-9_-]+\.php\z/', $templatefile)) {
            return self::renderErrorResponse('Bad templatefile parameter.');
        } else {
            try {
                $pubmed = new Pubmed($wgPubmedApiKey);
                $pubmed->setCache($cache, $wgPubmedCacheExpires);
                if ('' !== $wgPubmedProxyHost) {
                    $pubmed->setProxy($wgPubmedProxyHost, $wgPubmedProxyPort, $wgPubmedProxyUser, $wgPubmedProxyPass);
                }
                $articles = $pubmed->search($term, $limit, $offset);
                if (empty($articles)) {
                    return self::renderErrorResponse('Resource not found in PubMed.');
                }
                $template = new Template($templatefile);
                foreach ($articles as $article) {
                    $html[] = $template->render($article);
                }
            } catch (\Exception $e) {
                return self::renderErrorResponse($e->getMessage());
            }
        }

        return self::renderResponse(implode('<br />', $html));
    }

    /**
     * render error response.
     *
     * @param string $message
     *
     * @return string
     */
    private static function renderErrorResponse($message)
    {
        return self::renderResponse('<span style="color:red;font-weight:bold;">'.$message.'</span>');
    }

    /**
     * render response.
     *
     * @param string $message
     *
     * @return string
     */
    private static function renderResponse($message)
    {
        return '<!-- MediaWiki extension PubmedEx -->'.$message.'<!-- End of PubmedEx -->';
    }
}
