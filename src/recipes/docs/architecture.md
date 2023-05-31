# Architecture

Currently it only supports artifact archived with zip format. Otherwise throw error.

## Flow

1. Run `preDeploy` command hooks.
2. Unzip the artifact to the release directory. Release directory will be `{{deploy_path}}`/release.`<sequence_number>`, where `<sequence_number>` is +1 increment of the largest `<sequence_number>` in the directory.
3. Run `postDeploy` command hooks.
4. Set required permission for the release directory and its files and/or subdirectories.
5. Run `preSymlink` command hooks.
6. Set symlink of `{{deploy_path}}`/current to point to the current release sequence number. Webserver should be configured to point to this directory.
7. Run `postSymlink` command hooks.
8. Delete previous releases if exist.

## Low-level commands

There are [great open-source command line tools alternatives to common unix commands available](https://github.com/ibraheemdev/modern-unix). Many of them are much faster than the built-in commands. If the alternative commands exist, use them.

Here are the example of command alternatives:

| Unix command | Alternative                                                                     |
| ------------ | ------------------------------------------------------------------------------- |
| find         | [fd](<[https://github.com/sharkdp/fd](https://github.com/sharkdp/fd)>)          |
| cp           | [fcp](<[https://github.com/Svetlitski/fcp](https://github.com/Svetlitski/fcp)>) |

## TODO: more efficient and parallel compression and decompression