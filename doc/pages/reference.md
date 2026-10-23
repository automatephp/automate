---
title: "Reference"
menu:
  main:
    weight: 20
---

# Reference

### 1. Repository

~~~~yaml
repository: git@github.com:symfony/symfony-demo.git
~~~~

or for a public repository :
~~~~yaml
repository: "https://github.com/symfony/demo"
~~~~

### 2. Platforms

List of platforms.

You can configure several platforms ; a project must have at least one platform.

~~~~yaml
platforms:
    production:
        default_branch: master # The default branch to be launched if no branch is specified during the deployement
        max_releases: 1        # The number of releases to be kept on remote servers.
        servers:
            prod-exemple-front-01:
                host: prod-1.exemple.com     # The domain name or the server's IP
                user: automate               # The SSH user to be used for the deployment
                password: "%prod_password%"  # Read more below in "The SSH password" section
                path: /home/wwwroot/         # The path on the remote server
                port: 22                     # The SSH port (default:22)    
            prod-exemple-front-02:
                host: prod-2.exemple.com
                user: automate
                ssh_key: /keys/private       # A file path to private key
                password: "%passphrase%"     # An optional passphrase
                path: /home/wwwroot/
~~~~

It’s possible to authenticate on the server with a password or with a private key. For the latter, you must define a path to the private key file and an optional passphrase (password) as the example above describes.

The `password` is usually a variable, see "Variables" below: if it cannot be resolved, Automate asks you for it in your terminal.

#### Deploying on the local machine

By default, servers are reached over SSH (`transport: ssh`). To deploy on the machine that runs Automate, use the `local` transport: no SSH connection is involved, commands are run directly and uploads are copied on the file system.

~~~~yaml
platforms:
    production:
        default_branch: master
        servers:
            self:
                transport: local
                path: /home/wwwroot/
~~~~

With the `local` transport, the commands run on the current machine as the user who launches Automate, and they inherit its environment. The `host`, `user`, `password` and `ssh_key` options are rejected: Automate cannot switch user, so declaring one would be misleading. Run Automate itself as the user who must own the releases.

Every command works with a local server: `deploy`, `check`, `remove` and `exec --current`.

The local transport runs the commands through `/bin/sh` (`export`, `&&`, `test`...), exactly as they are sent to an SSH server. It therefore requires a POSIX system (Linux, macOS): it does not work when Automate itself runs on Windows.

### 3. Variables

A `%variable_name%` token can be used in any string value of the configuration (`path`, `host`, hook commands, ...),
alone or inside a larger string. A name matches `[a-z][a-z0-9_]*`.

~~~~yaml
platforms:
    review:
        default_branch: master
        servers:
            vm:
                host: review.exemple.com
                user: automate
                password: "%review_password%"
                path: /srv/review/%slug%

post_deploy:
    - "bash deploy.sh"   # reads $AUTOMATE__slug and $AUTOMATE_RELEASE_PATH
~~~~

Values are looked up in this order:

1. the `--var name=value` option of `deploy`, `check`, `remove` and `exec` (repeatable): `automate deploy review my-branch --var slug=my-branch`
2. the environment variable `AUTOMATE__name`
3. for a `password` only: an interactive prompt (the whole value must be `%name%`, with any name such as `%PROD_PASSWORD%`; any other password is used as is)

Any other variable that cannot be resolved stops the command with an error naming the configuration key.
Only the requested platform is resolved, the variables of the other platforms are not needed.

`--var` does not accept an empty value.

Write `%%` to get a literal `%`. Beware of commands that contain a valid token, such as `date +%Y%m%d` (`%m%` is a token)
or `printf '%s%s'` (`%s%` is a token): write `date +%%Y%%m%%d` and `printf '%%s%%s'` instead. Quote the YAML value when it starts with `%`.

The deploy summary only lists the names of the variables, and their values are replaced by their `%name%` token in the
commands printed in the console. They are still exported to the hooks (see "Hook environment").

Values are inserted as is, without shell escaping: use trusted values or reference them in a command through the
environment (`"$AUTOMATE__slug"`, see "Deployment Hooks").

### 4. Shared_files

The list of files to be shared with releases.    
For example, some parameters files,…

~~~~yaml
shared_files:
    - .env.local
    - ...
~~~~

### 5. Shared_folders

The list of folders to be shared between releases.    
For example some uploaded pictures,…

~~~~yaml
shared_folders:
    - app/data
    - ...
~~~~

### 6. Deployment Hooks

Automate supports four types of deployment hooks to execute actions at different stages of the deployment process:

