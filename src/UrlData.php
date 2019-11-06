<?php

namespace Drupal\commerce;

/**
 * Encodes and decodes array data in a URL-safe way.
 */
final class UrlData {

  /**
   * Encodes the given data.
   *
   * @param array $data
   *   The data.
   *
   * @return string
   *   The encoded data.
   */
  public static function encode(array $data) {
    $data = json_encode($data);
    // URL-safe Base64 encoding (base64url).
    $data = str_replace(['+', '/', '='], ['-', '_', ''], base64_encode($data));

    return $data;
  }

  /**
   * Decodes the given data.
   *
   * @param string $data
   *   The encoded data.
   *
   * @return array|false
   *   The decoded data, or FALSE if decoding failed.
   */
  public static function decode($data) {
    $data = base64_decode(str_replace(['-', '_'], ['+', '/'], $data));
    if ($data) {
      $data = json_decode($data, TRUE);
    }

    return is_array($data) ? $data : FALSE;
  }

}
