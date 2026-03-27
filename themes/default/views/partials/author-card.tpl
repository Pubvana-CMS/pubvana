{% if author_profile %}
{% if author_profile.bio or author_profile.avatar or author_profile.twitter or author_profile.facebook or author_profile.linkedin %}
<p class="text-uppercase text-muted small mb-1 fw-semibold">{% lang 'Blog.authorCardLabel' %}</p>
<div class="card border-0 bg-light rounded-3 p-4 my-5 d-flex flex-row align-items-start">
    {% if author_profile.avatar %}
    <img src="{% base_url author_profile.avatar %}"
         alt="{{ author_profile.display_name | default(author_profile.username) | default('') }}"
         class="rounded-circle me-4 flex-shrink-0"
         width="80" height="80"
         style="object-fit:cover">
    {% else %}
    <img src="https://www.gravatar.com/avatar/{{ author_profile.email | strtolower | md5 }}?s=80&d=mp"
         alt="{{ author_profile.display_name | default(author_profile.username) | default('') }}"
         class="rounded-circle me-4 flex-shrink-0"
         width="80" height="80"
         style="object-fit:cover">
    {% endif %}
    <div>
        <div class="d-flex align-items-center mb-1">
            <h6 class="fw-bold mb-0 me-2">{{ author_profile.display_name | default(author_profile.username) | default('') }}</h6>
            {% if author_profile.website %}
                <a href="{{ author_profile.website }}" class="text-muted small me-2" target="_blank" rel="noopener">
                    <i class="fas fa-globe"></i>
                </a>
            {% endif %}
            {% if author_profile.twitter %}
                <a href="https://twitter.com/{{ author_profile.twitter }}" class="text-info small me-2" target="_blank" rel="noopener">
                    <i class="fab fa-twitter"></i>
                </a>
            {% endif %}
            {% if author_profile.facebook %}
                <a href="{{ author_profile.facebook }}" class="text-primary small me-2" target="_blank" rel="noopener">
                    <i class="fab fa-facebook"></i>
                </a>
            {% endif %}
            {% if author_profile.linkedin %}
                <a href="{{ author_profile.linkedin }}" class="text-primary small" target="_blank" rel="noopener">
                    <i class="fab fa-linkedin"></i>
                </a>
            {% endif %}
        </div>
        {% if author_profile.bio %}
            <p class="text-muted small mb-0">{{ author_profile.bio | nl2br | raw }}</p>
        {% endif %}
    </div>
</div>
{% endif %}
{% endif %}
