---
title: "Troubleshooting"
description: "Common errors and how to fix them: locks, authentication, variables, uploads, failed releases, and how to inspect what Automate runs."
---

## Reading the output

Automate prints every command it runs on the servers, and the output of each one prefixed with the server name in brackets. When a step fails, the error message of the failing server is shown and the deployment stops. Add `-v` for more details, including the stack trace of an internal error.

To reproduce a command exactly as a hook runs it, use [`automate exec --current`](/docs/commands/#exec): it runs in the published release with the same environment variables.

## Errors

### A deployment, a removal or a rollback is already in progress

An `automate.lock` file exists in the project `path` of a server. Either another command is really running, or a previous one crashed before releasing the lock (lost connection, killed process).

When you are sure nothing is running, deploy with `--force` or remove the lock:

```bash
automate deploy production --force
# or
automate exec production web-01 "rm /var/www/shop/automate.lock"
```

### Invalid user or password

The SSH authentication failed. Check the `user`, the `password` or the `ssh_key` of the server, and that the key is accepted by the server (`~/.ssh/authorized_keys`). When the key is encrypted, its passphrase goes in `password`.

Note that Automate reads the key file itself: the SSH agent and `~/.ssh/config` are not consulted. Give the absolute path of the key.

### File "..." not found

The `ssh_key` path does not exist on the machine that runs Automate. In CI, make sure the step that writes the key from a secret ran before the deployment, and that both use the same path.

### Unable to resolve the variable "%name%"

A `%name%` token is used in the configuration but has no value. Provide it with `--var name=value` or with the `AUTOMATE__name` environment variable. If the `%` characters are not a variable, for example in `date +%Y%m%d`, double them: `date +%%Y%%m%%d`. See [Variables](/docs/variables/).

### Missing configuration file

No `.automate.yml` in the current directory. Run Automate from the root of the project, or give the file with `-c path/to/.automate.yml`.

### Missing platform ... or Missing server ...

The name given to the command does not match a key of `platforms` or `servers`. Names are case sensitive.

### Unrecognized option "..." under "automate"

A typo in a configuration key. The message lists the valid options at that level. See the [Configuration reference](/docs/configuration/).

### The "host" option is required with the "ssh" transport

A server of the default `ssh` transport is missing `host` or `user`. Conversely, `host`, `user`, `password` and `ssh_key` are rejected with `transport: local`.

### "..." does not exist

The path of an `upload` action does not exist on the machine that runs Automate. Paths are relative to the current directory. In CI, make sure the build step that produces the folder ran first.

### Invalid "exclude" pattern

An `exclude` entry looks like a regular expression but is not valid. The usual cause is an unescaped delimiter: write `'/^folder\/subfolder/'`, not `'/^folder/subfolder/'`. See [Upload actions](/docs/hooks/#upload).

### git clone or git checkout fails

The server cannot reach the repository, or the reference does not exist. Run `automate check <platform>` to verify the access from each server, and make sure the branch, tag or commit given to `deploy` exists in the repository. Private repositories need a deploy key on the server.

### A hook fails with "The command failed with exit code ..."

The command exited with a non-zero status without printing anything. Run it with `automate exec <platform> <server> "<command>" --current` to see its full output, or add `set -x` at the beginning of your script.

## Inspecting a failed release

When a deployment fails before publishing, its release is moved to `releases/failed` on each server. The release left by `automate rollback` goes there too. The folder is kept until the next deployment, so you can look at it:

```bash
automate exec production web-01 "ls -la /var/www/shop/releases/failed"
automate exec production web-01 "cat /var/www/shop/releases/failed/var/log/prod.log"
```

## The application still shows the previous version

- The web server must serve `<path>/current/...`, not a specific release folder. Check the document root.
- PHP OPcache caches scripts by their resolved path. When the web server passes `/var/www/shop/current/public/index.php` to PHP-FPM, OPcache keeps serving the file it resolved at the first request, which may belong to the previous release. Configure the web server to resolve the `current` link itself, so that every release has its own paths: see [Serving the current release](/docs/recipes/#serving-the-current-release-with-nginx-or-caddy).
- Long-running processes (queue workers, Node servers) keep the old release until they restart: restart them in `post_deploy`.

## Rolling back

Run [`automate rollback <platform>`](/docs/commands/#rollback) to point `current` back to the previous release on every server, or give it a release id. See [Deployment lifecycle](/docs/deployment-lifecycle/#rolling-back).

## Windows

The install script and the static binaries target Linux and macOS. On Windows, run the PHAR with PHP 8.4+. The `local` transport is not available there; deploying to remote servers over SSH works.

## Getting help

- Browse the [existing issues](https://github.com/automatephp/automate/issues), or open a new one with your configuration (without secrets) and the output of the failing command.
- Pull requests are welcome: see the [repository](https://github.com/automatephp/automate).
