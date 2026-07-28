<?php

declare(strict_types=1);

use App\Enums\LinkStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('links', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnUpdate()->cascadeOnDelete();
            $table->string('name', 150);
            $table->string('original_url', 2048);
            $table->string('short_code', 50)->unique();
            $table->boolean('is_custom_alias')->default(false);
            $table->string('status', 20)->default(LinkStatus::Active->value);
            $table->unsignedBigInteger('clicks_count')->default(0);
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_clicked_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_id');
            $table->index('status');
            $table->index('expires_at');
            $table->index('created_at');
            $table->index(['user_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('links');
    }
};
