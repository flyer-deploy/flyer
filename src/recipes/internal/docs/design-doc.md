# Design doc

Currently it only supports artifact archived with zip format. Otherwise throw error.

## Flow

1. Run `pre_deploy` command hooks.
2. Unzip the artifact to the release directory. Release directory will be `{{deploy_path}}`/release.`<current_date>`.`<sequence_number>`, where `<sequence_number>` is +1 increment of the largest `<current_date>`.`<sequence_number>` in the directory.
3. Create `current_backup` symlink that points to the previous release directory.
4. Run `post_release` command hooks.
5. Set required permission for the release directory and its files and/or subdirectories.
6. Run `pre_symlink` command hooks.
7. Set symlink of `{{deploy_path}}`/current to point to the current release sequence number. Webserver should be configured to point to this directory.
8. Run `post_symlink` command hooks.
9. Delete the `current_backup` symlink.
10. Delete previous releases if exist.

## Configuration

### Environment variables substitution

If Flyer sees a string with pattern `${[a-zA-Z0-9_]+}` (will be called _substitution macro_ onwards) somewhere in the config file, substitute it with the value of the environment variable it is referring to. Example, if there's `${FLYER_THIS_IS_FROM_ENVIRONMENT_VARIABLE}` then substitute it with the value of environment variable `$FLYER_THIS_IS_FROM_ENVIRONMENT_VARIABLE`. If the variable does not exist, substitute it with empty string. Only variables that start with `FLYER_` are allowed to be substituted. Flyer will throw error if it finds substitution macro in which the variables do not start with `FLYER_`.

## Permissions

1.  Flyer reads optional `APP_USER` and `APP_GROUP` environment variables. These variables will be used as the user and group of the files and directories.

