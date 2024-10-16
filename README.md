# __TomAtom/AtomBundle__

### __Symfony__ Bundle for easy __front-end content editing.__

#### Dependencies:

**For Symfony 2/3/4**

* symfony/framework-standard-edition ">=2.8|~3.0|~4.0"
* stof/doctrine-extensions-bundle "~1.2"

**For Symfony 5**

* "symfony/framework-bundle": "~5.1"
* "antishov/doctrine-extensions-bundle": "^1.4"

**For Symfony 6+**

* "symfony/framework-bundle": "^6"
* "stof/doctrine-extensions-bundle": "^1.9"

**For all**

* jQuery

### Installation:

* install [stof/doctrine-extensions-bundle -
  _Translatable_ behavior](https://github.com/stof/StofDoctrineExtensionsBundle)
  ([quick installation & config instructions](/doc/gedmo-config.md))

* install bundle

> * for Symfony __~2.8__: `composer require tomatom/atom-bundle "~1.0"`
> * for Symfony __~4.2__: `composer require tomatom/atom-bundle "~2.0"`
> * for Symfony __~5__: `composer require tomatom/atom-bundle "3.0-alpha-5"`
> * for Symfony __~6|~7__: `composer require tomatom/atom-bundle "^3"`

### Configuration:

**Symfony 6+**

* `bundles.php:`

```php
TomAtom\AtomBundle\TomAtomAtomBundle::class => ['all' => true],
```

* `routing.yaml:`

```yml
atom:
  resource: "@TomAtomAtomBundle/src/Controller/"
  type: attribute
```

* `framework.yaml:`

```yml
framework:
  # ...
  translator: { fallbacks: [ "%locale%" ] }
```

* `twig.yaml:`

```yaml
twig:
  # ...
  base_template_class: TomAtom\AtomBundle\Twig\Template
```

**Symfony < 6**

* `AppKernel.php:`

```php
new TomAtomAtomBundle\TomAtomAtomBundle(),
```

* `routing.yml:`

```yml
atom:
  resource: "@TomAtomAtomBundle/Controller/"
  type: annotation
```

* `config.yml:`

```yml
# Make sure translator is uncommented:
framework:
  translator: { fallbacks: [ "%locale%" ] }
# ...

# Twig Configuration
twig:
  base_template_class: TomAtom\AtomBundle\Twig\Template
  # ...
```

**Same for all versions**

* `security.yml:`

```yml
security:
  # ...
  # add role 'ROLE_ATOM_EDIT':
  role_hierarchy:
    ROLE_ATOM_EDIT: ROLE_USER
    ROLE_ADMIN: ROLE_ATOM_EDIT
    ROLE_SUPER_ADMIN: ROLE_ADMIN
  # ...
```

* `translation.yml:`

```yml
framework:
  # ...
  # Add enabled locales for multi language application
  enabled_locales: [ 'cs', 'en', 'de' ]
```

* `::base.html.twig` (or your base layout):

```twig
{# don't forget to include your jQuery (tested with 1.8.3 - 2.1.4, others may work, 3.0 doesn't): #}
<script src={{ asset('path/to/jQuery.js') }}></script>

{{ render(controller('TomAtom\\AtomBundle\\Controller\\AtomController::_metasAction')) }}
```

* for drag&drop image uploading from editor, __create upload directory__: `/web/uploads/atom`

### Usage:

* __Atoms intentionally works only in `prod` environment!__
  They are disabled in `test`, `dev` and all others, so you can always see updated changes right away.

* there are currently 3 __Atom__ types:

> * `atom` - __Atom__ with rich text editor ([CKEditor](http://ckeditor.com/))
>* `atomline` - __Atom Line__ for editing plaintext inside fixed html tags
>* `atomentity` - __Atom Entity__ display and update column for given entity

* if you want to use __Atom__ in your templates, add Atom tag with _unique_ identifier:
  `{% atom unique_identifier_here %}`
  and closing tag `{% endatom %}`. You can add default content between tags, which will be persisted on first load.

```twig
{% atom foo %}
    <p> I am editable! </p>
{% endatom %}
```

* in case you want to edit only text content (like headings or table cells) and don't want to use rich text editor,
  there is the __Atom Line__ tag (again with _unique_ identifier): `{% atomline unique_identifier_here %}` and closing
  `{% endatomline %}`.

```twig
<h1>
   {% atomline bar %}
       I am editable!
   {% endatomline %}
<h1>
```

* for editing other entities, there is __Atom Entity__ tag, which takes these arguments:

    * name of Bundle containing desired entity:Entity name (e.g. `AppBundle:Product`)
    * name of method used for saving content (usually some setter)
    * entity id

* example (no need to add default value, it will be fetched by appropriate getter):

```twig
<div class="product-price">
   {% atomentity  AppBundle:Product, setPrice, 123 %}{% endatomentity %}
<div>
```

### Editable mode

* entering page with __Atoms__ in `prod` environment as user with role `ROLE_ATOM_EDIT` unlocks _editable mode_, which _
  _can be enabled or disabled__ by icon in bottom-right corner of browser screen.

### Translations:

* when switching between locales by changing `_locale` request parameter, you can easily update atoms in specified
  language.
  Also Atom Entities can be translated from frontend, if they have implemented Gedmo Translatable behavior.

### Automatic translations:

* for automatic translations, you will need a DeepL API key, which you can
  get [here](https://www.deepl.com/en/pro-api) (Free version offers 500,000 character limit / month) and put that in
  your ```.env.local```, for example:

```dotenv
DEEPL_KEY=xxxxxx
```

* they are disabled by default, you can enable them by creating ```tom_atom_atom.yaml``` in config/packages with these
  values:

```yaml
tom_atom_atom:
  automatic_translations: true
  deepl_key: '%env(DEEPL_KEY)%' # env for your DeepL API KEY
```

* automatic translations take place only when editing an Atom in your *default_locale*
* atoms will be translated for all languages in *enabled_locales*, **even if they had some values before!**