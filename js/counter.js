/**
 * @file
 * Provides onovas_add_calendar_block attachment logic.
 */

(function ($, Drupal, drupalSettings) {

  /** Actualiza/Oculta la cuenta atrás */
  function cuenta_atras() {
    const second = 1000,
      minute = second * 60,
      hour = minute * 60,
      day = hour * 24,
      event_date = drupalSettings.onovas_add_calendar_block.start;

    var element = document.getElementById('counter-days');
    if (typeof(element) != 'undefined' && element != null) {
      let countDown = new Date(event_date).getTime(), x = setInterval(function() {

        let now = new Date().getTime(),
            distance = countDown - now;

        document.getElementById("counter-days").innerText    = zfill(Math.floor(distance / (day)), 2),
        document.getElementById("counter-hours").innerText   = zfill(Math.floor((distance % (day)) / (hour)), 2),
        document.getElementById("counter-minutes").innerText = zfill(Math.floor((distance % (hour)) / (minute)), 2),
        document.getElementById("counter-seconds").innerText = zfill(Math.floor((distance % (minute)) / second), 2);

        if (distance < 0) {
          document.getElementById("counter-days").innerText    = zfill(0, 2),
          document.getElementById("counter-hours").innerText   = zfill(0, 2),
          document.getElementById("counter-minutes").innerText = zfill(0, 2),
          document.getElementById("counter-seconds").innerText = zfill(0, 2);

          clearInterval(x);
        }

      }, second)
    }
  }

  /**
   * Attaches the onovas_add_calendar_block behavior
   *
   * @type {Drupal~behavior}
   *
   * @prop {Drupal~behaviorAttach} attach
   */
  Drupal.behaviors.counter = {
    attach: function (context, settings) {
      cuenta_atras();
    }
  };

})(jQuery, Drupal, drupalSettings);
