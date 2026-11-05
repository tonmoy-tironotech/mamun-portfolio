<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * A publication now has at most one PDF and one link, so they live on the
     * publication itself. The first PDF of each publication is carried over.
     */
    public function up(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            $table->string('pdf_path')->nullable();
            $table->string('pdf_name')->nullable();
            $table->unsignedBigInteger('pdf_size')->nullable();
            $table->string('link_url', 2000)->nullable();
        });

        if (Schema::hasTable('publication_files')) {
            DB::table('publication_files')->orderBy('id')->get()->unique('publication_id')->each(
                fn (object $file) => DB::table('publications')->where('id', $file->publication_id)->update([
                    'pdf_path' => $file->file_path,
                    'pdf_name' => $file->file_name,
                    'pdf_size' => $file->file_size,
                ])
            );

            Schema::drop('publication_files');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('publications', function (Blueprint $table) {
            $table->dropColumn(['pdf_path', 'pdf_name', 'pdf_size', 'link_url']);
        });

        Schema::create('publication_files', function (Blueprint $table) {
            $table->id();
            $table->foreignId('publication_id')->constrained()->cascadeOnDelete();
            $table->string('file_path');
            $table->string('file_name');
            $table->unsignedBigInteger('file_size');
            $table->string('label', 50)->nullable();
            $table->timestamps();
        });
    }
};
