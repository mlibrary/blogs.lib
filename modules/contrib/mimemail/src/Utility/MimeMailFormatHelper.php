<?php

namespace Drupal\mimemail\Utility;

use Drupal\Component\Utility\Mail;
use Drupal\Component\Utility\Unicode;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\StreamWrapper\StreamWrapperManager;
use Drupal\Core\Url;
use Drupal\user\UserInterface;

/**
 * Utility methods for formatting MIME-encoded email messages.
 */
class MimeMailFormatHelper {

  /**
   * Line terminator.
   *
   * @see http://tools.ietf.org/html/rfc2822#section-2.1
   */
  const CRLF = "\r\n";

  /**
   * Suggested line length in characters, not counting terminator characters.
   *
   * @see http://tools.ietf.org/html/rfc2822#section-2.2.1
   */
  const RFC2822_MAXLEN = 78;

  /**
   * Formats an email address as a string.
   *
   * @param string|array|\Drupal\user\UserInterface $address
   *   MimeMailFormatHelper::mimeMailAddress() accepts addresses in one of
   *   four different formats:
   *   - A text email address, e.g. someone@example.com.
   *   - An array where the values are each a text email address.
   *   - An associative array to represent one email address, containing keys:
   *     - mail: A text email address, as above.
   *     - (optional) name: A text name to accompany the email address,
   *       e.g. 'John Doe'.
   *   - A fully loaded object implementing \Drupal\user\UserInterface.
   * @param bool $simplify
   *   Whether to simply the formatted email address. Defaults to FALSE.
   *
   * @return string|false
   *   A RFC-2822 formatted email address string, or FALSE if the address
   *   parameter passed in is not one of the allowed data types.
   */
  public static function mimeMailAddress($address, $simplify = FALSE) {
    // It's an array containing 'mail' and/or 'name',
    // or it's an array of address items.
    if (is_array($address)) {
      // It's an array containing 'mail' and/or 'name'.
      if (isset($address['mail'])) {
        // Return full RFC2822 format only if we're NOT simplifying AND
        // the account name is NOT empty.
        if (!$simplify && !empty($address['name'])) {
          return Mail::formatDisplayName($address['name']) . ' <' . $address['mail'] . '>';
        }
        // All other combinations, return a simple email.
        return $address['mail'];
      }
      // It's an array of address items.
      $addresses = [];
      foreach ($address as $a) {
        $addresses[] = static::mimeMailAddress($a, $simplify);
      }
      return $addresses;
    }

    // It's a user object.
    if (($address instanceof UserInterface) && $address->getEmail()) {
      // Return full RFC2822 format only if we're NOT simplifying AND
      // the account name is NOT empty.
      if (!$simplify && !empty($address->getAccountName())) {
        return Mail::formatDisplayName($address->getAccountName()) . ' <' . $address->getEmail() . '>';
      }
      // All other combinations, return a simple email.
      return $address->getEmail();
    }

    // It's a formatted or unformatted string.
    // The logic is a little different here because we didn't receive the
    // string already separated into parts like we had in the above cases.
    if (is_string($address)) {
      $pattern = '/(.*?)<(.*?)>/';
      preg_match_all($pattern, $address, $matches);
      // $name is the entire string before the first '<'.
      $name = isset($matches[1][0]) ? $matches[1][0] : '';
      // $bare_address is the string between the first '<' and the last '>'.
      $bare_address = isset($matches[2][0]) ? $matches[2][0] : $address;

      // If we're simplifying, we just need the bare address.
      if ($simplify) {
        return $bare_address;
      }
      else {
        // If there is no $name, then assume what we've been given is already
        // in a valid RFC2822 format.
        if (empty(trim($name))) {
          return $address;
        }
        // Put $address into full RFC2822 format only if we're NOT simplifying
        // AND the account name is NOT empty.
        return Mail::formatDisplayName(trim($name)) . ' <' . $bare_address . '>';
      }
    }

    return FALSE;
  }

