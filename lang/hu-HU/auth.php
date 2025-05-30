<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Authentication Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines are used during authentication for various
    | messages that we need to display to the user. You are free to modify
    | these language lines according to your application's requirements.
    |
    */

    'failed' => 'Hibás bejelentkezési adatok',
    'password' => 'Hibás jelszó',
    'throttle' => 'Túl sok bejelentkezési kísérlet. Póbálja :seconds másodperc múlva.',
    'logout'=>[
        'success'=>'Sikeres kijelentkezés',
        'fail'=>'Hiba történt a kijelentkezés közben'
    ],
    'token'=>'Érvénytelen token',
    "token_refresh_error"=>'A token frissítése közben hiba történt',
    "invalid"=>[
        "credentials"=>"Érvénytelen bejelentkezési adatok.",
        "email"=>"Érvénytelen email."
    ]

];
