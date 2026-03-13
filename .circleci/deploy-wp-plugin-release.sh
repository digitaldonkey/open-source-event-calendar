#!/usr/bin/env bash

# Prevent history save to keep secrets seecret.
set +o history

if [[ -z "$CIRCLECI" ]]; then
    echo "This script can only be run by CircleCI. Aborting." 1>&2
    exit 1
fi

if [[ -z "$CIRCLE_BRANCH" || "$CIRCLE_BRANCH" != "master" ]]; then
    echo "Build branch is required and must be 'master' branch. Stopping deployment." 1>&2
#    exit 1
fi

if [[ -z "$CIRCLE_TAG" ]]; then
    echo "Git tag is required. Stopping deployment." 1>&2
#    exit 1
fi

if [[ -z "$WP_ORG_SVN_PASSWORD" ]]; then
    echo "WordPress.org password not set. Aborting." 1>&2
    exit 1
fi

if [[ -z "$WP_ORG_PLUGIN_NAME" ]]; then
    echo "WordPress.org plugin name not set. Aborting." 1>&2
    exit 1
fi

if [[ -z "$WP_ORG_USERNAME" ]]; then
    echo "WordPress.org username not set. Aborting." 1>&2
    exit 1
fi

if [[ ! -s "/tmp/$OSEC_RELEASE_FILE" ]]; then
    echo "Can not find release zip. Aborting." 1>&2
    exit 1
fi

# Unpack release for svn.
 mkdir /tmp/release_files
 unzip -q /tmp/open-source-event-calendar.zip -d /tmp/release_files

PLUGIN_BUILD_PATH="/tmp/release_files/open-source-event-calendar"
PLUGIN_SVN_PATH="/tmp/svn"

# Tag of the latest tagged commit across all branches.
LATEST_GIT_TAG=$(git describe --tags `git rev-list --tags --max-count=1`)

if [[ $CIRCLE_TAG != $LATEST_GIT_TAG ]]; then
    echo "LATEST_GIT_TAG:$LATEST_GIT_TAG  is not matching this tag : $CIRCLE_TAG." 1>&2
#    exit 1
fi

if [[ $CIRCLE_TAG =~ /^\d+\.\d+\.\d+$/ ]]; then
    echo "CIRCLE_TAG is a semantic tag." 1>&2
#    exit 1
  else
    echo "CIRCLE_TAG is NOT semantic tag." 1>&2
fi

if [[ "$LATEST_GIT_TAG" =~ /^\d+\.\d+\.\d+$/ ]]; then
    echo "LATEST_GIT_TAG:$LATEST_GIT_TAG is a semantic tag." 1>&2
#    exit 1
  else
    echo "LATEST_GIT_TAG:$LATEST_GIT_TAG is NOT a semantic tag." 1>&2
fi

# Check if the latest SVN tag exists already
TAG=$(svn ls "https://plugins.svn.wordpress.org/$WP_ORG_PLUGIN_NAME/tags/$LATEST_GIT_TAG")
error=$?
if [ $error == 0 ]; then
    # Tag exists, don't deploy
    echo "Latest tag ($LATEST_GIT_TAG) already exists on the WordPress directory. No deployment needed!"
    exit 0
fi


# Checkout the SVN repo
svn co -q "http://svn.wp-plugins.org/$WP_ORG_PLUGIN_NAME" $PLUGIN_SVN_PATH

echo "Existing SVN tags before deploy"
ls $PLUGIN_SVN_PATH/tags

## Move to SVN directory
cd $PLUGIN_SVN_PATH

## Delete the trunk directory
rm -rf ./trunk

# Copy our new version of the plugin as the new trunk directory
cp -r $PLUGIN_BUILD_PATH ./trunk

# copy for tags
svn copy trunk tags/$LATEST_GIT_TAG

# Add new files to SVN
svn stat | grep '^?' | awk '{print $2}' | xargs -I x svn add x@

# Remove deleted files from SVN
svn stat | grep '^!' | awk '{print $2}' | xargs -I x svn rm --force x@
#
#
## Commit to SVN
# svn ci --no-auth-cache --username $WP_ORG_USERNAME --password $WP_ORG_SVN_PASSWORD -m "Deploy OSEC assets"
