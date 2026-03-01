<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $resume->getFullName() }} - Resume</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11pt; line-height: 1.5; color: #333; }
        .page { width: 210mm; min-height: 297mm; margin: 0 auto; background: #fff; display: flex; }
        .sidebar { width: 80mm; background: #2c3e50; color: #fff; padding: 25px 20px; }
        .main { flex: 1; padding: 25px 30px; }
        .sidebar-name { font-size: 20pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 20px; line-height: 1.2; }
        .sidebar-section { margin-bottom: 25px; }
        .sidebar-title { font-size: 10pt; font-weight: bold; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid #3498db; padding-bottom: 5px; margin-bottom: 10px; }
        .contact-item { margin-bottom: 8px; font-size: 10pt; }
        .contact-item span { display: block; color: #bdc3c7; font-size: 9pt; margin-bottom: 2px; }
        .sidebar-skill { margin-bottom: 8px; }
        .sidebar-skill-name { font-size: 10pt; margin-bottom: 3px; }
        .skill-bar { height: 6px; background: #34495e; border-radius: 3px; }
        .skill-bar-fill { height: 100%; background: #3498db; border-radius: 3px; }
        .sidebar-link { display: block; color: #3498db; text-decoration: none; font-size: 10pt; margin-bottom: 6px; word-break: break-all; }
        .main-header { margin-bottom: 25px; }
        .main-name { font-size: 24pt; font-weight: bold; color: #2c3e50; text-transform: uppercase; letter-spacing: 1px; }
        .main-title { font-size: 12pt; color: #7f8c8d; margin-top: 5px; }
        .section { margin-bottom: 25px; }
        .section-title { font-size: 12pt; font-weight: bold; text-transform: uppercase; color: #2c3e50; letter-spacing: 1px; border-bottom: 2px solid #3498db; padding-bottom: 5px; margin-bottom: 15px; }
        .summary { text-align: justify; color: #555; }
        .experience-item, .education-item, .project-item { margin-bottom: 18px; }
        .item-header { margin-bottom: 5px; }
        .item-title { font-weight: bold; font-size: 12pt; color: #2c3e50; }
        .item-subtitle { color: #3498db; font-size: 11pt; }
        .item-meta { display: flex; justify-content: space-between; font-size: 10pt; color: #7f8c8d; margin-top: 3px; }
        .highlights { margin-top: 8px; padding-left: 20px; }
        .highlights li { margin-bottom: 4px; color: #555; }
        .cert-item { margin-bottom: 10px; }
        .cert-name { font-weight: bold; }
        .cert-issuer { color: #7f8c8d; font-size: 10pt; }
    </style>
</head>
<body>
    <div class="page">
        <div class="sidebar">
            <div class="sidebar-name">{{ $content['personal']['first_name'] ?? '' }}<br>{{ $content['personal']['last_name'] ?? '' }}</div>
            
            <div class="sidebar-section">
                <div class="sidebar-title">Contact</div>
                @if(!empty($content['personal']['email']))
                <div class="contact-item">
                    <span>Email</span>
                    {{ $content['personal']['email'] }}
                </div>
                @endif
                @if(!empty($content['personal']['phone']))
                <div class="contact-item">
                    <span>Phone</span>
                    {{ $content['personal']['phone'] }}
                </div>
                @endif
                @if(!empty($content['personal']['city']) || !empty($content['personal']['country']))
                <div class="contact-item">
                    <span>Location</span>
                    {{ trim(($content['personal']['city'] ?? '').', '.($content['personal']['country'] ?? ''), ', ') }}
                </div>
                @endif
            </div>

            @if(!empty($content['social_links']))
            <div class="sidebar-section">
                <div class="sidebar-title">Online</div>
                @if(!empty($content['social_links']['linkedin']))
                <a href="{{ $content['social_links']['linkedin'] }}" class="sidebar-link">LinkedIn</a>
                @endif
                @if(!empty($content['social_links']['github']))
                <a href="{{ $content['social_links']['github'] }}" class="sidebar-link">GitHub</a>
                @endif
                @if(!empty($content['social_links']['portfolio']))
                <a href="{{ $content['social_links']['portfolio'] }}" class="sidebar-link">Portfolio</a>
                @endif
            </div>
            @endif

            @if(!empty($content['skills']))
            <div class="sidebar-section">
                <div class="sidebar-title">Skills</div>
                @foreach($content['skills'] as $skill)
                <div class="sidebar-skill">
                    <div class="sidebar-skill-name">{{ $skill['name'] ?? $skill }}</div>
                </div>
                @endforeach
            </div>
            @endif

            @if(!empty($content['languages']))
            <div class="sidebar-section">
                <div class="sidebar-title">Languages</div>
                @foreach($content['languages'] as $lang)
                <div class="sidebar-skill">
                    <div class="sidebar-skill-name">{{ $lang['name'] ?? $lang }}@if(!empty($lang['proficiency'])) - {{ $lang['proficiency'] }}@endif</div>
                </div>
                @endforeach
            </div>
            @endif
        </div>

        <div class="main">
            @if(!empty($content['professional_summary']))
            <div class="section">
                <div class="section-title">Profile</div>
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

            @if(!empty($content['certifications']))
            <div class="section">
                <div class="section-title">Certifications</div>
                @foreach($content['certifications'] as $cert)
                <div class="cert-item">
                    <div class="cert-name">{{ $cert['name'] ?? '' }}</div>
                    @if(!empty($cert['issuer']))<div class="cert-issuer">{{ $cert['issuer'] }}@if(!empty($cert['date'])) - {{ $cert['date'] }}@endif</div>@endif
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
        </div>
    </div>
</body>
</html>
