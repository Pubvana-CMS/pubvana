{% extends 'layout' %}

{% block content %}
{% if theme_options.layout.blog_layout | default('sidebar-right') == 'sidebar-left' %}
<div class="row">
    <div class="col-lg-4">
        {! theme_regions.sidebar !}
    </div>
    <div class="col-lg-8">
        {% if archive_title %}
        <h1>{{ archive_title }}</h1>
        {% endif %}

        {% if posts %}
        {% for post in posts %}
        <article class="card mb-4">
            {% if post.featured_image %}
            <img src="{{ post.featured_image }}" class="card-img-top" alt="{{ post.title }}">
            {% endif %}
            <div class="card-body">
                <h2 class="card-title h5">
                    <a href="{{ post.url }}" class="text-decoration-none">{{ post.title }}</a>
                </h2>
                <p class="text-muted small">
                    {{ post.published_at | date('F j, Y') }}
                    {% if post.author %}
                    &middot; {{ post.author.name }}
                    {% endif %}
                </p>
                {% if post.excerpt %}
                <p class="card-text">{{ post.excerpt }}</p>
                {% endif %}
                {% if post.categories or post.tags %}
                <div class="mb-2">
                    {% for category in post.categories %}
                    <a href="{{ category.url }}" class="badge bg-primary text-decoration-none">{{ category.name }}</a>
                    {% endfor %}
                    {% for tag in post.tags %}
                    <a href="{{ tag.url }}" class="badge bg-secondary text-decoration-none">{{ tag.name }}</a>
                    {% endfor %}
                </div>
                {% endif %}
            </div>
        </article>
        {% endfor %}

        {% if pagination %}
        {% include 'partials/pagination' %}
        {% endif %}

        {% else %}
        <p>No posts found.</p>
        {% endif %}
    </div>
</div>
{% else %}
<div class="row">
    <div class="col-lg-8">
        {% if archive_title %}
        <h1>{{ archive_title }}</h1>
        {% endif %}

        {% if posts %}
        {% for post in posts %}
        <article class="card mb-4">
            {% if post.featured_image %}
            <img src="{{ post.featured_image }}" class="card-img-top" alt="{{ post.title }}">
            {% endif %}
            <div class="card-body">
                <h2 class="card-title h5">
                    <a href="{{ post.url }}" class="text-decoration-none">{{ post.title }}</a>
                </h2>
                <p class="text-muted small">
                    {{ post.published_at | date('F j, Y') }}
                    {% if post.author %}
                    &middot; {{ post.author.name }}
                    {% endif %}
                </p>
                {% if post.excerpt %}
                <p class="card-text">{{ post.excerpt }}</p>
                {% endif %}
                {% if post.categories or post.tags %}
                <div class="mb-2">
                    {% for category in post.categories %}
                    <a href="{{ category.url }}" class="badge bg-primary text-decoration-none">{{ category.name }}</a>
                    {% endfor %}
                    {% for tag in post.tags %}
                    <a href="{{ tag.url }}" class="badge bg-secondary text-decoration-none">{{ tag.name }}</a>
                    {% endfor %}
                </div>
                {% endif %}
            </div>
        </article>
        {% endfor %}

        {% if pagination %}
        {% include 'partials/pagination' %}
        {% endif %}

        {% else %}
        <p>No posts found.</p>
        {% endif %}
    </div>
    <div class="col-lg-4">
        {! theme_regions.sidebar !}
    </div>
</div>
{% endif %}
{% endblock %}
