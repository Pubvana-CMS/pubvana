<div class="pv-block pv-comments-recent">
    {% if title %}
    <h6 class="pv-block-title">{{ title }}</h6>
    {% endif %}

    {% if comments %}
    <ul class="pv-block-ul">
        {% for comment in comments %}
        <li class="pv-block-li">
            <span class="pv-comments-author">{{ comment.author }}</span>
            <span class="pv-comments-on">on</span>
            {% if comment.url != '#' %}
            <a class="pv-block-a" href="{{ comment.url }}">{{ comment.post_title }}</a>
            {% else %}
            <span class="pv-comments-title">{{ comment.post_title }}</span>
            {% endif %}
            <small class="pv-block-meta">{{ comment.date | date('M j, Y') }}</small>
        </li>
        {% endfor %}
    </ul>
    {% else %}
    <p class="pv-block-empty">No comments yet.</p>
    {% endif %}
</div>
