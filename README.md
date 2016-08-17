# __TomAtom/AtomBundle__

### __Symfony__ Bundle for easy __front-end content editing.__


#### Dependencies:

* symfony/framework-standard-edition ">=2.8|~3.0"

* stof/doctrine-extensions-bundle "~1.2"
 
* jQuery


### Installation:

* create project with Symfony framework

* install [stof/doctrine-extensions-bundle "~1.2" - _Translatable_ behavior](https://github.com/stof/StofDoctrineExtensionsBundle)
  ([quick installation & config instructions](Resources/doc/gedmo-config.md))

* composer require tomatom/atom-bundle "dev-master"

* `AppKernel.php:`
>```php
>new TomAtom\AtomBundle\TomAtomAtomBundle(),
>```


### Configuration:

* `routing.yml:`
>```yml
>atom:
>    resource: "@TomAtomAtomBundle/Controller/"
>    type:     annotation
>```

* `config.yml:`
>```yml
># Make sure translator is uncommented:
>framework:
>    translator:      { fallbacks: ["%locale%"] }
># ...
>
># Twig Configuration
>twig:
>    base_template_class: TomAtom\AtomBundle\Twig\Template
>    # ...
>```

* `security.yml:`
>```yml
>security:
>    # ...
>    # add role 'ROLE_ATOM_EDIT':
>    role_hierarchy:
>        ROLE_ATOM_EDIT:   ROLE_USER
>        ROLE_ADMIN:       ROLE_ATOM_EDIT
>        ROLE_SUPER_ADMIN: ROLE_ADMIN
>    # ...
>```

* `::base.html.twig` (or your base layout):
>```twig
>{# don't forget to include your jQuery (tested with 1.8.3 - 2.1.4, others may work, 3.0 doesn't): #}
><script src={{ asset('path/to/jQuery.js') }}></script>
>
>{{ render(controller('TomAtomAtomBundle:Atom:_metas')) }}
>```

* for drag&drop image uploading from editor, __create upload directory__: `/web/uploads/atom`


### Usage:

* there are currently 3 __Atom__ types:
>* `atom` - __Atom__ with rich text editor ([CKEditor](http://ckeditor.com/))
>* `atomline` - __Atom Line__ for editing plaintext inside fixed html tags
>* `atomentity` - __Atom Entity__ display and update column for given entity


* if you want to use __Atom__ in your templates, add Atom tag with _unique_ identifier: `{% atom unique_identifier_here %}`
    and closing tag `{% endatom %}`. You can add default content between tags, which will be persisted on first load.
>```twig
>{% atom foo %}
>    <p> I am editable! </p>
>{% endatom %}
>```

* in case you want to edit only text content (like headings or table cells) and don't want to use rich text editor,
 there is the __Atom Line__ tag (again with _unique_ identifier): `{% atomline unique_identifier_here %}` and closing `{% endatomline %}`.
>```twig
><h1>
>   {% atomline bar %}
>       I am editable!
>   {% endatomline %}
><h1>
>```

* for editing other entities, there is __Atom Entity__ tag, which takes these arguments:

    * name of Bundle containing desired entity:Entity name (e.g. `AppBundle:Product`)
    * name of method used for saving content (usually some setter)
    * entity id
    
* example (no need to add default value, it will be fetched by appropriate getter):
>```twig
><div class="product-price">
>   {% atomentity  AppBundle:Product, setPrice, 123 %}{% endatomentity %}
><div>
>```


#### Translations:

* when switching between locales by changing `_locale` request parameter, you can easily update atoms in specified language.
  Also Atom Entities can be translated from frontend, if they have implemented Gedmo Translatable behavior.
