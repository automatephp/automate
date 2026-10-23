---
title: "Hooks"
description: "Run commands and upload files at each stage of a deployment: pre_deploy, on_first_deploy, on_deploy, post_deploy, on_remove and post_rollback, with the environment variables they receive."
---

Hooks are lists of **actions** run on the servers at a given stage of a deployment. Every hook is optional.

| Hook | When | Working directory |
|---|---|---|
| `pre_deploy` | After the repository is cloned, before the shared items are linked | The new release |
| `on_first_deploy` | After the shared items, only on servers with no release yet | The new release |
| `on_deploy` | After the shared items, **before** the release is published | The new release |
| `post_deploy` | **After** the release is published | The new release, now `current` |
| `on_remove` | By `automate remove`, before anything is deleted | The `current` release |
| `post_rollback` | By `automate rollback`, **after** `current` is switched back | The restored release, now `current` |

The full sequence is described in the [Deployment lifecycle](/docs/deployment-lifecycle/).

## Actions

An action is a **command** or an **upload**. A plain string is a command:

```yaml
on_deploy:
    - composer install --no-dev
    - cmd: yarn build
    - upload: public/build
```

### Commands

| Option | Description |
|---|---|
| `cmd` | The shell command. It runs through `sh` with `cd <release> && <cmd>`, so `&&`, pipes, redirections and environment variables work as in a shell script |
| `only` | A server name or a list of server names the command runs on. Default: all the servers of the platform |

```yaml
post_deploy:
    - cmd: php bin/console doctrine:migrations:migrate -n
      only: web-01
    - cmd: php bin/console messenger:stop-workers
      only: [web-01, web-02]
```

The command fails, and so does the deployment, when it exits with a non-zero status. Its output is printed under the server name.

> **Tip:** long scripts are easier to maintain in a file committed with the project: `- bash deploy/after-publish.sh`. The script receives the [hook environment](#hook-environment).

### Upload

An `upload` action sends a local file or directory to the release folder of the servers. The path is read on the machine that runs Automate, relative to the current directory, and recreated at the same relative path in the release.

| Option | Description |
|---|---|
| `upload` | The local file or directory to send |
| `exclude` | Patterns of paths to leave out, relative to the uploaded directory |
| `only` | A server name or a list of server names. Default: all the servers |

```yaml
pre_deploy:
    - upload: .
      exclude:
          - node_modules
          - .git
          - '/^tests\//'
    - upload: public/build
      only: [web-01]
```

Automate packs the files in a `tar.gz` archive, sends it to each server and extracts it in the release folder. Each `exclude` entry is matched against the path of every file, relative to the uploaded directory:

- a **plain string** excludes every path that contains it: `node_modules` drops that folder wherever it appears;
- a **regular expression**, with its delimiters, is matched against the same relative path: `'/^tests\//'` only drops the `tests` folder at the root of the upload. Escape the delimiter inside the expression: `'/^folder/subfolder/'` is not a valid pattern and is rejected.

`upload` is not allowed in `on_remove` and `post_rollback`.

## First deployment

A server is in its **first deployment** when its `releases` folder contains no release. A release moved to `releases/failed` does not count, so the next attempt is still a first deployment. The detection is made per server: on a platform with one new server and one already deployed, only the new one runs `on_first_deploy`, and `only` can restrict it further.

Since the links to `shared/` already exist at that point, the hook can create the files that later deployments will find in place:

```yaml
on_first_deploy:
    - 'cp .env.dist "$AUTOMATE_SHARED_PATH/.env.local"'
    - php bin/console doctrine:database:create
```

Any command can also test the `AUTOMATE_FIRST_DEPLOY` variable, for example in `post_deploy`:

```yaml
post_deploy:
    - 'if [ "$AUTOMATE_FIRST_DEPLOY" = 1 ]; then php bin/console doctrine:fixtures:load -n; fi'
```

## Removal hook

`on_remove` lists the commands run by [`automate remove`](/docs/commands/#remove) in the `current` release, **before** anything is deleted. Use it to stop what the project started: containers, cron jobs, workers.

```yaml
on_remove:
    - docker compose down --volumes
    - cmd: crontab -r
      only: [web-01]
```

If a command fails, nothing is deleted on any server. The hook is skipped on a server that has no `current` release.

## Rollback hook

`post_rollback` lists the commands run by [`automate rollback`](/docs/commands/#rollback) in the restored release, **after** `current` has been switched back to it and before the release that was left is moved to `releases/failed`. Use it to reload or restart what runs the code: PHP-FPM, workers, a Node server.

```yaml
post_rollback:
    - sudo systemctl reload php8.3-fpm
    - cmd: php bin/console messenger:stop-workers
      only: [web-01]
```

If a command fails, the rollback is reported as failed: `current` already points to the restored release, and the release that was left stays in `releases/`.

## Hook environment

Every command runs with these environment variables, so scripts can locate the release and the shared folder without hard-coding paths:

| Variable | Value |
|---|---|
| `AUTOMATE_PHASE` | `deploy` for the deployment hooks, `remove` for `on_remove`, `rollback` for `post_rollback`, `exec` for `automate exec --current` |
| `AUTOMATE_PLATFORM` | The platform name |
| `AUTOMATE_SERVER` | The name of the server the command runs on |
| `AUTOMATE_GIT_REF` | The reference given to `deploy`, or the `default_branch` of the platform (deployment hooks only) |
| `AUTOMATE_RELEASE_ID` | The release id, for example `2026.10.23-1412.587` |
| `AUTOMATE_RELEASE_PATH` | `<path>/releases/<release id>` |
| `AUTOMATE_RELEASES_PATH` | `<path>/releases` |
| `AUTOMATE_SHARED_PATH` | `shared_path`, or `<path>/shared` |
| `AUTOMATE_CURRENT_PATH` | `<path>/current` |
| `AUTOMATE_FIRST_DEPLOY` | `1` on a server with no release yet, `0` otherwise. Always `0` outside of a deployment |
| `AUTOMATE__<name>` | Each [variable](/docs/variables/) given with `--var` or resolved from the configuration, except the ones used as a `password` |

During `on_remove`, `post_rollback` and `exec --current`, the `AUTOMATE_RELEASE_*` variables describe the release that `current` points to.

```yaml
post_deploy:
    - 'echo "Deployed $AUTOMATE_GIT_REF to $AUTOMATE_SERVER" >> "$AUTOMATE_SHARED_PATH/deployments.log"'
```
