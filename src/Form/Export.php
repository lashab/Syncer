<?php

namespace Drupal\syncer\Form;

use Drupal\Core\Form\FormStateInterface;
use Symfony\Component\Yaml\Yaml;
use Drupal\syncer\BatchExport;

/**
 * Provides Export form.
 */
class Export extends BaseForm {

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'syncer_export_form';
  }

  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
    $form['export'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'syncer-export',
      ],
    ];

    $form['export']['entity_type'] = [
      '#type' => 'select',
      '#title' => $this->t('Select entity type'),
      '#required' => TRUE,
      '#options' => $this->getEntityTypeOptions(),
      '#weight' => 0,
      '#ajax' => [
        'callback' => [$this, 'getContentEntityTypesAjaxForm'],
        'wrapper' => 'syncer-export',
        'event' => 'change',
      ],
    ];

    if ($entity_type = $form_state->getValue('entity_type')) {
      $content_form = [
        '#type' => 'entity_autocomplete',
        '#title' => $this->t('Select content'),
        '#description' => $this->t('Leave emtpy if you want to export all, otherwise seperate it with comma e.i: Node 1, Node 2 etc.'),
        '#tags' => TRUE,
        '#target_type' => $entity_type,
        '#weight' => 10,
      ];

      if ($options = $this->getEntityTypeBundleOptions($entity_type)) {
        $form['export']['entity_type_bundle'] = [
          '#type' => 'select',
          '#title' => $this->t('Select entity bundle'),
          '#options' => $options,
          '#required' => TRUE,
          '#ajax' => [
            'callback' => [$this, 'getContentEntityTypesAjaxForm'],
            'wrapper' => 'syncer-export',
            'event' => 'change',
          ],
          '#weight' => 5,
        ];

        if ($entity_type_bundle = $form_state->getValue('entity_type_bundle')) {
          $form['export']['entities'] = $content_form;
          $form['export']['entities']['#selection_settings'] = [
            'target_bundles' => (array) $entity_type_bundle,
          ];
        }
      }
      else {
        $form['export']['entities'] = $content_form;
      }
    }

    $form['actions']['#type'] = 'actions';
    $form['actions']['submit'] = [
      '#type' => 'submit',
      '#value' => $this->t('Export'),
      '#button_type' => 'primary',
    ];

    return $form;
  }

  /**
   * Entity type AJAX form handler.
   */
  public function getContentEntityTypesAjaxForm(array &$form, FormStateInterface $form_state) {
    return $form['export'];
  }

  /**
   * Get entity type bundle options.
   *
   * @param string $entity_type
   *   Entity type.
   *
   * @return array
   *   An array of entity type bundle options.
   */
  protected function getEntityTypeBundleOptions(string $entity_type) {
    $options = [];
    $entity = $this->entityTypeManager->getDefinition($entity_type);

    if ($entity && $type = $entity->getBundleEntityType()) {
      $types = $this->entityTypeManager->getStorage($type)->loadMultiple();

      if ($types && is_array($types)) {
        foreach ($types as $type) {
          $options[$type->id()] = $type->label();
        }
      }
    }

    return $options;
  }  

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {
    $entity_type = $form_state->getValue('entity_type');
    $entity_type_bundle = NULL;
    /** @var \Drupal\Core\Entity\ContentEntityType $entity_definition */
    $entity_definition = $this->entityTypeManager->getDefinition($entity_type);
    $operations = [];

    /** @var \Drupal\Core\Entity\Query\Sql\Query $query */
    $query = $this->entityTypeManager->getStorage($entity_type)->getQuery();

    if (!empty($form_state->getValue('entity_type_bundle')) && $entity_definition->hasKey('bundle')) {
      $entity_type_bundle = $form_state->getValue('entity_type_bundle');
      $query->condition($entity_definition->getKey('bundle'), $entity_type_bundle);
    }

    if (!empty($form_state->getValue('entities'))) {
      $query->condition($entity_definition->getKey('id'), array_column($form_state->getValue('entities'), 'target_id'), 'IN');
    }

    $ids = $query->execute();

    $zip = fopen($this->fileSystem->realpath("public://$entity_type.zip"), 'w');

    foreach ($ids as $id) {
      /** @var \Drupal\Core\Entity\EntityInterface $entity */
      $entity = $this->entityTypeManager
        ->getStorage($entity_type)
        ->load($id);

      $operations[] = [
        [BatchExport::class, 'process'],
        [$entity_type, $entity],
      ];
    }

    batch_set([
      'operations' => $operations,
      'finished' => [BatchExport::class, 'finished'],
    ]);

    fclose($zip);
  }

}
