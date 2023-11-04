<?php

namespace Drupal\onovas_add_calendar_block\lib;

/**
 * Genera los enlaces para los calendarios de:
 *   - Google.
 *   - Yahoo.
 *   - Outlook (web).
 *   - ICS (archivo).
 */
interface CalendarLinksInterface {

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
  public function linkGoogle(string $name,
                             int $begin,
                             int $end,
                             string $location,
                             string $details): string;

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
  public function linkYahoo(string $name,
                            int $begin,
                            int $end,
                            string $location,
                            string $details): string;

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
  public function linkOutlookWeb(string $name,
                                 int $begin,
                                 int $end,
                                 string $location,
                                 string $details): string;

  /**
   * Genera un archivo ICS (link) para añadir eventos a otro tipo de calendario.
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
   * @param bool $open
   *   Indica si se quiere abrir el archivo en lugar de descargarlo.
   *
   * @return string
   *   Url que apunta al archivo generado.
   */
  public function linkIcs(string $name,
                          int $begin,
                          int $end,
                          string $location,
                          string $details,
                          bool $open = FALSE): string;

}
