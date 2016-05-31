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

* for drag&drop image uploading from editor, __create upload directory__: `/web/uploads/atom`