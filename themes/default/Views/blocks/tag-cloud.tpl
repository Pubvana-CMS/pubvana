{# Content block template: Tag Cloud, the theme override for pubvana/blog/public/blocks/tags. #}
{# Content block templates render through RegionManager, not the page inheritance chain. #}
{# The tags variable comes from the Blog plugin's block provider. #}
<div class="card mb-3">
    <div class="card-header">
        {# Static heading: this block has no title option. #}
        <h3 class="card-title h5 mb-0">Tags</h3>
    </div>
    <div class="card-body">
        {# Conditional + loop: one badge per tag. #}
        {% if tags %}
        <div class="d-flex flex-wrap gap-2">
            {% for tag in tags %}
            <a href="/blog/tag/{{ tag.slug }}" class="badge bg-blue-lt text-blue">{{ tag.name }}</a>
            {% endfor %}
        </div>
        {% else %}
        {# Else branch: shown while the site has no tags. #}
        <p class="text-secondary mb-0">No tags yet.</p>
        {% endif %}
    </div>
</div>
