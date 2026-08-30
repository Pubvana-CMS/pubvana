{# Recent Posts block template #}
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title h5 mb-0">Recent Posts</h3>
    </div>
    <div class="list-group list-group-flush">
        {% if posts %}
        {% for post in posts %}
        <a href="/blog/{{ post.slug }}" class="list-group-item list-group-item-action">
            <div class="fw-bold">{{ post.title }}</div>
            <small class="text-secondary">{{ post.created_at }}</small>
        </a>
        {% endfor %}
        {% else %}
        <div class="list-group-item text-secondary">No posts yet.</div>
        {% endif %}
    </div>
</div>
