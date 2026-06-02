<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReceiverSignatureToInventoryBastDocumentsTable extends Migration
{
    public function up()
    {
        Schema::table('inventory_bast_documents', function (Blueprint $table) {
            $table->foreignId('signed_by_user_id')
                ->nullable()
                ->after('file_pdf')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('receiver_signature_name')->nullable()->after('signed_by_user_id');
            $table->timestamp('signed_at')->nullable()->after('receiver_signature_name');
            $table->string('signature_ip', 45)->nullable()->after('signed_at');
            $table->text('signature_user_agent')->nullable()->after('signature_ip');
        });
    }

    public function down()
    {
        Schema::table('inventory_bast_documents', function (Blueprint $table) {
            $table->dropForeign(['signed_by_user_id']);
            $table->dropColumn([
                'signed_by_user_id',
                'receiver_signature_name',
                'signed_at',
                'signature_ip',
                'signature_user_agent',
            ]);
        });
    }
}
