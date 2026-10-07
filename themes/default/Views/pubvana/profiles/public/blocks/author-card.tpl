{# Theme override for the Profiles Author Card block (pubvana.profiles.author-card).
   Resolved by RegionManager::resolveBlockTemplate() as
   themes/{active}/Views/pubvana/profiles/public/blocks/author-card.tpl.
   The whole block is guarded on `author`, so a placement with no author (a
   profile page, a list view) renders nothing, heading included. #}
{% if author %}
<div class="pv-block pv-block-author-card">
    {% if title %}<h3 class="pv-block-title">{{ title }}</h3>{% endif %}
    <div class="card">
        <div class="card-body d-flex align-items-center gap-3">
            {% if show_avatar and author.avatar_url %}
            <img src="{{ author.avatar_url }}" alt="{{ author.name }}" class="rounded-circle" width="64" height="64">
            {% endif %}
            <div>
                <div class="fw-bold">{{ author.name }}</div>
                {% if author.job_title %}<div class="text-muted small">{{ author.job_title }}</div>{% endif %}
                {% if author.works_for %}<div class="text-muted small">{{ author.works_for }}</div>{% endif %}
                {% if author.bio %}<div class="text-muted small">{{ author.bio }}</div>{% endif %}
                {% if show_socials and (author.safe_website or author.twitter_url or author.facebook_url or author.linkedin_url) %}
                <div class="small">
                    {% if author.safe_website %}<a href="{{ author.safe_website }}" rel="nofollow noopener" class="me-2">Website</a>{% endif %}
                    {% if author.twitter_url %}<a href="{{ author.twitter_url }}" rel="nofollow noopener" class="me-2">Twitter</a>{% endif %}
                    {% if author.facebook_url %}<a href="{{ author.facebook_url }}" rel="nofollow noopener" class="me-2">Facebook</a>{% endif %}
                    {% if author.linkedin_url %}<a href="{{ author.linkedin_url }}" rel="nofollow noopener" class="me-2">LinkedIn</a>{% endif %}
                </div>
                {% endif %}
                {% if author.url %}<a class="small" href="{{ author.url }}">View profile</a>{% endif %}
            </div>
        </div>
    </div>
</div>
{% endif %}
