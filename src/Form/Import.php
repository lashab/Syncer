<?php

namespace Drupal\syncer\Form;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Yaml\Yaml;
use Drupal\syncer\BatchImport;
use Drupal\file\Entity\File;
use ZipArchive;

/**
 * Provides Export form.
 */
class Import extends BaseForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'syncer_import_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['import'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'syncer-export',
      ],
    ];

    $form['import']['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Select entity type'),
      '#required' => TRUE,
      '#options' => $this->getEntityTypeOptions(),
      '#weight' => 0,
    ];

    $form['import']['content'] = [
      '#type' => 'managed_file',
      '#title' => $this->t('Select file'),
      '#required' => TRUE,
      '#autoupload' => TRUE,
      '#upload_validators' => ['file_validate_extensions' => ['zip']],
      '#weight' => 10,
    ];

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Import'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    /** @var \ZipArchive $zip */
    $zip = new ZipArchive();
    $operations = [];

    foreach ($form_state->getValue('content') as $content) {
      /** @var \Drupal\file\Entity|File $data */
      $data = File::load($content);
      $zip->open($this->fileSystem->realpath($data->getFileUri()));
      $count = $zip->numFiles;

      for ($i = 0; $i < $count; $i++) {
        if ($stat = $zip->statIndex($i)) {
          $resource = $zip->getStream($stat['name']);
          $operations[] = [
            [BatchImport::class, 'process'],
            [$form_state->getValue('entity_type'), Yaml::parse(stream_get_contents($resource))],
          ];
        }
      }

      $zip->close();
    }

    batch_set([
      'operations' => $operations,
      'finished' => [BatchImport::class, 'finished'],
    ]);
  }

}
