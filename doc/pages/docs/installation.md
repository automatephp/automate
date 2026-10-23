---
title: "Installation"
description: "Install the Automate binary with the install script, download it manually, or run the PHAR with PHP."
---

Automate is distributed as a **static binary** that embeds PHP: nothing needs to be installed beforehand. A **PHAR** archive is also published for platforms without a prebuilt binary.

## Install script

```bash
curl -LSs https://www.automate-deployer.com/install | bash
```

The script:

1. checks that `curl` or `wget` is available;
2. detects your operating system and architecture;
3. downloads the latest release from [GitHub Releases](https://github.com/automatephp/automate/releases);
4. installs it as `/usr/local/bin/automate` when run as root, or as `~/.automate/bin/automate` otherwise.

When installed in your home directory, add it to your `PATH` or move it to a system directory:

```bash
sudo mv ~/.automate/bin/automate /usr/local/bin/automate
```

Then check the installation:

```bash
automate --version
```

> **Upgrading:** run the install script again. It always downloads the latest release and replaces the binary.

## Supported platforms

| Platform | Binary | Notes |
|---|---|---|
| Linux x86_64 | `automate-linux-amd64` | Install script or manual download |
| macOS Apple Silicon | `automate-darwin-arm64` | Install script or manual download |
| macOS Intel | no binary | Use the PHAR |
| Linux ARM64 | no binary | Use the PHAR |
| Windows | no binary | Use the PHAR. The `local` transport is not available on Windows |

## Manual download

Every release publishes the binaries on GitHub. Download the one for your platform, make it executable and move it to your `PATH`:

```bash
sudo curl -LSs -o /usr/local/bin/automate https://github.com/automatephp/automate/releases/latest/download/automate-linux-amd64
sudo chmod +x /usr/local/bin/automate
```

Give the output file a path that does not already exist as a directory: `-o automate` fails with `Failure writing output to destination` when run next to a folder named `automate`, such as a clone of the repository.

To pin a specific version, replace `latest/download` with `download/<version>`:

```bash
sudo curl -LSs -o /usr/local/bin/automate https://github.com/automatephp/automate/releases/download/4.2.2/automate-linux-amd64
```

Pinning is recommended in CI pipelines so that a new release never changes a build unexpectedly.

## PHAR

The PHAR requires **PHP 8.3 or later**. The `openssl` and `gmp` (or `bcmath`) extensions are recommended: they make the SSH key exchange much faster.

```bash
curl -LSs -o automate.phar https://github.com/automatephp/automate/releases/latest/download/automate.phar
php automate.phar --version
```

You can also make it executable and move it to your `PATH` as `automate`.

## Shell completion

Automate is built with the Symfony Console component and supports its completion script. It needs a release newer than 4.2.2: older binaries do not embed the completion scripts and report `supported shells: ""`.

```bash
automate completion bash > /etc/bash_completion.d/automate   # bash
automate completion zsh > "${fpath[1]}/_automate"             # zsh
```
