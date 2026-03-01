<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreResumeRequest;
use App\Http\Requests\UpdateResumeRequest;
use App\Models\Resume;
use App\Services\AiService;
use App\Services\ResumeBuilder;
use App\Services\ResumeParser;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\View\View;

class ResumeController extends Controller
{
    public function __construct(
        protected ResumeBuilder $builder,
        protected ResumeParser $parser
    ) {}

    public function index(Request $request): View
    {
        $resumes = $request->user()->resumes()->with(['versions' => function ($query) {
            $query->latest()->limit(5);
        }])->get();

        return view('resumes.index', compact('resumes'));
    }

    public function create(Request $request): View
    {
        $profile = $request->user()->profile;
        $templates = Resume::getTemplates();

        return view('resumes.create', compact('profile', 'templates'));
    }

    public function store(StoreResumeRequest $request)
    {
        $this->authorize('create', Resume::class);

        $user = $request->user();
        $profile = $user->profile;

        $content = $request->input('content');

        if (empty($content) && $profile) {
            $content = $profile->toResumeContent();
        }

        $isPrimary = ! $user->resumes()->exists();

        $resume = $user->resumes()->create([
            'title' => $request->input('title'),
            'template' => $request->input('template', Resume::TEMPLATE_CLASSIC),
            'content' => $content,
            'is_primary' => $isPrimary,
            'current_version' => 1,
        ]);

        $resume->createVersion('Initial version');

        if ($request->wantsJson()) {
            return response()->json($resume->load('versions'), 201);
        }

        return redirect()->route('resumes.show', $resume)
            ->with('status', 'Resume created successfully.');
    }

    public function show(Resume $resume): View
    {
        $this->authorize('view', $resume);

        $resume->load(['versions' => function ($query) {
            $query->orderBy('version_number', 'desc');
        }]);

        return view('resumes.show', compact('resume'));
    }

    public function edit(Resume $resume): View
    {
        $this->authorize('update', $resume);

        $templates = Resume::getTemplates();
        $profile = $resume->user->profile;

        return view('resumes.edit', compact('resume', 'templates', 'profile'));
    }

    public function update(UpdateResumeRequest $request, Resume $resume)
    {
        $this->authorize('update', $resume);

        $data = $request->validated();

        $shouldVersion = $request->boolean('create_version', true);
        $changeSummary = $request->input('change_summary', 'Content updated');

        if ($shouldVersion) {
            $resume->bumpVersion($changeSummary);
        }

        $resume->update($data);

        if ($request->wantsJson()) {
            return response()->json($resume->fresh());
        }

        return redirect()->route('resumes.show', $resume)
            ->with('status', 'Resume updated successfully.');
    }

    public function destroy(Request $request, Resume $resume)
    {
        $this->authorize('delete', $resume);

        $resume->delete();

        if ($request->wantsJson()) {
            return response()->json(null, 204);
        }

        return redirect()->route('resumes.index')
            ->with('status', 'Resume deleted successfully.');
    }

    public function download(Resume $resume): Response
    {
        $this->authorize('view', $resume);

        return $this->builder->generatePdf($resume);
    }

    public function preview(Resume $resume): Response
    {
        $this->authorize('view', $resume);

        return $this->builder->streamPdf($resume);
    }

    public function duplicate(Request $request, Resume $resume)
    {
        $this->authorize('view', $resume);

        $newResume = $resume->replicate();
        $newResume->title = $resume->title.' (Copy)';
        $newResume->is_primary = false;
        $newResume->current_version = 1;
        $newResume->save();

        $newResume->createVersion('Duplicated from "'.$resume->title.'"');

        if ($request->wantsJson()) {
            return response()->json($newResume, 201);
        }

        return redirect()->route('resumes.show', $newResume)
            ->with('status', 'Resume duplicated successfully.');
    }

    public function setPrimary(Request $request, Resume $resume)
    {
        $this->authorize('update', $resume);

        $resume->user->resumes()->update(['is_primary' => false]);
        $resume->update(['is_primary' => true]);

        if ($request->wantsJson()) {
            return response()->json($resume);
        }

        return redirect()->back()
            ->with('status', 'Primary resume updated.');
    }

    public function restoreVersion(Request $request, Resume $resume, int $version): JsonResponse
    {
        $this->authorize('update', $resume);

        $success = $resume->restoreVersion($version);

        if (! $success) {
            return response()->json([
                'message' => 'Version not found.',
            ], 404);
        }

        return response()->json([
            'message' => 'Version restored successfully.',
            'resume' => $resume->fresh(['versions']),
        ]);
    }

    public function optimize(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('update', $resume);

        $request->validate([
            'job_description' => 'nullable|string',
        ]);

        $aiService = new AiService($request->user());

        $result = $aiService->optimizeResume(
            $resume->content,
            $request->input('job_description')
        );

        $resume->bumpVersion('AI Resume Optimization', 'ai_optimize', $result['optimization_notes']);
        $resume->update(['content' => $result['content']]);

        return response()->json([
            'message' => 'Resume optimized successfully.',
            'resume' => $resume->fresh(['versions']),
            'optimization_notes' => $result['optimization_notes'],
        ]);
    }

