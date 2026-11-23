<?php

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
        Schema::create('requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('request_number');
            // Public tracking code given to the requester (random, not guessable).
            $table->string('tracking_code')->unique();
            // Requests come from the public welcome page, so there may be no account.
            $table->foreignId('user_id')->nullable()->constrained('users');
            $table->string('requester_name');
            $table->string('supervisor_name')->nullable();
            // Designation from the administrative-areas API; the label is snapshotted.
            $table->unsignedBigInteger('administrative_area_id')->nullable();
            $table->string('designation')->nullable();
            $table->string('status');
            $table->string('purpose');
            $table->dateTime('submitted_at');
            $table->timestamps();

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('requisitions');
    }
};
