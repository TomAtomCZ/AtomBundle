### Install and configure Gedmo:Translatable

##### ([stof/doctrine-extensions-bundle](https://github.com/stof/StofDoctrineExtensionsBundle))

* `composer require stof/doctrine-extensions-bundle` (Version based on the version of Symfony)

**Symfony 5+**

* `bundles.php:`

> ```php
> Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle::class => ['all' => true],
>```

* `framework.yaml:`

> ```yml
> # double check that translator is uncommented:
> framework:
> # ...
>     translator: { fallbacks: [ "%locale%" ] }
>```

* `doctrine.yaml:`

> ```yml
> doctrine:
>    orm:
>        default:
>            mappings:
>               translatable:
>                   type: attribute # or annotation
>                   alias: GedmoTranslatable
>                   prefix: Gedmo\Translatable\Entity
>                   dir: "%kernel.project_dir%/vendor/gedmo/doctrine-extensions/src/Translatable/Entity"
>                   is_bundle: false
>``` 

* `stof_doctrine_extensions.yaml:`

> ```yml
> stof_doctrine_extensions:
>   default_locale: en
>   orm:
>     default:
>       timestampable: true
>       sluggable: true
>       sortable: true
>```


**Symfony < 5**

* `AppKernel.php:`

> ```php
> new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
>```

* `config.yml:`

> ```yml
> # double check that translator is uncommented:
> framework:
>     translator:      { fallbacks: ["%locale%"] }
> # ...
> 
> # Doctrine Configuration
> doctrine:
>     # ...
>     orm:
>         auto_generate_proxy_classes: "%kernel.debug%"
>         entity_managers:
>             default:
>                 naming_strategy: doctrine.orm.naming_strategy.underscore
>                 auto_mapping: true
>                 mappings:
>                     gedmo_translatable:
>                         type: annotation
>                         prefix: Gedmo\Translatable\Entity
>                         dir: "%kernel.root_dir%/../vendor/gedmo/doctrine-extensions/lib/Gedmo/Translatable/Entity"
>                         alias: GedmoTranslatable
>                         is_bundle: false
> 
> # you may want to use translator for handling translations explicitly in your entities (not mandatory for Atoms):
>                     gedmo_translator:
>                         type: annotation
>                         prefix: Gedmo\Translator\Entity
>                         dir: "%kernel.root_dir%/../vendor/gedmo/doctrine-extensions/lib/Gedmo/Translator/Entity"
>                         alias: GedmoTranslator
>                         is_bundle: false
>     # ...
> 
> # Gedmo Configuration
> stof_doctrine_extensions:
>     default_locale: en
>     translation_fallback: true
>     persist_default_translation: true
>     orm:
>         default:
>             translatable: true
>     # ...
>```

**Then for all versions**

* update schema, clear cache

* for translatable entities editable by __Atom Entity__ tag:

*With Annotations:*
> ```php
> use Gedmo\Mapping\Annotation as Gedmo;
> use Gedmo\Translatable\Translatable;
> // ...
> 
> class Product implements Translatable
> // ...
>     /**
>      * @Gedmo\Translatable
>      *
>      * @ORM\Column(type="string", nullable=TRUE)
>      */
>     protected $title;
>  
>     /**
>      * @Gedmo\Locale
>      * 
>      * helper field for ephemeral translation lang setting
>      */
>     protected $locale;
>  
>     // ... other props, getters, setters, etc.
>  
>     /**
>      * @param string $locale
>      */
>     public function setTranslatableLocale($locale)
>     {
>         $this->locale = $locale;
>     }
> }
>```

*With Attributes:*
> ```php
> use Doctrine\ORM\Mapping as ORM;
> use Gedmo\Mapping\Annotation as Gedmo;
> use Gedmo\Translatable\Translatable;
> // ..
> 
> #[ORM\Table(name: 'articles')]
> #[ORM\Entity]
> class Article implements Translatable
> {
>     #[ORM\Id]
>     #[ORM\GeneratedValue]
>     #[ORM\Column(type: 'integer')]
>     private $id;
> 
>     #[Gedmo\Translatable]
>     #[ORM\Column(name: 'title', type: 'string', length: 128)]
>     private $title;
> 
>     #[Gedmo\Translatable]
>     #[ORM\Column(name: 'content', type: 'text')]
>     private $content;
> 
>     /**
>      * Used locale to override Translation listener`s locale
>      * this is not a mapped field of entity metadata, just a simple property
>      */
>     #[Gedmo\Locale]
>     private $locale;
> 
>     public function getId()
>     {
>         return $this->id;
>     }
> 
>     public function setTitle($title)
>     {
>         $this->title = $title;
>     }
> 
>     public function getTitle()
>     {
>         return $this->title;
>     }
> 
>     public function setContent($content)
>     {
>         $this->content = $content;
>     }
> 
>     public function getContent()
>     {
>         return $this->content;
>     }
>
>     // ... other props, getters, setters, etc.
> 
>     public function setTranslatableLocale($locale)
>     {
>         $this->locale = $locale;
>     }
> }
>```


