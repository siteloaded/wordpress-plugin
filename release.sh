#!/bin/bash

pushd () {
    command pushd "$@" > /dev/null
}

popd () {
    command popd "$@" > /dev/null
}

BUILD_DIR=./tmpbuild
PLUGIN_NAME=siteloaded
ARCHIVE_LOC="$BUILD_DIR/$PLUGIN_NAME"
TAG=${1:-$(git describe --abbrev=0 --tags)}
ARCHIVE="siteloaded-$TAG.zip"

if [ -d "$BUILD_DIR" ]; then
    echo "- cleaning $BUILD_DIR"
    rm -rf "$BUILD_DIR"
fi

if [ -f "$ARCHIVE" ]; then
    echo "- cleaning $ARCHIVE"
    rm "$ARCHIVE"
fi

# echo "- checkouting $TAG"
# git checkout "$TAG" --quiet
# retval=$?
# if [ $retval -ne 0 ]; then
#     exit $retval
# fi

echo "- creating $BUILD_DIR"
mkdir -p "$ARCHIVE_LOC"

echo "- copying..."
cp -R \
    ./src/LICENSE \
    ./src/README.md \
    ./src/*.php \
    ./src/{admin,includes,vendor} \
    "$ARCHIVE_LOC"

echo "- zipping archive"
pushd "$BUILD_DIR"
zip -r -9 -q "../$ARCHIVE" "$PLUGIN_NAME"
popd

echo "- cleaning $BUILD_DIR"
rm -rf "$BUILD_DIR"

echo "- done: $ARCHIVE"
