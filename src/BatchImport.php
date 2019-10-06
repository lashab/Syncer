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
      $id = $entity_storage->getQuery()->condition($id, $content[$id])->execute();

      if (!$id) {
        $type = $entity_storage->getEntityType()->id();
        /** @var mixed $entity */
        $entity = $entity_storage->create($content);
        if ($entity->save()) {
          $context['results'][] = $entity;
          $context['message'] = t('Import succeed @type - @title', [
            '@type' => $type,
            '@title' => $entity->label(),
          ]);
        }
      }
      else {
        $entity = $entity_storage->load($id);
        $context['message'] = t('Import failed @type - @title', [
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
      $messenger->addMessage(t('@count entity has been imported successfully.', ['@count' => count($results)]));
    }
    else {
      $messenger->addMessage(t('nothing to import'));
    }
  }

}
