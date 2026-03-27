<div class="{{ cls_widget | default('widget widget-recent-comments') }}">
    {% if title %}
        <h4 class="{{ cls_title | default('widget-title') }}">{{ title }}</h4>
    {% endif %}
    {% if comments %}
    <ul class="{{ cls_list | default('widget-list') }}">
        {% for c in comments %}
        <li class="{{ cls_list_item | default('widget-list-item') }}">
            <span class="{{ cls_meta | default('widget-meta') }}">{{ c.author_name }}</span>
            <a href="{% post_url c.post_slug %}" class="{{ cls_link | default('widget-list-link') }}">{{ c.content | excerpt(60) }}</a>
        </li>
        {% endfor %}
    </ul>
    {% endif %}
</div>
