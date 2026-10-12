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
    - "bash deploy.sh %slug%"
~~~~

Values are looked up in this order:

1. the `--var name=value` option of `deploy`, `exec` and `check` (repeatable): `automate deploy review my-branch --var slug=my-branch`
2. the environment variable `AUTOMATE__name`
3. for a `password` only: an interactive prompt (the whole value must be `%name%`; any other password is used as is)

Any other variable that cannot be resolved stops the command with an error naming the configuration key.
Only the requested platform is resolved, the variables of the other platforms are not needed.

Write `%%` to get a literal `%`. Beware of commands that contain a valid token, such as `date +%Y%m%d` (`%m%` is a token):
write `date +%%Y%%m%%d` instead. Quote the YAML value when it starts with `%`.

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

Automate supports three types of deployment hooks to execute actions at different stages of the deployment process:

1. **pre_deploy:** List of actions to be executed on remote servers after downloading sources and before setting up shared folders and files.

2. **on_deploy:** The list of commands to be launched on remote servers **before deployment**.

3. **post_deploy:** The list of commands to be launched on remote servers **after downloading sources** and **before** setting up shared folders and files.


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
        - '/^myfile.ext/'
        - '/^folder/subfolder/'
      only: ['dddv-exemple-front-01']
~~~~

#### Hook environment

Hook commands run with these environment variables:

| Variable | Value |
|---|---|
| `AUTOMATE_PLATFORM` | platform name |
| `AUTOMATE_SERVER` | name of the server the command runs on |
| `AUTOMATE_GIT_REF` | git reference given to `deploy`, or the platform `default_branch` |
| `AUTOMATE_RELEASE_ID` | release identifier, e.g. `2026.10.12-1715.123` |
| `AUTOMATE_RELEASE_PATH` | `<path>/releases/<release id>` |
| `AUTOMATE_RELEASES_PATH` | `<path>/releases` |
| `AUTOMATE_SHARED_PATH` | `shared_path`, or `<path>/shared` |
| `AUTOMATE_CURRENT_PATH` | `<path>/current` |
| `AUTOMATE__<name>` | each variable given with `--var` or resolved from the configuration, except those used as a `password` |
