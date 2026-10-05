{# Theme override for the Comments Recent Comments block (pubvana.comments.recent).
   Resolved by RegionManager::resolveBlockTemplate() as
   themes/{active}/Views/pubvana/comments/public/blocks/recent-comments.tpl. #}
<div class="pv-block pv-block-recent-comments">
    {% if title %}<h3 class="pv-block-title">{{ title }}</h3>{% endif %}
    {% if comments %}
    <ul class="list-unstyled pv-recent-comments">
        {% for comment in comments %}
        <li class="pv-recent-comment mb-2">
            <strong>{{ comment.author }}</strong>
            <span class="text-muted small ms-1">{{ comment.created_at | date('M j, Y') }}</span>
            <div class="small">{! comment.excerpt !}</div>
            {% if comment.url %}<a class="small" href="{{ comment.url }}">View</a>{% endif %}
        </li>
        {% endfor %}
    </ul>
    {% else %}
    <p class="text-muted">No comments yet.</p>
    {% endif %}
</div>
