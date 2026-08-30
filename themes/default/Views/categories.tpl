{% extends 'layout' %}

{% block content %}
{% if theme_options.layout.blog_layout | default('sidebar-right') == 'sidebar-left' %}
<div class="row">
    <div class="col-lg-4">
        {! theme_regions.sidebar !}
    </div>
    <div class="col-lg-8">
        <h1 class="mb-4">Categories</h1>

        {% if categories %}
        <div class="list-group">
            {% for cat in categories %}
            <a href="{{ cat.url }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                {{ cat.name }}
                {% if cat.post_count %}
                <span class="badge bg-primary rounded-pill">{{ cat.post_count }}</span>
                {% endif %}
            </a>
            {% endfor %}
        </div>
        {% else %}
        <p>No categories found.</p>
        {% endif %}
    </div>
</div>
{% else %}
<div class="row">
    <div class="col-lg-8">
        <h1 class="mb-4">Categories</h1>

        {% if categories %}
        <div class="list-group">
            {% for cat in categories %}
            <a href="{{ cat.url }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                {{ cat.name }}
                {% if cat.post_count %}
                <span class="badge bg-primary rounded-pill">{{ cat.post_count }}</span>
                {% endif %}
            </a>
            {% endfor %}
        </div>
        {% else %}
        <p>No categories found.</p>
        {% endif %}
    </div>
    <div class="col-lg-4">
        {! theme_regions.sidebar !}
    </div>
</div>
{% endif %}
{% endblock %}
