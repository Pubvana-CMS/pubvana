{% extends 'layout' %}

{% block content %}
{% if posts %}
    {% for post in posts %}
        {% include 'partials/post-card' with {post: post} %}
    {% endfor %}
    {% if pager_links %}
        {% include 'partials/pagination' with {pager_links: pager_links} %}
    {% endif %}
{% else %}
    <p class="text-muted text-center py-4">{% lang 'Blog.noPostsYet' %}</p>
{% endif %}
{% endblock %}
