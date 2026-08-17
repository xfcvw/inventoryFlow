<?php
return ['default'=>env('MAIL_MAILER','log'),'mailers'=>['log'=>['transport'=>'log'],'array'=>['transport'=>'array']],'from'=>['address'=>'hello@inventoryflow.local','name'=>'InventoryFlow']];
