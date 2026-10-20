<?php

namespace App\Livewire\Wa;

use App\Livewire\Traits\WithPerPagePagination;
use App\Models\Employee;
use Closure;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.admin')]
class Employees extends Component
{
    use AuthorizesRequests, WithPagination, WithPerPagePagination;

    public ?int $employeeId = null;

    public string $name = '';

    public string $phone = '';

    public string $department = '';

    public bool $is_active = true;

    public bool $editMode = false;

    public string $search = '';

    public ?int $deleteId = null;

    public string $deleteItemName = '';

    protected function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'phone' => [
                'required', 'string', 'max:20',
                function (string $attribute, mixed $value, Closure $fail) {
                    if (Employee::isReservedSimulationPhone((string) $value)) {
                        $fail('Nomor dengan awalan 000 hanya dipakai untuk simulasi.');
                    }
                },
                Rule::unique('employees', 'phone')
                    ->whereNull('deleted_at')
                    ->ignore($this->employeeId),
            ],
            'department' => 'nullable|string|max:255',
            'is_active' => 'boolean',
        ];
    }

    public function render()
    {
        $employees = Employee::query()
            ->real()
            ->where(function ($q) {
                $q->where('name', 'like', '%'.$this->search.'%')
                    ->orWhere('phone', 'like', '%'.$this->search.'%')
                    ->orWhere('department', 'like', '%'.$this->search.'%');
            })
            ->orderBy('name')
            ->paginate($this->perPage)
            ->withPath(request()->url());

        return view('livewire.wa.employees', compact('employees'));
    }

    public function create(): void
    {
        $this->resetInput();
        $this->dispatch('show-modal');
    }

    public function store(): void
    {
        $this->authorize('employee.create');
        $this->validate();

        $phone = Employee::normalizePhone($this->phone);

        // Re-adding a previously removed number restores the existing record
        // (so its old tickets stay linked) instead of creating a duplicate.
        $existing = Employee::withTrashed()->where('phone', $phone)->first();

        if ($existing) {
            $existing->restore();
            $existing->update([
                'name' => $this->name,
                'department' => $this->department ?: null,
                'is_active' => $this->is_active,
            ]);
        } else {
            Employee::create([
                'name' => $this->name,
                'phone' => $phone,
                'department' => $this->department ?: null,
                'is_active' => $this->is_active,
            ]);
        }

        $this->dispatch('notify', type: 'success', message: 'Whitelist berhasil ditambahkan.');
        $this->dispatch('hide-modal');
        $this->resetInput();
    }

    public function toggleActive(int $id): void
    {
        $this->authorize('employee.update');

        $employee = Employee::real()->findOrFail($id);
        $employee->update(['is_active' => ! $employee->is_active]);

        $this->dispatch(
            'notify',
            type: 'success',
            message: $employee->is_active ? 'Whitelist diaktifkan.' : 'Whitelist dinonaktifkan.',
        );
    }

    public function edit(int $id): void
    {
        $employee = Employee::findOrFail($id);
        $this->employeeId = $employee->id;
        $this->name = $employee->name;
        $this->phone = $employee->phone;
        $this->department = (string) $employee->department;
        $this->is_active = $employee->is_active;
        $this->editMode = true;
        $this->dispatch('show-modal');
    }

    public function update(): void
    {
        $this->authorize('employee.update');
        $this->validate();

        Employee::findOrFail($this->employeeId)->update([
            'name' => $this->name,
            'phone' => Employee::normalizePhone($this->phone),
            'department' => $this->department ?: null,
            'is_active' => $this->is_active,
        ]);

        $this->dispatch('notify', type: 'success', message: 'Whitelist berhasil diperbarui.');
        $this->dispatch('hide-modal');
        $this->resetInput();
    }

    public function confirmDelete(int $id, string $name): void
    {
        $this->authorize('employee.delete');
        $this->deleteId = $id;
        $this->deleteItemName = $name;
        $this->dispatch('show-delete-modal');
    }

    public function deleteConfirmed(): void
    {
        $this->authorize('employee.delete');

        if ($this->deleteId) {
            Employee::findOrFail($this->deleteId)->delete();
            $this->dispatch('notify', type: 'success', message: 'Whitelist berhasil dihapus.');
        }

        $this->dispatch('hide-delete-modal');
        $this->deleteId = null;
        $this->deleteItemName = '';
    }

    private function resetInput(): void
    {
        $this->employeeId = null;
        $this->name = '';
        $this->phone = '';
        $this->department = '';
        $this->is_active = true;
        $this->editMode = false;
    }
}
