<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserImpersonationAudits extends Migration
{
    public function up()
    {
        Schema::create('user_impersonation_audits', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('audit_id')->unique();
            $table->unsignedBigInteger('actor_user_id')->index();
            $table->unsignedBigInteger('target_user_id')->index();
            $table->timestamp('started_at');
            $table->timestamp('expires_at')->index();
            $table->timestamp('ended_at')->nullable();
            $table->string('end_reason', 64)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->char('user_agent_hash', 64)->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_impersonation_audits');
    }
}
