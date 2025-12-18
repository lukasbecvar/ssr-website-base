#!/bin/bash

# install & build app requirements
sh scripts/install.sh

# build & start docker services
docker-compose up --build
