---
title: "Reference"
menu:
  main:
    weight: 20
---

# Reference

### 1. Repository

~~~~yaml
repository: git@github.com:symfony/symfony-demo.git
~~~~

or for a public repository :
~~~~yaml
repository: "https://github.com/symfony/demo"
~~~~

### 2. Platforms

List of platforms.

You can configure several platforms ; a project must have at least one platform.

~~~~yaml
platforms:
    production:
        default_branch: master # The default branch to be launched if no branch is specified during the deployement
        max_releases: 1        # The number of releases to be kept on remote servers.
        servers:
            prod-exemple-front-01:
                host: prod-1.exemple.com     # The domain name or the server's IP
                user: automate               # The SSH user to be used for the deployment
                password: "%prod_password%"  # Read more below in "The SSH password" section
                path: /home/wwwroot/         # The path on the remote server
                port: 22                     # The SSH port (default:22)    
            prod-exemple-front-02:
                host: prod-2.exemple.com
                user: automate
                ssh_key: /keys/private       # A file path to private key
                password: "%passphrase%"     # An optional passphrase
                path: /home/wwwroot/
~~~~

It’s possible to authenticate on the server with a password or with a private key. For the latter, you must define a path to the private key file and an optional passphrase (password) as the example above describes.

You can use a variable with the notation "%variable_name%"    
If one variable is detected Automate will search for the value in an environment variable « AUTOMATE__variable_name ».    
If the environment variable doesn’t exist, Automate will ask to you to provide your password upon each deployment through in your terminal.

### 3. Shared_files

The list of files to be shared with releases.    
For example, some parameters files,…

~~~~yaml
shared_files:
    - .env.local
    - ...
~~~~

### 4. Shared_folders

The list of folders to be shared between releases.    
For example some uploaded pictures,…

~~~~yaml
shared_folders:
    - app/data
    - ...
~~~~

### 5. Deployment Hooks

Automate supports three types of deployment hooks to execute actions at different stages of the deployment process:

1. **pre_deploy:** List of actions to be executed on remote servers after downloading sources and before setting up shared folders and files.

2. **on_deploy:** The list of commands to be launched on remote servers **before deployment**.

3. **post_deploy:** The list of commands to be launched on remote servers **after downloading sources** and **before** setting up shared folders and files.


#### Types of Actions

There are two types of actions that can be specified within the hooks:

1. **command:** Allows executing a command on the remote server. For example, `composer install`.

2. **upload:** Allows sending local files or directories to remote servers. You can specify files or directories to exclude.

In both cases, it is possible to restrict the servers on which the action should be executed using the `only` option. For example:

~~~~yaml
pre_deploy:
    - "php -v" ## All server

on_deploy:
    - cmd: "composer install"
      only: ['dddv-exemple-front-01']

    - upload: "build/assets"
      exclude:
        - '/^myfile.ext/'
        - '/^folder/subfolder/'
      only: ['dddv-exemple-front-01']
~~~~
