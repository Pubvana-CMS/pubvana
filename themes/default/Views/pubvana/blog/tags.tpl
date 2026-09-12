{# Tag index: all tags as badges. Content-only; the layout owns the sidebar. #}
<h1 class="mb-4">Tags</h1>

{# Conditional + loop: one badge per tag. #}
{% if tags %}
<div class="d-flex flex-wrap gap-2">
    {% for tag in tags %}
    <a href="{{ tag.url }}" class="badge bg-secondary text-decoration-none fs-6 py-2 px-3">{{ tag.name }}</a>
    {% endfor %}
</div>
{% else %}
{# Else branch: no tags exist yet. #}
<p>No tags found.</p>
{% endif %}

{# Region: content blocks placed below the tag list, inside the content column. #}
{% region 'after-content' %}
