<?php

use App\Models\DirectTransfer;
use App\Models\ProductVariant;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('direct_transfer_items', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(DirectTransfer::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(ProductVariant::class)->constrained()->restrictOnDelete();
            $table->string('unit_name');
            $table->integer('unit_ratio');
            $table->integer('qty');
            $table->integer('base_qty');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('direct_transfer_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('direct_transfer_items');
    }
};
