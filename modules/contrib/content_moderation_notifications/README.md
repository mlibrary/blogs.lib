# Content Moderation Notifications

The content moderation notifications module allows notifications to be sent to
All users of a particular role, or to the content's author when a piece of
Content is transitioned from one state to another via core's content moderation
Module.

- For a full description of the module visit the [project page](https://www.drupal.org/project/content_moderation_notifications).

- To submit bug reports and feature suggestions, or to track changes visit the [issue queue](https://www.drupal.org/project/issues/content_moderation_notifications).


## Contents of this File

- Requirements
- Installation
- Configuration
- Using twig templates
- Maintainers


## Requirements

This module requires no modules outside Drupal core.


## Installation

Install the content moderation notifications module as you would normally
Install a contributed drupal module. Visit
[Installing Drupal Modules](https://www.drupal.org/node/1897420) for further
information.


## Configuration

1. Navigate to Administration > Extend and enable the module and its dependencies.
1. Navigate to Administration > Configuration > Workflow > Content Moderation Notifications to add notifications.


## Using Twig Templates

You may use any valid twig syntax within fields that indicate support.

This will allow you to include additional fields, entity references, conditional
logic and twig functions in these fields.

*Note:* The twig tweak module will provide many additional twig functions to your
template, such as drupal_token() which can be used to display any available tokens.

**Examples:**

- Subject: (no twig used)
```
  New content needs review
```
- Subject: (using twig variables)
```
  {{ entity.bundle|title }} from {{ entity.owner.accountname }} needs review
```
- Subject: (using twig variables, comments, and conditionals and twig tweak's
drupal_token() function)
```
  {% set author = entity.owner.accountname %}

  {{ entity.bundle|title }} from {{ author }} needs review

  {# get their attention if it's from someone important #}

  {% if author == "Admin" %}

  !!! Do it now !!!

  {% endif %}

  {{ drupal_token('site:Name') }}
```
- Adhoc email addresses: (add an email in an single-value entity reference field
and a standard email box)
```
  {{ entity.field_department.entity.field_manager_email.0.value }},
   dropbox@example.com
```
- Adhoc email addresses: (add a specific email and all emails in an
multi-value entity reference field)
```
  dropbox@example.com
  {% for referenced_entity in entity.field_content_owners %}
    {{ referenced_entity.entity.field_email.0.value }}
  {% endfor %}
```

- Message: (can support all the same twig options, but will run template output
through the selected Text format's (basic html, full html, etc.) input filters before it
will be mailed).
```
   Please update this content from
   {{ entity.owner.accountname }}.
```

### Useful Twig Variables

Here is the information formatted as a Markdown table:

| Description                                   | Twig Variable                          |
|-----------------------------------------------|----------------------------------------|
| Current User Email                            | `{{ user.email }}`                     |
| Current User Username (the login name)        | `{{ user.accountname }}`               |
| Current User DisplayName                      | `{{ user.displayname }}`               |
| Current User ID (uid)                         | `{{ user.id }}`                        |
| Author Email                                  | `{{ entity.owner.email }}`             |
| Author Username (the login name)              | `{{ entity.owner.accountname }}`       |
| Author DisplayName                            | `{{ entity.owner.displayname }}`       |
| Author id (uid)                               | `{{ entity.owner.id }}`                |
| Entity Title                                  | `{{ entity.title }}`                   |
| Entity Bundle (Content type system name)      | `{{ entity.bundle }}`                  |
| Entity Bundle Label (Content type name)       | `{{ entity.type.entity.label }}`       |
| Entity ID                                     | `{{ entity.nid }}`                     |
| Entity Revision                               | `{{ entity.vid }}`                     |
| Entity UUID                                   | `{{ entity.uuid }}`                    |

## Maintainers

- Jonathan Hedstrom - [jhedstrom](https://www.drupal.org/u/jhedstrom)
- Rob Holmes - [Rob Holmes](https://www.drupal.org/u/rob-holmes)
- Oleksandr Monoharov [tyapchyc](https://www.drupal.org/u/tyapchyc)
- Brian Osborne [bkosborne](https://www.drupal.org/u/bkosborne)

**Supporting organization:**

- [s8080](https://www.drupal.org/s8080)
