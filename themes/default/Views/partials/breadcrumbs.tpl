{# Breadcrumbs partial, included from layout.tpl when enabled and a trail exists. #}
<nav aria-label="breadcrumb" class="container mt-3">
    <ol class="breadcrumb mb-0">
        {# Loop: one item per crumb, in order. #}
        {% for crumb in breadcrumbs %}
        {# Conditional: a crumb with a URL links back; the last one is the active page. #}
        {% if crumb.url %}
        <li class="breadcrumb-item"><a href="{{ crumb.url }}">{{ crumb.label }}</a></li>
        {% else %}
        <li class="breadcrumb-item active" aria-current="page">{{ crumb.label }}</li>
        {% endif %}
        {% endfor %}
    </ol>
</nav>
