<?php

namespace Drupal\onovas_add_calendar_block\Form\config;

/**
 * @file
 * SettingsForm.php
 */

use Drupal\Core\Form\ConfigFormBase;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Render\Markup;
use Drupal\Core\Config\Config;
use Drupal\Core\Extension\ExtensionPathResolver;

use Symfony\Component\DependencyInjection\ContainerInterface;

use Drupal\onovas_add_calendar_block\lib\general\MarkdownParser;

/**
 * Formulario de configuración del módulo.
 */
class SettingsForm extends ConfigFormBase {

  /**
   * @var \Drupal\Core\Extension\ExtensionPathResolver
   */
  protected $pathResolver;

  /**
   * Constructor para añadir dependencias.
   *
   * @param \Drupal\Core\Extension\ExtensionPathResolver $logger
   *   Servicio PathResolver.
   */
  public function __construct(ExtensionPathResolver $path_resolver) {
    $this->pathResolver = $path_resolver;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container) {
    return new static(
      $container->get('extension.path.resolver'),
    );
  }

  /**
   * Implements getFormId().
   */
  public function getFormId() {
    return 'onovas_add_calendar_block.settings';
  }

  /**
   * Implements getEditableConfigNames().
   */
  protected function getEditableConfigNames() {
    return ['onovas_add_calendar_block.settings'];
  }

