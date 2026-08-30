{% if posts %}
{% for post in posts %}
<article class="card mb-4">
    {% if post.featured_image %}
    <img src="{{ post.featured_image }}" class="card-img-top" alt="{{ post.title }}">
    {% endif %}
    <div class="card-body">
        <h2 class="card-title">
            <a href="{{ post.url }}" class="text-decoration-none">{{ post.title }}</a>
        </h2>
        <p class="text-muted small">
            {{ post.published_at | date('F j, Y') }}
            {% if post.author %}
            &middot; {{ post.author.name }}
            {% endif %}
        </p>
        {% if post.excerpt %}
        <p class="card-text">{{ post.excerpt }}</p>
        {% endif %}
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

{% if pagination %}
{% include 'partials/pagination' %}
{% endif %}

{% else %}
<p>No posts yet.</p>
{% endif %}
