{% extends '@OroUI/actions/update.html.twig' %}

{% oro_title_set({params : {'%title%': <?= $entity_title_expression ?>, '%entityName%': '<?= $entity_label ?>'|trans} }) %}

{% set formAction = form.vars.value.id ? path('<?= $routes['update'] ?>', { 'id': form.vars.value.id }) : path('<?= $routes['create'] ?>')  %}

{% block navButtons %}
    {% import '@OroUI/macros.html.twig' as UI %}

    {{ parent() }}

    {{ UI.cancelButton(path('<?= $routes['index'] ?>')) }}
    {% set html = UI.saveAndCloseButton({
        'route': '<?= $routes['view'] ?>',
        'params': {'id': '$id'}
    }) %}
    {% if is_granted('<?= $create_acl ?>') %}
        {% set html = html ~ UI.saveAndNewButton({
            'route': '<?= $routes['create'] ?>'
        }) %}
    {% endif %}
    {% if entity.id or is_granted('<?= $update_acl ?>') %}
        {% set html = html ~ UI.saveAndStayButton({
            'route': '<?= $routes['update'] ?>',
            'params': {'id': '$id'}
        }) %}
    {% endif %}
    {{ UI.dropdownSaveButton({'html': html}) }}
{% endblock %}

{% block pageHeader %}
    {% if entity.id %}
        {% set breadcrumbs = {
            'entity':      entity,
            'indexPath':   path('<?= $routes['index'] ?>'),
            'indexLabel': '<?= $entity_plural_label ?>'|trans,
            'entityTitle': <?= $entity_title_expression . PHP_EOL ?>
        } %}
        {{ parent() }}
    {% else %}
        {% set title = 'oro.ui.create_entity'|trans({'%entityName%': '<?= $entity_label ?>'|trans}) %}
        {% include '@OroUI/page_title_block.html.twig' with { title: title } %}
    {% endif %}
{% endblock pageHeader %}

{% block stats %}
    <li>{{ 'oro.ui.created_at'|trans }}: {{ entity.createdAt ? entity.createdAt|oro_format_datetime : 'N/A' }}</li>
    <li>{{ 'oro.ui.updated_at'|trans }}: {{ entity.updatedAt ? entity.updatedAt|oro_format_datetime : 'N/A' }}</li>
{% endblock stats %}

{% block content_data %}
    {% set dataBlocks = [
<?php foreach ($update_page_blocks['data_blocks'] as $data_block): ?>
        {
            'title': '<?= $data_block['title'] ?>'|trans,
            'subblocks': [
                {
                    'data' : [
<?php foreach ($data_block['fields']['column1'] as $field): ?>
                        form_row(form.<?= $field['name'] ?>),
<?php endforeach; ?>
                    ]
                }<?php if (!empty($data_block['fields']['column2'])): ?>,
                {
                    'data' : [
<?php foreach ($data_block['fields']['column2'] as $field): ?>
                        form_row(form.<?= $field['name'] ?>),
                    ]
<?php endforeach; ?>
                }
<?php else: ?><?= PHP_EOL ?><?php endif; ?>
            ]
        },
<?php endforeach; ?>
    ] %}

    {% set dataBlocks = dataBlocks|merge(oro_form_additional_data(form, 'Additional'|trans)) %}

    {% set data = {
        'formErrors': form_errors(form),
        'dataBlocks': dataBlocks
    }%}

    <div class="responsive-form-inner">
        {% set id = '<?= $page_id ?>' %}
        {{ parent() }}
    </div>
{% endblock content_data %}
