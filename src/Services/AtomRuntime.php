<?php

namespace TomAtom\AtomBundle\Services;

use Symfony\Component\HttpFoundation\RequestStack;

class AtomRuntime
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack, private readonly NodeHelper $nodeHelper)
    {
        $this->requestStack = $requestStack;
    }

    public function getRequestStack(): RequestStack
    {
        return $this->requestStack;
    }

    public function getAtomBody(string $name, string $defaultBody): string
    {
        return $this->nodeHelper->getAtomBody($name, $defaultBody);
    }
}