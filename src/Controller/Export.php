<?php

namespace Drupal\syncer\Controller;

use Symfony\Component\DependencyInjection\ContainerInterface;
use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Export data.
 */
class Export extends ControllerBase {

  /**
   * Export data.
   *
   * @param string $name
   *   The file name to export.
   */
  public function data($name) {
    $headers = [
      'Content-Type' => 'application/zip, application/octet-stream',
      'Content-Description' => 'File Download',
      'Content-Disposition' => 'attachment; filename=' . $name
    ];

    return new BinaryFileResponse('public://' . $name, 200, $headers, true);
  }
}
