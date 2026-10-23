---
title: "Quick start"
description: "Deploy a project to a server in five minutes: prepare the server, write a minimal configuration, check the connection and run the first deployment."
---

This guide deploys a Git repository to a single server. It takes about five minutes once [Automate is installed](/docs/installation/).

## 1. Prepare the server

Automate needs an SSH user on the server, a directory this user can write to, and access to your Git repository from the server.

```bash
# on the server
sudo adduser deploy
sudo mkdir -p /var/www/shop
sudo chown deploy:deploy /var/www/shop
```

Then make sure that `git` is installed and that the `deploy` user can clone your repository, for example by adding its public key as a **deploy key** on GitHub or GitLab:

```bash
# on the server, as the deploy user
ssh-keygen -t ed25519 -C "deploy@web-01"
cat ~/.ssh/id_ed25519.pub   # add this key to the repository
git ls-remote git@github.com:acme/shop.git
```

## 2. Write the configuration

Create a `.automate.yml` file at the root of your project:

```yaml
repository: git@github.com:acme/shop.git

platforms:
    production:
        default_branch: main
        servers:
            web-01:
                host: web-01.acme.io
                user: deploy
                password: "%prod_password%"
                path: /var/www/shop

shared_files:
    - .env.local

shared_folders:
    - var/uploads

on_deploy:
    - composer install --no-dev --optimize-autoloader

post_deploy:
    - php bin/console doctrine:migrations:migrate -n
```

A few things to note:

- `production` is a **platform**: a named group of servers deployed together. `main` is deployed when you do not specify another branch, tag or commit.
- `"%prod_password%"` is a [variable](/docs/variables/). Automate reads it from the `AUTOMATE__prod_password` environment variable, and prompts for it in your terminal when it is not set. To authenticate with a key instead, replace `password` with `ssh_key: /home/me/.ssh/id_ed25519`.
- `shared_files` and `shared_folders` are kept between releases: the first deployment moves them to the `shared` folder, and every release links to them.
- `on_deploy` runs in the new release **before** it is published, `post_deploy` **after**. The [hooks](/docs/hooks/) page lists all of them.

## 3. Check the connection

```bash
automate check production
```

Automate connects to every server of the platform and verifies that the repository is reachable from there with `git ls-remote`. Fix any error before deploying.

## 4. Deploy

```bash
automate deploy production
```

The first deployment creates the `releases`, `shared` and `current` entries under `/var/www/shop`. On the first deployment the hooks of `on_first_deploy` also run, which is a good place to create `.env.local` in the shared folder.

Point your web server at the `current` link, for example `/var/www/shop/current/public` for a Symfony or Laravel application. That path never changes: every deployment only updates the link.

## 5. Deploy something else

Deploy a branch, a tag or a commit by giving it after the platform name:

```bash
automate deploy production v1.4.0
automate deploy production hotfix/payment
automate deploy production 3f2a9c1
```

## Where to go next

- [Deployment lifecycle](/docs/deployment-lifecycle/): what happens on the server, step by step.
- [Deployment strategies](/docs/deployment-strategies/): clone on the server, upload a CI build, or both.
- [CI integration](/docs/ci-integration/): run Automate from GitHub Actions or GitLab CI.
- [Configuration reference](/docs/configuration/): every option of `.automate.yml`.
