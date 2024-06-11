<?php
return [
    "success" => "Operation was Successful.",
    "error" => "Operation was Unsuccessful.",
    "denied"=>[
        "role"=>"Denied: You have no role to this operation",
        "user"=>[
            "active"=>"Operation denied: user is not active.",
        ],
    ],
    "notFound"=>[
        "user"=>"User not found.",
        "role"=>"Role not found to this user.",
        "school"=>"School not found",
        "location"=>"School location not found to this school.",
        "search"=>"No matching items found",
        "child"=>"No child connected to this user."
    ],
    "attached"=>[
        "role"=>"This role already attached to this user.",
        "location"=>"This location already attached to this school",
    ],
    "detached"=>[
        "location"=>"School Location detached from this school"
    ],
    "unique"=>[
        "course"=>"The course must be unique until a school year"
    ]

];
