<div class="{{ cls_widget | default('widget widget-recent-posts') }}">
    {% if title %}
        <h4 class="{{ cls_title | default('widget-title') }}">{{ title }}</h4>
    {% endif %}
    {% if posts %}
    <ul class="{{ cls_list | default('widget-list') }}">
        {% for post in posts %}
        <li class="{{ cls_list_item | default('widget-list-item') }}">
            <a href="{% post_url post.slug %}" class="{{ cls_link | default('widget-list-link') }}">{{ post.title }}</a>
            {% if show_date and post.published_at %}
                <span class="{{ cls_meta | default('widget-meta') }}">{{ post.published_at | date('M j, Y') }}</span>
            {% endif %}
            {% if show_excerpt and post.excerpt %}
                <p class="{{ cls_meta | default('widget-meta') }}">{{ post.excerpt | excerpt(80) }}</p>
            {% endif %}
        </li>
        {% endfor %}
    </ul>
    {% else %}
        <p class="{{ cls_empty | default('widget-empty') }}">{% lang 'Blog.noPostsYet' %}</p>
    {% endif %}
</div>
