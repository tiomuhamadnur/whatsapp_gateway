<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->string('target_type', 20)->default('contact')->after('direction');
            $table->json('broadcast_targets')->nullable()->after('from_number');
            $table->string('recurrence', 20)->nullable()->after('scheduled_at');
            $table->unsignedInteger('recurrence_interval')->default(1)->after('recurrence');
            $table->timestamp('recurrence_until')->nullable()->after('recurrence_interval');
            $table->foreignId('parent_message_id')->nullable()->after('recurrence_until')->constrained('messages')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('messages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('parent_message_id');
            $table->dropColumn([
                'target_type',
                'broadcast_targets',
                'recurrence',
                'recurrence_interval',
                'recurrence_until',
            ]);
        });
    }
};
