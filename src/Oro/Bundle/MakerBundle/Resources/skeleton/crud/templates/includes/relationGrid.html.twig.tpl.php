{% import '@OroDataGrid/macros.html.twig' as dataGrid %}
{{ dataGrid.renderGrid(relation_grid_name, { holder_entity_id: holder_entity_id }, { cssClass: 'inner-grid' }) }}
