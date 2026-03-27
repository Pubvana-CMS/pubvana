{% if posts %}
<div class="{{ cls_widget | default('widget widget-related-posts') }}">
    {% if title %}
        <h4 class="{{ cls_title | default('widget-title') }}">{{ title }}</h4>
    {% endif %}
    <ul class="{{ cls_list | default('widget-list') }}">
        {% for post in posts %}
        <li class="{{ cls_list_item | default('widget-list-item') }}">
            {% if show_thumbnail and post.featured_image %}
                <img src="{% base_url post.featured_image %}" alt="{{ post.title }}" class="{{ cls_thumbnail | default('widget-thumbnail') }}">
            {% endif %}
            <a href="{% post_url post.slug %}" class="{{ cls_link | default('widget-list-link') }}">{{ post.title }}</a>
            <span class="{{ cls_meta | default('widget-meta') }}">{{ post.published_at | date('M j, Y') }}</span>
        </li>
        {% endfor %}
    </ul>
</div>
{% endif %}
