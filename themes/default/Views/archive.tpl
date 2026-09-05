{# Category/tag archive: a list of post cards. Extends the master layout. #}
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
        {# Conditional: the archive heading (category or tag name) is absent on some listings. #}
        {% if archive_title %}
        <h1>{{ archive_title }}</h1>
        {% endif %}

        {# Conditional + loop: one card per post. #}
        {% if posts %}
        {% for post in posts %}
        <article class="card mb-4">
            {# Conditional: card cover image. #}
            {% if post.featured_image %}
            <img src="{{ post.featured_image }}" class="card-img-top" alt="{{ post.title }}">
            {% endif %}
            <div class="card-body">
                {# Escaped output: title and date. #}
                <h2 class="card-title h5">
                    <a href="{{ post.url }}" class="text-decoration-none">{{ post.title }}</a>
                </h2>
                <p class="text-muted small">
                    {# Filter: date() formats the publish timestamp. #}
                    {{ post.published_at | date('F j, Y') }}
                    {# Conditional: author is optional. #}
                    {% if post.author %}
                    &middot; {{ post.author.name }}
                    {% endif %}
                </p>
                {# Filter-free output: excerpt is plain text. #}
                {% if post.excerpt %}
                <p class="card-text">{{ post.excerpt }}</p>
                {% endif %}
                {# Loops: the post's categories and tags, one badge each. #}
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

        {# Conditional + include: pagination partial renders only when there is more than one page. #}
        {% if pagination %}
        {% include 'partials/pagination' %}
        {% endif %}

        {% else %}
        {# Else branch: nothing published in this archive yet. #}
        <p>No posts found.</p>
        {% endif %}
    </div>
</div>
{% else %}
{# Default branch: post list first, sidebar on the right. Same markup as above, mirrored columns. #}
<div class="row">
    <div class="col-lg-8">
        {# Conditional: the archive heading. #}
        {% if archive_title %}
        <h1>{{ archive_title }}</h1>
        {% endif %}

        {# Conditional + loop: one card per post. #}
        {% if posts %}
        {% for post in posts %}
        <article class="card mb-4">
            {# Conditional: card cover image. #}
            {% if post.featured_image %}
            <img src="{{ post.featured_image }}" class="card-img-top" alt="{{ post.title }}">
            {% endif %}
            <div class="card-body">
                {# Escaped output: title and date. #}
                <h2 class="card-title h5">
                    <a href="{{ post.url }}" class="text-decoration-none">{{ post.title }}</a>
                </h2>
                <p class="text-muted small">
                    {# Filter: date() formats the publish timestamp. #}
                    {{ post.published_at | date('F j, Y') }}
                    {# Conditional: author is optional. #}
                    {% if post.author %}
                    &middot; {{ post.author.name }}
                    {% endif %}
                </p>
                {% if post.excerpt %}
                <p class="card-text">{{ post.excerpt }}</p>
                {% endif %}
                {# Loops: the post's categories and tags. #}
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

        {# Conditional + include: pagination partial. #}
        {% if pagination %}
        {% include 'partials/pagination' %}
        {% endif %}

        {% else %}
        <p>No posts found.</p>
        {% endif %}
    </div>
    <div class="col-lg-4">
        {# Region: sidebar content blocks. #}
        {% region 'sidebar' %}
    </div>
</div>
{% endif %}
{% endblock %}
