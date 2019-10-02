<?php

namespace Drupal\syncer;

/**
 * Batch interface.
 */
interface BatchInterface {

  /**
   * Process batch operations.
   * 
   * @param int $id
   *   A node id.
   * @param object $context
   *   A batch context.
   */
  public function process(int $id, $entity_storage, &$context);

  /**
   * Batch finish handler.
   *
   * @param bool $success
   *   A boolean indicating whether the batch has completed successfully.
   * @param mixed $results
   *   The value set in $context['results'] by callback_batch_operation().
   * @param array $operations
   *   Contains the operations that remained unprocessed.
   */
  public function finished($success, array $results, array $operations);

}
