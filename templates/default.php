<span class="pubmed-article">
<?php if ('PubmedArticle' === $Article->ArticleType) : ?>
  <strong><?= $Article->Authors; ?> (<?= $Article->Year; ?>).</strong><br />
  <?= $Article->Title; ?>
  <em><?= $Article->Journal; ?></em><?php if ('' !== $Article->Volume || '' !== $Article->Issue || '' !== $Article->Pages) :
      echo ', ';
      if ('' !== $Article->Volume) {
          echo $Article->Volume;
      }
      if ('' !== $Article->Issue) {
          echo '('.$Article->Issue.')';
      }
      if ('' !== $Article->Pages) {
          if ('' !== $Article->Volume || '' !== $Article->Issue) {
              echo ', ';
          }
          echo $Article->Pages;
      }
  endif; ?>.
<?php elseif ('PubmedBookArticle' == $Article->ArticleType) : ?>
  <span style="font-weight:bold;"><?= $Article->Authors; ?> (<?= $Article->Year; ?>).</span><br />
  <?= $Article->Title; ?>.
  In <?= '' !== $Article->Editors ? $Article->Editors.' '.(1 < $Article->NumOfEditors ? '(Eds.)' : '(Ed.)').', ' : ''; ?>
  <em><?= $Article->Book; ?></em><?php if ('' !== $Article->Pages) : ?> (pp. <?= $Artcle->Pages; ?>)<?php endif; ?>.
  <?= '' !== $Article->PublisherLocation ? $Article->PublisherLocation.': ' : ''; ?><?= $Article->PublisherName; ?>.
<?php endif; ?>
<?php include __DIR__.'/extlinks.inc.php'; ?>
</span>