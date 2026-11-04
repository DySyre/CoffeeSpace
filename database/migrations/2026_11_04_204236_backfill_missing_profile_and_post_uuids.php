<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // Fill only missing identifiers; preserve every existing shared link.
        foreach (['users' => 'profile_uuid', 'posts' => 'post_uuid'] as $table => $column) {
            DB::table($table)->whereNull($column)->select('id')->orderBy('id')->chunkById(200, function ($rows) use ($table, $column) {
                foreach ($rows as $row) {
                    DB::table($table)->where('id', $row->id)->whereNull($column)->update([$column => (string) Str::uuid()]);
                }
            });
        }
    }

    public function down(): void
    {
        // Generated UUIDs are retained so rolling back never invalidates shared links.
    }
};
