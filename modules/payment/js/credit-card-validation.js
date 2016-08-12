/**
 * @file
 * Attaches credit card validation logic.
 */

(function ($) {

  'use strict';

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
   * @return {object|boolean}
   */
  var detectType = function (number) {
    // Loop over all available types.
    for (var x in types) {
      var type = types[x];

      // Loop over all patterns in the type.
      for (var i in type.pattern) {
        var pattern = type.pattern[i];

        // If the pattern has a dash, we should create a range of patterns.
        if (pattern.indexOf('-') >= 0) {
          var exploded_pattern;
          var ranges = [];
          var range;
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
        // No dashes, so just validate this pattern.
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
   * @return {boolean}
   */
  var validatePrefix = function (number, prefix) {
    return number.substring(0, prefix.length) == prefix + '';
  };

  /**
   * Validate credit card.
   *
   * @param {string} number
   * @param {object} type
   *
   * @return {boolean}
   */
  var validateCreditCard = function (number, type) {
    // Make sure that the type is really the expected type.
    if (detectType(number) != type) {
      return false;
    }

    // Test that the length of the card is actually one of the expected lengths
    // defined in the type.
    for (var x in type.lengths) {
      var expected_lenght = type.lengths[x];
      if (number.length === expected_lenght) {
        return true;
      }
    }

    return false;
  };

  /**
   * Trigger all other validations.
   *
   * @param {object} element
   */
  var validation = function (element) {
    var value = element.val();
    // Strip spaces from the value for all validations.
    value = value.replace(/ /gi, '');

    // If the value is not filled in, don't do any validation.
    var empty_value = value.length === 0;
    if (empty_value) {
      return;
    }

    // Get the type of the card.
    var type = detectType(value);

    // If no type is found, don't bother doing anything else.
    if (!type) {
      return;
    }

    element.parent().append('<br /> CC is of type: ' + type.niceType);

    // Check if the card is actually valid as well.
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
        $(element).on('blur', function () {
          validation(element);
        });
      });
    }
  };

})(jQuery);
