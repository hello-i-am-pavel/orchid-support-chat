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
        Schema::create('support_tickets', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->unsignedBigInteger('created_by');
            $table->string('number');
            $table->string('status')->index();
            $table->unsignedBigInteger('status_changed_by')->nullable();
            $table->timestamps();
        });

        Schema::create('support_ticket_messages', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('ticket_id')->index();
            $table->unsignedBigInteger('sent_by');
            $table->text('message');
            $table->timestamps();
        });

        Schema::create('support_ticket_status_logs', static function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->uuid('ticket_id')->index();
            $table->string('old_status')->nullable();
            $table->string('new_status');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('support_ticket_status_logs');
        Schema::dropIfExists('support_ticket_messages');
        Schema::dropIfExists('support_tickets');
    }
};
