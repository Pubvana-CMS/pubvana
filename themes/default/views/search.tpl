{% extends 'layout' %}

{% block content %}
<h1 class="mb-2">{% lang 'Blog.searchResultsHeading' %}</h1>
{% if query %}
    <p class="text-muted mb-4">{% lang 'Blog.searchShowingFor' query %}</p>
{% endif %}

<form action="{% site_url 'search' %}" method="GET" class="mb-4">
    <div class="input-group">
        <input type="text" class="form-control" name="q" value="{{ query }}" placeholder="{% lang 'Blog.searchPostsPlaceholder' %}">
        <button type="submit" class="btn btn-primary"><i class="fas fa-magnifying-glass"></i></button>
    </div>
</form>

{% if posts %}
    {% for post in posts %}
        {% include 'partials/post-card' with {post: post} %}
    {% endfor %}
    {% if pager_links %}
        {% include 'partials/pagination' with {pager_links: pager_links} %}
    {% endif %}
{% else %}
    {% if query %}
        <p class="text-muted">{% lang 'Blog.searchNoResults' query %}</p>
    {% endif %}
{% endif %}
{% endblock %}
