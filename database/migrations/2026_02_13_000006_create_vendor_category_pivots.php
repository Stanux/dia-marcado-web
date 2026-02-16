<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('vendor_category_vendor', function (Blueprint $table) {
            $table->uuid('vendor_id');
            $table->uuid('vendor_category_id');

            $table->foreign('vendor_id')->references('id')->on('vendors')->onDelete('cascade');
            $table->foreign('vendor_category_id')->references('id')->on('vendor_categories')->onDelete('cascade');

            $table->primary(['vendor_id', 'vendor_category_id']);
        });

        Schema::create('vendor_category_wedding_vendor', function (Blueprint $table) {
            $table->uuid('wedding_vendor_id');
            $table->uuid('vendor_category_id');

            $table->foreign('wedding_vendor_id')->references('id')->on('wedding_vendors')->onDelete('cascade');
            $table->foreign('vendor_category_id')->references('id')->on('vendor_categories')->onDelete('cascade');

            $table->primary(['wedding_vendor_id', 'vendor_category_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vendor_category_wedding_vendor');
        Schema::dropIfExists('vendor_category_vendor');
    }
};
