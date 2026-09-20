{# Author Card block. Renders nothing when the provider found no author
   (non-post/page URI, toggle off, or content without an author). #}
{% if author %}
<div class="pv-profile-card pv-author-card">
    {# Conditional: the placement title renders only when one is set. #}
    {% if title %}
    <h5 class="pv-author-card-title">{{ title }}</h5>
    {% endif %}

    <div class="pv-author-card-body">
        {# Conditional: avatar renders only when enabled and uploaded (Media plugin). #}
        {% if show_avatar and author.avatar_url %}
        <img src="{{ author.avatar_url }}" class="pv-profile-avatar" alt="{{ author.name }}" width="80" height="80">
        {% endif %}

        <div>
            {# Escaped output: the author's display name (falls back to username in the provider). #}
            <p class="pv-profile-name">{{ author.name }}</p>
            {# Conditional: the profile link renders only when the user still exists. #}
            {% if author.url %}
            <p class="pv-profile-username"><a href="{{ author.url }}">@{{ author.username }}</a></p>
            {% endif %}

            {# Conditional: the bio renders only when one exists. #}
            {% if author.bio %}
            <p class="pv-profile-bio">{{ author.bio }}</p>
            {% endif %}

            {# Conditional: the links section renders only when enabled and at least one field is set. #}
            {% if show_socials and (author.safe_website or author.twitter_url or author.facebook_url or author.linkedin_url) %}
            <ul class="pv-profile-links">
                {# One conditional per network: each renders only when the field is filled. #}
                {# rel="nofollow noopener" keeps these user-entered links from passing ranking or window access. #}
                {# safe_website and the *_url fields are provider-side: only full http(s) URLs become a navigable href. #}
                {# Each link carries its own platform class so themes can target them individually. #}
                {% if author.safe_website %}
                <li class="pv-profile-link-item"><a href="{{ author.safe_website }}" class="pv-profile-link-website" rel="nofollow noopener">{{ author.safe_website }}</a></li>
                {% endif %}
                {% if author.twitter_url %}
                <li class="pv-profile-link-item"><a href="{{ author.twitter_url }}" class="pv-profile-link-twitter" rel="nofollow noopener">{{ author.twitter_url }}</a></li>
                {% endif %}
                {% if author.facebook_url %}
                <li class="pv-profile-link-item"><a href="{{ author.facebook_url }}" class="pv-profile-link-facebook" rel="nofollow noopener">{{ author.facebook_url }}</a></li>
                {% endif %}
                {% if author.linkedin_url %}
                <li class="pv-profile-link-item"><a href="{{ author.linkedin_url }}" class="pv-profile-link-linkedin" rel="nofollow noopener">{{ author.linkedin_url }}</a></li>
                {% endif %}
            </ul>
            {% endif %}
        </div>
    </div>
</div>
{% endif %}