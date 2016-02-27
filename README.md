# __TomAtom/AtomBundle__

### __Symfony3__ Bundle for easy __front-end content editing.__


#### Dependencies:

* symfony/framework-standard-edition 
 
* egeloen/ckeditor-bundle "~4.0"

* jQuery


### Instalation:

* create project with Symfony framework

* composer require egeloen/ckeditor-bundle "~4.0"

* composer require tomatom/atom-bundle "dev-master"

* `AppKernel.php`
```php
    new Ivory\CKEditorBundle\IvoryCKEditorBundle(),
    new TomAtom\AtomBundle\TomAtomAtomBundle(),
```

* `routing.yml`
```yml
    atom:
        resource: "@TomAtomAtomBundle/Controller/"
        type:     annotation
```

* `config.yml`
```yml
    # Twig Configuration
    twig:
        base_template_class: TomAtom\AtomBundle\Twig\Template
        # ...
    # CK Editor
    ivory_ck_editor:
        autoload: false
        auto_inline: false
        inline: true
```

* `::base.html.twig`
```twig
    {% if is_granted('ROLE_SUPER_ADMIN') %}
        <script src="{{ asset('bundles/ivoryckeditor/ckeditor.js') }}"></script>
        <script src="{{ asset('bundles/tomatomatom/js/atom_ckedit.js') }}"></script>
        {{ render(controller('TomAtomAtomBundle:Atom:_metas')) }}
    {% endif %}
```

* if you want to use Atom in your templates, add Atom tag with _unique_ identifier: `{% atom unique_identifier_here %}`
    and closing tag `{% endatom %}`. You can add default content between tags, which will be persisted on first load.
```twig
    {% atom foo %}
        <p> I am editable! </p>
    {% endatom %}
```