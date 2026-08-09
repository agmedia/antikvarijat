<div class="product-photo-manager">
    <div class="file-drop-area product-photo-dropzone" tabindex="0" role="button" aria-controls="files">
        <div class="product-photo-dropzone-icon"><i class="fa-duotone fa-cloud-arrow-up"></i></div>
        <div>
            <strong>Dodajte fotografije artikla</strong>
            <span>Povucite datoteke ovdje ili ih odaberite s uređaja. Možete dodati više fotografija odjednom.</span>
        </div>
        <label for="files" class="btn btn-secondary mb-0"><i class="fa-duotone fa-images mr-1"></i> Odaberi fotografije</label>
        <input name="files[][image]" id="files" type="file" accept="image/*" multiple>
    </div>

    <div class="row items-push product-photo-list" id="sortable">
        <div class="col-sm-12">
            @if (isset($product))
                <div
                    id="existing-images-root"
                    class="row items-push"
                    data-url="{{ route('products.photos', ['product' => $product]) }}"
                    data-loaded="false"
                    data-loading="false"
                >
                    <div class="col-12">
                        <div class="product-photo-loading">
                            <i class="fa-duotone fa-spinner-third fa-spin"></i>
                            <span>Fotografije će se učitati nakon otvaranja ovog taba.</span>
                        </div>
                    </div>
                </div>
            @endif

            <div class="row items-push" id="new-images"></div>
        </div>
    </div>

</div>

