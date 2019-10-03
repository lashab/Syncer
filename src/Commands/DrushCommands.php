<?php

namespace Drupal\syncer\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drush\Commands\DrushCommands as OriginalDrushCommands;
use Symfony\Component\Yaml\Yaml;
use Drupal\syncer\BatchExport;
use Drupal\Component\Utility\Unicode;

/**
 * Drush commands.
 */
class DrushCommands extends OriginalDrushCommands {

  /**
   * Entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The database service.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $database;

  /**
   * Constructs DrushCommands object.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   Entity type manager service.
   */
  public function __construct(Connection $database, EntityTypeManagerInterface $entity_type_manager) {
    $this->database = $database;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * Export content.
   *
   * @command syncer:export
   * @validate-module-enabled syncer
   * @aliases se
   */
  public function export($type = '', $options = ['type' => NULL]) {
    if (!$type) {
      return $this->io()->error('type is required');
    }

    $operations = [];

    try {
      /** @var mixed $entity_storage */
      $entity_storage = $this->entityTypeManager->getStorage($type);
      /** @var \Drupal\Core\Entity\Query\Sql\Query $query */
      $query = $entity_storage->getQuery();

      if (isset($options['type']) && $options['type']) {
        $query->condition('type', $options['type']);
      }

      $data = $query->execute();

      foreach ($data as $id) {
        /** @var mixed $entity_storage */
        $operations[] = [
          [BatchExport::class, 'process'],
          [$id, $entity_storage],
        ];
      }
    }
    catch (\Exception $e) {
      $this->io()->error($e);
    }

    batch_set([
      'operations' => $operations,
      'finished' => [BatchExport::class, 'finished'],
    ]);

    drush_backend_batch_process();
  }

}
