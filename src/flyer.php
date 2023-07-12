<?php

namespace Deployer;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/common/utils.php';
require __DIR__ . '/deploy/additional.php';
require __DIR__ . '/deploy/release.php';
require __DIR__ . '/deploy/load_config.php';
require __DIR__ . '/deploy/dependencies.php';
require __DIR__ . '/deploy/shared.php';
require __DIR__ . '/deploy/logging.php';
require __DIR__ . '/deploy/writable.php';
require __DIR__ . '/deploy/remove_files.php';
require __DIR__ . '/deploy/symlink.php';
require __DIR__ . '/deploy/cleanup.php';

localhost();

task('hook:post_release', function () {
    if (isset(get('config')['command_hooks']['post_release'])) {
        run(get('config')['command_hooks']['post_release']);
    }
});

task('hook:pre_symlink', function () {
    if (isset(get('config')['command_hooks']['pre_symlink'])) {
        run(get('config')['command_hooks']['pre_symlink']);
    }
});

task('hook:post_symlink', function () {
    if (isset(get('config')['command_hooks']['post_symlink'])) {
        run(get('config')['command_hooks']['post_symlink']);
    }
});

task('hook:start', function () {
    if (isset(get('config')['command_hooks']['start'])) {
        run(get('config')['command_hooks']['start']);
    }
});

function set_variables() {
    // Variable Sets
    // ===========================================
    // Name of the app
    set('app_id', mandatory(getenv('APP_ID'), 'APP_ID environment variable'));

    // User to be assigned to app
    set('app_user', getenv('APP_USER'));

    // Group to be assigned to app
    set('app_group', getenv('APP_GROUP'));

    set('release_version', mandatory(getenv('RELEASE_VERSION'), 'RELEASE_VERSION environment variable'));

    // Set writable to user or to group
    set('writable_mode', getenv('WRITABLE_MODE'));

    // File name of zipped artifact
    set('artifact_file', mandatory(getenv('ARTIFACT_FILE'), 'ARTIFACT_FILE environment variable'));

    // Location for the app to be deployed
    set('deploy_path', mandatory(getenv('DEPLOY_PATH'), 'DEPLOY_PATH environment variable'));

    // Return current release path
    set('current_path', '{{deploy_path}}/current');

    // Shared dir location for the app
    set('shared_path', getenv('SHARED_PATH') ?? '/var/share');

    // Additional files to be added to release, Azagent
    set('additional_files_dir', getenv('ADDITIONAL_FILES_DIR'));

    // IDK what this do
    set('with_secure_default_permission', getenv('WITH_SECURE_DEFAULT_PERMISSIONS'));

    // Async cleanup to make `rm -rf` command in the cleanup step be put in background
    set('async_cleanup', getenv('ASYNC_CLEANUP'));

    set('promtail_config_file_path', getenv('PROMTAIL_CONFIG_FILE_PATH'));
    // ===========================================
}

task('deploy', function () {
    set_variables();
    
    // Hall of shame
    if (get('with_secure_default_permission') == 1 && !commandExist('setfacl')) {
        writeln("YOU should be ashamed for not installing setfacl >:(");
    }

    // Showing info about current deployment
    info("deploying <fg=magenta;options=bold>{{app_id}}</>");

    // Release the app to deploy path
    invoke('deploy:release');

    // Load configuration flyer.yaml
    invoke('deploy:load_config');
    $config = get('config');

    // Check dependencies
    invoke('deploy:dependencies');

    // Command hook for post release
    if (isset($config['command_hooks']['post_release']) && $config['command_hooks']['post_release'] === false) {
        // Do nothing
    } else {
        invoke('hook:post_release');
    }

    // Set shared dirs
    invoke('deploy:shared');

    // Set permission writeable to dirs
    invoke('deploy:writable');

    // Add external additional files into release path
    invoke('deploy:additional');

    invoke('deploy:logging');

    // Command hook for pre symlink
    if (isset($config['command_hooks']['pre_symlink']) && $config['command_hooks']['pre_symlink'] === false) {
        // Do nothing
    } else {
        invoke('hook:pre_symlink');
    }

    // Remove files specificed in `remove` config option
    invoke('deploy:remove_files');

    // Symlink release to deploy_path/current
    invoke('deploy:symlink');

    // Command hook for post symlink
    if (isset($config['command_hooks']['post_symlink']) && $config['command_hooks']['post_symlink'] === false) {
        // Do nothing
    } else {
        invoke('hook:post_symlink');
    }

    // Command hook for starting the app
    if (isset($config['command_hooks']['start']) && $config['command_hooks']['start'] === false) {
        // Do nothing
    } else {
        invoke('hook:start');
    }

    // Cleanup for after deployment
    invoke('deploy:cleanup');
});
