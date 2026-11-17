<?php

use App\Models\Project;
use Illuminate\Support\Facades\Artisan;

// Used by scripts/verify-ui.mjs and verify-flows.mjs to find demo projects to open.
Artisan::command('demo:ids', function () {
    $this->line(json_encode([
        'approved' => Project::where('status', 'approved')->where('is_restricted', false)->value('id'),
        'restricted' => Project::where('is_restricted', true)->value('id'),
        'pending' => Project::where('status', 'pending')->value('id'),
        'returned' => Project::where('status', 'returned')->value('id'),
        'draft' => Project::where('status', 'draft')->value('id'),
    ]));
})->purpose('Print ids of demo projects (for the browser checks)');
