<?php
if (!function_exists('renderFields')) {
    function renderFields(array $fields): string
    {
        $data = [];
        foreach ($fields as $field) {
            if ($field['render_type'] === 'html') {
                $data[] = sprintf("%sUI.renderHtmlProperty('%s'|trans, %s)", str_repeat(' ', 24), $field['label'], $field['field_expression']);
            } elseif ($field['render_type'] === 'image') {
                $data[] = <<<HTML
                        UI.renderHtmlProperty(
                            '{$field['label']}'|trans,
                            include(
                                '@OroAttachment/Twig/picture.html.twig',
                                {
                                    sources: oro_filtered_picture_sources(entity.{$field['name']}, 'digital_asset_medium') ?: asset('bundles/oroui/img/avatar-xsmall.png')
                                }
                            )
                        )
HTML;
            } elseif ($field['render_type'] === 'collapsable_html') {
                $data[] = sprintf("%sUI.renderCollapsibleHtmlProperty('%s'|trans, %s, entity, '%s')", str_repeat(' ', 24), $field['label'], $field['field_expression'], $field['field_name']);
            } elseif ($field['render_type'] === 'entity_link') {
                $data[] = <<<HTML
                        UI.renderHtmlProperty(
                            '{$field['label']}'|trans,
                            UI.entityViewLink(entity.{$field['name']}, {$field['field_expression']}, '{$field['relation_view_route']}')
                        )
HTML;
            } elseif ($field['render_type'] === 'grid') {
                $data[] = sprintf("%sdataGrid.renderGrid('%s', { holder_entity_id: entity.id }, { cssClass: 'inner-grid' })", str_repeat(' ', 24), $field['grid_name']);
            } elseif ($field['render_type'] === 'extend_fields') {
                $data[] = sprintf('%sentityConfig.renderDynamicFields(entity)', str_repeat(' ', 24));
            } else {
                $data[] = sprintf("%sUI.renderProperty('%s'|trans, %s)", str_repeat(' ', 24), $field['label'], $field['field_expression']);
            }
        }

        return implode(',' . PHP_EOL, $data);
    }
}
?>
{% extends '@OroUI/actions/view.html.twig' %}
{% import '@OroUI/macros.html.twig' as UI %}
{% import '@OroEntityConfig/macros.html.twig' as entityConfig %}
<?php foreach ($view_page_blocks['additional_macros'] as $macrosAlias => $macrosPath) : ?>
{% import '<?= $macrosPath ?>' as <?= $macrosAlias ?> %}
<?php endforeach; ?>

{% oro_title_set({params : {"%title%": <?= $entity_title_expression ?> } }) %}

{% block pageHeader %}
    {% set breadcrumbs = {
        'entity': entity,
        'indexPath': path('<?= $routes['index'] ?>'),
        'indexLabel': '<?= $entity_plural_label ?>'|trans,
        'entityTitle': <?= $entity_title_expression ?><?= PHP_EOL ?>
} %}

    {{ parent() }}
{% endblock pageHeader %}

{% block content_data %}
<?php foreach ($block_buttons as $sectionAlias => $buttons): ?>
    {% set <?= $sectionAlias ?>_buttons %}
        <div class="row">
            <div class="pull-right">
                <div class="pull-left btn-group icons-holder">
<?php if ($buttons['type'] === 'import_export'): ?>                    {% include '@OroImportExport/ImportExport/buttons_from_configuration.html.twig' with {
                        'alias': '<?= $buttons['import_alias'] ?>',
                        'options': {'holder_entity_id': entity.id}
                    } %}
<?php endif ?>
                </div>
            </div>
        </div>
        <div class="clearfix"></div>
    {% endset %}
<?php endforeach ?>

    {% set dataBlocks = [
<?php foreach ($view_page_blocks['data_blocks'] as $data_block): ?>
        {
            'title': '<?= $data_block['title'] ?>'|trans,
            'subblocks': [
                {
                    'data' : [
<?php if (isset($data_block['alias'], $block_buttons[$data_block['alias']])): ?>
                        <?= $data_block['alias'] ?>_buttons,
<?php endif ?>
<?= renderFields($data_block['fields']['column1']) . PHP_EOL ?>
                    ]
                }<?php if (!empty($data_block['fields']['column2'])): ?>,
                {
                    'data' : [
<?= renderFields($data_block['fields']['column2']) . PHP_EOL ?>
                    ]
                }
<?php else: ?><?= PHP_EOL ?><?php endif; ?>
            ]
        },
<?php endforeach; ?>
    ] %}

    {% set id = '<?= $page_id ?>' %}
    {% set data = {'dataBlocks': dataBlocks} %}

    {{ parent() }}
{% endblock content_data %}
