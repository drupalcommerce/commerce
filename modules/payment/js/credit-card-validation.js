/**
 * @file
 * Attaches credit card validation logic.
 */

(function ($) {

  "use strict";

  var types = {};
  var VISA = 'visa';
  var MASTERCARD = 'master-card';

  types[VISA] = {
    niceType: 'Visa',
    type: VISA,
    pattern: ['4'],
    gaps: [4, 8, 12],
    lengths: [16]
  };

  types[MASTERCARD] = {
    niceType: 'MasterCard',
    type: MASTERCARD,
    pattern: ['51-55', '222100-272099'],
    gaps: [4, 8, 12],
    lengths: [16]
  };

  /**
   * Detect the type of credit card.
   *
   * @param {string} number
   *
   * @returns {object|boolean}
   */
  var detectType = function(number) {
    if (number.length === 0) {
      return false;
    }

    for (var x in types) {
      var type = types[x];

      for (var i in type.pattern) {
        var pattern = type.pattern[i];
        if (pattern.indexOf('-') >= 0) {
          var exploded_pattern, ranges = [], range;

          exploded_pattern = pattern.split('-');

          while (exploded_pattern[0] <= exploded_pattern[1]) {
            ranges.push(exploded_pattern[0]);
            exploded_pattern[0]++;
          }

          for (range in ranges) {
            if (validatePrefix(number, range)) {
              return type;
            }
          }
        }
        else if (validatePrefix(number, pattern)) {
          return type;
        }
      }
    }

    return false;
  };

  /**
   * Validate the prefix is according to the expected prefix.
   *
   * @param {string} number
   * @param {string} prefix
   *
   * @returns {boolean}
   */
  var validatePrefix = function(number, prefix) {
    return number.substring(0, prefix.length) == prefix + '';
  };

  /**
   * Validate credit card.
   *
   * @param {string} number
   * @param {object} type
   *
   * @returns {boolean}
   */
  var validateCreditCard = function(number, type) {
    if (detectType(number) != type) {
      return false;
    }

    for (var x in type.lengths) {
      var expected_lenght = type.lengths[x];
      if (number.length == expected_lenght) {
        return true;
      }
    }
  };

  /**
   * Trigger all other validations.
   *
   * @param {object} element
   */
  var validation = function(element) {
    var value = element.val();
    var type = detectType(value);

    if (!type) {
      return;
    }
    element.parent().append('<br /> CC is of type: ' + type.niceType);

    var is_valid = validateCreditCard(value, type);
    if (is_valid) {
      element.parent().append(' CC is valid');
    }
    else {
      element.parent().append(' CC is not valid');
    }
  };

  Drupal.behaviors.creditCardValidation = {
    attach: function (context, settings) {
      $('#edit-payment-information-add-payment-details-number', context).each(function () {
        var element = $(this);
        $(element).on('blur', function() {
          validation(element);
        })
      });
    }
  };

})(jQuery);
