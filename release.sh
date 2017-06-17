#!/bin/bash

pushd () {
    command pushd "$@" > /dev/null
}

popd () {
    command popd "$@" > /dev/null
}

RELEASE_DIR=./release
PLUGIN_NAME=siteloaded
ARCHIVE_LOC="$RELEASE_DIR/$PLUGIN_NAME"
TAG=${1:-$(git describe --abbrev=0 --tags)}

if [ -d "$RELEASE_DIR" ]; then
    echo "- cleaning $RELEASE_DIR"
    rm -rf "$RELEASE_DIR"
fi

echo "- checkouting $TAG"
git checkout "$TAG" --quiet
retval=$?
if [ $retval -ne 0 ]; then
    exit $retval
fi

echo "- creating $RELEASE_DIR"
mkdir -p "$ARCHIVE_LOC"

echo "- copying..."
cp -R \
    ./LICENSE \
    ./README.md \
    ./*.php \
    {admin,includes,languages}/ \
    "$ARCHIVE_LOC"

echo "- zipping archive"
pushd "$RELEASE_DIR"
zip -r -9 -q "../siteloaded-$TAG.zip" "$PLUGIN_NAME"
popd

echo "- cleaning $RELEASE_DIR"
rm -rf "$RELEASE_DIR"

echo "- done: siteloaded-$TAG.zip"
