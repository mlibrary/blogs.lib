# Automatic IP ban (Autoban)

Autoban module allows you to automatize IP ban by cron using module rules.

You create a rule that finds an IP address in the monitoring log table entries,
and then the module inserts the IP address into the banned IP table.
You must enable the Database Logging core module and at least one
IP Ban Provider by enabling the Autoban submodules.
Now this is the Autoban Core Ban and the [Autoban Advanced Ban](https://www.drupal.org/project/advban)
To enable these modules you must enable or install IP ban modules
(Ban, Advanced Ban).

- For a full description of the module visit:
  [Project Page](https://www.drupal.org/project/autoban).

- To submit bug reports and feature suggestions, or to track changes visit:
  [Issue Queue](https://www.drupal.org/project/issues/autoban).

Rules for ban IP consist of:
- Rule ID
- Type (watchdog type, like "page not found").
- Message pattern (rules seek in watchdog message as "LIKE %message_pattern%").
- URL referrer pattern.
- The threshold number of log entries.
- Window (Relative time window for log entries this rule should run against).
- User type (Anonymous, Authenticated,
  Anonymous strict, Authentificated strict).
  Strict options exclude another user type
  (e.g. Anonymous excludes Authentificated).
- IP ban provider.
- User type (anonymous, authenticated or any).

## Contents of this file

- Installation
- Requirements
- Configuration
- Troubleshooting
- Maintainers

## Installation

 - Install as you would normally install a contributed Drupal module.
   Visit: [Installing Drupal Modules](https://www.drupal.org/docs/extending-drupal/installing-modules).

 - Enable Autoban Core Ban Provider and/or Autoban Advanced Ban Provider
   submodules.


## Requirements

 - Enable Database Logging core module.

 - You have to enable at least one Autoban providers submodule
and then enable dependent modules.


## Configuration

- Configure at: [Your Site]/admin/config/people/autoban
  or: Administration > Configuration > People > Autoban

- In order to use this module you need the `"Administer autoban"`
  permission.

- Analyze watchdog table (/admin/reports/dblog).

- Go to the autoban admin page (/admin/config/people/autoban). Create and
  test rules or ban IP addresses for current rule.

- Cron will be ban IP using autoban rules.

## Troubleshooting

- A rule's type and message pattern looks in watchlog table.
You need put non translated value.

- The module using cron for automatic IP ban. If cron is disabled, you can
  click "Ban all" button at Show Ban IP for all rules tab.


## Maintainers

Current maintainers:
 - Serhii Klietsov - [goodboy](https://drupal.org/user/222910)
