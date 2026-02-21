<?php

use App\Models\JobApplication;
use Livewire\Component;
use Livewire\Attributes\On;

new class extends Component
{
    public bool $showModal = false;
    public bool $editing = false;
    public ?int $editingId = null;
    
    public string $company = '';
    public string $position = '';
    public string $job_description = '';
    public string $job_url = '';
    public string $status = 'wishlist';
    public string $location = '';
    public bool $remote = false;
    public ?string $salary_min = null;
    public ?string $salary_max = null;
    public ?string $applied_at = null;
    public ?string $deadline_at = null;
    public string $notes = '';

    protected $listeners = [
        'jobMoved' => 'handleJobMoved',
    ];

    public function getJobsProperty()
    {
        return auth()->user()->jobApplications()
            ->orderBy('priority', 'desc')
            ->orderBy('updated_at', 'desc')
            ->get()
            ->groupBy('status');
    }

    public function getStatusesProperty()
    {
        return JobApplication::getStatuses();
    }

    public function openModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->editing = false;
        $this->editingId = null;
        $this->company = '';
        $this->position = '';
        $this->job_description = '';
        $this->job_url = '';
        $this->status = 'wishlist';
        $this->location = '';
        $this->remote = false;
        $this->salary_min = null;
        $this->salary_max = null;
        $this->applied_at = null;
        $this->deadline_at = null;
        $this->notes = '';
    }

    public function save(): void
    {
        $this->validate([
            'company' => 'required|string|max:255',
            'position' => 'required|string|max:255',
            'status' => 'required|string',
        ]);

        $data = [
            'company' => $this->company,
            'position' => $this->position,
            'job_description' => $this->job_description,
            'job_url' => $this->job_url,
            'status' => $this->status,
            'location' => $this->location,
            'remote' => $this->remote,
            'salary_min' => $this->salary_min ?: null,
            'salary_max' => $this->salary_max ?: null,
            'applied_at' => $this->applied_at ?: null,
            'deadline_at' => $this->deadline_at ?: null,
            'notes' => $this->notes,
        ];

        if ($this->editing && $this->editingId) {
            auth()->user()->jobApplications()->where('id', $this->editingId)->update($data);
        } else {
            auth()->user()->jobApplications()->create($data);
        }

        $this->closeModal();
    }

    public function edit($id): void
    {
        $job = auth()->user()->jobApplications()->findOrFail($id);
        
        $this->editing = true;
        $this->editingId = $job->id;
        $this->company = $job->company;
        $this->position = $job->position;
        $this->job_description = $job->job_description ?? '';
        $this->job_url = $job->job_url ?? '';
        $this->status = $job->status;
        $this->location = $job->location ?? '';
        $this->remote = $job->remote;
        $this->salary_min = $job->salary_min;
        $this->salary_max = $job->salary_max;
        $this->applied_at = $job->applied_at?->format('Y-m-d');
        $this->deadline_at = $job->deadline_at?->format('Y-m-d');
        $this->notes = $job->notes ?? '';
        
        $this->showModal = true;
    }

    public function delete($id): void
    {
        auth()->user()->jobApplications()->where('id', $id)->delete();
    }

    public function handleJobMoved($data): void
    {
        $job = auth()->user()->jobApplications()->find($data['jobId']);
        if ($job) {
            $job->update(['status' => $data['status']]);
        }
    }

    public function updateStatus($jobId, $newStatus): void
    {
        $job = auth()->user()->jobApplications()->find($jobId);
        if ($job) {
            $job->update(['status' => $newStatus]);
        }
    }
};
?>

