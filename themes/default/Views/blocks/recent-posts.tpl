{# Content block template: Recent Posts, the theme override for pubvana/blog/public/blocks/recent-posts. #}
{# Content block templates render through RegionManager, not the page inheritance chain. #}
{# The posts variable comes from the Blog plugin's block provider. #}
<div class="card mb-3">
    <div class="card-header">
        {# Static heading: this block has no title option. #}
        <h3 class="card-title h5 mb-0">Recent Posts</h3>
    </div>
    <div class="list-group list-group-flush">
        {# Conditional + loop: one link per recent post. #}
        {% if posts %}
        {% for post in posts %}
        <a href="/blog/{{ post.slug }}" class="list-group-item list-group-item-action">
            {# Escaped output: post title and publish date. #}
            <div class="fw-bold">{{ post.title }}</div>
            <small class="text-secondary">{{ post.created_at }}</small>
        </a>
        {% endfor %}
        {% else %}
        {# Else branch: shown while the site has no posts. #}
        <div class="list-group-item text-secondary">No posts yet.</div>
        {% endif %}
    </div>
</div>
