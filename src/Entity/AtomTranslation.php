<?php

namespace TomAtom\AtomBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;
use Knp\DoctrineBehaviors\Contract\Entity\TranslationInterface;
use Knp\DoctrineBehaviors\Model\Translatable\TranslationTrait;

#[
    ORM\Table(name: 'atom_translation'),
    ORM\Entity
]
class AtomTranslation implements TranslationInterface
{
    use TranslationTrait;

    #[
        ORM\Column(name: 'id', type: 'integer'),
        ORM\Id,
        ORM\GeneratedValue(strategy: 'AUTO')
    ]
    private ?int $id = null;

    #[ORM\Column(name: 'body', type: 'text')]
    #[Gedmo\Translatable]
    private ?string $body = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setBody(string $body): AtomTranslation
    {
        $this->body = $body;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }
}
