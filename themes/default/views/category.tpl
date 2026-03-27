{% extends 'layout' %}

{% block content %}
<h1 class="mb-1">{% lang 'Blog.categoryHeading' category.name %}</h1>
{% if category.description %}
    <p class="text-muted mb-4">{{ category.description }}</p>
{% endif %}

{% if posts %}
    {% for post in posts %}
        {% include 'partials/post-card' with {post: post} %}
    {% endfor %}
    {% if pager_links %}
        {% include 'partials/pagination' with {pager_links: pager_links} %}
    {% endif %}
{% else %}
    <p class="text-muted">{% lang 'Blog.noPostsInCategory' %}</p>
{% endif %}
{% endblock %}
