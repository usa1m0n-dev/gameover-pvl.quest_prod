<?php

namespace App\Filament\Pages\Auth;

use Filament\Forms\Form;
use Filament\Pages\Auth\EditProfile as BaseEditProfile;

class EditProfile extends BaseEditProfile
{
    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // Поле Имя: только для чтения
                $this->getNameFormComponent()
                    ->disabled(),

                // Поле Email: только для чтения
                $this->getEmailFormComponent()
                    ->disabled(),

                // Поля пароля оставляем редактируемыми
                $this->getPasswordFormComponent(),
                $this->getPasswordConfirmationFormComponent(),
            ]);
    }
}