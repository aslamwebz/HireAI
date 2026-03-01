<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $resume->getFullName() }} - Resume</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11pt; line-height: 1.6; color: #333; }
        .page { width: 210mm; min-height: 297mm; padding: 30mm 25mm; margin: 0 auto; background: #fff; }
        .header { margin-bottom: 30px; }
        .name { font-size: 28pt; font-weight: 300; letter-spacing: 3px; text-transform: uppercase; color: #000; margin-bottom: 10px; }
        .contact-line { font-size: 10pt; color: #666; letter-spacing: 0.5px; }
        .contact-line span { margin-right: 20px; }
        .section { margin-bottom: 30px; }
        .section-title { font-size: 10pt; font-weight: 500; text-transform: uppercase; letter-spacing: 3px; color: #000; margin-bottom: 15px; }
        .summary { color: #555; font-size: 11pt; }
        .experience-item, .education-item, .project-item { margin-bottom: 20px; }
        .item-row { display: flex; justify-content: space-between; align-items: baseline; margin-bottom: 3px; }
        .item-title { font-weight: 500; font-size: 12pt; }
        .item-subtitle { color: #666; font-size: 11pt; }
        .item-date { font-size: 10pt; color: #999; }
        .highlights { margin-top: 8px; padding-left: 18px; }
        .highlights li { margin-bottom: 4px; color: #555; font-size: 10pt; }
        .skills-list { display: flex; flex-wrap: wrap; gap: 6px 20px; }
        .skill-item { font-size: 11pt; color: #333; }
        .skill-item::before { content: ''; display: inline-block; width: 4px; height: 4px; background: #000; border-radius: 50%; margin-right: 8px; vertical-align: middle; }
        .social-links { font-size: 10pt; color: #999; }
        .social-links a { color: #666; text-decoration: none; }
        .cert-row { display: flex; justify-content: space-between; margin-bottom: 8px; }
        .cert-name { font-size: 11pt; }
        .cert-issuer { color: #666; font-size: 10pt; }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <div class="name">{{ $content['personal']['first_name'] ?? '' }} {{ $content['personal']['last_name'] ?? '' }}</div>
            <div class="contact-line">
                @if(!empty($content['personal']['email']))<span>{{ $content['personal']['email'] }}</span>@endif
                @if(!empty($content['personal']['phone']))<span>{{ $content['personal']['phone'] }}</span>@endif
                @if(!empty($content['personal']['city']) || !empty($content['personal']['country']))
                    <span>{{ trim(($content['personal']['city'] ?? '').', '.($content['personal']['country'] ?? ''), ', ') }}</span>
                @endif
            </div>
            <div class="social-links">
                @if(!empty($content['social_links']['linkedin']))<a href="{{ $content['social_links']['linkedin'] }}">LinkedIn</a> · @endif
                @if(!empty($content['social_links']['github']))<a href="{{ $content['social_links']['github'] }}">GitHub</a> · @endif
                @if(!empty($content['social_links']['portfolio']))<a href="{{ $content['social_links']['portfolio'] }}">Portfolio</a>@endif
            </div>
        </div>

        @if(!empty($content['professional_summary']))
        <div class="section">
            <div class="section-title">Summary</div>
            <div class="summary">{{ $content['professional_summary'] }}</div>
        </div>
        @endif

        @if(!empty($content['work_experience']))
        <div class="section">
            <div class="section-title">Experience</div>
            @foreach($content['work_experience'] as $exp)
            <div class="experience-item">
                <div class="item-row">
                    <div>
                        <div class="item-title">{{ $exp['position'] ?? '' }}</div>
                        <div class="item-subtitle">{{ $exp['company'] ?? '' }}</div>
                    </div>
                    <div class="item-date">{{ $exp['start_date'] ?? '' }} — {{ ($exp['current'] ?? false) ? 'Present' : ($exp['end_date'] ?? '') }}</div>
                </div>
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
                <div class="item-row">
                    <div>
                        <div class="item-title">{{ $edu['degree'] ?? '' }}@if(!empty($edu['field_of_study'])) — {{ $edu['field_of_study'] }}@endif</div>
                        <div class="item-subtitle">{{ $edu['institution'] ?? '' }}</div>
                    </div>
                    <div class="item-date">{{ $edu['end_date'] ?? '' }}</div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($content['skills']))
        <div class="section">
            <div class="section-title">Skills</div>
            <div class="skills-list">
                @foreach($content['skills'] as $skill)
                <span class="skill-item">{{ $skill['name'] ?? $skill }}</span>
                @endforeach
            </div>
        </div>
        @endif

        @if(!empty($content['certifications']))
        <div class="section">
            <div class="section-title">Certifications</div>
            @foreach($content['certifications'] as $cert)
            <div class="cert-row">
                <span class="cert-name">{{ $cert['name'] ?? '' }}</span>
                <span class="cert-issuer">{{ $cert['issuer'] ?? '' }}</span>
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
                @if(!empty($project['description']))<div class="item-subtitle" style="margin-top: 3px;">{{ $project['description'] }}</div>@endif
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($content['languages']))
        <div class="section">
            <div class="section-title">Languages</div>
            <div class="skills-list">
                @foreach($content['languages'] as $lang)
                <span class="skill-item">{{ $lang['name'] ?? $lang }}@if(!empty($lang['proficiency'])) ({{ $lang['proficiency'] }})@endif</span>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</body>
</html>
