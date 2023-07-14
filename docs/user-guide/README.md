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
        recursive: true
      - path: storage/uploads
        recursive: false
        files_default_writable: true
      - path: storage/some_random_file
  ```

  This will:

  - Set storage/logs directory to be recursively
  - Set storage/uploads directory to be writable. It only applies to the storage/uploads directory since it's not recursive. It also makes newly created directories are by default writable, using the config `files_default_writable`
  - Set storage/some_random_file file to be writable. If `recursive` is specified, it will be ignored since it's a file.

  Some defaults:

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

### Dependencies

- `dependencies`

  List of dependencies. This checks if the commands exist in the system. If the dependencies cannot be satisfied, deployment will fail.

  **Default**: null

  Example:

  ```yaml
  dependencies:
    - ffmpeg
    - git
    - curl
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

### Remove files

- `remove`

  List of files and/or directories to be removed before showing up in production.

  **Default**: null

  Example:

  ```yaml
  remove:
    - test
    - composer.json
    - composer-lock.json
  ```

### Logging

- `logging.driver.type`

  What to use to manage log files. If you want for logs to be available in Grafana, set to `promtail`.

  **Supported values**: `promtail`

- `logging.driver.params`

  Parameters to be passed to the logging driver.

  **Default**: null

  Schema for `promtail` driver:

  ```yaml
  scrape_configs:
    pipeline_stages: <array; checkout [this Promtail doc](https://grafana.com/docs/loki/latest/clients/promtail/configuration/#pipeline_stages); optional>
    static_configs: <object; schema below; optional>
      app_logs_labels: <object; key is the `name` in the `logging.files` item, and the value is an object containing labels key-value pairs; optional>
      global_labels: <object; labels key-value pairs; optional>
  ```

  Example:

  ```yaml
  logging:
    drivers:
      - type: promtail
        params:
        scrape_configs:
          pipeline_stages:
            - match:
              selector: '{filename="storage/logs/database/database.*.log"}'
        static_configs:
          app_logs_labels:
            storage_logs_wildcard:
              type: database_logs
          global_labels:
            foo: bar
  ```

- `logging.rotate.enabled`

  Whether to rotate log files or not.

  **Default**: true if `logging.driver` is set to `promtail`

- `logging.rotate.driver`

  Logrotate driver.

  **Default**: 'logrotate' if `logging.rotate.enabled` is true.

- `logging.files`

  List of log files. It is possible to specify glob pattern.

  Schema:

  ```yaml
  logging:
    files:
      - file: <string; the log files, can be glob pattern; required>
        exclude: <string; excluded log files, can be glob pattern; optional>
        name: <string; name to identify this item to be used in Promtail or other places>
        driver: <string; the driver used to manage this log files; optional; defaults to the first driver in `logging.drivers` if specified >
  ```

  Example:

  ```yaml
  logging:
    files:
      - file: storage/logs/**/*.log
        exclude: storage/logs/**/debug.log
        name: storage_logs_wildcard
        driver: promtail
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

🔗 [Architecture](./src/recipes/internal/docs/design-doc.md)
