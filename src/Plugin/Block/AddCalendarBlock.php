<?php

namespace Drupal\onovas_add_calendar_block\Plugin\Block;

use Drupal\Core\Block\BlockBase;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Datetime\DateFormatterInterface;
use Drupal\Core\Datetime\DrupalDateTime;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeBundleInfoInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\node\NodeInterface;
use Drupal\onovas_add_calendar_block\lib\CalendarLinksInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Funcionalidad para añadir calendario y cuenta atrás.
 *
 * @Block(
 *   id = "onovas_add_calendar_block",
 *   admin_label = @Translation("Add Calendar Block"),
 *   category = @Translation("onovas")
 * )
 */
class AddCalendarBlock extends BlockBase implements ContainerFactoryPluginInterface {

  /**
   * {@inheritdoc}
   */
  public function __construct(
    array $configuration,
    $plugin_id,
    $plugin_definition,
    protected EntityTypeBundleInfoInterface $bundleInfo,
    protected EntityFieldManagerInterface $entityFieldManager,
    protected RouteMatchInterface $routeMatch,
    protected ConfigFactoryInterface $configFactory,
    protected DateFormatterInterface $dateFormatter,
    protected CalendarLinksInterface $calendarLinks
  ) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container,
                                array $configuration,
                                $plugin_id,
                                $plugin_definition) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $container->get('entity_type.bundle.info'),
      $container->get('entity_field.manager'),
      $container->get('current_route_match'),
      $container->get('config.factory'),
      $container->get('date.formatter'),
      $container->get('onovas_add_calendar_block.generators')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function build() {
    $config = $this->getConfiguration();

    $node = $this->routeMatch->getParameter('node');
    if ($node instanceof NodeInterface && $config['bundle'] === $node->bundle()) {

      $items = $this->generateLinks($node);

      /* Compruebo si es un evento del pasado */
      $older = TRUE;
      $start = $node->get($config['start'])->getValue();
      $start_date = new DrupalDateTime($start[0]['value'], 'UTC');
      $current_date = new DrupalDateTime();

      if ($start_date->getTimestamp() > $current_date->getTimestamp()) {
        $older = FALSE;
      }

      /* Verifico si tengo que mostrar la cuenta atrás */
      $module_config = $this->configFactory->get('onovas_add_calendar_block.settings');
      $show_countdown = FALSE;
      if ($module_config->get('show_countdown') && !$older) {
        /* Sólo se muestra si la fecha de inicio es mayor que hoy */
        $show_countdown = TRUE;
      }

      return [
        '#theme'        => 'onovas_add_calendar_block',
        '#node'         => $node,
        '#items'        => $items,
        '#show_counter' => $show_countdown,
        '#older'        => $older,
        '#attached'     => [
          'library'        => [
            'onovas_add_calendar_block/counter',
          ],
          'drupalSettings' => [
            'onovas_add_calendar_block' => [
              'start' => $this->dateFormatter->format($start_date->getTimestamp(), 'custom', 'Y-m-d H:i'),
            ],
          ],
        ],
      ];
    }

    return [];
  }

  /**
   * {@inheritdoc}
   */
  public function blockForm($form, FormStateInterface $form_state) {
    $form = parent::blockForm($form, $form_state);

    $config = $this->getConfiguration();

    $form['config'] = [
      '#type' => 'details',
      '#title' => $this->t('Settings'),
      '#open' => TRUE,
    ];

    $bundle = $config['bundle'] ?? NULL;
    $form['config']['bundle'] = [
      '#type' => 'select',
      '#title' => $this->t('Type of node that contains the event.'),
      '#default_value' => $config['bundle'] ?? '_none',
      '#required' => TRUE,
      '#options' => $this->getBundles(),
      '#empty_option' => $this->t('-- Select one --'),
      '#empty_value' => '_none',
      '#multiple' => FALSE,
      '#attributes' => [
        'attr-id' => 'bundle',
      ],
      '#ajax' => [
        'callback' => [$this, 'ajaxGetFields'],
        'disable-refocus' => FALSE,
        'event' => 'change',
        'method' => 'replace',
        'progress' => [
          'type' => 'throbber',
          'message' => $this->t('Getting fields...'),
        ],
        'wrapper' => 'onovas_wrapper_fields',
      ],
    ];

    $form['config']['fields'] = [
      '#type' => 'container',
      '#attributes' => [
        'id' => 'onovas_wrapper_fields',
      ],
    ];

    $form['config']['fields']['start'] = [
      '#type' => 'select',
      '#title' => $this->t('Event start date field.'),
      '#default_value' => $config['start'] ?? '_none',
      '#options' => $bundle ? $this->getFields($bundle, ['datetime']) : [],
      '#empty_option' => $this->t('-- Select one --'),
      '#empty_value' => '_none',
      '#multiple' => FALSE,
      '#states' => $this->getFormStates(),
    ];

    $form['config']['fields']['end'] = [
      '#type' => 'select',
      '#title' => $this->t('Event end date field.'),
      '#default_value' => $config['end'] ?? '_none',
      '#options' => $bundle ? $this->getFields($bundle, ['datetime']) : [],
      '#empty_option' => $this->t('-- Select one --'),
      '#empty_value' => '_none',
      '#multiple' => FALSE,
      '#states' => $this->getFormStates(),
    ];

    $form['config']['fields']['location'] = [
      '#type' => 'select',
      '#title' => $this->t('Event location field.'),
      '#default_value' => $config['location'] ?? '_none',
      '#options' => $bundle ? $this->getFields($bundle, ['string']) : [],
      '#empty_option' => $this->t('-- Select one --'),
      '#empty_value' => '_none',
      '#multiple' => FALSE,
      '#states' => $this->getFormStates(),
    ];

    $form['config']['fields']['description'] = [
      '#type' => 'select',
      '#title' => $this->t('Event description field.'),
      '#default_value' => $config['description'] ?? '_none',
      '#options' => $bundle ? $this->getFields($bundle, ['text_long', 'string', 'text_with_summary']) : [],
      '#empty_option' => $this->t('-- Select one --'),
      '#empty_value' => '_none',
      '#multiple' => FALSE,
      '#states' => $this->getFormStates(),
    ];

    return $form;
  }

  /**
   * {@inheritdoc}
   */
  public function blockSubmit($form, FormStateInterface $form_state) {
    parent::blockSubmit($form, $form_state);
    $values = $form_state->getValues();

    $this->configuration['bundle']      = $values['config']['bundle'];
    $this->configuration['start']       = $values['config']['fields']['start'];
    $this->configuration['end']         = $values['config']['fields']['end'];
    $this->configuration['location']    = $values['config']['fields']['location'];
    $this->configuration['description'] = $values['config']['fields']['description'];
  }

  /**
   * Rellena los diferentes campos del tipo de nodo.
   *
   * @param array $form
   *   Array con el formulario.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Objeto FormState.
   *
   * @return array
   *   Formulario modificado.
   */
  public function ajaxGetFields(array &$form, FormStateInterface $form_state) {
    $values = $form_state->getValues();
    $bundle = $values['settings']['config']['bundle'];

    if (!empty($bundle)) {
      $form['settings']['config']['fields']['start']['#options']       = $this->getFields($bundle, ['datetime']);
      $form['settings']['config']['fields']['end']['#options']         = $this->getFields($bundle, ['datetime']);
      $form['settings']['config']['fields']['location']['#options']    = $this->getFields($bundle, ['string']);
      $form['settings']['config']['fields']['description']['#options'] = $this->getFields($bundle, ['text_long', 'string', 'text_with_summary']);
    }

    return $form['settings']['config']['fields'];

  }

  /**
   * {@inheritdoc}
   */
  public function blockValidate($form, FormStateInterface $form_state) {
  }

  /**
   * {@inheritdoc}
   */
  public function getCacheMaxAge() {
    return 0;
  }

  /**
   * Obtiene los diferentes bundles de la categoría "node".
   *
   * @return array
   *   Array con los campos del bundle [nombre_máquina => Etiqueta].
   */
  protected function getBundles(): array {
    $entity_type_id = 'node';
    $list_bundles = [];

    $bundles = $this->bundleInfo->getBundleInfo($entity_type_id);
    foreach ($bundles as $bundle_name => $bundle_definition) {
      $list_bundles["$bundle_name"] = $bundle_definition['label'];
    }

    return $list_bundles;
  }

  /**
   * Obtiene los campos de un tipo determinado de un bundle.
   *
   * @param string $bundle
   *   Nombre del bundle.
   * @param array $types
   *   Tipos de los campos que se quieren obtener.
   *
   * @return array
   *   Array con los campos del bundle [nombre_máquina => Etiqueta].
   */
  protected function getFields(string $bundle, ?array $types = []): array {
    $entity_type_id = 'node';
    $list_fields = [];

    $fields = $this->entityFieldManager->getFieldDefinitions($entity_type_id, $bundle);
    foreach ($fields as $field_name => $field_definition) {
      if (!empty($field_definition->getTargetBundle())) {
        if (!empty($types)) {
          if (in_array($field_definition->getType(), $types)) {
            $list_fields["$field_name"] = $field_definition->getLabel();
          }
        }
        else {
          $list_fields["$field_name"] = $field_definition->getLabel();
        }
      }
    }

    return $list_fields;
  }

  /**
   * Genera los states para los campos del formulario.
   *
   * @return array
   *   Array con los states.
   */
  private function getFormStates(): array {
    return [
      'visible' => [
        'select[attr-id="bundle"]' => ['!value' => '_none'],
      ],
      'required' => [
        'select[attr-id="bundle"]' => ['!value' => '_none'],
      ],
    ];
  }

  /**
   * Genera un array con los valores para los servicios de calendario.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Nodo del que se obtienen los valores.
   *
   * @return array
   *   Array con los valores.
   */
  private function generateLinkValues(NodeInterface $node): array {
    $config = $this->getConfiguration();

    $fields = [
      'title'       => '',
      'start'       => '',
      'end'         => '',
      'location'    => '',
      'description' => '',
    ];

    $fields['title'] = $node->getTitle();

    if (is_array($aux = $node->get($config['start'])->getValue())) {
      $fields['start'] = strtotime($aux[0]['value']);
    }

    if (is_array($aux = $node->get($config['end'])->getValue())) {
      $fields['end'] = strtotime($aux[0]['value']);
    }

    if (is_array($aux = $node->get($config['location'])->getValue())) {
      $fields['location'] = $aux[0]['value'];
    }

    if (is_array($aux = $node->get($config['description'])->getValue())) {
      $fields['description'] = $aux[0]['value'];
    }

    return $fields;
  }

  /**
   * Genera un array con los enlaces a mostrar.
   *
   * @param \Drupal\node\NodeInterface $node
   *   Nodo del que se obtienen los valores.
   *
   * @return array
   *   Array con los enlaces.
   */
  private function generateLinks(NodeInterface $node): array {
    /* Obtengo los valores para generar las urls */
    $fields = $this->generateLinkValues($node);

    $module_config = $this->configFactory->get('onovas_add_calendar_block.settings');
    $enabled_calendars = $module_config->get('enabled_calendars');

    $items = [];
    foreach ($enabled_calendars as $key => $value) {
      if ($value) {
        $text = $module_config->get('text_for_' . $key);

        $link = '';
        switch ($value) {

          case 'google':
            $link = $this->calendarLinks->linkGoogle(
              $fields['title'],
              $fields['start'],
              $fields['end'],
              $fields['location'] ?? '',
              $fields['description']
            );
            break;

          case 'yahoo':
            $link = $this->calendarLinks->linkYahoo(
              $fields['title'],
              $fields['start'],
              $fields['end'],
              $fields['location'] ?? '',
              $fields['description']
            );
            break;

          case 'outlook':
            $link = $this->calendarLinks->linkOutlookWeb(
              $fields['title'],
              $fields['start'],
              $fields['end'],
              $fields['location'] ?? '',
              $fields['description']
            );
            break;

          case 'icalc':
            $link = $this->calendarLinks->linkIcs(
              $fields['title'],
              $fields['start'],
              $fields['end'],
              $fields['location'] ?? '',
              $fields['description'],
              TRUE
            );
            break;

          default:
            $link = '';
            break;
        }

        $items[$key] = [
          'link'      => $link,
          'text_link' => $text ?? '',
        ];
      }
    }

    return $items;
  }

}
