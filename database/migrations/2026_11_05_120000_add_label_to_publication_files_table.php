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
        Schema::table('publication_files', function (Blueprint $table) {
            // Optional link text; empty means the site shows pdf1, pdf2, ...
            $table->string('label', 50)->nullable()->after('file_size');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publication_files', function (Blueprint $table) {
            $table->dropColumn('label');
        });
    }
};
