<?php

namespace App\Policies;

use App\Models\CvAnalysis;
use App\Models\User;

class CvAnalysisPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, CvAnalysis $cvAnalysis): bool
    {
        return $user->id === $cvAnalysis->user_id;
    }

    public function create(User $user): bool
    {
        return true;
    }

    public function update(User $user, CvAnalysis $cvAnalysis): bool
    {
        return $user->id === $cvAnalysis->user_id;
    }

    public function delete(User $user, CvAnalysis $cvAnalysis): bool
    {
        return $user->id === $cvAnalysis->user_id;
    }
}
