#!/bin/bash

set -e

uglifyjs assets/js/scripts.js > assets/js/scripts.min.js

scss --force --no-cache --style compressed assets/css/style.css > assets/css/style.min.css
