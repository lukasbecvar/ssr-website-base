document.addEventListener('DOMContentLoaded', function() {
    // check if hash in the URL is set (coming from foreign key link)
    if (window.location.hash) {
        const targetId = window.location.hash.substring(1) // remove #
        
        // look for target row
        const targetRow = document.getElementById(targetId)
        if (targetRow) {
            const thElements = targetRow.getElementsByTagName('th')
            for (let j = 0; j < thElements.length; j++) {
                thElements[j].classList.add('highlighter')
            }
            
            // scroll to target row
            targetRow.scrollIntoView({
                block: 'center'
            })
        }
    }
})