  /**
   * Generates a multipart message body with a plaintext alternative.
   *
   * The first MIME part is a multipart/alternative containing MIME-encoded
   * sub-parts for HTML and plaintext. Each subsequent part is the required
   * image or attachment.
   *
   * @param string $body
   *   The HTML message body.
   * @param string $subject
   *   The message subject.
   * @param bool $plain
   *   (optional) Whether the recipient prefers plaintext-only messages.
   *   Defaults to FALSE.
   * @param string $plaintext
   *   (optional) The plaintext message body.
   * @param array $attachments
   *   (optional) The files to be attached to the message.
   *
   * @return array
   *   An associative array containing the following elements:
   *   - body: A string containing the MIME-encoded multipart body of a mail.
   *   - headers: An array that includes some headers for the mail to be sent.
   */
  public static function mimeMailHtmlBody($body, $subject, $plain = FALSE, $plaintext = NULL, array $attachments = []) {
    if (empty($plaintext)) {
      // @todo Remove once filter_xss() can handle direct descendant selectors in inline CSS.
      // @see http://drupal.org/node/1116930
      // @see http://drupal.org/node/370903
      // Pull out the message body.
      preg_match('|<body.*?</body>|mis', $body, $matches);
      $plaintext = MailFormatHelper::htmlToText($matches[0]);
    }
    if ($plain) {
      // Plain mail without attachment.
      if (empty($attachments)) {
        $content_type = 'text/plain';
        return [
          'body' => $plaintext,
          'headers' => ['Content-Type' => 'text/plain; charset=utf-8'],
        ];
      }
      // Plain mail with attachment.
      else {
        $content_type = 'multipart/mixed';
        $parts[] = [
          'Content-Type' => 'text/plain; charset=utf-8',
          'content' => $plaintext,
        ];
      }
    }
    else {
      $content_type = 'multipart/mixed';
      $plaintext_part = [
        'Content-Type' => 'text/plain; charset=utf-8',
        'content' => $plaintext,
      ];

      // Expand all local links.
      $pattern = '/(<a[^>]+href=")([^"]*)/mi';
      $body = preg_replace_callback(
        $pattern,
        function ($matches) {
          // matches[1] is the anchor tag starting with the <, up to and
          // including the starting quote of the href attribute.
          //
          // matches[2] is everything between quotes in the href attribute
          // of an anchor tag.
          return $matches[1] . self::mimeMailUrl($matches[2]);
        },
        $body
      );

      $mime_parts = static::mimeMailExtractFiles($body);

      $content = [$plaintext_part, array_shift($mime_parts)];
      $content = static::mimeMailMultipartBody($content, 'multipart/alternative', TRUE);
      $parts[] = [
        'Content-Type' => $content['headers']['Content-Type'],
        'content' => $content['body'],
      ];

      if ($mime_parts) {
        $parts = array_merge($parts, $mime_parts);
        $content = static::mimeMailMultipartBody($parts, 'multipart/related; type="multipart/alternative"', TRUE);
        $parts[] = [
          'Content-Type' => $content['headers']['Content-Type'],
          'content' => $content['body'],
        ];
      }
    }

    if (is_array($attachments) && !empty($attachments)) {
      foreach ($attachments as $a) {
        $a = (object) $a;
        $path = isset($a->uri) ? $a->uri : (isset($a->filepath) ? $a->filepath : NULL);
        $content = isset($a->filecontent) ? $a->filecontent : NULL;
        $name = isset($a->filename) ? $a->filename : NULL;
        $type = isset($a->filemime) ? $a->filemime : NULL;
        // Add this attachment to the cumulative manifest being built by
        // mimeMailFile().
        static::mimeMailFile($path, $content, $name, $type, 'attachment');
      }
      // Fetch the cumulative manifest from mimeMailFile() and merge it with
      // the other parts of this message.
      $parts = array_merge($parts, static::mimeMailFile());
    }

    return static::mimeMailMultipartBody($parts, $content_type);
  }