2.  Before unzipping artifact to the release directory, do these steps:

    2.1. Set the user and the group owner of the release directory to `APP_USER` and `APP_GROUP` respectively. If only one of the variables are set, only chown with the available variable.

    2.2. Run `chmod u+rwx,g+rx` to the directory.

    2.3. If `APP_GROUP` is specified, set [SGID](<https://www.redhat.com/sysadmin/suid-sgid-sticky-bit#:~:text=for%20the%20user.-,group%20%2B%20s%20(pecial),-Commonly%20noted%20as>) (`chmod g+s {{release_dir}}`) of the release directory. Doing so will make newly created files group ownership set to that of the directory group owner, in this case, `APP_GROUP`. This avoids running chown on the entire directory and all subdirectories, which will take a long time on slow machine.

3.  Flyer reads `WITH_SECURE_DEFAULT_PERMISSIONS` environment variable. If set to value '1', and `setfacl` command exists, before unzipping artifact to the release directory, set the ACL:

    ```sh
    setfacl -d -m g::r-- $release_dir
    ```

    Which **should** make the newly-created files have only read permission for groups (and execute if directory).

    If `setfacl` command does not exist, throw error saying that that the server admin should be ashamed of not installing it.

    > Note: this one is still not working properly. After unzip, group is still able to write to the files and directories.

4.  If `APP_USER` environment variable is set, do these:

    4.1. Check if `APP_USER` is either `root` or a member of the group `APP_GROUP` (if set). If true, unzip as that user. If not, throw error saying that `APP_ROOT` must be `root` or a member of the group `APP_GROUP`. This will make the unzipped files owned by `APP_USER` without chown-ing the entire directory and all subdirectories.

    Why the check?

    Somehow running `unzip` with a user that is either not `root` or not a member of the directory's SGID group will create the files with owner user:user, which is not what we want.

### Writable files and directories

What users care mostly are what files and directories needed to be writable. In order to achieve that, we have several options.

#### 1. chmod

Easy option, just set the permission bits (`chmod`) of either the user or group to be able to write to a file and/or directory. Add execute bit if it's a directory.

For each of the directory or file, run on it:

```sh
find $writable -type f -exec chmod g+w {} \;
find $writable -type d -exec chmod g+wx {} \;
```

#### 2. Default ACL

TBD

## Shared files and directories

This section describes how Flyer handles shared files and directories. This algorithm is taken from the deploy/recipe/shared.php from Deployer's common recipe with a few modifications.

1. Flyer accepts two config: `shared.dirs` and `shared.files`, arrays of strings containing dirs and files to be shared, respectively.

2. Validate shared dirs to prevent duplicates.

3. Flyer will see environment variable `SHARED_ROOT_DIR`, is a dir to put the shared dirs and files.

4. Flyer will see environment variable `APP_ID`, is a string to uniquely identify an application. Flyer will store the shared dirs and files under the directory `$SHARED_ROOT_DIR`/`$APP_ID`.

5. Copy all directories specified in `shared.dirs` to the shared dir path. Directory tree structure must be preserved. E.g. if the directory name is assets/uploads/json, then create the full path to the shared dir path, which will be `$SHARED_ROOT_DIR`/`$APP_ID`/assets/uploads/json.

6. Remove the directory in the release. In the example above the assets/uploads/json dir will be removed.

7. Create path to the shared dir in release dir if it does not exist since symlink will not create the full path. E.g. if the path is assets/uploads/json, then create the assets/uploads directory.

8. Create symlink from the release dir to the shared dir.

9. Copy all files specified in `shared.files` to the shared dir path. Directory tree structure must be preserved. If the files do not actually exist in the release, touch the file in the shared dir. Yes, it is possible that the file is not available at the release time but later needed when the application is running.

10. Remove file from release.

11. Ensure dir is available in release. E.g. if the file is assets/uploads/json/users.json, the directory assets/uploads/json needs to exist in release.

12. Create symlink from the release file to the file in shared dir.

### Err handling, edge cases, and stuffs that might happen kaboom-ly

- If the dir is already shared, but then developer specifies a shared file inside the same directory, what would happen?

  It is possible that developer would create a config as horrendous as this:

  ```yaml
  shared:
    dirs:
      - assets/uploads/json
    files:
      - assets/uploads/json/users.json
  ```

Horrendous. Might lead to unexpected kaboom if not handled correctly.

Following is just a theoretical of what would happen:

1. Flyer will copy `{{release_path}}/assets/uploads/json` to `{{shared_dir}}/{{app_id}}/assets/uploads/`

2. Removes `{{release_path}}/assets/uploads/json` in release dir
3. Create symlink from `{{release_path}}/assets/uploads/json` to `{{shared_dir}}/{{app_id}}/assets/uploads/json`

4. For the files, flyer will copy (notice carefully this part) the `{{release_path}}/assets/uploads/json/users.json` to `{{shared_dir}}/{{app_id}}/assets/uploads/json`. Unfortunately, `{{release_path}}/assets/uploads/json/` is already symlinked to the shared dir. Meaning we are copying files in the symlinked directory to the shared dir, which actually copies nothing.

5. Flyer will REMOVE (notice carefully this part) `{{release_path}}/assets/uploads/json/users.json`. Unfortunately, `{{release_path}}/assets/uploads/json/` is already symlinked to the shared dir. Meaning we are removing files in the symlinked directory, which actually removes the files in the shared dir itself, which in turn removes nothing.

Solution:

- Validate. Just make sure that this is not possible. Prevent devs from specifying files that is inside the directories that are shared. In this case, shared dirs are enough since, well, it shares the whole directory, without specifying individual files.

## Additional files

Flyer accepts `additional.files` and `ADDITIONAL_FILES_DIR` environment variable. For each file in `additional.files`, copy the corresponding file inside `ADDITIONAL_FILES_DIR` to the release directory.

Example flyer.yaml config:

```yaml
additional:
  files:
    - .env
    - a_file_from_external_source
```

This will copy from `$ADDITIONAL_FILES_DIR/.env` and `$ADDITIONAL_FILES_DI/a_file_from_external_source` to the release directory.

If the `ADDITIONAL_FILES_DIR` is not provided but the `additional.files` is specified, throw error.

## Remove files and/or directories

Before symlinking, remove files and/or directories configured in `remove` configuration.

## Template

Templates are just predefined command hooks. That's it. For each template-specific doc, refer to [templates docs directory](./templates/).

Templates can only do so much. In fact, I really want to make templates to be as minimal as possible. But it's not a good idea to expect that developers know how to configure nginx efficiently and securely, for example.

## Logging

## Low-level commands

There are [great open-source command line tools alternatives to common unix commands available](https://github.com/ibraheemdev/modern-unix). Many of them are much faster than the built-in commands. If the alternative commands exist, use them.

Here are the example of command alternatives:

| Unix command | Alternative                              |
| ------------ | ---------------------------------------- |
| find         | [fd](https://github.com/sharkdp/fd)      |
| cp           | [fcp](https://github.com/Svetlitski/fcp) |

## A few thoughts

### Use a separate config file to configure the internal behaviour

Since we have so many environment variables to configure Flyer's behaviour internally, it could get messy that we have to define those many variables. Maybe have a separate config to configure flyer internally?

## TODO: more efficient and parallel compression and decompression
