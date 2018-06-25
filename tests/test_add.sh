#!/bin/bash

set -e

docker run -v $(pwd)/..:/app \
    --rm \
    -v $(pwd):/aenthill \
    -v "/var/run/docker.sock:/var/run/docker.sock" \
    -e PHEROMONE_FROM_IMAGE_NAME= \
    -e PHEROMONE_FROM_CONTAINER_ID= \
    -e PHEROMONE_WHOAMI=theaentmachine/aent-mysql \
    -e PHEROMONE_CONTAINER_PROJECT_DIR=/aenthill \
    -e PHEROMONE_HOST_PROJECT_DIR=$(pwd) \
    -e PHEROMONE_LOG_LEVEL=DEBUG \
    -ti \
    theaentmachine/base-php-aent:0.0.14 \
    php /app/src/aent.php ADD
