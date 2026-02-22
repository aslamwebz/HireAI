<?php

use App\Models\UploadedCv;
use App\Services\CvTextExtractor;
use Illuminate\Support\Facades\Storage;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\Attributes\Validate;

new class extends Component
{
    use WithFileUploads;

    #[Validate('file|mimes:pdf,doc,docx,txt|max:10240')]
    public $cvFile = null;

    public string $cvName = '';
    public bool $showUploadModal = false;
    public ?int $editingCvId = null;
    public string $editingName = '';

    public function getCvsProperty()
    {
        return auth()->user()->uploadedCvs()->orderBy('is_default', 'desc')->orderBy('created_at', 'desc')->get();
    }

    public function openUploadModal(): void
    {
        $this->showUploadModal = true;
        $this->cvFile = null;
        $this->cvName = '';
    }

    public function uploadCv(): void
    {
        $this->validate([
            'cvFile' => 'required|file|mimes:pdf,doc,docx,txt|max:10240',
            'cvName' => 'required|string|min:1',
        ]);

        $extractor = app(CvTextExtractor::class);
        $extractedText = $extractor->extract($this->cvFile);
        $path = $this->cvFile->store('cvs', 'public');

        UploadedCv::create([
            'user_id' => auth()->id(),
            'name' => $this->cvName,
            'file_path' => $path,
            'original_name' => $this->cvFile->getClientOriginalName(),
            'mime_type' => $this->cvFile->getMimeType(),
            'file_size' => $this->cvFile->getSize(),
            'extracted_text' => $extractedText,
            'is_default' => $this->cvs->isEmpty(),
        ]);

        $this->showUploadModal = false;
        $this->cvFile = null;
        $this->cvName = '';
        $this->dispatch('cv-uploaded');
    }

    public function updatedCvFile(): void
    {
        if ($this->cvFile && empty($this->cvName)) {
            $name = pathinfo($this->cvFile->getClientOriginalName(), PATHINFO_FILENAME);
            $this->cvName = $name;
        }
    }

    public function editCv($cvId): void
    {
        $cv = auth()->user()->uploadedCvs()->find($cvId);
        if ($cv) {
            $this->editingCvId = $cvId;
            $this->editingName = $cv->name;
        }
    }

    public function saveEdit(): void
    {
        if ($this->editingCvId) {
            $cv = auth()->user()->uploadedCvs()->find($this->editingCvId);
            if ($cv) {
                $cv->update(['name' => $this->editingName]);
            }
            $this->editingCvId = null;
            $this->editingName = '';
            $this->dispatch('cv-updated');
        }
    }

    public function cancelEdit(): void
    {
        $this->editingCvId = null;
        $this->editingName = '';
    }

    public function deleteCv($cvId): void
    {
        $cv = auth()->user()->uploadedCvs()->find($cvId);
        if ($cv) {
            Storage::disk('public')->delete($cv->file_path);
            $cv->delete();
            $this->dispatch('cv-deleted');
        }
    }

    public function setDefault($cvId): void
    {
        auth()->user()->uploadedCvs()->update(['is_default' => false]);
        $cv = auth()->user()->uploadedCvs()->find($cvId);
        if ($cv) {
            $cv->update(['is_default' => true]);
            $this->dispatch('cv-updated');
        }
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">My CVs</h1>
            <p class="text-zinc-400">Manage your uploaded CVs for all tools</p>
        </div>
        <flux:button wire:click="openUploadModal" variant="primary" icon="plus">
            Upload CV
        </flux:button>
    </div>

    @if($this->cvs->isEmpty())
        <div class="glass-card text-center py-16">
            <div class="w-20 h-20 rounded-2xl bg-zinc-800 flex items-center justify-center mx-auto mb-4">
                <flux:icon name="document-duplicate" class="w-10 h-10 text-zinc-600" />
            </div>
            <h2 class="text-xl font-semibold text-white mb-2">No CVs uploaded yet</h2>
            <p class="text-zinc-400 mb-6">Upload your first CV to use in CV Analyzer, Cover Letters, and Interview Prep</p>
            <flux:button wire:click="openUploadModal" variant="primary">
                <flux:icon name="plus" class="w-4 h-4 mr-2" />
                Upload Your First CV
            </flux:button>
        </div>
    @else
        <div class="grid md:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach($this->cvs as $cv)
                <div class="glass-card relative group {{ $cv->is_default ? 'ring-2 ring-violet-500' : '' }}">
                    @if($cv->is_default)
                        <div class="absolute -top-2 -right-2">
                            <span class="px-2 py-1 bg-violet-500 text-white text-xs font-medium rounded-full flex items-center gap-1">
                                <flux:icon name="star" class="w-3 h-3" />
                                Default
                            </span>
                        </div>
                    @endif

                    <div class="flex items-start gap-4 mb-4">
                        <div class="w-12 h-12 rounded-xl bg-gradient-to-br from-violet-500/20 to-purple-500/20 flex items-center justify-center flex-shrink-0">
                            <flux:icon name="document-text" class="w-6 h-6 text-violet-400" />
                        </div>
                        <div class="flex-1 min-w-0">
                            @if($editingCvId === $cv->id)
                                <div class="flex gap-2">
                                    <flux:input wire:model="editingName" size="sm" class="flex-1" />
                                    <flux:button wire:click="saveEdit" size="sm" variant="primary" icon="check" />
                                    <flux:button wire:click="cancelEdit" size="sm" variant="subtle" icon="x-mark" />
                                </div>
                            @else
                                <h3 class="font-semibold text-white truncate">{{ $cv->name }}</h3>
                                <p class="text-sm text-zinc-500 truncate">{{ $cv->original_name }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="flex items-center gap-2 text-xs text-zinc-500 mb-4">
                        <span class="px-2 py-1 bg-zinc-800 rounded">{{ strtoupper($cv->extension) }}</span>
                        <span>{{ number_format($cv->file_size / 1024, 1) }} KB</span>
                        <span>•</span>
                        <span>{{ $cv->created_at->format('M d, Y') }}</span>
                    </div>

                    <div class="flex items-center gap-2 pt-3 border-t border-white/10">
                        @if(!$cv->is_default)
                            <flux:button wire:click="setDefault({{ $cv->id }})" size="sm" variant="subtle" class="flex-1">
                                <flux:icon name="star" class="w-4 h-4 mr-1" />
                                Set Default
                            </flux:button>
                        @else
                            <flux:button wire:click="editCv({{ $cv->id }})" size="sm" variant="subtle" class="flex-1">
                                <flux:icon name="pencil" class="w-4 h-4 mr-1" />
                                Edit
                            </flux:button>
                        @endif
                        <flux:button wire:click="deleteCv({{ $cv->id }})" size="sm" variant="subtle" icon="trash" class="text-red-400 hover:text-red-300" />
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    @if($showUploadModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4" wire:click.self="$set('showUploadModal', false)">
            <div class="absolute inset-0 bg-black/60 backdrop-blur-sm"></div>
            <div class="relative glass-card max-w-lg w-full p-6">
                <div class="flex items-center justify-between mb-6">
                    <h3 class="text-lg font-semibold text-white">Upload CV</h3>
                    <flux:button wire:click="$set('showUploadModal', false)" size="sm" variant="subtle" icon="x-mark" />
                </div>

                <div class="space-y-4">
                    <div 
                        class="border-2 border-dashed border-white/20 rounded-xl p-8 text-center hover:border-violet-500/50 transition"
                        wire:ignore
                        x-data="{ dragging: false }"
                        @dragover.prevent="dragging = true"
                        @dragleave.prevent="dragging = false"
                        @drop.prevent="dragging = false"
                        :class="{ 'border-violet-500 bg-violet-500/10': dragging }"
                    >
                        <flux:icon name="cloud-arrow-up" class="w-12 h-12 mx-auto text-zinc-500 mb-3" />
                        <p class="text-zinc-400 mb-2">Drag & drop your CV or click to browse</p>
                        <p class="text-xs text-zinc-500">PDF, DOC, DOCX, TXT (max 10MB)</p>
                        <input 
                            type="file" 
                            wire:model="cvFile" 
                            accept=".pdf,.doc,.docx,.txt"
                            class="mt-4 block w-full text-sm text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-violet-600 file:text-white hover:file:bg-violet-500 file:cursor-pointer"
                        >
                    </div>

                    @if($cvFile)
                        <div class="flex items-center gap-3 p-4 bg-violet-500/10 border border-violet-500/20 rounded-lg">
                            <flux:icon name="document-text" class="w-8 h-8 text-violet-400" />
                            <div class="flex-1">
                                <p class="text-sm text-white font-medium">{{ $cvFile->getClientOriginalName() }}</p>
                                <p class="text-xs text-zinc-500">{{ number_format($cvFile->getSize() / 1024, 1) }} KB</p>
                            </div>
                            <flux:button wire:click="$set('cvFile', null)" size="sm" variant="subtle" icon="x-mark" />
                        </div>
                    @endif

                    <flux:field label="CV Name">
                        <flux:input 
                            wire:model="cvName" 
                            placeholder="e.g., Software Engineer Resume 2024" 
                        />
                        <flux:description>Give your CV a name to easily identify it</flux:description>
                    </flux:field>

                    <div class="flex gap-3 pt-2">
                        <flux:button wire:click="$set('showUploadModal', false)" variant="subtle" class="flex-1">
                            Cancel
                        </flux:button>
                        <flux:button wire:click="uploadCv" variant="primary" class="flex-1" :disabled="!$cvFile || !$cvName">
                            <flux:icon name="cloud-arrow-up" class="w-4 h-4 mr-2" />
                            Upload CV
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
