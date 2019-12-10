Skeleton library
===

Create a new blank library.

The library will use the test tools as specified in [Jasny Code Quality](https://github.com/jasny/php-code-quality).

Upon installation, a new repository on [GitHub](https://github.com) is created and the project is enabled for
[Travis](https://travis.org) and [Scrutinizer](https://scrutinizer.com).

Environment
---

The following environment variables are used

- `PACKAGIST_VENDOR` (required) - Vendor name for package
- `GITHUB_REPO` - Github repository name, defaults to `vendor/library`

You name, email address, and homepage are read from git global config. Set this as

    git config --global --add user.name <YOUR NAME>
    git config --global --add user.email <YOUR EMAIL>
    git config --global --add user.homepage <YOUR HOMEPAGE>

For Scrutinizer, also configure the following env vars

- `SCRUTINIZER_ACCESS_TOKEN` - Required for using scrutinizer
- `SCRUTINIZER_ORGANIZATION` - Omit to use your personal account
- `SCRUTINIZER_GLOBAL_CONFIG` - Use a global configuration in addition to `scrutinizer.yml`.

Usage
---

    composer create-project jasny/library -s dev --remove-vcs <LIBRARY NAME>

