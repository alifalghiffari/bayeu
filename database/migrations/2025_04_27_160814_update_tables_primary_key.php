<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {

    public $withinTransaction = false;
    public function up(): void
    {
        // Step 1: Drop the 'table_id' column from the 'orders' table
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('table_id');  // Drop the old 'table_id' column
        });

        // Step 2: Drop the 'tables' table
        Schema::dropIfExists('tables');

        // Step 3: Create a new 'tables' table with id, x, y, w, h, area_id
        Schema::create('tables', function (Blueprint $table) {
            $table->unsignedBigInteger('id');
            $table->integer('x');
            $table->integer('y');
            $table->integer('w');
            $table->integer('h');
            $table->foreignId('area_id')->constrained()->onDelete('cascade');  // Foreign key to area
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            // Composite primary key (id + area_id)
            $table->primary(['id', 'area_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('table_id')->nullable();  // Add 'table_id' column without a foreign key constraint
            $table->unsignedBigInteger('area_id')->nullable();  // Add 'area_id' column without a foreign key constraint
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tables');

        Schema::create('tables', function (Blueprint $table) {
            $table->id();
            $table->string('name');  // Assuming the old 'tables' table had a 'name' column
            $table->integer('x');
            $table->integer('y');
            $table->integer('w');
            $table->integer('h');
            $table->foreignId('area_id')->nullable()->constrained()->onDelete('cascade');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('table_id');
            $table->dropColumn('area_id');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('table_id')->references('id')->on('tables')->onDelete('cascade');
        });
    }
};
