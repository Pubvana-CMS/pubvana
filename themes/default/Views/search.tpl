{% extends 'layout' %}

{% block content %}
<div class="row">
    <div class="col-lg-8">
        <h1 class="h2 mb-3">Search</h1>

        <form action="/search" method="get" class="mb-4">
            <div class="input-group">
                <input type="search" name="q" class="form-control" value="{{ query }}" placeholder="Search..." aria-label="Search">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>

        {% if error %}
        <div class="alert alert-warning">{{ error }}</div>

        {% elseif query and results %}
        <p class="text-muted mb-3">
            {{ total }} result{% if total != 1 %}s{% endif %} for &ldquo;{{ query }}&rdquo;
        </p>

        {% for result in results %}
        <article class="card mb-3">
            <div class="card-body">
                <h2 class="h5 mb-1">
                    <a href="{{ result.url }}" class="text-decoration-none">{! result.title !}</a>
                </h2>
                <div class="text-muted small mb-2">
                    <span class="badge bg-secondary">{{ result.content_type }}</span>
                    {% if result.published_at %}
                    &middot; {{ result.published_at | date('F j, Y') }}
                    {% endif %}
                </div>
                {% if result.excerpt %}
                <p class="mb-0">{! result.excerpt !}</p>
                {% endif %}
            </div>
        </article>
        {% endfor %}

        {% if pagination %}
        <nav aria-label="Search results pages">
            <ul class="pagination justify-content-center">
                {% if pagination.prev %}
                <li class="page-item"><a class="page-link" href="{{ pagination.prev }}">Previous</a></li>
                {% else %}
                <li class="page-item disabled"><span class="page-link">Previous</span></li>
                {% endif %}

                <li class="page-item active"><span class="page-link">Page {{ pagination.current }} of {{ pagination.total }}</span></li>

                {% if pagination.next %}
                <li class="page-item"><a class="page-link" href="{{ pagination.next }}">Next</a></li>
                {% else %}
                <li class="page-item disabled"><span class="page-link">Next</span></li>
                {% endif %}
            </ul>
        </nav>
        {% endif %}

        {% elseif query %}
        <p class="text-muted">
            No results found for &ldquo;{{ query }}&rdquo;.
        </p>
        {% endif %}

        {% if not query %}
        <p class="text-muted">
            Search the site by entering a term above. Results are pulled from the content sources enabled in the admin.
        </p>
        {% endif %}
    </div>
    <div class="col-lg-4">
        {! theme_regions.sidebar !}
    </div>
</div>
{% endblock %}
