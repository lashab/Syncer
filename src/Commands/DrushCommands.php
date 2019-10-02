<?php

namespace Drupal\syncer\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drush\Commands\DrushCommands as OriginalDrushCommands;
use Symfony\Component\Yaml\Yaml;

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
    if (!$type || !$options['type']) {
      return $this->io()->error('type is required');
    }

    try {
      /** @var mixed $entity_storage */
      $entity_storage = $this->entityTypeManager->getStorage($type);
      /** @var \Drupal\Core\Entity\Query\Sql\Query $query */
      $query = $entity_storage->getQuery();

      if (isset($options['type']) && $options['type']) {
        $query->condition('type', $options['type']);
      }

      $data = $query->execute();
      $operations = [];

      foreach ($data as $id) {
        /** @var mixed $entity_storage */
        $operations[] = [
          
        ];
      }

      dump($operations);
    }
    catch (\Exception $e) {
      $this->io()->error($e);
    }

    $batch = [
      'title' => t('Updating @num node(s)', ['@num' => $numOperations]),
      'operations' => $operations,
      'finished' => '\Drupal\drush9_batch_processing\BatchService::processMyNodeFinished',
    ];

    batch_set($batch);

    drush_backend_batch_process();


    //$this->io()->success(sprintf('%d contacts updated.', $count));
  }

}
