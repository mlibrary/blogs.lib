<?php

namespace Drupal\mimemail\Plugin\Mail;

use Drupal\Component\Utility\EmailValidatorInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Extension\ModuleHandlerInterface;
use Drupal\Core\Mail\MailFormatHelper;
use Drupal\Core\Mail\Plugin\Mail\PhpMail;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Render\RendererInterface;
use Drupal\mimemail\Utility\MimeMailFormatHelper;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Defines the default Drupal mail backend, using PHP's native mail() function.
 *
 * @Mail(
 *   id = "mime_mail",
 *   label = @Translation("Mime Mail mailer"),
 *   description = @Translation("Sends MIME-encoded emails with embedded images and attachments.")
 * )
 */
class MimeMail extends PhpMail implements ContainerFactoryPluginInterface {

  /**
   * The module handler service.
   *
   * @var \Drupal\Core\Extension\ModuleHandlerInterface
   */
  protected $moduleHandler;

  /**
   * The email.validator service.
   *
   * @var \Drupal\Component\Utility\EmailValidatorInterface
   */
  protected $emailValidator;

  /**
   * The renderer service.
   *
   * @var \Drupal\Core\Render\RendererInterface
   */
  protected $renderer;

  /**
   * MimeMail plugin constructor.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory service.
   * @param \Drupal\Core\Extension\ModuleHandlerInterface $module_handler
   *   The module handler service.
   * @param \Drupal\Component\Utility\EmailValidatorInterface $email_validator
   *   The email validator service.
   * @param \Drupal\Core\Render\RendererInterface $renderer
   *   The renderer service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, ModuleHandlerInterface $module_handler, EmailValidatorInterface $email_validator, RendererInterface $renderer) {
    parent::__construct();

    // Replace the parent constructor's configFactory because the parent
    // statically initializes it instead of injecting it.
    $this->configFactory = $config_factory;

    $this->moduleHandler = $module_handler;
    $this->emailValidator = $email_validator;
    $this->renderer = $renderer;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition) {
    return new static(
      $container->get('config.factory'),
      $container->get('module_handler'),
      $container->get('email.validator'),
      $container->get('renderer')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function format(array $message) {
    if (is_array($message['body'])) {
      $message['body'] = implode("\n\n", $message['body']);
    }

    if (preg_match('/plain/', $message['headers']['Content-Type'])) {
      if (!$format = $this->configFactory->get('mimemail.settings')->get('format')) {
        $format = filter_fallback_format();
      }
      $langcode = $message['langcode'] ?? '';
      $message['body'] = check_markup($message['body'], $format, $langcode);
    }

    $message = $this->prepareMessage($message);

    return $message;
  }

  /**
   * Prepares the message for sending.
   *
   * @param array $message
   *   An array containing the message data. The optional parameters are:
   *   - plain: (optional) Whether to send the message as plaintext only or
   *     HTML. If this evaluates to TRUE the message will be sent as plaintext.
   *   - plaintext: (optional) Plaintext portion of a multipart email.
   *   - attachments: (optional) An array where each element is an array that
   *     describes an attachment. Existing files may be added by path while
   *     dynamically-generated files may be added by content. Each internal
   *     array contains the following elements:
   *     - filepath: Relative Drupal path to an existing file
   *       (filecontent is NULL).
   *     - filecontent: The actual content of the file (filepath is NULL).
   *     - filename: (optional) The filename of the file.
   *     - filemime: (optional) The MIME type of the file.
   *     The array of arrays looks something like this:
   *     @code
   *     [
   *       0 => [
   *         'filepath' => '/sites/default/files/attachment.txt',
   *         'filecontent' => NULL,
   *         'filename' => 'attachment1.txt',
   *         'filemime' => 'text/plain',
   *       ],
   *       1 => [
   *         'filepath' => NULL,
   *         'filecontent' => 'This is the contents of my second attachment.',
   *         'filename' => 'attachment2.txt',
   *         'filemime' => 'text/plain',
   *       ],
   *     ]
   *     @endcode
   *
   * @return array
   *   All details of the message.
   */
  protected function prepareMessage(array $message) {
    $module = $message['module'];
    $key = $message['key'];
    $to = $message['to'];
    $from = $message['from'];
    $subject = $message['subject'];
    $body = $message['body'];

    $headers = $message['params']['headers'] ?? [];
    $plain = $message['params']['plain'] ?? NULL;
    $plaintext = $message['params']['plaintext'] ?? NULL;
    $attachments = $message['params']['attachments'] ?? [];

    $site_name = $this->configFactory->get('system.site')->get('name');
    $site_mail = $this->configFactory->get('system.site')->get('mail');
    $simple_address = $this->configFactory->get('mimemail.settings')->get('simple_address');

    // Override site mail's default sender.
    if ((empty($from) || $from == $site_mail)) {
      $mimemail_name = $this->configFactory->get('mimemail.settings')->get('name');
      $mimemail_mail = $this->configFactory->get('mimemail.settings')->get('mail');
      $from = [
        'name' => !empty($mimemail_name) ? $mimemail_name : $site_name,
        'mail' => !empty($mimemail_mail) ? $mimemail_mail : $site_mail,
      ];
    }

    if (empty($body)) {
      // Body is empty, this is a plaintext message.
      $plain = TRUE;
    }
    // Try to determine recipient's text mail preference.
    elseif (is_null($plain)) {
      if (is_string($to) && $this->emailValidator->isValid($to)) {
        $user_plaintext_field = $this->configFactory->get('mimemail.settings')->get('user_plaintext_field');
        if (is_object($account = user_load_by_mail($to)) && $account->hasField($user_plaintext_field)) {
          /** @var boolean $plain */
          $plain = $account->{$user_plaintext_field}->value;
          // Might as well pass the user object to the address function.
          $to = $account;
        }
      }
    }

    // MailFormatHelper::htmlToText() removes \r and adds \n both directly and
    // within the utility method MailFormatHelper::wrapMailLine(). Subject
    // headers can't contain \n characters, so we remove those here.
    $subject = str_replace(["\n"], '', trim(MailFormatHelper::htmlToText($subject)));

    $body = [
      '#theme' => 'mimemail_message',
      '#module' => $module,
      '#key' => $key,
      '#recipient' => $to,
      '#subject' => $subject,
      '#body' => $body,
    ];

    $body = $this->renderer->renderPlain($body);

    $plain = $plain || $this->configFactory->get('mimemail.settings')->get('textonly');
    $from = MimeMailFormatHelper::mimeMailAddress($from);
    $mail = MimeMailFormatHelper::mimeMailHtmlBody($body, $subject, $plain, $plaintext, $attachments);
    $headers = array_merge($message['headers'], $headers, $mail['headers']);

    $message['to'] = MimeMailFormatHelper::mimeMailAddress($to, $simple_address);
    $message['from'] = $from;
    $message['subject'] = $subject;
    $message['body'] = $mail['body'];
    $message['headers'] = MimeMailFormatHelper::mimeMailHeaders($headers, $from);

    return $message;
  }

}
