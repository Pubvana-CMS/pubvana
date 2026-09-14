{# Public profile view. Content-only; the layout owns the sidebar. #}
<div class="pv-profile-card">
    <div class="pv-profile-header">
        {# Conditional: avatar renders only when the user uploaded one (Media plugin). #}
        {% if avatar_url %}
        <img src="{{ avatar_url }}" class="pv-profile-avatar" alt="{{ user.username }}" width="80" height="80">
        {% endif %}
        <div>
            {# Filter: default() falls back to the username when no display name is set. #}
            <h1 class="pv-profile-name">{{ profile.display_name | default(user.username) }}</h1>
            {# Escaped output: the handle. #}
            <p class="pv-profile-username">@{{ user.username }}</p>
        </div>
    </div>

    {# Conditional: the bio section renders only when a bio exists. #}
    {% if profile.bio %}
    <div class="pv-profile-section">
        <h5 class="pv-profile-section-title">Bio</h5>
        {# Escaped output: bio is plain text. #}
        <p class="pv-profile-bio">{{ profile.bio }}</p>
    </div>
    {% endif %}

    {# Conditional: the links section renders only when at least one social field is set. #}
    {% if safe_website or twitter_url or facebook_url or linkedin_url %}
    <div class="pv-profile-section">
        <h5 class="pv-profile-section-title">Links</h5>
        <ul class="pv-profile-links">
            {# One conditional per network: each renders only when the field is filled. #}
            {# rel="nofollow noopener" keeps these user-entered links from passing ranking or window access. #}
            {# safe_website and the *_url fields are controller-side: only full http(s) URLs become a navigable href. #}
            {# Each link carries its own platform class so themes can target them individually. #}
            {% if safe_website %}
            <li class="pv-profile-link-item"><a href="{{ safe_website }}" class="pv-profile-link-website" rel="nofollow noopener">{{ safe_website }}</a></li>
            {% endif %}
            {% if twitter_url %}
            <li class="pv-profile-link-item"><a href="{{ twitter_url }}" class="pv-profile-link-twitter" rel="nofollow noopener">{{ twitter_url }}</a></li>
            {% endif %}
            {% if facebook_url %}
            <li class="pv-profile-link-item"><a href="{{ facebook_url }}" class="pv-profile-link-facebook" rel="nofollow noopener">{{ facebook_url }}</a></li>
            {% endif %}
            {% if linkedin_url %}
            <li class="pv-profile-link-item"><a href="{{ linkedin_url }}" class="pv-profile-link-linkedin" rel="nofollow noopener">{{ linkedin_url }}</a></li>
            {% endif %}
        </ul>
    </div>
    {% endif %}

    {# Conditional: the edit link renders only for the profile's owner. #}
    {% if isOwner %}
    <a href="/profile/{{ user.username }}/edit" class="pv-profile-btn">Edit Profile</a>
    {% endif %}
</div>

{# Region: content blocks placed below the profile, inside the content column. #}
{% region 'after-content' %}
