{# Category index: all categories with post counts. Extends the master layout. #}
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
        <h1 class="mb-4">Categories</h1>

        {# Conditional + loop: one list row per category. #}
        {% if categories %}
        <div class="list-group">
            {% for cat in categories %}
            <a href="{{ cat.url }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                {# Escaped output: category name. #}
                {{ cat.name }}
                {# Conditional: post count badge, shown only when the category has posts. #}
                {% if cat.post_count %}
                <span class="badge bg-primary rounded-pill">{{ cat.post_count }}</span>
                {% endif %}
            </a>
            {% endfor %}
        </div>
        {% else %}
        {# Else branch: no categories exist yet. #}
        <p>No categories found.</p>
        {% endif %}
    </div>
</div>
{% else %}
{# Default branch: list first, sidebar on the right. #}
<div class="row">
    <div class="col-lg-8">
        <h1 class="mb-4">Categories</h1>

        {# Conditional + loop: one list row per category. #}
        {% if categories %}
        <div class="list-group">
            {% for cat in categories %}
            <a href="{{ cat.url }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                {# Escaped output: category name. #}
                {{ cat.name }}
                {# Conditional: post count badge. #}
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
        {# Region: sidebar content blocks. #}
        {% region 'sidebar' %}
    </div>
</div>
{% endif %}
{% endblock %}