  /**
   * Extracts links to local images from HTML documents.
   *
   * @param string $html
   *   A string containing the HTML source of the message.
   *
   * @return array
   *   An array containing the document body and the extracted files,
   *   structured like the following:
   *   @code
   *   [
   *     [
   *       'name' => document name,
   *       'content' => html text, local image urls replaced by Content-IDs,
   *       'Content-Type' => 'text/html; charset=utf-8',
   *     ],
   *     [
   *       'name' => file name,
   *       'file' => reference to local file,
   *       'Content-ID' => generated Content-ID,
   *       'Content-Type' => derived using mime_content_type if available, educated guess otherwise,
   *     ],
   *   ]
   *   @endcode
   */
  public static function mimeMailExtractFiles($html) {
    $pattern = '/(<link[^>]+href=[\'"]?|<object[^>]+codebase=[\'"]?|@import |[\s]src=[\'"]?)([^\'>"]+)([\'"]?)/mis';
    $content = preg_replace_callback(
      $pattern,
      function ($matches) {
        // matches[1] is one of:
        // - A link tag starting with the <, up to and including the starting
        //   quote of the href attribute. OR,
        // - An object tag starting with the <, up to and including the starting
        //   quote of the codebase attribute. OR,
        // - A CSS @import rule starting with the @, up to and including the
        //   starting quote of the url property. OR,
        // - A tag with a src attribute, starting with the whitespace before the
        //   src, up to and including the starting quote of the src attribute.
        //
        // matches[2] is everything between quotes after one of the above
        // matches.
        //
        // matches[3] is the trailing quote.
        return stripslashes($matches[1]) . self::mimeMailFile($matches[2]) . stripslashes($matches[3]);
      },
      $html
    );

    $encoding = '8Bit';
    $body = explode("\n", $content);
    foreach ($body as $line) {
      if (mb_strlen($line) > 998) {
        $encoding = 'base64';
        break;
      }
    }
    if ($encoding == 'base64') {
      $content = rtrim(chunk_split(base64_encode($content)));
    }

    $document[] = [
      'Content-Type' => "text/html; charset=utf-8",
      'Content-Transfer-Encoding' => $encoding,
      'content' => $content,
    ];

    $files = static::mimeMailFile();

    return array_merge($document, $files);
  }

  /**
   * Builds a manifest containing metadata about files attached to an email.
   *
   * Calling this function with either a $url argument or a $content argument
   * will generate metadata for the file described by that argument and will
   * accumulate that metadata internally in a static array. Subsequent calls
   * with one of these arguments will add additional metadata to this internal
   * array.
   *
   * Calling this function with no arguments will return everything stored in
   * the internal static array by all previous calls (this is the 'manifest'),
   * then will clear the contents of the internal static array.
   *
   * The manifest is an associative array indexed by the Content-ID, which is a
   * unique identifier calculated internally using a hash of the attachment name
   * and the server's host name. The required (always present) keys of this
   * manifest array are:
   * - name: The file name of the attachment.
   * - file: The absolute URL of the file or the contents of the file.
   * - Content-ID: The unique identifier of the file.
   * - Content-Disposition: How the file is attached - either 'inline' or
   *   'attachment'.
   * - Content-Type: MIME type of the file.
   *
   * @param string $url
   *   (optional) The URI or the absolute URL of the file.
   * @param string $content
   *   (optional) The actual file content. This is required if $url is not set.
   * @param string $name
   *   (optional) The file name.
   * @param string $type
   *   (optional) The MIME type of the file.
   * @param string $disposition
   *   (optional) The content disposition. One of 'inline' or 'attachment'.
   *   Defaults to 'inline'.
   *
   * @return mixed
   *   Returned value is one of:
   *   - The Content-ID, if this function is called with either a $url or a
   *     $content argument.
   *   - An array of accumulated metadata indexed by Content-ID if this function
   *     is called with no arguments. The structure of this metadata (the
   *     'manifest') is described above.
   *   - URL if the $url is not a local file and $content is not set.
   */
  public static function mimeMailFile($url = NULL, $content = NULL, $name = '', $type = '', $disposition = 'inline') {
    // A cumulative manifest of metadata about the files that are attached
    // to an email message.
    static $files = [];

    /** @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface $mime_type_guesser */
    $mime_type_guesser = \Drupal::service('file.mime_type.guesser');

    /** @var \Drupal\Core\File\FileSystemInterface $file_system */
    $file_system = \Drupal::service('file_system');

    /** @var \Drupal\Core\Config\ImmutableConfig file_config */
    $file_config = \Drupal::config('system.file');

    /** @var \Symfony\Component\HttpFoundation\Request $current_request */
    $current_request = \Drupal::requestStack()->getCurrentRequest();

    /** @var \Drupal\Core\Session\AccountProxyInterface $current_user */
    $current_user = \Drupal::currentUser();

    /** @var \Drupal\Core\Config\ImmutableConfig $mimemail_config */
    $mimemail_config = \Drupal::config('mimemail.settings');

    if ($url) {
      $image = preg_match('!\.(png|gif|jpg|jpeg)$!i', $url);
      $linkonly = \Drupal::config('mimemail.settings')->get('linkonly');
      // The file exists on the server as-is.
      // Allows for non-web-accessible files.
      if (@is_file($url) && $image && !$linkonly) {
        $file = $url;
      }
      else {
        $url = static::mimeMailUrl($url, TRUE);
        // The $url is absolute, we're done here.
        $scheme = StreamWrapperManager::getScheme($url);
        if ($scheme == 'http' || $scheme == 'https' || preg_match('!mailto:!', $url)) {
          return $url;
        }
        // The $url is a non-local URI that needs to be converted to a URL.
        else {
          $file = $file_system->realpath($url) ? $file_system->realpath($url) : file_create_url($url);
        }
      }
    }
    // We have the actual content.
    elseif ($content) {
      $file = $content;
    }

    if (isset($file) && (@is_file($file) || $content)) {
      $public_path = $file_config->get('default_scheme') . '://';
      $no_access = !$current_user->hasPermission('send arbitrary files');
      $not_in_public_path = mb_strpos($file_system->realpath($file), $file_system->realpath($public_path)) !== 0;
      if (@is_file($file) && $not_in_public_path && $no_access) {
        return $url;
      }

      if (!$name) {
        $name = (@is_file($file)) ? basename($file) : 'attachment.dat';
      }
      if (!$type) {
        // @todo $name will ALWAYS be TRUE here because of the above if{}.
        $type = ($name) ? $mime_type_guesser->guess($name) : $mime_type_guesser->guess($file);
      }

      // Generate a unique ID using the HTTP host requested.
      $id = md5($file) . '@' . $current_request->getHttpHost();

      // Prevent attaching the same file more than once. For example, an image
      // that's part of the theme may be referenced many times in the HTML, but
      // should only be attached once.
      if (!isset($files[$id])) {
        // This is a new file that needs to be attached.
        // Store the metadata in our static $files array indexed by $id.
        $files[$id] = [
          'name' => $name,
          'file' => $file,
          'Content-ID' => $id,
          'Content-Disposition' => $disposition,
          'Content-Type' => $type,
        ];
      }

      // Return the content id for this item.
      return 'cid:' . $id;
    }

    // The $file does not exist and no $content was provided.
    // Return the $url if possible.
    elseif ($url) {
      return $url;
    }

    $ret = $files;
    $files = [];
    $ids = [];

    return $ret;
  }

