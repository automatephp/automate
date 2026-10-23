---
title: "Recipes"
description: "Ready-to-adapt configurations for Symfony, Laravel, static sites, Docker Compose, multi-server platforms and more."
---

Starting points for common setups. Every example is a complete `.automate.yml`; adapt the hosts, paths and credentials.

## Serving the current release with nginx or Caddy

Point the document root at `<path>/current/public`, and make the web server **resolve the symbolic link** before passing the script path to PHP-FPM. PHP OPcache caches scripts by path: with the resolved path, each release gets fresh entries and there is nothing to reload after a deployment. Without it, OPcache keeps serving the previous release until PHP-FPM restarts.

With nginx, use `$realpath_root` instead of `$document_root`:

```text
server {
    server_name www.acme.io;
    root /var/www/shop/current/public;

    location / {
        try_files $uri /index.php$is_args$args;
    }

    location ~ ^/index\.php(/|$) {
        fastcgi_pass unix:/run/php/php8.3-fpm.sock;
        fastcgi_split_path_info ^(.+\.php)(/.*)$;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        fastcgi_param DOCUMENT_ROOT $realpath_root;
        internal;
    }
}
```

Caddy resolves the link by default in `php_fastcgi` (`resolve_root_symlink` is enabled unless you turn it off):

```text
www.acme.io {
    root * /var/www/shop/current/public
    php_fastcgi unix//run/php/php8.3-fpm.sock
    file_server
}
```

## Symfony

```yaml
repository: git@github.com:acme/shop.git

platforms:
    production:
        default_branch: main
        servers:
            web-01:
                host: web-01.acme.io
                user: deploy
                ssh_key: /home/me/.ssh/id_ed25519
                path: /var/www/shop

shared_files:
    - .env.local

shared_folders:
    - var/log
    - public/uploads

on_first_deploy:
    - 'cp .env "$AUTOMATE_SHARED_PATH/.env.local"'

on_deploy:
    - composer install --no-dev --optimize-autoloader --no-interaction
    - php bin/console cache:clear --no-warmup
    - php bin/console cache:warmup
    - php bin/console asset-map:compile

post_deploy:
    - cmd: php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
      only: [web-01]
```

Point the web server at `/var/www/shop/current/public` as shown [above](#serving-the-current-release-with-nginx-or-caddy). `var/cache` stays inside each release, which is what Symfony expects: a release always starts with a cache built for its own code.

## Laravel

```yaml
repository: git@github.com:acme/app.git

platforms:
    production:
        default_branch: main
        servers:
            web-01:
                host: web-01.acme.io
                user: deploy
                ssh_key: /home/me/.ssh/id_ed25519
                path: /var/www/app

shared_files:
    - .env

shared_folders:
    - storage

on_first_deploy:
    - 'cp .env.example "$AUTOMATE_SHARED_PATH/.env"'
    - php artisan key:generate --force

on_deploy:
    - composer install --no-dev --optimize-autoloader --no-interaction
    - php artisan config:cache
    - php artisan route:cache
    - php artisan view:cache

post_deploy:
    - cmd: php artisan migrate --force
      only: [web-01]
    - php artisan queue:restart
```

The web server serves `/var/www/app/current/public`. The `storage` folder is shared, so logs, sessions and uploads survive deployments. `php artisan storage:link` can be added to `on_deploy` when you use the public disk.

## Static site built in CI

The servers only receive files. Build in the pipeline, then upload the output:

```yaml
platforms:
    production:
        default_branch: main
        servers:
            web-01:
                host: web-01.acme.io
                user: deploy
                password: "%prod_password%"
                path: /var/www/site

pre_deploy:
    - upload: dist
```

Serve `/var/www/site/current/dist`. Since there is no `repository`, nothing needs to be installed on the server, not even `git`.

## Assets built in CI, PHP built on the server

The mixed strategy: the server clones and installs the PHP dependencies, the CI uploads the compiled assets so that the server does not need Node.

```yaml
repository: git@github.com:acme/shop.git

platforms:
    production:
        default_branch: main
        servers:
            web-01: { host: web-01.acme.io, user: deploy, ssh_key: /tmp/deploy_key, path: /var/www/shop }

shared_files: [.env.local]
shared_folders: [var/log, public/uploads]

pre_deploy:
    - upload: public/build

on_deploy:
    - composer install --no-dev --optimize-autoloader --no-interaction

post_deploy:
    - php bin/console doctrine:migrations:migrate -n
```

## Docker Compose on the host

Deploy the sources, then rebuild and restart the containers from the published release. The `local` transport runs everything on the machine itself, which suits a runner installed on the host.

```yaml
repository: git@github.com:acme/stack.git

platforms:
    production:
        default_branch: main
        max_releases: 2
        servers:
            host:
                transport: local
                path: /srv/stack

shared_files:
    - .env

post_deploy:
    - docker compose -f "$AUTOMATE_CURRENT_PATH/compose.yaml" up -d --build --remove-orphans

on_remove:
    - docker compose down --volumes
```

`on_remove` stops the stack before `automate remove` deletes the files.

## Several servers behind a load balancer

Run the build everywhere, the migration once, and restart each server:

```yaml
repository: git@github.com:acme/shop.git

platforms:
    production:
        default_branch: main
        servers:
            web-01: { host: web-01.acme.io, user: deploy, ssh_key: /home/me/.ssh/id_ed25519, path: /var/www/shop }
            web-02: { host: web-02.acme.io, user: deploy, ssh_key: /home/me/.ssh/id_ed25519, path: /var/www/shop }
            web-03: { host: web-03.acme.io, user: deploy, ssh_key: /home/me/.ssh/id_ed25519, path: /var/www/shop }

shared_files: [.env.local]
shared_folders: [var/log]

on_deploy:
    - composer install --no-dev --optimize-autoloader --no-interaction
    - php bin/console cache:warmup

post_deploy:
    - cmd: php bin/console doctrine:migrations:migrate -n
      only: web-01
```

Uploads shared by all servers, such as user files, belong on a network mount declared with `shared_path`:

```yaml
            web-01:
                host: web-01.acme.io
                user: deploy
                ssh_key: /home/me/.ssh/id_ed25519
                path: /var/www/shop
                shared_path: /mnt/nfs/shop/shared
```

## Staging and production in one file

```yaml
repository: git@github.com:acme/shop.git

platforms:
    staging:
        default_branch: develop
        max_releases: 2
        servers:
            staging-01: { host: staging.acme.io, user: deploy, ssh_key: /home/me/.ssh/id_ed25519, path: /var/www/shop }
    production:
        default_branch: main
        max_releases: 5
        servers:
            web-01: { host: web-01.acme.io, user: deploy, ssh_key: /home/me/.ssh/id_ed25519, path: /var/www/shop }

shared_files: [.env.local]

on_deploy:
    - composer install --no-dev --optimize-autoloader --no-interaction

post_deploy:
    - php bin/console doctrine:migrations:migrate -n
    - cmd: php bin/console doctrine:fixtures:load -n --group=demo
      only: staging-01
```

```bash
automate deploy staging              # deploys "develop"
automate deploy production v2.3.0    # deploys a tag
```
