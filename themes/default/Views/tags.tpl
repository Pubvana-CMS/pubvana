{# Tag index: all tags as badges. Extends the master layout. #}
{% extends 'layout' %}

{% block content %}
{# Theme option layout.blog_layout chooses which side the sidebar sits on. #}
{# This branch: sidebar on the left (theme option set to sidebar-left). #}
{% if theme_options.layout.blog_layout | default('sidebar-right') == 'sidebar-left' %}
<div class="row">
    <div class="col-lg-4">
        {# Region: sidebar content blocks. #}
        {% region 'sidebar' %}
    </div>
    <div class="col-lg-8">
        <h1 class="mb-4">Tags</h1>

        {# Conditional + loop: one badge per tag. #}
        {% if tags %}
        <div class="d-flex flex-wrap gap-2">
            {% for tag in tags %}
            <a href="{{ tag.url }}" class="badge bg-secondary text-decoration-none fs-6 py-2 px-3">{{ tag.name }}</a>
            {% endfor %}
        </div>
        {% else %}
        {# Else branch: no tags exist yet. #}
        <p>No tags found.</p>
        {% endif %}

        {# Region: content blocks placed below the tag list, inside the content column. #}
        {% region 'after-content' %}
    </div>
</div>
{% else %}
{# Default branch: badges first, sidebar on the right. #}
<div class="row">
    <div class="col-lg-8">
        <h1 class="mb-4">Tags</h1>

        {# Conditional + loop: one badge per tag. #}
        {% if tags %}
        <div class="d-flex flex-wrap gap-2">
            {% for tag in tags %}
            <a href="{{ tag.url }}" class="badge bg-secondary text-decoration-none fs-6 py-2 px-3">{{ tag.name }}</a>
            {% endfor %}
        </div>
        {% else %}
        <p>No tags found.</p>
        {% endif %}

        {# Region: content blocks placed below the tag list, inside the content column. #}
        {% region 'after-content' %}
    </div>
    <div class="col-lg-4">
        {# Region: sidebar content blocks. #}
        {% region 'sidebar' %}
    </div>
</div>
{% endif %}
{% endblock %}
