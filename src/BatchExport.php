<?php

namespace Drupal\syncer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Yaml\Yaml;

/**
 * Batch export.
 */
class BatchExport implements BatchInterface {

  /**
   * {@inheritdoc}
   */
  public function process(int $id, $entity_storage, &$context) {
    $data = $entity_storage->load($id)->toArray();
    if (file_save_data(Yaml::dump($data, 2, 2), file_default_scheme() . '://' . '/content/content.yml')) {
      $context['results'][] = $id;
      $context['message'] = t('Export @type - @id', [
        '@type' => $entity_storage->getEntityType()->id(),
        '@id' => $id,
      ]);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function finished($success, array $results, array $operations) {
    $messenger = \Drupal::messenger();

    if ($success) {
      $messenger->addMessage(t('@count entity has been exported successfully.', ['@count' => count($results)]));
    }
  }

}
