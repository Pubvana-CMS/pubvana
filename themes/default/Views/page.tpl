{% extends 'layout' %}

{% block content %}
{% if is_homepage and theme_options.layout.home_layout | default('full-width') == 'sidebar-left' %}
<div class="row">
    <div class="col-lg-4">
        {! theme_regions.sidebar !}
    </div>
    <div class="col-lg-8">
        <article>
            <h1>{{ title }}</h1>

            {% if ai_disclosure %}
            <p class="small text-secondary"><em>This content was created with AI assistance and reviewed for accuracy.</em></p>
            {% endif %}

            {% if featured_image %}
            <img src="{{ featured_image }}" class="img-fluid rounded mb-4" alt="{{ title }}">
            {% endif %}

            <div class="content">
                {! content !}
            </div>
        </article>

        {! comments_html !}
    </div>
</div>
{% elseif is_homepage and theme_options.layout.home_layout | default('full-width') == 'sidebar-right' %}
<div class="row">
    <div class="col-lg-8">
        <article>
            <h1>{{ title }}</h1>

            {% if ai_disclosure %}
            <p class="small text-secondary"><em>This content was created with AI assistance and reviewed for accuracy.</em></p>
            {% endif %}

            {% if featured_image %}
            <img src="{{ featured_image }}" class="img-fluid rounded mb-4" alt="{{ title }}">
            {% endif %}

            <div class="content">
                {! content !}
            </div>
        </article>

        {! comments_html !}
    </div>
    <div class="col-lg-4">
        {! theme_regions.sidebar !}
    </div>
</div>
{% else %}
<article>
    <h1>{{ title }}</h1>

    {% if ai_disclosure %}
    <p class="small text-secondary"><em>This content was created with AI assistance and reviewed for accuracy.</em></p>
    {% endif %}

    {% if featured_image %}
    <img src="{{ featured_image }}" class="img-fluid rounded mb-4" alt="{{ title }}">
    {% endif %}

    <div class="content">
        {! content !}
    </div>
</article>

{! comments_html !}
{% endif %}
{% endblock %}