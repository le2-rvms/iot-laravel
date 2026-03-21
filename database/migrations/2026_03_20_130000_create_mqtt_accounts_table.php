<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mqtt_accounts', function (Blueprint $table) {
            // 保留旧表字段名，避免后续对接 EMQX 或存量数据迁移时再做二次映射。
            $table->bigIncrements('act_id');
            $table->string('clientid', 50)->nullable();
            $table->string('user_name', 64)->unique();
            $table->string('password_hash', 64);
            $table->text('certificate')->nullable();
            $table->string('salt', 64);
            $table->boolean('is_superuser')->default(false);
            $table->string('product_key', 64)->nullable();
            $table->string('device_name', 255)->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamp('act_created_at')->nullable();
            $table->timestamp('act_updated_at')->nullable();
            $table->string('act_updated_by')->nullable();

            // 先补业务字段上的基础索引，后续若 PostgreSQL 搜索量上来，再按 lower()/trgm 追加专项索引。
            $table->index('clientid');
            $table->index('product_key');
            $table->index('device_name');
            $table->index('enabled');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mqtt_accounts');
    }
};
