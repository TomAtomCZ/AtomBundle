<?php

namespace TomAtom\AtomBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Gedmo\Translatable\Translatable;
use Knp\DoctrineBehaviors\Contract\Entity\TranslatableInterface;
use Knp\DoctrineBehaviors\Model\Translatable\TranslatableTrait;

/**
 * Atom
 *
 */
#[
    ORM\Entity,
    ORM\Table(name:'atom')
]
class Atom implements TranslatableInterface
{
    use TranslatableTrait;

    /**
     * @var integer
     *
     */
    #[
        ORM\Column(name: 'id', type: 'integer'),
        ORM\Id,
        ORM\GeneratedValue(strategy: 'AUTO')
        ]
    private $id;

    /**
     * @var string
     *
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    private $name;

    /**
     * @var string
     *
     *
     */
    #[ORM\Column(name: 'title', type: 'string', length: 255, nullable: true)]
    private $title;

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
        $method = 'get'. ucfirst($name);
        $arguments = [];

        return $this->proxyCurrentLocaleTranslation($method, $arguments);
    }


    /**
     * Get id
     *
     * @return integer
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Set name
     *
     * @param string $name
     * @return Atom
     */
    public function setName($name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * Get name
     *
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Set title
     *
     * @param string $title
     * @return Atom
     */
    public function setTitle($title)
    {
        $this->title = $title;

        return $this;
    }

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle()
    {
        return $this->title;
    }


}