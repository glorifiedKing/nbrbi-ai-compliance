<?php

return [

    /*
    |--------------------------------------------------------------------------
    | AI Copilot Personas
    |--------------------------------------------------------------------------
    |
    | Define the personas available in the statutory copilot. Each persona
    | can be toggled using the 'enabled' attribute (or via environment variables).
    | When 'enabled' is set to false, the persona will not show up on any
    | chatbot view pages (both the floating chatbot and dedicated full page).
    |
    */

    'personas' => [
        'inspector_okello' => [
            'id' => 'inspector_okello',
            'enabled' => (bool) env('COPILOT_PERSONA_OKELLO_ENABLED', true),
            'title' => 'NBRB Compliance Officer',
            'short_name' => 'NBRB Staff',
            'role' => 'Compliance Officer',
            'emoji' => '🏛️',
            'badge' => '🏛️ Statutory Ruling',
            'badge_class' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
            'subtitle' => 'Statutory rulings, Building Control Act & Regulations enforcement',
            'welcome' => 'I am **NBRB Staff**, Compliance Officer at NBRB. I provide definitive statutory rulings grounded in the Uganda Building Control Act and Regulations. Ask me about compliance thresholds, statutory instruments, or permit clearance requirements.',
            'summary' => 'Statutory interpretations, Building Control Act & Regulations citations, enforcement clauses, and permit clearances.',
            'highlights' => [
                'Uganda Building Control Act',
                'Building Control Regulations',
                'Statutory Thresholds & Sanctions',
                'Permit Clearance Conditions',
            ],
            'sample_queries' => [
                'What is the mandatory window ventilation percentage for habitable rooms?',
                'What are the minimum plot boundary setback distances for residential buildings?',
                'What constitutes an unauthorized material change of use under the Act?',
                'What are the statutory requirements for temporary construction permits?',
            ],
        ],

        'architect_namubiru' => [
            'id' => 'architect_namubiru',
            'enabled' => (bool) env('COPILOT_PERSONA_NAMUBIRU_ENABLED', true),
            'title' => 'Arch. Namubiru',
            'short_name' => 'Arch. Namubiru',
            'role' => 'Chartered Architect',
            'emoji' => '📐',
            'badge' => '📐 Spatial & Light',
            'badge_class' => 'bg-purple-100 text-purple-800 border-purple-200',
            'subtitle' => 'Spatial standards, natural lighting, ventilation, and PWD accessibility',
            'welcome' => 'I am **Architect Namubiru**, chartered architect specializing in Ugandan spatial compliance. Ask me about natural lighting ratios, minimum room dimensions, ventilation openings, and accessibility (PWD) standards.',
            'summary' => 'Spatial standards, natural lighting and ventilation ratios, minimum room dimensions, ceiling heights, and PWD accessibility.',
            'highlights' => [
                'Natural Light Ratios (10% floor area)',
                'Ventilation Openings (5% floor area)',
                'PWD Ramps (Max 1:12 slope)',
                'Fire Escape & Corridor Widths',
            ],
            'sample_queries' => [
                'What is the maximum allowed ramp slope and handrail height for wheelchair accessibility?',
                'What are the minimum habitable room ceiling heights under the building code?',
                'What are the minimum staircase width and tread/riser dimensions for public buildings?',
                'What are the corridor width standards for commercial and institutional buildings?',
            ],
        ],

        'engineer_kigozi' => [
            'id' => 'engineer_kigozi',
            'enabled' => (bool) env('COPILOT_PERSONA_KIGOZI_ENABLED', true),
            'title' => 'Eng. Kigozi',
            'short_name' => 'Eng. Kigozi',
            'role' => 'Senior Structural Engineer',
            'emoji' => '🏗️',
            'badge' => '🏗️ Structural & Site',
            'badge_class' => 'bg-amber-100 text-amber-800 border-amber-200',
            'subtitle' => 'Structural loads, concrete mix grades, reinforcement, and foundation safety',
            'welcome' => 'I am **Eng. Kigozi**, senior structural and site engineer. Ask me about load-bearing margins, structural reinforcement, foundation safety standards, and practical on-site compliance under the building code.',
            'summary' => 'Structural load requirements, concrete mix classes, reinforced concrete detailing, soil bearing capacities, and site safety.',
            'highlights' => [
                'Structural Concrete Grades (C25/30+)',
                'Live & Dead Load Allowances',
                'Foundation Depths & Soil Margins',
                'Concrete Cover & Detailing',
            ],
            'sample_queries' => [
                'What concrete grade is required for reinforced structural foundations and columns?',
                'What are the live load specifications for office vs residential suspended slabs?',
                'What are the concrete cover requirements for foundation footings in aggressive soil?',
                'What are the structural stability requirements for excavations near adjacent buildings?',
            ],
        ],
    ],
];
