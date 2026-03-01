<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $resume->getFullName() }} - Resume</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11pt; line-height: 1.5; color: #333; }
        .page { width: 210mm; min-height: 297mm; padding: 20mm; margin: 0 auto; background: #fff; }
        .header { text-align: center; border-bottom: 2px solid #333; padding-bottom: 15px; margin-bottom: 20px; }
        .name { font-size: 28pt; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 8px; }
        .contact-info { font-size: 10pt; color: #555; }
        .contact-info span { margin: 0 10px; }
        .section { margin-bottom: 20px; }
        .section-title { font-size: 12pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; border-bottom: 1px solid #333; padding-bottom: 5px; margin-bottom: 12px; }
        .summary { text-align: justify; }
        .experience-item, .education-item, .project-item { margin-bottom: 15px; }
        .item-header { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 5px; }
        .item-title { font-weight: bold; font-size: 11pt; }
        .item-subtitle { font-style: italic; color: #555; }
        .item-date { font-size: 10pt; color: #666; }
        .item-location { font-size: 10pt; color: #666; }
        .highlights { margin-top: 5px; padding-left: 20px; }
        .highlights li { margin-bottom: 3px; }
        .skills-grid { display: flex; flex-wrap: wrap; gap: 8px; }
        .skill-tag { background: #f0f0f0; padding: 4px 10px; border-radius: 3px; font-size: 10pt; }
        .social-links { font-size: 10pt; margin-top: 5px; }
        .social-links a { color: #555; text-decoration: none; }
        .two-column { display: flex; gap: 20px; }
        .two-column .left { flex: 1; }
        .two-column .right { flex: 1; }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <div class="name">{{ $content['personal']['first_name'] ?? '' }} {{ $content['personal']['last_name'] ?? '' }}</div>
            <div class="contact-info">
                @if(!empty($content['personal']['email']))<span>{{ $content['personal']['email'] }}</span>@endif
                @if(!empty($content['personal']['phone']))<span>{{ $content['personal']['phone'] }}</span>@endif
                @if(!empty($content['personal']['city']) || !empty($content['personal']['country']))
                    <span>{{ trim(($content['personal']['city'] ?? '').', '.($content['personal']['country'] ?? ''), ', ') }}</span>
                @endif
            </div>
            <div class="social-links">
                @if(!empty($content['social_links']['linkedin']))<a href="{{ $content['social_links']['linkedin'] }}">LinkedIn</a> @endif
                @if(!empty($content['social_links']['github']))<a href="{{ $content['social_links']['github'] }}">GitHub</a> @endif
                @if(!empty($content['social_links']['portfolio']))<a href="{{ $content['social_links']['portfolio'] }}">Portfolio</a> @endif
            </div>
        </div>

        @if(!empty($content['professional_summary']))
        <div class="section">
            <div class="section-title">Professional Summary</div>
            <div class="summary">{{ $content['professional_summary'] }}</div>
        </div>
        @endif

        @if(!empty($content['work_experience']))
        <div class="section">
            <div class="section-title">Professional Experience</div>
            @foreach($content['work_experience'] as $exp)
            <div class="experience-item">
                <div class="item-header">
                    <div>
                        <div class="item-title">{{ $exp['position'] ?? '' }}</div>
                        <div class="item-subtitle">{{ $exp['company'] ?? '' }}@if(!empty($exp['location'])) - {{ $exp['location'] }}@endif</div>
                    </div>
                    <div class="item-date">{{ $exp['start_date'] ?? '' }} - {{ ($exp['current'] ?? false) ? 'Present' : ($exp['end_date'] ?? '') }}</div>
                </div>
                @if(!empty($exp['description']))
                <div>{{ $exp['description'] }}</div>
                @endif
                @if(!empty($exp['highlights']))
                <ul class="highlights">
                    @foreach($exp['highlights'] as $highlight)
                    <li>{{ $highlight }}</li>
                    @endforeach
                </ul>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($content['education']))
        <div class="section">
            <div class="section-title">Education</div>
            @foreach($content['education'] as $edu)
            <div class="education-item">
                <div class="item-header">
                    <div>
                        <div class="item-title">{{ $edu['degree'] ?? '' }}@if(!empty($edu['field_of_study'])) in {{ $edu['field_of_study'] }}@endif</div>
                        <div class="item-subtitle">{{ $edu['institution'] ?? '' }}</div>
                    </div>
                    <div class="item-date">{{ $edu['end_date'] ?? '' }}</div>
                </div>
                @if(!empty($edu['gpa']))
                <div>GPA: {{ $edu['gpa'] }}</div>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($content['skills']))
        <div class="section">
            <div class="section-title">Skills</div>
            <div class="skills-grid">
                @foreach($content['skills'] as $skill)
                <span class="skill-tag">{{ $skill['name'] ?? $skill }}</span>
                @endforeach
            </div>
        </div>
        @endif

        @if(!empty($content['certifications']))
        <div class="section">
            <div class="section-title">Certifications</div>
            @foreach($content['certifications'] as $cert)
            <div class="experience-item">
                <div class="item-header">
                    <div class="item-title">{{ $cert['name'] ?? '' }}</div>
                    @if(!empty($cert['date']))<div class="item-date">{{ $cert['date'] }}</div>@endif
                </div>
                @if(!empty($cert['issuer']))<div class="item-subtitle">{{ $cert['issuer'] }}</div>@endif
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($content['projects']))
        <div class="section">
            <div class="section-title">Projects</div>
            @foreach($content['projects'] as $project)
            <div class="project-item">
                <div class="item-title">{{ $project['name'] ?? '' }}</div>
                @if(!empty($project['description']))<div>{{ $project['description'] }}</div>@endif
                @if(!empty($project['technologies']))
                <div class="skills-grid" style="margin-top: 5px;">
                    @foreach($project['technologies'] as $tech)
                    <span class="skill-tag">{{ $tech }}</span>
                    @endforeach
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($content['languages']))
        <div class="section">
            <div class="section-title">Languages</div>
            <div class="skills-grid">
                @foreach($content['languages'] as $lang)
                <span class="skill-tag">{{ $lang['name'] ?? $lang }}@if(!empty($lang['proficiency'])) - {{ $lang['proficiency'] }}@endif</span>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</body>
</html>
