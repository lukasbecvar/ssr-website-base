/* account settings page script */
document.addEventListener('DOMContentLoaded', function() {
    // profile picture upload logic
    const wrapper = document.querySelector('.settings-form-pic-upload')
    
    if (wrapper) {
        const picInputId = wrapper.dataset.inputId
        const picInput = document.getElementById(picInputId)
        const dropzone = document.getElementById('pic-dropzone')
        const picPreview = document.getElementById('pic-preview')
        const removeBtn = document.getElementById('pic-remove-btn')
        const previewContainer = document.getElementById('pic-preview-container')

        if (picInput && previewContainer && picPreview && removeBtn && dropzone) {
            
            // when a file is selected
            picInput.addEventListener('change', function(event) {
                const file = event.target.files[0]
                if (file) {
                    const reader = new FileReader()
                    reader.onload = function(e) {
                        picPreview.src = e.target.result
                        previewContainer.style.display = 'block'
                        dropzone.style.display = 'none'
                    }
                    reader.readAsDataURL(file)
                }
            })

            // when the remove button is clicked
            removeBtn.addEventListener('click', function() {
                picInput.value = '' // clear the file input
                picPreview.src = '#'
                previewContainer.style.display = 'none'
                dropzone.style.display = 'flex'
            })
        }
    }
})
