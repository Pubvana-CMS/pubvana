<div class="comments-list mb-4">
    {% for comment in comments %}
        {% include 'partials/_comment' with {comment: comment, depth: 0} %}
    {% endfor %}
</div>
