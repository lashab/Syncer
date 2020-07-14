<?php

namespace Drupal\syncer;

/**
 * Batch export.
 */
class BatchImport implements BatchInterface {

  /**
   * {@inheritdoc}
   */
  public static function process($entity_type, $data, &$context) {
    try {
      /** @var \Drupal\Core\Entity\EntityFieldManagerInterface $entity_type_manager */
      $entity_type_manager = \Drupal::entityTypeManager();
      /** @var \Drupal\Core\Entity\ContentEntityType $entity_definition */
      $entity_definition = $entity_type_manager->getDefinition($entity_type);
      $entity_type_bundle_id = $entity_definition->getKey('bundle');

      if (isset($data[$entity_type_bundle_id]) && $data[$entity_type_bundle_id]) {
        $content = $entity_type_manager
          ->getStorage($entity_type)
          ->create($data);

        if ($content->save()) {
          $context['results']['content'][] = $content;
        }
      }
    }
    catch (\Exception $e) {

    }
  }

  /**
   * {@inheritdoc}
   */
  public static function finished($success, array $results, array $operations) {
    /** @var \Drupal\Core\Messenger\Messenger $messenger */
    $messenger = \Drupal::messenger();

    if ($success && isset($results['content'])) {
      $messenger->addMessage(t('@count content has been imported.', ['@count' => count($results['content'])]));
    }
    else {
      $messenger->addMessage(t('0 content has been imported.'));
    }
  }

}
