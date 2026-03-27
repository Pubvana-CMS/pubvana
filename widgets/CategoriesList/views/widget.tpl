<div class="{{ cls_widget | default('widget widget-categories') }}">
    {% if title %}
        <h4 class="{{ cls_title | default('widget-title') }}">{{ title }}</h4>
    {% endif %}
    <ul class="{{ cls_list | default('widget-list') }}">
        {% for cat in categories %}
        <li class="{{ cls_list_item | default('widget-list-item') }}">
            <a href="{% category_url cat.slug %}" class="{{ cls_link | default('widget-list-link') }}">{{ cat.name }}</a>
            {% if show_count %}
                <span class="{{ cls_badge | default('widget-badge') }}">{{ cat.post_count }}</span>
            {% endif %}
        </li>
        {% endfor %}
        {% if not categories %}
            <li class="{{ cls_list_item | default('widget-list-item') }} {{ cls_empty | default('widget-empty') }}">{% lang 'Blog.noPostsYet' %}</li>
        {% endif %}
    </ul>
</div>
