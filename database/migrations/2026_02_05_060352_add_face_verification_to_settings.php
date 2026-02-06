<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            // Face recognition fallback when attendance validation fails
            $table->boolean('enable_face_verification_fallback')->default(false)->after('enable_daily_qr');
            $table->decimal('face_match_threshold', 5, 2)->default(70.00)->after('enable_face_verification_fallback'); // 0-100%
            $table->text('face_verification_message')->nullable()->after('face_match_threshold');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn(['enable_face_verification_fallback', 'face_match_threshold', 'face_verification_message']);
        });
    }
};
