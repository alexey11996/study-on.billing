<?php

namespace App\Controller;

use Symfony\Bundle\TwigBundle\Controller\ExceptionController;
use Symfony\Component\Debug\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Log\DebugLoggerInterface;
use Twig\Environment;

class CustomExceptionController extends ExceptionController
{
    protected $debug;

    /**
     * @param bool  $debug Show error (false) or exception (true) pages by default
     */
    public function __construct(bool $debug)
    {
        $this->debug = $debug;
    }

    public function showAction(Request $request, FlattenException $exception, DebugLoggerInterface $logger = null)
    {
        return new Response(json_encode(['code' => $exception->getStatusCode(), 'message' => $exception->getMessage()]));
    }
}
