#!/bin/bash

set -e

# Wordpress.org username
wordpressuser="hallme"
# Wordpress.org plugin slug
package="woo-address-book"

echo "Pushing current README to WordPress.org"
version=`grep "Stable tag:" readme.txt | awk '{print $3}'`

echo "Checking out current trunk on Wordpress SVN"
svn co https://plugins.svn.wordpress.org/${package}/trunk /tmp/readme-${package}

echo "Copying in updated readme.txt"
cp "$(pwd)/readme.txt" /tmp/readme-${package}/readme.txt

echo "Commiting new README to Wordpress SVN trunk"
svn commit --username ${wordpressuser} -m"Updating README file" /tmp/readme-${package}

echo "Cleaning up trunk"
rm -rf /tmp/readme-${package}


echo "Checking out current ${version} version on Wordpress SVN"
svn co https://plugins.svn.wordpress.org/${package}/tags/${version} /tmp/readme-${package}

echo "Copying in updated readme.txt"
cp "$(pwd)/readme.txt" /tmp/readme-${package}/readme.txt

echo "Commiting new README to Wordpress SVN ${version} version"
svn commit --username ${wordpressuser} -m"Updating README file" /tmp/readme-${package}

echo "Cleaning up ${version} version"
rm -rf /tmp/readme-${package}

echo "Done"
