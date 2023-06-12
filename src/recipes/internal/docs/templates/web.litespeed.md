# Template web.litespeed

```yaml
template:
  name: web.litespeed
  params:
    webroot: public
    blocked_files:
      - .env
    extra_headers:
      x-frame-options: DENY
```

## Flow

`post_release` command hook:

1. Generate context config and put to `context-$APP_ID.conf` file within `FLYER_WEB_LITESPEED_CONTEXTS_DIR` directory

`post_symlink` command hook:

2. Run `service lsws restart`

## Requirements

Create a contexts directory somewhere to put all of the contexts config. Provide Flyer with `FLYER_WEB_LITESPEED_CONTEXTS_DIR` environment variable that is the directory to store the contexts config and has to be set to a valid directory since Flyer will not attempt to create the directory.

## Context config generation

Context is the LiteSpeed way to define a path and apply specific configuration for that specific path.

Use this template to generate context config file:

```
context $FLYER_WEB_LITESPEED_PATH {
  location $DEPLOY_PATH/{{params.webroot}}
  allowBrowse 1
  rewrite {
   enable 1
  }
  addDefaultCharset off
  phpIniOverride {}
  extraHeaders <<<END_rules
    {{params.extra_headers}}
  END_rules
}
```

### Extra headers

This template accepts parameter `extra_headers` which are key-value pairs to add additional HTTP headers to the context.

For each pair, add this line inside `extraHeaders` key in context config:

`Header set {{key}} {{value}}`

The provided template configuration (yaml) above will generate this `extraHeaders` config value:

```
    ...
    extraHeaders <<<END_rules
        Header set x-frame-options DENY
    END_rules
    ...
```

### Changing existing context config

Difficult. The old deployer uses [this library](https://github.com/bagaswh/litespeed-conf) to work with the config file. Unfortunately that library is written in JavaScript.

For a second thought, let's skip this. It's extremely rare that any code changes also requires a webserver config update.

### Blocked files

This template accepts array parameter `blocked_files` that will block the files specified in it.

For each entry in the parameter, create the following block in the context config:

```
context $FLYER_WEB_LITESPEED_PATH/{{file_to_be_blocked}} {
    allowBrowse 0
}
```

## Required environment variables

This template requires these environment variables:

- `FLYER_WEB_LITESPEED_PATH`
- `FLYER_WEB_LITESPEED_CONTEXTS_DIR`

Throw error if those variables are not set.
