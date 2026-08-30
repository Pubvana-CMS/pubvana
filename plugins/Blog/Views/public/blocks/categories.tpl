<div class="card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">{{ title }}</h5>
    </div>
    <div class="list-group list-group-flush">
        {% for cat in categories %}
        <a href="{{ cat.url }}" class="list-group-item list-group-item-action">{{ cat.name }}</a>
        {% endfor %}
    </div>
</div>
