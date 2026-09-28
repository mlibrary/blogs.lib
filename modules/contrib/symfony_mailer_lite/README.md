# Drupal Symfony Mailer Lite

Drupal Symfony Mailer Lite integrates Drupal with the
[Symfony Mailer library](https://symfony.com/doc/current/mailer.html),
allowing for the sending of HTML-formatted emails and emails with
attachments. This module is a direct successor to the
[Swiftmailer module](https://www.drupal.org/project/swiftmailer),
which has been deprecated because the Swiftmailer library is no
longer maintained.

## Requirements and Installation

This module requires the [Mail System module](https://www.drupal.org/project/mailsystem).
After installation, you should go to Configuration > Mail System on your site and
assign Symfony Mailer Lite as either the default formatter and sender for emails,
or assign it to specific modules and/or keys to only send specific emails with it.

## Similar projects

[Drupal Symfony Mailer](https://www.drupal.org/project/symfony_mailer):
This project also allows the sending of HTML-formatted emails from Drupal
using the Symfony Mailer library. However, it does much more than that,
providing an alternative templating system and API for sending emails
from Drupal.

Drupal Symfony Mailer Lite sends emails using the same approach as the
Swiftmailer module. It’s designed to be a direct drop-in replacement
for Swiftmailer.

## Using custom sendmail commands
If you need to customize your sendmail command for the sendmail transport in
this module, you must declare the allowed sendmail command in your
settings.php or settings.local.php file. The specification should look
something like:

```php
$settings['mailer_sendmail_commands'] = [
  '/usr/sbin/sendmail -t',
];
```

## Upgrading from Swiftmailer Module

To upgrade from the Swiftmailer module to Drupal Symfony Mailer Lite, you should:

- Install this module using Composer.
- Enable this module.
- Go to Configuration > System > Mail System, and switch your default or
- module-level configurations for the formatters and senders from Swiftmailer
- to Drupal Symfony Mailer Lite and save your configuration.
- If you have customized the `swiftmailer.html.twig` template, you should
- rename or copy that template to `symfony-mailer-lite-email.html.twig`.
- If you have a custom `swiftmailer` library in your theme, you should rename
- or copy that library to one named `symfony_mailer_lite`.

### Acknowledgements

The code in this module is largely based on code from the Swiftmailer and
Drupal Symfony Mailer modules. Thank you to [AdamPS](https://www.drupal.org/u/adamps)
and other contributors to those modules, which have made this module possible.