  /**
   * Implements buildForm().
   */
  public function buildForm(array $form, FormStateInterface $form_state) {

    /* Obtengo la configuración actual */
    /* $config = \Drupal::configFactory()->getEditable('custom_module.onovas_add_calendar_block.settings'); */
    $config = $this->config('onovas_add_calendar_block.settings');

    /* SETTINGS FORM */
    $form['settings'] = [
      '#type' => 'vertical_tabs',
    ];

    $form['calendar_settings'] = $this->getCalendarSettings($config);

    /* *************************************************************************
     * CONTENIDO DE CHANGELOG.md, LICENSE.md y README.md
     * ************************************************************************/

    /* Datos auxiliares */
    $module_path = $this->pathResolver
      ->getPath('module', "onovas_add_calendar_block");

    /* Compruebo si existe y leo el contenido del archivo CHANGELOG.md */
    $contenido = $this->getChangeLogBuild($config, $module_path);
    if ($contenido) {
      $form['info'] = $contenido;
    }

    /* Compruebo si existe y leo el contenido del archivo LICENSE.md */
    $contenido = $this->getLicenseBuild($config, $module_path);
    if ($contenido) {
      $form['license'] = $contenido;
    }

    /* Compruebo si existe y leo el contenido del archivo README.md */
    $contenido = $this->getReadmeBuild($config, $module_path);
    if ($contenido) {
      $form['help'] = $contenido;
    }

    return parent::buildForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function validateForm(array &$form, FormStateInterface $form_state) {
    parent::validateForm($form, $form_state);
  }

  /**
   * {@inheritdoc}
   */
  public function submitForm(array &$form, FormStateInterface $form_state) {

    $config = $this->config('onovas_add_calendar_block.settings');

    /* Campos a guardar */
    $list = [
      'show_countdown',
      'enabled_calendars',
      'text_for_google',
      'text_for_outlook',
      'text_for_yahoo',
      'text_for_icalc',
    ];

    foreach ($list as $item) {
      $config->set($item, $form_state->getValue($item));
    }
    $config->save();

    parent::submitForm($form, $form_state);
  }

  /**
   * Parte del formulario de configuración que se refiere a los calendarios.
   *
   * @param \Drupal\Core\Config\Config $config
   *   Configuración del formulario.
   *
   * @return array
   *   Array renderizable.
   */
  private function getCalendarSettings(Config $config): array {
    $form = [];

    $form['calendar_settings'] = [
      '#type'        => 'details',
      '#title'       => $this->t('Calendars'),
      '#open'        => FALSE,
      '#group'       => 'settings',
      '#weight'      => -100,
      '#description' => $this->t('<p><h2>Calendars Settings</h2></p>'),
    ];

    $form['calendar_settings']['show_countdown'] = [
      '#type'          => 'checkbox',
      '#title'         => $this->t('Show countdown'),
      '#default_value' => $config->get('show_countdown') ?? FALSE,
    ];

    $form['calendar_settings']['enabled_calendars'] = [
      '#type'          => 'checkboxes',
      '#title'         => $this->t('Select the calendars to enable'),
      '#default_value' => $config->get('enabled_calendars') ?? [],
      '#required'      => TRUE,
      '#access'        => TRUE,
      '#options'       => [
        'google'  => 'Google',
        'outlook' => 'Outlook',
        'yahoo'   => 'Yahoo',
        'icalc'   => 'iCal',
      ],
    ];

    $form['calendar_settings']['texts'] = [
      '#type'  => 'details',
      '#title' => $this->t('Link texts'),
      '#open'  => TRUE,
    ];

    $form['calendar_settings']['texts']['text_for_google'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Text for Google Link'),
      '#default_value' => $config->get('text_for_google') ?? '',
      '#required'      => TRUE,
    ];

    $form['calendar_settings']['texts']['text_for_outlook'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Text for Outlook Link'),
      '#default_value' => $config->get('text_for_outlook') ?? '',
      '#required'      => TRUE,
    ];

    $form['calendar_settings']['texts']['text_for_yahoo'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Text for Yahoo Link'),
      '#default_value' => $config->get('text_for_yahoo') ?? '',
      '#required'      => TRUE,
    ];

    $form['calendar_settings']['texts']['text_for_icalc'] = [
      '#type'          => 'textfield',
      '#title'         => $this->t('Text for iCalc Link'),
      '#default_value' => $config->get('text_for_icalc') ?? '',
      '#required'      => TRUE,
    ];

    return $form['calendar_settings'];
  }

  /**
   * Obtiene el contenido del archivo CHANGELOG.md.
   *
   * @param \Drupal\Core\Config\Config $config
   *   Configuración del módulo.
   * @param string $module_path
   *   Path del módulo.
   *
   * @return array
   *   Array con el contenido a renderizar, si procede.
   */
  private function getChangeLogBuild(Config $config, string $module_path): array {
    $template = file_get_contents($module_path . "/templates/custom/info.html.twig");

    $ruta = $module_path . "/CHANGELOG.md";
    $contenido = $this->getMdContent($ruta);

    if ($contenido) {
      $form['info'] = [
        '#type'  => 'details',
        '#title' => $this->t('Info'),
        '#group' => 'settings',

        'info' => [
          '#type'     => 'inline_template',
          '#template' => $template,
          '#context'  => [
            'changelog' => Markup::create($contenido),
          ],
        ],
      ];

      return $form['info'];
    }

    return [];
  }

  /**
   * Obtiene el contenido del archivo LICENSE.md.
   *
   * @param \Drupal\Core\Config\Config $config
   *   Configuración del módulo.
   * @param string $module_path
   *   Path del módulo.
   *
   * @return array
   *   Array con el contenido a renderizar, si procede.
   */
  private function getLicenseBuild(Config $config, string $module_path): array {
    $template = file_get_contents($module_path . "/templates/custom/license.html.twig");

    $ruta = $module_path . "/LICENSE.md";
    $contenido = $this->getMdContent($ruta);

    if ($contenido) {
      $form['license'] = [
        '#type'  => 'details',
        '#title' => $this->t('License'),
        '#group' => 'settings',

        'license' => [
          '#type'     => 'inline_template',
          '#template' => $template,
          '#context'  => [
            'license' => Markup::create($contenido),
          ],
        ],
      ];

      return $form['license'];
    }

    return [];
  }

  /**
   * Obtiene el contenido del archivo LICENSE.md.
   *
   * @param \Drupal\Core\Config\Config $config
   *   Configuración del módulo.
   * @param string $module_path
   *   Path del módulo.
   *
   * @return array
   *   Array con el contenido a renderizar, si procede.
   */
  private function getReadmeBuild(Config $config, string $module_path): array {
    $template = file_get_contents($module_path . "/templates/custom/help.html.twig");

    $ruta = $module_path . "/README.md";
    $contenido = $this->getMdContent($ruta);

    if ($contenido) {
      $form['help'] = [
        '#type'  => 'details',
        '#title' => $this->t('Help'),
        '#group' => 'settings',

        'help' => [
          '#type'     => 'inline_template',
          '#template' => $template,
          '#context'  => [
            'readme' => Markup::create($contenido),
          ],
        ],
      ];

      return $form['help'];
    }

    return [];
  }

  /**
   * Obtiene el contenido de un archivo .md.
   *
   * @param string $path
   *   Ruta completa del archivo.
   *
   * @return string
   *   Contenido del archivo.
   */
  private function getMdContent(string $path): string {
    $parser = new MarkdownParser();

    $contenido = '';
    if (file_exists($path)) {
      $contenido = file_get_contents($path);
      $contenido = $parser->text($contenido);
    }

    return $contenido;
  }

}
