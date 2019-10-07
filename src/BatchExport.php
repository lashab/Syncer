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
    try {
      /** @var mixed $entity */
      $entity = $entity_storage->load($content);
      $data = $entity->toArray();
      $type = $entity_storage->getEntityType()->id();
      $zip_dir = dirname(DRUPAL_ROOT . '../') . '/syncer/content';

      if (file_prepare_directory($zip_dir, FILE_CREATE_DIRECTORY)) {
        /** @var ZIpArchive $zip */
        $zip = new ZipArchive();
        $zip->open(sprintf('%s/%s.zip', $zip_dir, $type), ZipArchive::CREATE);

        if ($zip->addFromString(sprintf('%s-%s.yml', $type, $content), Yaml::dump($data, 2, 2))) {
          $context['results'][] = $content;
          $context['message'] = t('Export: @title', [
            '@type' => $type,
            '@title' => $entity->label(),
          ]);
        }

        $zip->close();
      }
    }
    catch (\Exception $e) {

    }
  }

  /**
   * {@inheritdoc}
   */
  public function finished($success, array $results, array $operations) {
    $messenger = \Drupal::messenger();

    if ($success) {
      $messenger->addMessage(t('@count entity has been exported.', ['@count' => count($results)]));
    }
  }

}
