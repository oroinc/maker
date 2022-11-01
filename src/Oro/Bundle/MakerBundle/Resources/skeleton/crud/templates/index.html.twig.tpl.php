{% extends '@OroUI/actions/index.html.twig' %}
{% import '@OroUI/macros.html.twig' as UI %}
{% set gridName = '<?= $grid_name ?>' %}
{% set pageTitle = '<?= $entity_plural_label ?>'|trans %}

{% block navButtons %}
    {% import '@OroUI/macros.html.twig' as UI %}

<?php if ($import_export_alias): ?>
    {% include '@OroImportExport/ImportExport/buttons_from_configuration.html.twig' with {
        'alias': '<?= $import_export_alias ?>'
    } %}

<?php endif ?>
    {% if is_granted('<?= $create_acl ?>') %}
        <div class="btn-group">
        {{ UI.addButton({
            'path': path('<?= $routes['create'] ?>'),
            'entity_label': '<?= $entity_label ?>'|trans
        }) }}
        </div>
    {% endif %}
{% endblock %}
