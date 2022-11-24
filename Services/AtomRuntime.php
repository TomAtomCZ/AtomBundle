<?php

namespace TomAtom\AtomBundle\Services;

use Symfony\Component\HttpFoundation\RequestStack;

class AtomRuntime
{

    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack){
        $this->requestStack = $requestStack;
    }

    /**
     * @return RequestStack
     */
    public function getRequestStack(): RequestStack
    {
        return $this->requestStack;
    }

}