<div class="admin-entity-picker" wire:keydown.escape="$set('show_add_window', false)">
    <div class="admin-entity-picker-control{{ $list ? ' admin-entity-picker-control-list' : '' }}">
        <i class="fa-duotone fa-magnifying-glass admin-entity-picker-search-icon" aria-hidden="true"></i>
        <input type="search"
               wire:model.debounce.300ms="search"
               class="form-control admin-entity-picker-input @error('publisher_id') is-invalid @enderror"
               id="publisher-input"
               placeholder="{{ ! $list ? 'Pretraži ili dodaj izdavača' : 'Pretraži izdavača' }}"
               aria-label="Pretraži izdavača"
               autocomplete="off">

        @if ( ! $list)
            <input type="hidden" wire:model="publisher_id" name="publisher_id">
            <button type="button"
                    wire:click="viewAddWindow"
                    class="btn admin-entity-picker-add"
                    title="Dodaj novog izdavača"
                    aria-label="Dodaj novog izdavača"
                    aria-expanded="{{ $show_add_window ? 'true' : 'false' }}">
                <i class="fa-solid fa-plus" aria-hidden="true"></i>
            </button>
        @endif
    </div>

    @if ( ! empty($search_results) && ! $show_add_window)
        <div class="admin-entity-picker-menu" role="listbox" aria-label="Rezultati pretraživanja izdavača">
            @foreach ($search_results as $publisher)
                <button type="button"
                        class="admin-entity-picker-option"
                        role="option"
                        wire:key="publisher-result-{{ $publisher->id }}"
                        wire:click="addPublisher('{{ $publisher->id }}')">
                    <span class="admin-entity-picker-option-icon"><i class="fa-duotone fa-building" aria-hidden="true"></i></span>
                    <span>
                        <strong>{{ $publisher->title }}</strong>
                        <small>Izdavač</small>
                    </span>
                    <i class="fa-solid fa-chevron-right admin-entity-picker-option-arrow" aria-hidden="true"></i>
                </button>
            @endforeach
        </div>
    @endif

    @if ( ! $list && $show_add_window)
        <div class="admin-entity-picker-create" role="group" aria-labelledby="new-publisher-title">
            <div class="admin-entity-picker-create-header">
                <div>
                    <strong id="new-publisher-title">Novi izdavač</strong>
                    <small>Unesite naziv izdavača i odmah ga odaberite za ovaj artikl.</small>
                </div>
                <button type="button" wire:click="viewAddWindow" class="btn admin-entity-picker-close" aria-label="Zatvori">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <div class="admin-entity-picker-create-body">
                <label for="new-publisher-name">Naziv izdavača</label>
                <input type="text"
                       class="form-control @if (session()->has('title')) is-invalid @endif"
                       id="new-publisher-name"
                       wire:model.defer="new.title"
                       wire:keydown.enter.prevent="makeNewPublisher"
                       placeholder="npr. Školska knjiga"
                       autocomplete="off">
                @if (session()->has('title'))
                    <small class="text-danger">Naziv izdavača je obvezan.</small>
                @endif
            </div>
            <div class="admin-entity-picker-create-actions">
                <button type="button" wire:click="viewAddWindow" class="btn btn-alt-secondary">Odustani</button>
                <button type="button" wire:click="makeNewPublisher" wire:loading.attr="disabled" wire:target="makeNewPublisher" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk mr-1" aria-hidden="true"></i> Snimi izdavača
                </button>
            </div>
        </div>
    @endif
</div>
