CONTRIBUTING
------------

You may set up your local environment with [DDEV] using the [DDEV Drupal Contrib] plugin and [DDEV Selenium Standalone Chrome].

1. [Install DDEV] with a [Docker provider].
2. Clone this project's repository from Drupal's GitLab.

        git clone git@git.drupal.org:project/calendar.git
        cd calendar

3. Start the environment.

        ddev start

4. Install composer dependencies.

        ddev poser

    Note: `ddev poser` is shorthand for `ddev composer` to add in Drupal core dependencies
    without needing to modify the root composer.json. Find out more in DDEV Drupal Contrib
    [commands].

5. Install Drupal and this module.

        ddev install

6. Visit site in browser.

        ddev describe

    Or, login as user 1:

        ddev drush uli

7. Push work to Merge Requests (MRs) opened via this project's [issue queue].


CHANGING DRUPAL CORE VERSION
----------------------------

Use the `ddev core-version` command to update Drupal core and related dependencies. For example:

    ddev core-version ^11

Alternatively, manually set the core version in .ddev/.env.web:

    DRUPAL_CORE=^11

Then run:

    ddev restart
    ddev poser


UPDATING DEPENDENCIES
---------------------

This project depends on 3rd party PHP libraries. It also specifies suggested "dev dependencies"
for contribution on local development environments. Occasionally, DDEV and DDEV Drupal Contrib
must be updated as well.

1.  Create an issue, MR, and checkout the MR branch.
2.  Update DDEV and DDEV Drupal Contrib itself.

    Read https://ddev.readthedocs.io/en/stable/users/install/ddev-upgrade/

        ddev config --update
        ddev get ddev/ddev-selenium-standalone-chrome
        ddev get ddev/ddev-drupal-contrib
        ddev restart
        ddev poser

3.  Review and update PHP dependencies defined in composer.json

        ddev composer outdated --direct

3.  Test clean install, commit, and push.


[DDEV]: https://www.ddev.com/
[DDEV Drupal Contrib]: https://github.com/ddev/ddev-drupal-contrib
[Install DDEV]: https://ddev.readthedocs.io/en/stable/
[Docker provider]: https://ddev.readthedocs.io/en/stable/users/install/docker-installation/
[issue queue]: https://www.drupal.org/project/issues/calendar
[commands]: https://github.com/ddev/ddev-drupal-contrib#commands
[DDEV Selenium Standalone Chrome]: https://github.com/ddev/ddev-selenium-standalone-chrome
[Drupal's testing documentation]: https://www.drupal.org/docs/automated-testing
