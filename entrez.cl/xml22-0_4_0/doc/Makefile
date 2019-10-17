#!/usr/bin/make

# make documentation

VENDOR    = Ister.ORG
VENDORWWW = " "
NAME      = xml22

# insert manpage stems
MAN1FILES =
MAN2FILES =
MAN3FILES = xml22
MAN4FILES =
MAN5FILES =
MAN6FILES =
MAN7FILES =
MAN8FILES =
MAN9FILES =

###
srcdir   = .
poddir   = $(srcdir)/src
htmldir  = $(srcdir)/html
pdfdir   = $(srcdir)/pdf

mandir   = $(srcdir)/man
man1dir  = $(mandir)/man1
man2dir  = $(mandir)/man2
man3dir  = $(mandir)/man3
man4dir  = $(mandir)/man4
man5dir  = $(mandir)/man5
man6dir  = $(mandir)/man6
man7dir  = $(mandir)/man7
man8dir  = $(mandir)/man8
man9dir  = $(mandir)/man9
man1ext  = .1
man2ext  = .2
man3ext  = .3
man4ext  = .4
man5ext  = .5
man6ext  = .6
man7ext  = .7
man8ext  = .8
man9ext  = .9


MAN1LIST = $(patsubst %, $(man1dir)/%$(man1ext), $(MAN1FILES))
MAN2LIST = $(patsubst %, $(man2dir)/%$(man2ext), $(MAN2FILES))
MAN3LIST = $(patsubst %, $(man3dir)/%$(man3ext), $(MAN3FILES))
MAN4LIST = $(patsubst %, $(man4dir)/%$(man4ext), $(MAN4FILES))
MAN5LIST = $(patsubst %, $(man5dir)/%$(man5ext), $(MAN5FILES))
MAN6LIST = $(patsubst %, $(man6dir)/%$(man6ext), $(MAN6FILES))
MAN7LIST = $(patsubst %, $(man7dir)/%$(man7ext), $(MAN7FILES))
MAN8LIST = $(patsubst %, $(man8dir)/%$(man8ext), $(MAN8FILES))
MAN9LIST = $(patsubst %, $(man9dir)/%$(man9ext), $(MAN9FILES))


POD2HTML      = pod2html
POD2HTMLFLAGS =  --css doc.css --header --backlink 'back to top'
POD2MAN       = pod2man
POD2MANFLAGS  = --center "$(VENDOR) - $(NAME)"
POD2PDF       = pod2pdf
POD2PDFFLAGS  = --paper A4 --in-header=$(VENDOR) --out-header=$(NAME) \
  --cover-title-vendor=$(VENDORWWW) --cover-title-head="Documentation:by:$(VENDOR)"


PODLIST  = $(wildcard $(poddir)/*.pod)
HTMLLIST = $(patsubst $(poddir)/%.pod, $(htmldir)/%.html, $(PODLIST))
PDFLIST  = $(patsubst $(poddir)/%.pod, $(pdfdir)/%.pdf, $(PODLIST))

INTERMEDIATE  =
INTERMEDIATE += $(srcdir)/pod2*~


define run-pod2man
$(POD2MAN) $(POD2MANFLAGS) $< $@
gzip -f $@
endef


.PHONY : doc
doc: $(HTMLLIST) man $(PDFLIST) cleanup

#html
$(HTMLLIST): $(htmldir)/%.html: $(poddir)/%.pod $(poddir)/doc.css
	test -e $(htmldir) || mkdir $(htmldir)
	cd $(htmldir)
	cp $(poddir)/doc.css $(htmldir)
	$(POD2HTML) $(POD2HTMLFLAGS) $< > $@
	cd $(srcdir)

#pdf
$(PDFLIST): $(pdfdir)/%.pdf: $(poddir)/%.pod
	test -e $(pdfdir) || mkdir $(pdfdir)
	cd $(pdfdir)
	$(POD2PDF) $(POD2PDFFLAGS) --podfile $<
	mv $<.pdf $@
	cd $(srcdir)

#man
.PHONY : man
man: $(MAN1LIST) $(MAN2LIST) $(MAN3LIST) $(MAN4LIST) $(MAN5LIST) \
        $(MAN6LIST) $(MAN7LIST) $(MAN8LIST) $(MAN9LIST)

$(MAN1LIST): $(man1dir)/%$(man1ext): $(poddir)/%.pod
	mkdir -p $(man1dir)
	$(run-pod2man)

$(MAN2LIST): $(man2dir)/%$(man2ext): $(poddir)/%.pod
	mkdir -p $(man2dir)
	$(run-pod2man)

$(MAN3LIST): $(man3dir)/%$(man3ext): $(poddir)/%.pod
	mkdir -p $(man3dir)
	$(run-pod2man)

$(MAN4LIST): $(man4dir)/%$(man4ext): $(poddir)/%.pod
	mkdir -p $(man4dir)
	$(run-pod2man)

$(MAN5LIST): $(man5dir)/%$(man5ext): $(poddir)/%.pod
	mkdir -p $(man5dir)
	$(run-pod2man)

$(MAN6LIST): $(man6dir)/%$(man6ext): $(poddir)/%.pod
	mkdir -p $(man6dir)
	$(run-pod2man)

$(MAN7LIST): $(man7dir)/%$(man7ext): $(poddir)/%.pod
	mkdir -p $(man7dir)
	$(run-pod2man)

$(MAN8LIST): $(man8dir)/%$(man8ext): $(poddir)/%.pod
	mkdir -p $(man8dir)
	$(run-pod2man)

$(MAN9LIST): $(man9dir)/%$(man9ext): $(poddir)/%.pod
	mkdir -p $(man9dir)
	$(run-pod2man)


#cleanup
.PHONY : cleanup
cleanup:
	@echo "cleaning up..."
	-rm $(INTERMEDIATE)

#clean
.PHONY : clean
clean: cleanup
ifdef HTMLLIST
	-rm $(htmldir)/*
	-rmdir $(htmldir)
endif
ifdef PDFLIST
	-rm $(PDFLIST)
	-rmdir $(pdfdir)
endif
ifdef MAN1FILES
	-rm $(man1dir)/*
	-rmdir $(man1dir)
endif
ifdef MAN2FILES
	-rm $(man2dir)/*
	-rmdir $(man2dir)
endif
ifdef MAN3FILES
	-rm $(man3dir)/*
	-rmdir $(man3dir)
endif
ifdef MAN4FILES
	-rm $(man4dir)/*
	-rmdir $(man4dir)
endif
ifdef MAN5FILES
	-rm $(man5dir)/*
	-rmdir $(man5dir)
endif
ifdef MAN6FILES
	-rm $(man6dir)/*
	-rmdir $(man6dir)
endif
ifdef MAN7FILES
	-rm $(man7dir)/*
	-rmdir $(man7dir)
endif
ifdef MAN8FILES
	-rm $(man8dir)/*
	-rmdir $(man8dir)
endif
ifdef MAN9FILES
	-rm $(man9dir)/*
	-rmdir $(man9dir)
endif
	test -e $(mandir) && rmdir $(mandir)

