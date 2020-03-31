#!/bin/bash

set -e

# Wordpress.org username
wordpressuser="hallme"
# Wordpress.org plugin slug
package="woo-address-book"

version=$1

if [[ ! $version ]]; then
	echo "Needs a version number as argument"
	exit 1
fi

./build.sh

echo "Releasing version ${version}"

echo "Setting version number in readme.txt and php files"
perl -pi -e "s/Stable tag: .*/Stable tag: ${version}/g" readme.txt
perl -pi -e "s/Version: .*/Version: ${version}/g" woocommerce-address-book.php
perl -pi -e "s/\@version .*/\@version  ${version}/g" includes/class-wc-address-book.php
perl -pi -e "s/this->version = '.*';/this->version = '${version}';/g" includes/class-wc-address-book.php

if ([[ $(git status | grep readme.txt) ]] || [[ $(git status | grep woocommerce-address-book.php) ]]); then
	echo "Committing changes"
	git add readme.txt
	git add woocommerce-address-book.php
	git add includes/class-wc-address-book.php
	git commit -m"Update readme with new stable tag $version"
fi

echo "Tagging locally"
git tag $version

echo "Pushing tag to git"
git push --tags origin master

echo "Checking out current version on Wordpress SVN"
svn co https://plugins.svn.wordpress.org/${package}/trunk /tmp/release-${package}

echo "Copying in updated files"
rsync -rv --delete \
	--exclude=".git" \
	--exclude=".distignore" \
	--exclude=".travis.yml" \
	--exclude=".gitignore" \
	--exclude=".svn" \
	--exclude="vendor" \
	--exclude="node_modules" \
	--exclude="tests" \
	--exclude="composeer.json" \
	--exclude="composeer.lock" \
	--exclude="phpcs.xml.dist" \
	--exclude="phpunit.xml.dist" \
	--exclude="build.sh" \
	--exclude="release.sh" \
	--exclude="release-readme.sh" \
	--exclude="README.md" \
	. /tmp/release-${package}/.

cd /tmp/release-${package}/
# Add and delete new/old files.
svn status | grep "^!" | awk '{print $2"@"}' | tr \\n \\0 | xargs -0 svn delete || true
svn status | grep "^?" | awk '{print $2"@"}' | tr \\n \\0 | xargs -0 svn add || true

echo "Commiting version ${version} to Wordpress SVN"
svn commit --username ${wordpressuser} -m"Releasing version ${version}" /tmp/release-${package}

echo "Creating version ${version} SVN tag"
svn copy --username ${wordpressuser} https://plugins.svn.wordpress.org/${package}/trunk https://plugins.svn.wordpress.org/${package}/tags/${version} -m"Creating new ${version} tag"

echo "Cleaning up"
rm -rf /tmp/release-${package}

echo "Done"
