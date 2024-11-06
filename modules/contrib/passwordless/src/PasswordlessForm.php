<?php

namespace Drupal\passwordless;

use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormBuilderInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Password\PasswordGeneratorInterface;
use Drupal\Core\Routing\RedirectDestinationInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Url;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Form-related hook implementations.
 */
class PasswordlessForm implements ContainerInjectionInterface {

  use StringTranslationTrait;

  /**
   * The form_builder service.
   *
   * @var \Drupal\Core\Form\FormBuilderInterface
   */
  protected $formBuilder;

  /**
   * The current request.
   *
   * @var \Symfony\Component\HttpFoundation\Request
   */
  protected $request;

  /**
   * The redirect.destination service.
   *
   * @var \Drupal\Core\Routing\RedirectDestinationInterface
   */
  protected $redirectDestination;

  /**
   * The password_generator service.
   *
   * @var \Drupal\Core\Password\PasswordGeneratorInterface
   */
  protected $passwordGenerator;

  /**
   * PasswordlessForm constructor.
   *
   * @param \Drupal\Core\Form\FormBuilderInterface $form_builder
   *   The form_builder service.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request_stack service.
   * @param \Drupal\Core\Routing\RedirectDestinationInterface $redirect_destination
   *   The redirect.destination service.
   * @param \Drupal\Core\Password\PasswordGeneratorInterface $password_generator
   *   The password_generator service.
   */
  public function __construct(
    FormBuilderInterface $form_builder,
    RequestStack $request_stack,
    RedirectDestinationInterface $redirect_destination,
    PasswordGeneratorInterface $password_generator
  ) {
    $this->formBuilder = $form_builder;
    $this->request = $request_stack->getCurrentRequest();
    $this->redirectDestination = $redirect_destination;
    $this->passwordGenerator = $password_generator;
  }

  /**
   * {@inheritDoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('form_builder'),
      $container->get('request_stack'),
      $container->get('redirect.destination'),
      $container->get('password_generator')
    );
  }

  /**
   * Implements hook_form_alter().
   */
  public function alter(&$form, FormStateInterface $form_state, $form_id) {
    $mail_description = $this->t('A valid email address. All emails from the system will be sent to this address. The email address is not made public and will only be used to send you a login link or if you wish to receive certain news or notifications by email.');

    switch ($form_id) {
      case 'user_login_form':
        // Replace form built at Drupal\user\Plugin\Block\UserLoginBlock.
        $form = $this->formBuilder->getForm('Drupal\passwordless\Form\PasswordlessLoginForm');
        unset($form['name']['#attributes']['autofocus']);
        unset($form['name']['#description']);
        $form['name']['#size'] = 15;
        $form['#action'] = Url::fromRoute(
          '<current>',
          [],
          [
            'query' => $this->redirectDestination->getAsArray(),
            'external' => FALSE,
          ])->toString();
        break;

      case 'user_admin_settings':
        $form['email_password_reset']['#title'] = $this->t('Login-link request');
        $form['email_password_reset']['#description'] = $this->t('Edit the email messages sent to users who request a login link.');
        break;

      case 'user_register_form':
        if (!empty($form['account']['mail'])) {
          $form['account']['mail']['#description'] = $mail_description;
          $form['account']['mail']['#required'] = TRUE;
        }
        // Hides the password field, and populates it with a random password.
        $form['account']['pass']['#type'] = 'value';
        $form['account']['pass']['#value'] = sha1($this->passwordGenerator->generate());
        break;

      case 'user_form':
        $form_state->set('user_pass_reset', 1);
        $form['account']['mail']['#description'] = $mail_description;
        $validate_unset = array_search('user_validate_current_pass', $form['#validate']);
        if (!empty($validate_unset)) {
          unset($form['#validate'][$validate_unset]);
        }
        unset($form['account']['pass'], $form['account']['current_pass']);
        break;

      case 'user_pass_reset':
        $form['#title'] = $this->t('Log in');
        $build_info = $form_state->getBuildInfo();
        // See args at \Drupal\user\Form\UserPasswordResetForm::buildForm().
        /** @var Drupal\Core\Session\AccountInterface $user */
        [$user, $expiration_date, $timestamp, $hash] = $build_info['args'];

        if ($expiration_date) {
          $message = $this->t('<p>This is a one-time login for %user_name and will expire on %expiration_date.</p><p>Click on this button to log in to the site.</p>',
            [
              '%user_name' => $user->getAccountName(),
              '%expiration_date' => $expiration_date,
            ]
          );
        }
        else {
          $message = $this->t('<p>This is a one-time login for %user_name.</p><p>Click on this button to log in to the site.</p>', ['%user_name' => $user->getAccountName()]);
        }

        $form['message'] = ['#markup' => $message];

        // Add the destination to the action route.
        if ($destination = $this->request->query->get('destination')) {
          $form['#action'] = Url::fromRoute('user.reset.login', [
            'uid' => $user->id(),
            'timestamp' => $timestamp,
            'hash' => $hash,
          ],
          ['query' => ['destination' => $destination]],
          )->toString();
        }
        break;
    }
  }

}
