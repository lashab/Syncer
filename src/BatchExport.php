<?php

namespace Drupal\syncer;

use Drupal\Core\Url;
use Symfony\Component\Yaml\Yaml;
use ZipArchive;

/**
 * Batch export.
 */
class BatchExport implements BatchInterface {

  /**
   * {@inheritdoc}
   */
  public static function process($entity_type, $data, &$context) {
    try {
      /** @var \ZipArchive $zip */
      $zip = new ZipArchive();
      $zip->open(\Drupal::service('file_system')->realpath("public://$entity_type.zip"));

      if ($zip->addFromString(sprintf('%s.yml', $data->id()), Yaml::dump($data->toArray(), 2, 2))) {
        $context['results']['content'][] = $data;
        $context['results']['entity_type'] = $entity_type;
        $context['message'] = t('Export: @title', [
          '@title' => $data->label(),
        ]);
      }

      $zip->close();
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

    if ($success && isset($results['content'], $results['entity_type']) && is_array($results['content'])) {
      $route = Url::fromRoute('syncer.export_data', [
        'name' => $results['entity_type'] . '.zip',
      ]);

      $messenger->addMessage(t('@count content has been exported: <a href="@uri">click here to download file</a>', [
        '@count' => count($results['content']),
        '@uri' => $route->toString(),
      ]));
    }
    else {
      $messenger->addMessage(t('0 content has been exported.'));
    }
  }

}
