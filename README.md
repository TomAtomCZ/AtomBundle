# __TomAtom/AtomBundle__

### __Symfony__ Bundle for easy __front-end content editing.__


#### Dependencies:

* symfony/framework-standard-edition ">=2.8|~3.0"
 
* jQuery


### Instalation:

* create project with Symfony framework

* composer require tomatom/atom-bundle "dev-master"

* `AppKernel.php:`
>```php
>new TomAtom\AtomBundle\TomAtomAtomBundle(),
>```

* `routing.yml:`
>```yml
>atom:
>    resource: "@TomAtomAtomBundle/Controller/"
>    type:     annotation
>```

* `config.yml:`
>```yml
># Twig Configuration
>twig:
>    base_template_class: TomAtom\AtomBundle\Twig\Template
>    # ...
>```

* `::base.html.twig:`
>```twig
>{% if is_granted('ROLE_SUPER_ADMIN') %}
>    {{ render(controller('TomAtomAtomBundle:Atom:_metas')) }}
>{% endif %}
>```

* if you want to use Atom in your templates, add Atom tag with _unique_ identifier: `{% atom unique_identifier_here %}`
    and closing tag `{% endatom %}`. You can add default content between tags, which will be persisted on first load.
>```twig
>{% atom foo %}
>    <p> I am editable! </p>
>{% endatom %}
>```

* in case you want to edit only text content (like headings or table cells) and don't want to use rich text editor,
 there is the Atom Line tag (again with _unique_ identifier): `{% atomline unique_identifier_here %}` and closing `{% endatomline %}`.
>```twig
><h1>
>   {% atomline bar %}
>       I am editable!
>   {% endatomline %}
><h1>
>```

* for editing other entities, there is Atom Entity tag, which takes these arguments:

    * name of Bundle containing desired entity:Entity name (e.g. `AppBundle:Product`)

    * name of method used for saving content (usually some setter)

    * entity id
    
* example:
>```twig
><div class="product-price">
>   {% atomentity  AppBundle:Product, setPrice, 123 %}
>       12345
>   {% endatomentity %}
><div>
>```

* for drag&drop image uploading from editor, __create upload directory__: `/web/uploads/atom`