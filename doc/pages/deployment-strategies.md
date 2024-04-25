---
title: "Deployment strategies"
menu:
  main:
    weight: 30
---

# Deployment strategies Automate

Automate offers three deployment options, each tailored to specific needs:

### 1. Git Deployment

In this approach, you specify a repository in Automate's configuration.
Automate then clones this repository and uses commands to prepare the application directly on the remote server.
This method is the simplest to implement.

example : 

~~~~yaml
repository: git@github.com:symfony/symfony-demo.git
on_deploy:
    - composer install
    - yarn install
    - yarn build
post_deploy:
    - cmd: "php bin/console doctrine:migrations:migrate"
      only: [ dev-example-front-01]
~~~~

### 2. Continuous Integration (CI) + Automate

Utilize your continuous integration (CI) tools to perform build steps in your CI environment.
Then, Automate is used to push files directly to the server without using Git. 
This approach allows executing commands on the server at the end of the process, such as updating your database.

example :

~~~~yaml
# do not specify a repository
pre_deploy:
    - upload: .
      exclude: [node_modules]
post_deploy:
    - cmd: "php bin/console doctrine:migrations:migrate"
      only: [ dev-example-front-01]
~~~~

### 3. Mixed Deployment

In this setup, you use Git to download your sources and then send only specific files or folders from your CI. 
For example, you might only send the build of your assets. This approach combines the simplicity of Git with the flexibility of custom deployment.
~~~~yaml
repository: git@github.com:symfony/symfony-demo.git
pre_deploy:
    - upload: public/build
post_deploy:
    - cmd: "php bin/console doctrine:migrations:migrate"
      only: [ dev-example-front-01]
~~~~