@push('product_scripts')

    <script>
        function initMainPhotoTitleCounter(context = document) {
            let el = $(context).find('#max');

            if (!el.length || typeof el.maxlength !== 'function') {
                return;
            }

            el.maxlength({
                alwaysShow: true,
                threshold: el.data('threshold') || 10,
                warningClass: el.data('warning-class') || 'badge badge-warning',
                limitReachedClass: el.data('limit-reached-class') || 'badge badge-danger',
                placement: el.data('placement') || 'bottom',
                preText: el.data('pre-text') || '',
                separator: el.data('separator') || '/',
                postText: el.data('post-text') || ''
            });
        }
    </script>

    <script>
        //
        let blocks = {{ $existingImagesCount ?? 0 }};
        let created_id = 0;
        // get a reference to the file drop area and the file input
        var fileDropArea = document.querySelector('.file-drop-area');
        var fileInput = fileDropArea.querySelector('input');
        var fileInputName = fileInput.name;

        // listen to events for dragging and dropping
        fileDropArea.addEventListener('dragover', handleDragOver);
        fileDropArea.addEventListener('dragleave', function () { fileDropArea.classList.remove('is-dragover'); });
        fileDropArea.addEventListener('drop', handleDrop);
        fileInput.addEventListener('change', handleFileSelect);
        fileDropArea.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                fileInput.click();
            }
        });
        fileDropArea.addEventListener('click', function (event) {
            if (!event.target.closest('label')) {
                fileInput.click();
            }
        });

        function handleDragOver(e) {
            e.preventDefault();
            fileDropArea.classList.add('is-dragover');
        }
        function handleDrop(e) {
            e.preventDefault();
            fileDropArea.classList.remove('is-dragover');
            handleFileItems(e.dataTransfer.items || e.dataTransfer.files);
        }
        function handleFileSelect(e) {
            handleFileItems(e.target.files);
        }

        // loops over a list of items
        function handleFileItems(items) {
            let l = items.length;
            for (let i=0; i<l; i++) {
                handleItem(items[i]);
            }
        }

        function handleItem(item) {
            // get file from item
            let file = item;
            if (item.getAsFile && item.kind == 'file') {
                file = item.getAsFile();
            }

            handleFile(file);
        }

        // now we're sure each item is a file
        function handleFile(file) {
            createCropper(file);
        }

        // create an Image Cropper for each passed file
        function createCropper(file) {
            // create container element for cropper
            let holder = document.getElementById('new-images');

            let col = document.createElement('div');
            col.className = 'col-lg-4 col-md-6 animated fadeIn mb-3 product-photo-card product-photo-card-new';

            let cropper = document.createElement('div');

            // insert this element after the file drop area
            col.insertAdjacentElement('afterbegin', cropper);
            col.insertAdjacentHTML('beforeend', '<div class="product-photo-new-controls">\n' +
                '                                    <label>Redoslijed<input type="number" min="0" class="form-control" name="files[' + created_id + '][sort_order]" value="' + blocks + '"></label>\n' +
                '                                    <label class="custom-control custom-radio mb-0">\n' +
                '                                        <input type="radio" class="custom-control-input" id="new-main-photo-' + created_id + '" name="files[default]" value="image/' + file.name + '">\n' +
                '                                        <span class="custom-control-label">Postavi kao glavnu</span>\n' +
                '                                    </label>\n' +
                '                                </div>');

            holder.insertAdjacentElement('beforeend', col);

            // create a Slim Cropper
            Slim.create(cropper, {
                ratio: 'free',
                //size: '600,800',
                maxFileSize: '2',
                service: false,
                meta: {
                    type: 'products',
                    type_id: "{{ isset($product) ? $product->id : '' }}",
                    image_id: 0
                },
                defaultInputName: fileInputName,
                didInit: function() {
                    // load the file to our slim cropper
                    this.load(file);

                },
                didRemove: function(data, slim) {
                    col.parentNode.removeChild(col)
                    // destroy the slim cropper
                    this.destroy();

                }
            });

            blocks++;
            created_id++;
        }

        function handleXHRRequest(xhr) {
            xhr.setRequestHeader('X-CSRF-TOKEN', "{{ csrf_token() }}");

        }

        function removeImage(data, slim) {
            if (data.meta.hasOwnProperty('image_id')) {
                axios.post("{{ route('products.destroy.image') }}", { data: data.meta.image_id })
                    .then((response) => {
                        successToast.fire({
                            text: 'Fotografija je uspješno izbrisana',
                        })

                        let elem = document.getElementById('image_id_' + data.meta.image_id);

                        elem.parentNode.removeChild(elem);
                    })
                    .catch((error) => {
                        errorToast.fire({
                            text: 'Greška u brisanju fotografije..! Molimo pokušajte ponovo.',
                        })
                    })
            } else {
                errorToast.fire({
                    text: 'Glavna slika se ne može izbrisati..!',
                })
            }

            //slim.destroy();
        }

        // hide file input, we can now upload with JavaScript
        fileInput.style.display = 'none';

        // remove file input name so it's value is
        // not posted to the server
        fileInput.removeAttribute('name');
    </script>

    @if (isset($product))
        <script>
            async function loadExistingProductImages() {
                const root = document.getElementById('existing-images-root');

                if (!root || root.dataset.loaded === 'true' || root.dataset.loading === 'true') {
                    return;
                }

                root.dataset.loading = 'true';
                root.innerHTML = `
                    <div class="col-12"><div class="product-photo-loading"><i class="fa-duotone fa-spinner-third fa-spin"></i><span>Učitavam postojeće fotografije...</span></div></div>
                `;

                try {
                    const response = await fetch(root.dataset.url, {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });

                    if (!response.ok) {
                        throw new Error('Neuspjelo učitavanje slika.');
                    }

                    root.innerHTML = await response.text();
                    root.dataset.loaded = 'true';

                    if (window.Slim && typeof window.Slim.parse === 'function') {
                        window.Slim.parse(root);
                    }

                    initMainPhotoTitleCounter(root);
                } catch (error) {
                    root.innerHTML = `
                        <div class="col-12"><div class="alert alert-warning mb-3">Postojeće fotografije se trenutno ne mogu učitati. Pokušajte ponovno otvoriti tab.</div></div>
                    `;
                } finally {
                    root.dataset.loading = 'false';
                }
            }

            document.addEventListener('DOMContentLoaded', function () {
                const photosTabLink = document.querySelector('a[href="#slike"]');

                if (!photosTabLink) {
                    return;
                }

                photosTabLink.addEventListener('shown.bs.tab', loadExistingProductImages);
                photosTabLink.addEventListener('click', function () {
                    window.setTimeout(loadExistingProductImages, 0);
                });

            });
        </script>
    @endif

@endpush