  /**
   * Helper function to format URLs.
   *
   * @param string $url
   *   The file path.
   * @param bool $to_embed
   *   (optional) Whether the URL is used to embed the file. Defaults to FALSE.
   *
   * @return string
   *   A processed URL.
   */
  public static function mimeMailUrl($url, $to_embed = FALSE) {
    /** @var \Drupal\Core\Config\ImmutableConfig $mimemail_config */
    $mimemail_config = \Drupal::config('mimemail.settings');

    /** @var \Drupal\Core\Language\LanguageManagerInterface $language_manager */
    $language_manager = \Drupal::languageManager();

    $url = urldecode($url);

    $to_link = $mimemail_config->get('linkonly');
    $is_image = preg_match('!\.(png|gif|jpg|jpeg)!i', $url);
    $is_absolute = StreamWrapperManager::getScheme($url) != FALSE || preg_match('!(mailto|callto|tel)\:!', $url);

    // Strip the base path as Uri adds it again at the end.
    $base_path = rtrim(base_path(), '/');
    $url = preg_replace('!^' . $base_path . '!', '', $url, 1);

    if (!$to_embed) {
      if ($is_absolute) {
        return str_replace(' ', '%20', $url);
      }
    }
    else {
      if ($is_image) {
        if ($to_link) {
          // Exclude images from embedding if needed.
          $url = file_create_url($url);
          $url = str_replace(' ', '%20', $url);
        }
        else {
          // Remove security token from URL, this allows for styled image
          // embedding.
          // @see https://drupal.org/drupal-7.20-release-notes
          $url = preg_replace('/\\?itok=.*$/', '', $url);
        }

      }
      return $url;
    }

    $url = str_replace('?q=', '', $url);
    @list($url, $fragment) = explode('#', $url, 2);
    @list($path, $query) = explode('?', $url, 2);

    // If we're dealing with an intra-document reference, return it.
    if (empty($path)) {
      return '#' . $fragment;
    }

    // Get a list of enabled languages.
    $languages = $language_manager->getLanguages(LanguageInterface::STATE_ALL);

    // Default language settings.
    $prefix = '';
    $language = $language_manager->getDefaultLanguage();

    // Check for language prefix.
    $args = explode('/', $path);
    foreach ($languages as $lang) {
      if ($args[1] == $lang->getId()) {
        $prefix = array_shift($args);
        $language = $lang;
        $path = implode('/', $args);
        break;
      }
    }

    parse_str($query, $arr);
    $options = [
      'query' => !empty($arr) ? $arr : [],
      'fragment' => $fragment,
      'absolute' => TRUE,
      'language' => $language,
      'prefix' => $prefix,
    ];

    $url = Url::fromUserInput($path, $options)->toString();

    // If url() added a ?q= where there should not be one, remove it.
    if (preg_match('!^\?q=*!', $url)) {
      $url = preg_replace('!\?q=!', '', $url);
    }

    $url = str_replace('+', '%2B', $url);
    return $url;
  }

