{# Static page. Extends the master layout and fills its `content` block. #}
{# This template also serves the homepage when the front page is a static page (is_homepage). #}
{% extends 'layout' %}

{% block content %}
{# Homepage + theme option home_layout = sidebar-left: sidebar first, page body second. #}
{% if is_homepage and theme_options.layout.home_layout | default('full-width') == 'sidebar-left' %}
<div class="row">
    <div class="col-lg-4">
        {# Region: sidebar content blocks. #}
        {% region 'sidebar' %}
    </div>
    <div class="col-lg-8">
        <article>
            {# Escaped output: the page title. #}
            <h1>{{ title }}</h1>

            {# Conditional: AI-assistance disclosure line. #}
            {% if ai_disclosure %}
            <p class="small text-secondary"><em>This content was created with AI assistance and reviewed for accuracy.</em></p>
            {% endif %}

            {# Conditional: featured image. #}
            {% if featured_image %}
            <img src="{{ featured_image }}" class="img-fluid rounded mb-4" alt="{{ title }}">
            {% endif %}

            {# Raw output: the page body, admin-authored HTML from the editor. #}
            <div class="content">
                {! content !}
            </div>
        </article>

        {# Raw output: the rendered comment thread (empty string when comments are off for pages). #}
        {! comments_html !}

        {# Region: content blocks placed below the page, inside the content column. #}
        {% region 'after-content' %}
    </div>
</div>
{# Homepage + theme option home_layout = sidebar-right: page body first, sidebar second. #}
{% elseif is_homepage and theme_options.layout.home_layout | default('full-width') == 'sidebar-right' %}
<div class="row">
    <div class="col-lg-8">
        <article>
            {# Escaped output: the page title. #}
            <h1>{{ title }}</h1>

            {# Conditional: AI-assistance disclosure line. #}
            {% if ai_disclosure %}
            <p class="small text-secondary"><em>This content was created with AI assistance and reviewed for accuracy.</em></p>
            {% endif %}

            {# Conditional: featured image. #}
            {% if featured_image %}
            <img src="{{ featured_image }}" class="img-fluid rounded mb-4" alt="{{ title }}">
            {% endif %}

            {# Raw output: the page body, admin-authored HTML. #}
            <div class="content">
                {! content !}
            </div>
        </article>

        {# Raw output: the rendered comment thread. #}
        {! comments_html !}

        {# Region: content blocks placed below the page, inside the content column. #}
        {% region 'after-content' %}
    </div>
    <div class="col-lg-4">
        {# Region: sidebar content blocks. #}
        {% region 'sidebar' %}
    </div>
</div>
{% else %}
{# Full-width (default): plain article, no sidebar. #}
<article>
    {# Escaped output: the page title. #}
    <h1>{{ title }}</h1>

    {# Conditional: AI-assistance disclosure line. #}
    {% if ai_disclosure %}
    <p class="small text-secondary"><em>This content was created with AI assistance and reviewed for accuracy.</em></p>
    {% endif %}

    {# Conditional: featured image. #}
    {% if featured_image %}
    <img src="{{ featured_image }}" class="img-fluid rounded mb-4" alt="{{ title }}">
    {% endif %}

    {# Raw output: the page body, admin-authored HTML. #}
    <div class="content">
        {! content !}
    </div>
</article>

{# Raw output: the rendered comment thread. #}
{! comments_html !}

{# Region: content blocks placed below the page. Full width here: this branch has no sidebar. #}
{% region 'after-content' %}
{% endif %}
{% endblock %}
