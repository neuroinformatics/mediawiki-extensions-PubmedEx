#!/bin/make

SHELL       = /bin/sh
PERL        = /usr/bin/perl
PERLFLAGS   = cat.pl
CAT         = /bin/cat
CATFLAGS    =
CP          = /bin/cp
CAT         = /bin/cat
RM          = /bin/rm
PHP4        = /usr/bin/php4
PHP4FLAGS   =  -l
ZIP         = /bin/gzip
ZIPFLAGS    = -9 -f

PHPDIR      = ../xml22
PHPFILES    = xml22-parser.inc xml22-search.inc xml22-edit.inc xml22-share.inc
CATLIST    := $(foreach FILE,$(PHPFILES),$(PHPDIR)/$(FILE))
CATFILESTEM = xml22
LICENSEFILE = shortlicense
CATFILETMP  = $(CATFILESTEM).cat

.PHONY: all
all: cat cleanup


.PHONY: cat
cat: $(CATFILETMP)
	$(CP) $(CATFILETMP) $(CATFILESTEM).inc
	$(PHP4) $(PHP4FLAGS) $(CATFILESTEM).inc
	$(ZIP) $(ZIPFLAGS) $(CATFILESTEM).inc


$(CATFILETMP): $(CATLIST)
	echo '<?php' > $@
	$(CAT) $(LICENSEFILE) >> $@
	echo '?>' >> $@
	$(foreach FILE,$(CATLIST),$(PERL) $(PERLFLAGS) $(FILE) >> $@;)


.PHONY: cleanup
cleanup:
	-test -e $(CATFILETMP) && $(RM) $(CATFILETMP)
	-test -e $(CATFILESTEM).inc && $(RM) $(CATFILESTEM).inc

.PHONY : clean
clean: cleanup
	-test -e $(CATFILESTEM).inc.gz && $(RM) $(CATFILESTEM).inc.gz