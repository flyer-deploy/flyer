# Flyer Deployer recipe

Dead-simple, bare-minimum Deployer recipe. It will accept a zipped artifact file and deploy it to destination directories.

## Why very bare-minimum?

To allow great flexibility. Each application can specify their command hooks to be run at various steps in the pipeline. It also supports "templates" to deploy common application like a web application behind an already-installed webserver without manually specifying those command hooks.

## Configuration

Flyer will check these files in the artifact directory in order:

1. flyer.yaml

The config schema:

### Permission

- `permission.writable_paths`

  An array containing list of files or directories that need to be writable.

  **Default**: null

  Example:

  ```yaml
  permission:
    writable_paths:
      - path: storage/logs
        by: group
        recursive: true
      - path: storage/uploads
        by: user
        recursive: false
      - path: storage/some_random_file
        by: user
  ```

  This will:

  - Set storage/logs directory to be writable by group of the directories and/or files recursively,
  - Set storage/uploads directory to be writable by user. It only applies to the storage/uploads directory since it's not recursive, and
  - Set storage/some_random_file file to be writable by user. If `recursive` is specified, it will be ignored since it's a file.

  Some defaults:

  - `by` defaults to "user"
  - `recursive` defaults to `false`

### Template

- `template.name`

  Select deployment template.

  **Supported values**: 'web.litespeed', 'web.nginx', 'general_process.supervisord', 'general_process.systemd'

- `template.params.<param>`

  Template may need to be provided parameters.

  **Default**: null

### Command hooks

- `command_hooks.pre_deploy`

  Run shell command provided here before deployment.

  **Default**: null

- `command_hooks.post_release`

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

### Shared

- `shared.dirs`

  List of directories to be linked to shared dir.

  **Default**: null

  Example:

  ```yaml
  shared:
    dirs:
      - storage
      - var
  ```

- `shared.files`

  List of files to be linked to shared dir.

  **Default**: null

  Example:

  ```yaml
  shared:
    files:
      - assets/json/users.json
      - assets/csv/pricing.csv
  ```

### Additional files

- `additional.files`

  List of additional files to be copied to release.

  **Default**: null

  Example:

  ```yaml
  additional:
    files:
      - /tmp/app_envs/.env
  ```

### Logging

- `logging.driver.type`

  What to use to manage log files. If you want for logs to be available in Grafana, set to 'promtail'.

  **Supported values**: 'promtail'

- `logging.rotate`

  Whether to rotate log files or not.

  **Default**: true if `logging.driver.type` is set to 'promtail'

- `logging.log_files`

  List of log files. It is possible to specify glob pattern.

  Example:

  ```yaml
  logging:
    log_files:
      - storage/logs/**/*.log
  ```

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

For example, this will populate command hooks for 'web.litespeed' except the `post_release`.

```yaml
template:
  name: web.litespeed

command_hook:
  post_release: null
```

## Architecture

🔗 [Architecture](./docs/architecture.md)