    public function optimizeForAts(Request $request, Resume $resume): JsonResponse
    {
        $this->authorize('update', $resume);

        $request->validate([
            'job_description' => 'required|string',
        ]);

        $aiService = new AiService($request->user());

        $result = $aiService->optimizeResumeForAts(
            $resume->content,
            $request->input('job_description')
        );

        $resume->bumpVersion('ATS Optimization', 'ats_optimize', $result['optimization_notes']);
        $resume->update(['content' => $result['content']]);

        return response()->json([
            'message' => 'Resume optimized for ATS successfully.',
            'resume' => $resume->fresh(['versions']),
            'ats_keywords_added' => $result['ats_keywords_added'],
            'ats_score_before' => $result['ats_score_before'],
            'ats_score_after' => $result['ats_score_after'],
            'optimization_notes' => $result['optimization_notes'],
        ]);
    }

    public function parseUpload(Request $request): JsonResponse
    {
        $request->validate([
            'resume' => 'required|file|mimes:pdf,doc,docx,txt|max:10240',
        ]);

        $data = $this->parser->parse($request->file('resume'));

        if (empty($data)) {
            return response()->json([
                'message' => 'Could not extract content from the uploaded file.',
            ], 422);
        }

        return response()->json([
            'message' => 'Resume parsed successfully.',
            'data' => $data,
        ]);
    }

    public function getProfile(Request $request)
    {
        $profile = $request->user()->profile;

        if ($request->wantsJson()) {
            return response()->json($profile);
        }

        return view('profile.edit', compact('profile'));
    }

    public function updateProfile(Request $request)
    {
        $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:50',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'country' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:20',
            'website' => 'nullable|url|max:255',
            'professional_summary' => 'nullable|string',
            'linkedin_url' => 'nullable|url|max:255',
            'github_url' => 'nullable|url|max:255',
            'twitter_url' => 'nullable|url|max:255',
            'portfolio_url' => 'nullable|url|max:255',
            'work_experience' => 'nullable|array',
            'education' => 'nullable|array',
            'skills' => 'nullable|array',
            'certifications' => 'nullable|array',
            'languages' => 'nullable|array',
            'projects' => 'nullable|array',
            'awards' => 'nullable|array',
            'publications' => 'nullable|array',
            'volunteer_work' => 'nullable|array',
            'references' => 'nullable|array',
        ]);

        $user = $request->user();

        $profile = $user->profile()->updateOrCreate(
            ['user_id' => $user->id],
            $request->only([
                'first_name',
                'last_name',
                'email',
                'phone',
                'address',
                'city',
                'state',
                'country',
                'postal_code',
                'website',
                'professional_summary',
                'linkedin_url',
                'github_url',
                'twitter_url',
                'portfolio_url',
                'work_experience',
                'education',
                'skills',
                'certifications',
                'languages',
                'projects',
                'awards',
                'publications',
                'volunteer_work',
                'references',
                'last_updated_at' => now(),
            ])
        );

        if ($request->wantsJson()) {
            return response()->json($profile);
        }

        return redirect()->back()
            ->with('status', 'Profile updated successfully.');
    }

    public function importProfileFromUpload(Request $request): JsonResponse
    {
        $request->validate([
            'resume' => 'required|file|mimes:pdf,doc,docx,txt|max:10240',
        ]);

        $data = $this->parser->parse($request->file('resume'));

        if (empty($data)) {
            return response()->json([
                'message' => 'Could not extract content from the uploaded file.',
            ], 422);
        }

        $user = $request->user();
        $profile = $user->profile ?? $user->profile()->create(['user_id' => $user->id]);

        if (! empty($data['personal']['first_name'])) {
            $profile->first_name = $data['personal']['first_name'];
        }
        if (! empty($data['personal']['last_name'])) {
            $profile->last_name = $data['personal']['last_name'];
        }
        if (! empty($data['personal']['email'])) {
            $profile->email = $data['personal']['email'];
        }
        if (! empty($data['personal']['phone'])) {
            $profile->phone = $data['personal']['phone'];
        }
        if (! empty($data['professional_summary'])) {
            $profile->professional_summary = $data['professional_summary'];
        }
        if (! empty($data['social_links']['linkedin'])) {
            $profile->linkedin_url = $data['social_links']['linkedin'];
        }
        if (! empty($data['social_links']['github'])) {
            $profile->github_url = $data['social_links']['github'];
        }
        if (! empty($data['work_experience'])) {
            $profile->work_experience = $data['work_experience'];
        }
        if (! empty($data['education'])) {
            $profile->education = $data['education'];
        }
        if (! empty($data['skills'])) {
            $profile->skills = $data['skills'];
        }
        if (! empty($data['certifications'])) {
            $profile->certifications = $data['certifications'];
        }
        if (! empty($data['languages'])) {
            $profile->languages = $data['languages'];
        }
        if (! empty($data['awards'])) {
            $profile->awards = $data['awards'];
        }

        $profile->last_updated_at = now();
        $profile->save();

        return response()->json([
            'message' => 'Profile imported successfully.',
            'profile' => $profile->fresh(),
        ]);
    }
}
