<?php
return [
    "success"=>"A művelet sikeres volt.",
    "error"=>"A művelet sikertelen volt.",
    "denied"=>[
        "role"=>"Megtagadva: Ehhez a művelethez nincs jogosultsága",
        "user"=>[
            "active"=>"Művelet megtagadva: a felahasználó nem aktív",
        ]
    ],
    "notFound"=>[
        "user"=>"Felhasználó nem található",
        "role"=>"Nem található szerepkör a felhasználóhoz",
        "school"=>"Az iskola nem található",
        "location"=>"Nem található helyszín az iskolához.",
        "search"=>"A keresésnek megfelelő elem nem található.",
        "child"=>"Nem található gyerek ehhez a felhasználóhoz"
    ],
    "attached"=>[
        "role"=>"Ez a szerepkör már hozzá van rendelve a felhasználóhoz.",
        "location"=>"Ez a helyszín már hozzá van rendelve ehhez az iskolához",
    ],
    "detached"=>[
        "location"=>"A helyszín leválasztva az iskoláról."
    ],
    "unique"=>[
        "course"=>"A kurzusnak egyedinek kell lennie a tanéven belül."
    ]
];
