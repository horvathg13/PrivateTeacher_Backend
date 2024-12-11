<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    'accepted' => 'The :attribute field must be accepted.',
    'accepted_if' => 'The :attribute field must be accepted when :other is :value.',
    'active_url' => 'The :attribute field must be a valid URL.',
    'after' => 'A(z) :attribute mezőnek :date utáni dátumnak kell lennie.',
    'after_or_equal' => 'The :attribute field must be a date after or equal to :date.',
    'alpha' => 'The :attribute field must only contain letters.',
    'alpha_dash' => 'The :attribute field must only contain letters, numbers, dashes, and underscores.',
    'alpha_num' => 'The :attribute field must only contain letters and numbers.',
    'array' => 'The :attribute field must be an array.',
    'ascii' => 'The :attribute field must only contain single-byte alphanumeric characters and symbols.',
    'before' => 'The :attribute field must be a date before :date.',
    'before_or_equal' => 'The :attribute field must be a date before or equal to :date.',
    'between' => [
        'array' => 'The :attribute field must have between :min and :max items.',
        'file' => 'The :attribute field must be between :min and :max kilobytes.',
        'numeric' => 'The :attribute field must be between :min and :max.',
        'string' => 'The :attribute field must be between :min and :max characters.',
    ],
    'boolean' => 'The :attribute field must be true or false.',
    'can' => 'The :attribute field contains an unauthorized value.',
    'confirmed' => 'The :attribute field confirmation does not match.',
    'contains' => 'The :attribute field is missing a required value.',
    'current_password' => 'The password is incorrect.',
    'date' => 'The :attribute field must be a valid date.',
    'date_equals' => 'The :attribute field must be a date equal to :date.',
    'date_format' => 'The :attribute field must match the format :format.',
    'decimal' => 'The :attribute field must have :decimal decimal places.',
    'declined' => 'The :attribute field must be declined.',
    'declined_if' => 'The :attribute field must be declined when :other is :value.',
    'different' => 'The :attribute field and :other must be different.',
    'digits' => 'The :attribute field must be :digits digits.',
    'digits_between' => 'The :attribute field must be between :min and :max digits.',
    'dimensions' => 'The :attribute field has invalid image dimensions.',
    'distinct' => 'The :attribute field has a duplicate value.',
    'doesnt_end_with' => 'The :attribute field must not end with one of the following: :values.',
    'doesnt_start_with' => 'The :attribute field must not start with one of the following: :values.',
    'email' => 'The :attribute field must be a valid email address.',
    'ends_with' => 'The :attribute field must end with one of the following: :values.',
    'enum' => 'The selected :attribute is invalid.',
    'exists' => 'A megadott :attribute  nem létezik.',
    'extensions' => 'The :attribute field must have one of the following extensions: :values.',
    'file' => 'The :attribute field must be a file.',
    'filled' => 'The :attribute field must have a value.',
    'gt' => [
        'array' => 'The :attribute field must have more than :value items.',
        'file' => 'The :attribute field must be greater than :value kilobytes.',
        'numeric' => 'The :attribute field must be greater than :value.',
        'string' => 'The :attribute field must be greater than :value characters.',
    ],
    'gte' => [
        'array' => 'The :attribute field must have :value items or more.',
        'file' => 'The :attribute field must be greater than or equal to :value kilobytes.',
        'numeric' => 'The :attribute field must be greater than or equal to :value.',
        'string' => 'The :attribute field must be greater than or equal to :value characters.',
    ],
    'hex_color' => 'The :attribute field must be a valid hexadecimal color.',
    'image' => 'The :attribute field must be an image.',
    'in' => 'The selected :attribute is invalid.',
    'in_array' => 'The :attribute field must exist in :other.',
    'integer' => 'The :attribute field must be an integer.',
    'ip' => 'The :attribute field must be a valid IP address.',
    'ipv4' => 'The :attribute field must be a valid IPv4 address.',
    'ipv6' => 'The :attribute field must be a valid IPv6 address.',
    'json' => 'The :attribute field must be a valid JSON string.',
    'list' => 'The :attribute field must be a list.',
    'lowercase' => 'The :attribute field must be lowercase.',
    'lt' => [
        'array' => 'The :attribute field must have less than :value items.',
        'file' => 'The :attribute field must be less than :value kilobytes.',
        'numeric' => 'The :attribute field must be less than :value.',
        'string' => 'The :attribute field must be less than :value characters.',
    ],
    'lte' => [
        'array' => 'The :attribute field must not have more than :value items.',
        'file' => 'The :attribute field must be less than or equal to :value kilobytes.',
        'numeric' => 'The :attribute field must be less than or equal to :value.',
        'string' => 'The :attribute field must be less than or equal to :value characters.',
    ],
    'mac_address' => 'The :attribute field must be a valid MAC address.',
    'max' => [
        'array' => 'The :attribute field must not have more than :max items.',
        'file' => 'The :attribute field must not be greater than :max kilobytes.',
        'numeric' => 'The :attribute field must not be greater than :max.',
        'string' => 'The :attribute field must not be greater than :max characters.',
    ],
    'max_digits' => 'The :attribute field must not have more than :max digits.',
    'mimes' => 'The :attribute field must be a file of type: :values.',
    'mimetypes' => 'The :attribute field must be a file of type: :values.',
    'min' => [
        'array' => 'The :attribute field must have at least :min items.',
        'file' => 'The :attribute field must be at least :min kilobytes.',
        'numeric' => 'The :attribute field must be at least :min.',
        'string' => 'The :attribute field must be at least :min characters.',
    ],
    'min_digits' => 'The :attribute field must have at least :min digits.',
    'missing' => 'The :attribute field must be missing.',
    'missing_if' => 'The :attribute field must be missing when :other is :value.',
    'missing_unless' => 'The :attribute field must be missing unless :other is :value.',
    'missing_with' => 'The :attribute field must be missing when :values is present.',
    'missing_with_all' => 'The :attribute field must be missing when :values are present.',
    'multiple_of' => 'The :attribute field must be a multiple of :value.',
    'not_in' => 'The selected :attribute is invalid.',
    'not_regex' => 'The :attribute field format is invalid.',
    'numeric' => 'The :attribute field must be a number.',
    'password' => [
        'letters' => 'The :attribute field must contain at least one letter.',
        'mixed' => 'The :attribute field must contain at least one uppercase and one lowercase letter.',
        'numbers' => 'The :attribute field must contain at least one number.',
        'symbols' => 'The :attribute field must contain at least one symbol.',
        'uncompromised' => 'The given :attribute has appeared in a data leak. Please choose a different :attribute.',
    ],
    'present' => 'The :attribute field must be present.',
    'present_if' => 'The :attribute field must be present when :other is :value.',
    'present_unless' => 'The :attribute field must be present unless :other is :value.',
    'present_with' => 'The :attribute field must be present when :values is present.',
    'present_with_all' => 'The :attribute field must be present when :values are present.',
    'prohibited' => 'The :attribute field is prohibited.',
    'prohibited_if' => 'The :attribute field is prohibited when :other is :value.',
    'prohibited_unless' => 'The :attribute field is prohibited unless :other is in :values.',
    'prohibits' => 'The :attribute field prohibits :other from being present.',
    'regex' => 'The :attribute field format is invalid.',
    'required' => 'A(z) :attribute mező megadása kötelező.',
    'required_array_keys' => 'The :attribute field must contain entries for: :values.',
    'required_if' => 'The :attribute field is required when :other is :value.',
    'required_if_accepted' => 'The :attribute field is required when :other is accepted.',
    'required_if_declined' => 'The :attribute field is required when :other is declined.',
    'required_unless' => 'The :attribute field is required unless :other is in :values.',
    'required_with' => 'The :attribute field is required when :values is present.',
    'required_with_all' => 'The :attribute field is required when :values are present.',
    'required_without' => 'The :attribute field is required when :values is not present.',
    'required_without_all' => 'The :attribute field is required when none of :values are present.',
    'same' => 'A(z) :attribute mezőnek meg kell egyeznie a(z) :other mezővel.',
    'size' => [
        'array' => 'The :attribute field must contain :size items.',
        'file' => 'The :attribute field must be :size kilobytes.',
        'numeric' => 'The :attribute field must be :size.',
        'string' => 'The :attribute field must be :size characters.',
    ],
    'starts_with' => 'The :attribute field must start with one of the following: :values.',
    'string' => 'The :attribute field must be a string.',
    'timezone' => 'The :attribute field must be a valid timezone.',
    'unique' => 'The :attribute has already been taken.',
    'uploaded' => 'The :attribute failed to upload.',
    'uppercase' => 'The :attribute field must be uppercase.',
    'url' => 'The :attribute field must be a valid URL.',
    'ulid' => 'The :attribute field must be a valid ULID.',
    'uuid' => 'The :attribute field must be a valid UUID.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "attribute.rule" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        /*'attribute-name' => [
            'rule-name' => 'custom-message',
        ],*/
        'fname'=>[
            'required'=>'Keresztnév megadása kötelező',
            'max'=>"A keresztnév túl hosszú."
        ],
        'lname'=>[
            'required'=>'Vezetéknév megadása kötelező',
            'max'=>"A vezetéknév túl hosszú."
        ],
        "username"=>[
            "required"=>"Felhasználónév megadása kötelező",
            "unique"=>"A felhasználónév nem egyedi.",
            "max"=>"A felhasználónév túl hosszú."
        ],
        'email'=>[
            'required'=>'Email megadása kötelező',
            'unique'=>'Már létezik ilyen email cím a rendszerben',
            "max"=>"Az email cím túl hosszú"
        ],
        'password'=>[
            'required'=>'Jelszó megadása kötelező',
            "max"=>"A megadott jelszó túl hosszú"
        ],
        'cpassword'=>[
            'required'=>'Jelszó megerősítése kötelező',
            'same'=>'A jelszavak nem egyeznek'
        ],
        'birthday'=>[
            'required'=>'A születési dátum megadása kötelező.',
            'date'=>'A születési dátumnak dátum formátumnak kell lennie.',
            "before"=>"A születési dátum événytelen."
        ],
        "name"=>[
            "required"=>"Név megadása kötelező",
            "max"=>"A név túl hosszú"
        ],
        "name.*.lang"=>[
            "required"=>"A név nyelvének kiválasztása"
        ],
        "name.*.name"=>[
            "required"=>"Név megadása kötelező"
        ],
        "studentLimit"=>[
            "required"=>"A tanulói létszám limit megadása kötelező",
            "min"=>"A tanulói létszám limit értékének nullától nagyobb számnak kell lennie."
        ],
        "minutesLesson"=>[
            "required"=>"A tanóra hosszát (percben) kötelező megadni.",
            "min"=>"A tanóra hosszának nullától nagyobb számnak kell lennie."
        ],
        "minTeachingDay"=>[
            "required"=>"A minimális tanórák számát kötelező megadni",
            "min"=>"A minimális tanórák számának nullától nagyobb számnak kell lennie."
        ],
        "coursePricePerLesson"=>[
            "required"=>"A kurzus árát kötelező megadni",
            "min"=>"A kurzus árának egytől nagyobb pozitív számnak kell lennie"
        ],
        "locationId"=>[
            "required"=>"A helyszín megadása kötelező",
            "nullable" => "A helyszín megadása opcionális.",
            "exists" => "A megadott helyszín nem található az adatbázisban.",
            "numeric"=>"A helyszín azonosítónak számnak kell lennie."
        ],
        "labels"=>[
            "required"=>"Címkék megadása kötelező"
        ],
        "paymentPeriod"=>[
            "required"=>"A fizetési periódus megadása kötelező."
        ],
        "currency"=>[
            "required"=>"A pénznem megadása kötelező."
        ],
        "country" => [
            "required" => "Az ország megadása kötelező.",
            "max"=>"Az ország neve túl hosszú"
        ],
        "zip" => [
            "required" => "Az irányítószám megadása kötelező.",
            "max"=>"Az irányítószám túl hosszú"
        ],
        "city" => [
            "required" => "A város megadása kötelező.",
            "max"=>"A város neve túl hosszú"
        ],
        "street" => [
            "required" => "Az utca megadása kötelező.",
            "max"=>"Az utca neve túl hosszú"
        ],
        "number" => [
            "required" => "A házszám megadása kötelező.",
            "max"=>"A házszám túl hosszú"
        ],
        "floor" => [
            "nullable" => "Az emelet megadása opcionális.",
            "max"=>"Az emelet mező értéke túl hosszú"
        ],
        "door" => [
            "nullable" => "Az ajtószám megadása opcionális.",
            "max"=>"Az ajtószám mező értéke túl hosszú"
        ],
        "selectedCourseId" => [
            "nullable" => "A kiválasztott kurzus megadása opcionális.",
            "exists" => "A megadott kurzus nem található az adatbázisban."
        ],
        "message"=>[
            "required"=>"Üzenet megadása kötelező.",
            "max"=>"Az üzenet túl hosszú"
        ],
        "childId" => [
            "required" => "A gyermek azonosító megadása kötelező.",
            "exists" => "A megadott gyermek nem található az adatbázisban.",
            "numeric"=>"A gyermek azonosítónak számnam kell lennie."
        ],
        "courseId" => [
            "required" => "A kurzus azonosító megadása kötelező.",
            "exists" => "A megadott kurzus nem található az adatbázisban.",
            "numeric"=>"A kurzus azonosítónak számnak kell lennie."
        ],
        "notice" => [
            "nullable" => "A megjegyzés megadása opcionális.",
            "max"=>"A megjegyzés túl hosszú."
        ],
        "numberOfLesson" => [
            "required" => "A tanórák számának megadása kötelező.",
            "integer" => "A tanórák számának egész számnak kell lennie.",
            "min" => "A tanórák számának legalább 1-nek kell lennie."
        ],
        "teacher_course_request_id"=>[
            "required"=>"A kérvény kiválsztása kötelező."
        ],
        "requestId"=>[
            "required" => "A kérelem azonosítójának megadása kötelező.",
            "exists" => "A kérelem nem található az adatbázisban.",
            "numeric"=>"A kérelem azonosítónak számnak kell lennie."
        ],
        "messageId"=>[
            "required" => "Az üzenet azonosítójának megadása kötelező.",
            "exists" => "Az üzenet nem található az adatbázisban.",
            "numeric"=>"Az üzenet azonosítónak számnak kell lennie."
        ]

    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [],

];
