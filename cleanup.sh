#!/bin/sh
PREFIX=/Users/Adios/Works/wg
find $PREFIX/public/posters -iname '*.png' -cmin +0 -exec rm -rf {} \;
