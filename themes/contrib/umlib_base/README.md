# umlib_base

## This is the base drupal theme for um library sites. ##

**Please add this theme to your drupal full stack project using composer:**

### Setup

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
+        "umlib_base-theme": {
+            "type": "package",
+            "package": {
+                "name": "mlibrary/umlib_base",
+                "version": "1.0",
+                "type": "drupal-theme",
+                "dist": {
+                    "type": "zip",
+                    "url": "https://github.com/mlibrary/umlib_base/archive/refs/heads/main.zip",
+                    "reference": "main"
+                }
+            }
+        },
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
-            "themes/contrib/{$name}": [
+            "themes/{$name}": [
                 "type:drupal-theme"
             ],
```

### Install

You can now run the following to get the latest version of the umlib_base theme

```
composer require mlibrary/umlib_base
```

### Update

To get the lastest version in an existing project, you unfortunately cannot simply run composer update. Instead run

```
composer clear-cache
composer reinstall mlibrary/umlib_base
```

### Customize

You can then add a custom theme for your project based on this theme using the *base theme:* in your themes *info.yml* file. For example:

```
vi themes/umlib_blogs/umlib_blogs.info.yml
```
```
name: UM Library Blogs
type: theme
description: "A theme for UM Library Blogs."
core_version_requirement: ^9 || ^10
base theme: umlib_base
regions:
  header: "Header"
  banner: "Banner"
  content: "Content"
  sidebar_first: "Sidebar first"
  footer_first: "Footer First"
  ```

All um_base styles will then be inherited to your project.
For an example visit https://github.com/mlibrary/blogs.lib/tree/main/themes

