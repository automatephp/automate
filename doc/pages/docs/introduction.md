---
title: "Introduction"
description: "Automate is a command line tool that deploys a Git repository, or a build produced by your CI, to one or more Linux servers over SSH."
alias: [get-started]
---

Automate ships your application to Linux servers with a single command. You describe your platforms, servers and deployment steps in a `.automate.yml` file at the root of your project, then run `automate deploy production` from your workstation or from your CI pipeline.

Every deployment creates a new, isolated **release** on each server. When all your build steps succeed, a `current` symbolic link is switched to the new release. Your web server always points to `current`, so visitors never see a half-deployed application, and a failed deployment never goes live.

## Features

- **Atomic, zero-downtime releases.** Each deployment lives in its own timestamped folder; publishing is a single symlink switch, and so is rolling back with `automate rollback`.
- **Multi-server platforms.** Deploy to one server or to a whole fleet. Hooks run in parallel on every server, and any command can be restricted to a subset with `only`.
- **Shared files and folders.** Keep `.env.local`, uploads or caches across releases: Automate links them into every new release.
- **Git, CI build, or both.** Clone the repository on the server, upload the artifacts built by your CI, or mix the two. See [Deployment strategies](/docs/deployment-strategies/).
- **Hooks at every stage.** Run commands before the release is prepared, on the first deployment of a server, before publishing, after publishing, after a rollback, and when a project is removed. See [Hooks](/docs/hooks/).
- **Secrets from the environment.** Passwords and other values are resolved from `--var` options or `AUTOMATE__*` environment variables, and masked in the output. See [Variables](/docs/variables/).
- **Safe by default.** A lock file prevents two deployments from overlapping, failed releases are set aside, and old releases are pruned automatically.
- **Any stack.** PHP, Node, Ruby, Python, static sites: if it runs on a Linux server, Automate can deploy it. Automate itself is a single static binary with no runtime dependency.

## How it works

```text
$ automate deploy production

 Start deployment
 ----------------
 ------------ -----------------------------------
  Repository   git@github.com:acme/shop.git
  Platform     production
  Servers      web-01 (web-01.acme.io)
               web-02 (web-02.acme.io)
  Version      main
 ------------ -----------------------------------

 Prepare Release
 ---------------
 git clone git@github.com:acme/shop.git -q --recursive -b main .

 Setting up shared items
 -----------------------
 [web-01] /var/www/shop/releases/2026.10.23-1412.587/.env.local --> /var/www/shop/shared/.env.local
 [web-02] /var/www/shop/releases/2026.10.23-1412.587/.env.local --> /var/www/shop/shared/.env.local

 On deploy
 ---------
 composer install --no-dev --optimize-autoloader

 Publish new release
 -------------------
 [web-01] /var/www/shop/current --> /var/www/shop/releases/2026.10.23-1412.587
 [web-02] /var/www/shop/current --> /var/www/shop/releases/2026.10.23-1412.587

 Post deploy
 -----------
 php bin/console doctrine:migrations:migrate -n

 Clear olds releases
 -------------------

 [OK] All is OK
```

On each server, Automate maintains this layout under the configured `path`:

```text
/var/www/shop
├── current -> releases/2026.10.23-1412.587
├── releases
│   ├── 2026.10.22-1805.341
│   └── 2026.10.23-1412.587
└── shared
    ├── .env.local
    └── var/uploads
```

The [Deployment lifecycle](/docs/deployment-lifecycle/) page details every step.

## Requirements

**On your machine** (or your CI runner):

- Linux x86_64 or macOS Apple Silicon for the prebuilt binary, or PHP 8.4+ for the PHAR. See [Installation](/docs/installation/).
- `tar`, when you use `upload` actions.

**On each server:**

- SSH access with a password or a private key, for the default `ssh` transport.
- A POSIX shell and the usual tools: `sh`, `tar`, `find`, `ln`, `mkdir`.
- `git`, when you deploy from a repository. The server must be able to reach the repository, for example with a deploy key.
- Whatever your hooks need: `composer`, `php`, `node`, and so on.

## Next steps

1. [Install Automate](/docs/installation/)
2. [Follow the quick start](/docs/quick-start/) to deploy your first project
3. [Browse the configuration reference](/docs/configuration/)
