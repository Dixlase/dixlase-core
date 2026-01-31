<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('webauthn_credentials', function (Blueprint $table) {
            $table->uuid('user_id')->nullable()->after('member_id');
        });
        
        // 既存レコードのuser_idを生成
        $credentials = DB::table('webauthn_credentials')->get();
        foreach ($credentials as $credential) {
            $member = DB::table('members')->where('id', $credential->member_id)->first();
            if ($member) {
                // member_idから一貫したUUID v5を生成
                $uuid = \Ramsey\Uuid\Uuid::uuid5(
                    \Ramsey\Uuid\Uuid::NAMESPACE_DNS,
                    'dixlase.member.' . $member->id
                );
                DB::table('webauthn_credentials')
                    ->where('id', $credential->id)
                    ->update(['user_id' => $uuid->toString()]);
            }
        }
        
        // user_idをNOT NULLに変更
        Schema::table('webauthn_credentials', function (Blueprint $table) {
            $table->uuid('user_id')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('webauthn_credentials', function (Blueprint $table) {
            $table->dropColumn('user_id');
        });
    }
};
