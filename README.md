onovas: Add Calendar Block
===

>Nombre de máquina: onovas_add_calendar_block

[![version][version-badge]][changelog]
[![Licencia][license-badge]][license]
[![Código de conducta][conduct-badge]][conduct]
[![Donate][donate-badge]][donate-url]
[![wakatime](https://wakatime.com/badge/user/236d57da-61e8-46f2-980b-7af630b18f42/project/018b5dbc-29b8-4d3a-afa8-41b92f34581b.svg)](https://wakatime.com/badge/user/236d57da-61e8-46f2-980b-7af630b18f42/project/018b5dbc-29b8-4d3a-afa8-41b92f34581b)

---

## Información
Módulo con dos partes: Una que genera un bloque con una cuenta atrás hasta la
fecha del evento y una serie de links para generar entradas en los calendarios
de Google, Yahoo, Outlook e iCal.  
Otra parte con una serie de funciones para Twig que generan el contador y los
diferentes enlaces.

Las funciones Twig definidas son:
- onovas_calendar_countdown(int $begin, int $end)
- onovas_calendar_google(string $name, int $begin, int $end, string $location, string $details)
- onovas_calendar_yahoo(string $name, int $begin, int $end, string $location, string $details)
- onovas_calendar_outlook(string $name, int $begin, int $end, string $location, string $details)
- onovas_calendar_ics(string $name, int $begin, int $end, string $location, string $details)

Ejemplo de uso:

```twig
{{ onovas_calendar_countdown(node.field_event_start_date.0.value | date('U'),
                             node.field_event_end_date.0.value   | date('U')) }}
```
> Si se trata de un evento ya realizado, no se muestra el bloque.

---

## Requisitos
Este módulo necesita para su correcto funcionamiento una versión superior
a la 10.x de Drupal.

---

## Instalación
Este módulo se instala como cualquier otro módulo de Drupal.  
No es necesario un proceso de instalación más avanzado.

Se recomienda, eso sí, instalarlo en la ruta **modules/custom/** para que se
instale la traducción al castellano.

---

## Configuración
El módulo dispone de un formulario de configuración para definir textos de los
enlaces y otras opciones.

---
⌨️ con ❤️ por [Óscar Novás][mi-web] 😊

[mi-web]: https://oscarnovas.com "for developers"

[version]: v1.0.0
[version-badge]: https://img.shields.io/badge/Versión-1.0.0-blue.svg

[license]: LICENSE.md
[license-badge]: https://img.shields.io/badge/Licencia-GPLv3+-green.svg "Leer la licencia"

[conduct]: CODE_OF_CONDUCT.md
[conduct-badge]: https://img.shields.io/badge/C%C3%B3digo%20de%20Conducta-2.0-4baaaa.svg "Código de conducta"

[changelog]: CHANGELOG.md "Histórico de cambios"

[donate-badge]: https://img.shields.io/badge/Donaci%C3%B3n-PayPal-red.svg
[donate-url]: https://paypal.me/oscarnovasf "Haz una donación"
