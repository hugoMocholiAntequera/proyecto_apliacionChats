<?php

namespace App\EventListener;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

class ApiExceptionListener
{
    private string $environment;

    public function __construct(ParameterBagInterface $params)
    {
        $this->environment = $params->get('kernel.environment');
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $request = $event->getRequest();
        
        // Solo aplicar para rutas que empiecen con /api/
        if (!str_starts_with($request->getPathInfo(), '/api/')) {
            return;
        }

        $exception = $event->getThrowable();
        
        $statusCode = 500;
        if ($exception instanceof HttpExceptionInterface) {
            $statusCode = $exception->getStatusCode();
        }

        $response = new JsonResponse([
            'success' => false,
            'message' => 'Error interno del servidor',
            'error' => (object)[],
            'debug' => [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => array_slice($exception->getTrace(), 0, 3) // Solo primeras 3 líneas
            ]
        ], $statusCode);

        $event->setResponse($response);
    }
}
