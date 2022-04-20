# Migrate file fields to media fields from D7 to D8.

It is a great functionality to be able to migrate D7 images directly to D8 media image entities with this module (without the redirection via an D8 image field).

As the drush generator doesn't provide details about differences in configuration between migration from D8 image source and D7 image source and the provided examples also only focus on D8=>D8 migration, I'd like to suggest the inclusion of an example configuration for D7 => D8 migration explaining all the glory, using the D7 and D8 Article content types.

## Prerequisites

- A standard Drupal 7 installation and one or more articles with an image created.
- A standard Drupal 8.8.x installation
- A media field 'field_media' (with image enabled) added to the D8 article content type and the 'field_image' removed.
- D8 core modules migrate, migrate_drupal and contrib modules migrate_plus, migrate_tools and migrate_file_to_media enabled
drush 9 installed (10 is also ok) for the D8 site
- A database entry defining key 'migration_source_db' in the D8 settings.php, pointing to the D7 database
- Create the directory-path 'media/article' under the D8 'sites/default/files' path and make sure it is writeable for Drupal

The Media migration expects the nodes migrated, before working properly, so I start with the basic node migration configuration for the 'Article' content type, having a constant user 1 (admin) to keep it simple.

Add the configuration file `config/install/migrate_plus.migration.mig_article_node.yml` to your custom migration module
Add the configuration file `config/install/migrate_plus.migration.mig_article_media_step1.yml` to your custom migration module and replace `<d7-domain.tld>` with the domain of your D7 site
Add the configuration file `config/install/migrate_plus.migration.mig_article_media_step2.yml` to your custom migration module

Enable your custom migration module.

`drush en <your_custom_migration_module>`

Use the duplicate-file-detection drush command (described on the start page of this module):

`drush migrate:duplicate-file-detection mig_article_media_step1`

Now check for the three new migrations appear in the list of available migrations

`drush ms`

and work through the migrations themselves.

`drush mim mig_article_node`
`drush mim mig_article_media_step1`
`drush mim mig_article_media_step2`
If all went right, you have your articles and their images migrated from D7 to D8, rightfully.

## Remarks

There is a file and a media entity created for each source image. While the media entities regain the created and changed times of their original D7 article node, it is not possible to preserve the timestamps of the original files. They instead get the timestamp of when the migration was performed. The best solution, so far, would be to use an SQL update for updating the file timestamps from the media entity timestamps. To keep this text simple, I don't go into the details of an SQL update.
At the time of this writing there are two bugs in the 'migrate_file_to_media' module, which can cause errors with the example. The first occurs, when the D7 article images are stored in a subdirectory of 'sites/default/files', and the second, when there is no Crop API module enabled in your D8 installation. For both errors I've provided a patch in related issues.