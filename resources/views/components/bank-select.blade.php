@props(['banks' => [], 'model' => 'bankId'])

<div
    x-data="{
        open: false,
        query: '',
        value: @entangle($model),
        options: @js($banks->map(fn ($bank) => [
            'id' => $bank->id,
            'label' => $bank->name.' ('.$bank->initial.')',
        ])->values()->all()),
        get filtered() {
            return this.options.filter(o => o.label.toLowerCase().includes(this.query.toLowerCase()));
        },
        get selected() {
            return this.options.find(o => o.id == this.value);
        },
        select(o) {
            this.value = o.id;
            this.open = false;
            this.query = '';
        },
    }"
    class="position-relative"
>
    <button type="button" @click="open = !open"
        class="form-control text-start d-flex align-items-center justify-content-between @error($model) is-invalid @enderror"
        style="background:#fff;">
        <span x-text="selected ? selected.label : '-- Pilih Bank --'" x-bind:class="selected ? '' : 'text-muted'"></span>
        <x-lucide-chevron-down style="width: 14px; height: 14px;" class="text-muted ms-1" />
    </button>
    @error($model)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror

    <div x-show="open" @click.outside="open = false" x-cloak
        class="dropdown-menu show position-absolute w-100 mt-1" style="z-index: 1060;">
        <div class="p-2">
            <input x-model="query" type="text" class="form-control form-control-sm" placeholder="Cari bank...">
        </div>
        <div style="max-height: 200px; overflow-y: auto;">
            <template x-for="o in filtered" :key="o.id">
                <a href="#" @click.prevent="select(o)" class="dropdown-item"
                    x-bind:class="o.id == value ? 'active' : ''" x-text="o.label"></a>
            </template>
            <span x-show="filtered.length === 0" class="dropdown-item-text text-muted small">Bank tidak ditemukan</span>
        </div>
    </div>
</div>
