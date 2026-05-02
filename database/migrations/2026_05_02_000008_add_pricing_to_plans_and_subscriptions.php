<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_plans', function (Blueprint $table) {
            $table->unsignedBigInteger('price')->default(0)->after('description');
            $table->string('currency', 10)->default('IDR')->after('price');
            $table->string('billing_period', 20)->default('monthly')->after('currency');
        });

        Schema::table('subscriptions', function (Blueprint $table) {
            $table->unsignedBigInteger('price')->default(0)->after('plan_name');
            $table->string('currency', 10)->default('IDR')->after('price');
        });

        DB::table('product_plans')->where('slug', 'free')->update(['price' => 0]);
        DB::table('product_plans')->where('slug', 'starter')->update(['price' => 99000]);
        DB::table('product_plans')->where('slug', 'media')->update(['price' => 199000]);
        DB::table('product_plans')->where('slug', 'complete')->update(['price' => 399000]);
        DB::table('product_plans')->where('slug', 'custom')->update(['price' => 0, 'billing_period' => 'custom']);
    }

    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn(['price', 'currency']);
        });

        Schema::table('product_plans', function (Blueprint $table) {
            $table->dropColumn(['price', 'currency', 'billing_period']);
        });
    }
};
