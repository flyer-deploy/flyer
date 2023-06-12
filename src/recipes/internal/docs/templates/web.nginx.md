# Template web.nginx

```yaml
template:
  name: web.nginx
  params:
    mode: php.laravel
    webroot: public
    index: index.php
    static_file_caching:
      files:
        - pattern: .(ico|png|jpg|jpeg|mp3|wav)
          expires_in: 1y
          access_log: off
    blocked_files:
      - %{hidden_files}
      - .env
```

## Things this template should do and ask

- Set root directory
- Set URL path of where the application can be accessed
- Is it PHP or generic?
- Is the PHP application have entrypoint (public/index.php) or the files can be accessed directly?

## NGINX required configurations

Create a locations directory somewhere to put all of the locations config. Each project (remember that project is uniquely identified by `APP_ID` environment variable) will have its own location subdirectory. Flyer will generate location config file and put it in a versioned way, same as how Flyer do releases.

The locations directory is configured via `FLYER_WEB_NGINX_LOCATIONS_DIR` environment variable and has to be set to a valid directory since Flyer will not attempt to create the directory.

Example of how the config is stored: say we have 'Client_eiger' project. We will create a directory `FLYER_WEB_NGINX_LOCATIONS_DIR`/'Client_eiger'. We will also create `current` directory inside the newly-created directory that points to the latest release. Each configuration version will be stored in `release.<current_date>.<sequence_number>` directory.

This way of storing configuration files is needed so we can rollback quickly when the config is not working properly **during the deployment**. This template will check the output of `nginx -t`. If it fails then rollback or do not change the `current` directory.

## Required environment variables

This template requires these environment variables:

- `FLYER_WEB_NGINX_LOCATION`

## Parameters

These keys will be read from `template.params`.

- `language`

**Supported values**: 'php', 'generic'

**Default**: 'generic'

## Language handling

The parameters `language` accepts 'php' and 'generic' value.

### PHP

### Generic
