### Install and configure Gedmo:Translatable
##### ([stof/doctrine-extensions-bundle](https://github.com/stof/StofDoctrineExtensionsBundle))

* `composer require stof/doctrine-extensions-bundle "~1.2"`

* `AppKernel.php:`
>```php
>new Stof\DoctrineExtensionsBundle\StofDoctrineExtensionsBundle(),
>```

* `config.yml:`
>```yml
># double check that translator is uncommented:
>framework:
>    translator:      { fallbacks: ["%locale%"] }
># ...
>
># Doctrine Configuration
>doctrine:
>    # ...
>    orm:
>        auto_generate_proxy_classes: "%kernel.debug%"
>        entity_managers:
>            default:
>                naming_strategy: doctrine.orm.naming_strategy.underscore
>                auto_mapping: true
>                mappings:
>                    gedmo_translatable:
>                        type: annotation
>                        prefix: Gedmo\Translatable\Entity
>                        dir: "%kernel.root_dir%/../vendor/gedmo/doctrine-extensions/lib/Gedmo/Translatable/Entity"
>                        alias: GedmoTranslatable
>                        is_bundle: false
>
># you may want to use translator for handling translations explicitly in your entities (not mandatory for Atoms):
>                    gedmo_translator:
>                        type: annotation
>                        prefix: Gedmo\Translator\Entity
>                        dir: "%kernel.root_dir%/../vendor/gedmo/doctrine-extensions/lib/Gedmo/Translator/Entity"
>                        alias: GedmoTranslator
>                        is_bundle: false
>    # ...
>
># Gedmo Configuration
>stof_doctrine_extensions:
>    default_locale: en
>    translation_fallback: true
>    persist_default_translation: true
>    orm:
>        default:
>            translatable: true
>    # ...
>```

* update schema, clear cache

* for translatable entities editable by __Atom Entity__ tag:
>```php
>use Gedmo\Mapping\Annotation as Gedmo;
>use Gedmo\Translatable\Translatable;
>// ...
>
>class Product implements Translatable
>// ...
>    /**
>     * @Gedmo\Translatable
>     *
>     * @ORM\Column(type="string", nullable=TRUE)
>     */
>    protected $title;
> 
>    /**
>     * @Gedmo\Locale
>     * 
>     * helper field for ephemeral translation lang setting
>     */
>    protected $locale;
> 
>// ... other props, getters, setters, etc.
> 
>    /**
>     * @param string $locale
>     */
>    public function setTranslatableLocale($locale)
>    {
>        $this->locale = $locale;
>    }
>}
>```