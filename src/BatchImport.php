<?php

namespace Drupal\syncer;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Symfony\Component\Yaml\Yaml;
use ZipArchive;

/**
 * Batch export.
 */
class BatchImport implements BatchInterface {

  /**
   * {@inheritdoc}
   */
  public function process($content, $entity_storage, &$context) {
    try {
      $entity_storage->create($content)->save();
    }
    catch (\Exception $e) {}
  }

  /**
   * {@inheritdoc}
   */
  public function finished($success, array $results, array $operations) {
    $messenger = \Drupal::messenger();

    if ($success) {
      $messenger->addMessage(t('@count entity has been imported successfully.', ['@count' => count($results)]));
    }
  }

}
