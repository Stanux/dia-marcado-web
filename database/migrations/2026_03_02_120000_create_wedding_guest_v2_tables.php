<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wedding_events', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('wedding_id');
            $table->uuid('created_by')->nullable();
            $table->string('name');
            $table->date('event_date')->nullable();
            $table->time('event_time')->nullable();
            $table->string('event_type', 20)->default('open');
            $table->boolean('is_active')->default(true);
            $table->text('instructions')->nullable();
            $table->unsignedInteger('adult_quota')->nullable();
            $table->unsignedInteger('child_quota')->nullable();
            $table->timestamps();

            $table->foreign('wedding_id')->references('id')->on('weddings')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['wedding_id', 'event_type', 'is_active'], 'wedding_events_type_active_idx');
            $table->index(['wedding_id', 'event_date', 'event_time'], 'wedding_events_datetime_idx');
        });

        Schema::create('wedding_guests', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('wedding_id');
            $table->uuid('created_by')->nullable();
            $table->uuid('primary_contact_id')->nullable();
            $table->string('name');
            $table->string('nickname')->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('relationship', 120)->nullable();
            $table->boolean('is_child')->default(false);
            $table->string('side', 20)->default('both');
            $table->string('status', 20)->default('pending');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('wedding_id')->references('id')->on('weddings')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

            $table->index(['wedding_id', 'status'], 'wedding_guests_status_idx');
            $table->index(['wedding_id', 'primary_contact_id'], 'wedding_guests_primary_contact_idx');
        });

        Schema::table('wedding_guests', function (Blueprint $table): void {
            $table->foreign('primary_contact_id')
                ->references('id')
                ->on('wedding_guests')
                ->nullOnDelete();
        });

        Schema::create('wedding_invites', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('wedding_id');
            $table->uuid('event_id');
            $table->uuid('created_by')->nullable();
            $table->uuid('primary_contact_id')->nullable();
            $table->string('invite_type', 20)->default('individual');
            $table->string('token', 120)->unique();
            $table->string('confirmation_code', 6)->nullable();
            $table->unsignedInteger('adult_quota')->nullable();
            $table->unsignedInteger('child_quota')->nullable();
            $table->timestamp('expires_at');
            $table->string('sent_via', 20)->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->jsonb('metadata')->nullable();
            $table->timestamps();

            $table->foreign('wedding_id')->references('id')->on('weddings')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('wedding_events')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('primary_contact_id')->references('id')->on('wedding_guests')->nullOnDelete();

            $table->index(['wedding_id', 'event_id'], 'wedding_invites_event_idx');
            $table->index(['wedding_id', 'confirmation_code'], 'wedding_invites_code_idx');
            $table->index(['wedding_id', 'is_active', 'expires_at'], 'wedding_invites_active_expire_idx');
        });

        Schema::create('wedding_event_rsvps', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->uuid('wedding_id');
            $table->uuid('event_id');
            $table->uuid('guest_id');
            $table->uuid('invite_id')->nullable();
            $table->string('status', 20)->default('pending');
            $table->timestamp('responded_at')->nullable();
            $table->string('response_channel', 20)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('wedding_id')->references('id')->on('weddings')->onDelete('cascade');
            $table->foreign('event_id')->references('id')->on('wedding_events')->onDelete('cascade');
            $table->foreign('guest_id')->references('id')->on('wedding_guests')->onDelete('cascade');
            $table->foreign('invite_id')->references('id')->on('wedding_invites')->nullOnDelete();

            $table->unique(['event_id', 'guest_id'], 'wedding_event_rsvps_event_guest_unique');
            $table->index(['wedding_id', 'event_id', 'status'], 'wedding_event_rsvps_status_idx');
            $table->index(['wedding_id', 'guest_id'], 'wedding_event_rsvps_guest_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wedding_event_rsvps');
        Schema::dropIfExists('wedding_invites');
        Schema::dropIfExists('wedding_guests');
        Schema::dropIfExists('wedding_events');
    }
};
