<?php if ('' !== $Article->Pmid) : ?>
  [<a href="https://www.ncbi.nlm.nih.gov/pubmed/<?= $Article->Pmid; ?>" target="_blank" rel="noopener noreferrer">PubMed:<?= $Article->Pmid; ?></a>]
<?php endif; ?>
<?php if ('' !== $Article->Pmc) : ?>
  [<a href="https://www.ncbi.nlm.nih.gov/pmc/articles/<?= $Article->Pmc; ?>" target="_blank" rel="noopener noreferrer">PMC</a>]
<?php endif; ?>
<?php if ('' !== $Article->Issn) : ?>
  [<a href="https://www.worldcat.org/issn/<?= $Article->Issn; ?>" target="_blank" rel="noopener noreferrer">WorldCat</a>]
<?php endif; ?>
<?php if ('' !== $Article->Doi) : ?>
  [<a href="https://doi.org/<?= $Article->Doi; ?>" target="_blank" rel="noopener noreferrer">DOI</a>]
<?php endif; ?>