<?php

namespace TomAtom\AtomBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Translatable;
use Knp\DoctrineBehaviors\Contract\Entity\TranslationInterface;
use Knp\DoctrineBehaviors\Model\Translatable\TranslationTrait;

/**
 * Atom
 */
#[
    ORM\Table(name: 'atom_translation'),
    ORM\Entity
    ]
class AtomTranslation implements TranslationInterface
{
    use TranslationTrait;

    /**
     * @var integer
     *
     */
    #[
        ORM\Column(name: 'id', type: 'integer'),
        ORM\Id,
        ORM\GeneratedValue(strategy: 'AUTO')
    ]
    private int $id;

    /**
     * @var string
     *
     * @Gedmo\Translatable
     *
     */
    #[ORM\Column(name: 'body', type: 'text')]
    private string $body;

    /**
     * Get id
     *
     * @return integer
     */
    public function getId(): int
    {
        return $this->id;
    }

    /**
     * Set body
     *
     * @param string $body
     * @return AtomTranslation
     */
    public function setBody(string $body): AtomTranslation
    {
        $this->body = $body;

        return $this;
    }

    /**
     * Get body
     *
     * @return string
     */
    public function getBody(): ?string
    {
        return $this->body;
    }
}