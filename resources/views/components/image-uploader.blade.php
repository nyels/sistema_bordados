<div class="border-2 border-dashed rounded-2xl p-6 text-center transition
            hover:border-black hover:bg-gray-50"
    id="dropzone">

    <input type="file" name="imagenes[]" id="imageInput" class="hidden" accept="image/*" multiple>

    <div class="space-y-3 cursor-pointer" onclick="document.getElementById('imageInput').click()">

        <i class="fas fa-cloud-upload-alt text-3xl text-gray-400"></i>

        <p class="text-sm text-gray-600">
            Arrastra imágenes aquí o haz clic para seleccionar
        </p>

        <p class="text-xs text-gray-400">
            JPG, PNG — Máx 5MB
        </p>
    </div>

</div>

<div id="previewContainer" class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-6"></div>
