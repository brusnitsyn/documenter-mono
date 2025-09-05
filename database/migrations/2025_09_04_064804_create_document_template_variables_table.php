<?php

use App\Models\DocumentTemplate;
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
        Schema::create('document_template_variables', function (Blueprint $table) {
            $table->id();
            $table->foreignIdFor(DocumentTemplate::class, 'document_template_id');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('document_template_variables');
    }
};
