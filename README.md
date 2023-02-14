[![CircleCI](https://circleci.com/gh/refactory-id/phabricator.svg?style=svg&circle-token=1532f411cf76a561328a62a7c9477c7d345111db)](https://app.circleci.com/pipelines/github/refactory-id/phabricator)

# SEV-2 Instance Skeleton

We use Phabricator as our primary dashboard. Image being build on each merge, and will be pushed to our private registry. To provision new instance in Kubernetes use [SEV-2 Helm Chart](https://github.com/refactory-id/helm-sev2-instance)

## Update from old build

Copy the new structure of [config from the manifest folder](https://github.com/refactory-id/phabricator/blob/consumer/manifest/php7/local.json.dist) and update the values accordingly. Also, you need to delete the old database(s), and run 
```
bin/storage upgrade
```

## PR Requirements

When you made any changes and want to merge it to `consumer`, your PR needs to pass both linter and unit-test. You can do the following from your local before you ask for a review 
```
arc lint
arc unit
``` 
Once they passed,  ask review to @toopay or @mul14 or @mprambadi

## Verbatim Vendor

**Phabricator** is a collection of web applications which help software companies build better software.

Phabricator includes applications for:

  - reviewing and auditing source code
  - hosting and browsing repositories;
  - tracking bugs;
  - managing projects;
  - conversing with team members;
  - assembling a party to venture forth;
  - writing stuff down and reading it later;
  - hiding stuff from coworkers; and
  - also some other things.

You can learn more about the project (and find links to documentation and resources) at [Phabricator.org](http://phabricator.org)

Phabricator is developed and maintained by [Phacility](http://phacility.com).

---------

**SUPPORT RESOURCES**

For resources on filing bugs, requesting features, reporting security issues, and getting other kinds of support, see [Support Resources](https://secure.phabricator.com/book/phabricator/article/support/).

**NO PULL REQUESTS!**

We do not accept pull requests through GitHub. If you would like to contribute code, please read our [Contributor's Guide](https://secure.phabricator.com/book/phabcontrib/article/contributing_code/).

**LICENSE**

Phabricator is released under the Apache 2.0 license except as otherwise noted.
