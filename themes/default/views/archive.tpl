{% extends 'layout' %}

{% block content %}
<h1 class="mb-4">{% lang 'Blog.archiveHeading' archive.title %}</h1>

{% if posts %}
    {% for post in posts %}
        {% include 'partials/post-card' with {post: post} %}
    {% endfor %}
    {% if pager_links %}
        {% include 'partials/pagination' with {pager_links: pager_links} %}
    {% endif %}
{% else %}
    <p class="text-muted">{% lang 'Blog.noPostsInPeriod' %}</p>
{% endif %}
{% endblock %}
