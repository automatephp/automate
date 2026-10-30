---
title: "CI integration"
description: "Run Automate from GitHub Actions, GitLab CI or any other runner: install a pinned version, pass secrets through the environment, and deploy on push or on tag."
---

Automate runs anywhere a shell runs. In a pipeline, three things change compared to your workstation:

1. **Install a pinned version** of the binary at the start of the job, so that a new release never changes your pipeline unexpectedly.
2. **Provide secrets through the environment.** There is no terminal to answer a prompt: map your CI secrets to `AUTOMATE__<name>` variables, or pass them with `--var`. See [Variables](/docs/variables/).
3. **Use `--no-interaction`** so that any missing value fails the job instead of hanging on a prompt.

Private keys stored as secrets must be written to a file, since `ssh_key` expects a path.

## GitHub Actions

This workflow deploys `main` to `staging` on every push, and tags to `production`.

```yaml
# .github/workflows/deploy.yml
name: Deploy

on:
    push:
        branches: [main]
        tags: ['*']

concurrency: deploy-${{ github.ref_name }}

jobs:
    deploy:
        runs-on: ubuntu-latest
        env:
            AUTOMATE_VERSION: 4.2.2
        steps:
            - uses: actions/checkout@v4

            - name: Install Automate
              run: |
                  curl -LSs -o /usr/local/bin/automate \
                      "https://github.com/automatephp/automate/releases/download/${AUTOMATE_VERSION}/automate-linux-amd64"
                  chmod +x /usr/local/bin/automate
                  automate --version

            - name: Write the SSH key
              run: |
                  install -m 600 /dev/null /tmp/deploy_key
                  echo "${{ secrets.DEPLOY_SSH_KEY }}" > /tmp/deploy_key

            - name: Deploy to staging
              if: github.ref == 'refs/heads/main'
              run: automate deploy staging --no-interaction

            - name: Deploy to production
              if: startsWith(github.ref, 'refs/tags/')
              run: automate deploy production "${{ github.ref_name }}" --no-interaction
              env:
                  AUTOMATE__prod_password: ${{ secrets.PROD_PASSWORD }}
```

With the matching configuration:

```yaml
platforms:
    staging:
        default_branch: main
        servers:
            staging-01:
                host: staging.acme.io
                user: deploy
                ssh_key: /tmp/deploy_key
                path: /var/www/shop
    production:
        default_branch: main
        servers:
            web-01:
                host: web-01.acme.io
                user: deploy
                password: "%prod_password%"
                path: /var/www/shop
```

> The `concurrency` key makes GitHub queue the deployments of the same reference. Automate also protects the servers with its own lock file, so two overlapping jobs cannot corrupt a release.

### Building in CI and uploading

When the servers should not build the application, build it in the job and upload the result. Leave `repository` unset, or keep it and upload only the generated files (see [Deployment strategies](/docs/deployment-strategies/)).

```yaml
            - uses: actions/setup-node@v4
              with:
                  node-version: 22
                  cache: npm
            - run: npm ci && npm run build

            - name: Deploy
              run: automate deploy production --no-interaction
```

```yaml
# .automate.yml
repository: git@github.com:acme/shop.git

pre_deploy:
    - upload: public/build
```

## GitLab CI

```yaml
# .gitlab-ci.yml
stages: [build, deploy]

variables:
    AUTOMATE_VERSION: "4.2.2"

.automate:
    image: ubuntu:24.04
    before_script:
        - apt-get update -qq && apt-get install -y -qq curl ca-certificates
        - curl -LSs -o /usr/local/bin/automate "https://github.com/automatephp/automate/releases/download/${AUTOMATE_VERSION}/automate-linux-amd64"
        - chmod +x /usr/local/bin/automate
        - install -m 600 /dev/null /tmp/deploy_key
        - echo "$DEPLOY_SSH_KEY" > /tmp/deploy_key

deploy:staging:
    extends: .automate
    stage: deploy
    script:
        - automate deploy staging --no-interaction
    rules:
        - if: $CI_COMMIT_BRANCH == "main"
    environment:
        name: staging
        url: https://staging.acme.io

deploy:production:
    extends: .automate
    stage: deploy
    script:
        - automate deploy production "$CI_COMMIT_TAG" --no-interaction
    rules:
        - if: $CI_COMMIT_TAG
    environment:
        name: production
        url: https://www.acme.io
    when: manual
```

Declare `DEPLOY_SSH_KEY` and the `AUTOMATE__*` passwords as **masked** CI/CD variables in the project settings. A variable named `AUTOMATE__prod_password` is picked up directly, with no extra mapping.

### Review environments

Combine [variables](/docs/variables/) with `automate remove` to create one environment per branch and tear it down when the branch is closed:

```yaml
# .automate.yml
platforms:
    review:
        default_branch: main
        servers:
            vm:
                host: review.acme.io
                user: deploy
                ssh_key: /tmp/deploy_key
                path: /srv/review/%slug%
```

```yaml
deploy:review:
    extends: .automate
    stage: deploy
    script:
        - automate deploy review "$CI_COMMIT_REF_NAME" --var slug="$CI_COMMIT_REF_SLUG" --no-interaction
    environment:
        name: review/$CI_COMMIT_REF_SLUG
        url: https://$CI_COMMIT_REF_SLUG.review.acme.io
        on_stop: stop:review
    rules:
        - if: $CI_MERGE_REQUEST_ID

stop:review:
    extends: .automate
    stage: deploy
    script:
        - automate remove review --var slug="$CI_COMMIT_REF_SLUG" --yes --no-interaction
    environment:
        name: review/$CI_COMMIT_REF_SLUG
        action: stop
    rules:
        - if: $CI_MERGE_REQUEST_ID
          when: manual
```

## Other runners

Any runner works the same way: download the binary (or the PHAR with PHP 8.4+), export the `AUTOMATE__*` variables, run `automate deploy`. On macOS runners, use the `automate-darwin-arm64` binary.
