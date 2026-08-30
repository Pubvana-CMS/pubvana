{% extends 'layout' %}

{% block content %}
<div class="row">
    <div class="col-lg-8">
        <div class="pv-profile-card">
            <div class="pv-profile-header">
                {% if avatar_url %}
                <img src="{{ avatar_url }}" class="pv-profile-avatar" alt="{{ user.username }}" width="80" height="80">
                {% endif %}
                <div>
                    <h1 class="pv-profile-name">{{ profile.display_name | default(user.username) }}</h1>
                    <p class="pv-profile-username">@{{ user.username }}</p>
                </div>
            </div>

            {% if profile.bio %}
            <div class="pv-profile-section">
                <h5 class="pv-profile-section-title">Bio</h5>
                <p class="pv-profile-bio">{{ profile.bio }}</p>
            </div>
            {% endif %}

            {% if profile.website or profile.twitter or profile.facebook or profile.linkedin %}
            <div class="pv-profile-section">
                <h5 class="pv-profile-section-title">Links</h5>
                <ul class="pv-profile-links">
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

            {% if isOwner %}
            <a href="/profile/{{ user.username }}/edit" class="pv-profile-btn">Edit Profile</a>
            {% endif %}
        </div>
    </div>
    <div class="col-lg-4">
        {! theme_regions.sidebar !}
    </div>
</div>
{% endblock %}
