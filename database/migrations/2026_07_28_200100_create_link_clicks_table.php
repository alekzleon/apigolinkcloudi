<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('link_clicks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('link_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('ip_hash', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('referrer', 2048)->nullable();
            $table->string('language', 50)->nullable();
            $table->string('device_type', 50)->nullable();
            $table->string('browser', 100)->nullable();
            $table->string('operating_system', 100)->nullable();
            $table->boolean('is_bot')->default(false);
            $table->string('utm_source', 255)->nullable();
            $table->string('utm_medium', 255)->nullable();
            $table->string('utm_campaign', 255)->nullable();
            $table->string('utm_content', 255)->nullable();
            $table->string('utm_term', 255)->nullable();
            $table->timestamp('clicked_at');
            $table->timestamps();

            $table->index('link_id');
            $table->index('clicked_at');
            $table->index(['link_id', 'clicked_at']);
            $table->index('utm_source');
            $table->index('device_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('link_clicks');
    }
};
