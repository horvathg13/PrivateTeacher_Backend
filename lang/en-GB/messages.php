<?php
return [
    "success" => "Operation was Successful.",
    "error" => "Operation was Unsuccessful.",
    "denied"=>[
        "role"=>"Denied: You have no role to this operation",
        "user"=>[
            "active"=>"Operation denied: user is not active.",
        ],
        "teacher"=>"The requested teacher is not attached to this school.",
        "permission"=>"Denied: You have no permission to this operation."
    ],
    "notFound"=>[
        "user"=>"User not found.",
        "role"=>"Role not found to this user.",
        "school"=>"School not found",
        "location"=>"School location not found to this school.",
        "search"=>"No matching items found",
        "child"=>"No child connected to this user.",
        "course"=>"Course not found."
    ],
    "invalid"=>[
        "name"=>"The nem filed is invalid.",
        "year"=>"The requested school year do not exists or closed.",
        "location"=>"The given location id is not valid.",
    ],
    "attached"=>[
        "role"=>"This role already attached to this user.",
        "location"=>"This location already attached to this school",
        "exists"=>"The given data already exists."
    ],
    "detached"=>[
        "location"=>"School Location detached from this school"
    ],
    "unique"=>[
        "course"=>"The course must be unique until a school year"
    ],
    "notification"=>[
        "rejected"=>"Your request has been rejected. Click here for the details.",
        "accepted"=>"Your request has been accepted. Click here for the details.",
    ]
];