1. **pre_deploy:** List of actions to be executed on remote servers after downloading sources and before setting up shared folders and files.

2. **on_first_deploy:** List of actions executed **only on the servers where no release has been deployed yet**, after setting up shared folders and files and before `on_deploy`. Since the links to `shared` already exist, the hook can create files or folders there (a `.env.local`, a data folder, …) and `on_deploy` finds them in place.

3. **on_deploy:** List of actions to be executed on remote servers after setting up shared folders and files and **before the release is published** (the `current` symbolic link still points to the previous release).

4. **post_deploy:** List of actions to be executed on remote servers **after the release is published**.

The steps of a deployment run in this order:

1. clone the repository and checkout the requested reference
2. `pre_deploy`
3. setting up the shared folders and files
4. `on_first_deploy` (new servers only)
5. `on_deploy`
6. publish the release (the `current` symbolic link)
7. `post_deploy`
8. clear the old releases

#### First deployment

A server is in its first deployment when its `releases` folder contains no release. A failed release (moved to
`releases/failed`) does not count, so the next deployment is still a first one. The detection is made
for each server: on a platform with a new server and an already installed one, only the new server runs
`on_first_deploy`, and `only` restricts the hook further.

~~~~yaml
on_first_deploy:
    - 'cp .env.dist "$AUTOMATE_SHARED_PATH/.env.local"'
~~~~

Commands can also check the `AUTOMATE_FIRST_DEPLOY` variable (see below), for example from `post_deploy`:
`if [ "$AUTOMATE_FIRST_DEPLOY" = 1 ]; then bin/console doctrine:fixtures:load -n; fi`.


#### Types of Actions

There are two types of actions that can be specified within the hooks:

1. **command:** Allows executing a command on the remote server. For example, `composer install`.

2. **upload:** Allows sending local files or directories to remote servers. You can specify files or directories to exclude.

In both cases, it is possible to restrict the servers on which the action should be executed using the `only` option. For example:

~~~~yaml
pre_deploy:
    - "php -v" ## All server

on_deploy:
    - cmd: "composer install"
      only: ['dddv-exemple-front-01']

    - upload: "build/assets"
      exclude:
        - 'myfile.ext'
        - '/^folder\/subfolder/'
      only: ['dddv-exemple-front-01']
~~~~

Each `exclude` entry is matched against the path of every file, **relative to the
uploaded directory**:

- a plain string excludes every path containing it — `node_modules` drops the folder
  wherever it appears ;
- a regular expression, delimiters included, is matched against that same relative
  path — `'/^folder\/subfolder/'` only drops `folder/subfolder` at the root of the
  upload. Mind the escaped delimiter: `'/^folder/subfolder/'` is not a valid
  regular expression.

#### Hook environment

Hook commands run with these environment variables:

| Variable | Value |
|---|---|
| `AUTOMATE_PHASE` | `deploy` for the deployment hooks, `remove` for `on_remove`, `exec` for `automate exec --current` |
| `AUTOMATE_PLATFORM` | platform name |
| `AUTOMATE_SERVER` | name of the server the command runs on |
| `AUTOMATE_GIT_REF` | git reference given to `deploy`, or the platform `default_branch` (deployment hooks only) |
| `AUTOMATE_RELEASE_ID` | release identifier, e.g. `2026.10.12-1715.123` |
| `AUTOMATE_RELEASE_PATH` | `<path>/releases/<release id>` |
| `AUTOMATE_RELEASES_PATH` | `<path>/releases` |
| `AUTOMATE_SHARED_PATH` | `shared_path`, or `<path>/shared` |
| `AUTOMATE_CURRENT_PATH` | `<path>/current` |
| `AUTOMATE_FIRST_DEPLOY` | `1` if no release has been deployed yet on this server, `0` otherwise (always `0` outside of a deployment) |
| `AUTOMATE__<name>` | each variable given with `--var` or resolved from the configuration, except those used as a `password` |

During `on_remove` and `exec --current`, the `AUTOMATE_RELEASE_*` variables describe the release that `current` points to.

### 7. Removal Hook

**on_remove:** the list of commands launched on remote servers by `automate remove`, in the `current` release and
**before** anything is deleted. Use it to stop what the project started (containers, cron jobs, workers, ...).
It accepts commands and the `only` option, but no `upload` action.

~~~~yaml
on_remove:
    - "docker compose down --volumes"
    - cmd: "crontab -r"
      only: ['dddv-exemple-front-01']
~~~~

If a command fails, nothing is deleted. Hooks are skipped on a server that has no `current` release.
