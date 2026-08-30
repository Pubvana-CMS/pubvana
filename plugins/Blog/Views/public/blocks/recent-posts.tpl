<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">{{ title }}</h5>
    </div>
    <div class="list-group list-group-flush">
        {% for post in posts %}
        <a href="{{ post.url }}" class="list-group-item list-group-item-action">
            <div>{{ post.title }}</div>
            <small class="text-secondary">{{ post.published_at | date('M j, Y') }}</small>
        </a>
        {% endfor %}
    </div>
</div>
