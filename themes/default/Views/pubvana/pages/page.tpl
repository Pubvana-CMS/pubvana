{# Static page. Content-only; the layout owns the sidebar (layout.page_sidebar). #}
{# This template also serves the homepage when the front page is a static page (is_homepage). #}
<article>
    {% if not is_homepage %}
    {# Escaped output: the page title. The homepage omits it: the hero carries it. #}
    <h1>{{ title }}</h1>
    {% endif %}

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
