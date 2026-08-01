<?php

return [
    'features' => [
        'html_learning_engine' => env('FEATURE_HTML_LEARNING_ENGINE', true),
        'html_code_editor' => env('FEATURE_HTML_CODE_EDITOR', true),
        'html_visual_builder' => env('FEATURE_HTML_VISUAL_BUILDER', true),
        'html_project_assignments' => env('FEATURE_HTML_PROJECT_ASSIGNMENTS', true),
        'html_teacher_review' => env('FEATURE_HTML_TEACHER_REVIEW', true),
        'html_parent_preview' => env('FEATURE_HTML_PARENT_PREVIEW', true),
        'html_private_showcase' => env('FEATURE_HTML_PRIVATE_SHOWCASE', true),
        'html_adaptive_practice' => env('FEATURE_HTML_ADAPTIVE_PRACTICE', true),
    ],

    'curriculum' => [
        'worlds' => [
            1 => 'Computer Discovery',
            2 => 'Mouse Adventure',
            3 => 'Keyboard Island',
            4 => 'Typing Jungle',
            5 => 'Capital City',
            6 => 'Symbol Mountain',
            7 => 'Logic Land',
            8 => 'Block Coding Village',
            9 => 'HTML Builder Bay',
            10 => 'CSS Colour Kingdom',
            11 => 'JavaScript Action City',
            12 => 'Young Creator Studio',
        ],
        'default_world_name' => 'Keyboard Island',
        'default_world_index' => 3,
        'missions_per_world' => 12,
    ],

    'learning_progression' => [
        'Watch an activity',
        'Hear the instruction',
        'Find the correct object, key or symbol',
        'Drag or select the answer',
        'Type the answer',
        'Complete missing code',
        'Repair simple code',
        'Write and run small pieces of code',
        'Build an original webpage or mini-game',
    ],

    'child_dashboard' => [
        'level_step' => 20,
        'default_badge_name' => 'Key Explorer',
    ],
];