  /**
   * Builds a multipart body.
   *
   * @param array $parts
   *   An associative array containing the parts to be included:
   *   - name: A string containing the name of the attachment.
   *   - content: A string containing textual content.
   *   - file: A string containing file content.
   *   - Content-Type: A string containing the content type of either file or
   *     content. Mandatory for content, optional for file. If not present, it
   *     will be derived from file the file if mime_content_type is available.
   *     If not, application/octet-stream is used.
   *   - Content-Disposition: (optional) A string containing the disposition.
   *     Defaults to inline.
   *   - Content-Transfer-Encoding: (optional) Base64 is assumed for files,
   *     8bit for other content.
   *   - Content-ID: (optional) for in-mail references to attachments.
   *   Name is mandatory, one of content and file is required, they are
   *   mutually exclusive.
   * @param string $content_type
   *   (optional) A string containing the content-type for the combined
   *   message. Defaults to multipart/mixed.
   * @param bool $sub_part
   *   (optional) FALSE to return the entire body, TRUE to return only the
   *   body sub-part.
   *
   * @return array
   *   An associative array containing the following elements:
   *   - body: A string containing the MIME-encoded multipart body of a mail.
   *   - headers: An array that includes some headers for the mail to be sent.
   */
  public static function mimeMailMultipartBody(array $parts, $content_type = 'multipart/mixed; charset=utf-8', $sub_part = FALSE) {
    // Control variable to avoid boundary collision.
    static $part_num = 0;

    /** @var \Symfony\Component\HttpFoundation\File\MimeType\MimeTypeGuesserInterface $mime_type_guesser */
    $mime_type_guesser = \Drupal::service('file.mime_type.guesser');

    /** @var \Drupal\Component\Datetime\TimeInterface $time */
    $time = \Drupal::time();

    // Compute boundary hash.
    $request_time = $time->getRequestTime();
    $boundary = sha1(uniqid($request_time, TRUE)) . $part_num++;

    // Header for mail body.
    $body = '';
    $headers = ['Content-Type' => "$content_type; boundary=\"$boundary\""];
    if (!$sub_part) {
      $headers['MIME-Version'] = '1.0';
      $body = 'This is a multi-part message in MIME format.' . static::CRLF;
    }

    // Part headers and contents.
    foreach ($parts as $part) {
      $part_headers = [];

      if (isset($part['Content-ID'])) {
        $part_headers['Content-ID'] = '<' . $part['Content-ID'] . '>';
      }

      if (isset($part['Content-Type'])) {
        $part_headers['Content-Type'] = $part['Content-Type'];
      }

      if (isset($part['Content-Disposition'])) {
        $part_headers['Content-Disposition'] = $part['Content-Disposition'];
      }
      elseif (mb_strpos($part['Content-Type'], 'multipart/alternative') === FALSE) {
        $part_headers['Content-Disposition'] = 'inline';
      }

      if (isset($part['Content-Transfer-Encoding'])) {
        $part_headers['Content-Transfer-Encoding'] = $part['Content-Transfer-Encoding'];
      }

      // Mail content provided as a string.
      if (isset($part['content']) && $part['content']) {
        if (!isset($part['Content-Transfer-Encoding'])) {
          $part_headers['Content-Transfer-Encoding'] = '8bit';
        }
        $part_body = $part['content'];
        if (isset($part['name'])) {
          $part_headers['Content-Type'] .= '; name="' . $part['name'] . '"';
          $part_headers['Content-Disposition'] .= '; filename="' . $part['name'] . '"';
        }

        // Mail content references in a filename.
      }
      else {
        if (!isset($part['Content-Transfer-Encoding'])) {
          $part_headers['Content-Transfer-Encoding'] = 'base64';
        }

        if (!isset($part['Content-Type'])) {
          $part['Content-Type'] = $mime_type_guesser->guess($part['file']);
        }

        if (isset($part['name'])) {
          $part_headers['Content-Type'] .= '; name="' . $part['name'] . '"';
          $part_headers['Content-Disposition'] .= '; filename="' . $part['name'] . '"';
        }

        if (isset($part['file'])) {
          $file = (is_file($part['file'])) ? file_get_contents($part['file']) : $part['file'];
          $part_body = chunk_split(base64_encode($file), 76, static::CRLF);
        }
      }

      $body .= static::CRLF . "--$boundary" . static::CRLF;
      $body .= static::mimeMailRfcHeaders($part_headers) . static::CRLF;
      $body .= isset($part_body) ? $part_body : '';
    }
    $body .= static::CRLF . "--$boundary--" . static::CRLF;

    return ['headers' => $headers, 'body' => $body];
  }

