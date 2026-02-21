<?php

namespace App\Filament\Admin\Widgets;

use App\Models\CvAnalysis;
use App\Models\JobApplication;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Total Users', User::count())
                ->description('Registered users')
                ->descriptionIcon('heroicon-m-user-group')
                ->color('success')
                ->chart([7, 3, 4, 5, 6, 3, 5, 3]),

            Stat::make('Pro Users', User::where('plan', 'pro')->count())
                ->description('Paying customers')
                ->descriptionIcon('heroicon-m-star')
                ->color('warning'),

            Stat::make('Job Applications', JobApplication::count())
                ->description('Total tracked')
                ->descriptionIcon('heroicon-m-briefcase')
                ->color('info'),

            Stat::make('CV Analyses', CvAnalysis::where('status', 'completed')->count())
                ->description('Completed analyses')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('primary'),
        ];
    }
}
