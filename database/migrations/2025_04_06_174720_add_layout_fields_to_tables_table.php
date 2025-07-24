<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public $withinTransaction = false;
    public function up(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            // Drop the old 'number' column
            $table->dropColumn('number');

            // Add new 'name' column
            $table->string('name')->after('id');

            // Add layout fields
            $table->integer('x')->after('name');
            $table->integer('y')->after('x');
            $table->integer('w')->after('y');
            $table->integer('h')->after('w');

            // Add area relation
            $table->foreignId('area_id')->nullable()->constrained()->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('tables', function (Blueprint $table) {
            // Drop added columns
            $table->dropColumn(['name', 'x', 'y', 'w', 'h']);

            // Drop foreign key and column
            $table->dropForeign(['area_id']);
            $table->dropColumn('area_id');

            // Restore 'number' column
            $table->string('number')->after('id');
        });
    }
};
