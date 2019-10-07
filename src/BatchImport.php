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
      $id = $entity_storage->getEntityType()->getKeys()['id'];
      $ids = $entity_storage->getQuery()->condition($id, reset($content[$id][0]))->execute();

      if (!$ids) {
        $type = $entity_storage->getEntityType()->id();
        /** @var mixed $entity */
        $entity = $entity_storage->create($content);
        if ($entity->save()) {
          $context['results'][] = $entity;
          $context['message'] = t('Import @title', [
            '@type' => $type,
            '@title' => $entity->label(),
          ]);
        }
      }
      else {
        $entity = $entity_storage->load(reset($ids));
        $context['message'] = t('Import not succeed (reason: content exists) @title', [
          '@type' => $type,
          '@title' => $entity->label(),
        ]); 
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
    $count = count($results);

    if ($success && $count) {
      $messenger->addMessage(t('@count entity has been imported.', ['@count' => count($results)]));
    }
    else {
      $messenger->addMessage(t('nothing to import'));
    }
  }

}
