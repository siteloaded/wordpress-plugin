#!/bin/bash

set -eux

DOCKER_COMPOSE="docker-compose --no-ansi -f docker-compose-local.yml"

build() {
    $DOCKER_COMPOSE build
}

start_db() {
    $DOCKER_COMPOSE run --rm wait_for_db
}

start_wordpress() {
    $DOCKER_COMPOSE run --rm wait_for_wordpress
}

listen_wordpress() {
    $DOCKER_COMPOSE logs -f wordpress
}

cleanup() {
    $DOCKER_COMPOSE down || true
}

trap "cleanup" EXIT

build
start_db
start_wordpress
listen_wordpress
