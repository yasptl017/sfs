<?php
use Illuminate\Database\Migrations\Migration;use Illuminate\Database\Schema\Blueprint;use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void{Schema::create('lc_entries',function(Blueprint $t){$t->id();$t->foreignId('division_id')->constrained('users')->cascadeOnDelete();$t->unsignedInteger('serial_number');$t->string('scheme');$t->string('class');$t->date('entry_date');$t->decimal('amount',15,2);$t->timestamps();$t->unique(['division_id','serial_number']);});}public function down():void{Schema::dropIfExists('lc_entries');} };
