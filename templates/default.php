<span class="pubmed-article">
<?php if ('PubmedArticle' === $Article->ArticleType) { ?>
  <strong><?php echo $Article->Authors; ?> (<?php echo $Article->Year; ?>).</strong><br />
  <?php echo $Article->Title; ?>
  <em><?php echo $Article->Journal; ?></em><?php
    if ('' !== $Article->Volume || '' !== $Article->Issue || '' !== $Article->Pages) {
        echo ', ';
        echo $Article->Volume;
        if ('' !== $Article->Issue) {
            echo '('.$Article->Issue.')';
        }
        if ('' !== $Article->Pages) {
            if ('' !== $Article->Volume || '' !== $Article->Issue) {
                echo ', ';
            }
            echo $Article->Pages;
        }
    }
?>.
<?php } elseif ('PubmedBookArticle' == $Article->ArticleType) { ?>
  <strong><?php echo $Article->Authors; ?> (<?php echo $Article->Year; ?>).</strong><br />
  <?php echo $Article->Title; ?>.
  In <?php echo '' !== $Article->Editors ? $Article->Editors.' '.(1 < $Article->NumOfEditors ? '(Eds.)' : '(Ed.)').', ' : ''; ?>
  <em><?php echo $Article->Book; ?></em><?php if ('' !== $Article->Pages) { ?> (pp. <?php echo $Artcle->Pages; ?>)<?php } ?>.
  <?php echo '' !== $Article->PublisherLocation ? $Article->PublisherLocation.': ' : ''; ?><?php echo $Article->PublisherName; ?>.
<?php } ?>
<?php include __DIR__.'/extlinks.inc.php'; ?>
</span>
