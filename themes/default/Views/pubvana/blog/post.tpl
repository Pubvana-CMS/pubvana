{# Single blog post. Content-only; the layout owns the sidebar. #}
<article>
    {# Escaped output: the post title is plain text and safe to escape. #}
    <h1>{{ title }}</h1>
    {# Conditional: the AiAssistant plugin adds ai_disclosure when the post used AI assistance. #}
    {% if ai_disclosure %}
    <p class="small text-secondary"><em>This content was created with AI assistance and reviewed for accuracy.</em></p>
    {% endif %}
    <p class="text-muted">
        {# Filter: date() formats the timestamp. Missing values pass through unchanged. #}
        {{ published_at | date('F j, Y') }}
        {# Conditional: author is optional. #}
        {% if author %}
        &middot; {% if author.url %}<a href="{{ author.url }}">{{ author.name }}</a>{% else %}{{ author.name }}{% endif %}
        {% endif %}
    </p>

    {# Conditional: featured image is optional. #}
    {% if featured_image %}
    <img src="{{ featured_image }}" class="img-fluid rounded mb-4" alt="{{ title }}">
    {% endif %}

    {# Loop: the post's tags, one badge each. #}
    {% if tags %}
    <div class="mb-3">
        {% for tag in tags %}
        <a href="{{ tag.url }}" class="badge bg-secondary text-decoration-none">{{ tag.name }}</a>
        {% endfor %}
    </div>
    {% endif %}

    {# Raw output: the post body is admin-authored HTML from the editor. Escaping would show tags as text. #}
    <div class="content">
        {! content !}
    </div>

    {# Loop: the post's categories, one badge each. #}
    {% if categories %}
    <div class="mt-4">
        <strong>Categories:</strong>
        {% for category in categories %}
        <a href="{{ category.url }}" class="badge bg-primary text-decoration-none">{{ category.name }}</a>
        {% endfor %}
    </div>
    {% endif %}
</article>

{# Raw output: the rendered comment thread (empty string when the Comments plugin is off). #}
{! comments_html !}

{# Region: content blocks placed below the post, inside the content column. #}
{% region 'after-content' %}
