{% extends 'layout' %}

{% block content %}
{% if theme_options.layout.blog_layout | default('sidebar-right') == 'sidebar-left' %}
<div class="row">
    <div class="col-lg-4">
        {! theme_regions.sidebar !}
    </div>
    <div class="col-lg-8">
        <article>
            <h1>{{ title }}</h1>
            {% if ai_disclosure %}
            <p class="small text-secondary"><em>This content was created with AI assistance and reviewed for accuracy.</em></p>
            {% endif %}
            <p class="text-muted">
                {{ published_at | date('F j, Y') }}
                {% if author %}
                &middot; {% if author.url %}<a href="{{ author.url }}">{{ author.name }}</a>{% else %}{{ author.name }}{% endif %}
                {% endif %}
            </p>

            {% if featured_image %}
            <img src="{{ featured_image }}" class="img-fluid rounded mb-4" alt="{{ title }}">
            {% endif %}

            {% if tags %}
            <div class="mb-3">
                {% for tag in tags %}
                <a href="{{ tag.url }}" class="badge bg-secondary text-decoration-none">{{ tag.name }}</a>
                {% endfor %}
            </div>
            {% endif %}

            <div class="content">
                {! content !}
            </div>

            {% if categories %}
            <div class="mt-4">
                <strong>Categories:</strong>
                {% for category in categories %}
                <a href="{{ category.url }}" class="badge bg-primary text-decoration-none">{{ category.name }}</a>
                {% endfor %}
            </div>
            {% endif %}
        </article>

        {! comments_html !}
    </div>
    <div class="col-lg-4">
        {! theme_regions.sidebar !}
    </div>
</div>
{% else %}
<div class="row">
    <div class="col-lg-8">
        <article>
            <h1>{{ title }}</h1>
            {% if ai_disclosure %}
            <p class="small text-secondary"><em>This content was created with AI assistance and reviewed for accuracy.</em></p>
            {% endif %}
            <p class="text-muted">
                {{ published_at | date('F j, Y') }}
                {% if author %}
                &middot; {% if author.url %}<a href="{{ author.url }}">{{ author.name }}</a>{% else %}{{ author.name }}{% endif %}
                {% endif %}
            </p>

            {% if featured_image %}
            <img src="{{ featured_image }}" class="img-fluid rounded mb-4" alt="{{ title }}">
            {% endif %}

            {% if tags %}
            <div class="mb-3">
                {% for tag in tags %}
                <a href="{{ tag.url }}" class="badge bg-secondary text-decoration-none">{{ tag.name }}</a>
                {% endfor %}
            </div>
            {% endif %}

            <div class="content">
                {! content !}
            </div>

            {% if categories %}
            <div class="mt-4">
                <strong>Categories:</strong>
                {% for category in categories %}
                <a href="{{ category.url }}" class="badge bg-primary text-decoration-none">{{ category.name }}</a>
                {% endfor %}
            </div>
            {% endif %}
        </article>

        {! comments_html !}
    </div>
    <div class="col-lg-4">
        {! theme_regions.sidebar !}
    </div>
</div>
{% endif %}
{% endblock %}
