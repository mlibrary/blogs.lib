# Drupal Calendar 8.x

## Introduction

The Calendar module makes it possible to create calendars with Views, based on
date fields on nodes and taxonomy terms.

## Simple setup

The easiest way to set up a calendar is by using the "add from template"
functionality provided by the Views Templates module. After enabling the module
and clearing the cache, a link "add from template" should appear on the Views
overview page. This should list the different options to create a calendar
based on core fields (created and updated) or any other custom defined date
field.

## CACHING & PERFORMANCE

Calendars are very time-consuming to process, so caching is recommended.
You can set up caching options for your calendar in the Advanced section
of the View settings. Even setting a lifetime of 1 hour will provide some
benefits if you can live with a calendar that is 1 hour out of date.
Set the lifetime to the longest value possible. You will need to clear
the caches manually or using custom code if the content of the calendar
changes before the cache lifetime expires.

The recommended settings for time-based caching in Views are:

- Query results:
  Enable caching of query results where possible. Avoid caching if your display
  uses AJAX, as it can cause stale or incorrect data to appear.

- Rendered output:
  Always enable caching for rendered output. Rendering is the most
  performance-intensive part of displaying a calendar. This is particularly
  important for Views that show longer time ranges, such as the Year View.

Be sure to test your cache configuration to confirm it behaves as expected.

If performance is a problem, or you have a lot of items in the calendar,
you may want to remove the Year View completely.

## CONTRIBUTING

See [CONTRIBUTING](./CONTRIBUTING.md).
