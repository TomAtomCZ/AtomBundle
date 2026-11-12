<?php

namespace TomAtom\AtomBundle\Services;

/**
 * Signs the entity, method and id rendered by an {% atomentity %} tag, so that only those combinations
 * can be saved and the request cannot be changed to call another method or another entity.
 */
class AtomEntitySigner
{
    public function __construct(#[\SensitiveParameter] private readonly string $secret)
    {
    }

    public function sign(string $entity, string $method, string|int $id): string
    {
        return hash_hmac('sha256', implode("\0", ['atomentity', $entity, $method, (string)$id]), $this->secret);
    }

    public function isValid(string $entity, string $method, string|int $id, mixed $signature): bool
    {
        if ($this->secret === '' || !is_string($signature) || $signature === '') {
            return false;
        }

        return hash_equals($this->sign($entity, $method, $id), $signature);
    }
}
