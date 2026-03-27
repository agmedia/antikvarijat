@if (! empty($product->image))
    <div class="col-sm-12 animated fadeIn mb-0 p-3 ribbon ribbon-left ribbon-bookmark ribbon-crystal" id="{{ 'image_0' }}">
        <div class="row form-group mt-2">
            <div class="col-sm-3">
                <div class="options-container fx-item-zoom-in fx-overlay-zoom-out">
                    <div class="ribbon-box" style="background-color: #c3c3c3">
                        <i class="fa fa-check"></i> Glavna Slika
                    </div>
                    <div class="slim"
                         data-ratio="free"
                         data-max-file-size="2"
                         data-meta-type="products"
                         data-meta-type_id="{{ $product->id }}"
                         data-will-remove="removeImage">
                        <img
                            src="{{ asset($product->image) }}"
                            alt="{{ 'image_' . $product->id }}"
                            loading="lazy"
                            decoding="async"
                            fetchpriority="low"
                        />
                        <input type="file" name="slim[0][image]"/>
                    </div>
                </div>
            </div>
            <div class="col-sm-9">
                <div class="row mb-2">
                    <label class="col-sm-3 text-right font-size-sm pt-2">Naziv fotografije</label>
                    <div class="col-sm-9">
                        <input type="text" id="max" class="form-control js-tooltip-enabled" name="slim[0][title]" value="{{ $product->imageName() }}" data-toggle="tooltip" data-placement="top" title="Image Title" placeholder="Naziv fotografije">
                    </div>
                </div>
                <div class="row mb-4">
                    <label class="col-sm-3 text-right font-size-sm pt-2">Alt. tekst</label>
                    <div class="col-sm-9 font-size-sm pt-2">
                        Alternativni tekst glavne fotografije je jednak nazivu knjige + autor.
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 text-right">
                        <label class="css-control css-control-primary css-radio">
                            <input type="radio" class="css-control-input" name="slim[default]" checked>
                            Glavna fotografija<span class="css-control-indicator"></span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif

@foreach($images as $image)
    <div class="col-sm-12 animated fadeIn mb-0 p-3 ribbon ribbon-left ribbon-bookmark ribbon-crystal" id="{{ 'image_id_' . $image['id'] }}">
        <div class="row form-group mt-2">
            <div class="col-md-2 col-sm-3">
                <div class="options-container fx-item-zoom-in fx-overlay-zoom-out">
                    <div class="slim"
                         data-ratio="free"
                         data-max-file-size="2"
                         data-meta-type="products"
                         data-meta-type_id="{{ $product->id }}"
                         data-meta-image_id="{{ $image['id'] }}"
                         data-will-remove="removeImage">
                        <img
                            src="{{ asset($image['image']) }}"
                            alt="{{ 'image_' . $image['id'] }}"
                            loading="lazy"
                            decoding="async"
                            fetchpriority="low"
                        />
                        <input type="file" name="slim[{{ $image['id'] }}][image]"/>
                    </div>
                </div>
            </div>
            <div class="col-md-10 col-sm-9">
                <div class="row mb-2">
                    <label class="col-sm-3 text-right font-size-sm pt-2">Naziv fotografije</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control js-tooltip-enabled" name="slim[{{ $image['id'] }}][title]" value="{{ $image['title'] }}" data-toggle="tooltip" data-placement="top" title="Image Title" placeholder="Naziv fotografije">
                    </div>
                </div>
                <div class="row mb-4">
                    <label class="col-sm-3 text-right font-size-sm pt-2">Alt. tekst</label>
                    <div class="col-sm-9">
                        <input type="text" class="form-control js-tooltip-enabled" name="slim[{{ $image['id'] }}][alt]" value="{{ $image['alt'] }}" data-toggle="tooltip" data-placement="top" title="Image Alt Text" placeholder="Alternativni tekst fotografije">
                    </div>
                </div>

                <div class="row mb-3">
                    <label class="col-sm-9 text-right font-size-sm pt-2">Redosljed</label>
                    <div class="col-sm-3">
                        <input type="text" class="form-control js-tooltip-enabled" name="slim[{{ $image['id'] }}][sort_order]" value="{{ $image['sort_order'] }}" data-toggle="tooltip" data-placement="top" title="Sort Order">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-12 text-right mb-2">
                        <div class="custom-control custom-radio mb-1">
                            <input type="radio" class="custom-control-input" id="radio-default-{{ $image['id'] }}" name="slim[default]" value="{{ $image['id'] }}">
                            <label class="custom-control-label" for="radio-default-{{ $image['id'] }}">Glavna fotografija</label>
                        </div>
                    </div>
                    <div class="col-md-12 text-right">
                        <div class="custom-control custom-checkbox custom-checkbox-square custom-control-success mb-1">
                            <input type="checkbox" class="custom-control-input" id="check-published-{{ $image['id'] }}" name="slim[{{ $image['id'] }}][published]" @if($image['published']) checked @endif>
                            <label class="custom-control-label" for="check-published-{{ $image['id'] }}">Vidljivost foto.</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach

<input type="hidden" name="images_order" id="images-order">
