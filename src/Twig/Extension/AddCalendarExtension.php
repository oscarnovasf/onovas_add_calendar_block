<?php

namespace Drupal\onovas_add_calendar_block\Twig\Extension;

use Drupal\Core\Datetime\DrupalDateTime;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

/**
 * Extensiones de las diferentes opciones de calendario.
 *
 * También se incluye la función para mostrar la cuenta atrás.
 */
class AddCalendarExtension extends AbstractExtension {

  /**
   * {@inheritdoc}
   */
  public function getFunctions(): array {
    $functions = [
      new TwigFunction('onovas_calendar_countdown', [self::class, 'showCountDown']),
      new TwigFunction('onovas_calendar_google', [self::class, 'googleGetLink']),
      new TwigFunction('onovas_calendar_yahoo', [self::class, 'yahooGetLink']),
      new TwigFunction('onovas_calendar_outlook', [self::class, 'outlookWebGetLink']),
      new TwigFunction('onovas_calendar_ics', [self::class, 'icsGetLink']),
    ];

    return $functions;
  }

  /**
   * Función showCountDown().
   *
   * Genera una cuenta regresiva para el evento.
   *
   * @param int $begin
   *   Fecha de inicio del evento en formato timestamp.
   * @param int $end
   *   Fecha de fin del evento en formato timestamp.
   *
   * @return array|null
   *   Plantilla con los datos del contador.
   *   NULL si el evento ya ha terminado.
   */
  public static function showCountDown(int $begin, int $end): ?array {
    /* Compruebo si la fecha de fin es posterior a la fecha actual */
    $current_date = new DrupalDateTime();

    if ($begin < $current_date->getTimestamp()) {
      return NULL;
    }
    else {
      /* Genero la plantilla con el contador */
      return [
        '#theme'    => 'onovas_add_calendar_counter',
        '#older'    => FALSE,
        '#attached' => [
          'library'        => [
            'onovas_add_calendar_block/counter.twig',
          ],
          'drupalSettings' => [
            'onovas_add_calendar_block' => [
              'start' => \Drupal::service('date.formatter')->format($begin, 'custom', 'Y-m-d H:i'),
            ],
          ],
        ],
      ];
    }
  }

  /**
   * Genera un link para añadir un evento al calendario de Google.
   *
   * @param string $name
   *   Nombre del evento.
   * @param int $begin
   *   Fecha de inicio del evento en formato timestamp.
   * @param int $end
   *   Fecha de fin del evento en formato timestamp.
   * @param string $location
   *   Cadena con la dirección del evento.
   * @param string $details
   *   Cadena con los detalles del evento.
   *
   * @return string
   *   Cadena con la url del enlace.
   */
  public static function googleGetLink(string $name,
                                       int $begin,
                                       int $end,
                                       string $location,
                                       string $details): string {
    return \Drupal::service('onovas_add_calendar_block.generators')
      ->linkGoogle($name, $begin, $end, $location, $details);
  }

  /**
   * Genera un link para añadir un evento al calendario de Yahoo.
   *
   * @param string $name
   *   Nombre del evento.
   * @param int $begin
   *   Fecha de inicio del evento en formato timestamp.
   * @param int $end
   *   Fecha de fin del evento en formato timestamp.
   * @param string $location
   *   Cadena con la dirección del evento.
   * @param string $details
   *   Cadena con los detalles del evento.
   *
   * @return string
   *   Cadena con la url del enlace.
   */
  public static function yahooGetLink(string $name,
                                      int $begin,
                                      int $end,
                                      string $location,
                                      string $details): string {
    return \Drupal::service('onovas_add_calendar_block.generators')
      ->linkYahoo($name, $begin, $end, $location, $details);
  }

  /**
   * Genera un link para añadir un evento al calendario de Outlook (web).
   *
   * @param string $name
   *   Nombre del evento.
   * @param int $begin
   *   Fecha de inicio del evento en formato timestamp.
   * @param int $end
   *   Fecha de fin del evento en formato timestamp.
   * @param string $location
   *   Cadena con la dirección del evento.
   * @param string $details
   *   Cadena con los detalles del evento.
   *
   * @return string
   *   Cadena con la url del enlace.
   */
  public static function outlookWebGetLink(string $name,
                                           int $begin,
                                           int $end,
                                           string $location,
                                           string $details): string {
    return \Drupal::service('onovas_add_calendar_block.generators')
      ->linkOutlookWeb($name, $begin, $end, $location, $details);
  }

  /**
   * Genera un link a un archivo ICS.
   *
   * @param string $name
   *   Nombre del evento.
   * @param int $begin
   *   Fecha de inicio del evento en formato timestamp.
   * @param int $end
   *   Fecha de fin del evento en formato timestamp.
   * @param string $location
   *   Cadena con la dirección del evento.
   * @param string $details
   *   Cadena con los detalles del evento.
   *
   * @return string
   *   Cadena con la url del enlace.
   */
  public static function icsGetLink(string $name,
                                    int $begin,
                                    int $end,
                                    string $location,
                                    string $details): string {
    return \Drupal::service('onovas_add_calendar_block.generators')
      ->linkIcs($name, $begin, $end, $location, $details);
  }

}
