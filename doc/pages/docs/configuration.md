---
title: "Configuration"
description: "Reference of every option of the .automate.yml file: repository, platforms, servers, transports, shared items and hooks."
alias: [reference]
---

Automate reads its configuration from `.automate.yml` in the current directory. Use the `-c` option of any command to point to another file.

## Complete example

```yaml
repository: git@github.com:acme/shop.git

platforms:
    staging:
        default_branch: develop
        max_releases: 3
        servers:
            staging-01:
                host: staging.acme.io
                user: deploy
                password: "%staging_password%"
                path: /var/www/shop
                port: 2222
    production:
        default_branch: main
        max_releases: 5
        servers:
            web-01:
                host: web-01.acme.io
                user: deploy
                ssh_key: /home/me/.ssh/id_ed25519
                password: "%key_passphrase%"
                path: /var/www/shop
            web-02:
                host: web-02.acme.io
                user: deploy
                ssh_key: /home/me/.ssh/id_ed25519
                path: /var/www/shop
                shared_path: /mnt/nfs/shop-shared

shared_files:
    - .env.local

shared_folders:
    - var/uploads
    - var/log

pre_deploy:
    - upload: public/build

on_first_deploy:
    - 'cp .env.dist "$AUTOMATE_SHARED_PATH/.env.local"'

on_deploy:
    - composer install --no-dev --optimize-autoloader
    - php bin/console cache:warmup

post_deploy:
    - cmd: php bin/console doctrine:migrations:migrate -n
      only: [web-01]
    - sudo systemctl restart shop-worker

on_remove:
    - sudo systemctl stop shop-worker

post_rollback:
    - sudo systemctl restart shop-worker
```

## Root options

| Option | Type | Default | Description |
|---|---|---|---|
| `repository` | string | none | Git URL cloned on the servers. Omit it to fill the release with `upload` actions instead. See [Deployment strategies](/docs/deployment-strategies/) |
| `platforms` | map | **required** | The platforms, keyed by name. At least one is required |
| `shared_files` | list | `[]` | Files kept across releases, relative to the release root |
| `shared_folders` | list | `[]` | Folders kept across releases, relative to the release root |
| `pre_deploy` | list | `[]` | Actions run after the clone, before the shared items are set up |
| `on_first_deploy` | list | `[]` | Actions run only on servers that have no release yet |
| `on_deploy` | list | `[]` | Actions run before the release is published |
| `post_deploy` | list | `[]` | Actions run after the release is published |
| `on_remove` | list | `[]` | Commands run by `automate remove` before anything is deleted |
| `post_rollback` | list | `[]` | Commands run by `automate rollback` after `current` is switched back |

Hooks are described in detail on the [Hooks](/docs/hooks/) page.

### repository

```yaml
repository: git@github.com:acme/shop.git      # SSH
repository: https://github.com/acme/shop.git  # HTTPS, for a public repository
```

The clone runs **on the server**, so the server needs access to the repository, for example with a deploy key. The clone is recursive: submodules are included.

### shared_files and shared_folders

```yaml
shared_files:
    - .env.local
    - config/jwt/private.pem

shared_folders:
    - var/uploads
    - var/sessions
```

Paths are relative to the release root. On every deployment, each entry is replaced in the release by a symbolic link to the same path under `shared/`. The first deployment moves the file or folder from the release to `shared/` when it exists there; otherwise an empty file or folder is created. Intermediate directories are created as needed.

## Platforms

A platform is a named group of servers deployed together, such as `staging` or `production`.

| Option | Type | Default | Description |
|---|---|---|---|
| `default_branch` | string | **required** | The branch deployed when `deploy` is called without a reference |
| `max_releases` | integer | `5` | Number of releases kept on each server. Older ones are removed at the end of a deployment |
| `servers` | map | **required** | The servers, keyed by name. At least one is required |

```yaml
platforms:
    production:
        default_branch: main
        max_releases: 5
        servers:
            web-01: { host: web-01.acme.io, user: deploy, ssh_key: /home/me/.ssh/id_ed25519, path: /var/www/shop }
            web-02: { host: web-02.acme.io, user: deploy, ssh_key: /home/me/.ssh/id_ed25519, path: /var/www/shop }
```

Server names are used by the `only` option of actions, by the `exec` command and in the console output. Keep them short and unique within the platform.

## Servers

| Option | Type | Default | Description |
|---|---|---|---|
| `transport` | `ssh` or `local` | `ssh` | How the server is reached. See [Local transport](#local-transport) |
| `host` | string | required with `ssh` | Host name or IP address |
| `user` | string | required with `ssh` | SSH user |
| `port` | integer | `22` | SSH port |
| `password` | string | none | SSH password, or the passphrase of `ssh_key` when it is set. Usually a [variable](/docs/variables/) |
| `ssh_key` | string | none | Path to a private key file on the machine that runs Automate |
| `path` | string | **required** | Directory of the project on the server. It must exist and be writable by the user |
| `shared_path` | string | `<path>/shared` | Location of the shared files and folders, for example on a network mount |

### Authentication

Automate authenticates with a **password** or with a **private key**:

```yaml
servers:
    with-password:
        host: web-01.acme.io
        user: deploy
        password: "%prod_password%"
        path: /var/www/shop
    with-key:
        host: web-02.acme.io
        user: deploy
        ssh_key: /home/me/.ssh/id_ed25519
        password: "%key_passphrase%"   # only if the key is encrypted
        path: /var/www/shop
```

Automate reads the key file itself; the SSH agent and `~/.ssh/config` are not used. Give the absolute path of the key. In CI, write the key from a secret to a file before running Automate (see [CI integration](/docs/ci-integration/)).

A `password` written as `%name%` is a variable: Automate reads it from `--var name=...`, then from the `AUTOMATE__name` environment variable, and finally prompts for it in the terminal. See [Variables](/docs/variables/).

### Local transport

With `transport: local`, the server is the machine that runs Automate. No SSH connection is made: commands run directly through `/bin/sh`, and uploads are copied on the file system. Use it to deploy on the host itself, for example from a CI runner that sits on the target machine, or to try Automate without a remote server.

```yaml
platforms:
    production:
        default_branch: main
        servers:
            self:
                transport: local
                path: /var/www/shop
```

Commands run as the user who launches Automate and inherit its environment. The `host`, `user`, `password` and `ssh_key` options are rejected with the `local` transport, since Automate cannot switch user: run Automate as the user who must own the releases.

All the commands work with a local server: `deploy`, `rollback`, `check`, `remove` and `exec --current`. The local transport needs a POSIX system (Linux, macOS); it does not work when Automate itself runs on Windows.

## Validation

The configuration is validated when a command starts. Unknown options, a missing `path` or a platform without servers stop the command with a message naming the faulty key, for example:

```text
Unrecognized option "shared_file" under "automate". Available options are "on_deploy", "on_first_deploy", ...
```
