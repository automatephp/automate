---
title: "Variables"
description: "Keep passwords and environment-specific values out of the configuration file with %name% variables, resolved from the command line, the environment or an interactive prompt."
---

A `%name%` token can be used in **any string value** of the configuration: a `password`, a `host`, a `path`, a hook command. It is replaced by the value of the variable when the command starts. A name matches `[a-z][a-z0-9_]*`.

```yaml
platforms:
    review:
        default_branch: main
        servers:
            vm:
                host: review.acme.io
                user: deploy
                password: "%review_password%"
                path: /srv/review/%slug%

post_deploy:
    - bash deploy/notify.sh   # reads $AUTOMATE__slug and $AUTOMATE_RELEASE_PATH
```

## Resolution order

Values are looked up in this order:

1. the `--var name=value` option of `deploy`, `check`, `remove` and `exec`, which can be repeated:
   `automate deploy review my-branch --var slug=my-branch`
2. the environment variable `AUTOMATE__name`, for example `AUTOMATE__review_password`;
3. for a `password` only, an **interactive prompt** in the terminal.

A variable that cannot be resolved stops the command with an error naming the configuration key and the ways to provide the value. Only the platform being deployed is resolved: the variables of the other platforms are not needed.

> **Passwords:** the prompt is only offered when the whole `password` value is a token, such as `"%prod_password%"`. Any other password is used as is. A name used as a password is accepted in any case, such as `%PROD_PASSWORD%`, for compatibility with older configurations.

## In CI pipelines

Pipelines have no terminal, so passwords must come from the environment or from `--var`. Map your CI secrets to `AUTOMATE__*` variables:

```yaml
# GitHub Actions
env:
    AUTOMATE__prod_password: ${{ secrets.PROD_PASSWORD }}
```

See [CI integration](/docs/ci-integration/) for complete examples.

## Masking

The deploy summary lists the **names** of the variables, never their values. In the commands printed to the console, each value is replaced by its `%name%` token. The values are still exported to the hooks as `AUTOMATE__name` environment variables, except the ones used as a `password`.

## Escaping and quoting

- Write `%%` to get a literal `%`. Commands that contain a valid token are the usual trap: in `date +%Y%m%d`, `%m%` is a token, and in `printf '%s%s'`, `%s%` is a token. Write `date +%%Y%%m%%d` and `printf '%%s%%s'`.
- Quote a YAML value that starts with `%`: `password: "%prod_password%"`.
- Values are inserted **as is**, without shell escaping. Use trusted values, or reference the variable through the environment in a command: `"$AUTOMATE__slug"`.
- `--var` does not accept an empty value.
