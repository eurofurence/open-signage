<?php

namespace App\Filament\Pages\Auth;

use Filament\Actions\Action;
use Filament\Auth\Http\Responses\Contracts\LoginResponse;
use Filament\Auth\Pages\Login;
use Filament\Schemas\Schema;

class LoginPage extends Login
{
    public function authenticate(): ?LoginResponse
    {
        if (config('services.identity.disable_password_login')) {
            return null;
        }

        return parent::authenticate();
    }

    public function form(Schema $schema): Schema
    {
        if (config('services.identity.disable_password_login')) {
            return $schema;
        } else {
            return parent::form($schema);
        }
    }

    public function identityLoginAction(): Action
    {
        return Action::make('identity_login')
            ->label('Login with Identity')
            ->url(route('identity.redirect'));
    }

    protected function getFormActions(): array
    {
        if (config('services.identity.disable_password_login')) {
            return [
                $this->identityLoginAction(),
            ];
        } elseif (config('services.identity.enabled')) {
            return [
                $this->getAuthenticateFormAction(),
                $this->identityLoginAction(),
            ];
        } else {
            return [
                $this->getAuthenticateFormAction(),
            ];
        }
    }
}
