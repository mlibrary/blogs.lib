# Label Help

The Label Help module provides a way of adding help text to Drupal form fields
which appears below the form label but above the form field itself.

For a full description of the module, visit the
[project page](https://www.drupal.org/project/label_help).

Submit bug reports and feature suggestions, or track changes in the
[issue queue](https://www.drupal.org/project/issues/label_help).

## Requirements

This module requires no modules outside of Drupal core.


## Installation

1. Install and enable as you would normally install a contributed Drupal module.
   For further information, see [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-drupal-modules).

2. In Field UI, look for the “Label help message“ option when editing a field.
   Text entered here appears between the label and the form field.

3. This module doesn’t change the existing “Help text” field, which appears
   under the form field. You may use both or just one — whatever suits your needs.


## Troubleshooting

1.  Label Help placement logic 'use case' debugging.

    If a given field or widget type is not behaving as expected, enable debug
    mode in settings.php with the following line:

        $settings['label_help_debug'] = TRUE;

    Then reload the form page to see debug output identifying the field widget
    type and use case matched by Label Help placement logic. If no use case is
    detected you'll see a warning message.

2.  Form API dump debugging

    To troubleshoot Form API structures for a given form field enable the Form
    API element dump mode in settings.php with the following line:

        $settings['label_help_debug_dump'] = TRUE;

    This debug mode leverages the Symfony Variable Dumper `dump()` to provide
    a drillable screen dump of the Form API element array. However, because
    this dump runs inside the form_alter, it can result in incomplete page
    load. This is expected.


## Using Label Help to create form fields programmatically

The Label Help field defines a theme option named 'description at top' which
can be used to insert label help text in form fields that are defined
programmatically. The following function would therefore define a form with
label help text at the top of field 'example':

```php
function my_module_form($form, &$form_state) {
  $form['example'] = [
    '#type' => 'textfield',
    '#title' => t('Example'),
    '#label_help' => t('Label help text for the example field.'),
  ];
  return $form;
}
```

## Modifying form fields using hook_form_alter()

Drupal's hook_form_alter() and hook_FORM_ID_alter() functions may be used
in a custom module or theme to add Label Help text to existing form fields.
The following example adds help text to the Article content type's Title field.

```php
function my_module_form_node_article_alter(&$form, &$form_state, $form_id) {
  $form['title']['#label_help'] = t('Label help message for the Title field.');
}
```


## Contributing

Local development is done via DDEV and the `ddev-drupal-contrib` add-on.

1.  Install Docker and DDEV.

    Follow the documentation at https://ddev.readthedocs.io/

2.  Clone the module and install it inside a clean Drupal instance.

    Note: `ddev poser` is a wrapper for `ddev composer` that ensures the module
    gets installed correctly inside a "non standard" folder structure.

        git clone git@git.drupal.org:project/label_help.git && cd label_help
        ddev start
        ddev poser
        ddev symlink-project
        ddev drush site:install
        ddev drush user:login

3.  Install and configure the module.

    See the [Installation][#installation] section above.

4.  Enable debug mode.

    See [Troubleshooting](#troubleshooting) section above.

5.  Ensure code quality before submitting MRs.

        ddev cspell
        ddev phpcs
        ddev phpstan

    Automatically fix php syntax errors with `phpcbf`:

        ddev phpcbf

    This avoids wait time for the Drupal GitLabCI build to test code quality.

6.  If working on admin theme integrations, ensure CSS stylelint passes:

        ddev exec "cd web/core && corepack enable"
        ddev exec "cd web/core && yarn install"
        ddev stylelint
