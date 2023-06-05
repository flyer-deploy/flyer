#!/bin/bash

sudo docker build -t devops-flyer .
sudo docker run --rm devops-flyer "-a ./examples/artifact.zip" "-d /tmp/project"
