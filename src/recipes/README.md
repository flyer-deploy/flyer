# Flyer Deployer recipe

Dead-simple, bare-minimum Deployer recipe. It will accept a zipped artifact file and deploy it to destination directories.

## Why very bare-minimum?

To allow great flexibility. Each application can specify their command hooks to be run at various steps in the pipeline. It also supports "templates" to deploy common application like a web application behind an already-installed webserver without manually specifying those command hooks.

## Configuration

Flyer will check these files in the artifact directory in order:

1. flyer.toml

The config schema:

**Default**: null

- `permission.user`

  User owner of the files and directories. If empty Flyer will keep the user unchanged.

  **Default**: null

- `permission.group`

  Group of the files and directories. If empty Flyer will keep the group unchanged.

  **Default**: null

- `permission.acl_list`

  List of ACL (Access Control List) definitions for directories. If empty Flyer will keep the ACL unchanged.

  **Default**: null

- `permission.default_permission`

  An array containing list of files or directories that need to be writable.

  **Default**: null

- `permission.writable_paths`

  An array containing list of files or directories that need to be writable

  **Default**: null

- `template.name`

  Select deployment template.

  **Supported values**: 'web.litespeed', 'web.nginx', 'general_process.supervisord', 'general_process.systemd'A

- `template.params.<param>`

  Template may need to be provided parameters.

  **Default**: null

- `command_hooks.pre_deploy`

  Run shell command provided here before deployment.

  **Default**: null

- `command_hooks.post_deploy`

  Run shell command provided here after deployment.

  **Default**: null

- `command_hooks.pre_symlink`

  Run shell command provided here before symlink-ing current release to the `{{current_directory}}`.

  **Default**: null

- `command_hooks.post_symlink`

  Run shell command provided here after symlink-ing current release to the `{{current_directory}}`.

  **Default**: null

- `command_hooks.start`

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

## Permissions

## Command hooks

Flyer supports command hooks to run commands at various steps in the pipeline. Currently it only supports shell command. The process spawned by the commands will have all environment variables specified in [Environment variables section](#environment-variables).

## Templates

Templates are just predefined command hooks. For example, if you use 'web.litespeed', all of the command hooks are automatically populated with the commands required to deploy web application behind a LiteSpeed webserver. You can omit one or more predefined command hooks by specifying the hook with `null`.

For example, this will populate command hooks for 'web.litespeed' except the `post_deploy`.

```toml
[template]
name = 'web.litespeed'

[command_hooks]
post_deploy = null
```

## Architecture

🔗 [Architecture](./docs/architecture.md)
