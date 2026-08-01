<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Issuer
    |--------------------------------------------------------------------------
    | Sender block and footer, kept out of the Blade so it needs no code edit.
    |
    */

    'issuer' => [
        'name' => 'Pascal Kraupner',
        'contact_name' => 'Pascal Kraupner',
        'street' => 'Karl-Wagener-Straße 61',
        'postal_code' => '44879',
        'city' => 'Bochum',
        'tax_number' => '350/5135/3082',
        'email' => 'pascal@kraupner.me',
        'bank' => 'Solaris',
        'bic' => 'SOBKDEB2XXX',
        'iban' => 'DE85110101015931725406',
    ],

    /*
    |--------------------------------------------------------------------------
    | Numbering
    |--------------------------------------------------------------------------
    | Prefix R with length 7 produces R0000003. Assigned on creation and
    | immutable, which is why invoices are cancelled rather than deleted.
    |
    */

    'number' => [
        'prefix' => env('INVOICE_NUMBER_PREFIX', 'R'),
        'length' => (int) env('INVOICE_NUMBER_LENGTH', 7),
    ],

    /*
    |--------------------------------------------------------------------------
    | Paper box
    |--------------------------------------------------------------------------
    | setPaper('a4') would give 595.28 x 841.89, 3.15pt shorter, shifting
    | everything positioned from the bottom edge.
    |
    */

    'paper' => [0.0, 0.0, 595.92, 845.04],

    'payment_terms_days' => (int) env('INVOICE_PAYMENT_TERMS_DAYS', 14),

    'default_unit' => 'h',

    'small_business_note' => 'Steuerbefreiung für Kleinunternehmer gemäß § 19 UStG.',

    /*
    |--------------------------------------------------------------------------
    | Service period
    |--------------------------------------------------------------------------
    | § 34a UStDV does not require a service date for § 19 turnover. Turn on
    | after a switch to standard taxation, where § 14 Abs. 4 applies in full.
    |
    */

    'show_service_period' => env('INVOICE_SHOW_SERVICE_PERIOD', false),

    /*
    |--------------------------------------------------------------------------
    | Formatting
    |--------------------------------------------------------------------------
    | de_DE regardless of app.locale (en), or amounts render as "1,400.00 €".
    |
    */

    'locale' => 'de_DE',

];
