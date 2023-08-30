<?php

namespace Drupal\openid_connect\Controller;

use Drupal\Core\Controller\ControllerBase;
use Drupal\openid_connect\OpenIDConnectClientEntityInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;

/**
 * Controller for operations on the OpenID Connect clients.
 */
class OpenIDConnectClientController extends ControllerBase {

  /**
   * Build the OpenID Connect client instance add form.
   *
   * @param string $plugin_id
   *   The plugin ID for the OpenID Connect client instance.
   *
   * @return array
   *   The OpenID Connect client add form.
   *
   * @throws \Drupal\Component\Plugin\Exception\InvalidPluginDefinitionException
   * @throws \Drupal\Component\Plugin\Exception\PluginNotFoundException
   */
  public function add(string $plugin_id): array {
    // Create an OpenID Connect client entity.
    $entity = $this->entityTypeManager()->getStorage('openid_connect_client')->create(['plugin' => $plugin_id]);

    return $this->entityFormBuilder()->getForm($entity, 'add');
  }

  /**
   * Enable an OpenID Connect client.
   *
   * @param \Drupal\openid_connect\OpenIDConnectClientEntityInterface $openid_connect_client
   *   The OpenID Connect client entity to enable.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect back to the client list page.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function enable(OpenIDConnectClientEntityInterface $openid_connect_client): RedirectResponse {
    $openid_connect_client->enable()->save();
    $this->messenger()->addMessage($this->t('The %label client has been enabled.', ['%label' => $openid_connect_client->label()]));

    // Return to the listing page.
    return $this->redirect('entity.openid_connect_client.list', [], []);
  }

  /**
   * Disable an OpenID Connect client.
   *
   * @param \Drupal\openid_connect\OpenIDConnectClientEntityInterface $openid_connect_client
   *   The OpenID Connect client entity to disable.
   *
   * @return \Symfony\Component\HttpFoundation\RedirectResponse
   *   A redirect back to the client list page.
   *
   * @throws \Drupal\Core\Entity\EntityStorageException
   */
  public function disable(OpenIDConnectClientEntityInterface $openid_connect_client): RedirectResponse {
    $openid_connect_client->disable()->save();
    $this->messenger()->addMessage($this->t('The %label client has been disabled.', ['%label' => $openid_connect_client->label()]));

    // Return to the listing page.
    return $this->redirect('entity.openid_connect_client.list', [], []);
  }

}
