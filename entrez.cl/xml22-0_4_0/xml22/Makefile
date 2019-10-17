#!/usr/bin/make

SHELL     = /bin/sh
PHP       = /usr/bin/php4
PHPFLAGS  = -l

PHPFILES        = $(wildcard *.inc)
SYNTAXFILES     = $(patsubst %.inc,%.stx,$(PHPFILES))

.PHONY : syntax
syntax: $(SYNTAXFILES)

%.stx: %.inc
	$(PHP) -l $<