<?php

return [
    'name' => env('COMPANY_NAME', 'Safety Shield Global Limited'),
    'address' => [
        'line1' => env('COMPANY_ADRS_LINE1', 'Unity House'),
        'line2' => env('COMPANY_ADRS_LINE2', 'Road Five, Winsford Industrial Estate'),
        'city' => env('COMPANY_ADRS_CITY', 'Winsford'),
        'postcode' => env('COMPANY_POSTAL_CODE', 'CW7 3RB'),
    ],
    'telephone' => env('COMPANY_PHONE', '01606 210 340'),
    'email' => env('COMPANY_EMAIL', 'info@safetyshieldglobal.com'),
    'website' => env('COMPANY_WEBSITE', 'www.safetyshieldglobal.com'),
];

