{# Minimal post list, kept as the simplest possible template. #}
{# The richer homepage is home.tpl; this one shows the bare minimum a public template needs. #}
{% extends "layout.tpl" %}

{% block content %}
{# Conditional + loop: one plain article per post, no cards or images. #}
{% if posts %}
{% for post in posts %}
<article class="mb-4">
    {# Escaped output: title, date, and excerpt. #}
    <h2><a href="/blog/{{ post.slug }}">{{ post.title }}</a></h2>
    <div class="text-secondary mb-2">{{ post.created_at }}</div>
    <p>{{ post.excerpt }}</p>
</article>
{% endfor %}
{% else %}
{# Else branch: nothing published yet. #}
<p>No posts yet.</p>
{% endif %}
{% endblock %}
