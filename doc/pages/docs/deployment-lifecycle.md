---
title: "Deployment lifecycle"
description: "What Automate does on your servers, step by step: locking, releases, hooks, shared items, publishing, cleanup and failure handling."
---

This page describes what `automate deploy` does on each server, in order. Knowing the sequence helps you choose the right [hook](/docs/hooks/) for each task.

## Directory layout

Under the `path` configured for a server, Automate maintains three entries:

```text
/var/www/shop
├── automate.lock                      # only while a deployment runs
├── current -> releases/2026.10.23-1412.587
├── releases
│   ├── 2026.10.21-0930.112
│   ├── 2026.10.22-1805.341
│   ├── 2026.10.23-1412.587
│   └── failed                         # the last failed release, if any
└── shared
    ├── .env.local
    └── var/uploads
```

| Entry | Role |
|---|---|
| `releases/` | One folder per deployment, named after its release id: `YYYY.MM.DD-HHMM.NNN`, where `NNN` is a random number |
| `current` | A symbolic link to the published release. Point your web server, workers or cron jobs at it |
| `shared/` | Files and folders kept across releases (see `shared_files` and `shared_folders`). Can be moved elsewhere with `shared_path` |
| `releases/failed` | The release of the last failed deployment, kept for inspection and replaced at the next deployment |
| `automate.lock` | Created at the start of a deployment and removed at its end, successful or not |

## Steps of a deployment

Automate opens a session with every server of the platform, then runs the following steps. Each step completes on every server before the next one starts, and a failure at any step stops the deployment on all servers.

1. **Connect and detect first deployments.** A server with no release in its `releases` folder is in its first deployment, which enables the `on_first_deploy` hooks for that server.
2. **Lock.** `automate.lock` is created in `path`. If it already exists, the deployment stops with `A deployment is already in progress`, unless `--force` is given. Any previous `releases/failed` folder is removed.
3. **Create the release folder** `releases/<release id>`.
4. **Clone the repository**, when `repository` is set: `git clone <repository> -q --recursive -b <default_branch> .`, followed by a `git checkout` of the branch, tag or commit given to `deploy`. A reference that matches a tag is checked out as `tags/<ref>`. Without `repository`, the release folder stays empty: your `pre_deploy` hooks are expected to [upload](/docs/deployment-strategies/) the application.
5. **`pre_deploy` hooks** run in the release folder.
6. **Shared items.** Each entry of `shared_files` and `shared_folders` is replaced in the release by a symbolic link to `shared/`. On the first deployment, a file or folder present in the release is moved to `shared/` first; otherwise an empty file or folder is created there.
7. **`on_first_deploy` hooks** run, only on the servers in their first deployment.
8. **`on_deploy` hooks** run in the release folder. This is where you build the application: install dependencies, compile assets, warm up caches.
9. **Publish.** `current` is pointed to the new release with `ln -sfn`. From this moment, the new release is live.
10. **`post_deploy` hooks** run in the new release: migrations, cache clearing, restarting workers.
11. **Clean up.** Releases beyond `max_releases` are removed, oldest first. `releases/failed` and any folder that does not look like a release id are ignored.
12. **Unlock.** `automate.lock` is removed.

> **Which hook should I use?** Anything that must be ready before the release goes live belongs in `on_deploy`. Anything that acts on the live application, such as database migrations or process restarts, belongs in `post_deploy`. See [Hooks](/docs/hooks/) for details and the environment variables available to your commands.

## Multiple servers

Commands run **in parallel** on all the servers of the platform, each one in its own SSH session. The output is prefixed with the server name. If a command fails on one server, the deployment stops once the command completed on the others; the release is never published anywhere, because publishing comes after all the build hooks.

Use the `only` option of an action to run it on a subset of servers, typically to run a database migration once:

```yaml
post_deploy:
    - cmd: php bin/console doctrine:migrations:migrate -n
      only: [web-01]
```

## When a deployment fails

- **Before publishing** (steps 2 to 8): the release is moved to `releases/failed`, `current` still points to the previous release, and the lock is released. Nothing changed for your users. Inspect `releases/failed`, fix the problem and deploy again.
- **After publishing** (`post_deploy`): the new release stays live, since it was already switched. The deployment is reported as failed, the lock is released, and the release is kept. Run the failing command again with [`automate exec --current`](/docs/commands/#exec), or deploy a fix.

## Rolling back

Automate does not have a rollback command: publishing is a symbolic link, so rolling back is pointing `current` to a previous release. The releases kept by `max_releases` are intact, including their shared links.

```bash
automate exec production web-01 "ls /var/www/shop/releases"
automate exec production web-01 "ln -sfn /var/www/shop/releases/2026.10.22-1805.341 /var/www/shop/current"
```

Remember to run the same command on every server of the platform, and to revert any `post_deploy` effect that is not backward compatible, such as a database migration.

## Locking

The lock is per server, in the `path` directory. A lock left behind by a crashed process or a lost connection makes every later deployment fail with `A deployment is already in progress`. When you are sure no deployment is running, deploy with `--force`, which ignores the existing lock, or remove the file:

```bash
automate exec production web-01 "rm /var/www/shop/automate.lock"
```
