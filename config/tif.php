<?php

return [
    'days' => [
        '2026-07-31' => 'DAY1 (7/31)',
        '2026-08-01' => 'DAY2 (8/1)',
        '2026-08-02' => 'DAY3 (8/2)',
    ],

    /** Official main timetable column order (left → right). */
    'stage_order' => [
        'hot-stage',
        'heat-garage',
        'smile-garden',
        'doll-factory',
        'sky-stage',
        'torocco-park',
        'ukishima-stage',
        'info-centre',
    ],

    /** Compact header labels for full-width 8-column grid. */
    'stage_short_names' => [
        'hot-stage' => "HOT\nSTAGE",
        'smile-garden' => "SMILE\nGARDEN",
        'doll-factory' => "DOLL\nFACTORY",
        'heat-garage' => "HEAT\nGARAGE",
        'sky-stage' => "SKY\nSTAGE",
        'info-centre' => "INFO\nCENTRE",
        'torocco-park' => "TOROCCO\nPARK",
        'ukishima-stage' => "浮島\nSTAGE",
    ],

    /**
     * Stage colors from TIF 2026 official main timetable
     * (official.idolfes.com — .p-timetable__table-column).
     */
    'stage_themes' => [
        'hot-stage' => ['header' => '#ff7b75', 'column' => '#ff7b75', 'label' => '#ffffff'],
        'heat-garage' => ['header' => '#ff9c26', 'column' => '#ff9c26', 'label' => '#ffffff'],
        'smile-garden' => ['header' => '#b1d502', 'column' => '#b1d502', 'label' => '#ffffff'],
        'doll-factory' => ['header' => '#39c960', 'column' => '#39c960', 'label' => '#ffffff'],
        'sky-stage' => ['header' => '#6dc8f9', 'column' => '#6dc8f9', 'label' => '#ffffff'],
        'torocco-park' => ['header' => '#5f6ade', 'column' => '#5f6ade', 'label' => '#ffffff'],
        'ukishima-stage' => ['header' => '#f2bb01', 'column' => '#f2bb01', 'label' => '#ffffff'],
        'info-centre' => ['header' => '#cd6cbc', 'column' => '#cd6cbc', 'label' => '#ffffff'],
    ],

    'grid' => [
        'slot_minutes' => 10,
        'px_per_minute' => 2.2,
        'padding_minutes' => 15,
        /** Visual gap between back-to-back blocks on the same stage (px). */
        'block_gap_px' => 3,
    ],

    /** Allow ?now= simulation for timetable NOW line (disable in production). */
    'allow_now_override' => env('TIF_ALLOW_NOW_OVERRIDE', env('APP_DEBUG', false)),

    /** Optional default simulated time when override is enabled (Y-m-d H:i:s). */
    'fake_now' => env('TIF_FAKE_NOW'),
];
