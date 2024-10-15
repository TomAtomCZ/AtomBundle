<?php

namespace TomAtom\AtomBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Knp\DoctrineBehaviors\Contract\Entity\TranslatableInterface;
use Knp\DoctrineBehaviors\Model\Translatable\TranslatableTrait;

#[
    ORM\Entity,
    ORM\Table(name: 'atom')
]
class Atom implements TranslatableInterface
{
    use TranslatableTrait;

    #[
        ORM\Column(name: 'id', type: 'integer'),
        ORM\Id,
        ORM\GeneratedValue(strategy: 'AUTO')
    ]
    private ?int $id = null;

    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private string $name;

    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: true)]
    private string $title;

    public function __call($method, $arguments)
    {
        return $this->proxyCurrentLocaleTranslation($method, $arguments);
    }

//    public function __call($method, $arguments)
//    {
//        $method = ('get' === substr($method, 0, 3) || 'set' === substr($method, 0, 3)) ? $method : 'get'. ucfirst($method);
//
//        try {
//            return $this->proxyCurrentLocaleTranslation($method, $arguments);
//        } catch (\Exception $e) {return false;}    }
//
    public function __get($name)
    {
        $method = 'get' . ucfirst($name);
        $arguments = [];

        return $this->proxyCurrentLocaleTranslation($method, $arguments);
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setName($name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setTitle($title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }
}
