<div class="admin-entity-picker" wire:keydown.escape="closePanels">
    <input type="hidden" name="translator_ids_present" value="1">

    @if ( ! empty($selected_translators))
        <div class="d-flex flex-wrap mb-2" style="gap: .4rem;" role="list" aria-label="Odabrani prevoditelji">
            @foreach ($selected_translators as $index => $translator)
                <div class="badge badge-light border d-inline-flex align-items-center py-2 px-2"
                     wire:key="selected-translator-{{ $translator['id'] }}"
                     role="listitem">
                    <input type="hidden" name="translator_ids[]" value="{{ $translator['id'] }}">
                    <i class="fa-duotone fa-language text-primary mr-1" aria-hidden="true"></i>
                    <span class="font-w600">{{ $translator['title'] }}</span>

                    @if ($index > 0)
                        <button type="button"
                                class="btn btn-sm btn-link text-muted p-0 ml-2"
                                wire:click="moveTranslator({{ $translator['id'] }}, -1)"
                                title="Pomakni prevoditelja ulijevo"
                                aria-label="Pomakni {{ $translator['title'] }} ulijevo">
                            <i class="fa-solid fa-arrow-left" aria-hidden="true"></i>
                        </button>
                    @endif

                    @if ($index < count($selected_translators) - 1)
                        <button type="button"
                                class="btn btn-sm btn-link text-muted p-0 ml-1"
                                wire:click="moveTranslator({{ $translator['id'] }}, 1)"
                                title="Pomakni prevoditelja udesno"
                                aria-label="Pomakni {{ $translator['title'] }} udesno">
                            <i class="fa-solid fa-arrow-right" aria-hidden="true"></i>
                        </button>
                    @endif

                    <button type="button"
                            class="btn btn-sm btn-link text-danger p-0 ml-2"
                            wire:click="removeTranslator({{ $translator['id'] }})"
                            title="Ukloni prevoditelja"
                            aria-label="Ukloni {{ $translator['title'] }}">
                        <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                    </button>
                </div>
            @endforeach
        </div>
    @endif

    <div class="admin-entity-picker-control">
        <i class="fa-duotone fa-magnifying-glass admin-entity-picker-search-icon" aria-hidden="true"></i>
        <input type="search"
               wire:model.debounce.300ms="search"
               wire:keydown.enter.prevent
               class="form-control admin-entity-picker-input @error('translator_ids') is-invalid @enderror @error('translator_ids.*') is-invalid @enderror"
               id="translator-input"
               placeholder="Pretraži ili dodaj prevoditelja"
               aria-label="Pretraži prevoditelja"
               autocomplete="off">

        <button type="button"
                wire:click="toggleAddWindow"
                class="btn admin-entity-picker-add"
                title="Dodaj novog prevoditelja"
                aria-label="Dodaj novog prevoditelja"
                aria-expanded="{{ $show_add_window ? 'true' : 'false' }}">
            <i class="fa-duotone fa-user-plus" aria-hidden="true"></i>
        </button>
    </div>

    @error('translator_ids')
        <small class="text-danger d-block mt-1">{{ $message }}</small>
    @enderror
    @error('translator_ids.*')
        <small class="text-danger d-block mt-1">{{ $message }}</small>
    @enderror

    @if ( ! empty($search_results) && ! $show_add_window)
        <div class="admin-entity-picker-menu" role="listbox" aria-label="Rezultati pretraživanja prevoditelja">
            @foreach ($search_results as $translator)
                <button type="button"
                        class="admin-entity-picker-option"
                        role="option"
                        wire:key="translator-result-{{ $translator['id'] }}"
                        wire:click="addTranslator({{ $translator['id'] }})">
                    <span class="admin-entity-picker-option-icon"><i class="fa-duotone fa-language" aria-hidden="true"></i></span>
                    <span>
                        <strong>{{ $translator['title'] }}</strong>
                        <small>Prevoditelj</small>
                    </span>
                    <i class="fa-solid fa-chevron-right admin-entity-picker-option-arrow" aria-hidden="true"></i>
                </button>
            @endforeach
        </div>
    @endif

    @if ($show_add_window)
        <div class="admin-entity-picker-create" role="group" aria-labelledby="new-translator-title">
            <div class="admin-entity-picker-create-header">
                <div>
                    <strong id="new-translator-title">Novi prevoditelj</strong>
                    <small>Unesite ime prevoditelja i odmah ga dodajte ovom artiklu.</small>
                </div>
                <button type="button" wire:click="toggleAddWindow" class="btn admin-entity-picker-close" aria-label="Zatvori">
                    <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                </button>
            </div>
            <div class="admin-entity-picker-create-body">
                <label for="new-translator-name">Ime prevoditelja</label>
                <input type="text"
                       class="form-control @error('new_title') is-invalid @enderror"
                       id="new-translator-name"
                       wire:model.defer="new_title"
                       wire:keydown.enter.prevent="makeNewTranslator"
                       maxlength="191"
                       placeholder="npr. Zlatko Crnković"
                       autocomplete="off">
                @error('new_title')
                    <small class="text-danger d-block mt-1">{{ $message }}</small>
                @enderror
            </div>
            <div class="admin-entity-picker-create-actions">
                <button type="button" wire:click="toggleAddWindow" class="btn btn-alt-secondary">Odustani</button>
                <button type="button"
                        wire:click="makeNewTranslator"
                        wire:loading.attr="disabled"
                        wire:target="makeNewTranslator"
                        class="btn btn-primary">
                    <i class="fa-solid fa-floppy-disk mr-1" aria-hidden="true"></i> Snimi i odaberi
                </button>
            </div>
        </div>
    @endif
</div>
