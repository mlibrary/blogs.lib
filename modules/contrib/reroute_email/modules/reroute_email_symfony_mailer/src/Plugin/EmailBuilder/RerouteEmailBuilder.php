<?php

namespace Drupal\reroute_email_symfony_mailer\Plugin\EmailBuilder;

use Drupal\reroute_email\RerouteEmailHelpers;
use Drupal\symfony_mailer\EmailFactoryInterface;
use Drupal\symfony_mailer\EmailInterface;
use Drupal\symfony_mailer\Processor\EmailBuilderBase;

/**
 * Defines the Reroute Email Builder.
 *
 * @EmailBuilder(
 *   id = "reroute_email",
 *   sub_types = {
 *     "test_email_form" = @Translation("Test Email Form"),
 *   },
 *   common_adjusters = {"reroute_email"},
 * )
 */
class RerouteEmailBuilder extends EmailBuilderBase {

  use RerouteEmailHelpers;

  /**
   * {@inheritdoc}
   */
  public function createParams(EmailInterface $email, array $params = []) {
    $email->setParam('email_property', $params);
  }

  /**
   * {@inheritdoc}
   */
  public function fromArray(EmailFactoryInterface $factory, array $message) {
    return $factory->newTypedEmail($message['module'], $message['key'], [
      'to' => $message['to'],
      'cc' => $message['params']['cc'] ?? NULL,
      'bcc' => $message['params']['bcc'] ?? NULL,
      'subject' => $message['params']['subject'] ?? NULL,
      'body' => $message['params']['body'] ?? NULL,
    ]);
  }

  /**
   * {@inheritdoc}
   */
  public function build(EmailInterface $email): void {
    parent::build($email);
    $params = $email->getParam('email_property');
    if ($params['to']) {
      $email->setTo($this->extractEmailAddresses($params['to'], FALSE));
    }
    if ($params['cc']) {
      $email->setCc($this->extractEmailAddresses($params['cc'], FALSE));
    }
    if ($params['bcc']) {
      $email->setBcc($this->extractEmailAddresses($params['bcc'], FALSE));
    }
    if ($params['subject']) {
      $email->setSubject($params['subject']);
    }
    if ($params['body']) {
      $email->setBody($params['body']);
    }
  }

}
