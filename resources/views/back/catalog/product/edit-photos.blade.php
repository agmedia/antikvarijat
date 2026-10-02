<div class="product-photo-manager">
    <div class="file-drop-area product-photo-dropzone" tabindex="0" role="button" aria-controls="files">
        <div class="product-photo-dropzone-icon"><i class="fa-duotone fa-cloud-arrow-up"></i></div>
        <div>
            <strong>Dodajte fotografije artikla</strong>
            <span>Povucite datoteke ovdje ili ih odaberite s uređaja. Do 10 fotografija po spremanju; velike se automatski smanjuju.</span>
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
        const maxNewImages = 10;
        const pendingImageFiles = [];
        let isProcessingImage = false;
        window.productImageQueueBusy = false;
        // get a reference to the file drop area and the file input
        var fileDropArea = document.querySelector('.file-drop-area');
        var fileInput = fileDropArea.querySelector('input');

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
            const files = [];

            for (let i = 0; i < items.length; i++) {
                const file = getFileFromItem(items[i]);

                if (file) {
                    files.push(file);
                }
            }

            const activeImages = document.querySelectorAll('#new-images .product-photo-card-new').length;
            const availableSlots = Math.max(0, maxNewImages - activeImages - pendingImageFiles.length);

            if (files.length > availableSlots) {
                showPhotoError('Odjednom možete dodati najviše ' + maxNewImages + ' fotografija.');
            }

            pendingImageFiles.push(...files.slice(0, availableSlots));
            updateImageQueueState();
            processNextImage();
        }

        function getFileFromItem(item) {
            if (item.getAsFile && item.kind !== 'file') {
                return null;
            }

            let file = item;

            if (item.getAsFile && item.kind == 'file') {
                file = item.getAsFile();
            }

            return file;
        }

        function processNextImage() {
            if (isProcessingImage || !pendingImageFiles.length) {
                if (!isProcessingImage) {
                    fileInput.value = '';
                }

                updateImageQueueState();
                return;
            }

            isProcessingImage = true;
            updateImageQueueState();
            createCropper(pendingImageFiles.shift(), function () {
                isProcessingImage = false;
                updateImageQueueState();
                processNextImage();
            });
        }

        function updateImageQueueState() {
            const busy = isProcessingImage || pendingImageFiles.length > 0;
            const saveButton = document.getElementById('product-save-button');

            window.productImageQueueBusy = busy;

            if (!saveButton || saveButton.dataset.submitting === 'true') {
                return;
            }

            saveButton.disabled = busy;
            saveButton.innerHTML = busy
                ? '<i class="fa fa-spinner fa-spin mr-1"></i> Pripremam fotografije...'
                : '<i class="fa-duotone fa-floppy-disk mr-1"></i> Spremi artikl';
        }

        function showPhotoError(message) {
            if (typeof errorToast !== 'undefined' && errorToast && typeof errorToast.fire === 'function') {
                errorToast.fire({ text: message });
                return;
            }

            window.alert(message);
        }

        // create an Image Cropper for each passed file
        function createCropper(file, onComplete) {
            // create container element for cropper
            let holder = document.getElementById('new-images');
            const imageIndex = created_id;

            let col = document.createElement('div');
            col.className = 'col-lg-4 col-md-6 animated fadeIn mb-3 product-photo-card product-photo-card-new';

            let cropper = document.createElement('div');

            // insert this element after the file drop area
            col.insertAdjacentElement('afterbegin', cropper);
            col.insertAdjacentHTML('beforeend', '<div class="product-photo-new-controls">\n' +
                '                                    <label>Redoslijed<input type="number" min="0" class="form-control" name="files[' + imageIndex + '][sort_order]" value="' + blocks + '"></label>\n' +
                '                                    <label class="custom-control custom-radio mb-0">\n' +
                '                                        <input type="radio" class="custom-control-input" id="new-main-photo-' + imageIndex + '" name="files[default]" value="' + imageIndex + '">\n' +
                '                                        <span class="custom-control-label">Postavi kao glavnu</span>\n' +
                '                                    </label>\n' +
                '                                </div>');

            holder.insertAdjacentElement('beforeend', col);

            // create a Slim Cropper
            Slim.create(cropper, {
                ratio: 'free',
                size: '1600,2000',
                internalCanvasSize: { width: 2048, height: 2560 },
                internalCanvasSizeLowMemory: { width: 1600, height: 2000 },
                maxFileSize: 8,
                forceType: 'jpg',
                jpegCompression: 82,
                service: false,
                meta: {
                    type: 'products',
                    type_id: "{{ isset($product) ? $product->id : '' }}",
                    image_id: 0
                },
                defaultInputName: 'files[' + imageIndex + '][image]',
                didInit: function() {
                    // load the file to our slim cropper
                    this.load(file, function (error) {
                        if (error) {
                            this.destroy();
                            col.remove();

                            if (error === 'file-too-big') {
                                showPhotoError('Fotografija smije imati najviše 8 MB.');
                            } else {
                                showPhotoError('Fotografiju nije moguće učitati. Provjerite format datoteke.');
                            }
                        }

                        if (typeof onComplete === 'function') {
                            onComplete();
                        }
                    });

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
