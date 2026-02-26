#!/bin/bash

set -e

esbuild assets/js/scripts.js --minify --outfile=assets/js/scripts.min.js
esbuild assets/css/style.css --minify --outfile=assets/css/style.min.css
