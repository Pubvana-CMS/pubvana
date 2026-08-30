{% extends "layout.tpl" %}

{% block content %}
{% if posts %}
{% for post in posts %}
<article class="mb-4">
    <h2><a href="/blog/{{ post.slug }}">{{ post.title }}</a></h2>
    <div class="text-secondary mb-2">{{ post.created_at }}</div>
    <p>{{ post.excerpt }}</p>
</article>
{% endfor %}
{% else %}
<p>No posts yet.</p>
{% endif %}
{% endblock %}
