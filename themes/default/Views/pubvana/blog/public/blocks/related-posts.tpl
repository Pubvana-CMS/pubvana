{# Theme override for the Blog Related Posts block (pubvana.blog.related-posts).
   Resolved by RegionManager::resolveBlockTemplate() as
   themes/{active}/Views/pubvana/blog/public/blocks/related-posts.tpl. #}
<div class="pv-block pv-block-related-posts">
    {% if title %}<h3 class="pv-block-title">{{ title }}</h3>{% endif %}
    {% if posts %}
    <ul class="list-unstyled pv-related-list">
        {% for post in posts %}
        <li class="pv-related-item">
            <a href="{{ post.url }}">{{ post.title }}</a>
            {% if post.excerpt %}<p class="text-muted small mb-0">{{ post.excerpt }}</p>{% endif %}
        </li>
        {% endfor %}
    </ul>
    {% else %}
    <p class="text-muted">No related posts.</p>
    {% endif %}
</div>
