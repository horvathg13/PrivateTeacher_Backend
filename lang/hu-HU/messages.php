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
        "permission"=>"Megtagadva: Ehhez a művelethez nincs jogosultsága.",
        "locationInUse"=>"Megtagadva: A helyszín használatban van."
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
    ],
    "notification"=>[
        "rejected"=>"A kérelme elutasításra került. Kattintson a részletekért.",
        "accepted"=>"A kérelmét elfogadták. Kattintson a részletekért.",
        "terminationOfCourse"=>"Kurzusmegszüntetési kérelme érkezett.",
        "terminationAccepted"=>"Kurzusmegszüntetési kérelmét elfogadták",
        "terminationRejected"=>"Kurzusmegszüntetési kérelmét elutasították."

    ],
    "status"=>[
        "read"=>"Olvasott",
        "unread"=>"Olvasatlan",
    ],
    "studentLimit"=>[
        "goodDay"=>"A kurzus megtelt. A következő szabad időpont :goodDay.",
        "null"=>"A kurzus megtelt. Nem találtaltunk szabad időpontot."
    ],
    "hack_attempt"=>"A szerver gyanús tevékenységet észlelt, ezért kérését elutasította és fiókja felfüggesztésre került. Keresse meg a rendszer üzemeltetőjét további információért."

];
