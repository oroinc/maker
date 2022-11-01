{% extends '@OroAction/Operation/form.html.twig' %}

{% block form_widget %}
    <fieldset class="form-horizontal">
<?php foreach ($fields as $field): ?>
        {{ form_row(form.entity.<?= $field ?>) }}
<?php endforeach; ?>
    </fieldset>

    <div class="hidden">
        {{ form_rest(form) }}
    </div>
{% endblock %}
