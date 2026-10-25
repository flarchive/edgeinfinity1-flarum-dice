<?php
use Illuminate\Database\Schema\Builder;
use Illuminate\Database\Schema\Blueprint;
return [
    'up' => function (Builder $schema): void {
        if (!$schema->hasColumn('posts', 'edge_dice')) {
            $schema->table('posts', function (Blueprint $table): void {
                // Same INSERT as the post: no dangling records, including while disabled.
                $table->text('edge_dice')->nullable();
            });
        }
    },
    // Deliberately retained on Flarum's "purge": reinstall restores old rolls.
    // A DBA can explicitly remove this column after exporting it; see docs/LIFECYCLE.md.
    'down' => function (Builder $schema): void {},
];
