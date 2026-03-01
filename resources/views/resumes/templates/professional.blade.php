<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $resume->getFullName() }} - Resume</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Georgia, 'Times New Roman', Times, serif; font-size: 11pt; line-height: 1.5; color: #333; }
        .page { width: 210mm; min-height: 297mm; padding: 25mm 30mm; margin: 0 auto; background: #fff; }
        .header { text-align: center; border-bottom: 2px solid #1a365d; padding-bottom: 20px; margin-bottom: 25px; }
        .name { font-size: 26pt; font-weight: normal; letter-spacing: 4px; text-transform: uppercase; color: #1a365d; margin-bottom: 10px; }
        .contact-line { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 10pt; color: #4a5568; }
        .contact-line span { margin: 0 12px; }
        .social-line { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 9pt; color: #718096; margin-top: 8px; }
        .social-line a { color: #2b6cb0; text-decoration: none; }
        .section { margin-bottom: 22px; }
        .section-title { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11pt; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; color: #1a365d; border-bottom: 1px solid #cbd5e0; padding-bottom: 6px; margin-bottom: 15px; }
        .summary { font-style: italic; color: #4a5568; text-align: center; padding: 0 20px; }
        .experience-item, .education-item, .project-item { margin-bottom: 18px; }
        .item-row { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 4px; }
        .item-left { flex: 1; }
        .item-title { font-weight: bold; font-size: 12pt; color: #1a365d; }
        .item-subtitle { color: #2d3748; font-style: italic; font-size: 11pt; margin-top: 2px; }
        .item-right { text-align: right; }
        .item-date { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 10pt; color: #718096; }
        .item-location { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 9pt; color: #a0aec0; }
        .highlights { margin-top: 10px; padding-left: 20px; }
        .highlights li { margin-bottom: 5px; color: #4a5568; }
        .skills-grid { display: flex; flex-wrap: wrap; gap: 8px 15px; }
        .skill-item { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 10pt; color: #2d3748; background: #edf2f7; padding: 4px 12px; border-radius: 3px; }
        .cert-item { margin-bottom: 10px; }
        .cert-name { font-weight: bold; color: #1a365d; }
        .cert-meta { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 10pt; color: #718096; }
        .lang-row { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 10pt; color: #4a5568; }
        .lang-row span { margin-right: 20px; }
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
            <div class="social-line">
                @if(!empty($content['social_links']['linkedin']))<a href="{{ $content['social_links']['linkedin'] }}">LinkedIn</a>@endif
                @if(!empty($content['social_links']['github'])) · <a href="{{ $content['social_links']['github'] }}">GitHub</a>@endif
                @if(!empty($content['social_links']['portfolio'])) · <a href="{{ $content['social_links']['portfolio'] }}">Portfolio</a>@endif
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
                <div class="item-row">
                    <div class="item-left">
                        <div class="item-title">{{ $exp['position'] ?? '' }}</div>
                        <div class="item-subtitle">{{ $exp['company'] ?? '' }}</div>
                    </div>
                    <div class="item-right">
                        <div class="item-date">{{ $exp['start_date'] ?? '' }} — {{ ($exp['current'] ?? false) ? 'Present' : ($exp['end_date'] ?? '') }}</div>
                        @if(!empty($exp['location']))<div class="item-location">{{ $exp['location'] }}</div>@endif
                    </div>
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
                    <div class="item-left">
                        <div class="item-title">{{ $edu['degree'] ?? '' }}@if(!empty($edu['field_of_study'])) in {{ $edu['field_of_study'] }}@endif</div>
                        <div class="item-subtitle">{{ $edu['institution'] ?? '' }}</div>
                    </div>
                    <div class="item-right">
                        <div class="item-date">{{ $edu['end_date'] ?? '' }}</div>
                        @if(!empty($edu['gpa']))<div class="item-location">GPA: {{ $edu['gpa'] }}</div>@endif
                    </div>
                </div>
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($content['skills']))
        <div class="section">
            <div class="section-title">Core Competencies</div>
            <div class="skills-grid">
                @foreach($content['skills'] as $skill)
                <span class="skill-item">{{ $skill['name'] ?? $skill }}</span>
                @endforeach
            </div>
        </div>
        @endif

        @if(!empty($content['certifications']))
        <div class="section">
            <div class="section-title">Professional Certifications</div>
            @foreach($content['certifications'] as $cert)
            <div class="cert-item">
                <div class="cert-name">{{ $cert['name'] ?? '' }}</div>
                <div class="cert-meta">{{ $cert['issuer'] ?? '' }}@if(!empty($cert['date'])) • {{ $cert['date'] }}@endif</div>
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($content['projects']))
        <div class="section">
            <div class="section-title">Notable Projects</div>
            @foreach($content['projects'] as $project)
            <div class="project-item">
                <div class="item-title">{{ $project['name'] ?? '' }}</div>
                @if(!empty($project['description']))<div class="item-subtitle" style="margin-top: 4px;">{{ $project['description'] }}</div>@endif
            </div>
            @endforeach
        </div>
        @endif

        @if(!empty($content['languages']))
        <div class="section">
            <div class="section-title">Languages</div>
            <div class="lang-row">
                @foreach($content['languages'] as $lang)
                <span>{{ $lang['name'] ?? $lang }}@if(!empty($lang['proficiency'])) ({{ $lang['proficiency'] }})@endif</span>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</body>
</html>
