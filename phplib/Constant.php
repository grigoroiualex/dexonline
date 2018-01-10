<?php

/* Big ugly constants sit here so as not to clutter the code base. */

class Constant {

  const CLEANUP_REPLACEMENTS = [
    'ş'   => 'ș',
    'Ş'   => 'Ș',
    'ţ'   => 'ț',
    'Ţ'   => 'Ț',

    ' ◊ ' => ' * ',
    ' ♦ ' => ' **',

    // hyphens and spaces
    ' '   => ' ',     /* U+00A0 non-breaking space */
    '‑'   => '-',     /* U+2011 non-breaking hyphen */
    '—'   => '-',     /* U+2014 em dash */
    '­'   => '',      /* U+00AD soft hyphen */

    // Replace all kinds of double quotes with the ASCII ones.
    // Do NOT alter ″ (double prime, 0x2033), which is used for inch and second symbols.
    '„'   => '"',     /* U+201E */
    '”'   => '"',     /* U+201D */
    '“'   => '"',     /* U+201C */
    '‟'   => '"',     /* U+201F */

    // Replace all kinds of single quotes and acute accents with the ASCII apostrophe.
    // Do NOT alter ′ (prime, 0x2032), which is used for foot and minute symbols.
    '´'   => "'",     /* U+00B4 */
    '‘'   => "'",     /* U+2018 */
    '’'   => "'",     /* U+2019 */ 

    // Replace the ordinal indicator with the degree sign.
    'º'   =>  '°',    /* U+00BA => U+00B0 */

    "\r\n" => "\n"    /* Unix newlines only */
  ];

  const HTML_REPLACEMENTS = [
    'internal' => [' - ', ' ** ', ' * ', "'", ],
    'html' => [' &#x2013; ', ' &#x2666; ', ' &#x25ca; ', '’' /* U+2019 */, ],
  ];

  const ACCENTS = [
    'accented' => [
      'á', 'Á', 'ắ', 'Ắ', 'ấ', 'Ấ', 'é', 'É', 'í', 'Í', 'î́', 'Î́',
      'ó', 'Ó', 'ö́', 'Ö́', 'ú', 'Ú', 'ǘ', 'Ǘ', 'ý', 'Ý',
    ],
    'unaccented' => [
      'a', 'A', 'ă', 'Ă', 'â', 'Â', 'e', 'E', 'i', 'I', 'î', 'Î',
      'o', 'O', 'ö', 'Ö', 'u', 'U', 'ü', 'Ü', 'y', 'Y',
    ],
  ];

}