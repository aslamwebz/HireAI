<?php

namespace App\Filament\Admin\Resources\Users\Schemas;

use App\Models\User;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('password')
                    ->password()
                    ->dehydrated(fn ($state) => filled($state))
                    ->required(fn (string $operation): bool => $operation === 'create'),
                Select::make('role')
                    ->options(User::getRoles())
                    ->default('user')
                    ->required(),
                Select::make('plan')
                    ->options(User::getPlans())
                    ->default('free')
                    ->required(),
                DateTimePicker::make('email_verified_at'),
                DateTimePicker::make('trial_ends_at'),
                TextInput::make('stripe_id')
                    ->label('Stripe ID')
                    ->disabled(),
            ]);
    }
}
