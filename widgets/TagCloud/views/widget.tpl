<div class="{{ cls_widget | default('widget widget-tag-cloud') }}">
    {% if title %}
        <h4 class="{{ cls_title | default('widget-title') }}">{{ title }}</h4>
    {% endif %}
    <div class="{{ cls_tags | default('widget-tags') }}">
        {% for tag in tags %}
            <a href="{% tag_url tag.slug %}" class="{{ cls_tag | default('widget-tag') }}">{{ tag.name }}</a>
        {% endfor %}
    </div>
</div>
