<?php











namespace Composer;

use Composer\Autoload\ClassLoader;
use Composer\Semver\VersionParser;








class InstalledVersions
{
private static $installed = array (
  'root' => 
  array (
    'pretty_version' => 'dev-main',
    'version' => 'dev-main',
    'aliases' => 
    array (
    ),
    'reference' => '5b10fbf769fb0c5e28b92fc931a46a15817eef86',
    'name' => 'drupal/recommended-project',
  ),
  'versions' => 
  array (
    'alchemy/zippy' => 
    array (
      'pretty_version' => '0.4.9',
      'version' => '0.4.9.0',
      'aliases' => 
      array (
      ),
      'reference' => '59fbeefb9a249122867ef25e53addfcce31850d7',
    ),
    'asm89/stack-cors' => 
    array (
      'pretty_version' => '1.3.0',
      'version' => '1.3.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'b9c31def6a83f84b4d4a40d35996d375755f0e08',
    ),
    'chi-teck/drupal-code-generator' => 
    array (
      'pretty_version' => '1.33.1',
      'version' => '1.33.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '5f814e980b6f9cf1ca8c74cc9385c3d81090d388',
    ),
    'commerceguys/addressing' => 
    array (
      'pretty_version' => 'v1.2.2',
      'version' => '1.2.2.0',
      'aliases' => 
      array (
      ),
      'reference' => 'fb98dfc72f8a3d12fac55f69ab2477a0fbfa9860',
    ),
    'composer/installers' => 
    array (
      'pretty_version' => 'v1.12.0',
      'version' => '1.12.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd20a64ed3c94748397ff5973488761b22f6d3f19',
    ),
    'composer/semver' => 
    array (
      'pretty_version' => '3.3.1',
      'version' => '3.3.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '5d8e574bb0e69188786b8ef77d43341222a41a71',
    ),
    'consolidation/annotated-command' => 
    array (
      'pretty_version' => '4.5.2',
      'version' => '4.5.2.0',
      'aliases' => 
      array (
      ),
      'reference' => '24c1529436b4f4beec3d19aab71fd127817f47ef',
    ),
    'consolidation/config' => 
    array (
      'pretty_version' => '1.2.1',
      'version' => '1.2.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'cac1279bae7efb5c7fb2ca4c3ba4b8eb741a96c1',
    ),
    'consolidation/filter-via-dot-access-data' => 
    array (
      'pretty_version' => '1.0.0',
      'version' => '1.0.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'a53e96c6b9f7f042f5e085bf911f3493cea823c6',
    ),
    'consolidation/log' => 
    array (
      'pretty_version' => '2.1.1',
      'version' => '2.1.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '3ad08dc57e8aff9400111bad36beb0ed387fe6a9',
    ),
    'consolidation/output-formatters' => 
    array (
      'pretty_version' => '4.2.2',
      'version' => '4.2.2.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd57992bf81ead908ee21cd94b46ed65afa2e785b',
    ),
    'consolidation/robo' => 
    array (
      'pretty_version' => '3.0.10',
      'version' => '3.0.10.0',
      'aliases' => 
      array (
      ),
      'reference' => '206bbe23b34081a36bfefc4de2abbc1abcd29ef4',
    ),
    'consolidation/self-update' => 
    array (
      'pretty_version' => '2.0.5',
      'version' => '2.0.5.0',
      'aliases' => 
      array (
      ),
      'reference' => '8a64bdd8daf5faa8e85f56534dd99caf928164b3',
    ),
    'consolidation/site-alias' => 
    array (
      'pretty_version' => '3.1.5',
      'version' => '3.1.5.0',
      'aliases' => 
      array (
      ),
      'reference' => 'ef2eb7d37e59b3d837b4556d4d8070cb345b378c',
    ),
    'consolidation/site-process' => 
    array (
      'pretty_version' => '4.2.0',
      'version' => '4.2.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '9ef08d471573d6a56405b06ef6830dd70c883072',
    ),
    'cweagans/composer-patches' => 
    array (
      'pretty_version' => '1.x-dev',
      'version' => '1.9999999.9999999.9999999-dev',
      'aliases' => 
      array (
      ),
      'reference' => 'e9969cfc0796e6dea9b4e52f77f18e1065212871',
    ),
    'dflydev/dot-access-configuration' => 
    array (
      'pretty_version' => 'v1.0.3',
      'version' => '1.0.3.0',
      'aliases' => 
      array (
      ),
      'reference' => '2e6eb0c8b8830b26bb23defcfc38d4276508fc49',
    ),
    'dflydev/dot-access-data' => 
    array (
      'pretty_version' => 'v1.1.0',
      'version' => '1.1.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '3fbd874921ab2c041e899d044585a2ab9795df8a',
    ),
    'dflydev/placeholder-resolver' => 
    array (
      'pretty_version' => 'v1.0.3',
      'version' => '1.0.3.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd0161b4be1e15838327b01b21d0149f382d69906',
    ),
    'doctrine/annotations' => 
    array (
      'pretty_version' => '1.13.2',
      'version' => '1.13.2.0',
      'aliases' => 
      array (
      ),
      'reference' => '5b668aef16090008790395c02c893b1ba13f7e08',
    ),
    'doctrine/collections' => 
    array (
      'pretty_version' => '1.6.8',
      'version' => '1.6.8.0',
      'aliases' => 
      array (
      ),
      'reference' => '1958a744696c6bb3bb0d28db2611dc11610e78af',
    ),
    'doctrine/lexer' => 
    array (
      'pretty_version' => '1.2.3',
      'version' => '1.2.3.0',
      'aliases' => 
      array (
      ),
      'reference' => 'c268e882d4dbdd85e36e4ad69e02dc284f89d229',
    ),
    'doctrine/reflection' => 
    array (
      'pretty_version' => '1.2.2',
      'version' => '1.2.2.0',
      'aliases' => 
      array (
      ),
      'reference' => 'fa587178be682efe90d005e3a322590d6ebb59a5',
    ),
    'drupal/action' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/address' => 
    array (
      'pretty_version' => '1.10.0',
      'version' => '1.10.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-1.10',
    ),
    'drupal/aggregator' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/automated_cron' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/ban' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/bartik' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/basic_auth' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/big_pipe' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/block' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/block_class' => 
    array (
      'pretty_version' => '1.3.0',
      'version' => '1.3.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-1.3',
    ),
    'drupal/block_content' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/book' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/breakpoint' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/calendar' => 
    array (
      'pretty_version' => '1.0.0-alpha4',
      'version' => '1.0.0.0-alpha4',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-1.0-alpha4',
    ),
    'drupal/calendar_datetime' => 
    array (
      'pretty_version' => '1.0.0-alpha4',
      'version' => '1.0.0.0-alpha4',
      'aliases' => 
      array (
      ),
      'reference' => NULL,
    ),
    'drupal/captcha' => 
    array (
      'pretty_version' => '1.2.0',
      'version' => '1.2.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-1.2',
    ),
    'drupal/ckeditor' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/ckeditor5' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/claro' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/classy' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/color' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/comment' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/config' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/config_devel' => 
    array (
      'pretty_version' => '1.8.0',
      'version' => '1.8.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-1.8',
    ),
    'drupal/config_translation' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/console' => 
    array (
      'pretty_version' => '1.9.8',
      'version' => '1.9.8.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd292c940c07d164e32bbe9525e909311ca65e8cb',
    ),
    'drupal/console-core' => 
    array (
      'pretty_version' => '1.9.7',
      'version' => '1.9.7.0',
      'aliases' => 
      array (
      ),
      'reference' => 'ab3abc2631761c9588230ba88189d9ba4eb9ed63',
    ),
    'drupal/console-en' => 
    array (
      'pretty_version' => 'v1.9.7',
      'version' => '1.9.7.0',
      'aliases' => 
      array (
      ),
      'reference' => '7594601fff153c2799a62bd678ff80749baeee0c',
    ),
    'drupal/console-extend-plugin' => 
    array (
      'pretty_version' => '0.9.5',
      'version' => '0.9.5.0',
      'aliases' => 
      array (
      ),
      'reference' => 'eff6da99cfb5fe1fc60990672d2667c402eb3585',
    ),
    'drupal/contact' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/content_moderation' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/content_translation' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/contextual' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/core' => 
    array (
      'pretty_version' => '9.3.8',
      'version' => '9.3.8.0',
      'aliases' => 
      array (
      ),
      'reference' => 'e8e3b7e5b3353f7ebf24de9d39087df75bd66afe',
    ),
    'drupal/core-annotation' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/core-assertion' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/core-bridge' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/core-class-finder' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/core-datetime' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/core-dependency-injection' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/core-diff' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/core-discovery' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/core-event-dispatcher' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/core-file-cache' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/core-file-security' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/core-filesystem' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/core-front-matter' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/core-gettext' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/core-graph' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/core-http-foundation' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/core-php-storage' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/core-plugin' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/core-proxy-builder' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/core-render' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/core-serialization' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/core-transliteration' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/core-utility' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/core-uuid' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/core-version' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/ctools' => 
    array (
      'pretty_version' => '3.7.0',
      'version' => '3.7.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-3.7',
    ),
    'drupal/datetime' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/datetime_range' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/dblog' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/dynamic_page_cache' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/editor' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/entity' => 
    array (
      'pretty_version' => '1.3.0',
      'version' => '1.3.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-1.3',
    ),
    'drupal/entity_reference' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/field' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/field_group' => 
    array (
      'pretty_version' => '3.2.0',
      'version' => '3.2.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-3.2',
    ),
    'drupal/field_layout' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/field_ui' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/file' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/file_entity' => 
    array (
      'pretty_version' => '2.0.0-beta9',
      'version' => '2.0.0.0-beta9',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-2.0-beta9',
    ),
    'drupal/filter' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/forum' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/geolocation' => 
    array (
      'pretty_version' => '3.7.0',
      'version' => '3.7.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-3.7',
    ),
    'drupal/google_analytics' => 
    array (
      'pretty_version' => '4.0.0',
      'version' => '4.0.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '4.0.0',
    ),
    'drupal/group' => 
    array (
      'pretty_version' => '1.4.0',
      'version' => '1.4.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-1.4',
    ),
    'drupal/hal' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/help' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/help_topics' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/history' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/image' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/image_field_caption' => 
    array (
      'pretty_version' => '1.1.0',
      'version' => '1.1.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-1.1',
    ),
    'drupal/inline_form_errors' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/jquery_ui' => 
    array (
      'pretty_version' => '1.4.0',
      'version' => '1.4.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-1.4',
    ),
    'drupal/jquery_ui_accordion' => 
    array (
      'pretty_version' => '1.1.0',
      'version' => '1.1.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-1.1',
    ),
    'drupal/jquery_ui_draggable' => 
    array (
      'pretty_version' => '1.2.0',
      'version' => '1.2.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-1.2',
    ),
    'drupal/jquery_ui_droppable' => 
    array (
      'pretty_version' => '1.2.0',
      'version' => '1.2.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-1.2',
    ),
    'drupal/jsonapi' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/language' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/layout_builder' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/layout_discovery' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/libraries' => 
    array (
      'pretty_version' => '3.0.0-beta2',
      'version' => '3.0.0.0-beta2',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-3.0-beta2',
    ),
    'drupal/link' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/locale' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/location_migration' => 
    array (
      'pretty_version' => '1.0.0-beta4',
      'version' => '1.0.0.0-beta4',
      'aliases' => 
      array (
      ),
      'reference' => '1.0.0-beta4',
    ),
    'drupal/mailsystem' => 
    array (
      'pretty_version' => '4.3.0',
      'version' => '4.3.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-4.3',
    ),
    'drupal/media' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/media_library' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/menu_link_content' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/menu_ui' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/migrate' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/migrate_drupal' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/migrate_drupal_multilingual' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/migrate_drupal_ui' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/migrate_plus' => 
    array (
      'pretty_version' => '5.2.0',
      'version' => '5.2.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-5.2',
    ),
    'drupal/migrate_source_csv' => 
    array (
      'pretty_version' => '3.5.0',
      'version' => '3.5.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-3.5',
    ),
    'drupal/migrate_tools' => 
    array (
      'pretty_version' => '5.1.0',
      'version' => '5.1.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-5.1',
    ),
    'drupal/migrate_upgrade' => 
    array (
      'pretty_version' => '3.2.0',
      'version' => '3.2.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-3.2',
    ),
    'drupal/mimemail' => 
    array (
      'pretty_version' => '1.0.0-alpha4',
      'version' => '1.0.0.0-alpha4',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-1.0-alpha4',
    ),
    'drupal/minimal' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/mysql56' => 
    array (
      'pretty_version' => '1.3.0',
      'version' => '1.3.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-1.3',
    ),
    'drupal/node' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/og' => 
    array (
      'pretty_version' => 'dev-1.x',
      'version' => 'dev-1.x',
      'aliases' => 
      array (
        0 => '1.x-dev',
      ),
      'reference' => 'dec6a56b3a4ac1bbffb7897fabb4d459d36ca3c1',
    ),
    'drupal/olivero' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/openid_connect' => 
    array (
      'pretty_version' => '1.2.0',
      'version' => '1.2.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-1.2',
    ),
    'drupal/options' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/override_node_options' => 
    array (
      'pretty_version' => '2.6.0',
      'version' => '2.6.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-2.6',
    ),
    'drupal/page_cache' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/panels' => 
    array (
      'pretty_version' => '4.6.0',
      'version' => '4.6.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-4.6',
    ),
    'drupal/panels_ipe' => 
    array (
      'pretty_version' => '4.6.0',
      'version' => '4.6.0.0',
      'aliases' => 
      array (
      ),
      'reference' => NULL,
    ),
    'drupal/path' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/path_alias' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/pathauto' => 
    array (
      'pretty_version' => '1.9.0',
      'version' => '1.9.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-1.9',
    ),
    'drupal/quickedit' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/r4032login' => 
    array (
      'pretty_version' => '2.1.0',
      'version' => '2.1.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '2.1.0',
    ),
    'drupal/rdf' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/recommended-project' => 
    array (
      'pretty_version' => 'dev-main',
      'version' => 'dev-main',
      'aliases' => 
      array (
      ),
      'reference' => '5b10fbf769fb0c5e28b92fc931a46a15817eef86',
    ),
    'drupal/reroute_email' => 
    array (
      'pretty_version' => '2.1.0',
      'version' => '2.1.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '2.1.0',
    ),
    'drupal/responsive_image' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/rest' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/scheduler' => 
    array (
      'pretty_version' => '1.4.0',
      'version' => '1.4.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-1.4',
    ),
    'drupal/search' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/serialization' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/settings_tray' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/seven' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/shortcut' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/standard' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/stark' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/statistics' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/syslog' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/system' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/taxonomy' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/telephone' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/text' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/token' => 
    array (
      'pretty_version' => '1.10.0',
      'version' => '1.10.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-1.10',
    ),
    'drupal/toolbar' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/tour' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/tracker' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/update' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/user' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/variationcache' => 
    array (
      'pretty_version' => '1.0.0',
      'version' => '1.0.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-1.0',
    ),
    'drupal/views' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/views_bulk_operations' => 
    array (
      'pretty_version' => '4.1.0',
      'version' => '4.1.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '4.1.0',
    ),
    'drupal/views_field_view' => 
    array (
      'pretty_version' => '1.0.0-beta3',
      'version' => '1.0.0.0-beta3',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-1.0-beta3',
    ),
    'drupal/views_migration' => 
    array (
      'pretty_version' => '1.1.7',
      'version' => '1.1.7.0',
      'aliases' => 
      array (
      ),
      'reference' => '1.1.7',
    ),
    'drupal/views_templates' => 
    array (
      'pretty_version' => '1.1.0',
      'version' => '1.1.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-1.1',
    ),
    'drupal/views_ui' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/viewsreference' => 
    array (
      'pretty_version' => '1.7.0',
      'version' => '1.7.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8.x-1.7',
    ),
    'drupal/workflows' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drupal/workspaces' => 
    array (
      'replaced' => 
      array (
        0 => '9.3.8',
      ),
    ),
    'drush/drush' => 
    array (
      'pretty_version' => '10.6.2',
      'version' => '10.6.2.0',
      'aliases' => 
      array (
      ),
      'reference' => '0a570a16ec63259eb71195aba5feab532318b337',
    ),
    'egulias/email-validator' => 
    array (
      'pretty_version' => '3.1.2',
      'version' => '3.1.2.0',
      'aliases' => 
      array (
      ),
      'reference' => 'ee0db30118f661fb166bcffbf5d82032df484697',
    ),
    'enlightn/security-checker' => 
    array (
      'pretty_version' => 'v1.10.0',
      'version' => '1.10.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '196bacc76e7a72a63d0e1220926dbb190272db97',
    ),
    'grasmash/expander' => 
    array (
      'pretty_version' => '1.0.0',
      'version' => '1.0.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '95d6037344a4be1dd5f8e0b0b2571a28c397578f',
    ),
    'grasmash/yaml-expander' => 
    array (
      'pretty_version' => '1.4.0',
      'version' => '1.4.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '3f0f6001ae707a24f4d9733958d77d92bf9693b1',
    ),
    'guzzlehttp/guzzle' => 
    array (
      'pretty_version' => '6.5.5',
      'version' => '6.5.5.0',
      'aliases' => 
      array (
      ),
      'reference' => '9d4290de1cfd701f38099ef7e183b64b4b7b0c5e',
    ),
    'guzzlehttp/promises' => 
    array (
      'pretty_version' => '1.5.1',
      'version' => '1.5.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'fe752aedc9fd8fcca3fe7ad05d419d32998a06da',
    ),
    'guzzlehttp/psr7' => 
    array (
      'pretty_version' => '1.8.3',
      'version' => '1.8.3.0',
      'aliases' => 
      array (
      ),
      'reference' => '1afdd860a2566ed3c2b0b4a3de6e23434a79ec85',
    ),
    'laminas/laminas-diactoros' => 
    array (
      'pretty_version' => '2.8.0',
      'version' => '2.8.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '0c26ef1d95b6d7e6e3943a243ba3dc0797227199',
    ),
    'laminas/laminas-escaper' => 
    array (
      'pretty_version' => '2.10.0',
      'version' => '2.10.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '58af67282db37d24e584a837a94ee55b9c7552be',
    ),
    'laminas/laminas-feed' => 
    array (
      'pretty_version' => '2.16.0',
      'version' => '2.16.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'cbd0e10c867a1efa6594164d229d8caf4a4ae4c7',
    ),
    'laminas/laminas-stdlib' => 
    array (
      'pretty_version' => '3.7.1',
      'version' => '3.7.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'bcd869e2fe88d567800057c1434f2380354fe325',
    ),
    'league/container' => 
    array (
      'pretty_version' => '3.4.1',
      'version' => '3.4.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '84ecbc2dbecc31bd23faf759a0e329ee49abddbd',
    ),
    'league/csv' => 
    array (
      'pretty_version' => '9.8.0',
      'version' => '9.8.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '9d2e0265c5d90f5dd601bc65ff717e05cec19b47',
    ),
    'masterminds/html5' => 
    array (
      'pretty_version' => '2.7.5',
      'version' => '2.7.5.0',
      'aliases' => 
      array (
      ),
      'reference' => 'f640ac1bdddff06ea333a920c95bbad8872429ab',
    ),
    'nikic/php-parser' => 
    array (
      'pretty_version' => 'v4.13.2',
      'version' => '4.13.2.0',
      'aliases' => 
      array (
      ),
      'reference' => '210577fe3cf7badcc5814d99455df46564f3c077',
    ),
    'orno/di' => 
    array (
      'replaced' => 
      array (
        0 => '~2.0',
      ),
    ),
    'pear/archive_tar' => 
    array (
      'pretty_version' => '1.4.14',
      'version' => '1.4.14.0',
      'aliases' => 
      array (
      ),
      'reference' => '4d761c5334c790e45ef3245f0864b8955c562caa',
    ),
    'pear/console_getopt' => 
    array (
      'pretty_version' => 'v1.4.3',
      'version' => '1.4.3.0',
      'aliases' => 
      array (
      ),
      'reference' => 'a41f8d3e668987609178c7c4a9fe48fecac53fa0',
    ),
    'pear/pear-core-minimal' => 
    array (
      'pretty_version' => 'v1.10.11',
      'version' => '1.10.11.0',
      'aliases' => 
      array (
      ),
      'reference' => '68d0d32ada737153b7e93b8d3c710ebe70ac867d',
    ),
    'pear/pear_exception' => 
    array (
      'pretty_version' => 'v1.0.2',
      'version' => '1.0.2.0',
      'aliases' => 
      array (
      ),
      'reference' => 'b14fbe2ddb0b9f94f5b24cf08783d599f776fff0',
    ),
    'psr/cache' => 
    array (
      'pretty_version' => '1.0.1',
      'version' => '1.0.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd11b50ad223250cf17b86e38383413f5a6764bf8',
    ),
    'psr/container' => 
    array (
      'pretty_version' => '1.1.2',
      'version' => '1.1.2.0',
      'aliases' => 
      array (
      ),
      'reference' => '513e0666f7216c7459170d56df27dfcefe1689ea',
    ),
    'psr/container-implementation' => 
    array (
      'provided' => 
      array (
        0 => '^1.0',
        1 => '1.0',
      ),
    ),
    'psr/event-dispatcher-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0',
      ),
    ),
    'psr/http-factory' => 
    array (
      'pretty_version' => '1.0.1',
      'version' => '1.0.1.0',
      'aliases' => 
      array (
      ),
      'reference' => '12ac7fcd07e5b077433f5f2bee95b3a771bf61be',
    ),
    'psr/http-factory-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0',
      ),
    ),
    'psr/http-message' => 
    array (
      'pretty_version' => '1.0.1',
      'version' => '1.0.1.0',
      'aliases' => 
      array (
      ),
      'reference' => 'f6561bf28d520154e4b0ec72be95418abe6d9363',
    ),
    'psr/http-message-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0',
      ),
    ),
    'psr/log' => 
    array (
      'pretty_version' => '1.1.4',
      'version' => '1.1.4.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd49695b909c3b7628b6289db5479a1c204601f11',
    ),
    'psr/log-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0|2.0',
      ),
    ),
    'psy/psysh' => 
    array (
      'pretty_version' => 'v0.10.12',
      'version' => '0.10.12.0',
      'aliases' => 
      array (
      ),
      'reference' => 'a0d9981aa07ecfcbea28e4bfa868031cca121e7d',
    ),
    'ralouphie/getallheaders' => 
    array (
      'pretty_version' => '3.0.3',
      'version' => '3.0.3.0',
      'aliases' => 
      array (
      ),
      'reference' => '120b605dfeb996808c31b6477290a714d356e822',
    ),
    'roundcube/plugin-installer' => 
    array (
      'replaced' => 
      array (
        0 => '*',
      ),
    ),
    'rsky/pear-core-min' => 
    array (
      'replaced' => 
      array (
        0 => 'v1.10.11',
      ),
    ),
    'shama/baton' => 
    array (
      'replaced' => 
      array (
        0 => '*',
      ),
    ),
    'squizlabs/php_codesniffer' => 
    array (
      'pretty_version' => '3.6.2',
      'version' => '3.6.2.0',
      'aliases' => 
      array (
      ),
      'reference' => '5e4e71592f69da17871dba6e80dd51bce74a351a',
    ),
    'stack/builder' => 
    array (
      'pretty_version' => 'v1.0.6',
      'version' => '1.0.6.0',
      'aliases' => 
      array (
      ),
      'reference' => 'a4faaa6f532c6086bc66c29e1bc6c29593e1ca7c',
    ),
    'stecman/symfony-console-completion' => 
    array (
      'pretty_version' => '0.11.0',
      'version' => '0.11.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'a9502dab59405e275a9f264536c4e1cb61fc3518',
    ),
    'symfony-cmf/routing' => 
    array (
      'pretty_version' => '2.3.4',
      'version' => '2.3.4.0',
      'aliases' => 
      array (
      ),
      'reference' => 'bbcdf2f6301d740454ba9ebb8adaefd436c36a6b',
    ),
    'symfony/config' => 
    array (
      'pretty_version' => 'v4.4.37',
      'version' => '4.4.37.0',
      'aliases' => 
      array (
      ),
      'reference' => 'e8c2d2c951ddedecb6d28954d336cb7d2e852d0e',
    ),
    'symfony/console' => 
    array (
      'pretty_version' => 'v4.4.38',
      'version' => '4.4.38.0',
      'aliases' => 
      array (
      ),
      'reference' => '5a50085bf5460f0c0d60a50b58388c1249826b8a',
    ),
    'symfony/css-selector' => 
    array (
      'pretty_version' => 'v4.4.37',
      'version' => '4.4.37.0',
      'aliases' => 
      array (
      ),
      'reference' => '0628e6c6d7c92f1a7bae543959bdc17347be2436',
    ),
    'symfony/debug' => 
    array (
      'pretty_version' => 'v4.4.37',
      'version' => '4.4.37.0',
      'aliases' => 
      array (
      ),
      'reference' => '5de6c6e7f52b364840e53851c126be4d71e60470',
    ),
    'symfony/dependency-injection' => 
    array (
      'pretty_version' => 'v4.4.39',
      'version' => '4.4.39.0',
      'aliases' => 
      array (
      ),
      'reference' => '5d0fbcdb9317864b2bd9e49d570d88ae512cadf3',
    ),
    'symfony/deprecation-contracts' => 
    array (
      'pretty_version' => 'v2.5.0',
      'version' => '2.5.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '6f981ee24cf69ee7ce9736146d1c57c2780598a8',
    ),
    'symfony/dom-crawler' => 
    array (
      'pretty_version' => 'v4.4.39',
      'version' => '4.4.39.0',
      'aliases' => 
      array (
      ),
      'reference' => '4e9215a8b533802ba84a3cc5bd3c43103e7a6dc3',
    ),
    'symfony/error-handler' => 
    array (
      'pretty_version' => 'v4.4.37',
      'version' => '4.4.37.0',
      'aliases' => 
      array (
      ),
      'reference' => '8d80ad881e1ce17979547873d093e3c987a6a629',
    ),
    'symfony/event-dispatcher' => 
    array (
      'pretty_version' => 'v4.4.37',
      'version' => '4.4.37.0',
      'aliases' => 
      array (
      ),
      'reference' => '3ccfcfb96ecce1217d7b0875a0736976bc6e63dc',
    ),
    'symfony/event-dispatcher-contracts' => 
    array (
      'pretty_version' => 'v1.1.11',
      'version' => '1.1.11.0',
      'aliases' => 
      array (
      ),
      'reference' => '01e9a4efac0ee33a05dfdf93b346f62e7d0e998c',
    ),
    'symfony/event-dispatcher-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.1',
      ),
    ),
    'symfony/filesystem' => 
    array (
      'pretty_version' => 'v4.4.39',
      'version' => '4.4.39.0',
      'aliases' => 
      array (
      ),
      'reference' => '72a5b35fecaa670b13954e6eaf414acbe2a67b35',
    ),
    'symfony/finder' => 
    array (
      'pretty_version' => 'v4.4.37',
      'version' => '4.4.37.0',
      'aliases' => 
      array (
      ),
      'reference' => 'b17d76d7ed179f017aad646e858c90a2771af15d',
    ),
    'symfony/http-client-contracts' => 
    array (
      'pretty_version' => 'v2.5.0',
      'version' => '2.5.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'ec82e57b5b714dbb69300d348bd840b345e24166',
    ),
    'symfony/http-foundation' => 
    array (
      'pretty_version' => 'v4.4.39',
      'version' => '4.4.39.0',
      'aliases' => 
      array (
      ),
      'reference' => '60e8e42a4579551e5ec887d04380e2ab9e4cc314',
    ),
    'symfony/http-kernel' => 
    array (
      'pretty_version' => 'v4.4.39',
      'version' => '4.4.39.0',
      'aliases' => 
      array (
      ),
      'reference' => '19d1cacefe81cb448227cc4d5909fb36e2e23081',
    ),
    'symfony/mime' => 
    array (
      'pretty_version' => 'v5.4.3',
      'version' => '5.4.3.0',
      'aliases' => 
      array (
      ),
      'reference' => 'e1503cfb5c9a225350f549d3bb99296f4abfb80f',
    ),
    'symfony/polyfill-ctype' => 
    array (
      'pretty_version' => 'v1.25.0',
      'version' => '1.25.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '30885182c981ab175d4d034db0f6f469898070ab',
    ),
    'symfony/polyfill-iconv' => 
    array (
      'pretty_version' => 'v1.25.0',
      'version' => '1.25.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'f1aed619e28cb077fc83fac8c4c0383578356e40',
    ),
    'symfony/polyfill-intl-idn' => 
    array (
      'pretty_version' => 'v1.25.0',
      'version' => '1.25.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '749045c69efb97c70d25d7463abba812e91f3a44',
    ),
    'symfony/polyfill-intl-normalizer' => 
    array (
      'pretty_version' => 'v1.25.0',
      'version' => '1.25.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '8590a5f561694770bdcd3f9b5c69dde6945028e8',
    ),
    'symfony/polyfill-mbstring' => 
    array (
      'pretty_version' => 'v1.25.0',
      'version' => '1.25.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '0abb51d2f102e00a4eefcf46ba7fec406d245825',
    ),
    'symfony/polyfill-php72' => 
    array (
      'pretty_version' => 'v1.25.0',
      'version' => '1.25.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '9a142215a36a3888e30d0a9eeea9766764e96976',
    ),
    'symfony/polyfill-php73' => 
    array (
      'pretty_version' => 'v1.25.0',
      'version' => '1.25.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'cc5db0e22b3cb4111010e48785a97f670b350ca5',
    ),
    'symfony/polyfill-php80' => 
    array (
      'pretty_version' => 'v1.25.0',
      'version' => '1.25.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '4407588e0d3f1f52efb65fbe92babe41f37fe50c',
    ),
    'symfony/polyfill-php81' => 
    array (
      'pretty_version' => 'v1.25.0',
      'version' => '1.25.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '5de4ba2d41b15f9bd0e19b2ab9674135813ec98f',
    ),
    'symfony/process' => 
    array (
      'pretty_version' => 'v4.4.37',
      'version' => '4.4.37.0',
      'aliases' => 
      array (
      ),
      'reference' => 'b2d924e5a4cb284f293d5092b1dbf0d364cb8b67',
    ),
    'symfony/psr-http-message-bridge' => 
    array (
      'pretty_version' => 'v2.1.2',
      'version' => '2.1.2.0',
      'aliases' => 
      array (
      ),
      'reference' => '22b37c8a3f6b5d94e9cdbd88e1270d96e2f97b34',
    ),
    'symfony/routing' => 
    array (
      'pretty_version' => 'v4.4.37',
      'version' => '4.4.37.0',
      'aliases' => 
      array (
      ),
      'reference' => '324f7f73b89cd30012575119430ccfb1dfbc24be',
    ),
    'symfony/serializer' => 
    array (
      'pretty_version' => 'v4.4.39',
      'version' => '4.4.39.0',
      'aliases' => 
      array (
      ),
      'reference' => '0c7866443c42e0d4bd460c0ed1c4d5891445ddab',
    ),
    'symfony/service-contracts' => 
    array (
      'pretty_version' => 'v2.5.0',
      'version' => '2.5.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '1ab11b933cd6bc5464b08e81e2c5b07dec58b0fc',
    ),
    'symfony/service-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0|2.0',
      ),
    ),
    'symfony/translation' => 
    array (
      'pretty_version' => 'v4.4.37',
      'version' => '4.4.37.0',
      'aliases' => 
      array (
      ),
      'reference' => '4ce00d6875230b839f5feef82e51971f6c886e00',
    ),
    'symfony/translation-contracts' => 
    array (
      'pretty_version' => 'v2.5.0',
      'version' => '2.5.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd28150f0f44ce854e942b671fc2620a98aae1b1e',
    ),
    'symfony/translation-implementation' => 
    array (
      'provided' => 
      array (
        0 => '1.0|2.0',
      ),
    ),
    'symfony/validator' => 
    array (
      'pretty_version' => 'v4.4.39',
      'version' => '4.4.39.0',
      'aliases' => 
      array (
      ),
      'reference' => '8fdee5a7118e30a6247113a925fb4d702b2a3bcd',
    ),
    'symfony/var-dumper' => 
    array (
      'pretty_version' => 'v5.4.6',
      'version' => '5.4.6.0',
      'aliases' => 
      array (
      ),
      'reference' => '294e9da6e2e0dd404e983daa5aa74253d92c05d0',
    ),
    'symfony/yaml' => 
    array (
      'pretty_version' => 'v4.4.37',
      'version' => '4.4.37.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd7f637cc0f0cc14beb0984f2bb50da560b271311',
    ),
    'twig/twig' => 
    array (
      'pretty_version' => 'v2.14.11',
      'version' => '2.14.11.0',
      'aliases' => 
      array (
      ),
      'reference' => '66baa66f29ee30e487e05f1679903e36eb01d727',
    ),
    'typo3/phar-stream-wrapper' => 
    array (
      'pretty_version' => 'v3.1.7',
      'version' => '3.1.7.0',
      'aliases' => 
      array (
      ),
      'reference' => '5cc2f04a4e2f5c7e9cc02a3bdf80fae0f3e11a8c',
    ),
    'webflo/drupal-finder' => 
    array (
      'pretty_version' => '1.2.2',
      'version' => '1.2.2.0',
      'aliases' => 
      array (
      ),
      'reference' => 'c8e5dbe65caef285fec8057a4c718a0d4138d1ee',
    ),
    'webmozart/assert' => 
    array (
      'pretty_version' => '1.10.0',
      'version' => '1.10.0.0',
      'aliases' => 
      array (
      ),
      'reference' => '6964c76c7804814a842473e0c8fd15bab0f18e25',
    ),
    'webmozart/path-util' => 
    array (
      'pretty_version' => '2.3.0',
      'version' => '2.3.0.0',
      'aliases' => 
      array (
      ),
      'reference' => 'd939f7edc24c9a1bb9c0dee5cb05d8e859490725',
    ),
  ),
);
private static $canGetVendors;
private static $installedByVendor = array();







