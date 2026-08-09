@if (! empty($product->image))
    <div class="col-12 animated fadeIn product-photo-card product-photo-card-main" id="{{ 'image_0' }}">
        <div class="row product-photo-card-layout">
            <div class="col-lg-3 col-md-4">
                <div class="options-container fx-item-zoom-in fx-overlay-zoom-out product-photo-preview">
                    <div class="product-photo-primary-badge">
                        <i class="fa-duotone fa-star"></i> Glavna fotografija
                    </div>
                    <div class="slim"
                         data-ratio="free"
                         data-max-file-size="2"
                         data-meta-type="products"
                         data-meta-type_id="{{ $product->id }}"
                         data-will-remove="removeImage">
                        <img
                            src="{{ \App\Support\AdminImage::url($product->image) }}"
                            alt="{{ 'image_' . $product->id }}"
                            loading="lazy"
                            decoding="async"
                            fetchpriority="low"
                        />
                        <input type="file" name="slim[0][image]"/>
                    </div>
                </div>
            </div>
            <div class="col-lg-9 col-md-8 product-photo-fields">
                <div class="form-group mb-3">
                    <label for="max">Naziv fotografije</label>
                    <div>
                        <input type="text" id="max" class="form-control" name="slim[0][title]" value="{{ $product->imageName() }}" placeholder="Naziv fotografije">
                    </div>
                </div>
                <div class="product-photo-help mb-3">
                    <i class="fa-duotone fa-circle-info"></i>
                    <span>
                        <strong>Alternativni tekst</strong>
                        Alternativni tekst glavne fotografije je jednak nazivu knjige + autor.
                    </span>
                </div>
                <div class="product-photo-state">
                    <i class="fa-duotone fa-circle-check"></i>
                    Ova fotografija trenutačno je glavna.
                    <input type="radio" class="d-none" name="slim[default]" checked>
                </div>
            </div>
        </div>
    </div>
@endif

@foreach($images as $image)
    <div class="col-12 animated fadeIn product-photo-card" id="{{ 'image_id_' . $image['id'] }}">
        <div class="row product-photo-card-layout">
            <div class="col-lg-3 col-md-4">
                <div class="options-container fx-item-zoom-in fx-overlay-zoom-out product-photo-preview">
                    <div class="slim"
                         data-ratio="free"
                         data-max-file-size="2"
                         data-meta-type="products"
                         data-meta-type_id="{{ $product->id }}"
                         data-meta-image_id="{{ $image['id'] }}"
                         data-will-remove="removeImage">
                        <img
                            src="{{ \App\Support\AdminImage::url($image['image']) }}"
                            alt="{{ 'image_' . $image['id'] }}"
                            loading="lazy"
                            decoding="async"
                            fetchpriority="low"
                        />
                        <input type="file" name="slim[{{ $image['id'] }}][image]"/>
                    </div>
                </div>
            </div>
            <div class="col-lg-9 col-md-8 product-photo-fields">
                <div class="form-group mb-3">
                    <label>Naziv fotografije</label>
                    <input type="text" class="form-control" name="slim[{{ $image['id'] }}][title]" value="{{ $image['title'] }}" placeholder="Naziv fotografije">
                </div>
                <div class="form-group mb-3">
                    <label>Alternativni tekst</label>
                    <input type="text" class="form-control" name="slim[{{ $image['id'] }}][alt]" value="{{ $image['alt'] }}" placeholder="Opišite što se nalazi na fotografiji">
                </div>

                <div class="product-photo-controls">
                    <div class="form-group mb-0 product-photo-order">
                        <label>Redoslijed</label>
                        <input type="number" min="0" class="form-control" name="slim[{{ $image['id'] }}][sort_order]" value="{{ $image['sort_order'] }}">
                    </div>
                    <div class="custom-control custom-radio">
                        <input type="radio" class="custom-control-input" id="radio-default-{{ $image['id'] }}" name="slim[default]" value="{{ $image['id'] }}">
                        <label class="custom-control-label" for="radio-default-{{ $image['id'] }}">Postavi kao glavnu</label>
                    </div>
                    <div class="custom-control custom-checkbox custom-checkbox-square custom-control-success">
                        <input type="checkbox" class="custom-control-input" id="check-published-{{ $image['id'] }}" name="slim[{{ $image['id'] }}][published]" @if($image['published']) checked @endif>
                        <label class="custom-control-label" for="check-published-{{ $image['id'] }}">Fotografija je vidljiva</label>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endforeach

<input type="hidden" name="images_order" id="images-order">
