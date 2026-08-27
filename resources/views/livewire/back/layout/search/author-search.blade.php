<div class="admin-entity-picker" wire:keydown.escape="$set('show_add_window', false)">
    <div class="admin-entity-picker-control{{ $list ? ' admin-entity-picker-control-list' : '' }}">
        <i class="fa-duotone fa-magnifying-glass admin-entity-picker-search-icon" aria-hidden="true"></i>
        <input type="search"
               wire:model.debounce.300ms="search"
               wire:keydown.enter.prevent
               class="form-control admin-entity-picker-input @error('author_id') is-invalid @enderror"
               id="author-input"
               placeholder="{{ ! $list ? 'Pretraži ili dodaj autora' : 'Pretraži autora' }}"
               aria-label="Pretraži autora"
               autocomplete="off">

        @if ( ! $list)
            <input type="hidden" wire:model="author_id" name="author_id">
            <button type="button"
                    wire:click="viewAddWindow"
                    class="btn admin-entity-picker-add"
                    title="Dodaj novog autora"
                    aria-label="Dodaj novog autora"
                    aria-expanded="{{ $show_add_window ? 'true' : 'false' }}">
                <i class="fa-duotone fa-user-plus" aria-hidden="true"></i>
            </button>
        @endif
    </div>

    @if ( ! empty($search_results) && ! $show_add_window)
        <div class="admin-entity-picker-menu" role="listbox" aria-label="Rezultati pretraživanja autora">
            @foreach ($search_results as $author)
                <button type="button"
                        class="admin-entity-picker-option"
                        role="option"
                        wire:key="author-result-{{ $author->id }}"
                        wire:click="addAuthor('{{ $author->id }}')">
                    <span class="admin-entity-picker-option-icon"><i class="fa-duotone fa-user-pen" aria-hidden="true"></i></span>
                    <span>
                        <strong>{{ $author->title }}</strong>
                        <small>Autor</small>
                    </span>
                    <i class="fa-solid fa-chevron-right admin-entity-picker-option-arrow" aria-hidden="true"></i>
                </button>
            @endforeach
        </div>
    @endif

    @if ( ! $list && $show_add_window)
        <div class="admin-entity-picker-create" role="group" aria-labelledby="new-author-title">
            <div class="admin-entity-picker-create-header">
                <div>
                    <strong id="new-author-title">Novi autor</strong>
                    <small>Unesite ime autora i odmah ga odaberite za ovaj artikl.</small>
                </div>
                <button type="button" wire:click="viewAddWindow" class="btn admin-entity-picker-close" aria-label="Zatvori">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <div class="admin-entity-picker-create-body">
                <label for="new-author-name">Ime autora</label>
                <input type="text"
                       class="form-control @error('new.title') is-invalid @enderror"
                       id="new-author-name"
                       wire:model.defer="new.title"
                       wire:keydown.enter.prevent="makeNewAuthor"
                       maxlength="191"
                       placeholder="npr. William Shakespeare"
                       autocomplete="off">
                @error('new.title')
                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                @enderror
            </div>
            <div class="admin-entity-picker-create-actions">
                <button type="button" wire:click="viewAddWindow" class="btn btn-alt-secondary">Odustani</button>
                <button type="button" wire:click="makeNewAuthor" wire:loading.attr="disabled" wire:target="makeNewAuthor" class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk mr-1" aria-hidden="true"></i> Snimi autora
                </button>
            </div>
        </div>
    @endif
</div>