public static function getInstalledPackages()
{
$packages = array();
foreach (self::getInstalled() as $installed) {
$packages[] = array_keys($installed['versions']);
}

if (1 === \count($packages)) {
return $packages[0];
}

return array_keys(array_flip(\call_user_func_array('array_merge', $packages)));
}









public static function isInstalled($packageName)
{
foreach (self::getInstalled() as $installed) {
if (isset($installed['versions'][$packageName])) {
return true;
}
}

return false;
}














public static function satisfies(VersionParser $parser, $packageName, $constraint)
{
$constraint = $parser->parseConstraints($constraint);
$provided = $parser->parseConstraints(self::getVersionRanges($packageName));

return $provided->matches($constraint);
}










public static function getVersionRanges($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

$ranges = array();
if (isset($installed['versions'][$packageName]['pretty_version'])) {
$ranges[] = $installed['versions'][$packageName]['pretty_version'];
}
if (array_key_exists('aliases', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['aliases']);
}
if (array_key_exists('replaced', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['replaced']);
}
if (array_key_exists('provided', $installed['versions'][$packageName])) {
$ranges = array_merge($ranges, $installed['versions'][$packageName]['provided']);
}

return implode(' || ', $ranges);
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getVersion($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['version'])) {
return null;
}

return $installed['versions'][$packageName]['version'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getPrettyVersion($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['pretty_version'])) {
return null;
}

