# Passwordless

### Important note on 8.x version

A development version of Passwordless for Drupal 8 is now available. Its basic
functionality is the same as 7.x-1.x, but a few things are still missing:

+ because [Email Change Confirmation](http://drupal.org/project/email_confirm) is not
yet available for Drupal 8, login will be impossible if a user enters an incorrect email
address
+ hook_install(): although the install function sets up the module correctly, it's still
an older version that does things in a weird way
+ hook_uninstall(): the uninstall function is still not putting things back where they
belong
+ migration from 7.x-1.x is still not available.

-----

## About Passwordless

This module replaces the regular Drupal login form with a modification of the password-
request form, to give the possibility to log in without using a password.

Every time a user needs to log in, only the email address is required. The login link
will be sent to the user's email address, and will expire in 24 hours if not used.

Passwordless disables the password-reset form, and changes the relevant settings at
admin/config/people/accounts. Uninstalling the module will restore everything to the way
it was before (including the settings). It's also compatible with other login-enhancing
modules, like [LoginToboggan](http://drupal.org/project/logintoboggan).

### Note

Passwordless disables the password fields in user-registration and user-profile forms,
which means that:

1. the system takes care of creating a password for new users
2. there's no longer a requirement for users to reenter their current password when they
enter a new email address in their profile.

Due to point number 2, Passwordless depends on
[Email Change Confirmation](http://drupal.org/project/email_confirm),
at least until [#85494] is resolved. Make sure you save settings at
admin/config/people/email_confirm, including the "From" address, for the module to work
properly.

### Settings

Passwordless settings can be found at admin/config/system/passwordless.

### Suggested modules

[Email Registration](http://drupal.org/project/email_registration) is recommended, to
allow users to register just with their email address, without providing a user name.

On Drupal 7, enabling [HTML5 Tools](http://drupal.org/project/html5_tools) is encouraged
for HTML5 sites, since it allows Passwordless to produce an HTML5-compliant `type="email"`
field in login forms. Without HTML5 Tools, a regular text field will be produced.

### Due credit

Passwordless follows the idea behind [NoPassword](https://nopassword.alexsmolen.com), but
is all based on Drupal's native functionality and code.