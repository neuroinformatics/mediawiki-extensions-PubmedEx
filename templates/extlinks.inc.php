<?php if ('' !== $Article->Pmid) { ?>
  [<a href="https://www.ncbi.nlm.nih.gov/pubmed/<?php echo $Article->Pmid; ?>" target="_blank" rel="noopener noreferrer">PubMed:<?php echo $Article->Pmid; ?></a>]
<?php } ?>
<?php if ('' !== $Article->Pmc) { ?>
  [<a href="https://www.ncbi.nlm.nih.gov/pmc/articles/<?php echo $Article->Pmc; ?>" target="_blank" rel="noopener noreferrer">PMC</a>]
<?php } ?>
<?php if ('' !== $Article->Issn) { ?>
  [<a href="https://www.worldcat.org/issn/<?php echo $Article->Issn; ?>" target="_blank" rel="noopener noreferrer">WorldCat</a>]
<?php } ?>
<?php if ('' !== $Article->Doi) { ?>
  [<a href="https://doi.org/<?php echo $Article->Doi; ?>" target="_blank" rel="noopener noreferrer">DOI</a>]
<?php } ?>
