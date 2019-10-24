# MediaWiki Extension PubmedEx

This extension provides `<pubmed>...</pubmed>` tags to show article information fetched from [PubMed](https://www.ncbi.nlm.nih.gov/pubmed/).

This is forked from [Pubmed Extension](https://www.mediawiki.org/wiki/Extension:Pubmed) version 2.0 (2018-10-31).

## Install
To install this extension, add the following to LocalSettings.php.

```PHP
wfLoadExtension("PubmedEx");
```

### Optional settings
* `$wgPubmedApiKey`
  * enables to use the E-utilities API key.
    * see more [details](https://ncbiinsights.ncbi.nlm.nih.gov/2017/11/02/new-api-keys-for-the-e-utilities/)
  * default: `""`
* `$wgPubmedCache`
  * cache directory to store the PubMed responses and article data.
  * default: `"${IP}/images/pubmed"`
* `$wgPubmedCacheExpires = 604800;`
  * enables to set cache expiration in sec. `0` means never expires.
  * default: `604800`
* `$wgPubmedProxyHost`
  * set the proxy host for querying PubMed API.
  * default: `""`
* `$wgPubmedProxyPort`
  * set the proxy port.
  * default: `8080`
* `$wgPubmedProxyUser`
  * set the proxy user, if proxy requires authentication.
  * default: `""`
* `$wgPubmedProxyPass`
  * set the proxy password.
  * default: `""`
* `$wgPubmedLimit`
  * maximum number of the PubMed search query returns.
    * remember the PubMed limit of 500 articles.
  * default: `20`
* `$wgPubmedOffset`
  * start offset of the PubMed search query returns.
  * default: `0`
* `$wgPubmedTemplateFile`
  * template file to format PubMed articles. this file should be located under `templates/` directory.
  * default: `"default.php"`

## Usage

#### typical case.

```MediaWiki
<pubmed>15011281</pubmed>
```

#### search `neuroinformatics` and show related five and next five articles.
```MediaWiki
<pubmed limit=5>neuroinformatics</pubmed>
...
<pubmed limit=5 offset=5>neuroinformatics</pubmed>
```

#### override template file `yetanother.php` dynamically.
```MediaWiki
<pubmed template="yetanother.php">15046238</pubmed>
```

## Changes from the original version
* Reimplemented all sources.
  * new E-utilities client to support API key.
  * new PubMed xml parser to support PubmedBookArticle data.
  * new cache system to store each pubmed responses and parsed article data.
  * new template system for more flexible format support.
  * modern MediaWiki extention framework.
* Global variables
  * added: `$wgPubmedApiKey`, `$wgCacheExpires` and `$wgPubmedTemplateFile`
  * removed: `$wgPubmedLayoutFile`, `$wgPubmedLayoutLinks`, `$wgPubmedSOAP`, `$wgNUSOAPencodinghack`, `$wgPubmedWDSLVersion` and `$wgPubmedDEBUG`
* Tag parameters
  * added: `templatefile`.
  * removed: `layoutfile`, `layoutlink` and `debug`.

## License
This software is licensed under the [GNU General Public License 2.0 or later](COPYING).

## Author
* [Yoshihiro Okumura](https://github.com/orrisroot)
* [Andreas Bohne-Lang](https://github.com/bohnelang) (Original Author)

## Usage examples
* https://bsd.neuroinf.jp Brain Science Dictionary project in Japanese.
