<?php

namespace App\Livewire\Account;

use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use Illuminate\Support\Facades\Storage;

#[Layout('layouts.admin')]
class Profile extends Component
{
    use WithFileUploads;

    public $name = '';
    public $email = '';
    public $photo = null; // uploaded file
    public $currentPhoto = ''; // existing photo path

    protected function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . auth()->id(),
            'photo' => 'nullable|image|mimes:jpeg,png,jpg|max:2048',
        ];
    }

    public function mount()
    {
        $user = auth()->user();
        $this->name = $user->name;
        $this->email = $user->email;
        $this->currentPhoto = $user->photo ?? '';
    }

    public function render()
    {
        return view('livewire.account.profile');
    }

    public function save()
    {
        $this->validate();

        $user = auth()->user();
        $user->name = $this->name;
        $user->email = $this->email;

        if ($this->photo) {
            if ($user->photo) {
                Storage::disk('public')->delete($user->photo);
            }
            $path = $this->photo->store('photos', 'public');
            $user->photo = $path;
            $this->currentPhoto = $path;
            $this->photo = null;
        }

        $user->save();

        $this->dispatch('notify', type: 'success', message: 'Profile updated successfully.');
        $this->dispatch('close-edit-modal');
    }
}
