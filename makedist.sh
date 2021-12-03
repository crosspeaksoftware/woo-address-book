#!/bin/bash

./build.sh

rm -rf dist
mkdir -p dist/assets/css
mkdir -p dist/assets/js
mkdir -p dist/includes
mkdir -p dist/templates/myaccount

cp assets/css/style.css dist/assets/css/
cp assets/css/style.min.css dist/assets/css/
cp assets/js/scripts.js dist/assets/js/
cp assets/js/scripts.min.js dist/assets/js/
cp includes/class-wc-address-book.php dist/includes/
cp includes/settings.php dist/includes/
cp templates/myaccount/my-address-book.php dist/templates/myaccount/
cp readme.txt dist/readme.txt
cp LICENSE dist/LICENSE
cp woocommerce-address-book.php dist/woocommerce-address-book.php
