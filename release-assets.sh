#!/bin/bash

set -e

# Wordpress.org username
wordpressuser="crosspeak"
# Wordpress.org plugin slug
package="woo-address-book"

echo "Releasing new version of the assets"

echo "Checking out current version on Wordpress SVN"
svn co https://plugins.svn.wordpress.org/${package}/assets /tmp/assets-${package}

echo "Copying in updated files"
rsync -rv --delete --exclude ".svn" wordpress.org-assets/. /tmp/assets-${package}/.

cd /tmp/assets-${package}/
# Add and delete new/old files.
svn status | grep "^!" | awk '{print $2"@"}' | tr \\n \\0 | xargs -0 svn delete || true
svn status | grep "^?" | awk '{print $2"@"}' | tr \\n \\0 | xargs -0 svn add || true

echo "Commiting assets to Wordpress SVN"
svn commit --username ${wordpressuser} -m"Updating assets for ${package}" /tmp/assets-${package}

echo "Cleaning up"
rm -rf /tmp/assets-${package}

echo "Done"