return $installed['versions'][$packageName]['pretty_version'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getReference($packageName)
{
foreach (self::getInstalled() as $installed) {
if (!isset($installed['versions'][$packageName])) {
continue;
}

if (!isset($installed['versions'][$packageName]['reference'])) {
return null;
}

return $installed['versions'][$packageName]['reference'];
}

throw new \OutOfBoundsException('Package "' . $packageName . '" is not installed');
}





public static function getRootPackage()
{
$installed = self::getInstalled();

return $installed[0]['root'];
}







public static function getRawData()
{
return self::$installed;
}



















public static function reload($data)
{
self::$installed = $data;
self::$installedByVendor = array();
}





private static function getInstalled()
{
if (null === self::$canGetVendors) {
self::$canGetVendors = method_exists('Composer\Autoload\ClassLoader', 'getRegisteredLoaders');
}

$installed = array();

if (self::$canGetVendors) {
foreach (ClassLoader::getRegisteredLoaders() as $vendorDir => $loader) {
if (isset(self::$installedByVendor[$vendorDir])) {
$installed[] = self::$installedByVendor[$vendorDir];
} elseif (is_file($vendorDir.'/composer/installed.php')) {
$installed[] = self::$installedByVendor[$vendorDir] = require $vendorDir.'/composer/installed.php';
}
}
}

$installed[] = self::$installed;

return $installed;
}
}
