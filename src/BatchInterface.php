<?php

namespace Drupal\syncer;

/**
 * Batch interface.
 */
interface BatchInterface {

  /**
   * Process batch operations.
   *
   * @param string|null $entity_type
   *   An entity type.
   * @param mixed $data
   *   A data to be processed.
   * @param array $context
   *   A batch context.
   */
  public static function process($entity_type, $data, &$context);

  /**
   * Batch finish handler.
   *
   * @param bool $success
   *   A boolean indicating whether the batch has completed successfully.
   * @param array $results
   *   The value set in $context['results'] by callback_batch_operation().
   * @param array $operations
   *   Contains the operations that remained unprocessed.
   */
  public static function finished($success, array $results, array $operations);

}
