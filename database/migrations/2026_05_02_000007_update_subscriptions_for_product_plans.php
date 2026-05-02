<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->foreignId('product_plan_id')->nullable()->after('user_id')->constrained()->nullOnDelete();
            $table->unsignedInteger('messages_used_today')->default(0)->after('messages_used');
            $table->date('quota_resets_on')->nullable()->after('messages_used_today');
        });

        $freePlanId = DB::table('product_plans')->where('slug', 'free')->value('id');

        DB::table('subscriptions')->update([
            'product_plan_id' => $freePlanId,
            'quota_resets_on' => now()->toDateString(),
        ]);

        DB::table('users')
            ->where('email', 'tiomuhamadnur@gmail.com')
            ->update(['role' => 'superadmin']);
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_plan_id');
            $table->dropColumn(['messages_used_today', 'quota_resets_on']);
        });
    }
};
