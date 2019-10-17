#!/usr/bin/perl

my $filename = shift;
my $file     = '';

open(FILE, "<$filename") || die "could not open file: $filename\n$!\n";
while (<FILE>) {
  $file .= $_ unless ( /^\s*$/ or /^$/ );
}
close(FILE);

$file =~ s|/\*L.*L\*/\n||gs;    #delete license
$file =~ s|require_once.*||g;   #delete require directives
$file =~ s|//.*\n||g;           #delete comments

print $file;

