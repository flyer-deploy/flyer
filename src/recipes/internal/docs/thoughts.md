# Thoughts

## The `current` path

Using `current` path that points to the latest release version can greatly reduce deployment time since there is only going to be one copy operation. However, this solution to the old deployer which performed at least four copy operations is not _**free**_. Consider this possibility:

1. Copy artifact as usual.

2. If using `web.*` template, it will create config files for the webserver and reload the webserver so it picks up the new config.

   However (and this is the problem), we can only either reload the config _after_ or _before_ symlink-ing the new release to `current`. There is a possibility that the app might break for a few seconds if the app really really has to be incorporated with the webserver config update, i.e. the new code update should not be visible to users unless the config is also updated.

The old deployer did this:

1. Copy the current `production` directory to `production_backup` directory

2. Update webserver config to point to the `production_backup` directory as the web root. Other webserver config updates are also done here

3. Destroy the `production` directory's contents and copy the new code there

4. Update webserver config to point back to the `production` directory as the web root

### Solution

We can do the same thing (the `production_backup` part), minus the so many copy operations. Here's what we will do:

1. After creating the new release in the releases dir, create `current_backup` that points to the previous release

2. In the `post_release` command hook, template **must** configure the webserver (or anything that it configures, this is template code's responsibility) to point the web root to the `current_backup`

3. After symlinking the current release to `current`, the `post_symlink` command hook will be run. Again, template **must** configure the software it manages to point the web root back to the `current`

4. Remove `current_backup` symlink file after running the `post_symlink` command hook

Apparently, it is still **FREE** BAHAHAHAHA LETSGO.

### So many tight coupling?

I don't know. Let's think about it later.

TODO: are we creating so many tight couplings?
