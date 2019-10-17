#!/bin/make

SHELL     = /bin/sh
PHP       = /usr/bin/php4
PHPFLAGS  = -q -d max_execution_time=300
REPEAT    = 250
TESTFLAGS = --repeat=1 --nodump --noprofile

PHPFILES        = $(wildcard *.php)
TESTFILES       = $(patsubst %.php,%.test,$(PHPFILES))
SYNTAXFILES     = $(patsubst %.php,%.stx,$(PHPFILES))

.PHONY : all
all: syntax test


.PHONY : test
test: $(TESTFILES)


.PHONY : syntax
syntax: $(SYNTAXFILES)


.PHONY : profile
profile: override TESTFLAGS = --repeat=$(REPEAT) --nodump
profile: $(TESTFILES)


.PHONY : dump
dump: override TESTFLAGS = --repeat=1 --noprofile
dump: $(TESTFILES)


.PHONY : dev
dev: override TESTFLAGS = --repeat=50 --profile --dump
dev: syntax $(TESTFILES)

%.stx: %.php
	$(PHP) -l $<


%.test: %.php
	$(PHP) $(PHPFLAGS) $< $(TESTFLAGS)

##############

.PHONY : devtestinit
devtestinit:
	@make dump > dump.init

.PHONY : devtest
devtest:
	@make dump > dump.test
	@diff --brief dump.init dump.test; \
	 test $$? -eq 0 || diff -u dump.init dump.test | grep '+'
	@echo 'ok'
