{# Content block template override: Recent Posts (pubvana/blog/public/blocks/recent-posts). #}
{# Content block templates render through RegionManager, not the page inheritance chain. #}
{# The posts variable comes from the Blog plugin's block provider. #}
<div class="card mb-4">
    {# Conditional: the block title comes from the placement options in the admin. #}
    {% if title %}
    <div class="card-header">
        <h3 class="card-title h5 mb-0">{{ title }}</h3>
    </div>
    {% endif %}
    <div class="list-group list-group-flush">
        {# Conditional + loop: one link per recent post. #}
        {% if posts %}
        {% for post in posts %}
        <a href="{{ post.url }}" class="list-group-item list-group-item-action">
            <div class="fw-bold">{{ post.title }}</div>
            <small class="text-secondary">{{ post.published_at | date('F j, Y') }}</small>
        </a>
        {% endfor %}
        {% else %}
        {# Else branch: shown while the site has no posts. #}
        <div class="list-group-item text-secondary">No posts yet.</div>
        {% endif %}
    </div>
</div>