---
title: "Commands"
description: "Reference of the Automate command line: deploy, check, exec and remove, with their arguments and options."
---

```text
automate <command> [arguments] [options]
```

| Command | Purpose |
|---|---|
| [`deploy`](#deploy) | Deploy a platform |
| [`check`](#check) | Verify the connection to the servers and the access to the repository |
| [`exec`](#exec) | Run a command on one server |
| [`remove`](#remove) | Remove the project from the servers of a platform |

## Common options

These options are accepted by every command:

| Option | Description |
|---|---|
| `-c, --config <file>` | Path of the configuration file. Default: `.automate.yml` in the current directory |
| `--var <name>=<value>` | Value of a [variable](/docs/variables/). Repeat the option for several variables |
| `-n, --no-interaction` | Never ask a question. Use it in CI; a password that cannot be resolved then fails instead of prompting |
| `-v, -vv, -vvv` | Increase the verbosity of the output |
| `-h, --help` | Help of the command |
| `-V, --version` | Version of Automate |

## deploy

```bash
automate deploy <platform> [<gitRef>] [--force] [-c <file>] [--var name=value]
```

Deploys the platform on all its servers. See the [Deployment lifecycle](/docs/deployment-lifecycle/).

| Argument or option | Description |
|---|---|
| `platform` | Name of the platform, as declared in `platforms` |
| `gitRef` | Branch, tag or commit to deploy. Default: the `default_branch` of the platform. Ignored when `repository` is not set |
| `-f, --force` | Deploy even if an `automate.lock` file exists on a server |

```bash
automate deploy production
automate deploy production v1.4.0
automate deploy staging feature/checkout -c deploy/.automate.yml
automate deploy review my-branch --var slug=my-branch
```

The command prints a summary (repository, platform, servers, version, variable names), then each step with the output of the commands prefixed by the server name. It exits with a non-zero status when the deployment fails.

## check

```bash
automate check <platform> [-c <file>] [--var name=value]
```

Connects to every server of the platform and runs `git ls-remote <repository>` from there. Use it after writing a configuration, or to diagnose a failing deployment: a wrong password, an unreachable host or a missing deploy key are reported without touching the server.

```bash
automate check production
```

## exec

```bash
automate exec <platform> <server> "<command>" [--current] [-c <file>] [--var name=value]
```

Runs a single command on one server of the platform and prints its output.

| Argument or option | Description |
|---|---|
| `platform` | Name of the platform |
| `server` | Name of the server, as declared under `servers` |
| `command` | The shell command, quoted |
| `--current` | Run the command in the `current` release, with the same [environment variables](/docs/hooks/#hook-environment) as the hooks |

Without `--current`, the command runs as is, from the home directory of the SSH user:

```bash
automate exec production web-01 "df -h /var/www"
automate exec production web-01 "ls -la /var/www/shop/releases"
```

With `--current`, it runs in the published release, which is convenient to replay a hook or to run a console command:

```bash
automate exec production web-01 "php bin/console cache:clear" --current
automate exec production web-01 'echo $AUTOMATE_RELEASE_ID' --current
```

## remove

```bash
automate remove <platform> [--yes] [--force] [--keep-shared] [-c <file>] [--var name=value]
```

Removes the project from every server of the platform, for example to clean up a review environment. The command shows what will be removed and asks for confirmation.

| Option | Description |
|---|---|
| `-y, --yes` | Do not ask for confirmation. Required when the command runs without a terminal |
| `-f, --force` | Ignore an existing `automate.lock` file |
| `--keep-shared` | Keep the `shared` folder. The project directory is then kept too |

On each server, Automate runs the [`on_remove` hooks](/docs/hooks/#removal-hook) in the `current` release, then deletes the `releases` folder, the `current` link and the `shared` folder, and finally the project `path` itself when nothing else is left in it.

```bash
automate remove review --var slug=my-branch --yes
```

- A `shared_path` located outside of the project path is never deleted, since other projects may use it.
- If an `on_remove` command fails, nothing is deleted on any server.
- The command can be run again safely: when the project path does not exist, there is nothing to remove.
