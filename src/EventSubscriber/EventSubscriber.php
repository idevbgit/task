<?php

    namespace App\EventSubscriber;

    use Symfony\Component\EventDispatcher\EventSubscriberInterface;
    use Symfony\Component\HttpKernel\Event\RequestEvent;
    use Symfony\Component\HttpKernel\KernelEvents;

    class EventSubscriber implements EventSubscriberInterface
    {
        public function onKernelRequest(RequestEvent $event): void
        {
            $request = $event->getRequest();
            if(!empty($request->getContent()) && $request->getContentType() === 'json') {
                $data = json_decode($request->getContent(), true);
                if(json_last_error() !== JSON_ERROR_NONE) {
                    $response = Response::create('Unable to parse json data.', 400);
                    $event->setResponse($response);
                } else {
                    $request->request->replace($data);
                }
            }
        }

        public static function getSubscribedEvents(): array
        {
            return [
                KernelEvents::REQUEST => 'onKernelRequest',
            ];
        }
    }

?>