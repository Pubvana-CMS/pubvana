{# Post cards partial: the reusable blog list, included by home.tpl and archive.tpl. #}
{# Conditional + loop: one card per post. #}
{% if posts %}
{% for post in posts %}
<article class="card mb-4">
    {# Conditional: card cover image. #}
    {% if post.featured_image %}
    <img src="{{ post.featured_image }}" class="card-img-top" alt="{{ post.title }}">
    {% endif %}
    <div class="card-body">
        {# Escaped output: title, date, author, excerpt. #}
        <h2 class="card-title">
            <a href="{{ post.url }}" class="text-decoration-none">{{ post.title }}</a>
        </h2>
        <p class="text-muted small">
            {# Filter: date() formats the publish timestamp. #}
            {{ post.published_at | date('F j, Y') }}
            {# Conditional: author is optional. #}
            {% if post.author %}
            &middot; {{ post.author.name }}
            {% endif %}
        </p>
        {# Conditional: excerpt is optional. #}
        {% if post.excerpt %}
        <p class="card-text">{{ post.excerpt }}</p>
        {% endif %}
        {# Loops: the post's categories and tags, one badge each. #}
        {% if post.categories or post.tags %}
        <div class="mb-2">
            {% for category in post.categories %}
            <a href="{{ category.url }}" class="badge bg-primary text-decoration-none">{{ category.name }}</a>
            {% endfor %}
            {% for tag in post.tags %}
            <a href="{{ tag.url }}" class="badge bg-secondary text-decoration-none">{{ tag.name }}</a>
            {% endfor %}
        </div>
        {% endif %}
        <a href="{{ post.url }}" class="btn btn-primary">Read More</a>
    </div>
</article>
{% endfor %}

{# Conditional + include: pagination partial renders only when there is more than one page. #}
{% if pagination %}
{% include 'partials/pagination' %}
{% endif %}

{% else %}
{# Else branch: nothing published yet. #}
<p>No posts yet.</p>
{% endif %}
