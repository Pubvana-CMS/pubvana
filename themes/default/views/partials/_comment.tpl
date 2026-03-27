<div class="comment mb-3 {% if depth > 0 %}ms-4 ps-3 border-start border-2{% endif %}">
    <div class="d-flex">
        <div class="flex-shrink-0 me-3">
            <img src="https://www.gravatar.com/avatar/{{ comment.author_email | strtolower | md5 }}?s=48&d=mp" class="rounded-circle" width="48" height="48" alt="">
        </div>
        <div class="flex-grow-1">
            <div class="fw-bold">{{ comment.author_name }}</div>
            <div class="text-muted small">{{ comment.created_at | date('F j, Y \a\t g:i a') }}</div>
            <div class="mt-2">{{ comment.content | nl2br | raw }}</div>
        </div>
    </div>
    {% if comment.children %}
        {% for child in comment.children %}
            {% include 'partials/_comment' with {comment: child, depth: depth + 1} %}
        {% endfor %}
    {% endif %}
</div>
