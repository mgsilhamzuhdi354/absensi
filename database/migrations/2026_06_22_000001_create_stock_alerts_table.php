<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateStockAlertsTable extends Migration
{
    public function up()
    {
        Schema::create('stock_alerts', function (Blueprint $table) {
            $table->id();
            $table->string('alertable_type');
            $table->unsignedBigInteger('alertable_id');
            $table->string('source', 30);
            $table->string('status', 20)->default('normal');
            $table->decimal('stock', 15, 2)->default(0);
            $table->decimal('threshold', 15, 2)->default(5);
            $table->timestamp('last_notified_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->unique(['alertable_type', 'alertable_id'], 'stock_alert_unique_item');
            $table->index(['source', 'status'], 'stock_alert_source_status_index');
        });
    }

    public function down()
    {
        Schema::dropIfExists('stock_alerts');
    }
}
