---
title: "Deployment strategies"
description: "Three ways to get your code on the server: clone the Git repository, upload a build produced by your CI, or combine both."
alias: [deployment-strategies]
---

Automate can fill a release in three ways. Choose the one that matches where your application is built.

| Strategy | The server needs | Best for |
|---|---|---|
| [Git](#git-deployment) | `git`, access to the repository, the build tools | Simple projects, servers that can build |
| [CI build and upload](#ci-build-and-upload) | Nothing but a shell | Heavy builds, servers without build tools, no repository access from the server |
| [Mixed](#mixed-deployment) | `git`, access to the repository | Build the assets in CI, keep the source from Git |

## Git deployment

Set `repository`. On each server, Automate clones the repository into the new release, checks out the requested reference and runs your hooks there. This is the simplest setup: the server does everything.

```yaml
repository: git@github.com:acme/shop.git

on_deploy:
    - composer install --no-dev --optimize-autoloader
    - yarn install --frozen-lockfile
    - yarn build

post_deploy:
    - cmd: php bin/console doctrine:migrations:migrate -n
      only: [web-01]
```

The server must be able to reach the repository, usually with a deploy key, and must have the build tools installed: `composer`, `node`, and so on. `automate check <platform>` verifies the repository access from each server.

> The clone uses `--recursive`, so Git submodules are checked out too.

## CI build and upload

Leave `repository` unset. Build the application in your CI pipeline, then upload the result with an `upload` action in `pre_deploy`. The server only receives files and runs the commands that must happen there, such as a database migration.

```yaml
# no "repository" key

pre_deploy:
    - upload: .
      exclude: [node_modules, .git, tests]

post_deploy:
    - cmd: php bin/console doctrine:migrations:migrate -n
      only: [web-01]
```

Each `upload` packs the local path in a `tar.gz` archive, sends it to the release folder of every server (or of the servers listed in `only`), and extracts it there. See [Upload actions](/docs/hooks/#upload) for the `exclude` syntax.

This strategy keeps the servers minimal and makes the deployed artifact identical on every server, since it is built once.

## Mixed deployment

Set `repository` **and** upload what the CI built. Automate clones the source on the server, then your `pre_deploy` upload adds the generated files, for example the compiled assets, on top of it.

```yaml
repository: git@github.com:acme/shop.git

pre_deploy:
    - upload: public/build

on_deploy:
    - composer install --no-dev --optimize-autoloader

post_deploy:
    - cmd: php bin/console doctrine:migrations:migrate -n
      only: [web-01]
```

This keeps the Git history and the PHP build on the server, while sparing it from installing Node and compiling assets.

## Deploying on the local machine

With `transport: local`, a server is the machine that runs Automate itself: no SSH is involved, commands run directly and uploads are plain file copies. The three strategies above work the same way. See [Local transport](/docs/configuration/#local-transport).
