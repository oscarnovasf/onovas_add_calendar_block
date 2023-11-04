<?php

namespace Drupal\onovas_add_calendar_block\lib;

use Drupal\Core\File\FileSystemInterface;
use Drupal\Core\File\FileUrlGeneratorInterface;

/**
 * Genera los enlaces para los calendarios de:
 *   - Google.
 *   - Yahoo.
 *   - Outlook (web).
 *   - ICS (archivo).
 */
class CalendarLinks implements CalendarLinksInterface {

  /**
   * Construye un objeto CalendarLinks.
   *
   * @param \Drupal\Core\File\FileSystemInterface $fileSystem
   *   Servicio file_system.
   * @param \Drupal\Core\File\FileUrlGeneratorInterface $fileUrlGenerator
   *   Servicio file_url_generator.
   */
  public function __construct(
    protected FileSystemInterface $fileSystem,
    protected FileUrlGeneratorInterface $fileUrlGenerator
  ) {}

  /**
   * {@inheritdoc}
   */
  public function linkGoogle(string $name,
                             int $begin,
                             int $end,
                             string $location,
                             string $details): string {
    /* Parámetros que debo añadir a la url de google calendar */
    $params = [
      '&dates=',
      '/',
      '&location=',
      '&details=',
      '&sf=true&output=xml',
    ];

    $url = 'https://www.google.com/calendar/render?action=TEMPLATE&text=';
    $url_data = $this->generateUrlParams($params, func_get_args(), 'Ymd\THis\Z');

    return $url . $url_data;
  }

  /**
   * {@inheritdoc}
   */
  public function linkYahoo(string $name,
                            int $begin,
                            int $end,
                            string $location,
                            string $details): string {
    /* Parámetros que debo añadir a la url de yahoo calendar */
    $params = [
      '&st=',
      '&et=',
      '&in_loc=',
      '&desc=',
      '&uid',
    ];

    $url = 'https://calendar.yahoo.com/?v=60&view=d&type=20&title=';
    $url_data = $this->generateUrlParams($params, func_get_args(), 'Ymd\THis');

    return $url . $url_data;
  }

  /**
   * {@inheritdoc}
   */
  public function linkOutlookWeb(string $name,
                                 int $begin,
                                 int $end,
                                 string $location,
                                 string $details): string {
    /* Parámetros que debo añadir a la url de outlook calendar (web) */
    $params = [
      '&startdt=',
      '&enddt=',
      '&location=',
      '&body=',
      '&allday=false',
    ];

    // $url = 'https://outlook.live.com/calendar/deeplink/compose?path=/calendar/action/compose&rru=addevent&subject=';
    $url = 'https://outlook.live.com/calendar/0/deeplink/compose?path=/calendar/action/compose&rru=addevent';
    $url_data = $this->generateUrlParams($params, func_get_args(), 'Y-m-d\TH:i:s\Z');

    return $url . $url_data;
  }

  /**
   * {@inheritdoc}
   */
  public function linkIcs(string $name,
                          int $begin,
                          int $end,
                          string $location,
                          string $details,
                          bool $open = FALSE): string {

    /* Datos para el archivo */
    $url = [
      'BEGIN:VCALENDAR',
      'VERSION:2.0',
      'METHOD:PUBLISH',
      'PRODID:-//Gcommons//AddCalendarLinks//EN',
      'X-MS-OLK-FORCEINSPECTOROPEN:TRUE',
      'BEGIN:VEVENT',

      'DTSTAMP:' . date('Ymd\THis\Z'),
      'DTSTART;TZID=Europe/Madrid:' . date('Ymd\THis', $begin),
      'DTEND;TZID=Europe/Madrid:' . date('Ymd\THis', $end),
      'SUMMARY:' . $this->escapeString($name),
      'LOCATION:' . $this->escapeString($location),
      'UID:' . $this->generateEventUid($name, $begin, $end, $location),
      'DESCRIPTION:' . $this->escapeString($details),

      'BEGIN:VALARM',
      'TRIGGER:-PT15M',
      'ACTION:DISPLAY',
      'DESCRIPTION:Reminder',
      'END:VALARM',
      'END:VEVENT',
      'END:VCALENDAR',
    ];

    $file_content = implode("\r\n", $url);
    if ($open) {
      $file_content = 'data:text/calendar;charset=utf8;base64,' . base64_encode(implode("\r\n", $url));
      return $file_content;
    }

    /* Guardo el archivo .ics */
    $directory = 'public://calendario-eventos';
    $options = FileSystemInterface::CREATE_DIRECTORY | FileSystemInterface::MODIFY_PERMISSIONS;
    $this->fileSystem->prepareDirectory($directory, $options);

    $file_name = str_replace(' ', '_', trim($name));
    $file_name = str_replace('¡', '', $file_name);
    $file_name = str_replace('!', '', $file_name);
    $file_name = str_replace('¿', '', $file_name);
    $file_name = str_replace('?', '', $file_name);

    $file_location = $directory . '/' . $file_name . ".ics";
    $file = $this->fileSystem->saveData($file_content,
                                        $file_location,
                                        FileSystemInterface::EXISTS_REPLACE);

    if ($file) {
      $file_path = $this->fileUrlGenerator->generateAbsoluteString($file_location);
      return $file_path;
    }
    else {
      return FALSE;
    }
  }

  /* ***************************************************************************
   * FUNCIONES PRIVADAS
   ************************************************************************** */

  /**
   * Genera una cadena MD5 para los archivos ICS.
   *
   * @param string $name
   *   Nombre del evento.
   * @param int $begin
   *   Fecha de inicio del evento en formato timestamp.
   * @param int $end
   *   Fecha de fin del evento en formato timestamp.
   * @param string $location
   *   Cadena con la dirección del evento.
   *
   * @return string
   *   Cadena codificada en MD5.
   */
  private function generateEventUid(string $name,
                                    int $begin,
                                    int $end,
                                    string $location): string {
    return md5(sprintf(
      '%s%s%s%s',
      date('Y-m-d\TH:i:sP', $begin),
      date('Y-m-d\TH:i:sP', $end),
      $name,
      $location
    ));
  }

  /**
   * Formatea la cadena al estilo C.
   *
   * @param string $field
   *   Cadena a formatear.
   *
   * @return string
   *   Cadena formateada.
   */
  private function escapeString(string $field): string {
    return addcslashes($field, "\r\n,;");
  }

  /**
   * Genera los parámetros de la url.
   *
   * @param array $params
   *   Parámetros de la url.
   * @param array $arg_list
   *   Valores de los parámetros.
   * @param string $date_format
   *   Formato para la fecha.
   *
   * @return string
   *   Parámetros para ser usados en la URL.
   */
  private function generateUrlParams(array $params,
                                     array $arg_list,
                                     string $date_format): string {
    /* Cadena a devolver */
    $url = '';

    for ($i = 0; $i < count($arg_list); $i++) {
      $current = $arg_list[$i];
      if (is_int($current)) {
        $current = date($date_format, $current);
      }
      else {
        $current = urlencode($current);
      }
      $url .= (string) $current . $params[$i];
    }

    return $url;
  }

}
