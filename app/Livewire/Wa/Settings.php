<?php

namespace App\Livewire\Wa;

use App\Models\Setting;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin')]
class Settings extends Component
{
    use AuthorizesRequests;

    public int $max_attempts = 3;

    public string $system_prompt = '';

    public string $welcome_message = '';

    public string $it_notify_numbers = '';

    public int $auto_close_resolved_hours = 24;

    public int $session_timeout_hours = 6;

    public array $business_days = [1, 2, 3, 4, 5];

    public string $business_start = '08:00';

    public string $business_end = '17:00';

    public string $business_timezone = 'Asia/Jakarta';

    public function mount(): void
    {
        $this->authorize('setting.read');

        $this->max_attempts = Setting::int('max_attempts', 3);
        $this->system_prompt = (string) Setting::get('system_prompt', '');
        $this->welcome_message = (string) Setting::get('welcome_message', '');
        $this->it_notify_numbers = implode("\n", Setting::phones('it_notify_numbers'));
        $this->auto_close_resolved_hours = Setting::int('auto_close_resolved_hours', 24);
        $this->session_timeout_hours = Setting::int('session_timeout_hours', 6);

        $hours = Setting::get('business_hours');
        if (is_array($hours)) {
            $this->business_days = array_map('intval', (array) ($hours['days'] ?? [1, 2, 3, 4, 5]));
            $this->business_start = (string) ($hours['start'] ?? '08:00');
            $this->business_end = (string) ($hours['end'] ?? '17:00');
            $this->business_timezone = (string) ($hours['timezone'] ?? 'Asia/Jakarta');
        }
    }

    protected function rules(): array
    {
        return [
            'max_attempts' => 'required|integer|min:1|max:20',
            'system_prompt' => 'required|string|max:8000',
            'welcome_message' => 'nullable|string|max:2000',
            'it_notify_numbers' => 'nullable|string',
            'auto_close_resolved_hours' => 'required|integer|min:1|max:720',
            'session_timeout_hours' => 'required|integer|min:1|max:168',
            'business_days' => 'array',
            'business_start' => 'required|date_format:H:i',
            'business_end' => 'required|date_format:H:i',
            'business_timezone' => 'required|string|max:100',
        ];
    }

    public function save(): void
    {
        $this->authorize('setting.update');
        $this->validate();

        Setting::put('max_attempts', (int) $this->max_attempts);
        Setting::put('system_prompt', $this->system_prompt);
        Setting::put('welcome_message', $this->welcome_message);
        Setting::put('auto_close_resolved_hours', (int) $this->auto_close_resolved_hours);
        Setting::put('session_timeout_hours', (int) $this->session_timeout_hours);
        Setting::put('business_hours', [
            'days' => array_values(array_map('intval', $this->business_days)),
            'start' => $this->business_start,
            'end' => $this->business_end,
            'timezone' => $this->business_timezone,
        ]);

        $numbers = collect(preg_split('/[\s,;]+/', $this->it_notify_numbers) ?: [])
            ->map(fn ($n) => preg_replace('/\D+/', '', (string) $n))
            ->filter()
            ->unique()
            ->values()
            ->all();
        Setting::put('it_notify_numbers', $numbers);

        $this->dispatch('notify', type: 'success', message: 'Pengaturan berhasil disimpan.');
    }

    public function render()
    {
        return view('livewire.wa.settings', [
            'dayNames' => [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'],
        ]);
    }
}
