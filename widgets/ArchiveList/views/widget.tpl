<div class="{{ cls_widget | default('widget widget-archive') }}">
    {% if title %}
        <h4 class="{{ cls_title | default('widget-title') }}">{{ title }}</h4>
    {% endif %}
    <ul class="{{ cls_list | default('widget-list') }}">
        {% for row in rows %}
        <li class="{{ cls_list_item | default('widget-list-item') }}">
            <a href="{% base_url row.url %}" class="{{ cls_link | default('widget-list-link') }}">{{ row.label }}</a>
            {% if row.count %}
                <span class="{{ cls_badge | default('widget-badge') }}">{{ row.count }}</span>
            {% endif %}
        </li>
        {% endfor %}
    </ul>
</div>