  /**
   * Makes mail message and MIME part headers RFC2822-compliant.
   *
   * Implements and enforces header field formatting including line length,
   * line termination, and line folding as specified in RFC2822.
   *
   * @param array $headers
   *   An array of headers where the keys are header field names and the
   *   values are the header field bodies.
   *
   * @return string
   *   One string containing a concatenation of all formatted header fields,
   *   suitable for directly including in an email.
   *
   * @see http://tools.ietf.org/html/rfc2822#section-2.2
   */
  public static function mimeMailRfcHeaders(array $headers) {
    // Use RFC2822 terminology for all variables - 'header' refers to the
    // collection of all header fields. Each header field is composed of
    // a header field name followed by a colon followed by the header field
    // body, terminated by CRLF.
    $header = '';
    foreach ($headers as $field_name => $field_body) {
      // Header field names should not have leading or trailing whitespace.
      $field_name = trim($field_name);

      // Collapse spaces and get rid of newline characters.
      $field_body = trim($field_body);
      $field_body = preg_replace('/(\s+|\n|\r)/', ' ', $field_body);

      // Fold headers if they're too long.
      // A CRLF may be inserted before any WSP.
      // @see http://tools.ietf.org/html/rfc2822#section-2.2.3
      $header_field = $field_name . ': ' . $field_body;
      if (mb_strlen($header_field) >= static::RFC2822_MAXLEN) {
        // If there's a semicolon, use that to separate.
        if (count($array = preg_split('/;\s*/', $header_field)) > 1) {
          $header_field = trim(implode(';' . static::CRLF . ' ', $array));
        }
        // Always try to wordwrap.
        $header_field = wordwrap($header_field, static::RFC2822_MAXLEN, static::CRLF . ' ', FALSE);
      }
      $header .= $header_field . static::CRLF;
    }

    return $header;
  }

  /**
   * Gives useful defaults for standard email headers.
   *
   * @param array $headers
   *   Message headers.
   * @param string $from
   *   The address of the sender.
   *
   * @return array
   *   Overwritten headers.
   */
  public static function mimeMailHeaders(array $headers, $from = NULL) {
    /** @var \Drupal\Core\Config\ImmutableConfig $site_config */
    $site_config = \Drupal::config('system.site');

    // Overwrite standard headers.
    if ($from) {
      $default_from = $site_config->get('mail');
      if (!isset($headers['From']) || $headers['From'] == $default_from) {
        $headers['From'] = $from;
      }
      if (!isset($headers['Sender']) || $headers['Sender'] == $default_from) {
        $headers['Sender'] = $from;
      }
      // This may not work. The MTA may rewrite the Return-Path.
      if (!isset($headers['Return-Path']) || $headers['Return-Path'] == $default_from) {
        if (preg_match('/[a-z\d\-\.\+_]+@(?:[a-z\d\-]+\.)+[a-z\d]{2,4}/i', $from, $matches)) {
          $headers['Return-Path'] = "<$matches[0]>";
        }
      }
    }

    // Convert From header if it is an array.
    if (is_array($headers['From'])) {
      $headers['From'] = static::mimeMailAddress($headers['From']);
    }

    // Run all headers through mime_header_encode() to convert non-ASCII
    // characters to an RFC compliant string, similar to drupal_mail().
    foreach ($headers as $field_name => $field_body) {
      $headers[$field_name] = Unicode::mimeHeaderEncode($field_body);
    }

    return $headers;
  }

}
