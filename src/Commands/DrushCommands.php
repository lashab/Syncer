<?php

namespace Drupal\syncer\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drush\Commands\DrushCommands as OriginalDrushCommands;
use Symfony\Component\Yaml\Yaml;
use Drupal\syncer\BatchExport;
use Drupal\syncer\BatchImport;
use Drupal\Component\Utility\Unicode;
use ZipArchive;

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
   * @aliases snce
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


  /**
   * Import content.
   *
   * @command syncer:import
   * @validate-module-enabled syncer
   * @aliases snci
   */
  public function import($type) {
    /** @var mixed $entity_storage */
    $entity_storage = $this->entityTypeManager->getStorage($type);

    $zip_dir = dirname(DRUPAL_ROOT . '../') . '/syncer/content/' . $type . '.zip';

    if (!file_exists($zip_dir)) {
      return $this->io->warning($zip_dir . ' not found');
    }

    /** @var \ZipArchive $zip */
    $zip = new ZipArchive();
    $zip->open($zip_dir);
    $count = $zip->numFiles;

    if (!$count) {
      return $this->io()->warning('nothing to import');
    }

    for ($i = 0; $i < $count; $i++) {
      if ($stat = $zip->statIndex($i)) {
        $content = $zip->getStream($stat['name']);   
        $operations[] = [
          [BatchImport::class, 'process'],
          [Yaml::parse(stream_get_contents($content)), $entity_storage],
        ];
      }
    }

    $zip->close();

    batch_set([
      'operations' => $operations,
      'finished' => [BatchImport::class, 'finished'],
    ]);

    drush_backend_batch_process();
  }

}
