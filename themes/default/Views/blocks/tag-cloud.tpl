{# Tag Cloud block template #}
<div class="card mb-3">
    <div class="card-header">
        <h3 class="card-title h5 mb-0">Tags</h3>
    </div>
    <div class="card-body">
        {% if tags %}
        <div class="d-flex flex-wrap gap-2">
            {% for tag in tags %}
            <a href="/blog/tag/{{ tag.slug }}" class="badge bg-blue-lt text-blue">{{ tag.name }}</a>
            {% endfor %}
        </div>
        {% else %}
        <p class="text-secondary mb-0">No tags yet.</p>
        {% endif %}
    </div>
</div>
