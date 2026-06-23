<?php

declare(strict_types=1);

return [
    /*
    | Numeric shirt measurement fields (inches). Free-text notes are note_1..note_5.
    */
    'shirt_fields' => [
        'chest', 'waist', 'hip', 'shoulder', 'sleeve_length', 'shirt_length',
        'collar', 'cuff', 'arm_round', 'neck', 'front_chest', 'cross_back',
        'dart', 'bicep', 'wrist',
    ],

    /*
    | Numeric pant measurement fields (inches).
    */
    'pant_fields' => [
        'waist', 'hip', 'thigh', 'knee', 'bottom', 'length',
        'in_seam', 'out_seam', 'crotch', 'fly',
    ],

    'note_count' => 5,

    'field_min' => 0,
    'field_max' => 100,

    /*
    | Per-field significant-change thresholds (inches). A version whose change vs
    | the prior approved version exceeds a field's threshold requires supervisor
    | approval. Looked up by bare field name, falling back to 'default'.
    */
    'thresholds' => [
        'chest' => 2.0,
        'waist' => 1.5,
        'hip' => 1.5,
        'shirt_length' => 1.5,
        'length' => 1.5,
        'shoulder' => 1.0,
        'default' => 1.0,
    ],
];
