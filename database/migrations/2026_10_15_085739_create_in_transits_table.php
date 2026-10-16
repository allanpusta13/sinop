<?php

use App\Models\TransferRequisition;
use App\Models\TransferRequisitionItem;
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
        Schema::create('in_transits', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(TransferRequisition::class)->constrained()->cascadeOnDelete();
            $table->foreignIdFor(TransferRequisitionItem::class)->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained('product_variants')->restrictOnDelete();

            $table->integer('dispatched_base_qty');
            $table->timestamp('dispatched_at');

            // String + PHP backed enum (App\Enums\InTransitStatus) instead of DB enum().
            $table->string('status')->default('in_transit');
            $table->timestamp('cleared_at')->nullable();
            $table->timestamps();

            $table->index(['transfer_requisition_id', 'status']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('in_transits');
    }
};
