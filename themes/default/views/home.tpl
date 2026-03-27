{% extends 'layout' %}

{% block content %}
<div class="row">
    <div class="{% if show_sidebar == '1' %}col-lg-8{% else %}col-12{% endif %}">
        {% widget_area 'before-content' %}
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
    </div>
    {% if show_sidebar == '1' %}
    <div class="col-lg-4">
        {% include 'partials/sidebar' %}
    </div>
    {% endif %}
</div>
{% endblock %}
