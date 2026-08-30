<nav aria-label="breadcrumb" class="container mt-3">
    <ol class="breadcrumb mb-0">
        {% for crumb in breadcrumbs %}
        {% if crumb.url %}
        <li class="breadcrumb-item"><a href="{{ crumb.url }}">{{ crumb.label }}</a></li>
        {% else %}
        <li class="breadcrumb-item active" aria-current="page">{{ crumb.label }}</li>
        {% endif %}
        {% endfor %}
    </ol>
</nav>
