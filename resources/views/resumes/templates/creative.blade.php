<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $resume->getFullName() }} - Resume</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11pt; line-height: 1.5; color: #333; }
        .page { width: 210mm; min-height: 297mm; margin: 0 auto; background: #fff; }
        .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 30px 40px; }
        .name { font-size: 32pt; font-weight: bold; margin-bottom: 5px; }
        .title-text { font-size: 14pt; opacity: 0.9; margin-bottom: 15px; }
        .contact-bar { display: flex; flex-wrap: wrap; gap: 20px; font-size: 10pt; }
        .contact-bar span { opacity: 0.9; }
        .content { padding: 30px 40px; }
        .section { margin-bottom: 25px; }
        .section-title { font-size: 14pt; font-weight: bold; color: #667eea; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 15px; padding-bottom: 8px; border-bottom: 3px solid #667eea; }
        .summary { text-align: justify; color: #555; font-size: 11pt; }
        .experience-item, .education-item, .project-item { margin-bottom: 20px; padding-left: 15px; border-left: 3px solid #764ba2; }
        .item-header { margin-bottom: 5px; }
        .item-title { font-weight: bold; font-size: 13pt; color: #333; }
        .item-subtitle { color: #764ba2; font-size: 12pt; }
        .item-meta { display: flex; justify-content: space-between; font-size: 10pt; color: #888; margin-top: 3px; }
        .highlights { margin-top: 8px; padding-left: 20px; }
        .highlights li { margin-bottom: 4px; color: #555; }
        .skills-container { display: flex; flex-wrap: wrap; gap: 10px; }
        .skill-pill { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: #fff; padding: 6px 14px; border-radius: 20px; font-size: 10pt; }
        .social-row { display: flex; flex-wrap: wrap; gap: 15px; margin-top: 10px; font-size: 10pt; }
        .social-row a { color: #fff; text-decoration: none; }
        .cert-item { margin-bottom: 12px; }
        .cert-name { font-weight: bold; color: #333; }
        .cert-meta { color: #888; font-size: 10pt; }
        .lang-item { display: inline-block; margin-right: 20px; margin-bottom: 8px; }
        .lang-name { font-weight: bold; }
        .lang-level { color: #888; font-size: 10pt; }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <div class="name">{{ $content['personal']['first_name'] ?? '' }} {{ $content['personal']['last_name'] ?? '' }}</div>
            @if(!empty($content['professional_summary']))
            <div class="title-text">{{ Str::limit($content['professional_summary'], 100) }}</div>
            @endif
            <div class="contact-bar">
                @if(!empty($content['personal']['email']))<span>{{ $content['personal']['email'] }}</span>@endif
                @if(!empty($content['personal']['phone']))<span>{{ $content['personal']['phone'] }}</span>@endif
                @if(!empty($content['personal']['city']) || !empty($content['personal']['country']))
                    <span>{{ trim(($content['personal']['city'] ?? '').', '.($content['personal']['country'] ?? ''), ', ') }}</span>
                @endif
            </div>
            <div class="social-row">
                @if(!empty($content['social_links']['linkedin']))<a href="{{ $content['social_links']['linkedin'] }}">LinkedIn</a>@endif
                @if(!empty($content['social_links']['github']))<a href="{{ $content['social_links']['github'] }}">GitHub</a>@endif
                @if(!empty($content['social_links']['portfolio']))<a href="{{ $content['social_links']['portfolio'] }}">Portfolio</a>@endif
            </div>
        </div>

        <div class="content">
            @if(!empty($content['professional_summary']))
            <div class="section">
                <div class="section-title">About Me</div>
                <div class="summary">{{ $content['professional_summary'] }}</div>
            </div>
            @endif

            @if(!empty($content['work_experience']))
            <div class="section">
                <div class="section-title">Experience</div>
                @foreach($content['work_experience'] as $exp)
                <div class="experience-item">
                    <div class="item-header">
                        <div class="item-title">{{ $exp['position'] ?? '' }}</div>
                        <div class="item-subtitle">{{ $exp['company'] ?? '' }}</div>
                    </div>
                    <div class="item-meta">
                        <span>{{ $exp['start_date'] ?? '' }} - {{ ($exp['current'] ?? false) ? 'Present' : ($exp['end_date'] ?? '') }}</span>
                        @if(!empty($exp['location']))<span>{{ $exp['location'] }}</span>@endif
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
                    <div class="item-header">
                        <div class="item-title">{{ $edu['degree'] ?? '' }}@if(!empty($edu['field_of_study'])) in {{ $edu['field_of_study'] }}@endif</div>
                        <div class="item-subtitle">{{ $edu['institution'] ?? '' }}</div>
                    </div>
                    <div class="item-meta">
                        <span>{{ $edu['end_date'] ?? '' }}</span>
                        @if(!empty($edu['gpa']))<span>GPA: {{ $edu['gpa'] }}</span>@endif
                    </div>
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($content['skills']))
            <div class="section">
                <div class="section-title">Skills</div>
                <div class="skills-container">
                    @foreach($content['skills'] as $skill)
                    <span class="skill-pill">{{ $skill['name'] ?? $skill }}</span>
                    @endforeach
                </div>
            </div>
            @endif

            @if(!empty($content['certifications']))
            <div class="section">
                <div class="section-title">Certifications</div>
                @foreach($content['certifications'] as $cert)
                <div class="cert-item">
                    <div class="cert-name">{{ $cert['name'] ?? '' }}</div>
                    <div class="cert-meta">{{ $cert['issuer'] ?? '' }}@if(!empty($cert['date'])) - {{ $cert['date'] }}@endif</div>
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
                    @if(!empty($project['description']))<div style="color: #555; margin-top: 5px;">{{ $project['description'] }}</div>@endif
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($content['languages']))
            <div class="section">
                <div class="section-title">Languages</div>
                <div>
                    @foreach($content['languages'] as $lang)
                    <span class="lang-item">
                        <span class="lang-name">{{ $lang['name'] ?? $lang }}</span>
                        @if(!empty($lang['proficiency']))<span class="lang-level"> - {{ $lang['proficiency'] }}</span>@endif
                    </span>
                    @endforeach
                </div>
            </div>
            @endif
        </div>
    </div>
</body>
</html>
