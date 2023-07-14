<?php

require __DIR__ . '/../vendor/autoload.php';

// require tasks
require __DIR__ . '/Task/additional.php';
require __DIR__ . '/Task/cleanup.php';
require __DIR__ . '/Task/dependencies.php';
require __DIR__ . '/Task/load_flyer_yaml.php';
require __DIR__ . '/Task/logging.php';
require __DIR__ . '/Task/release.php';
require __DIR__ . '/Task/remove.php';
require __DIR__ . '/Task/shared.php';
require __DIR__ . '/Task/symlink.php';
require __DIR__ . '/Task/writable.php';

use function Flyer\Utils\Common\depends;
use function Flyer\Utils\Common\obtain;
use function Flyer\Utils\Common\mandatory;

use function Deployer\task;

use function Deployer\get;
use function Deployer\set;
use function Deployer\info;
use function Deployer\invoke;
use function Deployer\error;
use function Deployer\run;
use function Deployer\test;
use function Deployer\localhost;

localhost();


task('hook:post_release', function () {
    $cmd = obtain(get('flyer_config'), 'command_hooks', 'post_release');
    if ($cmd != null) {
        run($cmd);
    }
});

task('hook:pre_symlink', function () {
    $cmd = obtain(get('flyer_config'), 'command_hooks', 'pre_symlink');
    if ($cmd != null) {
        run($cmd);
    }
});

task('hook:post_symlink', function () {
    $cmd = obtain(get('flyer_config'), 'command_hooks', 'post_symlink');
    if ($cmd != null) {
        run($cmd);
    }
});

task('hook:start', function () {
    $cmd = obtain(get('flyer_config'), 'command_hooks', 'start');
    if ($cmd != null) {
        run($cmd);
    }
});


function set_variables()
{
    // Common
    set('app_id', mandatory(getenv('APP_ID'), 'APP_ID environment variable'));
    set('app_user', getenv('APP_USER'));
    set('app_group', getenv('APP_GROUP'));
    set('artifact_file', mandatory(getenv('ARTIFACT_FILE'), 'ARTIFACT_FILE environment variable'));
    set('deploy_path', function () {
        $path = mandatory(getenv('DEPLOY_PATH'), 'DEPLOY_PATH environment variable');
        if (!test("[ -d $path ]")) {
            throw error("Deploy path $path is a regular file, not an existing or a non-existent directory");
        }
        return $path;
    });
    set('current_path', "{{deploy_path}}/current");

    // Release
    set('release_name', null);
    set('release_path', null);
    set('release_list', []);
    set('release_version', null);
    set('previous_release_name', null);
    set('with_secure_default_permission', getenv('WITH_SECURE_DEFAULT_PERMISSIONS'));

    // Additional
    set('additional', null);
    set('additional_files_dir', function () {
        $path = getenv('ADDITIONAL_FILES_DIR');
        if (!test("[ -d $path ]")) {
            throw error("Additional filed dir $path is a regular file, not an existing or a non-existent directory");
        }
        return $path;
    });


    // Cleanup
    set('async_cleanup', getenv('ASYNC_CLEANUP'));

    // Dependencies
    set('dependencies', null);

    // Logging
    set('logging', null);
    set('promtail_config_file_path', getenv('PROMTAIL_CONFIG_FILE_PATH'));

    // Remove
    set('remove_files', null);

    // Shared
    set('shared_dirs', null);
    set('shared_files', null);
    set('shared_path', getenv('SHARED_PATH') ?? "/var/share/{{app_id}}");

    // Writable
    set('writables', null);
    set('writable_mode', getenv('WRITABLE_MODE') ?? "by_group");
    echo "fin";
}


task('deploy', function () {
    set_variables();

    info("deploying <fg=magenta;options=bold>{{app_id}}</>");

    invoke('deploy:release');
    invoke('deploy:load_flyer_yaml');

    // Post Release command hook
    if (obtain(get('flyer_config'), 'command_hooks', 'post_release') !== false) {
        invoke('hook:post_release');
    }

    invoke('deploy:dependencies');
    invoke('deploy:shared');
    invoke('deploy:writable');
    invoke('deploy:additional');
    invoke('deploy:logging');
    invoke('deploy:remove');

    // Pre Symlink command hook
    if (obtain(get('flyer_config'), 'command_hooks', 'pre_symlink') !== false) {
        invoke('hook:pre_symlink');
    }

    invoke('deploy:symlink');

    // Post Symlink command hook
    if (obtain(get('flyer_config'), 'command_hooks', 'post_symlink') !== false) {
        invoke('hook:post_symlink');
    }

    invoke('deploy:cleanup');

    // Start command hook
    if (obtain(get('flyer_config'), 'command_hooks', 'start') !== false) {
        invoke('hook:start');
    }
});


task('rollback', function () {
    // invoke('deploy:release');

    invoke('deploy:load_flyer_yaml');
    invoke('deploy:dependencies');
    invoke('deploy:shared');
    invoke('deploy:writable');
    invoke('deploy:additional');
    invoke('deploy:logging');
    invoke('deploy:remove');
    invoke('deploy:symlink');
    invoke('deploy:cleanup');
});