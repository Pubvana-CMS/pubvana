<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">{{ title }}</h5>
    </div>
    <div class="card-body">
        <div class="d-flex flex-wrap gap-2">
            {% for tag in tags %}
            <a href="{{ tag.url }}" class="badge bg-secondary text-decoration-none">{{ tag.name }}</a>
            {% endfor %}
        </div>
    </div>
</div>