<div class="space-y-6">
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-white">Job Application Tracker</h1>
            <p class="text-zinc-400">Track and manage your job applications</p>
        </div>
        <flux:button wire:click="openModal" variant="primary" icon="plus">
            Add Job
        </flux:button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4">
        @foreach($this->statuses as $statusKey => $statusLabel)
            <div class="space-y-3">
                <div class="flex items-center justify-between px-3 py-2 bg-white/5 rounded-lg">
                    <h3 class="font-medium text-white">{{ $statusLabel }}</h3>
                    <span class="text-xs text-zinc-400">{{ ($this->jobs[$statusKey] ?? collect())->count() }}</span>
                </div>
                
                <div 
                    class="space-y-2 min-h-[200px] p-2 rounded-lg bg-white/[0.02] border border-white/5"
                    wire:sortable
                    wire:sortable.group="jobs"
                    wire:sortable.options="{ animation: 150, group: { name: 'jobs', put: true, pull: true } }"
                >
                    @forelse($this->jobs[$statusKey] ?? [] as $job)
                        <div 
                            wire:key="job-{{ $job->id }}"
                            wire:sortable.item="{{ $job->id }}"
                            class="glass-card p-3 cursor-pointer hover:bg-white/10 transition"
                            wire:click="edit({{ $job->id }})"
                        >
                            <div class="flex items-start justify-between mb-2">
                                <h4 class="font-medium text-white text-sm">{{ $job->position }}</h4>
                            </div>
                            <p class="text-xs text-zinc-400 mb-2">{{ $job->company }}</p>
                            
                            @if($job->location || $job->remote)
                                <div class="flex items-center gap-1 text-xs text-zinc-500">
                                    <flux:icon name="map-pin" class="w-3 h-3" />
                                    <span>{{ $job->remote ? 'Remote' : $job->location }}</span>
                                </div>
                            @endif
                            
                            @if($job->deadline_at)
                                <div class="flex items-center gap-1 text-xs mt-2 {{ $job->deadline_at->isPast() ? 'text-red-400' : 'text-zinc-500' }}">
                                    <flux:icon name="calendar" class="w-3 h-3" />
                                    <span>{{ $job->deadline_at->format('M j') }}</span>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="text-center py-8 text-zinc-500 text-sm">
                            No jobs
                        </div>
                    @endforelse
                </div>
            </div>
        @endforeach
    </div>

    <flux:modal wire:model="showModal" class="max-w-2xl">
        <div class="p-6 space-y-6">
            <div class="flex items-center justify-between">
                <h2 class="text-xl font-semibold text-white">
                    {{ $editing ? 'Edit Job' : 'Add New Job' }}
                </h2>
                <flux:button wire:click="closeModal" variant="subtle" icon="x-mark" />
            </div>

            <div class="grid gap-4">
                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Company *</flux:label>
                        <flux:input wire:model="company" placeholder="Company name" />
                        <flux:error name="company" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Position *</flux:label>
                        <flux:input wire:model="position" placeholder="Job title" />
                        <flux:error name="position" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Status</flux:label>
                    <flux:select wire:model="status">
                        @foreach($this->statuses as $key => $label)
                            <flux:select.option value="{{ $key }}">{{ $label }}</flux:select.option>
                        @endforeach
                    </flux:select>
                    <flux:error name="status" />
                </flux:field>

                <flux:field>
                    <flux:label>Job URL</flux:label>
                    <flux:input wire:model="job_url" type="url" placeholder="https://..." />
                </flux:field>

                <flux:field>
                    <flux:label>Job Description</flux:label>
                    <flux:textarea wire:model="job_description" rows="4" placeholder="Paste job description..." />
                </flux:field>

                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Location</flux:label>
                        <flux:input wire:model="location" placeholder="City, Country" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Remote</flux:label>
                        <div class="pt-2">
                            <flux:checkbox wire:model="remote" label="This is a remote position" />
                        </div>
                    </flux:field>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Salary Min</flux:label>
                        <flux:input wire:model="salary_min" type="number" placeholder="e.g., 80000" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Salary Max</flux:label>
                        <flux:input wire:model="salary_max" type="number" placeholder="e.g., 120000" />
                    </flux:field>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <flux:field>
                        <flux:label>Applied Date</flux:label>
                        <flux:input wire:model="applied_at" type="date" />
                    </flux:field>

                    <flux:field>
                        <flux:label>Deadline</flux:label>
                        <flux:input wire:model="deadline_at" type="date" />
                    </flux:field>
                </div>

                <flux:field>
                    <flux:label>Notes</flux:label>
                    <flux:textarea wire:model="notes" rows="3" placeholder="Any additional notes..." />
                </flux:field>
            </div>

            <div class="flex justify-between pt-4 border-t border-white/10">
                @if($editing)
                    <flux:button wire:click="delete({{ $editingId }})" variant="danger" icon="trash">
                        Delete
                    </flux:button>
                @else
                    <div></div>
                @endif
                <div class="flex gap-2">
                    <flux:button wire:click="closeModal" variant="subtle">
                        Cancel
                    </flux:button>
                    <flux:button wire:click="save" variant="primary">
                        {{ $editing ? 'Update' : 'Add Job' }}
                    </flux:button>
                </div>
            </div>
        </div>
    </flux:modal>
</div>
