---
title: "Get started"
menu:
  main:
    weight: 10
---
# Automate

Automate allows you to automate your deployments to remote Linux servers simply.
You can use Automate from your workstation or through an integration server like Github or Gitlab-ci.

### 1. Installation

You can download the latest version of Automate with the following command:    

~~~~bash
curl -sS https://www.automate-deployer.com/install | bash
~~~~

The command will verify your PHP settings and launch the download in the current directory.

Please note that this command is compatible with Linux and macOS systems.
If you are using Windows, you will need to download the .phar file manually from [github](https://github.com/automatephp/automate/releases).



### 2. Creating your configuration file

Foremost, you have to create a configuration file for Automate.
This file is usually located at the root of your project. The name of this file must be .automate.yml.

Here is an example file:

~~~~yaml
repository: git@github.com:symfony/symfony-demo.git
platforms:
    development:
        default_branch: dev
        max_releases: 3
        servers:
            dev-exemple-front-01:
                host: dev.exemple.com
                user: automate
                password: "%dev_password%"
                path: /home/wwwroot/
                port: 22
    production:
        default_branch: master
        max_releases: 3
        servers:
            prod-exemple-front-01:
                host: prod-1.exemple.com
                user: automate
                password: "%prod_password%"
                path: /home/wwwroot/
            prod-exemple-front-02:
                host: prod-2.exemple.com
                user: automate
                ssh_key: /path/to/key
                path: /home/wwwroot/
shared_files:
    - .env.local
shared_folders:
    - app/data
pre_deploy:
    - "php -v"
on_deploy:
    - "composer install"
post_deploy:
    - cmd: "php bin/console doctrine:schema:update --force"
      only: [ dev-exemple-front-01, prod-exemple-front-01]
~~~~


### 3. Launching a deployment

The following command allows you to launch the deployment on remote server(s)

~~~~bash
automate deploy development master
~~~~

~~~~bash
automate deploy ‹platform› [gitref] -c [path_of_config_file]
~~~~

* **platform**

The target platform name (e.g. development)

* **gitref (optional)**

The branch, the tag, or the commit to be deployed.    
By default Automate will use the « default_branch » in the configuration file

* **-c [path_of_config_file] (optional)**

By default, Automate will search for the file ```.automate.yml``` in the current directory. You can specify it with the option ```-c /path/to/.automate.yml```


### 4. Server Configuration

Automate will create the following directory structure to the remote server:

~~~~yaml
/your/project/path
   /releases
      /2024.04.22-1302.159
         .env.local --> /your/project/path/shared/.env.local
         /app
            /data --> /your/project/path/shared/app/data
 
   /shared_files
         .env.local #the real file is here
         /app
            /data #the real folder is here
 
   current -> /your/project/path/releases/2024.04.22-1302.159
~~~~

This is the schema of all your project’s architecture    
You have to target your domain name inside the folder ```/your/project/path/current/```.


### 5. Removing a deployment

The following command removes the project from the servers of a platform, for example to clean up a temporary environment:

~~~~bash
automate remove ‹platform› [--yes] [--force] [--keep-shared] [--var name=value] -c [path_of_config_file]
~~~~

For each server, Automate runs the `on_remove` hooks (see the reference) in the `current` release, then deletes the `releases` folder,
the `current` link and the `shared` folder, and finally the project path itself if nothing else is left in it.

* **--yes** : do not ask for confirmation. Required when the command runs without a terminal (CI).
* **--keep-shared** : keep the shared folder (uploads, `.env.local`, ...). The project path is then kept too.
* **--force** : ignore an existing `automate.lock` file.

A `shared_path` located outside of the project path is never deleted, as other projects may use it.
The command can be run again safely: when the project path does not exist, there is nothing to remove.

### 6. Rolling back a deployment

The following command points `current` back to a previous release:

~~~~bash
automate rollback ‹platform› [release] [--yes] [--force] [--var name=value] -c [path_of_config_file]
~~~~

Without **release**, each server goes back to the release that precedes the one `current` points to
(for example `2024.04.22-1302.159`; the available releases are those kept by `max_releases`).
Nothing is changed unless every server has a valid target, and all the servers must agree on it.
Once `current` is switched and the `post_rollback` hooks (see the reference) have run, the release that was left is moved to
`releases/failed`, where it can be inspected until the next deployment removes it: a second rollback therefore goes one release
further back. If a hook fails, that release stays in place.

* **--yes** : do not ask for confirmation. Required when the command runs without a terminal (CI).
* **--force** : ignore an existing `automate.lock` file.

### 7. Running a command on a server

~~~~bash
automate exec ‹platform› ‹server› "‹command›" [--current] [--var name=value] -c [path_of_config_file]
~~~~

By default the command runs as is on the server. With **--current**, it runs in the `current` release,
with the same environment variables as the hooks (see "Hook environment" in the reference):

~~~~bash
automate exec production prod-exemple-front-01 "php bin/console cache:clear" --current
~~~~
