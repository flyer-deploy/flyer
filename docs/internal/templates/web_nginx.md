# Template web_nginx

```yaml
template:
  name: web_nginx
  params:
    web_nginx:
      locations:
        - path: "/"
          block:
            - "try_files $uri $uri/ /index.php?$query_string;"
        - path: "/favicon.ico"
          block:
            - "access_log off; log_not_found off;"
        - path: "/robots.txt.ico"
          block:
            - "access_log off; log_not_found off;"
        - path: "\\.php$"
          modifier: ~
          block: |
            fastcgi_pass ${FLYER_PHP_FPM_8_2_FCGI_URI};
            fastcgi_param SCRIPT_FILENAME \$realpath_root\$fastcgi_script_name;
            include fastcgi_params;
        - path: "/\\.(?!well-known).*"
          modifier: ~
          block: |
            deny all;
            access_log off;
            log_not_found off;
      error_pages:
        404: /index.php
      webroot: public
      directives:
        index: index.php
        charset: utf-8
      headers:
        x-frame-options: SAMEORIGIN
        x-content-type-options: nosniff
```

## NGINX config generation

- For each `locations` item, create a location block with specified location and the modifier if exists. Generate block body based on the `block` key. If the `block` is array, join to string with newline as delimiter.

- For each `error_pages` item, create an `error_page` directive with the key as the status code and the value is the corresponding page.

- Set root to `{{release_path}}/{{webroot}}`

- For each `directives` item, just create a directive using key and the value.

- For each `headers` item, create `add_header` directive with key as the header name and the value as the header value.

- Flyer will provide a few variables that will be substituted if found in any of the config value. Below is the variables list:

  - `FLYER_SERVICE_UPSTREAM`

    This environment variable will be available when deploying an app that binds to an interface and opens a port.

  - `FLYER_USER_PROVIDED_UPSTREAM_XXX`

    This environment variable will be available when Flyer is passed with environment variable `FLYER_USER_PROVIDED_UPSTREAM_XXX`. User can put anything to the `XXX` part. If the config references this variable but the env var is not passed to Flyer, throw error.

  - `FLYER_PHP_FPM_7_4_FCGI_URI`
  - `FLYER_PHP_FPM_8_0_FCGI_URI`
  - `FLYER_PHP_FPM_8_1_FCGI_URI`
  - `FLYER_PHP_FPM_8_2_FCGI_URI`

    All of previous four variables above are the URIs to the FCGI server address for each PHP-FPM process. Not all of the variables will be available.

## NGINX required configurations

Create a locations directory somewhere to put all of the locations config. Each project (remember that project is uniquely identified by `APP_ID` environment variable) will have its own location subdirectory. Flyer will generate location config file and put it in a versioned way, same as how Flyer do releases.

The locations directory is configured via `FLYER_WEB_NGINX_LOCATIONS_DIR` environment variable and has to be set to a valid directory since Flyer will not attempt to create the directory.

Example of how the config is stored: say we have 'Client_eiger' project. We will create a directory `FLYER_WEB_NGINX_LOCATIONS_DIR`/'Client_eiger'. We will also create `current` directory inside the newly-created directory that points to the latest release. Each configuration version will be stored in `release.<current_date>.<sequence_number>` directory.

This way of storing configuration files is needed so we can rollback quickly when the config is not working properly **during the deployment**. This template will check the output of `nginx -t`. If it fails then rollback or do not change the `current` directory.

## Required environment variables

This template requires these environment variables:

- `FLYER_WEB_NGINX_LOCATION`
