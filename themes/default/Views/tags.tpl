{% extends 'layout' %}

{% block content %}
{% if theme_options.layout.blog_layout | default('sidebar-right') == 'sidebar-left' %}
<div class="row">
    <div class="col-lg-4">
        {! theme_regions.sidebar !}
    </div>
    <div class="col-lg-8">
        <h1 class="mb-4">Tags</h1>

        {% if tags %}
        <div class="d-flex flex-wrap gap-2">
            {% for tag in tags %}
            <a href="{{ tag.url }}" class="badge bg-secondary text-decoration-none fs-6 py-2 px-3">{{ tag.name }}</a>
            {% endfor %}
        </div>
        {% else %}
        <p>No tags found.</p>
        {% endif %}
    </div>
</div>
{% else %}
<div class="row">
    <div class="col-lg-8">
        <h1 class="mb-4">Tags</h1>

        {% if tags %}
        <div class="d-flex flex-wrap gap-2">
            {% for tag in tags %}
            <a href="{{ tag.url }}" class="badge bg-secondary text-decoration-none fs-6 py-2 px-3">{{ tag.name }}</a>
            {% endfor %}
        </div>
        {% else %}
        <p>No tags found.</p>
        {% endif %}
    </div>
    <div class="col-lg-4">
        {! theme_regions.sidebar !}
    </div>
</div>
{% endif %}
{% endblock %}
