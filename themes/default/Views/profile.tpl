{# Public profile view. Extends the master layout. #}
{% extends 'layout' %}

{% block content %}
<div class="row">
    <div class="col-lg-8">
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
            {% if profile.website or profile.twitter or profile.facebook or profile.linkedin %}
            <div class="pv-profile-section">
                <h5 class="pv-profile-section-title">Links</h5>
                <ul class="pv-profile-links">
                    {# One conditional per network: each renders only when the field is filled. #}
                    {# rel="nofollow noopener" keeps these user-entered links from passing ranking or window access. #}
                    {% if profile.website %}
                    <li><a href="{{ profile.website }}" rel="nofollow noopener">{{ profile.website }}</a></li>
                    {% endif %}
                    {% if profile.twitter %}
                    <li><a href="https://twitter.com/{{ profile.twitter }}" rel="nofollow noopener">@{{ profile.twitter }}</a></li>
                    {% endif %}
                    {% if profile.facebook %}
                    <li><a href="https://facebook.com/{{ profile.facebook }}" rel="nofollow noopener">{{ profile.facebook }}</a></li>
                    {% endif %}
                    {% if profile.linkedin %}
                    <li><a href="https://linkedin.com/in/{{ profile.linkedin }}" rel="nofollow noopener">{{ profile.linkedin }}</a></li>
                    {% endif %}
                </ul>
            </div>
            {% endif %}

            {# Conditional: the edit link renders only for the profile's owner. #}
            {% if isOwner %}
            <a href="/profile/{{ user.username }}/edit" class="pv-profile-btn">Edit Profile</a>
            {% endif %}
        </div>
    </div>
    <div class="col-lg-4">
        {# Region: sidebar content blocks. #}
        {% region 'sidebar' %}
    </div>
</div>
{% endblock %}
