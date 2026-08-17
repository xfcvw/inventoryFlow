<?php
use Illuminate\Database\Migrations\Migration; use Illuminate\Database\Schema\Blueprint; use Illuminate\Support\Facades\Schema;
return new class extends Migration { public function up():void{Schema::create('orders',function(Blueprint $t){$t->id();$t->foreignId('user_id')->constrained()->cascadeOnDelete();$t->string('customer',120);$t->decimal('total',12,2);$t->enum('status',['pending','processing','completed','cancelled'])->default('pending');$t->timestamps();$t->index(['user_id','status']);});} public function down():void{Schema::dropIfExists('orders');} };
