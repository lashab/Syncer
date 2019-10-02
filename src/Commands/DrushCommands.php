<?php

namespace Drupal\syncer\Commands;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Database\Connection;
use Drush\Commands\DrushCommands as OriginalDrushCommands;

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
   * Import content.
   *
   * @command syncer:import
   * @validate-module-enabled syncer
   * @aliases snim snex
   */
  public function import() {
    $this->io()->success(sprintf('%d contacts updated.', $count));
  }

}
