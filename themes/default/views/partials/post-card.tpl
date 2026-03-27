<article class="card mb-4 shadow-sm border-0">
    {% if post.featured_image %}
    <a href="{% post_url post.slug %}">
        <img src="{% base_url post.featured_image %}" class="card-img-top" alt="{{ post.title }}" style="height:220px;object-fit:cover;">
    </a>
    {% endif %}
    <div class="card-body">
        <h2 class="card-title h5">
            <a href="{% post_url post.slug %}" class="text-decoration-none text-dark">{{ post.title }}</a>
        </h2>
        <p class="card-text text-muted small mb-2">
            <i class="fas fa-calendar-days"></i>
            {{ post.published_at | default(post.created_at) | date('F j, Y') }}
            {% if post.views %}
                &nbsp;&middot;&nbsp;<i class="fas fa-eye"></i> {{ post.views | number_format }}
            {% endif %}
        </p>
        {% if post.excerpt %}
            <p class="card-text text-muted">{{ post.excerpt | excerpt(150) }}</p>
        {% endif %}
        <a href="{% post_url post.slug %}" class="btn btn-sm btn-outline-primary">{% lang 'Blog.readMore' %}</a>
    </div>
</article>
