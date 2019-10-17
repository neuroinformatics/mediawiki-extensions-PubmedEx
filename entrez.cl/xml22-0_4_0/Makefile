#!/usr/bin/make

### SETUP #######

NAME       = xml22
PHPDIR     = $(NAME)
TESTFILES  = tests/*.xml tests/*.php tests/*.dtd xml22/*.inc
DOCFILES   = doc/html/xml22.html
WEBDIR     = /home/apache/ister/www/code/$(NAME)

### END SETUP ###

SHELL      = /bin/bash
CP         = /bin/cp
CPFLAGS    = -a
RM         = /bin/rm
CVS        = /usr/bin/cvs
TAR        = /bin/tar
TARFLAGS   = -cz

DEVDIR       = $(shell pwd)
DEVDIRNAME   = $(shell basename `pwd`)
ROOTDIR      = $(shell pwd | sed 's|\(.*/\)$(DEVDIRNAME)|\1|')
WEBTESTDIR   = $(WEBDIR)/test
WEBDOCDIR    = $(WEBDIR)
PHPFILES     = $(wildcard $(PHPDIR)/*.inc)
INSTALLFILES = $(subst $(PHPDIR)/,,$(PHPFILES))


ifndef VERSION
VERSION = 0
endif

.PHONY : all
all: syntax doc onefile dist copy cleanup


.PHONY : doc
doc:
	make -C doc


.PHONY : syntax
syntax:
	make -C $(NAME) syntax
	make -C tests syntax


.PHONY : tests
tests: test

.PHONY : test
test: syntax
	make -C tests


#installation

ifdef INSTALLDIR

.PHONY : install
install:
	test -e $(INSTALLDIR) || mkdir -p $(INSTALLDIR)
	$(CP) $(CPFLAGS) $(foreach FILE,$(INSTALLFILES),$(PHPDIR)/$(FILE)) $(INSTALLDIR)


.PHONY : uninstall
uninstall:
	cd $(INSTALLDIR); $(RM) $(INSTALLFILES)

endif


#build distribution

ifneq ($(VERSION),0)

DISTDIR  = $(NAME)-$(VERSION)


.PHONY : onefile
onefile:
	@echo "make xml22.inc as one single file..."
	make -C xml22-onefile
	cd xml22-onefile; cvs commit -m'commit via make VERSION=$(VERSION)' xml22.inc.gz


.PHONY : dist
dist:
	@echo "make distribution $(VERSION)..."
	$(CP) $(CPFLAGS) $(DEVDIR) $(ROOTDIR)$(DISTDIR)-cvs
	cd $(ROOTDIR)$(DISTDIR)-cvs; $(CVS) tag -b -F version_$(VERSION) . ; cvs update .
	cd $(ROOTDIR); $(CVS) export -r version_$(VERSION) -d $(DISTDIR) $(NAME)
	cd $(ROOTDIR); $(TAR) $(TARFLAGS) -f $(DISTDIR).tar.gz $(DISTDIR)
	$(CVS) update -A .


.PHONY : copy
copy: 
	$(RM) $(WEBDIR)/$(NAME)*.tar.gz
	$(CP) $(CPFLAGS) $(ROOTDIR)$(DISTDIR).tar.gz $(WEBDIR)
	$(CP) $(CPFLAGS) $(TESTFILES) $(WEBTESTDIR)
	$(CP) $(CPFLAGS) $(DOCFILES) $(WEBDOCDIR)
	echo "$(VERSION)" > $(WEBDIR)/version


.PHONY : cleanup
cleanup:
	@echo "cleaning up..."
	-$(RM) -rf $(ROOTDIR)$(DISTDIR)
	-$(RM) -rf $(ROOTDIR)$(DISTDIR)-cvs


.PHONY : clean
clean: cleanup
	make -C xml22-onefile clean
	-$(RM) -rf $(ROOTDIR)$(DISTDIR).tar.gz

endif


.PHONY: help
help:
	@echo "valid targets are:"
	@echo "all"
	@echo "test"
	@echo "install INSTALLDIR=<path>"
	@echo "dist VERSION=<MAJOR_MINOR_BUILD>"