<?php

namespace App\EventListener;

use Symfony\Component\HttpKernel\Event\ResponseEvent;

class DefaultEvent
{
    public function onKernelResponse(ResponseEvent $event)
    {
        //тут даем доступ для того чтобы можно было получать апи запросы , иначе блочит CORSE
        $response = $event->getResponse();
        $response->headers->set('Access-Control-Allow-Origin', '*');
        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS, PATCH, DELETE');
        $response->headers->set('Access-Control-Allow-Headers', 'Origin, X-Requested-With, Content-Type, Accept, Authorization');
    }
}