<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMultiSignatureToInventoryBastDocumentsTable extends Migration
{
    public function up()
    {
        Schema::table('inventory_bast_documents', function (Blueprint $table) {
            $table->string('receiver_signature_image')->nullable()->after('receiver_signature_name');

            $table->foreignId('known_by_user_id')
                ->nullable()
                ->after('signature_user_agent')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('known_signature_name')->nullable()->after('known_by_user_id');
            $table->string('known_signature_image')->nullable()->after('known_signature_name');
            $table->timestamp('known_signed_at')->nullable()->after('known_signature_image');
            $table->string('known_signature_ip', 45)->nullable()->after('known_signed_at');
            $table->text('known_signature_user_agent')->nullable()->after('known_signature_ip');

            $table->foreignId('first_party_user_id')
                ->nullable()
                ->after('known_signature_user_agent')
                ->constrained('users')
                ->nullOnDelete();
            $table->string('first_party_signature_name')->nullable()->after('first_party_user_id');
            $table->string('first_party_signature_image')->nullable()->after('first_party_signature_name');
            $table->timestamp('first_party_signed_at')->nullable()->after('first_party_signature_image');
            $table->string('first_party_signature_ip', 45)->nullable()->after('first_party_signed_at');
            $table->text('first_party_signature_user_agent')->nullable()->after('first_party_signature_ip');
        });
    }

    public function down()
    {
        Schema::table('inventory_bast_documents', function (Blueprint $table) {
            $table->dropForeign(['known_by_user_id']);
            $table->dropForeign(['first_party_user_id']);
            $table->dropColumn([
                'receiver_signature_image',
                'known_by_user_id',
                'known_signature_name',
                'known_signature_image',
                'known_signed_at',
                'known_signature_ip',
                'known_signature_user_agent',
                'first_party_user_id',
                'first_party_signature_name',
                'first_party_signature_image',
                'first_party_signed_at',
                'first_party_signature_ip',
                'first_party_signature_user_agent',
            ]);
        });
    }
}
