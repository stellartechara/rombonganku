<div wire:key="p-{{ $p->id }}" class="flex items-center gap-2 pr-3">
    <button type="button" wire:click="toggleBoarded({{ $p->id }})"
        class="flex flex-1 items-center gap-3 p-4 text-left text-lg">
        <span @class([
            'flex size-8 shrink-0 items-center justify-center rounded-md border-2 text-white',
            'border-green-600 bg-green-600' => $p->boarded,
            'border-zinc-400' => ! $p->boarded,
        ])>{{ $p->boarded ? '✓' : '' }}</span>
        <span @class(['text-zinc-500 line-through' => $p->boarded])>{{ $p->name }}</span>
    </button>

    @if ($vehicles->count() > 0)
        <select wire:change="assign({{ $p->id }}, $event.target.value)"
            class="max-w-[9rem] rounded-md border border-zinc-300 bg-transparent px-2 py-1 text-sm dark:border-zinc-600"
            aria-label="Kendaraan untuk {{ $p->name }}">
            <option value="" class="text-black">Belum ada</option>
            @foreach ($vehicles as $v)
                <option value="{{ $v->id }}" class="text-black" @selected($p->booking_vehicle_id === $v->id)>
                    {{ $v->driver->name }}
                </option>
            @endforeach
        </select>
    @endif
</div>