{% extends 'layout' %}

{% block content %}
<article>
    <h1 class="mb-4">{{ page.title }}</h1>
    <div class="page-content">
        {% render_content page %}
    </div>
</article>
{% endblock %}
