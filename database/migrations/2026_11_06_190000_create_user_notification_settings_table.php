<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notification_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->boolean('notify_messages_by_email')->default(false);
            $table->boolean('notify_comments_by_email')->default(false);
            $table->boolean('notify_saves_by_email')->default(false);
            $table->timestamps();
        });

        DB::table('user_notification_settings')->insertUsing(
            ['user_id', 'notify_messages_by_email', 'notify_comments_by_email', 'notify_saves_by_email'],
            DB::table('users')->select([
                'id', 'notify_messages_by_email', 'notify_comments_by_email', 'notify_saves_by_email',
            ]),
        );

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'notify_messages_by_email',
                'notify_comments_by_email',
                'notify_saves_by_email',
            ]);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->boolean('notify_messages_by_email')->default(false);
            $table->boolean('notify_comments_by_email')->default(false);
            $table->boolean('notify_saves_by_email')->default(false);
        });

        DB::table('user_notification_settings')->orderBy('user_id')->chunkById(500, function ($settings) {
            foreach ($settings as $setting) {
                DB::table('users')->where('id', $setting->user_id)->update([
                    'notify_messages_by_email' => $setting->notify_messages_by_email,
                    'notify_comments_by_email' => $setting->notify_comments_by_email,
                    'notify_saves_by_email' => $setting->notify_saves_by_email,
                ]);
            }
        }, 'user_id');

        Schema::dropIfExists('user_notification_settings');
    }
};
