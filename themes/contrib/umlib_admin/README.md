# umlib_admin

This is the admin drupal theme for U-M Library sites built using the Claro admin theme as a parent. The umlib_admin theme uses styles from the U-M Library Design System.

## Regions & Blocks

The website header relies on two blocks in the header region.

Region: Header
- Site branding
- User account menu

For more information, see the Drupal umlib_admin Figma file.

## Website header

The website header pulls in the system site name provided in your Drupal instance (Configuration  > System > Site name) and the logo and favicons files provided with this theme (`logo.svg` and `favicon.ico`.)

Ensure that the following are checked in Appearance settings:

- [X] Use the logo supplied by the theme
- [X] Use the favicon supplied by the theme

### Setup

**Please add this theme to your Drupal full stack project using composer:**

```
vi composer.json
```

and add the following below your drupal line in repositories

```
    "repositories": {
        "drupal": {
            "type": "composer",
            "url": "https://packages.drupal.org/8"
        },
         "umlib_admin-theme": {
             "type": "package",
             "package": {
                 "name": "mlibrary/umlib_admin",
                 "version": "1.0",
                 "type": "drupal-theme",
                 "dist": {
                     "type": "zip",
                     "url": "https://github.com/mlibrary/umlib_admin/archive/refs/heads/main.zip",
                     "reference": "main"
                 }
             }
         },
```

You may also wish to alter what directory the theme is installed in

```
    "extra": {
        "enable-patching": true,
        "patchLevel": {
            "drupal/core": "-p2"
        },
        "installer-paths": {
            "core": [
                "type:drupal-core"
            ],
            "libraries/{$name}": [
                "type:drupal-library"
            ],
            "modules/contrib/{$name}": [
                "type:drupal-module"
            ],
             "themes/contrib/{$name}": [
             "themes/{$name}": [
                 "type:drupal-theme"
             ],
```

### Install

You can now run the following to get the latest version of the umlib_base theme

```
composer require mlibrary/umlib_admin
```

### Update

To get the lastest version in an existing project, you unfortunately cannot simply run composer update. Instead run

```
composer clear-cache
composer reinstall mlibrary/umlib_admin
```
