<?php
return [
    "success"=>"A művelet sikeres volt.",
    "error"=>"A művelet sikertelen volt.",
    "denied"=>[
        "role"=>"Megtagadva: Ehhez a művelethez nincs szerepköre.",
        "user"=>[
            "active"=>"Művelet megtagadva: a felahasználó nem aktív",
        ],
        "teacher"=>"A megadott tanár nem tartozik az iskolához.",
        "permission"=>"Megtagadva: Ehhez a művelethez nincs jogosultsága."
    ],
    "notFound"=>[
        "user"=>"Felhasználó nem található",
        "role"=>"Nem található szerepkör a felhasználóhoz",
        "school"=>"Az iskola nem található",
        "location"=>"Nem található helyszín az iskolához.",
        "search"=>"A keresésnek megfelelő elem nem található.",
        "child"=>"Nem található gyerek ehhez a felhasználóhoz",
        "course"=>"Nem található kurzus."
    ],
    "invalid"=>[
        "name"=>"A megadott név mező érvénytelen.",
        "year"=>"A tanév nem létezik vagy lezárva.",
        "location"=>"Érvénytelen helyszínazonosító."
    ],
    "attached"=>[
        "role"=>"Ez a szerepkör már hozzá van rendelve a felhasználóhoz.",
        "location"=>"Ez a helyszín már hozzá van rendelve ehhez az iskolához",
        "exists"=>"A megadott adatok már szerepelnek a rendszerben."
    ],
    "detached"=>[
        "location"=>"A helyszín leválasztva az iskoláról."
    ],
    "unique"=>[
        "course"=>"A kurzusnak egyedinek kell lennie a tanéven belül."
    ]
];
