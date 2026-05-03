<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_plans', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('slug', 100)->unique();
            $table->text('description')->nullable();
            $table->unsignedInteger('daily_message_quota')->default(100);
            $table->unsignedInteger('max_sessions')->default(1);
            $table->json('allowed_message_types')->nullable();
            $table->boolean('can_send_media')->default(false);
            $table->boolean('can_use_webhook')->default(false);
            $table->boolean('enforce_footer')->default(false);
            $table->string('footer_text')->nullable();
            $table->boolean('is_custom')->default(false);
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();
        });

        DB::table('product_plans')->insert([
            [
                'name' => 'Free',
                'slug' => 'free',
                'description' => '100 pesan text per hari dengan footer platform wajib.',
                'daily_message_quota' => 100,
                'max_sessions' => 1,
                'allowed_message_types' => json_encode(['text']),
                'can_send_media' => false,
                'can_use_webhook' => false,
                'enforce_footer' => true,
                'footer_text' => "\n\nPowered by SapaChat",
                'is_custom' => false,
                'is_active' => true,
                'sort_order' => 10,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Starter',
                'slug' => 'starter',
                'description' => '1000 pesan text per hari.',
                'daily_message_quota' => 1000,
                'max_sessions' => 1,
                'allowed_message_types' => json_encode(['text']),
                'can_send_media' => false,
                'can_use_webhook' => false,
                'enforce_footer' => false,
                'footer_text' => null,
                'is_custom' => false,
                'is_active' => true,
                'sort_order' => 20,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Media',
                'slug' => 'media',
                'description' => '1000 pesan per hari dengan text, foto, dan video.',
                'daily_message_quota' => 1000,
                'max_sessions' => 2,
                'allowed_message_types' => json_encode(['text', 'image', 'video']),
                'can_send_media' => true,
                'can_use_webhook' => false,
                'enforce_footer' => false,
                'footer_text' => null,
                'is_custom' => false,
                'is_active' => true,
                'sort_order' => 30,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Complete',
                'slug' => 'complete',
                'description' => 'Paket lengkap dengan media dan webhook.',
                'daily_message_quota' => 5000,
                'max_sessions' => 5,
                'allowed_message_types' => json_encode(['text', 'image', 'document', 'audio', 'video']),
                'can_send_media' => true,
                'can_use_webhook' => true,
                'enforce_footer' => false,
                'footer_text' => null,
                'is_custom' => false,
                'is_active' => true,
                'sort_order' => 40,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Custom',
                'slug' => 'custom',
                'description' => 'Paket custom sesuai kebutuhan tenant.',
                'daily_message_quota' => 10000,
                'max_sessions' => 10,
                'allowed_message_types' => json_encode(['text', 'image', 'document', 'audio', 'video']),
                'can_send_media' => true,
                'can_use_webhook' => true,
                'enforce_footer' => false,
                'footer_text' => null,
                'is_custom' => true,
                'is_active' => true,
                'sort_order' => 50,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('product_plans');
    }
};
