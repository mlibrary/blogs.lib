CONTENTS OF THIS FILE
---------------------

 * Introduction
 * Requirements
 * Installation
 * Configuration
 * Maintainers


INTRODUCTION
------------

This is a utility Drupal extension that allows you to configure exposed filters
on block display views to be displaye and placed as standard blocks. Although
this is possible by default for page display views, this functionality is not
possible for block display views without this module.

This extension requires no configuration. Just install it and it works; you
should see an "exposed filter in block" option under the "advanced" pane of
the Views UI for all block display views.


REQUIREMENTS
------------

* [Views](https://www.drupal.org/project/views)


INSTALLATION
------------

#### Drupal 7
* Install as you would normally install a contributed Drupal module. Visit
  https://www.drupal.org/node/895232/ for further information.

#### Drupal 8/9
* Install using composer
```bash
composer require drupal/views_block_filter_block
```
* Install as you would normally install a contributed Drupal module. Visit
  https://www.drupal.org/node/1897420 for further information.


CONFIGURATION
------------

1. Enable the module using drush or by going to `admin/modules`.
2. Create a new view or open an existing one.
3. Go to *advanced > Exposed form in block* and set it to `Yes`
4. Go to `admin/structure/block` and place the block as you normally would.


MAINTAINERS
-----------

Current Maintainers:
* Yogesh Pawar (yogeshmpawar)  - https://www.drupal.org/u/yogeshmpawar
* Eric Peterson (iamEAP) - https://www.drupal.org/u/iameap
* Sumit Madan (sumitmadan) - https://www.drupal.org/u/sumitmadan


This project has been sponsored by:
* [QED42](https://www.drupal.org/qed42) Drupal 8 version
