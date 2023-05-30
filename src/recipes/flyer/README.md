# Flyer Deployer recipe

Dead-simple, bare-minimum Deployer recipe. It will accept a zipped artifact file and deploy it to the destined directories.

## Why very bare-minimum?

To allow great flexibility. Each application can specify their command hooks to be run at various steps in the pipeline. It also supports "templates" to deploy common application like a web application behind an already-installed webserver without manually specifying those command hooks.

## Configuration

Flyer will check these files in the artifact directory in order:

1. flyer.toml
2. flyer.php

The config schema:

- `template.name`

  Select deployment template.

  **Supported values**: 'web.litespeed', 'web.nginx', 'general_process.supervisord', 'general_process.systemd'

  Default: null

- `template.params.<param>`

  Template may need to be provided parameters.

  Default: null

- `commandHooks.preDeploy`

  Run shell command provided here before deployment.

  **Default**: null

- `commandHooks.postDeploy`

  Run shell command provided here after deployment.

  **Default**: null

- `commandHooks.preSymlink`

  Run shell command provided here before symlink-ing current release to the `{{current_directory}}`.

  **Default**: null

- `commandHooks.postSymlink`

  Run shell command provided here after symlink-ing current release to the `{{current_directory}}`.

  **Default**: null

- `commandHooks.start`

  Run shell command provided here to start the application.

  **Default**: null

## Environment variables

### User-provided variables

User must provide these variables to configure Flyer:

- `ARTIFACT_FILENAME`

  Absolute path to zipped artifact file.

- `DEPLOY_PATH`

  Directory to put releases in. It will be accessible internally with `{{deploy_path}}` variable in Deployer.

### Command hooks variables

The process spawned by the commands will have following environment variables:

- `CURRENT_DIRECTORY`

  The `{{deploy_path}}`/current directory.

- `CURRENT_RELEASE_DIRECTORY`

  The `{{deploy_path}}`/release.<current_sequence_number> directory.

## Command hooks

Flyer supports command hooks to run commands at various steps in the pipeline. Currently it only supports shell command. The process spawned by the commands will have all environment variables specified in [Environment variables section](#environment-variables).

## Architecture

🔗 [Architecture](./docs/architecture.md)
