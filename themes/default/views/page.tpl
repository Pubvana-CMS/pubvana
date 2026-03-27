{% extends 'layout' %}

{% block content %}
<div class="row justify-content-center">
    <div class="col-lg-9">
        <article>
            <h1 class="mb-4">{{ page.title }}</h1>
            <div class="page-content">
                {% render_content page %}
            </div>
        </article>
    </div>
</div>
{% endblock %}
