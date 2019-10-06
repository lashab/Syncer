<?php

namespace Drupal\syncer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Yaml\Yaml;
use ZipArchive;

/**
 * Batch export.
 */
class BatchExport implements BatchInterface {

  /**
   * {@inheritdoc}
   */
  public function process($content, $entity_storage, &$context) {
    $data = $entity_storage->load($content)->toArray();
    $type = $entity_storage->getEntityType()->id();
    $zip = new ZipArchive();
    $zip->open(dirname(DRUPAL_ROOT . '../') . '/content/sync/' . $type . '.zip', ZipArchive::CREATE);

    if ($zip->addFromString(sprintf('%s-%s.yml', $type, $content), Yaml::dump($data, 2, 2))) {
      $context['results'][] = $content;
      $context['message'] = t('Export @type - @id', [
        '@type' => $type,
        '@id' => $id,
      ]);
    }

    $zip->close();
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
