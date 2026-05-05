<?php

declare(strict_types=1);

namespace App\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class CacheHeadersSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => ['onResponse', -10]];
    }

    public function onResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        if ($request->getMethod() !== 'GET') {
            return;
        }

        $response = $event->getResponse();
        if ($response->getStatusCode() !== 200) {
            return;
        }

        $contentType = (string) $response->headers->get('Content-Type', '');
        if (!str_starts_with($contentType, 'text/html')) {
            return;
        }

        $response->setPublic();
        $response->setMaxAge(300);
        $response->setSharedMaxAge(86400);
        $response->headers->set('Vary', 'Accept-Encoding');
    }
}
