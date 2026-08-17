<?php
use Monolog\Handler\NullHandler; use Monolog\Handler\StreamHandler; use Monolog\Processor\PsrLogMessageProcessor;
return ['default'=>env('LOG_CHANNEL','stderr'),'deprecations'=>['channel'=>'null','trace'=>false],'channels'=>[
'stderr'=>['driver'=>'monolog','level'=>env('LOG_LEVEL','debug'),'handler'=>StreamHandler::class,'handler_with'=>['stream'=>'php://stderr'],'processors'=>[PsrLogMessageProcessor::class]],
'single'=>['driver'=>'single','path'=>storage_path('logs/laravel.log'),'level'=>'debug'],'null'=>['driver'=>'monolog','handler'=>NullHandler::class],'emergency'=>['path'=>storage_path('logs/laravel.log')]
]];
