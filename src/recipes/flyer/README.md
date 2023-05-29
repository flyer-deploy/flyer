# 'flyer' deployment recipe

A simple deployment recipe. It will accept a zip file containing multiple artifacts. Each artifact directory will have configuration file for the deployment. Flyer will deploy each of the artifact to the destined directories.

## Configuration

Flyer will check these files in artifact directory in order:

1. flyer.toml
2. flyer.php

## How it works
