
/**
 * Función para rellenar con ceros a la izquierda.
 */
function zfill(number, width) {
  var numberOutput = Math.abs(number);
  var length = number.toString().length;
  var zero = "0";

  if (width <= length) {
    if (number < 0) {
      return ("-" + numberOutput.toString());
    }
    else {
      return numberOutput.toString();
    }
  }
  else {
    if (number < 0) {
      return ("-" + (zero.repeat(width - length)) + numberOutput.toString());
    }
    else {
      return ((zero.repeat(width - length)) + numberOutput.toString());
    }
  }
}
