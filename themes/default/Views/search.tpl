{# Search results page, rendered by the Search plugin. Extends the master layout. #}
{% extends 'layout' %}

{% block content %}
<div class="row">
    <div class="col-lg-8">
        <h1 class="h2 mb-3">Search</h1>

        {# Plain HTML form: GET /search, the query travels in the q parameter. #}
        <form action="/search" method="get" class="mb-4">
            <div class="input-group">
                <input type="search" name="q" class="form-control" value="{{ query }}" placeholder="Search..." aria-label="Search">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>

        {# Branch order matters: error first, then results, then an empty result set. #}
        {% if error %}
        {# Escaped output: the error message from the Search plugin. #}
        <div class="alert alert-warning">{{ error }}</div>

        {% elseif query and results %}
        {# Results found. The count line pluralizes with an inline conditional. #}
        <p class="text-muted mb-3">
            {{ total }} result{% if total != 1 %}s{% endif %} for &ldquo;{{ query }}&rdquo;
        </p>

        {# Loop: one card per result. #}
        {% for result in results %}
        <article class="card mb-3">
            <div class="card-body">
                <h2 class="h5 mb-1">
                    {# Raw output: the Search plugin returns titles with highlight markup. #}
                    <a href="{{ result.url }}" class="text-decoration-none">{! result.title !}</a>
                </h2>
                <div class="text-muted small mb-2">
                    {# Escaped output: content type badge and publish date. #}
                    <span class="badge bg-secondary">{{ result.content_type }}</span>
                    {% if result.published_at %}
                    &middot; {{ result.published_at | date('F j, Y') }}
                    {% endif %}
                </div>
                {# Raw output: excerpts carry the same highlight markup. #}
                {% if result.excerpt %}
                <p class="mb-0">{! result.excerpt !}</p>
                {% endif %}
            </div>
        </article>
        {% endfor %}

        {# Pagination is rendered inline here rather than through the partial, #}
        {# because search pagination uses prev/next URLs instead of a page list. #}
        {% if pagination %}
        <nav aria-label="Search results pages">
            <ul class="pagination justify-content-center">
                {# Conditional: previous link, or a disabled placeholder. #}
                {% if pagination.prev %}
                <li class="page-item"><a class="page-link" href="{{ pagination.prev }}">Previous</a></li>
                {% else %}
                <li class="page-item disabled"><span class="page-link">Previous</span></li>
                {% endif %}

                {# Escaped output: current page position. #}
                <li class="page-item active"><span class="page-link">Page {{ pagination.current }} of {{ pagination.total }}</span></li>

                {# Conditional: next link, or a disabled placeholder. #}
                {% if pagination.next %}
                <li class="page-item"><a class="page-link" href="{{ pagination.next }}">Next</a></li>
                {% else %}
                <li class="page-item disabled"><span class="page-link">Next</span></li>
                {% endif %}
            </ul>
        </nav>
        {% endif %}

        {% elseif query %}
        {# A query was submitted but nothing matched. #}
        <p class="text-muted">
            No results found for &ldquo;{{ query }}&rdquo;.
        </p>
        {% endif %}

        {# First visit, no query yet: show the hint. `not` flips the truthiness of the empty query. #}
        {% if not query %}
        <p class="text-muted">
            Search the site by entering a term above. Results are pulled from the content sources enabled in the admin.
        </p>
        {% endif %}
    </div>
    <div class="col-lg-4">
        {# Region: sidebar content blocks. #}
        {% region 'sidebar' %}
    </div>
</div>
{% endblock %}
