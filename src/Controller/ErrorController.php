<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Twig\Environment;

class ErrorController extends AbstractController
{
    private $twig;
    private $debug;

    public function __construct(Environment $twig, bool $debug = false)
    {
        $this->twig = $twig;
        $this->debug = $debug;
    }

    /**
     * Výchozí metoda pro volání controlleru bez specifikace metody
     */
    public function __invoke(Request $request): Response
    {
        // Získáme poslední část URL, která by měla být kód chyby (např. 404, 500)
        $path = $request->getPathInfo();
        $errorCode = intval(basename($path)) ?: 404; // Výchozí hodnota 404, pokud není kód v URL

        // Vytvoříme FlattenException s daným kódem
        $exception = FlattenException::create(
            new \Exception('Page not found'),
            $errorCode
        );

        // Použijeme metodu show pro zobrazení chybové stránky
        return $this->show($request, $exception);
    }

    public function show(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null): Response
    {
        $statusCode = $exception->getStatusCode();
        $statusText = Response::$statusTexts[$statusCode] ?? 'Unknown Error';

        // Pokus o vykreslení specifické chybové šablony
        try {
            $template = "bundles/TwigBundle/Exception/error{$statusCode}.html.twig";
            if (!$this->twig->getLoader()->exists($template)) {
                $template = 'bundles/TwigBundle/Exception/error.html.twig';
                if (!$this->twig->getLoader()->exists($template)) {
                    // Pokud ani obecná šablona neexistuje, vytvoříme jednoduchý HTML výstup
                    return new Response(
                        "<html><body><h1>Chyba {$statusCode}</h1><p>{$statusText}</p></body></html>",
                        $statusCode,
                        ['Content-Type' => 'text/html']
                    );
                }
            }

            return new Response($this->twig->render($template, [
                'status_code' => $statusCode,
                'status_text' => $statusText,
                'exception' => $exception,
                'logger' => $logger,
                'title' => 'Chyba ' . $statusCode,
            ]), $statusCode);
        } catch (\Exception $e) {
            // Pokud došlo k chybě při renderování, vytvoříme jednoduchý HTML výstup
            return new Response(
                "<html><body><h1>Chyba {$statusCode}</h1><p>{$statusText}</p></body></html>",
                $statusCode,
                ['Content-Type' => 'text/html']
            );
        }
    }

    public function preview(Request $request, int $code): Response
    {
        $exception = FlattenException::create(new \Exception('This is a preview of an error page.'), $code);
        return $this->show($request, $exception);
    }
}
