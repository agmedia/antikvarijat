@push('product_css')
    <style>
        .fileContainer {
            overflow: hidden;
            position: relative;
        }

        .fileContainer [type=file] {
            cursor: inherit;
            display: block;
            font-size: 999px;
            filter: alpha(opacity=0);
            min-height: 34px;
            min-width: 100%;
            opacity: 0;
            position: absolute;
            right: 0;
            text-align: right;
            top: 0;
        }

        .fileContainer {
            background: #E3E3E3;
            float: left;
            padding: .5em 1.5rem;
            height: 34px;
        }

        .fileContainer [type=file] {
            cursor: pointer;
        }

        img.preview {
            width: 200px;
            background-color: white;
            border: 1px solid #DDD;
            padding: 5px;
        }
    </style>
@endpush

<div>
    <div class="row">
        <div class="col-12">
            <div class="file-drop-area">
                <label for="files" style="display: block;padding: 1rem 2rem;border: 1px solid #CCCCCC;background-color: #eee;text-align: center;cursor: pointer;">Odaberite fotografiju proizvoda... Ili više njih...</label>
                <input name="files[][image]" id="files" type="file" multiple>
            </div>
        </div>
    </div>

    <div class="row items-push" id="sortable">
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
                        <div class="alert alert-info mb-3">
                            Postojeće slike učitavaju se tek kad otvoriš ovaj tab, da se edit artikla otvori brže.
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
        fileDropArea.addEventListener('drop', handleDrop);
        fileInput.addEventListener('change', handleFileSelect);

        function handleDragOver(e) {
            e.preventDefault();
        }
        function handleDrop(e) {
            e.preventDefault();
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
            col.className = 'col-lg-3 col-md-4 animated fadeIn mb-5 p-3 ribbon ribbon-left ribbon-bookmark ribbon-crystal';

            let cropper = document.createElement('div');

            // insert this element after the file drop area
            col.insertAdjacentElement('afterbegin', cropper);
            col.insertAdjacentHTML('beforeend', '<div class="row form-group mt-2">\n' +
                '                                    <div class="col-sm-4" style="padding-right: 0;">\n' +
                '                                        <input type="text" class="form-control js-tooltip-enabled" name="files[' + created_id + '][sort_order]" value="' + blocks + '" data-toggle="tooltip" data-placement="top" title="Sort Order">\n' +
                '                                    </div>\n' +
                '                                    <div class="col-sm-8 text-right">\n' +
                '                                        <label class="css-control css-control-primary css-radio mt-2">\n' +
                '                                            <input type="radio" class="css-control-input" name="files[default]" value="image/' + file.name + '">\n' +
                '                                            <span class="mr-2">Default</span> <span class="css-control-indicator"></span>\n' +
                '                                        </label>\n' +
                '                                    </div>\n' +
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

            console.log(fileInput)
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
                    <div class="col-12">
                        <div class="alert alert-secondary mb-3">Učitavam postojeće slike...</div>
                    </div>
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
                        <div class="col-12">
                            <div class="alert alert-warning mb-3">Postojeće slike se trenutno ne mogu učitati. Pokušaj ponovo otvoriti tab.</div>
                        </div>
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
