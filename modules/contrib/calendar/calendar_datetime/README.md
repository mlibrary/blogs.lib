# Drupal Calendar 8.x - Calendar DateTime

This module simply holds classes and other functions that will be later
added to Drupal core DateTime module(or possibly contrib Date module).
Each class or function should have a reference to the Drupal core issue
where it came from.

Ideally these classes should come directly from RTBC core datetime issues
that will be added in later 8.x point releases(8.1, 8.2, etc).

Once they have been added to core they can be remove from this module and the
main Calendar module should only have to change "use" statements for classes
or change function calls from "calendar_datetime_*" to "datetime_*" **without
other programming changes**.

## Current Core Patches
~
1. [#2325899] Error when setting current day as default argument.
 *Patch*: https://www.drupal.org/files/issues/2024-06-24/2325899-212.patch
 *Purpose*: Allow current date to be default argument.
