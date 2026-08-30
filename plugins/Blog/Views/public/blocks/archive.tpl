<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">{{ title }}</h5>
    </div>
    <div class="list-group list-group-flush">
        {% for month in months %}
        <a href="{{ month.url }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
            {{ month.label }}
            <span class="badge bg-primary rounded-pill">{{ month.count }}</span>
        </a>
        {% endfor %}
    </div>
</div>
